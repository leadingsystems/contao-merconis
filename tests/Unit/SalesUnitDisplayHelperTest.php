<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\TestCase;

final class SalesUnitDisplayHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = '.';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = ',';
    }

    public function testTransformDisplayQuantityMultipliesInternalQuantityWhenSalesUnitsAreActive(): void
    {
        self::assertSame('20', (string) ls_shop_generalHelper::transformDisplayQuantity('0.2', 100));
    }

    public function testGetDisplayQuantityDecimalsRemovesSuperfluousTrailingZerosFromPieceStep(): void
    {
        self::assertSame(0, ls_shop_generalHelper::getDisplayQuantityDecimals(1, 100));
        self::assertSame(2, ls_shop_generalHelper::getDisplayQuantityDecimals(2, 25));
    }

    public function testOutputDisplayQuantityUsesDerivedDisplayPrecision(): void
    {
        self::assertSame('20', ls_shop_generalHelper::outputDisplayQuantity('0.2', 1, 100, '.', ''));
        self::assertSame('0.25', ls_shop_generalHelper::outputDisplayQuantity('0.01', 2, 25, '.', ''));
        self::assertSame('2.50', ls_shop_generalHelper::outputDisplayQuantity('2.5', 2, 0, '.', ''));
    }
}
