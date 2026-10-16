<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Support;

use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingTaxResolver;

final class EInvoicingFixtureLoader
{
    private const DDEV_PCF_REPOSITORY_PATH = '/var/www/project-control-contao-merconis';
    private const FIXTURE_DIRECTORY = 'meta/reference/e-rechnung/fixtures';

    /**
     * @return array{
     *   0: array<string, mixed>,
     *   1: array<string, mixed>,
     *   2: array<string, mixed>,
     *   3: array<string, mixed>,
     *   4: array<int|string, array<string, mixed>>,
     *   5: array<string, mixed>
     * }
     */
    public static function loadFixtureInput(string $fixtureId): array
    {
        $fixtureBasePath = self::fixtureBasePath($fixtureId);

        /** @var array<string, mixed> $configuration */
        $configuration = json_decode(
            (string) file_get_contents($fixtureBasePath . '/konfiguration.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        /** @var array<string, mixed> $orderSnapshot */
        $orderSnapshot = json_decode(
            (string) file_get_contents($fixtureBasePath . '/order-snapshot.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $shopSettings = is_array($configuration['tl_lsShopSettings'] ?? null)
            ? $configuration['tl_lsShopSettings']
            : [];

        $paymentMethods = is_array($configuration['tl_ls_shop_payment_methods'] ?? null)
            ? $configuration['tl_ls_shop_payment_methods']
            : [];
        $paymentMethod = self::firstRecord($paymentMethods);

        $shippingMethods = is_array($configuration['tl_ls_shop_shipping_methods'] ?? null)
            ? $configuration['tl_ls_shop_shipping_methods']
            : [];
        $shippingMethod = self::firstRecord($shippingMethods);

        $taxRates = is_array($configuration['tl_ls_shop_steuersaetze'] ?? null)
            ? $configuration['tl_ls_shop_steuersaetze']
            : [];

        $memberGroups = is_array($configuration['tl_member_group'] ?? null)
            ? $configuration['tl_member_group']
            : [];
        $memberGroup = self::firstRecord($memberGroups);
        $orderSnapshot = self::enrichOrderSnapshot(
            $orderSnapshot,
            $shopSettings,
            $paymentMethod,
            $shippingMethod,
            $taxRates
        );

        return [$orderSnapshot, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup];
    }

    public static function loadReferenceXml(string $fixtureId): string
    {
        $fixtureBasePath = self::fixtureBasePath($fixtureId);

        return (string) file_get_contents($fixtureBasePath . '/reference-cii.xml');
    }

    private static function fixtureBasePath(string $fixtureId): string
    {
        return self::pcfRepositoryPath() . '/' . self::FIXTURE_DIRECTORY . '/' . $fixtureId;
    }

    private static function pcfRepositoryPath(): string
    {
        $configuredPath = getenv('EINVOICING_PCF_REPOSITORY_PATH');

        if (is_string($configuredPath) && $configuredPath !== '' && is_dir($configuredPath)) {
            return rtrim($configuredPath, '/');
        }

        if (is_dir(self::DDEV_PCF_REPOSITORY_PATH)) {
            return self::DDEV_PCF_REPOSITORY_PATH;
        }

        $localRepositoryPath = dirname(__DIR__, 6) . '/project-control-contao-merconis';

        if (is_dir($localRepositoryPath)) {
            return $localRepositoryPath;
        }

        throw new \RuntimeException('PCF-Repository mit Fixtures konnte nicht aufgelöst werden.');
    }

    /**
     * @param array<int|string, mixed> $records
     *
     * @return array<string, mixed>
     */
    private static function firstRecord(array $records): array
    {
        $firstRecord = reset($records);

        return is_array($firstRecord) ? $firstRecord : [];
    }

    /**
     * @param array<string, mixed> $orderSnapshot
     * @param array<string, mixed> $shopSettings
     * @param array<string, mixed> $paymentMethod
     * @param array<string, mixed> $shippingMethod
     * @param array<int|string, array<string, mixed>> $taxRates
     *
     * @return array<string, mixed>
     */
    private static function enrichOrderSnapshot(
        array $orderSnapshot,
        array $shopSettings,
        array $paymentMethod,
        array $shippingMethod,
        array $taxRates,
    ): array {
        $taxContext = EInvoicingTaxResolver::resolveSnapshotTaxContextFromOrder($orderSnapshot, $shopSettings);
        $orderSnapshot[EInvoicingSnapshotFields::ORDER_TAX_COUNTRY_ORDER_KEY] = $taxContext['taxCountry'];
        $orderSnapshot[EInvoicingSnapshotFields::ORDER_VAT_EXEMPTION_STATUS_ORDER_KEY] = $taxContext['vatExemptionStatus'];
        $orderSnapshot[EInvoicingSnapshotFields::ORDER_PAYMENT_TAX_CATEGORY_ORDER_KEY] = self::resolveChargeTaxCategory(
            $orderSnapshot['paymentMethod_amountTaxedWith'] ?? null,
            $taxRates,
            $taxContext
        ) ?? '';
        $orderSnapshot[EInvoicingSnapshotFields::ORDER_SHIPPING_TAX_CATEGORY_ORDER_KEY] = self::resolveChargeTaxCategory(
            $orderSnapshot['shippingMethod_amountTaxedWith'] ?? null,
            $taxRates,
            $taxContext
        ) ?? '';

        if (!is_array($orderSnapshot['items'] ?? null)) {
            return $orderSnapshot;
        }

        foreach ($orderSnapshot['items'] as $itemKey => $item) {
            if (!is_array($item)) {
                continue;
            }

            $taxClassId = (string) ($item['taxClass'] ?? '');
            $taxConfiguration = isset($taxRates[$taxClassId]) && is_array($taxRates[$taxClassId])
                ? $taxRates[$taxClassId]
                : [];
            $taxCategory = EInvoicingTaxResolver::resolveTaxCategory(
                is_scalar($taxConfiguration[EInvoicingSnapshotFields::TAX_CONFIGURATION_CATEGORY_FIELD] ?? null)
                    ? (string) $taxConfiguration[EInvoicingSnapshotFields::TAX_CONFIGURATION_CATEGORY_FIELD]
                    : null,
                is_numeric($item['taxPercentage'] ?? null) ? (float) $item['taxPercentage'] : null,
                $taxContext
            );

            if (!is_array($item['extendedInfo'] ?? null)) {
                $item['extendedInfo'] = [];
            }

            if ($taxCategory !== null) {
                $item['extendedInfo'][EInvoicingSnapshotFields::ITEM_TAX_CATEGORY_CODE] = $taxCategory;
            }

            $orderSnapshot['items'][$itemKey] = $item;
        }

        return $orderSnapshot;
    }

    /**
     * @param array<int|string, mixed>|mixed $taxedWithValue
     * @param array<int|string, array<string, mixed>> $taxRates
     * @param array{
     *   taxCountry: string,
     *   isVatExempt: bool,
     *   isEuCountry: bool,
     *   vatExemptionStatus: string
     * } $taxContext
     */
    private static function resolveChargeTaxCategory(
        mixed $taxedWithValue,
        array $taxRates,
        array $taxContext,
    ): ?string {
        $taxedWith = is_array($taxedWithValue) ? $taxedWithValue : [];

        foreach ($taxedWith as $taxClassId => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $taxConfiguration = isset($taxRates[(string) $taxClassId]) && is_array($taxRates[(string) $taxClassId])
                ? $taxRates[(string) $taxClassId]
                : [];

            return EInvoicingTaxResolver::resolveTaxCategory(
                is_scalar($taxConfiguration[EInvoicingSnapshotFields::TAX_CONFIGURATION_CATEGORY_FIELD] ?? null)
                    ? (string) $taxConfiguration[EInvoicingSnapshotFields::TAX_CONFIGURATION_CATEGORY_FIELD]
                    : null,
                is_numeric($entry['taxRate'] ?? null) ? (float) $entry['taxRate'] : null,
                $taxContext
            );
        }

        return null;
    }

}
