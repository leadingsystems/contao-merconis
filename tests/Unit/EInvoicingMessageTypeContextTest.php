<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingMessageTypeContext;
use PHPUnit\Framework\TestCase;

final class EInvoicingMessageTypeContextTest extends TestCase
{
    public function testWithoutStoredContextNoMessageTypeIsAvailable(): void
    {
        self::assertNull((new EInvoicingMessageTypeContext())->getMessageTypeIdForOrder(84));
    }

    public function testMessageTypeIsOnlyAvailableForTheOrderItWasStoredFor(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $messageTypeContext->remember(84, 4);

        self::assertSame(4, $messageTypeContext->getMessageTypeIdForOrder(84));
        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(85));
    }

    public function testStoringReplacesTheWholeContext(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $messageTypeContext->remember(84, 4);
        $messageTypeContext->remember(84, null);

        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(84));
    }

    public function testContextWithoutOrderIdIsNeverUsed(): void
    {
        $messageTypeContext = new EInvoicingMessageTypeContext();
        $messageTypeContext->remember(null, 4);

        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(0));
        self::assertNull($messageTypeContext->getMessageTypeIdForOrder(-1));
    }
}
