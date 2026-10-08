<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;

final class OrderConfirmationMessageAugmenter
{
    public const DYNAMIC_ATTACHMENT_PATH = 'vendor/leadingsystems/contao-merconis/src/Resources/contao/classes/dynamicAttachment_legalGuaranteeLabels_01.php';

    public function __construct(
        private readonly OrderConfirmationAttachmentGenerator $attachmentGenerator,
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
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

        $translations = $this->getTranslations();
        $htmlSections = [];
        $rawLines = [];

        if ('' !== trim((string) ($order['gllLanguage'] ?? ''))) {
            $gllEuUrl = $this->assetLocator->getGllEuUrlByLanguage(
                (string) $order['gllLanguage'],
                is_string($order['gllVersion'] ?? null) ? (string) $order['gllVersion'] : null
            );
            $htmlSections[] = sprintf(
                '<p><a href="%s" target="_blank" rel="noreferrer noopener">%s</a></p>',
                htmlspecialchars($gllEuUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($translations['gllEuLinkText'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
            $rawLines[] = $translations['gllEuLinkText'] . ': ' . $gllEuUrl;
        }

        $garanItems = $this->collectGaranItems($order);
        if ([] !== $garanItems) {
            $htmlItems = [];
            $rawLines[] = $translations['garanBlockHeadline'];

            foreach ($garanItems as $garanItem) {
                $garanItemLabel = $garanItem['label'];
                $garanEuUrl = $this->assetLocator->resolveGaranEuUrl($garanItem['version']);
                $escapedLabel = htmlspecialchars($garanItemLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedLinkText = htmlspecialchars($translations['garanEuLinkText'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $escapedUrl = htmlspecialchars($garanEuUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

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
                    $garanEuUrl
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
     * @return list<array{label: string, version: ?string}>
     */
    private function collectGaranItems(array $order): array
    {
        $items = [];

        foreach ($order['items'] ?? [] as $item) {
            if (!is_array($item) || '' === trim((string) ($item['garanVersion'] ?? ''))) {
                continue;
            }

            $items[] = [
                'label' => $this->buildItemLabel($item),
                'version' => is_string($item['garanVersion'] ?? null) ? (string) $item['garanVersion'] : null,
            ];
        }

        return $items;
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
    private function getTranslations(): array
    {
        return [
            'gllEuLinkText' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee']['gllEuLinkText'],
            'garanBlockHeadline' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee']['garanBlockHeadline'],
            'garanEuLinkText' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee']['garanEuLinkText'],
        ];
    }

    private function appendHtmlSection(string $bodyHtml, string $appendix): string
    {
        if ('' === trim($appendix)) {
            return $bodyHtml;
        }

        if ('' === trim($bodyHtml)) {
            return $appendix;
        }

        foreach (['</body>', '</html>'] as $closingTag) {
            $closingTagPosition = strripos($bodyHtml, $closingTag);

            if (false === $closingTagPosition) {
                continue;
            }

            return substr($bodyHtml, 0, $closingTagPosition)
                . "\n"
                . $appendix
                . "\n"
                . substr($bodyHtml, $closingTagPosition);
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
