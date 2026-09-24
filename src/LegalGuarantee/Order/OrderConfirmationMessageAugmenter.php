<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

final class OrderConfirmationMessageAugmenter
{
    public const DYNAMIC_ATTACHMENT_PATH = 'vendor/leadingsystems/contao-merconis/src/Resources/contao/classes/dynamicAttachment_legalGuaranteeLabels_01.php';

    private const GLL_EU_URLS = [
        'de' => 'https://europa.eu/youreurope/garantien',
        'en' => 'https://europa.eu/youreurope/business/dealing-with-customers/consumer-contracts-guarantees/eu-legal-guarantee-notice-and-garan-label/index_en.htm',
    ];
    private const GARAN_EU_URL = 'https://europa.eu/youreurope/commercial-guarantee-durability/index.htm';

    public function __construct(
        private readonly OrderConfirmationAttachmentGenerator $attachmentGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $messagePayload
     * @param array<string, mixed> $messageType
     * @param array<string, mixed>|null $order
     *
     * @return array<string, mixed>
     */
    public function enhancePayload(
        array $messagePayload,
        array $messageType,
        ?array $order,
        string $language
    ): array {
        if (
            !is_array($order)
            || ($messageType['sendWhen'] ?? '') !== 'asOrderConfirmation'
            || !$this->attachmentGenerator->hasRelevantAttachments($order)
        ) {
            return $messagePayload;
        }

        $translations = $this->getTranslations($language);
        $htmlSections = [];
        $rawLines = [];

        if ('' !== trim((string) ($order['gllLanguage'] ?? ''))) {
            $gllEuUrl = $this->resolveGllEuUrl((string) $order['gllLanguage']);
            $htmlSections[] = sprintf(
                '<p><a href="%s" target="_blank" rel="noreferrer noopener">%s</a></p>',
                htmlspecialchars($gllEuUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($translations['gllEuLinkText'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
            $rawLines[] = $translations['gllEuLinkText'] . ': ' . $gllEuUrl;
        }

        $garanItems = $this->collectGaranItemLabels($order);
        if ([] !== $garanItems) {
            $htmlItems = [];
            $rawLines[] = $translations['garanBlockHeadline'];

            foreach ($garanItems as $garanItemLabel) {
                $escapedLabel = htmlspecialchars($garanItemLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedLinkText = htmlspecialchars($translations['garanEuLinkText'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedUrl = htmlspecialchars(self::GARAN_EU_URL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $htmlItems[] = sprintf(
                    '<li>%s: <a href="%s" target="_blank" rel="noreferrer noopener">%s</a></li>',
                    $escapedLabel,
                    $escapedUrl,
                    $escapedLinkText
                );
                $rawLines[] = sprintf(
                    '- %s: %s (%s)',
                    $garanItemLabel,
                    $translations['garanEuLinkText'],
                    self::GARAN_EU_URL
                );
            }

            $htmlSections[] = sprintf(
                '<p>%s</p><ul>%s</ul>',
                htmlspecialchars($translations['garanBlockHeadline'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                implode('', $htmlItems)
            );
        }

        if ([] === $htmlSections && [] === $rawLines) {
            return $messagePayload;
        }

        $messagePayload['bodyHTML'] = $this->appendHtmlSection(
            (string) ($messagePayload['bodyHTML'] ?? ''),
            implode('', $htmlSections)
        );
        $messagePayload['bodyRawtext'] = $this->appendRawtextSection(
            (string) ($messagePayload['bodyRawtext'] ?? ''),
            implode("\n", $rawLines)
        );

        $dynamicAttachmentPaths = $messagePayload['dynamicAttachmentPaths'] ?? [];
        if (!is_array($dynamicAttachmentPaths)) {
            $dynamicAttachmentPaths = [];
        }

        if (!in_array(self::DYNAMIC_ATTACHMENT_PATH, $dynamicAttachmentPaths, true)) {
            $dynamicAttachmentPaths[] = self::DYNAMIC_ATTACHMENT_PATH;
        }

        $messagePayload['dynamicAttachmentPaths'] = $dynamicAttachmentPaths;

        return $messagePayload;
    }

    /**
     * @param array<string, mixed> $order
     * @return list<string>
     */
    private function collectGaranItemLabels(array $order): array
    {
        $labels = [];

        foreach ($order['items'] ?? [] as $item) {
            if (!is_array($item) || '' === trim((string) ($item['garanVersion'] ?? ''))) {
                continue;
            }

            $labels[] = $this->buildItemLabel($item);
        }

        return $labels;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildItemLabel(array $item): string
    {
        $extendedInfo = $item['extendedInfo'] ?? [];
        $productTitle = is_array($extendedInfo)
            ? trim((string) ($extendedInfo['_productTitle_customerLanguage'] ?? ''))
            : '';
        $variantTitle = is_array($extendedInfo) && !empty($item['isVariant'])
            ? trim((string) ($extendedInfo['_title_customerLanguage'] ?? ''))
            : '';

        if ('' === $productTitle) {
            $productTitle = trim((string) ($item['productTitle'] ?? ''));
        }

        if ('' === $variantTitle) {
            $variantTitle = trim((string) ($item['variantTitle'] ?? ''));
        }

        if ('' !== $variantTitle && $variantTitle !== $productTitle) {
            return sprintf('%s (%s)', $productTitle, $variantTitle);
        }

        return $productTitle;
    }

    /**
     * @return array{gllEuLinkText: string, garanBlockHeadline: string, garanEuLinkText: string}
     */
    private function getTranslations(string $language): array
    {
        $languageKey = str_starts_with($language, 'de') ? 'de' : 'en';
        $translations = $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'][$languageKey] ?? [];

        if ('de' === $languageKey) {
            return [
                'gllEuLinkText' => $translations['gllEuLinkText'] ?? 'EU-Information zur gesetzlichen Gewährleistung',
                'garanBlockHeadline' => $translations['garanBlockHeadline'] ?? 'Herstellergarantie für folgende Artikel:',
                'garanEuLinkText' => $translations['garanEuLinkText'] ?? 'EU-Information zur Herstellergarantie',
            ];
        }

        return [
            'gllEuLinkText' => $translations['gllEuLinkText'] ?? 'EU information about the legal guarantee',
            'garanBlockHeadline' => $translations['garanBlockHeadline'] ?? 'Manufacturer\'s commercial guarantee for the following items:',
            'garanEuLinkText' => $translations['garanEuLinkText'] ?? 'EU information about the manufacturer\'s commercial guarantee',
        ];
    }

    private function resolveGllEuUrl(string $officialLanguage): string
    {
        return self::GLL_EU_URLS[$officialLanguage] ?? self::GLL_EU_URLS['en'];
    }

    private function appendHtmlSection(string $bodyHtml, string $appendix): string
    {
        if ('' === trim($appendix)) {
            return $bodyHtml;
        }

        if ('' === trim($bodyHtml)) {
            return $appendix;
        }

        return $bodyHtml . "\n" . $appendix;
    }

    private function appendRawtextSection(string $bodyRawtext, string $appendix): string
    {
        if ('' === trim($appendix)) {
            return $bodyRawtext;
        }

        if ('' === trim($bodyRawtext)) {
            return $appendix;
        }

        return rtrim($bodyRawtext) . "\n\n" . $appendix;
    }
}
