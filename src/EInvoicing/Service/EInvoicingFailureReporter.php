<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Email;
use Contao\Idna;
use Contao\Validator;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use Psr\Log\LoggerInterface;
use Throwable;

class EInvoicingFailureReporter
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $contaoLogger,
    ) {
    }

    /**
     * @param array<string, mixed> $orderData
     */
    public function reportFailureForOrder(array $orderData, Throwable $throwable, string $trigger): void
    {
        $orderNumber = $this->resolveOrderNumber($orderData);

        $this->reportFailure($orderNumber, $throwable, $trigger);
    }

    public function reportFailure(string $orderNumber, Throwable $throwable, string $trigger): void
    {
        $normalizedOrderNumber = $this->normalizeOrderNumber($orderNumber);
        $failureReason = $this->formatFailureReason($throwable);

        $this->contaoLogger->error(
            sprintf(
                'MERCONIS E-INVOICING: Failed to generate the e-invoice for order "%s" during %s. %s',
                $normalizedOrderNumber,
                $trigger,
                $failureReason
            ),
            ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)]
        );

        $this->notifyMerchant($normalizedOrderNumber, $failureReason, $trigger);
    }

    public function formatFailureReason(Throwable $throwable): string
    {
        if ($throwable instanceof InvoiceValidationException) {
            $violations = array_values(array_filter(
                array_map('trim', $throwable->violations),
                static fn (string $violation): bool => $violation !== ''
            ));

            if ($violations !== []) {
                return implode(' | ', $violations);
            }

            return 'The order data is incomplete for EN 16931 export.';
        }

        $message = trim($throwable->getMessage());

        if ($message !== '') {
            return $message;
        }

        return 'An unspecified error occurred.';
    }

    private function notifyMerchant(string $orderNumber, string $failureReason, string $trigger): void
    {
        $merchantEmailAddress = $this->resolveMerchantEmailAddress();

        if ($merchantEmailAddress === null) {
            $this->contaoLogger->error(
                'MERCONIS E-INVOICING: Merchant notification could not be sent because no valid shop email address is configured.',
                ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)]
            );

            return;
        }

        $email = new Email();
        $email->from = $merchantEmailAddress;
        $email->subject = 'E-invoice generation failed for order ' . $orderNumber;
        $email->text = implode("\n", [
            'Order number: ' . $orderNumber,
            'Trigger: ' . $trigger,
            'Reason: ' . $failureReason,
            'Action required: Please correct the order or configuration data and generate the e-invoice manually again.',
        ]);

        try {
            $email->sendTo($merchantEmailAddress);
        } catch (Throwable $throwable) {
            $this->contaoLogger->error(
                sprintf(
                    'MERCONIS E-INVOICING: Merchant notification for order "%s" could not be sent. %s',
                    $orderNumber,
                    $this->formatFailureReason($throwable)
                ),
                ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)]
            );
        }
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private function resolveOrderNumber(array $orderData): string
    {
        $orderNumber = $orderData['orderNr'] ?? $orderData['id'] ?? 'n/a';

        return is_scalar($orderNumber) ? (string) $orderNumber : 'n/a';
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

    private function normalizeOrderNumber(string $orderNumber): string
    {
        $normalizedOrderNumber = trim($orderNumber);

        return $normalizedOrderNumber !== '' ? $normalizedOrderNumber : 'n/a';
    }
}
