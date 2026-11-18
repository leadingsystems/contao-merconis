<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use Traversable;

final class ProductGuaranteeDisplayResolver
{
    /**
     * Bestimmt die Ausgabe für die maßgebliche Mitgliedergruppe. `showLgn` folgt der Tabelle
     * in Spec 27, Abschnitt 3.5; für eine Variante gilt `lgnMode` des Elternprodukts aus
     * `$productData`. `showGaran` setzt `showLgn` voraus.
     *
     * @param string|null $memberGroupLgnMode Wert `lsShopLgnMode` der maßgeblichen Gruppe
     * @param iterable<string, mixed> $productData
     * @param iterable<string, mixed>|null $variantData
     *
     * @return array{
     *   showLgn: bool,
     *   showGaran: bool,
     *   garan: array{brand: string, modelIdentifier: string, durationMonths: string}|null
     * }
     */
    public function resolve(?string $memberGroupLgnMode, iterable $productData, ?iterable $variantData = null): array
    {
        $productData = $this->normalizeData($productData);
        $variantData = null !== $variantData ? $this->normalizeData($variantData) : null;
        $showLgn = $this->resolveShowLgn($memberGroupLgnMode, $productData['lgnMode'] ?? null);

        $effectiveGaranData = $this->resolveEffectiveGaranData($productData, $variantData);
        $showGaran = $showLgn
            && $this->isTruthy($effectiveGaranData['enableGaran'] ?? null)
            && '' !== $effectiveGaranData['brand']
            && '' !== $effectiveGaranData['modelIdentifier']
            && '' !== $effectiveGaranData['durationMonths'];

        return [
            'showLgn' => $showLgn,
            'showGaran' => $showGaran,
            'garan' => $showGaran
                ? [
                    'brand' => $effectiveGaranData['brand'],
                    'modelIdentifier' => $effectiveGaranData['modelIdentifier'],
                    'durationMonths' => $effectiveGaranData['durationMonths'],
                ]
                : null,
        ];
    }

    /**
     * @param array<string, mixed> $productData
     * @param array<string, mixed>|null $variantData
     *
     * @return array{
     *   enableGaran: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string
     * }
     */
    private function resolveEffectiveGaranData(array $productData, ?array $variantData): array
    {
        $productEnableGaran = $this->normalizeCheckboxValue($productData['enableGaran'] ?? null);
        $productBrand = $this->normalizeTextValue($productData['guaranteeBrand'] ?? null);
        $productModelIdentifier = $this->normalizeTextValue($productData['guaranteeModelIdentifier'] ?? null);
        $productDurationMonths = $this->normalizeDurationValue($productData['guaranteeDurationMonths'] ?? null);

        if (null === $variantData) {
            return [
                'enableGaran' => $productEnableGaran,
                'brand' => $productBrand,
                'modelIdentifier' => $productModelIdentifier,
                'durationMonths' => $productDurationMonths,
            ];
        }

        $variantMode = $this->normalizeModeValue($variantData['garanMode'] ?? null);

        if ('inherit' === $variantMode) {
            return [
                'enableGaran' => $productEnableGaran,
                'brand' => $productBrand,
                'modelIdentifier' => $productModelIdentifier,
                'durationMonths' => $productDurationMonths,
            ];
        }

        if ('disabled' === $variantMode) {
            return [
                'enableGaran' => '',
                'brand' => '',
                'modelIdentifier' => '',
                'durationMonths' => '',
            ];
        }

        $variantBrand = $this->normalizeTextValue($variantData['guaranteeBrand'] ?? null);
        $variantModelIdentifier = $this->normalizeTextValue($variantData['guaranteeModelIdentifier'] ?? null);
        $variantDurationMonths = $this->normalizeDurationValue($variantData['guaranteeDurationMonths'] ?? null);

        return [
            'enableGaran' => '1',
            'brand' => $variantBrand,
            'modelIdentifier' => $variantModelIdentifier,
            'durationMonths' => $variantDurationMonths,
        ];
    }

    /**
     * Leer und `NULL` wirken wie `blocked`. Ein unbekannter Gruppen- oder Produktwert ergibt
     * keinen LGN-Output.
     */
    private function resolveShowLgn(?string $memberGroupLgnMode, mixed $productLgnMode): bool
    {
        $normalizedMemberGroupLgnMode = trim((string) $memberGroupLgnMode);
        $normalizedProductLgnMode = trim((string) $productLgnMode);

        return match ($normalizedMemberGroupLgnMode) {
            'on' => in_array($normalizedProductLgnMode, ['inherit', 'always'], true),
            'off' => 'always' === $normalizedProductLgnMode,
            default => false,
        };
    }

    private function normalizeCheckboxValue(mixed $value): string
    {
        return $this->isTruthy($value) ? '1' : '';
    }

    /**
     * @param iterable<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalizeData(iterable $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if ($data instanceof Traversable) {
            return iterator_to_array($data);
        }

        return [];
    }

    private function normalizeDurationValue(mixed $value): string
    {
        $normalizedValue = trim((string) $value);

        if ('' === $normalizedValue) {
            return '';
        }

        return $normalizedValue;
    }

    private function normalizeTextValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function normalizeModeValue(mixed $value): string
    {
        $mode = trim((string) $value);

        return in_array($mode, ['inherit', 'disabled', 'override'], true)
            ? $mode
            : 'inherit';
    }

    private function isTruthy(mixed $value): bool
    {
        return !in_array($value, [null, '', 0, '0', false], true);
    }
}
