<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\InsertTag\AsInsertTag\OrderWithdrawalIdentifier;
use PHPUnit\Framework\TestCase;

final class OrderWithdrawalIdentifierInsertTagTest extends TestCase
{
    public function testInsertTagReturnsWithdrawalIdentifierInOrderContext(): void
    {
        $insertTag = new class () extends OrderWithdrawalIdentifier {
            protected function resolveCurrentOrder(): ?array
            {
                return ['withdrawalIdentifier' => '2026000001-G54FQ9'];
            }
        };

        self::assertSame(
            '2026000001-G54FQ9',
            $insertTag->customInserttags('shop_order_withdrawal_identifier', [])
        );
    }

    public function testInsertTagReturnsEmptyStringOutsideOrderContext(): void
    {
        $insertTag = new class () extends OrderWithdrawalIdentifier {
            protected function resolveCurrentOrder(): ?array
            {
                return null;
            }
        };

        self::assertSame(
            '',
            $insertTag->customInserttags('shop_order_withdrawal_identifier', [])
        );
    }
}
