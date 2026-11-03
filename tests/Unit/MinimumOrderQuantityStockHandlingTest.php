<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\MinimumOrderQuantityCalculator;
use PHPUnit\Framework\TestCase;

final class MinimumOrderQuantityStockHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_CONFIG'] ??= [];
        unset($GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling']);
    }

    public function testDenyOrderModeBlocksOrderWhenAvailableStockFallsBelowMinimumOrderQuantity(): void
    {
        $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] = 'denyOrder';

        self::assertTrue(
            MinimumOrderQuantityCalculator::shouldDenyOrderDueToStockConflict(
                '5',
                '2',
                0
            )
        );
    }

    public function testAllowAvailableQuantityModeDoesNotBlockOrder(): void
    {
        $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] = 'allowAvailableQuantity';

        self::assertFalse(
            MinimumOrderQuantityCalculator::shouldDenyOrderDueToStockConflict(
                '5',
                '2',
                0
            )
        );
    }

    public function testAllowAvailableQuantityModeUsesRemainingStockAsEffectiveMinimum(): void
    {
        $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] = 'allowAvailableQuantity';

        self::assertSame(
            '2',
            MinimumOrderQuantityCalculator::resolveMinimumOrderQuantityForStockHandling(
                '5',
                '2',
                0
            )
        );
    }

    public function testZeroAvailableStockDoesNotTriggerMinimumOrderQuantityStockConflict(): void
    {
        $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] = 'allowAvailableQuantity';

        self::assertFalse(
            MinimumOrderQuantityCalculator::hasStockConflictWithMinimumOrderQuantity(
                '5',
                '0',
                0
            )
        );
        self::assertSame(
            '5',
            MinimumOrderQuantityCalculator::resolveMinimumOrderQuantityForStockHandling(
                '5',
                '0',
                0
            )
        );
    }
}
