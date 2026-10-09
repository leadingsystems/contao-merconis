<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Support;

use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;

final class EInvoicingFixtureLoader
{
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
        $fixtureBasePath = self::workspaceRoot()
            . '/project-control-contao-merconis/meta/reference/e-rechnung/fixtures/'
            . $fixtureId;

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

        return [$orderSnapshot, $shopSettings, $paymentMethod, $shippingMethod, $taxRates, $memberGroup];
    }

    public static function loadReferenceXml(string $fixtureId): string
    {
        $fixtureBasePath = self::workspaceRoot()
            . '/project-control-contao-merconis/meta/reference/e-rechnung/fixtures/'
            . $fixtureId;

        return (string) file_get_contents($fixtureBasePath . '/reference-cii.xml');
    }

    private static function workspaceRoot(): string
    {
        return dirname(__DIR__, 6);
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

}
