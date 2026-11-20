<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingOrderMessageHook;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingMessageTypeContext;
use PHPUnit\Framework\TestCase;

final class EInvoicingOrderMessageHookTest extends TestCase
{
    public function testHookReplacesPaymentTermsPlaceholdersFromPaymentMethod(): void
    {
        $GLOBALS['TL_CONFIG']['dateFormat'] = 'Y-m-d';

        $hook = new EInvoicingOrderMessageHook(new EInvoicingMessageTypeContext());
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

        $hook = new EInvoicingOrderMessageHook(new EInvoicingMessageTypeContext());
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

    public function testHookStoresTheMessageTypeOfTheMessageModelForTheOrder(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $hook = new EInvoicingOrderMessageHook($messageTypeContext);

        $hook->beforeSendingOrderMessage($this->createMessageModel('4'), $this->createOrder('84'));

        self::assertSame(4, $messageTypeContext->getMessageTypeIdForOrder(84));
    }

    public function testHookStoresTheContextEvenWithoutMultilanguageContent(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $hook = new EInvoicingOrderMessageHook($messageTypeContext);
        $messageModel = ['pid' => '4'];

        $result = $hook->beforeSendingOrderMessage($messageModel, $this->createOrder('84'));

        self::assertSame($messageModel, $result);
        self::assertSame(4, $messageTypeContext->getMessageTypeIdForOrder(84));
    }

    public function testLaterHookCallReplacesTheContextOfAnEarlierOrderAndMessageModel(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $hook = new EInvoicingOrderMessageHook($messageTypeContext);

        $hook->beforeSendingOrderMessage($this->createMessageModel('1'), $this->createOrder('83'));
        $hook->beforeSendingOrderMessage($this->createMessageModel('4'), $this->createOrder('84'));

        self::assertSame(4, $messageTypeContext->getMessageTypeIdForOrder(84));
        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(83));
    }

    public function testMessageModelWithoutValidMessageTypeClearsAnEarlierContext(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $hook = new EInvoicingOrderMessageHook($messageTypeContext);

        $hook->beforeSendingOrderMessage($this->createMessageModel('4'), $this->createOrder('84'));
        $hook->beforeSendingOrderMessage($this->createMessageModel(''), $this->createOrder('84'));

        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(84));
    }

    public function testOrderWithoutValidIdLeavesNoUsableContext(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $hook = new EInvoicingOrderMessageHook($messageTypeContext);

        $hook->beforeSendingOrderMessage($this->createMessageModel('4'), $this->createOrder('84'));
        $hook->beforeSendingOrderMessage($this->createMessageModel('4'), $this->createOrder('abc'));

        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(84));
        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(0));
    }

    /**
     * @return array<string, mixed>
     */
    private function createMessageModel(string $messageTypeId): array
    {
        return [
            'pid' => $messageTypeId,
            'messageLanguage' => 'de',
            'multilanguage' => [
                'subject' => 'Rechnung',
                'content_html' => '',
                'content_rawtext' => '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createOrder(string $orderId): array
    {
        return [
            'id' => $orderId,
            'customerLanguage' => 'de',
            'paymentMethod' => [],
            'memberGroup' => [],
        ];
    }
}
