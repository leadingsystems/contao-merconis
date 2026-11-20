<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Email;
use Contao\Idna;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReasonFormatter;
use Merconis\Core\ls_shop_languageHelper;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Meldet gescheiterte E-Rechnungs-Erzeugungen je nach Einstiegspunkt
 * (`spec/product/27-e-invoicing.md`, Abschnitt 3.6):
 *
 * - Nachrichtenversand: `tl_log` plus Händlerbenachrichtigung in der
 *   Fallback-Sprache des Shops.
 * - Manuelle Erzeugung: nur `tl_log`; die Console-Ausgabe übernimmt der
 *   CLI-Befehl über `formatFailureReasons()`.
 */
class EInvoicingFailureReporter
{
    /**
     * Sprache der Fehlerbeschreibungen in `tl_log` und Console-Ausgabe.
     */
    private const SYSTEM_OUTPUT_LANGUAGE = 'en';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $contaoLogger,
        private readonly EInvoicingFailureReasonFormatter $failureReasonFormatter,
        private readonly EInvoicingMessageTypeContext $messageTypeContext,
        private readonly Connection $connection,
    ) {
    }

    /**
     * Einstiegspunkt Nachrichtenversand (Dynamic-Attachment-Script).
     *
     * @param array<string, mixed> $orderData
     * @param string|null $assignedInvoiceNumber Vom Nachrichtenversand vergebene Rechnungsnummer (`counterNr`), sofern vorhanden
     */
    public function reportMessageDispatchFailure(array $orderData, Throwable $throwable, ?string $assignedInvoiceNumber): void
    {
        $this->framework->initialize();

        $orderNumber = $this->resolveOrderNumber($orderData);
        $invoiceNumber = $this->normalizeOptionalValue($assignedInvoiceNumber);
        $fallbackLanguage = (string) $this->framework->getAdapter(ls_shop_languageHelper::class)->getFallbackLanguage();
        $messageType = $this->resolveMessageType((int) ($orderData['id'] ?? 0), $fallbackLanguage);

        $this->contaoLogger->error(
            sprintf(
                'MERCONIS E-INVOICING: Failed to generate the e-invoice for order "%s". Entry point: message dispatch. Message type: %s. Failure reasons: %s',
                $orderNumber,
                $messageType !== null
                    ? sprintf('%s (%d)', $messageType['title'], $messageType['id'])
                    : 'could not be determined',
                implode(' | ', $this->formatFailureReasons($throwable))
            ),
            ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)]
        );

        $this->notifyMerchant($orderNumber, $messageType, $invoiceNumber, $throwable, $fallbackLanguage);
    }

    /**
     * Einstiegspunkt manuelle Erzeugung (CLI-Befehl). Schreibt nur den
     * `tl_log`-Eintrag; es wird keine Händlerbenachrichtigung versendet.
     */
    public function reportManualGenerationFailure(string $orderNumber, Throwable $throwable): void
    {
        $this->contaoLogger->error(
            sprintf(
                'MERCONIS E-INVOICING: Failed to generate the e-invoice for order "%s". Entry point: manual generation. Failure reasons: %s',
                $this->normalizeOptionalValue($orderNumber) ?? 'n/a',
                implode(' | ', $this->formatFailureReasons($throwable))
            ),
            ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)]
        );
    }

    /**
     * Liefert jeden Fehlergrund einzeln als `[<CODE>] <Beschreibung>`
     * in der Sprache von `tl_log` und Console-Ausgabe.
     *
     * @return list<string>
     */
    public function formatFailureReasons(Throwable $throwable): array
    {
        return $this->failureReasonFormatter->formatThrowable($throwable, self::SYSTEM_OUTPUT_LANGUAGE);
    }

    /**
     * Versendet eine Text-E-Mail; eigene Methode, damit Tests den
     * Versand ohne Contao-Mailer beobachten können.
     */
    protected function sendEmail(string $recipient, string $subject, string $text): void
    {
        $email = new Email();
        $email->from = $recipient;
        $email->subject = $subject;
        $email->text = $text;
        $email->sendTo($recipient);
    }

    /**
     * @param array{id: int, title: string}|null $messageType
     */
    private function notifyMerchant(
        string $orderNumber,
        ?array $messageType,
        ?string $invoiceNumber,
        Throwable $throwable,
        string $notificationLanguage,
    ): void {
        $merchantEmailAddress = $this->resolveMerchantEmailAddress();

        if ($merchantEmailAddress === null) {
            $this->contaoLogger->error(
                'MERCONIS E-INVOICING: Merchant notification could not be sent because no valid shop email address is configured.',
                ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)]
            );

            return;
        }

        $notificationTexts = $this->failureReasonFormatter->loadLanguageStrings($notificationLanguage)['einvoicingMerchantNotification'];
        $placeholderValues = [
            'orderNumber' => $orderNumber,
            'invoiceNumber' => $invoiceNumber ?? '',
            'messageTypeTitle' => $messageType['title'] ?? '',
            'messageTypeId' => isset($messageType['id']) ? (string) $messageType['id'] : '',
        ];

        $textLines = [
            $notificationTexts['introduction'],
            '',
            $notificationTexts['orderNumber'],
            $messageType !== null ? $notificationTexts['messageType'] : $notificationTexts['messageTypeUnknown'],
        ];

        if ($invoiceNumber !== null) {
            $textLines[] = $notificationTexts['invoiceNumber'];
        }

        $textLines[] = '';
        $textLines[] = $notificationTexts['failureReasons'];

        foreach ($this->failureReasonFormatter->formatThrowable($throwable, $notificationLanguage) as $formattedFailureReason) {
            $textLines[] = $formattedFailureReason;
        }

        $textLines[] = '';
        $textLines[] = $invoiceNumber !== null
            ? $notificationTexts['regenerationWithInvoiceNumber']
            : $notificationTexts['regenerationWithoutInvoiceNumber'];

        try {
            $this->sendEmail(
                $merchantEmailAddress,
                $this->replacePlaceholders($notificationTexts['subject'], $placeholderValues),
                $this->replacePlaceholders(implode("\n", $textLines), $placeholderValues)
            );
        } catch (Throwable $sendingFailure) {
            $this->contaoLogger->error(
                sprintf(
                    'MERCONIS E-INVOICING: Merchant notification for order "%s" could not be sent. %s',
                    $orderNumber,
                    implode(' | ', $this->formatFailureReasons($sendingFailure))
                ),
                ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)]
            );
        }
    }

    /**
     * Ermittelt den Nachrichtentyp aus dem vom Hook hinterlegten Kontext.
     * Die Bezeichnung stammt aus der Sprachspalte `title_<sprache>` von
     * `tl_ls_shop_message_type`. Ohne Kontext, ohne Datensatz oder ohne
     * Bezeichnung gilt der Nachrichtentyp als nicht ermittelbar.
     *
     * @return array{id: int, title: string}|null
     */
    private function resolveMessageType(int $orderId, string $language): ?array
    {
        $messageTypeId = $this->messageTypeContext->getMessageTypeIdForOrder($orderId);

        if ($messageTypeId === null) {
            return null;
        }

        $messageTypeRecord = $this->connection->fetchAssociative(
            'SELECT * FROM tl_ls_shop_message_type WHERE id = ?',
            [$messageTypeId]
        );

        if ($messageTypeRecord === false) {
            return null;
        }

        $messageTypeTitle = $this->normalizeOptionalValue($messageTypeRecord['title_' . $language] ?? null);

        if ($messageTypeTitle === null) {
            return null;
        }

        return ['id' => $messageTypeId, 'title' => $messageTypeTitle];
    }

    /**
     * @param array<string, string> $placeholderValues
     */
    private function replacePlaceholders(string $text, array $placeholderValues): string
    {
        $placeholderReplacements = [];

        foreach ($placeholderValues as $placeholderName => $placeholderValue) {
            $placeholderReplacements['{' . $placeholderName . '}'] = $placeholderValue;
        }

        return strtr($text, $placeholderReplacements);
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private function resolveOrderNumber(array $orderData): string
    {
        return $this->normalizeOptionalValue($orderData['orderNr'] ?? null)
            ?? $this->normalizeOptionalValue($orderData['id'] ?? null)
            ?? 'n/a';
    }

    private function resolveMerchantEmailAddress(): ?string
    {
        $this->framework->initialize();
        $config = $this->framework->getAdapter(Config::class);
        $merchantEmailAddress = trim((string) $config->get('ls_shop_ownEmailAddress'));

        if ($merchantEmailAddress === '') {
            return null;
        }

        if (!Validator::isEmail(Idna::encodeEmail($merchantEmailAddress))) {
            return null;
        }

        return $merchantEmailAddress;
    }

    private function normalizeOptionalValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue !== '' ? $normalizedValue : null;
    }
}
