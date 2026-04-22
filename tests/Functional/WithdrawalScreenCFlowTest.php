<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenCProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalScreenCFlowTest extends TestCase
{
    public function testScreenCFlowAcceptsSubmissionWhenRateLimitsAreBelowThreshold(): void
    {
        $processor = new WithdrawalScreenCProcessor();

        $isBlocked = $processor->hasExceededAnyRateLimit(
            4,
            5,
            2,
            3
        );

        self::assertFalse($isBlocked);
        self::assertFalse($processor->isHoneypotTriggered(''));
    }

    public function testScreenCFlowRejectsSubmissionWhenIpRateLimitIsExceeded(): void
    {
        $processor = new WithdrawalScreenCProcessor();

        $isBlocked = $processor->hasExceededAnyRateLimit(
            5,
            5,
            0,
            3
        );

        self::assertTrue($isBlocked);
    }

    public function testScreenCFlowRejectsSubmissionWhenRecipientRateLimitIsExceeded(): void
    {
        $processor = new WithdrawalScreenCProcessor();

        $isBlocked = $processor->hasExceededAnyRateLimit(
            0,
            5,
            3,
            3
        );

        self::assertTrue($isBlocked);
    }
}
