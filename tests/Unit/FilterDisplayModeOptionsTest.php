<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_filter_fields.php';

use Merconis\Core\ls_shop_filter_fields;
use PHPUnit\Framework\TestCase;

final class FilterDisplayModeOptionsTest extends TestCase
{
    public function testCheckboxFieldsOfferAllDisplayModes(): void
    {
        self::assertSame(
            ['showMoreLess', 'sliderRange', 'sliderDirect'],
            ls_shop_filter_fields::determineFilterDisplayModeOptions('checkbox')
        );
    }

    public function testRadioFieldsDoNotOfferDirectFilterMode(): void
    {
        self::assertSame(
            ['showMoreLess', 'sliderRange'],
            ls_shop_filter_fields::determineFilterDisplayModeOptions('radio')
        );
    }

    public function testInvalidRadioSelectionFallsBackToShowMoreLess(): void
    {
        self::assertSame(
            'showMoreLess',
            ls_shop_filter_fields::normalizeFilterDisplayModeForFieldType('sliderDirect', 'radio')
        );
    }
}
