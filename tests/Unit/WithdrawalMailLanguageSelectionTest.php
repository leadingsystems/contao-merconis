<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ModuleWithdrawal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class WithdrawalMailLanguageSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['merconis_globals']['arr_cache']['arr_languages']['str_fallbackLanguage'] = 'en';
        $GLOBALS['TL_LANGUAGE'] = 'fr';
    }

    public function testMailLanguageSelectionUsesOrderCustomerLanguageAndShopFallback(): void
    {
        $module = $this->createModuleWithdrawalWithoutConstructor();

        $result = $this->invokePrivateMethod(
            $module,
            'determineWithdrawalMailLanguages',
            [[
                'customerLanguage' => 'de',
            ]]
        );

        self::assertSame(
            [
                'customerLanguage' => 'de',
                'fallbackLanguage' => 'en',
            ],
            $result
        );
    }

    public function testMailLanguageSelectionFallsBackToCurrentPageLanguageForScreenC(): void
    {
        $module = $this->createModuleWithdrawalWithoutConstructor();

        $result = $this->invokePrivateMethod(
            $module,
            'determineWithdrawalMailLanguages',
            [null]
        );

        self::assertSame(
            [
                'customerLanguage' => 'fr',
                'fallbackLanguage' => 'en',
            ],
            $result
        );
    }

    private function createModuleWithdrawalWithoutConstructor(): ModuleWithdrawal
    {
        $reflectionClass = new ReflectionClass(ModuleWithdrawal::class);

        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivateMethod(object $instance, string $methodName, array $arguments): mixed
    {
        $reflectionMethod = new \ReflectionMethod($instance, $methodName);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($instance, $arguments);
    }
}
