<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use Traversable;

final class ProductGuaranteeDisplayResolver
{
    /**
     * @param iterable<string, mixed> $productData
     * @param iterable<string, mixed>|null $variantData
     *
     * @return array{
     *   showGll: bool,
     *   showGaran: bool,
     *   garan: array{brand: string, modelIdentifier: string, durationYears: string}|null
     * }
     */
    public function resolve(iterable $productData, ?iterable $variantData = null): array
    {
        $productData = $this->normalizeData($productData);
        $variantData = null !== $variantData ? $this->normalizeData($variantData) : null;
        $showGll = $this->isTruthy($productData['enableGll'] ?? null);

        $effectiveGaranData = $this->resolveEffectiveGaranData($productData, $variantData);
        $showGaran = $this->isTruthy($effectiveGaranData['enableGaran'] ?? null)
            && '' !== $effectiveGaranData['brand']
            && '' !== $effectiveGaranData['modelIdentifier']
            && '' !== $effectiveGaranData['durationYears'];

        return [
            'showGll' => $showGll,
            'showGaran' => $showGaran,
            'garan' => $showGaran
                ? [
                    'brand' => $effectiveGaranData['brand'],
                    'modelIdentifier' => $effectiveGaranData['modelIdentifier'],
                    'durationYears' => $effectiveGaranData['durationYears'],
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
     *   durationYears: string
     * }
     */
    private function resolveEffectiveGaranData(array $productData, ?array $variantData): array
    {
        $productEnableGaran = $this->normalizeCheckboxValue($productData['enableGaran'] ?? null);
        $productBrand = trim((string) ($productData['guaranteeBrand'] ?? ''));
        $productModelIdentifier = trim((string) ($productData['guaranteeModelIdentifier'] ?? ''));
        $productDurationYears = $this->normalizeDurationValue($productData['guaranteeDurationYears'] ?? null);

        if (null === $variantData || !$this->isTruthy($variantData['garanOverride'] ?? null)) {
            return [
                'enableGaran' => $productEnableGaran,
                'brand' => $productBrand,
                'modelIdentifier' => $productModelIdentifier,
                'durationYears' => $productDurationYears,
            ];
        }

        $variantEnableGaran = $this->normalizeCheckboxValue($variantData['enableGaran'] ?? null);
        $variantBrand = trim((string) ($variantData['guaranteeBrand'] ?? ''));
        $variantModelIdentifier = trim((string) ($variantData['guaranteeModelIdentifier'] ?? ''));
        $variantDurationYears = $this->normalizeDurationValue($variantData['guaranteeDurationYears'] ?? null);

        return [
            'enableGaran' => $variantEnableGaran,
            'brand' => '' !== $variantBrand ? $variantBrand : $productBrand,
            'modelIdentifier' => '' !== $variantModelIdentifier
                ? $variantModelIdentifier
                : $productModelIdentifier,
            'durationYears' => '' !== $variantDurationYears
                ? $variantDurationYears
                : $productDurationYears,
        ];
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

        return str_replace(',', '.', $normalizedValue);
    }

    private function isTruthy(mixed $value): bool
    {
        return !in_array($value, [null, '', 0, '0', false], true);
    }
}
