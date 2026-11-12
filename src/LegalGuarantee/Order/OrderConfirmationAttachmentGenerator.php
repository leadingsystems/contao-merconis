<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderConfirmationAttachmentGenerator
{
    private const GLL_BUNDLE_PATH_PATTERN = 'bundles/leadingsystemsmerconis/legal-guarantee/gll/%s/legal-guarantee-notice-%s.pdf';

    public function __construct(
        private readonly OrderLabelSnapshotRenderer $snapshotRenderer,
        private readonly GaranPdfAttachmentWriterInterface $garanPdfAttachmentWriter,
        private readonly GaranLabelGrouping $garanLabelGrouping,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $contaoErrorLogger,
    ) {
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array{relativePaths: list<string>}
     */
    public function generateAttachments(array $order): array
    {
        $relativePaths = [];

        try {
            $gllPdfLanguage = $this->snapshotRenderer->resolveGllPdfLanguage($order);
            $gllVersion = trim((string) ($order['gllVersion'] ?? ''));

            if (null !== $gllPdfLanguage && '' !== $gllVersion) {
                $relativePaths[] = sprintf(self::GLL_BUNDLE_PATH_PATTERN, $gllVersion, $gllPdfLanguage);
            }
        } catch (Throwable) {
            // GLL-Ausfall darf den Versand der restlichen Anhänge nicht blockieren.
        }

        $garanPages = [];

        foreach ($this->collectGroupedGaranItems($order['items'] ?? []) as $garanGroup) {
            try {
                $renderedSvg = $this->snapshotRenderer->renderGaranLabel([
                    'garanVersion' => $garanGroup['garanVersion'],
                    'garanBrand' => $garanGroup['brand'],
                    'garanModelIdentifier' => $garanGroup['modelIdentifier'],
                    'garanDurationMonths' => $garanGroup['durationMonths'],
                ]);

                $garanPages[] = [
                    'type' => 'label',
                    'svgMarkup' => $renderedSvg,
                    'productNames' => $garanGroup['productNames'],
                    'noticeText' => $this->resolveGaranUnavailableNoticeText($order),
                    'orderNumber' => $this->resolveOrderNumberForLogging($order),
                    'articleNumbers' => $garanGroup['articleNumbers'],
                    'euLinkText' => $this->resolveGaranEuLinkText($order),
                    'euLinkUrl' => $this->snapshotRenderer->resolveGaranEuUrl($garanGroup['garanVersion']),
                ];
            } catch (Throwable $exception) {
                $this->logUnavailableLabel($order, $garanGroup['articleNumbers'], $exception);
                $garanPages[] = [
                    'type' => 'notice',
                    'productNames' => $garanGroup['productNames'],
                    'noticeText' => $this->resolveGaranUnavailableNoticeText($order),
                    'orderNumber' => $this->resolveOrderNumberForLogging($order),
                    'articleNumbers' => $garanGroup['articleNumbers'],
                ];
            }
        }

        if ([] !== $garanPages) {
            try {
                $writtenAttachment = $this->garanPdfAttachmentWriter->write(
                    $garanPages,
                    $this->resolveOrderNumberForFileName($order),
                    $this->resolveOrderDateUnixTimestamp($order)
                );

                $relativePaths[] = $writtenAttachment['relativePath'];
            } catch (Throwable) {
                // Ein defektes GARAN-PDF darf übrige Anhänge nicht verhindern.
            }
        }

        return [
            'relativePaths' => array_values(array_unique($relativePaths)),
        ];
    }

    /**
     * @param mixed $items
     * @return list<array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   productNames: list<string>,
     *   articleNumbers: list<string>
     * }>
     */
    private function collectGroupedGaranItems(mixed $items): array
    {
        if (!is_iterable($items)) {
            return [];
        }

        $entries = [];

        foreach ($items as $item) {
            if (!$this->isGaranSnapshotItem($item)) {
                continue;
            }

            $entries[] = [
                'productId' => $this->extractSnapshotProductId($item),
                'garanVersion' => trim((string) ($item['garanVersion'] ?? '')),
                'brand' => trim((string) ($item['garanBrand'] ?? '')),
                'modelIdentifier' => trim((string) ($item['garanModelIdentifier'] ?? '')),
                'durationMonths' => trim((string) ($item['garanDurationMonths'] ?? '')),
                'productName' => $this->buildOrderProductName($item),
                'articleNumber' => $this->extractArticleNumber($item),
            ];
        }

        $articleNumbersByGroupKey = [];

        foreach ($entries as $entry) {
            $groupKey = $this->buildGaranGroupingKey(
                $entry['productId'],
                $entry['garanVersion'],
                $entry['brand'],
                $entry['modelIdentifier'],
                $entry['durationMonths']
            );

            $articleNumbersByGroupKey[$groupKey][] = $entry['articleNumber'];
        }

        $groupedEntries = $this->garanLabelGrouping->groupEntries(array_map(
            static fn (array $entry): array => [
                'productId' => $entry['productId'],
                'garanVersion' => $entry['garanVersion'],
                'brand' => $entry['brand'],
                'modelIdentifier' => $entry['modelIdentifier'],
                'durationMonths' => $entry['durationMonths'],
                'productName' => $entry['productName'],
            ],
            $entries
        ));

        return array_map(function (array $group) use ($articleNumbersByGroupKey): array {
            $groupKey = $this->buildGaranGroupingKey(
                (string) $group['productId'],
                (string) $group['garanVersion'],
                (string) $group['brand'],
                (string) $group['modelIdentifier'],
                (string) $group['durationMonths']
            );

            $group['articleNumbers'] = array_values(array_filter(
                $articleNumbersByGroupKey[$groupKey] ?? [],
                static fn (string $articleNumber): bool => $articleNumber !== ''
            ));

            return $group;
        }, $groupedEntries);
    }

    /**
     * @param mixed $item
     */
    private function isGaranSnapshotItem(mixed $item): bool
    {
        if (!is_array($item)) {
            return false;
        }

        return '' !== trim((string) ($item['garanVersion'] ?? ''))
            && '' !== trim((string) ($item['garanBrand'] ?? ''))
            && '' !== trim((string) ($item['garanModelIdentifier'] ?? ''))
            && '' !== trim((string) ($item['garanDurationMonths'] ?? ''));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function getProductTitle(array $item): string
    {
        $extendedInfo = $item['extendedInfo'] ?? [];

        if (is_array($extendedInfo) && '' !== trim((string) ($extendedInfo['_productTitle_customerLanguage'] ?? ''))) {
            return trim((string) $extendedInfo['_productTitle_customerLanguage']);
        }

        return trim((string) ($item['productTitle'] ?? 'garan-item'));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function getVariantTitle(array $item): string
    {
        $extendedInfo = $item['extendedInfo'] ?? [];

        if (is_array($extendedInfo) && !empty($item['isVariant'])) {
            $variantTitle = trim((string) ($extendedInfo['_title_customerLanguage'] ?? ''));

            if ('' !== $variantTitle) {
                return $variantTitle;
            }
        }

        return trim((string) ($item['variantTitle'] ?? ''));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildOrderProductName(array $item): string
    {
        $productTitle = $this->getProductTitle($item);
        $variantTitle = $this->getVariantTitle($item);
        $articleNumber = $this->extractArticleNumber($item);
        $productName = $productTitle;

        if ('' !== $variantTitle) {
            $productName = sprintf('%s (%s)', $productTitle, $variantTitle);
        }

        if ('' === $articleNumber) {
            return $productName;
        }

        return sprintf('%s - %s', $productName, $articleNumber);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function extractSnapshotProductId(array $item): string
    {
        $productVariantId = trim((string) ($item['productVariantID'] ?? $item['productVariantId'] ?? ''));

        if ($productVariantId !== '') {
            $parts = explode('-', $productVariantId, 2);

            return trim($parts[0]);
        }

        return trim((string) ($item['productID'] ?? $item['productId'] ?? $item['id'] ?? ''));
    }

    /**
     * @param array<string, mixed> $order
     */
    private function resolveGaranEuLinkText(array $order): string
    {
        $customerLanguage = trim((string) ($order['customerLanguage'] ?? ''));
        $locale = $customerLanguage !== '' ? $customerLanguage : null;

        $translatedValue = $this->translator->trans(
            'MSC.ls_shop.legalGuarantee.garanEuLinkText',
            [],
            'contao_default',
            $locale
        );

        return trim($translatedValue);
    }

    /**
     * @param array<string, mixed> $order
     */
    private function resolveOrderNumberForFileName(array $order): string
    {
        $orderNumber = trim((string) ($order['orderNr'] ?? ''));

        if ($orderNumber !== '') {
            return $orderNumber;
        }

        $orderId = trim((string) ($order['id'] ?? ''));

        return $orderId !== '' ? $orderId : 'order';
    }

    /**
     * @param array<string, mixed> $order
     */
    private function resolveOrderDateUnixTimestamp(array $order): int
    {
        return (int) ($order['orderDateUnixTimestamp'] ?? 0);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function extractArticleNumber(array $item): string
    {
        return trim((string) ($item['artNr'] ?? ''));
    }

    private function buildGaranGroupingKey(
        string $productId,
        string $garanVersion,
        string $brand,
        string $modelIdentifier,
        string $durationMonths
    ): string {
        return json_encode([
            trim($productId),
            trim($garanVersion),
            trim($brand),
            trim($modelIdentifier),
            trim($durationMonths),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $order
     */
    private function resolveGaranUnavailableNoticeText(array $order): string
    {
        $customerLanguage = trim((string) ($order['customerLanguage'] ?? ''));
        $locale = $customerLanguage !== '' ? $customerLanguage : null;

        return trim($this->translator->trans(
            'MSC.ls_shop.legalGuarantee.garanLabelUnavailableNotice',
            [],
            'contao_default',
            $locale
        ));
    }

    /**
     * @param array<string, mixed> $order
     * @param list<string> $articleNumbers
     */
    private function logUnavailableLabel(array $order, array $articleNumbers, Throwable $exception): void
    {
        $normalisedArticleNumbers = array_values(array_filter(array_map(
            static fn (string $articleNumber): string => trim($articleNumber),
            $articleNumbers
        ), static fn (string $articleNumber): bool => $articleNumber !== ''));

        $this->contaoErrorLogger->error(sprintf(
            'GARAN label generation failed for order %s and article numbers [%s]: %s',
            $this->resolveOrderNumberForLogging($order),
            [] !== $normalisedArticleNumbers ? implode(', ', $normalisedArticleNumbers) : 'unknown',
            $exception->getMessage()
        ));
    }

    /**
     * @param array<string, mixed> $order
     */
    private function resolveOrderNumberForLogging(array $order): string
    {
        $orderNumber = trim((string) ($order['orderNr'] ?? ''));

        if ($orderNumber !== '') {
            return $orderNumber;
        }

        $orderId = trim((string) ($order['id'] ?? ''));

        return $orderId !== '' ? $orderId : 'unknown';
    }
}
