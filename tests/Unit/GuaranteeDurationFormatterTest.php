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
    public function testFormatYearsForDisplay(
        string $storedValue,
        string $decimalSeparator,
        string $expectedDisplayValue
    ): void
    {
        self::assertSame(
            $expectedDisplayValue,
            GuaranteeDurationFormatter::formatYearsForDisplay($storedValue, $decimalSeparator)
        );
    }

    /**
     * @return iterable<string, array{
     *   storedValue: string,
     *   decimalSeparator: string,
     *   expectedDisplayValue: string
     * }>
     */
    public static function provideDurationFormats(): iterable
    {
        yield 'half years use comma' => [
            'storedValue' => '2.5',
            'decimalSeparator' => ',',
            'expectedDisplayValue' => '2,5',
        ];

        yield 'half years use dot' => [
            'storedValue' => '2.5',
            'decimalSeparator' => '.',
            'expectedDisplayValue' => '2.5',
        ];

        yield 'whole years drop trailing decimal' => [
            'storedValue' => '3.0',
            'decimalSeparator' => ',',
            'expectedDisplayValue' => '3',
        ];

        yield 'two digit whole years drop trailing decimal' => [
            'storedValue' => '30.0',
            'decimalSeparator' => '.',
            'expectedDisplayValue' => '30',
        ];

        yield 'already whole years remain unchanged' => [
            'storedValue' => '10',
            'decimalSeparator' => ',',
            'expectedDisplayValue' => '10',
        ];

        yield 'empty values remain empty' => [
            'storedValue' => '',
            'decimalSeparator' => '.',
            'expectedDisplayValue' => '',
        ];

        yield 'empty separator falls back to comma' => [
            'storedValue' => '2.5',
            'decimalSeparator' => '',
            'expectedDisplayValue' => '2,5',
        ];
    }
}
