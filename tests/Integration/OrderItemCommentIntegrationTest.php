<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class OrderItemCommentIntegrationTest extends TestCase
{
    private const BASE_PATH = __DIR__ . '/../../src/Resources/contao/';

    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TL_DCA']['tl_ls_shop_orders_items']);
    }

    public function testOrderItemsDcaContainsCommentField(): void
    {
        require self::BASE_PATH . 'dca/tl_ls_shop_orders_items.php';

        self::assertArrayHasKey('tl_ls_shop_orders_items', $GLOBALS['TL_DCA']);
        self::assertSame(
            'blob NULL',
            $GLOBALS['TL_DCA']['tl_ls_shop_orders_items']['fields']['comment']['sql']
        );
    }

    public function testCheckoutPersistsCommentIntoOrderItemsTable(): void
    {
        $checkoutContents = (string) file_get_contents(self::BASE_PATH . 'classes/ls_shop_checkout.php');

        self::assertStringContainsString('`comment` = ?,', $checkoutContents);
        self::assertStringContainsString("\$arrItem['comment']", $checkoutContents);
    }

    public function testLanguageFilesContainOrderCommentLabel(): void
    {
        $defaultLanguageDe = (string) file_get_contents(self::BASE_PATH . 'languages/de/default.php');
        $defaultLanguageEn = (string) file_get_contents(self::BASE_PATH . 'languages/en/default.php');

        self::assertStringContainsString("['ls_shop']['orderComment']['label']", $defaultLanguageDe);
        self::assertStringContainsString("['ls_shop']['orderComment']['label']", $defaultLanguageEn);
        self::assertStringContainsString("'Kommentar'", $defaultLanguageDe);
        self::assertStringContainsString("'Comment'", $defaultLanguageEn);
    }

    public function testPostCheckoutAndMailTemplatesRenderEscapedNl2brComment(): void
    {
        $afterCheckoutTemplate = (string) file_get_contents(self::BASE_PATH . 'templates/template_afterCheckout--cart.html5');
        $mailTemplate = (string) file_get_contents(self::BASE_PATH . 'templates/template_mail_orderConfirmation.html5');

        self::assertStringContainsString("['orderComment']['label']", $afterCheckoutTemplate);
        self::assertStringContainsString("['orderComment']['label']", $mailTemplate);
        self::assertStringContainsString(
            "nl2br(\\Contao\\StringUtil::specialchars(\$cartItem['comment']))",
            $afterCheckoutTemplate
        );
        self::assertStringContainsString(
            "nl2br(\\Contao\\StringUtil::specialchars(\$cartItem['comment']))",
            $mailTemplate
        );
    }

    public function testBackendOrderTemplatesRenderEscapedNl2brComment(): void
    {
        $template01 = (string) file_get_contents(self::BASE_PATH . 'templates/template_beOrderRepresentationDetails_01.html5');
        $template02 = (string) file_get_contents(self::BASE_PATH . 'templates/template_beOrderRepresentationDetails_02.html5');
        $template03 = (string) file_get_contents(self::BASE_PATH . 'templates/template_beOrderRepresentationDetails_03.html5');

        self::assertStringContainsString("['orderComment']['label']", $template01);
        self::assertStringContainsString("['orderComment']['label']", $template02);
        self::assertStringContainsString("['orderComment']['label']", $template03);
        self::assertStringContainsString(
            "nl2br(\\Contao\\StringUtil::specialchars(\$cartItem['comment']))",
            $template01
        );
        self::assertStringContainsString(
            "nl2br(\\Contao\\StringUtil::specialchars(\$cartItem['comment']))",
            $template02
        );
        self::assertStringContainsString(
            "nl2br(\\Contao\\StringUtil::specialchars(\$cartItem['comment']))",
            $template03
        );
    }

    public function testOrderReviewTemplateNoLongerSuppressesExistingComment(): void
    {
        $reviewTemplate = (string) file_get_contents(self::BASE_PATH . 'templates/template_cart--big_include_cart.html5');

        self::assertStringContainsString(
            "\$this->arrWidgets[\$productCartKey]['formattedComment'] !== ''",
            $reviewTemplate
        );
        self::assertStringNotContainsString(
            "!\$bln_reviewMode && \$this->arrWidgets[\$productCartKey]['formattedComment'] !== ''",
            $reviewTemplate
        );
    }
}
