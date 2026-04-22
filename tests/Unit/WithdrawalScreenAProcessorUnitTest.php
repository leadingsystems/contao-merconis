<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenAProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalScreenAProcessorUnitTest extends TestCase
{
    public function testHoneypotEmptyValueIsAccepted(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        self::assertFalse($processor->isHoneypotTriggered(''));
        self::assertFalse($processor->isHoneypotTriggered('   '));
    }

    public function testHoneypotFilledValueIsDetected(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        self::assertTrue($processor->isHoneypotTriggered('bot-value'));
    }

    public function testRateLimitThresholdCheckUsesGreaterOrEqual(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        self::assertFalse($processor->isRateLimitExceeded(19, 20));
        self::assertTrue($processor->isRateLimitExceeded(20, 20));
        self::assertTrue($processor->isRateLimitExceeded(21, 20));
    }
}
