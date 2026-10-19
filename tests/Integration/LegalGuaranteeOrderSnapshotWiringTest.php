<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranV1OrderSnapshotRenderer;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeOrderSnapshotWiringTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';
    private const CHECKOUT_PATH = __DIR__ . '/../../src/Resources/contao/classes/ls_shop_checkout.php';
    private const CONFIG_PATH = __DIR__ . '/../../src/Resources/contao/config/config.php';
    private const SERVICES_PATH = __DIR__ . '/../../src/Resources/config/services.yml';

    protected function setUp(): void
    {
        parent::setUp();
        unset(
            $GLOBALS['TL_DCA']['tl_ls_shop_orders'],
            $GLOBALS['TL_DCA']['tl_ls_shop_orders_items']
        );
    }

    public function testOrdersDcaContainsGllSnapshotFields(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_orders.php';

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_orders'];

        self::assertSame(
            "varchar(16) NOT NULL default ''",
            $tableConfig['fields']['gllVersion']['sql']
        );
        self::assertSame(
            "varchar(8) NOT NULL default ''",
            $tableConfig['fields']['gllLanguage']['sql']
        );
    }

    public function testOrderItemsDcaContainsGaranSnapshotFields(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_orders_items.php';

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_orders_items'];

        self::assertSame(
            "varchar(16) NOT NULL default ''",
            $tableConfig['fields']['garanVersion']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['garanBrand']['sql']
        );
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['garanModelIdentifier']['sql']
        );
        self::assertSame(
            "varchar(32) NOT NULL default ''",
            $tableConfig['fields']['garanDurationYears']['sql']
        );
    }

    public function testCheckoutPersistsOrderAndItemSnapshotColumns(): void
    {
        $checkoutContents = (string) file_get_contents(self::CHECKOUT_PATH);

        self::assertStringContainsString('`gllVersion` = ?', $checkoutContents);
        self::assertStringContainsString('`gllLanguage` = ?', $checkoutContents);
        self::assertStringContainsString('`garanVersion` = ?', $checkoutContents);
        self::assertStringContainsString('`garanBrand` = ?', $checkoutContents);
        self::assertStringContainsString('`garanModelIdentifier` = ?', $checkoutContents);
        self::assertStringContainsString('`garanDurationYears` = ?', $checkoutContents);
        self::assertStringContainsString("'gllVersion' => ''", $checkoutContents);
        self::assertStringContainsString("'gllLanguage' => ''", $checkoutContents);
    }

    public function testCheckoutBuildsSnapshotsDirectlyAndKeepsHookExtensionPoints(): void
    {
        $configContents = (string) file_get_contents(self::CONFIG_PATH);
        $checkoutContents = (string) file_get_contents(self::CHECKOUT_PATH);
        $servicesContents = (string) file_get_contents(self::SERVICES_PATH);

        self::assertStringNotContainsString(
            'OrderLabelSnapshotHook',
            $configContents
        );
        self::assertStringContainsString(
            'OrderLabelSnapshotBuilder::class',
            $checkoutContents
        );
        self::assertStringContainsString(
            'enrichOrderItem(',
            $checkoutContents
        );
        self::assertStringContainsString(
            'enrichOrder(',
            $checkoutContents
        );
        self::assertStringContainsString(
            "MERCONIS_HOOKS']['storeCartItemInOrder",
            $checkoutContents
        );
        self::assertStringContainsString(
            "MERCONIS_HOOKS']['preparingOrderDataToStore",
            $checkoutContents
        );
        self::assertStringContainsString(
            "LeadingSystems\\MerconisBundle\\LegalGuarantee\\Order\\OrderLabelSnapshotBuilder:\n    public: true",
            $servicesContents
        );
        self::assertStringContainsString('OrderLabelSnapshotRenderer', $servicesContents);
        self::assertStringContainsString('GaranV1OrderSnapshotRenderer', $servicesContents);
        self::assertStringContainsString(
            'merconis.legal_guarantee.garan_order_snapshot_renderer',
            $servicesContents
        );
    }

    public function testCurrentGaranVersionHasRegisteredLabelAndSnapshotRenderers(): void
    {
        $servicesContents = (string) file_get_contents(self::SERVICES_PATH);
        $registeredLabelRenderer = new GaranLabelRenderer(
            $this->createAssetLocator(),
            OfficialGuaranteeAssetLocator::GARAN_VERSION
        );
        $registeredRenderer = new GaranV1OrderSnapshotRenderer(
            new GaranPdfTemplateRenderer(
                $this->createAssetLocator(),
                OfficialGuaranteeAssetLocator::GARAN_VERSION
            )
        );

        self::assertStringContainsString(
            "merconis.legal_guarantee.garan_label_renderer.v1_0:\n    class: LeadingSystems\\MerconisBundle\\LegalGuarantee\\Garan\\V1_0\\GaranLabelRenderer",
            $servicesContents
        );
        self::assertStringContainsString(
            "name: 'merconis.legal_guarantee.garan_label_renderer'",
            $servicesContents
        );
        self::assertStringContainsString(
            "merconis.legal_guarantee.garan_pdf_template_renderer.v1_0:\n    class: LeadingSystems\\MerconisBundle\\LegalGuarantee\\Garan\\V1_0\\GaranPdfTemplateRenderer",
            $servicesContents
        );
        self::assertStringContainsString(
            "merconis.legal_guarantee.garan_order_snapshot_renderer.v1_0:\n    class: LeadingSystems\\MerconisBundle\\LegalGuarantee\\Order\\GaranV1OrderSnapshotRenderer",
            $servicesContents
        );
        self::assertStringContainsString(
            "name: 'merconis.legal_guarantee.garan_order_snapshot_renderer'",
            $servicesContents
        );
        self::assertStringContainsString(
            "$version: 'v1.0'",
            $servicesContents
        );
        self::assertSame(
            OfficialGuaranteeAssetLocator::GARAN_VERSION,
            $registeredLabelRenderer->getVersion()
        );
        self::assertSame(
            OfficialGuaranteeAssetLocator::GARAN_VERSION,
            $registeredRenderer->getVersion()
        );
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }
}
