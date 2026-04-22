<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenAProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalScreenAFlowTest extends TestCase
{
    public function testEmptyInputReturnsEmptyStatus(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        $result = $processor->process(
            '',
            '',
            0,
            0,
            20,
            static fn (string $normalizedIdentifier): ?string => null
        );

        self::assertSame(WithdrawalScreenAProcessor::STATUS_EMPTY, $result['status']);
        self::assertSame(0, $result['failedAttempts']);
        self::assertFalse($result['highlightFallback']);
    }

    public function testValidIdentifierReturnsSuccessAndResetsFailedAttempts(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        $result = $processor->process(
            ' 2026000001-g54fq9 ',
            '',
            2,
            1,
            20,
            static fn (string $normalizedIdentifier): ?string => $normalizedIdentifier === '2026000001G54FQ9'
                ? '2026000001-G54FQ9'
                : null
        );

        self::assertSame(WithdrawalScreenAProcessor::STATUS_SUCCESS, $result['status']);
        self::assertSame(0, $result['failedAttempts']);
        self::assertSame('2026000001G54FQ9', $result['normalizedIdentifier']);
        self::assertSame('2026000001-G54FQ9', $result['canonicalIdentifier']);
    }

    public function testInvalidIdentifierIncrementsFailedAttempts(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        $result = $processor->process(
            '2026000001-XXXXXX',
            '',
            1,
            5,
            20,
            static fn (string $normalizedIdentifier): ?string => null
        );

        self::assertSame(WithdrawalScreenAProcessor::STATUS_NOT_FOUND, $result['status']);
        self::assertSame(2, $result['failedAttempts']);
        self::assertFalse($result['highlightFallback']);
    }

    public function testThirdFailedAttemptHighlightsFallback(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        $result = $processor->process(
            '2026000001-XXXXXX',
            '',
            2,
            2,
            20,
            static fn (string $normalizedIdentifier): ?string => null
        );

        self::assertSame(WithdrawalScreenAProcessor::STATUS_NOT_FOUND, $result['status']);
        self::assertSame(3, $result['failedAttempts']);
        self::assertTrue($result['highlightFallback']);
    }
}
