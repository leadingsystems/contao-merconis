<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalTestmodeProcessor;
use Merconis\Core\ModuleWithdrawalConfirmation;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class WithdrawalConfirmationFallbackDecisionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/frontendModules/ModuleWithdrawalConfirmation.php';
    }

    public function testMissingRecordForNumericPrimaryKeyTriggersRedirectDecision(): void
    {
        $module = $this->createModuleWithoutConstructor();

        $shouldRedirect = $this->invokePrivateMethod(
            $module,
            'shouldRedirectWhenWithdrawalRecordIsMissing',
            [[
                'status' => 'success',
                'reference' => '123',
                'primaryKey' => 123,
            ], null]
        );

        self::assertTrue($shouldRedirect);
    }

    public function testPlaceholderReferenceKeepsTestmodeFallbackAvailable(): void
    {
        $module = $this->createModuleWithoutConstructor();

        $shouldRedirect = $this->invokePrivateMethod(
            $module,
            'shouldRedirectWhenWithdrawalRecordIsMissing',
            [[
                'status' => 'success',
                'reference' => WithdrawalTestmodeProcessor::PLACEHOLDER_WITHDRAWAL_ID,
            ], null]
        );

        self::assertFalse($shouldRedirect);
    }

    private function createModuleWithoutConstructor(): ModuleWithdrawalConfirmation
    {
        $reflectionClass = new ReflectionClass(ModuleWithdrawalConfirmation::class);

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
