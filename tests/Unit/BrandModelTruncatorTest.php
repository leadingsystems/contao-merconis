<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\BrandModelTruncator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BrandModelTruncatorTest extends TestCase
{
    private const WIDTH_TABLE_PATH = __DIR__ . '/../../src/Resources/legal-guarantee/garan/v1.0/' . BrandModelTruncator::WIDTH_TABLE_FILENAME;

    // Verfügbare Breite der Version `v1.0`: Zeile 256.65 minus Mindestabstand 13.5.
    private const AVAILABLE_WIDTH = 243.15;

    /**
     * Erwartete Ergebnisse aus Implementierungsentscheidung 0030, Abschnitt "Kürzungsregel".
     *
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function provideFixtureExpectations(): iterable
    {
        yield 'F1' => [
            'Mustermann Qualitätshersteller GmbH & Co. KG',
            'FooBar',
            'Mustermann Qualitätshersteller GmbH & Co. KG',
            'FooBar',
        ];
        yield 'F2' => ['ACME', 'X100', 'ACME', 'X100'];
        yield 'F6' => [
            'Mustermann Qualitätshersteller GmbH & Co. KG',
            'Professional Series X-2000 Ultra Silent Edition',
            'Mustermann Qu...',
            'Professional Series X-2000 Ultra Sile...',
        ];
        yield 'F7' => [
            'BSH Hausgeräte GmbH Deutschland Vertrieb',
            'SMV4HCX48E Serie 4 Geschirrspüler 60cm vollintegriert',
            'BSH Hausgerät...',
            'SMV4HCX48E Serie 4 Geschirrspüler...',
        ];
        yield 'F8' => [str_repeat('W', 30), 'FooBar', str_repeat('W', 23) . '...', 'FooBar'];
        yield 'F9' => [
            'Miele',
            'SMV4HCX48E Serie 4 Geschirrspüler 60cm vollintegriert Edelstahl Front',
            'Miele',
            'SMV4HCX48E Serie 4 Geschirrspüler 60cm vollin...',
        ];
    }

    #[DataProvider('provideFixtureExpectations')]
    public function testFixtureResultsMatchImplementationDecision(
        string $brand,
        string $modelIdentifier,
        string $expectedBrand,
        string $expectedModelIdentifier
    ): void {
        $truncator = BrandModelTruncator::fromWidthTableFile(self::WIDTH_TABLE_PATH);

        $result = $truncator->truncate($brand, $modelIdentifier);

        self::assertSame(['brand' => $expectedBrand, 'model' => $expectedModelIdentifier], $result);
        self::assertLessThanOrEqual(
            self::AVAILABLE_WIDTH,
            $truncator->measure($result['brand']) + $truncator->measure($result['model'])
        );
    }

    public function testValuesFillingTheAvailableWidthExactlyStayUntruncated(): void
    {
        $truncator = new BrandModelTruncator([
            mb_ord('b') => 75 / 9,
            mb_ord('m') => (self::AVAILABLE_WIDTH - 75) / 9,
            mb_ord('.') => 0.5,
        ]);

        self::assertEqualsWithDelta(self::AVAILABLE_WIDTH, $truncator->measure('b') + $truncator->measure('m'), 1e-9);
        self::assertSame(['brand' => 'b', 'model' => 'm'], $truncator->truncate('b', 'm'));
        // Eine Marke mehr überschreitet die Zeile; die Werte bleiben nicht unverändert.
        self::assertNotSame(['brand' => 'bb', 'model' => 'm'], $truncator->truncate('bb', 'm'));
    }

    public function testBrandNarrowerThanMinimumWidthLeavesTheRestToTheModel(): void
    {
        $truncator = $this->createUniformTruncator();

        // Marke 18 Einheiten: Das Modell erhält 243.15 - 18 = 225.15 statt 243.15 - 75 = 168.15.
        $result = $truncator->truncate('aa', str_repeat('a', 40));

        self::assertSame('aa', $result['brand']);
        self::assertSame(str_repeat('a', 23) . '...', $result['model']);
    }

    public function testBrandWiderThanMinimumWidthKeepsTheMinimumWidth(): void
    {
        $truncator = $this->createUniformTruncator();

        // Modell: 243.15 - 75 = 168.15 ergibt 17 Zeichen plus "..." (166.5); Marke: 243.15 - 166.5 = 76.65.
        $result = $truncator->truncate(str_repeat('a', 20), str_repeat('a', 40));

        self::assertSame(str_repeat('a', 17) . '...', $result['model']);
        self::assertSame(str_repeat('a', 7) . '...', $result['brand']);
        self::assertGreaterThanOrEqual(75.0, $truncator->measure($result['brand']));
    }

    public function testUnknownCharacterCountsAsOneFontSize(): void
    {
        $truncator = $this->createUniformTruncator();
        $tableTruncator = BrandModelTruncator::fromWidthTableFile(self::WIDTH_TABLE_PATH);
        $widthTable = json_decode((string) file_get_contents(self::WIDTH_TABLE_PATH), true);

        self::assertSame(9.0, $truncator->measure("\u{4E2D}"));
        self::assertSame(18.0, $truncator->measure("a\u{4E2D}"));
        self::assertArrayNotHasKey((string) mb_ord("\u{4E2D}"), $widthTable);
        self::assertSame(9.0, $tableTruncator->measure("\u{4E2D}"));
    }

    public function testWhitespaceBeforeTheEllipsisIsRemoved(): void
    {
        $truncator = $this->createUniformTruncator();

        // Der längste passende Anfang endet mit dem Leerzeichen nach den 16 `a`.
        $result = $truncator->truncate(str_repeat('a', 9), str_repeat('a', 16) . ' ' . str_repeat('b', 10));

        self::assertSame(str_repeat('a', 16) . '...', $result['model']);
        self::assertSame(str_repeat('a', 9), $result['brand']);
    }

    public function testMeasureCountsCodePointsWithoutEntities(): void
    {
        $truncator = BrandModelTruncator::fromWidthTableFile(self::WIDTH_TABLE_PATH);

        self::assertEqualsWithDelta(
            $truncator->measure('A') + $truncator->measure('&') + $truncator->measure('B'),
            $truncator->measure('A&B'),
            1e-9
        );
        self::assertGreaterThan($truncator->measure('A&B'), $truncator->measure('A&amp;B'));
        self::assertEqualsWithDelta(
            $truncator->measure('a') + $truncator->measure("\u{0308}"),
            $truncator->measure("a\u{0308}"),
            1e-9
        );
    }

    public function testMissingWidthTableIsAnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GARAN width table could not be read');

        BrandModelTruncator::fromWidthTableFile(sys_get_temp_dir() . '/merconis-missing-width-table-' . uniqid('', true) . '.json');
    }

    /**
     * Jedes Zeichen 1 em (9 Einheiten), der Punkt 0.5 em: "..." ist 13.5 Einheiten breit.
     */
    private function createUniformTruncator(): BrandModelTruncator
    {
        return new BrandModelTruncator([
            mb_ord(' ') => 1.0,
            mb_ord('.') => 0.5,
            mb_ord('a') => 1.0,
            mb_ord('b') => 1.0,
        ]);
    }
}
