<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use Contao\StringUtil;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface;
use Throwable;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderConfirmationAttachmentGenerator
{
    private const PUBLIC_BUNDLE_PREFIX = 'bundles/leadingsystemsmerconis/';
    private const LOCAL_GLL_BASE_PREFIX = 'src/Resources/public/legal-guarantee/gll/';
    private const VENDOR_GLL_BASE_PREFIX = 'vendor/leadingsystems/contao-merconis/src/Resources/public/legal-guarantee/gll/';

    public function __construct(
        private readonly OrderLabelSnapshotRenderer $snapshotRenderer,
        private readonly GaranPdfAttachmentWriterInterface $garanPdfAttachmentWriter,
        private readonly string $projectDir,
        private readonly GaranLabelGrouping $garanLabelGrouping,
        private readonly TranslatorInterface $translator,
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
            $gllPdfPath = $this->snapshotRenderer->resolveGllPdfPath($order);
            if (null !== $gllPdfPath && is_file($gllPdfPath)) {
                $relativePaths[] = $this->toRelativePath($gllPdfPath);
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

                if ('' === $renderedSvg) {
                    continue;
                }

                $garanPages[] = [
                    'svgMarkup' => $renderedSvg,
                    'productNames' => $garanGroup['productNames'],
                    'euLinkText' => $this->resolveGaranEuLinkText($order),
                    'euLinkUrl' => $this->snapshotRenderer->resolveGaranEuUrl($garanGroup['garanVersion']),
                ];
            } catch (Throwable) {
                // Ein defektes Positions-PDF darf übrige Anhänge nicht verhindern.
            }
        }

        if ([] !== $garanPages) {
            try {
                $writtenAttachment = $this->garanPdfAttachmentWriter->write(
                    $garanPages,
                    $this->buildAttachmentBaseName($order)
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
     * @return list<array<string, mixed>>
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
            ];
        }

        return $this->garanLabelGrouping->groupEntries($entries);
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
    private function buildAttachmentBaseName(array $order): string
    {
        $orderNumber = $this->createSlug((string) ($order['orderNr'] ?? $order['id'] ?? 'order'));

        return 'garan-labels-' . $orderNumber;
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

        if ('' === $variantTitle) {
            return $productTitle;
        }

        return sprintf('%s (%s)', $productTitle, $variantTitle);
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

    private function createSlug(string $value): string
    {
        $slug = trim(StringUtil::standardize($value), '-');

        return '' !== $slug ? $slug : 'item';
    }

    private function toRelativePath(string $absolutePath): string
    {
        $normalizedProjectDir = rtrim($this->projectDir, '/');

        if (str_starts_with($absolutePath, $normalizedProjectDir . '/')) {
            $relativePath = substr($absolutePath, strlen($normalizedProjectDir) + 1);

            if (str_starts_with($relativePath, self::LOCAL_GLL_BASE_PREFIX)) {
                return self::PUBLIC_BUNDLE_PREFIX . 'legal-guarantee/gll/' . substr($relativePath, strlen(self::LOCAL_GLL_BASE_PREFIX));
            }

            if (str_starts_with($relativePath, self::VENDOR_GLL_BASE_PREFIX)) {
                return self::PUBLIC_BUNDLE_PREFIX . 'legal-guarantee/gll/' . substr($relativePath, strlen(self::VENDOR_GLL_BASE_PREFIX));
            }

            return $relativePath;
        }

        return $absolutePath;
    }
}
