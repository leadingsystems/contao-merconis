<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class SalesUnitRuntimeCoverageTest extends TestCase
{
    private const MERCONIS_BASE_PATH = __DIR__ . '/../../src/Resources/contao/';
    private const HELPERS_BASE_PATH = __DIR__ . '/../../../contao-helpers/src/Resources/contao/';

    public function testCheckoutSnapshotPersistsSalesUnitDisplayFields(): void
    {
        $checkoutContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'classes/ls_shop_checkout.php');

        self::assertStringContainsString("\$arrItem['salesUnitSize'] = \$objProductOrVariant->_salesUnitSize;", $checkoutContents);
        self::assertStringContainsString("\$arrItem['displayQuantity'] = ls_shop_generalHelper::transformDisplayQuantity(", $checkoutContents);
        self::assertStringContainsString("\$arrItem['displayQuantityUnit'] = \$objProductOrVariant->_displayQuantityUnit;", $checkoutContents);
        self::assertStringContainsString("\$arrItem['salesUnit'] = \$objProductOrVariant->_salesUnit;", $checkoutContents);
        self::assertStringContainsString("'_displayQuantityUnit_customerLanguage'", $checkoutContents);
        self::assertStringContainsString("'_salesUnit_customerLanguage'", $checkoutContents);
        self::assertStringContainsString("`salesUnitSize` = ?", $checkoutContents);
        self::assertStringContainsString("`displayQuantity` = ?", $checkoutContents);
        self::assertStringContainsString("`displayQuantityUnit` = ?", $checkoutContents);
        self::assertStringContainsString("`salesUnit` = ?", $checkoutContents);
    }

    public function testCartRuntimeBuildsDisplayQuantityFieldsAndMessages(): void
    {
        $cartControllerContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'classes/ls_shop_cartX.php');
        $cartHelperContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'helpers/ls_shop_cartHelper.php');
        $cartTemplateContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'templates/template_cart--big_include_cart.html5');

        self::assertStringContainsString("'displayQuantity' => \$displayQuantity", $cartControllerContents);
        self::assertStringContainsString("'displayQuantityDecimals' => ls_shop_generalHelper::getDisplayQuantityDecimals(", $cartControllerContents);
        self::assertStringContainsString("'displayQuantityUnit' => \$objProduct->_displayQuantityUnit", $cartControllerContents);
        self::assertStringContainsString("'salesUnit' => \$objProduct->_salesUnit", $cartControllerContents);

        self::assertStringContainsString("'displayDesiredQuantity' => ls_shop_generalHelper::transformDisplayQuantity(", $cartHelperContents);
        self::assertStringContainsString("'displayAvailableQuantity' => ls_shop_generalHelper::transformDisplayQuantity(", $cartHelperContents);
        self::assertStringContainsString("'displayQuantityPutInCart' => ls_shop_generalHelper::transformDisplayQuantity(", $cartHelperContents);
        self::assertStringContainsString("'displayOriginalQuantity' => ls_shop_generalHelper::transformDisplayQuantity(", $cartHelperContents);
        self::assertStringContainsString("'displayNewQuantity' => ls_shop_generalHelper::transformDisplayQuantity(", $cartHelperContents);

        self::assertStringContainsString("\$msgDetails['displayDesiredQuantity']", $cartTemplateContents);
        self::assertStringContainsString("\$cartItem['displayQuantity']", $cartTemplateContents);
        self::assertStringContainsString("\$obj_tmp_productOrVariant->_displayQuantityUnit", $cartTemplateContents);
    }

    public function testQuantityInputsExposeDisplayStepAttributesAndStepperActivation(): void
    {
        $quantityHelperContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'helpers/ls_shop_generalHelper.php');
        $moduleCartContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'frontendModules/ModuleCart.php');
        $numberTemplateContents = (string) file_get_contents(self::HELPERS_BASE_PATH . 'templates/flexWidget/ls_flexWidget_defaultNumber.html5');

        self::assertStringContainsString('useNumberStepper', $quantityHelperContents);
        self::assertStringContainsString("'step' => (string) \$obj_productOrVariant->_displayQuantityStep", $quantityHelperContents);
        self::assertStringContainsString("\$quantityInputState = self::getQuantityInputState(\$obj_productOrVariant);", $quantityHelperContents);
        self::assertStringContainsString("'min' => \$quantityInputState['min']", $quantityHelperContents);
        self::assertStringContainsString("'max' => '999999999'", $quantityHelperContents);

        self::assertStringContainsString('useNumberStepper', $moduleCartContents);
        self::assertStringContainsString("'step' => (string) \$cartItem['objProduct']->_displayQuantityStep", $moduleCartContents);
        self::assertStringContainsString("\$quantityInputState = ls_shop_generalHelper::getQuantityInputState(", $moduleCartContents);
        self::assertStringContainsString("'min' => \$quantityInputState['min']", $moduleCartContents);
        self::assertStringContainsString("'max' => '999999999'", $moduleCartContents);

        self::assertStringContainsString("min=\"<?php echo \$this->arr_moreData['min'] ?? '0'; ?>\"", $numberTemplateContents);
        self::assertStringContainsString("max=\"<?php echo \$this->arr_moreData['max']; ?>\"", $numberTemplateContents);
        self::assertStringContainsString("step=\"<?php echo \$this->arr_moreData['step']; ?>\"", $numberTemplateContents);
    }

    public function testBackendOrderTemplatesUseInternalQuantityAndDisplayQuantityUnit(): void
    {
        foreach ([
            'template_beOrderRepresentationDetails_01.html5',
            'template_beOrderRepresentationDetails_02.html5',
            'template_beOrderRepresentationDetails_03.html5',
        ] as $templateFile) {
            $templateContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'templates/' . $templateFile);

            self::assertStringContainsString("\$cartItem['displayQuantityUnit']", $templateContents);
            self::assertStringContainsString("\$cartItem['quantityDecimals'] ?? 0", $templateContents);
            self::assertStringContainsString("\$cartItem['quantity']", $templateContents);
        }
    }

    public function testBackendStockManagementShowsRawStockWithSalesUnitContext(): void
    {
        $templateContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'templates/template_productBackendOverview_04.html5');

        self::assertStringContainsString("outputQuantity(\$this->objProduct->_stock, (int) \$this->objProduct->_quantityDecimals)", $templateContents);
        self::assertStringContainsString("\$this->objProduct->_hasSalesUnit ? ' × '.\$this->objProduct->_displayQuantityUnit : ' '.\$this->objProduct->_quantityUnit", $templateContents);
        self::assertStringContainsString("outputQuantity(\$variant->_stock, (int) \$variant->_quantityDecimals)", $templateContents);
        self::assertStringContainsString("\$variant->_hasSalesUnit ? ' × '.\$variant->_displayQuantityUnit : ' '.\$variant->_quantityUnit", $templateContents);
    }

    public function testPaymentAndTrackingKeepInternalQuantityBasis(): void
    {
        $payPalContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'paymentModules/ls_shop_paymentModule_payPal.php');
        $payPalPlusContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'paymentModules/ls_shop_paymentModule_payPalPlus.php');
        $payoneContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'paymentModules/ls_shop_paymentModule_payone.php');
        $payPalCheckoutContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'apiControllers/ls_shop_apiController_payment_payPalCheckout.php');
        $afterCheckoutTemplateContents = (string) file_get_contents(self::MERCONIS_BASE_PATH . 'templates/template_afterCheckout_default.html5');

        self::assertStringContainsString("cartItemExtended['quantity']", $payPalContents);
        self::assertStringContainsString("objProduct']->_displayQuantityUnit", $payPalContents);
        self::assertStringContainsString("arr_cartItemExtended['quantity']", $payPalPlusContents);
        self::assertStringContainsString("objProduct']->_displayQuantityUnit", $payPalPlusContents);
        self::assertStringContainsString("arr_item['quantity']", $payoneContents);
        self::assertStringContainsString("arr_item['displayQuantityUnit']", $payoneContents);
        self::assertStringContainsString("cartItemExtended['quantity']", $payPalCheckoutContents);
        self::assertStringContainsString("objProduct']->_displayQuantityUnit", $payPalCheckoutContents);

        self::assertStringContainsString("'quantity' => \\Merconis\\Core\\ls_shop_generalHelper::outputQuantity(\$cartItem['quantity']", $afterCheckoutTemplateContents);
        self::assertStringContainsString("'price' => (float) \$cartItem['price']", $afterCheckoutTemplateContents);
    }
}
