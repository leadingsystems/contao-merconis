<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_checkout;
use PHPUnit\Framework\TestCase;

final class WithdrawalTokenGenerationTest extends TestCase
{
    public function testTokenHasExactlySixCharacters(): void
    {
        $checkout = new ls_shop_checkout();
        $token = $checkout->generateWithdrawalToken();

        self::assertSame(6, strlen($token));
    }

    public function testTokenContainsOnlyAllowedCharacters(): void
    {
        $checkout = new ls_shop_checkout();
        $token = $checkout->generateWithdrawalToken();

        self::assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', $token);
    }

    public function testTokenDoesNotContainForbiddenCharacters(): void
    {
        $checkout = new ls_shop_checkout();
        $token = $checkout->generateWithdrawalToken();

        self::assertMatchesRegularExpression('/^[^IO10]+$/', $token);
    }

    public function testWithdrawalIdentifierHasExpectedFormat(): void
    {
        $checkout = new ls_shop_checkout();
        $token = $checkout->generateWithdrawalToken();
        $orderNumber = 'ORDER-12345';
        $withdrawalIdentifier = $orderNumber . '-' . $token;

        self::assertMatchesRegularExpression(
            '/^ORDER-12345-[A-HJ-NP-Z2-9]{6}$/',
            $withdrawalIdentifier
        );
    }
}
