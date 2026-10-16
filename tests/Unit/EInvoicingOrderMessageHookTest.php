<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingOrderMessageHook;
use PHPUnit\Framework\TestCase;

final class EInvoicingOrderMessageHookTest extends TestCase
{
    public function testHookReplacesPaymentTermsPlaceholdersFromPaymentMethod(): void
    {
        $GLOBALS['TL_CONFIG']['dateFormat'] = 'Y-m-d';

        $hook = new EInvoicingOrderMessageHook();
        $messageModel = [
            'messageLanguage' => 'de',
            'multilanguage' => [
                'subject' => 'Fällig am ##einvoicingPaymentDueDate##',
                'content_html' => '<p>##einvoicingPaymentTermsNote##</p>',
                'content_rawtext' => '##einvoicingPaymentTermsDays##',
            ],
        ];
        $order = [
            'customerLanguage' => 'de',
            'paymentMethod' => [
                'einvoicingPaymentTermsDays' => '14',
                'einvoicingPaymentTermsNote_de' => 'Zahlbar innerhalb von 14 Tagen netto.',
            ],
            'memberGroup' => [
                'lsShopEinvoicingPaymentTermsDays' => '',
                'lsShopEinvoicingPaymentTermsNote_de' => '',
            ],
        ];

        $result = $hook->beforeSendingOrderMessage($messageModel, $order);

        self::assertSame('14', $result['multilanguage']['content_rawtext']);
        self::assertSame('<p>Zahlbar innerhalb von 14 Tagen netto.</p>', $result['multilanguage']['content_html']);
        self::assertMatchesRegularExpression('/^Fällig am \d{4}-\d{2}-\d{2}$/', $result['multilanguage']['subject']);
    }

    public function testHookUsesMemberGroupOverrideAndKeepsZero(): void
    {
        $GLOBALS['TL_CONFIG']['dateFormat'] = 'Y-m-d';

        $hook = new EInvoicingOrderMessageHook();
        $messageModel = [
            'messageLanguage' => 'de',
            'multilanguage' => [
                'subject' => '##einvoicingPaymentDueDate##',
                'content_html' => '##einvoicingPaymentTermsNote##',
                'content_rawtext' => '##einvoicingPaymentTermsDays##',
            ],
        ];
        $order = [
            'customerLanguage' => 'de',
            'paymentMethod' => [
                'einvoicingPaymentTermsDays' => '14',
                'einvoicingPaymentTermsNote_de' => 'Später fällig.',
            ],
            'memberGroup' => [
                'lsShopEinvoicingPaymentTermsDays' => '0',
                'lsShopEinvoicingPaymentTermsNote_de' => 'Sofort fällig.',
            ],
        ];

        $result = $hook->beforeSendingOrderMessage($messageModel, $order);

        self::assertSame('0', $result['multilanguage']['content_rawtext']);
        self::assertSame('Sofort fällig.', $result['multilanguage']['content_html']);
        self::assertSame(date('Y-m-d'), $result['multilanguage']['subject']);
    }
}
