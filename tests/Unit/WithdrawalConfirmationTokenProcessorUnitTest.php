<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalConfirmationTokenProcessor;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalTestmodeProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalConfirmationTokenProcessorUnitTest extends TestCase
{
    public function testTokenCreationUsesExpectedHmacPayloadFormat(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();
        $token = $processor->createToken(42, 1700000000, 'test-secret');

        self::assertSame(
            '42-1700000000-a7e1827ab1d08be6b92202accbe2d95c23fd15386efec1c4c0d3b480af445688',
            $token
        );
    }

    public function testInvalidSignatureIsRejected(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();
        $token = $processor->createToken(77, 1700000000, 'correct-secret');

        $result = $processor->validateToken($token, 'other-secret', 1700000100);

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_INVALID_SIGNATURE, $result['status']);
    }

    public function testTimestampValidationAcceptsWithinOneHourAndRejectsOlderToken(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();
        $token = $processor->createToken(99, 1700000000, 'test-secret');

        $validResult = $processor->validateToken($token, 'test-secret', 1700003599);
        $expiredResult = $processor->validateToken($token, 'test-secret', 1700003601);

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_SUCCESS, $validResult['status']);
        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_EXPIRED, $expiredResult['status']);
    }

    public function testPlaceholderWithdrawalIdentifierCanBeSignedAndValidated(): void
    {
        $processor = new WithdrawalConfirmationTokenProcessor();
        $token = $processor->createToken(
            WithdrawalTestmodeProcessor::PLACEHOLDER_WITHDRAWAL_ID,
            1700000000,
            'test-secret'
        );

        $result = $processor->validateToken($token, 'test-secret', 1700000100);

        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_SUCCESS, $result['status']);
        self::assertSame(WithdrawalTestmodeProcessor::PLACEHOLDER_WITHDRAWAL_ID, $result['reference']);
        self::assertArrayNotHasKey('primaryKey', $result);
    }
}
