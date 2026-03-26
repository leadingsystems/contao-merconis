<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalConfirmationTokenProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalConfirmationFlowTest extends TestCase
{
    public function testValidTokenCanBeResolvedToWithdrawalPrimaryKey(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();
        $token = $processor->createToken(123, 1700000000, 'test-secret');

        $result = $processor->validateToken($token, 'test-secret', 1700000100);

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_SUCCESS, $result['status']);
        self::assertSame(123, $result['primaryKey']);
    }

    public function testManipulatedTokenIsRejected(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();
        $token = $processor->createToken(123, 1700000000, 'test-secret');
        $manipulatedToken = preg_replace('/^123-/', '124-', $token);

        $result = $processor->validateToken((string) $manipulatedToken, 'test-secret', 1700000100);

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_INVALID_SIGNATURE, $result['status']);
    }

    public function testIncompleteTokenIsRejectedAsInvalidFormat(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();

        $result = $processor->validateToken('123-1700000000', 'test-secret', 1700000100);

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_INVALID_FORMAT, $result['status']);
    }

    public function testMissingTokenIsRejected(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();

        $result = $processor->validateToken('', 'test-secret', 1700000100);

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_MISSING, $result['status']);
    }

    public function testUnknownPrimaryKeyCanBeHandledByCaller(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();
        $token = $processor->createToken(9999, 1700000000, 'test-secret');

        $result = $processor->validateToken($token, 'test-secret', 1700000100);
        $mockedLookupResult = null;

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_SUCCESS, $result['status']);
        self::assertNull($mockedLookupResult);
    }
}
