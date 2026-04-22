<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenCProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalScreenCProcessorUnitTest extends TestCase
{
    public function testHoneypotEmptyValueIsAccepted(): void
    {
        $processor = new WithdrawalScreenCProcessor();

        self::assertFalse($processor->isHoneypotTriggered(''));
        self::assertFalse($processor->isHoneypotTriggered('   '));
    }

    public function testHoneypotFilledValueIsDetected(): void
    {
        $processor = new WithdrawalScreenCProcessor();

        self::assertTrue($processor->isHoneypotTriggered('bot-value'));
    }

    public function testIpRateLimitThresholdCheckUsesGreaterOrEqual(): void
    {
        $processor = new WithdrawalScreenCProcessor();

        self::assertFalse($processor->isRateLimitExceeded(4, 5));
        self::assertTrue($processor->isRateLimitExceeded(5, 5));
        self::assertTrue($processor->isRateLimitExceeded(6, 5));
    }

    public function testRecipientRateLimitThresholdCheckUsesGreaterOrEqual(): void
    {
        $processor = new WithdrawalScreenCProcessor();

        self::assertFalse($processor->isRateLimitExceeded(2, 3));
        self::assertTrue($processor->isRateLimitExceeded(3, 3));
        self::assertTrue($processor->isRateLimitExceeded(4, 3));
    }
}
