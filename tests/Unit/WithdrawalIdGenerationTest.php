<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_checkout;
use PHPUnit\Framework\TestCase;

final class WithdrawalIdGenerationTest extends TestCase
{
    public function testWithdrawalIdHasExpectedFormat(): void
    {
        $checkout = new CheckoutWithdrawalIdTestDouble(0);

        $withdrawalId = $checkout->generateWithdrawalId();

        self::assertSame('W-00001', $withdrawalId);
    }

    public function testCounterIsIncrementedWhenExistingCounterIsPresent(): void
    {
        $checkout = new CheckoutWithdrawalIdTestDouble(14);

        $withdrawalId = $checkout->generateWithdrawalId();

        self::assertSame('W-00015', $withdrawalId);
        self::assertSame(15, $checkout->getPersistedCounterValue());
    }

    public function testMissingCounterStartsAtOne(): void
    {
        $checkout = new CheckoutWithdrawalIdTestDouble(null);

        $withdrawalId = $checkout->generateWithdrawalId();

        self::assertSame('W-00001', $withdrawalId);
        self::assertSame(1, $checkout->getPersistedCounterValue());
    }

    public function testZeroCounterStartsAtOne(): void
    {
        $checkout = new CheckoutWithdrawalIdTestDouble(0);

        $withdrawalId = $checkout->generateWithdrawalId();

        self::assertSame('W-00001', $withdrawalId);
        self::assertSame(1, $checkout->getPersistedCounterValue());
    }
}

final class CheckoutWithdrawalIdTestDouble extends ls_shop_checkout
{
    private ?int $configuredCounter;
    private ?int $persistedCounterValue = null;

    public function __construct(?int $configuredCounter)
    {
        $this->configuredCounter = $configuredCounter;
    }

    protected function getWithdrawalIdCounterFromConfig()
    {
        return $this->configuredCounter;
    }

    protected function reloadConfig()
    {
    }

    protected function persistWithdrawalIdCounter($nextCounter)
    {
        $this->persistedCounterValue = (int) $nextCounter;
    }

    public function getPersistedCounterValue(): ?int
    {
        return $this->persistedCounterValue;
    }
}
