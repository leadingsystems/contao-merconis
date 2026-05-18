<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_apiController_cart;
use Merconis\Core\ls_shop_cartHelper;
use PHPUnit\Framework\TestCase;

final class CartCommentHandlingTest extends TestCase
{
    public function testAddToCartCommentIsStoredForNewPosition(): void
    {
        $cartItem = ls_shop_cartHelper::applyAddToCartComment(
            [
                'quantity' => 0,
                'scalePriceKeyword' => 'default',
            ],
            'Bitte separat verpacken',
            true
        );

        self::assertSame('Bitte separat verpacken', $cartItem['comment']);
    }

    public function testAddToCartCommentIsMergedForExistingPosition(): void
    {
        $cartItem = ls_shop_cartHelper::applyAddToCartComment(
            [
                'quantity' => 2,
                'scalePriceKeyword' => 'default',
                'comment' => 'Erste Zeile',
            ],
            'Zweite Zeile',
            true
        );

        self::assertSame("Erste Zeile\nZweite Zeile", $cartItem['comment']);
    }

    public function testAddToCartWithoutCommentKeepsExistingComment(): void
    {
        $cartItem = ls_shop_cartHelper::applyAddToCartComment(
            [
                'quantity' => 2,
                'scalePriceKeyword' => 'default',
                'comment' => 'Bestehender Kommentar',
            ],
            null,
            false
        );

        self::assertSame('Bestehender Kommentar', $cartItem['comment']);
    }

    public function testUpdatedCommentReplacesExistingComment(): void
    {
        $cartItem = ls_shop_cartHelper::applyUpdatedComment(
            [
                'quantity' => 2,
                'scalePriceKeyword' => 'default',
                'comment' => 'Alt',
            ],
            'Neu',
            true
        );

        self::assertSame('Neu', $cartItem['comment']);
    }

    public function testUpdatedCommentAllowsRemovalWithEmptyString(): void
    {
        $cartItem = ls_shop_cartHelper::applyUpdatedComment(
            [
                'quantity' => 2,
                'scalePriceKeyword' => 'default',
                'comment' => 'Wird entfernt',
            ],
            '',
            true
        );

        self::assertSame('', $cartItem['comment']);
    }

    public function testApiRequestPassesCommentToAddToCart(): void
    {
        $capturedArguments = null;

        $result = ls_shop_apiController_cart::processAddToCartRequest(
            [
                'productVariantId' => '123',
                'quantity' => '2',
                'comment' => 'API-Kommentar',
                'commentWasSubmitted' => true,
            ],
            function (...$arguments) use (&$capturedArguments): array {
                $capturedArguments = $arguments;

                return ['ok' => true];
            }
        );

        self::assertTrue($result['success']);
        self::assertSame(['ok' => true], $result['data']);
        self::assertSame(
            ['123', '2', true, 'API-Kommentar', true],
            $capturedArguments
        );
    }

    public function testApiRequestWithoutCommentKeepsCommentFlagFalse(): void
    {
        $capturedArguments = null;

        $result = ls_shop_apiController_cart::processAddToCartRequest(
            [
                'productVariantId' => '123',
                'quantity' => '2',
            ],
            function (...$arguments) use (&$capturedArguments): array {
                $capturedArguments = $arguments;

                return ['ok' => true];
            }
        );

        self::assertTrue($result['success']);
        self::assertSame(['ok' => true], $result['data']);
        self::assertSame(
            ['123', '2', true, null, false],
            $capturedArguments
        );
    }
}
