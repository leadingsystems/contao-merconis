<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Support;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

final class EInvoicingTaxResolver
{
    public const VAT_EXEMPTION_STATUS_EXEMPT_EU = 'exempt_eu';
    public const VAT_EXEMPTION_STATUS_EXEMPT_NON_EU = 'exempt_non_eu';
    public const VAT_EXEMPTION_STATUS_NOT_EXEMPT_EU = 'not_exempt_eu';
    public const VAT_EXEMPTION_STATUS_NOT_EXEMPT_NON_EU = 'not_exempt_non_eu';

    /**
     * @param array<string, mixed> $orderData
     * @param array<string, mixed> $shopSettings
     *
     * @return array{
     *   taxCountry: string,
     *   isVatExempt: bool,
     *   isEuCountry: bool,
     *   vatExemptionStatus: string
     * }
     */
    public static function resolveSnapshotTaxContextFromOrder(array $orderData, array $shopSettings): array
    {
        $shopCountry = self::normalizeCountryCode($shopSettings['ls_shop_country'] ?? null) ?? '';
        $taxCountry = self::resolveTaxCountryFromOrder($orderData, $shopCountry);
        $buyerVatId = self::resolveBuyerVatIdFromOrder($orderData);
        $shopHasVatId = self::optionalString($shopSettings['ls_shop_ownVATID'] ?? null) !== null;
        $euCountries = self::parseEuCountries($shopSettings['ls_shop_euCountrycodes'] ?? null);
        $isEuCountry = in_array($taxCountry, $euCountries, true);
        $isVatExempt = $shopHasVatId
            && $buyerVatId !== null
            && $shopCountry !== ''
            && $taxCountry !== ''
            && $taxCountry !== $shopCountry;

        return [
            'taxCountry' => strtoupper($taxCountry),
            'isVatExempt' => $isVatExempt,
            'isEuCountry' => $isEuCountry,
            'vatExemptionStatus' => self::formatVatExemptionStatus($isVatExempt, $isEuCountry),
        ];
    }

    /**
     * @return array{
     *   taxCountry: string,
     *   isVatExempt: bool,
     *   isEuCountry: bool,
     *   vatExemptionStatus: string
     * }
     */
    public static function resolveSnapshotTaxContextFromCheckout(): array
    {
        $shopCountry = self::normalizeCountryCode($GLOBALS['TL_CONFIG']['ls_shop_country'] ?? null) ?? '';
        $taxCountry = self::normalizeCountryCode(ls_shop_generalHelper::getCustomerCountry()) ?? $shopCountry;
        $euCountries = self::parseEuCountries($GLOBALS['TL_CONFIG']['ls_shop_euCountrycodes'] ?? null);
        $isEuCountry = in_array($taxCountry, $euCountries, true);
        $isVatExempt = ls_shop_generalHelper::checkVATID();

        return [
            'taxCountry' => strtoupper($taxCountry),
            'isVatExempt' => $isVatExempt,
            'isEuCountry' => $isEuCountry,
            'vatExemptionStatus' => self::formatVatExemptionStatus($isVatExempt, $isEuCountry),
        ];
    }

    /**
     * @param array{
     *   taxCountry: string,
     *   isVatExempt: bool,
     *   isEuCountry: bool,
     *   vatExemptionStatus?: string
     * } $taxContext
     */
    public static function resolveTaxCategory(?string $configuredCategory, ?float $taxRate, array $taxContext): ?string
    {
        if ($taxRate !== null && $taxRate > 0.0) {
            return 'S';
        }

        if ($taxContext['isVatExempt'] && $taxContext['isEuCountry']) {
            return 'K';
        }

        if ($taxContext['isVatExempt'] && !$taxContext['isEuCountry']) {
            return 'G';
        }

        if (!$taxContext['isVatExempt'] && !$taxContext['isEuCountry']) {
            return 'G';
        }

        $normalizedCategory = strtoupper(trim($configuredCategory ?? 'auto'));

        if ($normalizedCategory !== '' && $normalizedCategory !== 'AUTO') {
            return $normalizedCategory;
        }

        return null;
    }

    /**
     * @return array{isVatExempt: bool, isEuCountry: bool}
     */
    public static function parseVatExemptionStatus(?string $status): array
    {
        return match (strtolower(trim((string) $status))) {
            self::VAT_EXEMPTION_STATUS_EXEMPT_EU => ['isVatExempt' => true, 'isEuCountry' => true],
            self::VAT_EXEMPTION_STATUS_EXEMPT_NON_EU => ['isVatExempt' => true, 'isEuCountry' => false],
            self::VAT_EXEMPTION_STATUS_NOT_EXEMPT_NON_EU => ['isVatExempt' => false, 'isEuCountry' => false],
            default => ['isVatExempt' => false, 'isEuCountry' => true],
        };
    }

    public static function formatVatExemptionStatus(bool $isVatExempt, bool $isEuCountry): string
    {
        if ($isVatExempt && $isEuCountry) {
            return self::VAT_EXEMPTION_STATUS_EXEMPT_EU;
        }

        if ($isVatExempt && !$isEuCountry) {
            return self::VAT_EXEMPTION_STATUS_EXEMPT_NON_EU;
        }

        if (!$isVatExempt && !$isEuCountry) {
            return self::VAT_EXEMPTION_STATUS_NOT_EXEMPT_NON_EU;
        }

        return self::VAT_EXEMPTION_STATUS_NOT_EXEMPT_EU;
    }

    /**
     * @param array<string, mixed> $orderData
     */
    public static function resolveTaxCountryFromOrder(array $orderData, string $shopCountry): string
    {
        $personalData = is_array($orderData['customerData']['personalData'] ?? null)
            ? $orderData['customerData']['personalData']
            : [];
        $originalValues = is_array($orderData['customerData']['personalData_originalOptionValues'] ?? null)
            ? $orderData['customerData']['personalData_originalOptionValues']
            : [];

        $useDeviantShippingAddress = self::isTruthy(
            $originalValues['useDeviantShippingAddress'] ?? $personalData['useDeviantShippingAddress'] ?? null
        );
        $alternativeCountry = self::normalizeCountryCode(
            $originalValues['country_alternative'] ?? $personalData['country_alternative'] ?? null
        );
        $billingCountry = self::normalizeCountryCode(
            $originalValues['country'] ?? $personalData['country'] ?? null
        );

        if ($useDeviantShippingAddress && $alternativeCountry !== null) {
            return $alternativeCountry;
        }

        if ($billingCountry !== null) {
            return $billingCountry;
        }

        return strtolower($shopCountry);
    }

    /**
     * @param array<string, mixed> $orderData
     */
    private static function resolveBuyerVatIdFromOrder(array $orderData): ?string
    {
        $personalData = is_array($orderData['customerData']['personalData'] ?? null)
            ? $orderData['customerData']['personalData']
            : [];

        return self::optionalString($personalData['VATID'] ?? null);
    }

    /**
     * @return list<string>
     */
    private static function parseEuCountries(mixed $configuredCountries): array
    {
        $configuredCountries = self::optionalString($configuredCountries);

        if ($configuredCountries === null) {
            self::logEuCountryFallback();

            return EInvoicingCodeLists::DEFAULT_EU_COUNTRIES;
        }

        $countries = [];

        foreach (preg_split('/\s*,\s*/', strtolower($configuredCountries)) ?: [] as $countryCode) {
            if ($countryCode !== '') {
                $countries[] = $countryCode;
            }
        }

        if ($countries === []) {
            self::logEuCountryFallback();

            return EInvoicingCodeLists::DEFAULT_EU_COUNTRIES;
        }

        $normalizedCountries = array_values(array_unique($countries));

        self::logEuCountryDifferenceIfNeeded($normalizedCountries);

        return $normalizedCountries;
    }

    private static function normalizeCountryCode(mixed $value): ?string
    {
        $value = self::optionalString($value);

        if ($value === null) {
            return null;
        }

        $value = strtolower($value);

        if (!preg_match('/^[a-z]{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private static function optionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        return $value;
    }

    private static function isTruthy(mixed $value): bool
    {
        $value = self::optionalString($value);

        return $value !== null && !in_array(strtolower($value), ['0', 'false', 'off', 'no'], true);
    }

    /**
     * @param list<string> $configuredCountries
     */
    private static function logEuCountryDifferenceIfNeeded(array $configuredCountries): void
    {
        $configuredCountriesSorted = $configuredCountries;
        $defaultCountriesSorted = EInvoicingCodeLists::DEFAULT_EU_COUNTRIES;

        sort($configuredCountriesSorted);
        sort($defaultCountriesSorted);

        if ($configuredCountriesSorted === $defaultCountriesSorted) {
            return;
        }

        $missingCountries = array_values(array_diff(EInvoicingCodeLists::DEFAULT_EU_COUNTRIES, $configuredCountries));
        $additionalCountries = array_values(array_diff($configuredCountries, EInvoicingCodeLists::DEFAULT_EU_COUNTRIES));

        self::logToContao(
            sprintf(
                'MERCONIS E-INVOICING: Configured EU country list differs from DEFAULT_EU_COUNTRIES. Missing: %s. Additional: %s. Configured: %s.',
                self::formatCountriesForLog($missingCountries),
                self::formatCountriesForLog($additionalCountries),
                self::formatCountriesForLog($configuredCountries)
            )
        );
    }

    private static function logEuCountryFallback(): void
    {
        self::logToContao(
            sprintf(
                'MERCONIS E-INVOICING: EU country list missing or empty. Falling back to DEFAULT_EU_COUNTRIES (%s). Please check the shop settings.',
                self::formatCountriesForLog(EInvoicingCodeLists::DEFAULT_EU_COUNTRIES)
            )
        );
    }

    /**
     * @param list<string> $countries
     */
    private static function formatCountriesForLog(array $countries): string
    {
        if ($countries === []) {
            return '(none)';
        }

        return strtoupper(implode(', ', $countries));
    }

    private static function logToContao(string $message): void
    {
        try {
            $container = System::getContainer();

            if ($container === null || !$container->has('monolog.logger.contao')) {
                return;
            }

            $container
                ->get('monolog.logger.contao')
                ->warning($message, ['contao' => new ContaoContext('MERCONIS E-INVOICING', ContaoContext::ERROR)])
            ;
        } catch (\Throwable) {
        }
    }
}
