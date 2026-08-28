<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\Helpers\FlexWidget;
use Merconis\Core\FlexWidgetValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class FlexWidgetValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = '.';
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = ',';
        $GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimalsFE'] =
            'Field "%s" must contain a number greater than 0';
        $GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['quantityStepMultipleFE'] =
            'Field "%s" contains an invalid quantity. The next valid value is %s.';
    }

    public function testGetterReturnsMoreData(): void
    {
        $widget = $this->createWidget('1', 'Quantity', ['salesUnitSize' => 100, 'quantityDecimals' => 1]);

        self::assertSame(
            ['salesUnitSize' => 100, 'quantityDecimals' => 1],
            $widget->getMoreData()
        );
    }

    public function testQuantityInputAcceptsActiveSalesUnitMultiples(): void
    {
        $widget = $this->createWidget('20', 'Quantity', ['salesUnitSize' => 100, 'quantityDecimals' => 1]);

        FlexWidgetValidator::quantityInput($widget);

        self::addToAssertionCount(1);
    }

    public function testQuantityInputRejectsInvalidActiveSalesUnitMultiple(): void
    {
        $widget = $this->createWidget('15', 'Quantity', ['salesUnitSize' => 100, 'quantityDecimals' => 1]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The next valid value is 20');

        FlexWidgetValidator::quantityInput($widget);
    }

    public function testQuantityInputRejectsInvalidInactiveStepWithoutFloatArtifacts(): void
    {
        $widget = $this->createWidget('0.15', 'Quantity', ['salesUnitSize' => 0, 'quantityDecimals' => 1]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The next valid value is 0.2');

        FlexWidgetValidator::quantityInput($widget);
    }

    public function testQuantityInputAllowsNonPositiveCartRemovalValues(): void
    {
        $widget = $this->createWidget(
            '0',
            'Quantity',
            ['salesUnitSize' => 0, 'quantityDecimals' => 1, 'allowNonPositiveQuantity' => true]
        );

        FlexWidgetValidator::quantityInput($widget);

        self::addToAssertionCount(1);
    }

    private function createWidget(string $value, string $label, array $moreData): FlexWidget
    {
        $widget = (new ReflectionClass(FlexWidget::class))->newInstanceWithoutConstructor();

        $this->setProtectedProperty($widget, 'var_value', $value);
        $this->setProtectedProperty($widget, 'str_label', $label);
        $this->setProtectedProperty($widget, 'arr_moreData', $moreData);

        return $widget;
    }

    private function setProtectedProperty(object $object, string $propertyName, mixed $value): void
    {
        $property = new ReflectionProperty($object, $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
