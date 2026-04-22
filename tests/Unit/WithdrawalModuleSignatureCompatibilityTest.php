<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\Module;
use Merconis\Core\ModuleWithdrawal;
use Merconis\Core\ModuleWithdrawalConfirmation;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class WithdrawalModuleSignatureCompatibilityTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/frontendModules/ModuleWithdrawal.php';
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/frontendModules/ModuleWithdrawalConfirmation.php';
    }

    public function testWithdrawalModuleCompileSignatureMatchesContaoModuleContract(): void
    {
        $parentCompileMethod = new ReflectionMethod(Module::class, 'compile');
        $withdrawalCompileMethod = new ReflectionMethod(ModuleWithdrawal::class, 'compile');

        self::assertTrue($withdrawalCompileMethod->isProtected());
        self::assertNull($withdrawalCompileMethod->getReturnType());
        self::assertSame(
            $parentCompileMethod->getNumberOfParameters(),
            $withdrawalCompileMethod->getNumberOfParameters()
        );
    }

    public function testWithdrawalConfirmationCompileSignatureMatchesContaoModuleContract(): void
    {
        $parentCompileMethod = new ReflectionMethod(Module::class, 'compile');
        $confirmationCompileMethod = new ReflectionMethod(ModuleWithdrawalConfirmation::class, 'compile');

        self::assertTrue($confirmationCompileMethod->isProtected());
        self::assertNull($confirmationCompileMethod->getReturnType());
        self::assertSame(
            $parentCompileMethod->getNumberOfParameters(),
            $confirmationCompileMethod->getNumberOfParameters()
        );
    }
}
