<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenAProcessor;
use Merconis\Core\ModuleWithdrawal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class WithdrawalScreenAProcessorUnitTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Resources/contao/frontendModules/ModuleWithdrawal.php';
    }

    public function testHoneypotEmptyValueIsAccepted(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        self::assertFalse($processor->isHoneypotTriggered(''));
        self::assertFalse($processor->isHoneypotTriggered('   '));
    }

    public function testHoneypotFilledValueIsDetected(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        self::assertTrue($processor->isHoneypotTriggered('bot-value'));
    }

    public function testNeutralHoneypotFieldNameAndInlineHidingAreConfigured(): void
    {
        $moduleReflection = new ReflectionClass(ModuleWithdrawal::class);
        $honeypotFieldName = $moduleReflection
            ->getReflectionConstant('HONEYPOT_FIELD_NAME')
            ?->getValue();

        self::assertSame('additional_info', $honeypotFieldName);

        $templateDirectory = dirname(__DIR__, 2) . '/src/Resources/contao/templates';
        $screenATemplate = (string) file_get_contents($templateDirectory . '/mod_ls_shop_withdrawal_screenA.html5');
        $screenCTemplate = (string) file_get_contents($templateDirectory . '/mod_ls_shop_withdrawal_screenC.html5');

        self::assertStringContainsString("'additional_info';", $screenATemplate);
        self::assertStringContainsString("'additional_info';", $screenCTemplate);
        self::assertStringContainsString('style="display:none"', $screenATemplate);
        self::assertStringContainsString('style="display:none"', $screenCTemplate);
        self::assertStringNotContainsString('mod_ls_shop_withdrawal__honeypot', $screenATemplate);
        self::assertStringNotContainsString('mod_ls_shop_withdrawal__honeypot', $screenCTemplate);
    }

    public function testRateLimitThresholdCheckUsesGreaterOrEqual(): void
    {
        $processor = new WithdrawalScreenAProcessor();

        self::assertFalse($processor->isRateLimitExceeded(19, 20));
        self::assertTrue($processor->isRateLimitExceeded(20, 20));
        self::assertTrue($processor->isRateLimitExceeded(21, 20));
    }
}
