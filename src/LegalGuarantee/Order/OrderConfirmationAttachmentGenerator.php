<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use Contao\StringUtil;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface;
use Throwable;

final class OrderConfirmationAttachmentGenerator
{
    public function __construct(
        private readonly OrderLabelSnapshotRenderer $snapshotRenderer,
        private readonly GaranPdfAttachmentWriterInterface $garanPdfAttachmentWriter,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array{relativePaths: list<string>, cleanupRelativePaths: list<string>}
     */
    public function generateAttachments(array $order): array
    {
        $relativePaths = [];
        $cleanupRelativePaths = [];

        try {
            $gllPdfPath = $this->snapshotRenderer->resolveGllPdfPath($order);
            if (null !== $gllPdfPath && is_file($gllPdfPath)) {
                $relativePaths[] = $this->toRelativePath($gllPdfPath);
            }
        } catch (Throwable) {
            // GLL-Ausfall darf den Versand der restlichen Anhänge nicht blockieren.
        }

        foreach ($this->collectUniqueGaranItems($order['items'] ?? []) as $garanItem) {
            try {
                $renderedSvg = $this->snapshotRenderer->renderGaranLabel($garanItem);

                if ('' === $renderedSvg) {
                    continue;
                }

                $writtenAttachment = $this->garanPdfAttachmentWriter->write(
                    $renderedSvg,
                    $this->buildAttachmentBaseName($order, $garanItem)
                );

                $relativePaths[] = $writtenAttachment['relativePath'];

                if ($writtenAttachment['cleanupAfterSend']) {
                    $cleanupRelativePaths[] = $writtenAttachment['relativePath'];
                }
            } catch (Throwable) {
                // Ein defektes Positions-PDF darf übrige Anhänge nicht verhindern.
            }
        }

        return [
            'relativePaths' => array_values(array_unique($relativePaths)),
            'cleanupRelativePaths' => array_values(array_unique($cleanupRelativePaths)),
        ];
    }

    /**
     * @param array<string, mixed> $order
     */
    public function hasRelevantAttachments(array $order): bool
    {
        if ('' !== trim((string) ($order['gllVersion'] ?? '')) && '' !== trim((string) ($order['gllLanguage'] ?? ''))) {
            return true;
        }

        foreach ($order['items'] ?? [] as $item) {
            if ($this->isGaranSnapshotItem($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $items
     * @return list<array<string, mixed>>
     */
    private function collectUniqueGaranItems(mixed $items): array
    {
        if (!is_iterable($items)) {
            return [];
        }

        $uniqueItems = [];

        foreach ($items as $item) {
            if (!$this->isGaranSnapshotItem($item)) {
                continue;
            }

            $uniqueItems[$this->buildDedupeKey($item)] = $item;
        }

        return array_values($uniqueItems);
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
            && '' !== trim((string) ($item['garanDurationYears'] ?? ''));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildDedupeKey(array $item): string
    {
        return implode('|', [
            trim((string) ($item['garanVersion'] ?? '')),
            trim((string) ($item['garanBrand'] ?? '')),
            trim((string) ($item['garanModelIdentifier'] ?? '')),
            trim((string) ($item['garanDurationYears'] ?? '')),
            $this->getProductTitle($item),
            $this->getVariantTitle($item),
        ]);
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $item
     */
    private function buildAttachmentBaseName(array $order, array $item): string
    {
        $productTitle = $this->createSlug($this->getProductTitle($item));
        $variantTitle = $this->createSlug($this->getVariantTitle($item));
        $orderNumber = $this->createSlug((string) ($order['orderNr'] ?? $order['id'] ?? 'order'));
        $baseName = 'garan-label-' . $orderNumber . '-' . $productTitle;

        if ('' !== $variantTitle) {
            $baseName .= '-' . $variantTitle;
        }

        return $baseName;
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

    private function createSlug(string $value): string
    {
        $slug = trim(StringUtil::standardize($value), '-');

        return '' !== $slug ? $slug : 'item';
    }

    private function toRelativePath(string $absolutePath): string
    {
        $normalizedProjectDir = rtrim($this->projectDir, '/');

        if (str_starts_with($absolutePath, $normalizedProjectDir . '/')) {
            return substr($absolutePath, strlen($normalizedProjectDir) + 1);
        }

        return $absolutePath;
    }
}
