<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalTestmodeProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalTestmodeProcessorUnitTest extends TestCase
{
    public function testResolveOrderReturnsOrderForValidTestmodeParameter(): void
    {
        $processor = new WithdrawalTestmodeProcessor();

        $arrOrder = $processor->resolveOrder(
            'valid-oih',
            static fn (string $orderIdentificationHash): ?array => $orderIdentificationHash === 'valid-oih'
                ? ['id' => 123, 'orderNr' => '2026000001']
                : null
        );

        self::assertSame(['id' => 123, 'orderNr' => '2026000001'], $arrOrder);
    }

    public function testResolveOrderReturnsNullForInvalidTestmodeParameter(): void
    {
        $processor = new WithdrawalTestmodeProcessor();

        $arrOrder = $processor->resolveOrder(
            'invalid-oih',
            static fn (string $orderIdentificationHash): ?array => $orderIdentificationHash === 'valid-oih'
                ? ['id' => 123]
                : null
        );

        self::assertNull($arrOrder);
    }

    public function testResolveOrderReturnsNullWhenTestmodeParameterIsMissing(): void
    {
        $processor = new WithdrawalTestmodeProcessor();

        $arrOrder = $processor->resolveOrder(
            '   ',
            static fn (string $orderIdentificationHash): ?array => ['id' => 123, 'value' => $orderIdentificationHash]
        );

        self::assertNull($arrOrder);
        self::assertFalse($processor->hasTestmodeParameter('   '));
    }
}
