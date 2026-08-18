<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_importController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class ImportControllerPrice30DayLowestValidationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/helpers/ls_shop_productManagementApiHelper.php';
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/classes/ls_shop_importController.php';
    }

    #[DataProvider('invalidPrice30DayLowestProvider')]
    public function testValidationBlocksInvalidPrice30DayLowestValues(array $row): void
    {
        $controller = $this->createControllerWithoutConstructor();

        self::assertTrue($this->invokeMethod($controller, 'checkDataFor', ['valueInvalid_price30DayLowest', $row]));
    }

    #[DataProvider('validPrice30DayLowestProvider')]
    public function testValidationAcceptsValidPrice30DayLowestValues(array $row): void
    {
        $controller = $this->createControllerWithoutConstructor();

        self::assertFalse($this->invokeMethod($controller, 'checkDataFor', ['valueInvalid_price30DayLowest', $row]));
    }

    public static function invalidPrice30DayLowestProvider(): array
    {
        return [
            'main price rule' => [[
                'type' => 'product',
                'delete' => '',
                'price30DayLowest' => 'abc',
            ]],
            'group price rule' => [[
                'type' => 'variant',
                'delete' => '',
                'price30DayLowest_1' => '12,34',
            ]],
        ];
    }

    public static function validPrice30DayLowestProvider(): array
    {
        return [
            'main price rule' => [[
                'type' => 'product',
                'delete' => '',
                'price30DayLowest' => '12.34',
            ]],
            'group price rule' => [[
                'type' => 'variant',
                'delete' => '',
                'price30DayLowest_1' => '-9.99',
            ]],
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
