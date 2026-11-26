<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../Support/ContaoTestApplication.php';

use Contao\System;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\Tests\Support\ContaoTestApplication;
use Merconis\Core\ls_shop_importController;
use Merconis\Core\ls_shop_languageHelper;
use Merconis\Core\ls_shop_productManagementApiHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Import der LGN- und GARAN-Spalten nach Spec 27, Abschnitt 10, und Spec 13, Abschnitt 1.8
 * (ADR 0032): Dateivalidierung und Schreiben über `validateFile()` und `importFile()` im
 * Contao-Anwendungskontext des Host-Projekts, gegen die Entwicklungsdatenbank.
 *
 * Jeder Test läuft in einer Transaktion, die am Ende zurückgerollt wird; danach prüft
 * `tearDown()`, dass kein Datensatz mit dem Testpräfix übrig ist (ADR 0016, ADR 0032).
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LegalGuaranteeImportTest extends TestCase
{
    private const PRODUCTCODE_PREFIX = 'LGN-IMPORT-TEST-';

    private const LEGAL_GUARANTEE_COLUMNS = [
        'lgnMode',
        'enableGaran',
        'garanMode',
        'guaranteeDurationMonths',
        'guaranteeBrand',
        'guaranteeModelIdentifier',
    ];

    /**
     * Fehlerarten in der Reihenfolge von `$arrDataErrors` in `analyzeImportFile()`.
     */
    private const LEGAL_GUARANTEE_ERROR_KEYS = [
        'productValueInvalid_lgnMode',
        'variantValueInvalid_garanMode',
        'valueInvalid_guaranteeDurationMonths',
        'valueInvalid_guaranteeBrand',
        'valueInvalid_guaranteeModelIdentifier',
        'productGuaranteeDataIncomplete',
        'productGuaranteeWithLgnModeNever',
        'variantGuaranteeDataIncomplete',
        'variantGuaranteeOverrideWithParentLgnModeNever',
    ];

    private const COMPLETE_GUARANTEE = [
        'guaranteeDurationMonths' => '36',
        'guaranteeBrand' => 'ACME',
        'guaranteeModelIdentifier' => 'M-1',
    ];

    private ?Connection $connection = null;
    private ?string $temporaryDirectory = null;
    private string $taxClassAlias;
    private string $categoryAlias;
    private string $language;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir() . '/merconis-lgn-import-test-' . bin2hex(random_bytes(4));
        mkdir($this->temporaryDirectory);

        ContaoTestApplication::boot('de');

        /*
         * `ls_shop_languageHelper::determineExistingLanguages()` liest `$objPage->domain`; die
         * Seite aus `ContaoTestApplication::boot()` hat keine Domain.
         */
        unset($GLOBALS['objPage']);

        $GLOBALS['TL_CONFIG']['ls_shop_importCsvDelimiter'] = ';';
        $GLOBALS['TL_CONFIG']['ls_shop_importCsvEnclosure'] = '"';
        $GLOBALS['TL_CONFIG']['ls_shop_importCsvEscape'] = '\\';
        $_SESSION = [];

        $this->connection = System::getContainer()->get('database_connection');
        self::assertSame(0, $this->countTestRecords(), 'Die Datenbank enthält bereits Datensätze mit dem Testpräfix.');
        $this->connection->beginTransaction();

        $this->taxClassAlias = (string) ls_shop_productManagementApiHelper::getTaxClassAliases()[0];
        $this->categoryAlias = (string) current(array_filter(ls_shop_productManagementApiHelper::getPageAliases()));
        $this->language = (string) ls_shop_languageHelper::getFallbackLanguage();
        self::assertNotSame('', $this->taxClassAlias);
        self::assertNotSame('', $this->categoryAlias);
        self::assertNotSame('', $this->language);
    }

    protected function tearDown(): void
    {
        $remainingTestRecords = 0;

        if ($this->connection !== null) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $remainingTestRecords = $this->countTestRecords();

            if ($remainingTestRecords > 0) {
                $this->connection->executeStatement('DELETE FROM tl_ls_shop_variant WHERE lsShopVariantCode LIKE ?', [self::PRODUCTCODE_PREFIX . '%']);
                $this->connection->executeStatement('DELETE FROM tl_ls_shop_product WHERE lsShopProductCode LIKE ?', [self::PRODUCTCODE_PREFIX . '%']);
            }
        }

        if ($this->temporaryDirectory !== null) {
            foreach (glob($this->temporaryDirectory . '/*') ?: [] as $temporaryFile) {
                unlink($temporaryFile);
            }

            rmdir($this->temporaryDirectory);
        }

        parent::tearDown();

        self::assertSame(0, $remainingTestRecords, 'Nach dem Zurückrollen waren noch Testdatensätze vorhanden.');
    }

    /**
     * Spec 27, Abschnitt 10.2: `lgnMode` leer oder genau `inherit`, `always`, `never`.
     */
    public function testLgnModeAcceptsOnlyEmptyOrExactValuesInProductRows(): void
    {
        $rows = [];

        foreach (['', 'inherit', 'always', 'never', 'Inherit', ' always', 'never ', '0', 'sometimes'] as $index => $lgnMode) {
            $rows[] = $this->productRow('P' . $index, ['lgnMode' => $lgnMode]);
        }

        self::assertSame(
            $this->expectedMessages(['productValueInvalid_lgnMode' => [5, 6, 7, 8, 9]]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.3: `garanMode` leer oder genau `inherit`, `disabled`, `override`.
     */
    public function testGaranModeAcceptsOnlyEmptyOrExactValuesInVariantRows(): void
    {
        $rows = [$this->productRow('P')];

        foreach (['', 'inherit', 'disabled', 'override', 'Override', ' disabled', '0', 'none'] as $index => $garanMode) {
            $rows[] = $this->variantRow('V' . $index, 'P', ['garanMode' => $garanMode] + self::COMPLETE_GUARANTEE);
        }

        self::assertSame(
            $this->expectedMessages(['variantValueInvalid_garanMode' => [6, 7, 8, 9]]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitte 3.2, 10.2 und 10.3: nur Ziffern ohne führende Null im Raster 30 bis
     * 360, durch 6 teilbar; geprüft auch bei ausgeschalteter Herstellergarantie und in
     * Variantenzeilen ohne `override`.
     */
    public function testDurationAcceptsOnlyGridValuesInProductAndVariantRows(): void
    {
        $productDurations = ['', '30', '36', '354', '360', '24', '31', '366', '0', '36.0', '036', ' 36', '36 ', "36\n", '-36', 'drei'];
        $rows = [];

        foreach ($productDurations as $index => $duration) {
            $rows[] = $this->productRow('P' . $index, ['guaranteeDurationMonths' => $duration]);
        }

        foreach (['30', '036', '36.0'] as $index => $duration) {
            $rows[] = $this->variantRow('V' . $index, 'P0', ['guaranteeDurationMonths' => $duration]);
        }

        self::assertSame(
            $this->expectedMessages(['valueInvalid_guaranteeDurationMonths' => [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 18, 19]]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.2; Implementierungsentscheidung 0034: höchstens 255 Zeichen,
     * gezählt mit `mb_strlen()` (255 Mehrbyte-Zeichen sind 510 Bytes).
     */
    public function testBrandAndModelAreLimitedTo255CharactersInProductAndVariantRows(): void
    {
        $longestValue = str_repeat('ä', 255);
        $tooLongValue = str_repeat('ä', 256);

        $rows = [
            $this->productRow('P1', ['guaranteeBrand' => $longestValue]),
            $this->productRow('P2', ['guaranteeBrand' => $tooLongValue]),
            $this->productRow('P3', ['guaranteeModelIdentifier' => $longestValue]),
            $this->productRow('P4', ['guaranteeModelIdentifier' => $tooLongValue]),
            $this->variantRow('V1', 'P1', ['guaranteeBrand' => $longestValue]),
            $this->variantRow('V2', 'P1', ['guaranteeBrand' => $tooLongValue]),
            $this->variantRow('V3', 'P1', ['guaranteeModelIdentifier' => $longestValue]),
            $this->variantRow('V4', 'P1', ['guaranteeModelIdentifier' => $tooLongValue]),
        ];

        self::assertSame(
            $this->expectedMessages([
                'valueInvalid_guaranteeBrand' => [2, 6],
                'valueInvalid_guaranteeModelIdentifier' => [4, 8],
            ]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.4, erster Punkt: `enableGaran` an (leer und `0` aus, sonst an)
     * verlangt Dauer, Marke und Modell; eine Zelle nur aus Leerzeichen gilt als leer.
     */
    public function testEnabledGuaranteeRequiresDurationBrandAndModelInProductRow(): void
    {
        $rows = [
            $this->productRow('P1', ['enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P2', ['enableGaran' => '1', 'guaranteeDurationMonths' => ''] + self::COMPLETE_GUARANTEE),
            $this->productRow('P3', ['enableGaran' => '1', 'guaranteeBrand' => ''] + self::COMPLETE_GUARANTEE),
            $this->productRow('P4', ['enableGaran' => '1', 'guaranteeModelIdentifier' => ''] + self::COMPLETE_GUARANTEE),
            $this->productRow('P5', ['enableGaran' => '1', 'guaranteeBrand' => '   '] + self::COMPLETE_GUARANTEE),
            $this->productRow('P6', ['enableGaran' => '0']),
            $this->productRow('P7', ['enableGaran' => '']),
            $this->productRow('P8', ['enableGaran' => 'ja']),
            $this->productRow('P9', ['enableGaran' => '1', 'guaranteeDurationMonths' => '  '] + self::COMPLETE_GUARANTEE),
        ];

        self::assertSame(
            $this->expectedMessages([
                'valueInvalid_guaranteeDurationMonths' => [9],
                'productGuaranteeDataIncomplete' => [2, 3, 4, 5, 8, 9],
            ]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.4, zweiter Punkt: `enableGaran` an und `lgnMode` genau `never`;
     * ein leeres `lgnMode` bedeutet `inherit`.
     */
    public function testEnabledGuaranteeWithLgnModeNeverIsRejected(): void
    {
        $rows = [
            $this->productRow('P1', ['enableGaran' => '1', 'lgnMode' => ''] + self::COMPLETE_GUARANTEE),
            $this->productRow('P2', ['enableGaran' => '1', 'lgnMode' => 'inherit'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P3', ['enableGaran' => '1', 'lgnMode' => 'always'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P4', ['enableGaran' => '1', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P5', ['enableGaran' => '0', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P6', ['enableGaran' => '', 'lgnMode' => 'never']),
        ];

        self::assertSame(
            $this->expectedMessages(['productGuaranteeWithLgnModeNever' => [4]]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.4, dritter Punkt; Abschnitt 10.3: Werte ohne `override` sind
     * zulässig und werden gespeichert.
     */
    public function testOverrideVariantRequiresDurationBrandAndModel(): void
    {
        $rows = [
            $this->productRow('P'),
            $this->variantRow('V2', 'P', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V3', 'P', ['garanMode' => 'override', 'guaranteeDurationMonths' => ''] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V4', 'P', ['garanMode' => 'override', 'guaranteeBrand' => ''] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V5', 'P', ['garanMode' => 'override', 'guaranteeModelIdentifier' => ''] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V6', 'P', ['garanMode' => 'override', 'guaranteeModelIdentifier' => '  '] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V7', 'P', ['garanMode' => 'inherit']),
            $this->variantRow('V8', 'P', ['garanMode' => 'disabled']),
            $this->variantRow('V9', 'P', ['garanMode' => '']),
            $this->variantRow('V10', 'P', ['garanMode' => 'disabled'] + self::COMPLETE_GUARANTEE),
        ];

        self::assertSame(
            $this->expectedMessages(['variantGuaranteeDataIncomplete' => [3, 4, 5, 6]]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.4, vierter Punkt: maßgeblich ist die letzte Produktzeile mit der
     * Artikelnummer, auch nach der Variante; eine Löschzeile hebt die Prüfung auf, eine Zeile
     * mit `ignore` zählt als Elternprodukt.
     */
    public function testOverrideVariantIsRejectedWhenLastParentProductRowInFileIsNever(): void
    {
        $rows = [
            $this->productRow('A', ['lgnMode' => 'never']),
            $this->variantRow('A-1', 'A', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('A-2', 'A', ['garanMode' => 'inherit']),
            $this->variantRow('A-3', 'A', ['garanMode' => 'disabled']),
            $this->productRow('B', ['lgnMode' => 'inherit']),
            $this->variantRow('B-1', 'B', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('C', ['lgnMode' => 'never']),
            $this->productRow('C', ['lgnMode' => 'inherit']),
            $this->variantRow('C-1', 'C', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('D', ['lgnMode' => 'inherit']),
            $this->productRow('D', ['lgnMode' => 'never']),
            $this->variantRow('D-1', 'D', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('E', ['lgnMode' => 'never']),
            $this->productRow('E', ['delete' => 'x']),
            $this->variantRow('E-1', 'E', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('F', ['lgnMode' => 'never', 'ignore' => 'x']),
            $this->variantRow('F-1', 'F', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('G-1', 'G', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('G', ['lgnMode' => 'never']),
        ];

        self::assertSame(
            $this->expectedMessages(['variantGuaranteeOverrideWithParentLgnModeNever' => [2, 12, 17, 18]]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.5: Löschzeilen werden nicht geprüft, Zeilen mit `ignore` schon.
     */
    public function testDeleteRowsAreNotCheckedAndIgnoredRowsAreChecked(): void
    {
        $rows = [
            $this->productRow('A', [
                'delete' => 'x',
                'lgnMode' => 'never',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '036',
                'guaranteeBrand' => str_repeat('ä', 256),
            ]),
            $this->productRow('B', ['ignore' => 'x', 'lgnMode' => 'sometimes']),
            $this->productRow('C'),
            $this->variantRow('C-1', 'C', ['delete' => 'x', 'garanMode' => 'sometimes', 'guaranteeDurationMonths' => '0']),
            $this->variantRow('C-2', 'C', ['ignore' => 'x', 'garanMode' => 'override'] + ['guaranteeBrand' => ''] + self::COMPLETE_GUARANTEE),
            $this->productRow('D', ['ignore' => 'x', 'enableGaran' => '1', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
        ];

        self::assertSame(
            $this->expectedMessages([
                'productValueInvalid_lgnMode' => [2],
                'productGuaranteeWithLgnModeNever' => [6],
                'variantGuaranteeDataIncomplete' => [5],
            ]),
            $this->validate($rows)
        );
    }

    /**
     * Spec 27, Abschnitt 10.5: `lgnMode` und `enableGaran` nur in Produktzeilen, `garanMode`
     * nur in Variantenzeilen; in Sprachzeilen werden alle diese Spalten ignoriert.
     */
    public function testColumnsAreIgnoredInRowTypesNotResponsibleForThem(): void
    {
        $invalidValues = [
            'lgnMode' => 'sometimes',
            'enableGaran' => '1',
            'garanMode' => 'sometimes',
            'guaranteeDurationMonths' => '036',
            'guaranteeBrand' => str_repeat('ä', 256),
            'guaranteeModelIdentifier' => str_repeat('ä', 256),
        ];

        $rows = [
            $this->productRow('A', ['garanMode' => 'sometimes']),
            $this->variantRow('A-1', 'A', ['lgnMode' => 'sometimes', 'enableGaran' => '1']),
            $this->productLanguageRow('A', $invalidValues),
            $this->variantLanguageRow('A-1', $invalidValues),
        ];

        self::assertSame([], $this->validate($rows));
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function versionRowProvider(): iterable
    {
        yield 'mit Versionszeile' => [true];
        yield 'ohne Versionszeile' => [false];
    }

    /**
     * Spec 27, Abschnitt 10.5; Spec 13, Abschnitt 1.8: eine Meldung je Fehlerart mit allen
     * Zeilennummern, gezählt ab dem ersten Datensatz; eine Zeile mit mehreren Verstößen steht
     * in jeder zutreffenden Meldung.
     */
    #[DataProvider('versionRowProvider')]
    public function testOneMessagePerErrorTypeWithAllRowNumbers(bool $withVersionRow): void
    {
        $rows = [
            $this->productRow('A', ['enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
            $this->productRow('B', ['lgnMode' => 'sometimes', 'enableGaran' => '1', 'guaranteeDurationMonths' => '036']),
            $this->productRow('C', ['lgnMode' => 'sometimes']),
            $this->variantRow('C-1', 'C', ['garanMode' => 'override', 'guaranteeDurationMonths' => '036', 'guaranteeModelIdentifier' => 'M-1']),
            $this->productRow('D', ['guaranteeDurationMonths' => '24']),
        ];

        self::assertSame(
            $this->expectedMessages([
                'productValueInvalid_lgnMode' => [2, 3],
                'valueInvalid_guaranteeDurationMonths' => [2, 4, 5],
                'productGuaranteeDataIncomplete' => [2],
                'variantGuaranteeDataIncomplete' => [4],
            ]),
            $this->validate($rows, $withVersionRow)
        );
    }

    /**
     * Spec 27, Abschnitt 10.1: Eine fehlende Spalte gilt wie eine leere Zelle.
     */
    public function testMissingColumnsCountAsEmptyCells(): void
    {
        self::assertSame(
            [],
            $this->validate(
                [$this->productRow('A'), $this->variantRow('A-1', 'A')],
                true,
                self::LEGAL_GUARANTEE_COLUMNS
            )
        );

        self::assertSame(
            $this->expectedMessages([
                'productGuaranteeDataIncomplete' => [1],
                'variantGuaranteeDataIncomplete' => [2],
            ]),
            $this->validate(
                [
                    $this->productRow('A', ['enableGaran' => '1', 'guaranteeDurationMonths' => '36', 'guaranteeModelIdentifier' => 'M-1']),
                    $this->variantRow('A-1', 'A', ['garanMode' => 'override', 'guaranteeDurationMonths' => '36', 'guaranteeModelIdentifier' => 'M-1']),
                ],
                true,
                ['guaranteeBrand']
            )
        );
    }

    /**
     * Spec 27, Abschnitte 10.1 und 10.4: Die Einstellung der Standard-Benutzergruppe und
     * gespeicherte Varianten gehen nicht in die Prüfung ein.
     */
    public function testValidationDoesNotDependOnStandardMemberGroupOrStoredVariants(): void
    {
        self::assertTrue($this->import([
            $this->productRow('A', ['lgnMode' => 'always']),
            $this->variantRow('A-1', 'A', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
        ]));
        self::assertSame('override', $this->fetchVariant('A-1')['garanMode']);

        $standardMemberGroupId = (int) $this->connection->fetchOne('SELECT id FROM tl_member_group ORDER BY id LIMIT 1');
        self::assertGreaterThan(0, $standardMemberGroupId);
        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = $standardMemberGroupId;

        $validRows = [
            $this->productRow('A', ['lgnMode' => 'never']),
            $this->productRow('B', ['lgnMode' => 'inherit', 'enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
        ];
        $invalidRows = [
            $this->productRow('B', ['lgnMode' => 'never', 'enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
        ];
        $expectedInvalidMessages = $this->expectedMessages(['productGuaranteeWithLgnModeNever' => [1]]);

        foreach (['on', 'off', 'blocked', '', null] as $standardMemberGroupLgnMode) {
            $this->connection->executeStatement(
                'UPDATE tl_member_group SET lsShopLgnMode = ? WHERE id = ?',
                [$standardMemberGroupLgnMode ?? '', $standardMemberGroupId]
            );

            if ($standardMemberGroupLgnMode === null) {
                unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
            }

            $caseLabel = $standardMemberGroupLgnMode ?? 'keine Standard-Benutzergruppe';
            self::assertSame([], $this->validate($validRows), $caseLabel);
            self::assertSame($expectedInvalidMessages, $this->validate($invalidRows), $caseLabel);
        }
    }

    /**
     * Spec 27, Abschnitt 10.1; ADR 0032: Ein Verstoß macht die ganze Datei ungültig; danach ist
     * kein Datensatz der Datei geschrieben, auch keine gültige Zeile. Dieselbe Datei ohne den
     * Verstoß wird geschrieben.
     */
    public function testFileWithOneViolationIsRejectedAndNoRecordOfTheFileIsWritten(): void
    {
        $validRows = [
            $this->productRow('A', ['enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('A-1', 'A', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('B'),
        ];
        $invalidRow = $this->productRow('C', ['lgnMode' => 'sometimes']);

        self::assertFalse($this->import([...$validRows, $invalidRow]));
        self::assertSame('importFailed', $_SESSION['lsShop']['importFileInfo']['status']);
        self::assertSame(0, $this->countTestRecords());

        self::assertTrue($this->import($validRows));
        self::assertSame(3, $this->countTestRecords());
    }

    /**
     * Spec 27, Abschnitte 3.1 und 10.1 bis 10.3: Die Werte werden ohne Korrektur und ohne
     * Vorbelegung geschrieben; `enableGaran` wie die bestehenden Ja/Nein-Spalten.
     */
    public function testValidFileWritesValuesUnchanged(): void
    {
        $brandWithSpecialCharacters = ' Söhne & <Partner> "Q" ';

        self::assertTrue($this->import([
            $this->productRow('A', [
                'producer' => 'Hersteller GmbH',
                'lgnMode' => 'always',
                'enableGaran' => 'x',
                'guaranteeDurationMonths' => '360',
                'guaranteeBrand' => $brandWithSpecialCharacters,
                'guaranteeModelIdentifier' => 'X/100',
            ]),
            $this->variantRow('A-1', 'A', [
                'garanMode' => 'disabled',
                'guaranteeDurationMonths' => '30',
                'guaranteeBrand' => 'Variantenmarke',
                'guaranteeModelIdentifier' => 'V-1',
            ]),
            $this->variantRow('A-2', 'A', ['garanMode' => '']),
            $this->productRow('B', ['producer' => 'Hersteller GmbH', 'lgnMode' => 'never', 'enableGaran' => '0']),
        ]));

        self::assertSame(
            [
                'lgnMode' => 'always',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => 360,
                'guaranteeBrand' => $brandWithSpecialCharacters,
                'guaranteeModelIdentifier' => 'X/100',
            ],
            $this->fetchProduct('A')
        );
        self::assertSame(
            [
                'garanMode' => 'disabled',
                'guaranteeDurationMonths' => 30,
                'guaranteeBrand' => 'Variantenmarke',
                'guaranteeModelIdentifier' => 'V-1',
            ],
            $this->fetchVariant('A-1')
        );
        self::assertSame(
            ['garanMode' => 'inherit', 'guaranteeDurationMonths' => null, 'guaranteeBrand' => '', 'guaranteeModelIdentifier' => ''],
            $this->fetchVariant('A-2')
        );
        self::assertSame(
            ['lgnMode' => 'never', 'enableGaran' => '', 'guaranteeDurationMonths' => null, 'guaranteeBrand' => '', 'guaranteeModelIdentifier' => ''],
            $this->fetchProduct('B')
        );
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function emptyFieldProvider(): iterable
    {
        foreach (self::LEGAL_GUARANTEE_COLUMNS as $columnName) {
            yield $columnName . ', leere Zelle' => [$columnName, false];
            yield $columnName . ', fehlende Spalte' => [$columnName, true];
        }
    }

    /**
     * Spec 27, Abschnitt 10.1; ADR 0032: Leere Zelle und fehlende Spalte setzen je Feld den
     * Default beziehungsweise einen leeren Wert, auch über einen gespeicherten Wert hinweg.
     * Dauer, Marke und Modell werden dafür mit ausgeschalteter Herstellergarantie und
     * `disabled` übertragen, weil sie sonst Pflicht sind.
     */
    #[DataProvider('emptyFieldProvider')]
    public function testEmptyCellOrMissingColumnOverwritesStoredValue(string $columnName, bool $omitColumn): void
    {
        $storedProductValues = ['lgnMode' => 'always', 'enableGaran' => '1', 'guaranteeDurationMonths' => '48', 'guaranteeBrand' => 'ACME', 'guaranteeModelIdentifier' => 'M-1'];
        $storedVariantValues = ['garanMode' => 'override', 'guaranteeDurationMonths' => '60', 'guaranteeBrand' => 'Variantenmarke', 'guaranteeModelIdentifier' => 'V-1'];

        self::assertTrue($this->import([
            $this->productRow('A', $storedProductValues),
            $this->variantRow('A-1', 'A', $storedVariantValues),
        ]));

        $productValues = $storedProductValues;
        $variantValues = $storedVariantValues;

        if (in_array($columnName, ['guaranteeDurationMonths', 'guaranteeBrand', 'guaranteeModelIdentifier'], true)) {
            $productValues['enableGaran'] = '';
            $variantValues['garanMode'] = 'disabled';
        }

        $expectedProduct = $this->toStoredValues($productValues);
        $expectedVariant = $this->toStoredValues($variantValues);

        $resetValues = [
            'lgnMode' => 'inherit',
            'enableGaran' => '',
            'garanMode' => 'inherit',
            'guaranteeDurationMonths' => null,
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $emptyColumn = static function (array &$values, array &$expectedValues) use ($columnName, $omitColumn, $resetValues): void {
            if (!array_key_exists($columnName, $values)) {
                return;
            }

            if ($omitColumn) {
                unset($values[$columnName]);
            } else {
                $values[$columnName] = '';
            }

            $expectedValues[$columnName] = $resetValues[$columnName];
        };
        $emptyColumn($productValues, $expectedProduct);
        $emptyColumn($variantValues, $expectedVariant);

        self::assertTrue($this->import(
            [$this->productRow('A', $productValues), $this->variantRow('A-1', 'A', $variantValues)],
            $omitColumn ? [$columnName] : []
        ));

        self::assertSame($expectedProduct, $this->fetchProduct('A'));
        self::assertSame($expectedVariant, $this->fetchVariant('A-1'));
    }

    /**
     * Spec 27, Abschnitt 10: Eine Spalte `enableGll` hat keine Wirkung, weder in der Prüfung
     * noch beim Schreiben.
     */
    public function testEnableGllColumnHasNoEffect(): void
    {
        self::assertSame(
            $this->expectedMessages(['productGuaranteeWithLgnModeNever' => [3]]),
            $this->validate(
                [
                    $this->productRow('A', ['enableGll' => '1', 'lgnMode' => '']),
                    $this->productRow('B', ['enableGll' => '', 'lgnMode' => 'always']),
                    $this->productRow('C', ['enableGll' => '1', 'enableGaran' => '1', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
                ],
                true,
                [],
                ['enableGll']
            )
        );

        self::assertTrue($this->import(
            [
                $this->productRow('A', ['enableGll' => '1', 'lgnMode' => '']),
                $this->productRow('B', ['enableGll' => '', 'lgnMode' => 'always']),
            ],
            [],
            ['enableGll']
        ));

        self::assertSame('inherit', $this->fetchProduct('A')['lgnMode']);
        self::assertSame('always', $this->fetchProduct('B')['lgnMode']);
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string>
     */
    private function productRow(string $code, array $values = []): array
    {
        return array_merge(
            [
                'type' => 'product',
                'productcode' => self::PRODUCTCODE_PREFIX . $code,
                'language' => $this->language,
                'name' => 'Testprodukt ' . $code,
                'category' => $this->categoryAlias,
                'taxclass' => $this->taxClassAlias,
                'price' => '10',
            ],
            $values
        );
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string>
     */
    private function variantRow(string $code, string $parentCode, array $values = []): array
    {
        return array_merge(
            [
                'type' => 'variant',
                'productcode' => self::PRODUCTCODE_PREFIX . $code,
                'parentProductcode' => self::PRODUCTCODE_PREFIX . $parentCode,
                'language' => $this->language,
                'name' => 'Testvariante ' . $code,
            ],
            $values
        );
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string>
     */
    private function productLanguageRow(string $parentCode, array $values = []): array
    {
        return array_merge(
            [
                'type' => 'productLanguage',
                'parentProductcode' => self::PRODUCTCODE_PREFIX . $parentCode,
                'language' => $this->language,
                'name' => 'Produktsprache ' . $parentCode,
            ],
            $values
        );
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string>
     */
    private function variantLanguageRow(string $parentCode, array $values = []): array
    {
        return array_merge(
            [
                'type' => 'variantLanguage',
                'parentProductcode' => self::PRODUCTCODE_PREFIX . $parentCode,
                'language' => $this->language,
                'name' => 'Variantensprache ' . $parentCode,
            ],
            $values
        );
    }

    /**
     * Spaltensatz einer Importdatei der Version 1.4 mit einem Merkmalspaar.
     *
     * @return list<string>
     */
    private function getImportColumns(): array
    {
        $columns = [
            'sorting', 'ignore', 'delete', 'publish', 'type', 'productcode', 'parentProductcode', 'language', 'name',
            'alias', 'property1', 'value1', 'description', 'shortDescription', 'category', 'producer',
        ];

        for ($groupPriceIndex = 0; $groupPriceIndex <= ls_shop_productManagementApiHelper::$int_numImportableGroupPrices; ++$groupPriceIndex) {
            $suffix = $groupPriceIndex === 0 ? '' : '_' . $groupPriceIndex;

            if ($groupPriceIndex > 0) {
                $columns[] = 'useGroupPrices' . $suffix;
                $columns[] = 'priceForGroups' . $suffix;
            }

            foreach (
                [
                    'price', 'priceType', 'useScalePrice', 'scalePriceType', 'scalePriceQuantityDetectionMethod',
                    'scalePriceQuantityDetectionAlwaysSeparateConfigurations', 'scalePriceKeyword', 'scalePrice',
                    'oldPrice', 'oldPriceType', 'useOldPrice',
                ] as $priceColumn
            ) {
                $columns[] = $priceColumn . $suffix;
            }
        }

        return array_merge(
            $columns,
            [
                'taxclass', 'weight', 'weightType', 'unit', 'quantityComparisonUnit', 'quantityComparisonDivisor',
                'quantityDecimals', 'new', 'onSale', 'keywords', 'pageTitle', 'metaDescription', 'image', 'moreImages',
                'changeStock', 'settingsForStockAndDeliveryTime', 'overrideAvailabilitySettingsOfParentProduct',
                'availableFrom', 'preorderingAllowed', 'settingsForStockAndDeliveryTimeInPreorderPhase',
                'recommendedProducts', 'configurator', 'template',
            ],
            ls_shop_productManagementApiHelper::getImportFlexFieldKeys(),
            ls_shop_productManagementApiHelper::getImportFlexFieldKeysLanguageIndependent(),
            self::LEGAL_GUARANTEE_COLUMNS
        );
    }

    /**
     * @param list<array<string, string>> $rows
     * @param list<string> $omittedColumns
     * @param list<string> $additionalColumns
     */
    private function writeImportFile(array $rows, bool $withVersionRow, array $omittedColumns, array $additionalColumns): string
    {
        $columns = array_values(array_merge(array_diff($this->getImportColumns(), $omittedColumns), $additionalColumns));
        $importFilePath = $this->temporaryDirectory . '/import-' . bin2hex(random_bytes(4)) . '.csv';
        $handle = fopen($importFilePath, 'wb');

        if ($withVersionRow) {
            fputcsv($handle, ['1.4', 'äöüßéèê'], ';', '"', '\\');
        }

        fputcsv($handle, $columns, ';', '"', '\\');

        foreach ($rows as $row) {
            self::assertSame([], array_values(array_diff(array_keys($row), $columns)), 'Die Zeile enthält Werte für fehlende Spalten.');
            fputcsv($handle, array_map(static fn (string $column): string => $row[$column] ?? '', $columns), ';', '"', '\\');
        }

        fclose($handle);

        return $importFilePath;
    }

    /**
     * Ohne Konstruktor: Er liest den Importordner des Shops und leert ihn, wenn er nicht genau
     * eine Datei enthält. Die Dateiinformation entspricht `getCurrentlyExistingImportFileInfo()`.
     */
    private function createController(string $importFilePath, bool $withVersionRow): ls_shop_importController
    {
        $reflectionClass = new ReflectionClass(ls_shop_importController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();
        $reflectionClass->getProperty('bln_importFileHasRowForVersionAndSpecialChars')->setValue($controller, $withVersionRow);
        $reflectionClass->getProperty('dataRowTypesInOrderToProcess')->setValue($controller, ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess);

        $_SESSION['lsShop']['importFileInfo'] = [
            'hash' => sha1($importFilePath),
            'name' => basename($importFilePath),
            'fullFilename' => $importFilePath,
            'date' => '',
            'size' => '',
            'status' => 'notValidatedYet',
            'numRecords' => ['products' => 0, 'variants' => 0, 'productLanguages' => 0, 'variantLanguages' => 0],
            'numProcessedRecords' => ['products' => 0, 'variants' => 0, 'productLanguages' => 0, 'variantLanguages' => 0],
            'recommendedProductsTranslated' => false,
            'changesStock' => false,
            'deletesRecords' => false,
            'arrKeys' => [],
            'arrAnalyzingInfo' => ['product_productcodes' => [], 'variant_productcodes' => []],
            'hasError' => false,
            'arrMessages' => [],
            'arrImportInfos' => [
                'productsToIgnore' => [],
                'productsToDelete' => [],
                'variantsToIgnore' => [],
                'variantsToDelete' => [],
                'productsProductcodeToID' => [],
                'variantsProductcodeToID' => [],
            ],
            'currentlyProcessingDataRowType' => null,
            'lastFilePointerPosition' => 0,
        ];

        return $controller;
    }

    /**
     * @param list<array<string, string>> $rows
     * @param list<string> $omittedColumns
     * @param list<string> $additionalColumns
     *
     * @return list<string>
     */
    private function validate(array $rows, bool $withVersionRow = true, array $omittedColumns = [], array $additionalColumns = []): array
    {
        $controller = $this->createController(
            $this->writeImportFile($rows, $withVersionRow, $omittedColumns, $additionalColumns),
            $withVersionRow
        );
        $controller->validateFile();

        return $_SESSION['lsShop']['importFileInfo']['arrMessages'];
    }

    /**
     * Validiert und importiert wie das Backend-Modul. Der letzte Durchlauf von `importFile()`
     * (Status `importFinished`) entfällt: Er schreibt den Zeitstempel der letzten
     * Datenänderung in die `localconfig` und läge damit außerhalb der Transaktion.
     *
     * @param list<array<string, string>> $rows
     * @param list<string> $omittedColumns
     * @param list<string> $additionalColumns
     */
    private function import(array $rows, array $omittedColumns = [], array $additionalColumns = []): bool
    {
        $controller = $this->createController($this->writeImportFile($rows, true, $omittedColumns, $additionalColumns), true);
        $isValid = $controller->validateFile();

        for ($round = 0; $round < 50; ++$round) {
            $importFileInfo = $_SESSION['lsShop']['importFileInfo'];

            if ($importFileInfo['status'] === 'importFailed' || $importFileInfo['recommendedProductsTranslated']) {
                return $isValid;
            }

            /*
             * `importFile()` liest Versions- und Kopfzeile ohne Spalte `type` und meldet dafür
             * "Undefined array key" (vorbestehend).
             */
            @$controller->importFile();
        }

        self::fail('Der Import wurde nicht abgeschlossen.');
    }

    /**
     * @param array<string, list<int>> $rowNumbersByErrorKey
     *
     * @return list<string>
     */
    private function expectedMessages(array $rowNumbersByErrorKey): array
    {
        self::assertSame([], array_values(array_diff(array_keys($rowNumbersByErrorKey), self::LEGAL_GUARANTEE_ERROR_KEYS)));

        $messages = [];

        foreach (self::LEGAL_GUARANTEE_ERROR_KEYS as $errorKey) {
            self::assertNotEmpty($GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importErrors'][$errorKey] ?? null, $errorKey);

            if (isset($rowNumbersByErrorKey[$errorKey])) {
                $messages[] = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['importErrors'][$errorKey],
                    implode(', ', $rowNumbersByErrorKey[$errorKey])
                );
            }
        }

        return $messages;
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string|int|null>
     */
    private function toStoredValues(array $values): array
    {
        $storedValues = [];

        foreach ($values as $columnName => $value) {
            $storedValues[$columnName] = match ($columnName) {
                'enableGaran' => $value ? '1' : '',
                'guaranteeDurationMonths' => $value === '' ? null : (int) $value,
                default => $value,
            };
        }

        return $storedValues;
    }

    /**
     * @return array<string, string|int|null>
     */
    private function fetchProduct(string $code): array
    {
        $product = $this->connection->fetchAssociative(
            'SELECT lgnMode, enableGaran, guaranteeDurationMonths, guaranteeBrand, guaranteeModelIdentifier FROM tl_ls_shop_product WHERE lsShopProductCode = ?',
            [self::PRODUCTCODE_PREFIX . $code]
        );
        self::assertIsArray($product, $code);

        return $this->normalizeDuration($product);
    }

    /**
     * @return array<string, string|int|null>
     */
    private function fetchVariant(string $code): array
    {
        $variant = $this->connection->fetchAssociative(
            'SELECT garanMode, guaranteeDurationMonths, guaranteeBrand, guaranteeModelIdentifier FROM tl_ls_shop_variant WHERE lsShopVariantCode = ?',
            [self::PRODUCTCODE_PREFIX . $code]
        );
        self::assertIsArray($variant, $code);

        return $this->normalizeDuration($variant);
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, string|int|null>
     */
    private function normalizeDuration(array $record): array
    {
        $record['guaranteeDurationMonths'] = $record['guaranteeDurationMonths'] === null ? null : (int) $record['guaranteeDurationMonths'];

        return $record;
    }

    private function countTestRecords(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_ls_shop_product WHERE lsShopProductCode LIKE ?', [self::PRODUCTCODE_PREFIX . '%'])
            + (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_ls_shop_variant WHERE lsShopVariantCode LIKE ?', [self::PRODUCTCODE_PREFIX . '%']);
    }
}
