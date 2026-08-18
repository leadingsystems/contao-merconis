<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\System;
use Merconis\Core\ls_shop_importController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Container;

final class ImportControllerVariantModificationTypeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/helpers/ls_shop_productManagementApiHelper.php';
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/classes/ls_shop_importController.php';
    }

    protected function setUp(): void
    {
        $_SESSION['lsShop']['importFileInfo']['arrImportInfos'] = [
            'productsToIgnore' => [],
            'productsToDelete' => [],
            'variantsToIgnore' => [],
            'variantsToDelete' => [],
            'productsProductcodeToID' => [],
            'variantsProductcodeToID' => [],
        ];
        $GLOBALS['MERCONIS_HOOKS'] = [];
        VariantImportHookRecorder::$capturedRow = null;

        $container = new Container();
        $container->setParameter('kernel.debug', false);
        System::setContainer($container);
    }

    public function testFallbackDefaultsAreAppliedForEmptyRelatedValues(): void
    {
        $controller = $this->createControllerWithoutConstructor();
        $row = [
            'type' => 'variant',
            'ignore' => '1',
            'delete' => '',
            'productcode' => 'variant-1',
            'parentProductcode' => 'product-1',
            'price' => '',
            'oldPrice' => '',
            'price30DayLowest' => '',
            'weight' => '',
            'price_1' => '',
            'oldPrice_1' => '',
            'price30DayLowest_1' => '',
        ];

        $GLOBALS['MERCONIS_HOOKS']['import_beforeProcessingVariantData'] = [
            [VariantImportHookRecorder::class, 'capture'],
        ];

        $this->invokeMethod($controller, 'processVariantData', [$row]);

        self::assertIsArray(VariantImportHookRecorder::$capturedRow);
        self::assertSame('adjustmentPercentaged', VariantImportHookRecorder::$capturedRow['priceType']);
        self::assertSame('adjustmentPercentaged', VariantImportHookRecorder::$capturedRow['oldPriceType']);
        self::assertSame('adjustmentPercentaged', VariantImportHookRecorder::$capturedRow['priceType30DayLowest']);
        self::assertSame('adjustmentPercentaged', VariantImportHookRecorder::$capturedRow['priceType30DayLowest_1']);
        self::assertSame('adjustmentPercentaged', VariantImportHookRecorder::$capturedRow['weightType']);
    }

    #[DataProvider('zeroValueValidationProvider')]
    public function testValidationTreatsZeroAsSetAcrossModificationTypes(string $checkFor, array $row): void
    {
        $controller = $this->createControllerWithoutConstructor();

        self::assertTrue($this->invokeMethod($controller, 'checkDataFor', [$checkFor, $row]));
    }

    #[DataProvider('invalidTypeValidationProvider')]
    public function testValidationBlocksInvalidModificationTypesWhenValueIsSet(string $checkFor, array $row): void
    {
        $controller = $this->createControllerWithoutConstructor();

        self::assertTrue($this->invokeMethod($controller, 'checkDataFor', [$checkFor, $row]));
    }

    #[DataProvider('validTypeValidationProvider')]
    public function testValidationAcceptsValidModificationTypesWhenValueIsSet(string $checkFor, array $row): void
    {
        $controller = $this->createControllerWithoutConstructor();

        self::assertFalse($this->invokeMethod($controller, 'checkDataFor', [$checkFor, $row]));
    }

    public static function zeroValueValidationProvider(): array
    {
        return [
            'price main field' => [
                'notExistingPriceType',
                ['type' => 'variant', 'delete' => '', 'price' => '0', 'priceType' => ''],
            ],
            'price group field' => [
                'notExistingPriceType',
                ['type' => 'variant', 'delete' => '', 'price_1' => '0', 'priceType_1' => ''],
            ],
            'old price field' => [
                'notExistingPriceTypeOld',
                ['type' => 'variant', 'delete' => '', 'oldPrice' => '0', 'oldPriceType' => ''],
            ],
            '30 day lowest price group field' => [
                'notExistingPriceType30DayLowest',
                ['type' => 'variant', 'delete' => '', 'price30DayLowest_1' => '0', 'priceType30DayLowest_1' => ''],
            ],
            'weight field' => [
                'notExistingWeightType',
                ['type' => 'variant', 'delete' => '', 'weight' => '0', 'weightType' => ''],
            ],
        ];
    }

    public static function invalidTypeValidationProvider(): array
    {
        return [
            'price main field' => [
                'notExistingPriceType',
                ['type' => 'variant', 'delete' => '', 'price' => '10.00', 'priceType' => 'unsupported'],
            ],
            'old price field' => [
                'notExistingPriceTypeOld',
                ['type' => 'variant', 'delete' => '', 'oldPrice' => '5.00', 'oldPriceType' => 'unsupported'],
            ],
            '30 day lowest price field' => [
                'notExistingPriceType30DayLowest',
                ['type' => 'variant', 'delete' => '', 'price30DayLowest' => '3.50', 'priceType30DayLowest' => 'unsupported'],
            ],
            'weight field' => [
                'notExistingWeightType',
                ['type' => 'variant', 'delete' => '', 'weight' => '1.25', 'weightType' => 'unsupported'],
            ],
        ];
    }

    public static function validTypeValidationProvider(): array
    {
        return [
            'price main field' => [
                'notExistingPriceType',
                ['type' => 'variant', 'delete' => '', 'price' => '10.00', 'priceType' => 'percentaged'],
            ],
            'old price field' => [
                'notExistingPriceTypeOld',
                ['type' => 'variant', 'delete' => '', 'oldPrice' => '5.00', 'oldPriceType' => 'fixed'],
            ],
            '30 day lowest price group field' => [
                'notExistingPriceType30DayLowest',
                ['type' => 'variant', 'delete' => '', 'price30DayLowest_1' => '0', 'priceType30DayLowest_1' => 'independent'],
            ],
            'weight field' => [
                'notExistingWeightType',
                ['type' => 'variant', 'delete' => '', 'weight' => '0', 'weightType' => 'percentaged'],
            ],
        ];
    }

    private function createControllerWithoutConstructor(): ls_shop_importController
    {
        $reflectionClass = new ReflectionClass(ls_shop_importController::class);

        return $reflectionClass->newInstanceWithoutConstructor();
    }

    private function invokeMethod(object $instance, string $methodName, array $arguments): mixed
    {
        $reflectionMethod = new ReflectionMethod($instance, $methodName);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($instance, $arguments);
    }
}

final class VariantImportHookRecorder
{
    public static ?array $capturedRow = null;

    public function capture(array $row): array
    {
        self::$capturedRow = $row;

        return $row;
    }
}
