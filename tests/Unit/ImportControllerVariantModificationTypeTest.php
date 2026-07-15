<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_importController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class ImportControllerVariantModificationTypeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/helpers/ls_shop_productManagementApiHelper.php';
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/classes/ls_shop_importController.php';
    }

    /**
     * @dataProvider normalizationFallbackProvider
     *
     * @param array<string, mixed> $row
     */
    public function testNormalizationFallsBackToNeutralDefaultWhenValueIsMissingOrEmpty(
        string $typeFieldName,
        string $valueFieldName,
        array $row
    ): void {
        $controller = $this->createControllerWithoutConstructor();

        $normalizedModificationType = $this->invokePrivateMethod(
            $controller,
            'getNormalizedVariantModificationType',
            [$row, $typeFieldName, $valueFieldName]
        );

        self::assertSame('adjustmentPercentaged', $normalizedModificationType);
    }

    /**
     * @dataProvider normalizationTranslationProvider
     *
     * @param array<string, mixed> $row
     */
    public function testNormalizationTranslatesAllowedModificationTypes(
        string $typeFieldName,
        string $valueFieldName,
        array $row,
        string $expectedModificationType
    ): void {
        $controller = $this->createControllerWithoutConstructor();

        $normalizedModificationType = $this->invokePrivateMethod(
            $controller,
            'getNormalizedVariantModificationType',
            [$row, $typeFieldName, $valueFieldName]
        );

        self::assertSame($expectedModificationType, $normalizedModificationType);
    }

    /**
     * @dataProvider validationAcceptedProvider
     *
     * @param array<string, mixed> $row
     */
    public function testValidationAcceptsMissingTypeWhenCorrespondingValueIsEmptyOrMissing(
        string $typeFieldName,
        string $valueFieldName,
        array $row
    ): void {
        $controller = $this->createControllerWithoutConstructor();

        $hasValidationError = $this->invokePrivateMethod(
            $controller,
            'hasVariantModificationTypeValidationError',
            [$row, $typeFieldName, $valueFieldName]
        );

        self::assertFalse($hasValidationError);
    }

    /**
     * @dataProvider missingTypeValidationProvider
     *
     * @param array<string, mixed> $row
     */
    public function testValidationRequiresTypeWhenCorrespondingValueIsSet(
        string $typeFieldName,
        string $valueFieldName,
        array $row
    ): void {
        $controller = $this->createControllerWithoutConstructor();

        $hasValidationError = $this->invokePrivateMethod(
            $controller,
            'hasVariantModificationTypeValidationError',
            [$row, $typeFieldName, $valueFieldName]
        );

        self::assertTrue($hasValidationError);
    }

    /**
     * @dataProvider invalidTypeValidationProvider
     *
     * @param array<string, mixed> $row
     */
    public function testValidationRejectsInvalidModificationTypes(
        string $typeFieldName,
        string $valueFieldName,
        array $row
    ): void {
        $controller = $this->createControllerWithoutConstructor();

        $hasValidationError = $this->invokePrivateMethod(
            $controller,
            'hasVariantModificationTypeValidationError',
            [$row, $typeFieldName, $valueFieldName]
        );

        self::assertTrue($hasValidationError);
    }

    /**
     * @return array<string, array{0:string, 1:string, 2:array<string, mixed>}>
     */
    public static function normalizationFallbackProvider(): array
    {
        return [
            'main price with empty value' => ['priceType', 'price', ['price' => '', 'priceType' => 'independent']],
            'main weight with missing value column' => ['weightType', 'weight', ['weightType' => 'fixed']],
            'group 30-day lowest price with empty value' => ['priceType30DayLowest_1', 'price30DayLowest_1', ['price30DayLowest_1' => '']],
        ];
    }

    /**
     * @return array<string, array{0:string, 1:string, 2:array<string, mixed>, 3:string}>
     */
    public static function normalizationTranslationProvider(): array
    {
        return [
            'main price independent' => ['priceType', 'price', ['price' => '12.34', 'priceType' => 'independent'], 'standalone'],
            'main 30-day lowest price fixed' => ['priceType30DayLowest', 'price30DayLowest', ['price30DayLowest' => '1.23', 'priceType30DayLowest' => 'fixed'], 'adjustmentFix'],
            'group old price percentaged' => ['oldPriceType_1', 'oldPrice_1', ['oldPrice_1' => '9.99', 'oldPriceType_1' => 'percentaged'], 'adjustmentPercentaged'],
        ];
    }

    /**
     * @return array<string, array{0:string, 1:string, 2:array<string, mixed>}>
     */
    public static function validationAcceptedProvider(): array
    {
        return [
            'main price without value and without type' => ['priceType', 'price', []],
            'group old price with empty value and no type' => ['oldPriceType_1', 'oldPrice_1', ['oldPrice_1' => '']],
            'main weight with valid type and zero value' => ['weightType', 'weight', ['weight' => '0', 'weightType' => 'fixed']],
            'group 30-day lowest price with valid type and value' => ['priceType30DayLowest_1', 'price30DayLowest_1', ['price30DayLowest_1' => '0', 'priceType30DayLowest_1' => 'percentaged']],
        ];
    }

    /**
     * @return array<string, array{0:string, 1:string, 2:array<string, mixed>}>
     */
    public static function missingTypeValidationProvider(): array
    {
        return [
            'main price zero value without type' => ['priceType', 'price', ['price' => '0']],
            'group price zero value without type' => ['priceType_1', 'price_1', ['price_1' => '0']],
            'main 30-day lowest price without type' => ['priceType30DayLowest', 'price30DayLowest', ['price30DayLowest' => '3.50']],
            'main weight zero value without type' => ['weightType', 'weight', ['weight' => '0']],
        ];
    }

    /**
     * @return array<string, array{0:string, 1:string, 2:array<string, mixed>}>
     */
    public static function invalidTypeValidationProvider(): array
    {
        return [
            'main price with invalid type' => ['priceType', 'price', ['price' => '10.00', 'priceType' => 'unsupported']],
            'group old price with invalid type' => ['oldPriceType_1', 'oldPrice_1', ['oldPrice_1' => '5.00', 'oldPriceType_1' => 'unsupported']],
            'main weight invalid type without value' => ['weightType', 'weight', ['weightType' => 'unsupported']],
            'group 30-day lowest price invalid type' => ['priceType30DayLowest_1', 'price30DayLowest_1', ['price30DayLowest_1' => '1.00', 'priceType30DayLowest_1' => 'unsupported']],
        ];
    }

    private function createControllerWithoutConstructor(): ls_shop_importController
    {
        $reflectionClass = new ReflectionClass(ls_shop_importController::class);

        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivateMethod(object $instance, string $methodName, array $arguments): mixed
    {
        $reflectionMethod = new ReflectionMethod($instance, $methodName);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($instance, $arguments);
    }
}
