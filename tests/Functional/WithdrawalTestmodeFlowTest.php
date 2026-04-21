<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalConfirmationTokenProcessor;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalTestmodeProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalTestmodeFlowTest extends TestCase
{
    public function testValidTestmodeOrderEnablesPreviewWithPlaceholderConfirmationReference(): void
    {
        $testmodeProcessor = new WithdrawalTestmodeProcessor();
        $tokenProcessor = new WithdrawalConfirmationTokenProcessor();

        $arrOrder = $testmodeProcessor->resolveOrder(
            'valid-oih',
            static fn (string $orderIdentificationHash): ?array => $orderIdentificationHash === 'valid-oih'
                ? [
                    'orderNr' => '2026000001',
                    'customerData' => [
                        'personalData' => [
                            'email' => 'kunde@example.com',
                        ],
                    ],
                ]
                : null
        );

        $token = $tokenProcessor->createToken(
            WithdrawalTestmodeProcessor::PLACEHOLDER_WITHDRAWAL_ID,
            1700000000,
            'test-secret'
        );
        $validationResult = $tokenProcessor->validateToken($token, 'test-secret', 1700000100);

        self::assertNotNull($arrOrder);
        self::assertSame('2026000001', $arrOrder['orderNr']);
        self::assertSame('kunde@example.com', $arrOrder['customerData']['personalData']['email']);
        self::assertSame(WithdrawalConfirmationTokenProcessor::STATUS_SUCCESS, $validationResult['status']);
        self::assertSame(WithdrawalTestmodeProcessor::PLACEHOLDER_WITHDRAWAL_ID, $validationResult['reference']);
    }

    public function testInvalidTestmodeOrderLeavesPreviewUnavailable(): void
    {
        $testmodeProcessor = new WithdrawalTestmodeProcessor();

        $arrOrder = $testmodeProcessor->resolveOrder(
            'invalid-oih',
            static fn (): ?array => null
        );

        self::assertNull($arrOrder);
    }
}
