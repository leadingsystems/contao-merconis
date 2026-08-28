<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WithdrawalScreenBTemplateTest extends TestCase
{
    public function testTemplateWiresNumberStepperAndDisplayBoundaries(): void
    {
        $templateContents = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Resources/contao/templates/mod_ls_shop_withdrawal_screenB.html5'
        );

        self::assertStringContainsString("'useNumberStepper'", $templateContents);
        self::assertStringContainsString('class="<?php echo \\Contao\\StringUtil::specialchars($inputClass); ?>"', $templateContents);
        self::assertStringContainsString('min="<?php echo \\Contao\\StringUtil::specialchars($stepValue); ?>"', $templateContents);
        self::assertStringContainsString('max="<?php echo \\Contao\\StringUtil::specialchars($orderedQuantity); ?>"', $templateContents);
        self::assertStringContainsString('step="<?php echo \\Contao\\StringUtil::specialchars($stepValue); ?>"', $templateContents);
    }
}
