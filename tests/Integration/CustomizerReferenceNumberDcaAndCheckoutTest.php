<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class CustomizerReferenceNumberDcaAndCheckoutTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';
    private const CHECKOUT_PATH = __DIR__ . '/../../src/Resources/contao/classes/ls_shop_checkout.php';

    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TL_DCA']['tl_ls_shop_orders_items']);
    }

    public function testOrderItemsDcaContainsCustomizerReferenceNumberField(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_orders_items.php';

        self::assertArrayHasKey('tl_ls_shop_orders_items', $GLOBALS['TL_DCA']);
        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_orders_items'];

        self::assertArrayHasKey('customizer_referenceNumber', $tableConfig['fields']);
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['customizer_referenceNumber']['sql']
        );
    }

    public function testCustomizerReferenceNumberFieldTypeMatchesConfiguratorPattern(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_orders_items.php';

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_orders_items'];

        self::assertSame(
            $tableConfig['fields']['configurator_referenceNumber']['sql'],
            $tableConfig['fields']['customizer_referenceNumber']['sql']
        );
    }

    public function testCheckoutCodeContainsCustomizerReferenceNumberInCreateOrder(): void
    {
        $checkoutContents = (string) file_get_contents(self::CHECKOUT_PATH);

        self::assertStringContainsString(
            "'referenceNumber' =>",
            $checkoutContents
        );
        self::assertStringContainsString(
            'strtoupper(substr(md5($objProductOrVariant->_customizer->getSummary() . $objProductOrVariant->_customizer->getFlexData()), 0, 8))',
            $checkoutContents
        );
    }

    public function testCheckoutCodeContainsCustomizerReferenceNumberInSaveOrderInDB(): void
    {
        $checkoutContents = (string) file_get_contents(self::CHECKOUT_PATH);

        self::assertStringContainsString(
            '`customizer_referenceNumber` = ?',
            $checkoutContents
        );
        self::assertStringContainsString(
            "\$arrItem['customizer']['referenceNumber']",
            $checkoutContents
        );
    }

    public function testCustomizerReferenceNumberInsertFollowsFlexDataInSqlInsert(): void
    {
        $checkoutContents = (string) file_get_contents(self::CHECKOUT_PATH);

        $flexDataPos = strpos($checkoutContents, '`customizer_flexData` = ?');
        $refNumberPos = strpos($checkoutContents, '`customizer_referenceNumber` = ?');
        $extendedInfoPos = strpos($checkoutContents, '`extendedInfo` = ?');

        self::assertNotFalse($flexDataPos);
        self::assertNotFalse($refNumberPos);
        self::assertNotFalse($extendedInfoPos);
        self::assertGreaterThan($flexDataPos, $refNumberPos);
        self::assertGreaterThan($refNumberPos, $extendedInfoPos);
    }
}
