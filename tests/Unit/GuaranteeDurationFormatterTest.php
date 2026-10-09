<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\GuaranteeDurationFormatter;
use PHPUnit\Framework\TestCase;

final class GuaranteeDurationFormatterTest extends TestCase
{
    /**
     * @dataProvider provideDurationFormats
     */
    public function testFormatYearsForDisplay(string $storedValue, string $expectedDisplayValue): void
    {
        self::assertSame($expectedDisplayValue, GuaranteeDurationFormatter::formatYearsForDisplay($storedValue));
    }

    /**
     * @return iterable<string, array{storedValue: string, expectedDisplayValue: string}>
     */
    public static function provideDurationFormats(): iterable
    {
        yield 'half years use comma' => [
            'storedValue' => '2.5',
            'expectedDisplayValue' => '2,5',
        ];

        yield 'whole years drop trailing decimal' => [
            'storedValue' => '3.0',
            'expectedDisplayValue' => '3',
        ];

        yield 'two digit whole years drop trailing decimal' => [
            'storedValue' => '30.0',
            'expectedDisplayValue' => '30',
        ];

        yield 'already whole years remain unchanged' => [
            'storedValue' => '10',
            'expectedDisplayValue' => '10',
        ];
    }
}
