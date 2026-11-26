<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../Support/ContaoTestApplication.php';

use Contao\System;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\Tests\Support\ContaoTestApplication;
use Merconis\Core\ls_shop_apiController_productManagement;
use Merconis\Core\ls_shop_languageHelper;
use Merconis\Core\ls_shop_productManagementApiHelper;
use Merconis\Core\ls_shop_productManagementApiPreprocessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Product Management API für die LGN- und GARAN-Felder nach Spec 27, Abschnitt 10 (ADR 0032):
 * Vorprüfung in `ls_shop_productManagementApiPreprocessor::preprocess()` und Schreiben über
 * `ls_shop_productManagementApiHelper`, im Contao-Anwendungskontext des Host-Projekts gegen
 * die Entwicklungsdatenbank. Die Datensätze kommen wie im Request über `json_decode()` an.
 *
 * Jeder Test läuft in einer Transaktion, die am Ende zurückgerollt wird; danach prüft
 * `tearDown()`, dass kein Datensatz mit dem Testpräfix übrig ist (ADR 0016, ADR 0032).
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LegalGuaranteeProductManagementApiTest extends TestCase
{
    private const PRODUCTCODE_PREFIX = 'LGN-API-TEST-';
    private const CONTEXT = 'apiResource_writeProductData';
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';

    private const LEGAL_GUARANTEE_FIELDS = [
        'lgnMode',
        'enableGaran',
        'garanMode',
        'guaranteeDurationMonths',
        'guaranteeBrand',
        'guaranteeModelIdentifier',
    ];

    private const COMPLETE_GUARANTEE = [
        'guaranteeDurationMonths' => '36',
        'guaranteeBrand' => 'ACME',
        'guaranteeModelIdentifier' => 'M-1',
    ];

    private ?Connection $connection = null;
    private string $taxClassAlias;
    private string $categoryAlias;
    private string $language;

    protected function setUp(): void
    {
        parent::setUp();

        ContaoTestApplication::boot('de');

        /*
         * `ls_shop_languageHelper::determineExistingLanguages()` liest `$objPage->domain`; die
         * Seite aus `ContaoTestApplication::boot()` hat keine Domain.
         */
        unset($GLOBALS['objPage']);
        $GLOBALS['TL_LANGUAGE'] = 'de';

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

        parent::tearDown();

        self::assertSame(0, $remainingTestRecords, 'Nach dem Zurückrollen waren noch Testdatensätze vorhanden.');
    }

    /**
     * Spec 27, Abschnitte 10.2 und 10.5: `lgnMode` leer, fehlend oder `null` ergibt `inherit`;
     * sonst genau `inherit`, `always` oder `never`. Ganzzahl, Gleitkommazahl, Wahrheitswert
     * und Liste sind Verstöße.
     */
    public function testLgnModeAcceptsOnlyEmptyOrExactValuesInProductRows(): void
    {
        $submittedValues = ['', null, 'inherit', 'always', 'never', 'Inherit', ' always', 'never ', '0', 0, 'sometimes', 1.0, true, ['never']];
        $rows = [$this->productRow('P-MISSING')];

        foreach ($submittedValues as $index => $lgnMode) {
            $rows[] = $this->productRow('P' . $index, ['lgnMode' => $lgnMode]);
        }

        $preprocessingResult = $this->preprocess($rows);

        self::assertSame(
            $this->expectedMessages(array_map(static fn (int $rowNumber): array => [$rowNumber, 'lgnMode', 'lgnModeInvalid'], range(7, 15))),
            $this->normalizeMessages($preprocessingResult['arr_messages'])
        );
        self::assertSame(
            ['inherit', 'inherit', 'inherit', 'inherit', 'always', 'never'],
            array_column(array_slice($preprocessingResult['arr_preprocessedDataRows'], 0, 6), 'lgnMode')
        );
    }

    /**
     * Spec 27, Abschnitte 10.3 und 10.5: `garanMode` leer, fehlend oder `null` ergibt
     * `inherit`; sonst genau `inherit`, `disabled` oder `override`.
     */
    public function testGaranModeAcceptsOnlyEmptyOrExactValuesInVariantRows(): void
    {
        $submittedValues = ['', null, 'inherit', 'disabled', 'override', 'Override', ' disabled', '0', 0, 'none', 2.5, false, ['override']];
        $rows = [$this->productRow('P'), $this->variantRow('V-MISSING', 'P')];

        foreach ($submittedValues as $index => $garanMode) {
            $rows[] = $this->variantRow('V' . $index, 'P', ['garanMode' => $garanMode] + self::COMPLETE_GUARANTEE);
        }

        $preprocessingResult = $this->preprocess($rows);

        self::assertSame(
            $this->expectedMessages(array_map(static fn (int $rowNumber): array => [$rowNumber, 'garanMode', 'garanModeInvalid'], range(8, 15))),
            $this->normalizeMessages($preprocessingResult['arr_messages'])
        );
        self::assertSame(
            ['inherit', 'inherit', 'inherit', 'inherit', 'disabled', 'override'],
            array_column(array_slice($preprocessingResult['arr_preprocessedDataRows'], 1, 6), 'garanMode')
        );
    }

    /**
     * Spec 27, Abschnitte 3.2, 10.2, 10.3 und 10.5: nur Ziffern ohne führende Null im Raster
     * 30 bis 360, durch 6 teilbar; eine Ganzzahl gilt wie ihre Dezimaldarstellung. Geprüft auch
     * bei ausgeschalteter Herstellergarantie und in Variantenzeilen ohne `override`.
     */
    public function testDurationAcceptsOnlyGridValuesInProductAndVariantRows(): void
    {
        $productDurations = [
            '', null, '30', '36', '354', '360', 36, 360,
            '24', '31', '366', '0', '36.0', '036', ' 36', '36 ', "36\n", '-36', 'drei', 24, -36, 36.0, true, [36],
        ];
        $rows = [];

        foreach ($productDurations as $index => $duration) {
            $rows[] = $this->productRow('P' . $index, ['guaranteeDurationMonths' => $duration]);
        }

        foreach (['30', 30, '036', 36.0] as $index => $duration) {
            $rows[] = $this->variantRow('V' . $index, 'P0', ['guaranteeDurationMonths' => $duration]);
        }

        $preprocessingResult = $this->preprocess($rows);

        self::assertSame(
            $this->expectedMessages(array_map(
                static fn (int $rowNumber): array => [$rowNumber, 'guaranteeDurationMonths', 'guaranteeDurationMonthsInvalid'],
                [...range(9, 24), 27, 28]
            )),
            $this->normalizeMessages($preprocessingResult['arr_messages'])
        );
        self::assertSame(
            ['', '', '30', '36', '354', '360', '36', '360'],
            array_column(array_slice($preprocessingResult['arr_preprocessedDataRows'], 0, 8), 'guaranteeDurationMonths')
        );
        self::assertSame('30', $preprocessingResult['arr_preprocessedDataRows'][25]['guaranteeDurationMonths']);
    }

    /**
     * Spec 27, Abschnitte 10.2, 10.3 und 10.5; Implementierungsentscheidung 0034: höchstens
     * 255 Zeichen, gezählt mit `mb_strlen()`; eine Ganzzahl gilt wie ihre Dezimaldarstellung;
     * der Wert wird nicht getrimmt.
     */
    public function testBrandAndModelAreLimitedTo255CharactersInProductAndVariantRows(): void
    {
        $longestValue = str_repeat('ä', 255);
        $tooLongValue = str_repeat('ä', 256);
        $rows = [];

        foreach (['guaranteeBrand', 'guaranteeModelIdentifier'] as $fieldName) {
            foreach ([$longestValue, ' ACME ', 4711, $tooLongValue, 1.5, true, ['ACME']] as $value) {
                $rows[] = $this->productRow('P' . count($rows), [$fieldName => $value]);
            }

            foreach ([$longestValue, $tooLongValue] as $value) {
                $rows[] = $this->variantRow('V' . count($rows), 'P0', [$fieldName => $value]);
            }
        }

        $preprocessingResult = $this->preprocess($rows);

        self::assertSame(
            $this->expectedMessages([
                [4, 'guaranteeBrand', 'guaranteeBrandInvalid'],
                [5, 'guaranteeBrand', 'guaranteeBrandInvalid'],
                [6, 'guaranteeBrand', 'guaranteeBrandInvalid'],
                [7, 'guaranteeBrand', 'guaranteeBrandInvalid'],
                [9, 'guaranteeBrand', 'guaranteeBrandInvalid'],
                [13, 'guaranteeModelIdentifier', 'guaranteeModelIdentifierInvalid'],
                [14, 'guaranteeModelIdentifier', 'guaranteeModelIdentifierInvalid'],
                [15, 'guaranteeModelIdentifier', 'guaranteeModelIdentifierInvalid'],
                [16, 'guaranteeModelIdentifier', 'guaranteeModelIdentifierInvalid'],
                [18, 'guaranteeModelIdentifier', 'guaranteeModelIdentifierInvalid'],
            ]),
            $this->normalizeMessages($preprocessingResult['arr_messages'])
        );
        self::assertSame($longestValue, $preprocessingResult['arr_preprocessedDataRows'][0]['guaranteeBrand']);
        self::assertSame(' ACME ', $preprocessingResult['arr_preprocessedDataRows'][1]['guaranteeBrand']);
        self::assertSame('4711', $preprocessingResult['arr_preprocessedDataRows'][2]['guaranteeBrand']);
        self::assertSame(' ACME ', $preprocessingResult['arr_preprocessedDataRows'][10]['guaranteeModelIdentifier']);
    }

    /**
     * Spec 27, Abschnitte 10.2, 10.4 und 10.5: `enableGaran` ohne Wertprüfung (leer, `0`,
     * `false`, `null` und fehlend aus, sonst an) verlangt Dauer, Marke und Modell; die Meldung
     * steht bei jedem leeren Feld. Ein Wert nur aus Leerzeichen gilt als leer; als Dauer ist er
     * zusätzlich ein unzulässiger Wert.
     */
    public function testEnabledGuaranteeRequiresDurationBrandAndModelInProductRow(): void
    {
        $rows = [
            $this->productRow('P1', ['enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P2', ['enableGaran' => '1', 'guaranteeDurationMonths' => ''] + self::COMPLETE_GUARANTEE),
            $this->productRow('P3', ['enableGaran' => '1', 'guaranteeBrand' => null] + self::COMPLETE_GUARANTEE),
            $this->productRow('P4', ['enableGaran' => true, 'guaranteeModelIdentifier' => '   '] + self::COMPLETE_GUARANTEE),
            $this->productRow('P5', ['enableGaran' => 'ja']),
            $this->productRow('P6', ['enableGaran' => '1', 'guaranteeDurationMonths' => '  '] + self::COMPLETE_GUARANTEE),
            $this->productRow('P7', ['enableGaran' => '0']),
            $this->productRow('P8', ['enableGaran' => '']),
            $this->productRow('P9', ['enableGaran' => false]),
            $this->productRow('P10', ['enableGaran' => 0]),
            $this->productRow('P11', ['enableGaran' => null]),
            $this->productRow('P12'),
        ];

        $preprocessingResult = $this->preprocess($rows);

        self::assertSame(
            $this->expectedMessages([
                [2, 'guaranteeDurationMonths', 'productGuaranteeDurationMonthsMissing'],
                [3, 'guaranteeBrand', 'productGuaranteeBrandMissing'],
                [4, 'guaranteeModelIdentifier', 'productGuaranteeModelIdentifierMissing'],
                [5, 'guaranteeDurationMonths', 'productGuaranteeDurationMonthsMissing'],
                [5, 'guaranteeBrand', 'productGuaranteeBrandMissing'],
                [5, 'guaranteeModelIdentifier', 'productGuaranteeModelIdentifierMissing'],
                [6, 'guaranteeDurationMonths', 'guaranteeDurationMonthsInvalid'],
            ]),
            $this->normalizeMessages($preprocessingResult['arr_messages'])
        );
        self::assertSame(
            ['1', '1', '1', '1', '1', '1', '', '', '', '', '', ''],
            array_column($preprocessingResult['arr_preprocessedDataRows'], 'enableGaran')
        );
    }

    /**
     * Spec 27, Abschnitte 10.4 und 10.5: `enableGaran` an und `lgnMode` genau `never` ist ein
     * Verstoß; die Meldung steht bei `enableGaran`. Leeres oder fehlendes `lgnMode` bedeutet
     * `inherit`.
     */
    public function testEnabledGuaranteeWithLgnModeNeverIsRejected(): void
    {
        $productWithoutLgnMode = $this->productRow('P1', ['enableGaran' => '1'] + self::COMPLETE_GUARANTEE);

        $rows = [
            $productWithoutLgnMode,
            $this->productRow('P2', ['enableGaran' => '1', 'lgnMode' => ''] + self::COMPLETE_GUARANTEE),
            $this->productRow('P3', ['enableGaran' => '1', 'lgnMode' => 'inherit'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P4', ['enableGaran' => '1', 'lgnMode' => 'always'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P5', ['enableGaran' => '1', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P6', ['enableGaran' => '0', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
            $this->productRow('P7', ['enableGaran' => '', 'lgnMode' => 'never']),
            $this->productRow('P8', ['enableGaran' => '1', 'lgnMode' => 'never '] + self::COMPLETE_GUARANTEE),
        ];

        self::assertSame(
            $this->expectedMessages([
                [5, 'enableGaran', 'productGuaranteeWithLgnModeNever'],
                [8, 'lgnMode', 'lgnModeInvalid'],
            ]),
            $this->normalizeMessages($this->preprocess($rows)['arr_messages'])
        );
    }

    /**
     * Spec 27, Abschnitte 10.3 bis 10.5: Eine Variante auf `override` verlangt Dauer, Marke und
     * Modell; die Meldung steht bei jedem leeren Feld. `inherit`, `disabled` und leer verlangen
     * nichts; gefüllte Werte sind dort zulässig.
     */
    public function testOverrideVariantRequiresDurationBrandAndModel(): void
    {
        $rows = [
            $this->productRow('P'),
            $this->variantRow('V1', 'P', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V2', 'P', ['garanMode' => 'override', 'guaranteeDurationMonths' => null] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V3', 'P', ['garanMode' => 'override', 'guaranteeBrand' => ''] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V4', 'P', ['garanMode' => 'override', 'guaranteeModelIdentifier' => ' '] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V5', 'P', ['garanMode' => 'override']),
            $this->variantRow('V6', 'P', ['garanMode' => 'inherit']),
            $this->variantRow('V7', 'P', ['garanMode' => 'disabled']),
            $this->variantRow('V8', 'P', ['garanMode' => '']),
            $this->variantRow('V9', 'P', ['garanMode' => 'disabled'] + self::COMPLETE_GUARANTEE),
        ];

        self::assertSame(
            $this->expectedMessages([
                [3, 'guaranteeDurationMonths', 'variantGuaranteeDurationMonthsMissing'],
                [4, 'guaranteeBrand', 'variantGuaranteeBrandMissing'],
                [5, 'guaranteeModelIdentifier', 'variantGuaranteeModelIdentifierMissing'],
                [6, 'guaranteeDurationMonths', 'variantGuaranteeDurationMonthsMissing'],
                [6, 'guaranteeBrand', 'variantGuaranteeBrandMissing'],
                [6, 'guaranteeModelIdentifier', 'variantGuaranteeModelIdentifierMissing'],
            ]),
            $this->normalizeMessages($this->preprocess($rows)['arr_messages'])
        );
    }

    /**
     * Spec 27, Abschnitte 10.4 und 10.5; Implementierungsentscheidung 0034: Variante auf
     * `override`, deren Elternprodukt im selben Aufruf auf `never` steht; maßgeblich ist die
     * letzte Produktzeile mit der Artikelnummer nach der Normalisierung. Die Meldung steht bei
     * `garanMode`. Ein gespeichertes Elternprodukt geht nicht in die Prüfung ein.
     */
    public function testOverrideVariantIsRejectedWhenLastParentProductRowInRequestIsNever(): void
    {
        $this->writeRows([$this->productRow('STORED', ['lgnMode' => 'never'])]);

        $rows = [
            $this->variantRow('A-0', 'A', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('A', ['lgnMode' => 'never']),
            $this->variantRow('A-1', 'A', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('A-2', 'A', ['garanMode' => 'inherit']),
            $this->variantRow('A-3', 'A', ['garanMode' => 'disabled'] + self::COMPLETE_GUARANTEE),
            $this->productRow('B', ['lgnMode' => 'inherit']),
            $this->variantRow('B-1', 'B', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('C', ['lgnMode' => 'never']),
            $this->productRow('C', ['lgnMode' => 'always']),
            $this->variantRow('C-1', 'C', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('D', ['lgnMode' => 'always']),
            $this->productRow('D', ['lgnMode' => 'never']),
            $this->variantRow('D-1', 'D', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('E-1', 'A', ['garanMode' => 'override', 'parentProductcode' => ' ' . self::PRODUCTCODE_PREFIX . 'A '] + self::COMPLETE_GUARANTEE),
            $this->variantRow('STORED-1', 'STORED', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
        ];

        self::assertSame(
            $this->expectedMessages([
                [1, 'garanMode', 'variantGuaranteeOverrideWithParentLgnModeNever'],
                [3, 'garanMode', 'variantGuaranteeOverrideWithParentLgnModeNever'],
                [13, 'garanMode', 'variantGuaranteeOverrideWithParentLgnModeNever'],
                [14, 'garanMode', 'variantGuaranteeOverrideWithParentLgnModeNever'],
            ]),
            $this->normalizeMessages($this->preprocess($rows)['arr_messages'])
        );
    }

    /**
     * Spec 27, Abschnitt 10.5: `lgnMode` und `enableGaran` nur in Produktzeilen, `garanMode`
     * nur in Variantenzeilen; in anderen Zeilentypen, auch in Sprachzeilen, werden die Felder
     * ignoriert.
     */
    public function testFieldsAreIgnoredInRowTypesNotResponsibleForThem(): void
    {
        $invalidValues = [
            'lgnMode' => 'sometimes',
            'enableGaran' => '1',
            'garanMode' => 'none',
            'guaranteeDurationMonths' => '31',
            'guaranteeBrand' => str_repeat('ä', 256),
            'guaranteeModelIdentifier' => 1.5,
        ];

        $rows = [
            $this->productRow('P', ['garanMode' => 'none']),
            $this->variantRow('V', 'P', ['lgnMode' => 'sometimes', 'enableGaran' => '1']),
            $this->productLanguageRow('P', $invalidValues),
            $this->variantLanguageRow('V', $invalidValues),
        ];

        $preprocessingResult = $this->preprocess($rows);

        self::assertSame([], $this->normalizeMessages($preprocessingResult['arr_messages']));
        self::assertFalse($preprocessingResult['bln_hasError']);
        self::assertSame('', $preprocessingResult['arr_preprocessedDataRows'][0]['garanMode']);
        self::assertSame(['', ''], [$preprocessingResult['arr_preprocessedDataRows'][1]['lgnMode'], $preprocessingResult['arr_preprocessedDataRows'][1]['enableGaran']]);

        foreach ([2, 3] as $languageRowIndex) {
            foreach (self::LEGAL_GUARANTEE_FIELDS as $fieldName) {
                self::assertSame('', $preprocessingResult['arr_preprocessedDataRows'][$languageRowIndex][$fieldName], $fieldName);
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function requestLanguageProvider(): iterable
    {
        yield 'de' => ['de'];
        yield 'en' => ['en'];
    }

    /**
     * Spec 27, Abschnitt 10.5: Meldungen je Datensatz und Feld auf dem bestehenden Weg der
     * Vorprüfung (`arr_messages[<Datensatz>][<Feld>]`). Die Sprachdatei `default` ist zu Beginn
     * nicht geladen; der Code lädt sie in der Sprache des Requests (`$GLOBALS['TL_LANGUAGE']`).
     * Der Aufruf deckt alle Meldungen ab.
     */
    #[DataProvider('requestLanguageProvider')]
    public function testMessagesStandPerRecordAndFieldInRequestLanguage(string $requestLanguage): void
    {
        $rows = [
            $this->productRow('P1', ['lgnMode' => 'sometimes']),
            $this->variantRow('V2', 'P1', ['garanMode' => 'none']),
            $this->productRow('P3', ['guaranteeDurationMonths' => '31']),
            $this->productRow('P4', ['guaranteeBrand' => str_repeat('ä', 256)]),
            $this->productRow('P5', ['guaranteeModelIdentifier' => str_repeat('ä', 256)]),
            $this->productRow('P6', ['enableGaran' => '1']),
            $this->productRow('P7', ['enableGaran' => '1', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('V8', 'P9', ['garanMode' => 'override']),
            $this->productRow('P9', ['lgnMode' => 'never']),
        ];
        $expectedMessages = [
            [1, 'lgnMode', 'lgnModeInvalid'],
            [2, 'garanMode', 'garanModeInvalid'],
            [3, 'guaranteeDurationMonths', 'guaranteeDurationMonthsInvalid'],
            [4, 'guaranteeBrand', 'guaranteeBrandInvalid'],
            [5, 'guaranteeModelIdentifier', 'guaranteeModelIdentifierInvalid'],
            [6, 'guaranteeDurationMonths', 'productGuaranteeDurationMonthsMissing'],
            [6, 'guaranteeBrand', 'productGuaranteeBrandMissing'],
            [6, 'guaranteeModelIdentifier', 'productGuaranteeModelIdentifierMissing'],
            [7, 'enableGaran', 'productGuaranteeWithLgnModeNever'],
            [8, 'garanMode', 'variantGuaranteeOverrideWithParentLgnModeNever'],
            [8, 'guaranteeDurationMonths', 'variantGuaranteeDurationMonthsMissing'],
            [8, 'guaranteeBrand', 'variantGuaranteeBrandMissing'],
            [8, 'guaranteeModelIdentifier', 'variantGuaranteeModelIdentifierMissing'],
        ];

        (new ReflectionProperty(System::class, 'arrLanguageFiles'))->setValue(null, []);
        $GLOBALS['TL_LANG'] = [];
        $GLOBALS['TL_LANGUAGE'] = $requestLanguage;

        $preprocessingResult = $this->preprocess($rows);

        self::assertTrue($preprocessingResult['bln_hasError']);
        self::assertSame(
            $this->expectedMessages($expectedMessages, $requestLanguage),
            $this->normalizeMessages($preprocessingResult['arr_messages'])
        );
        self::assertSame(
            $requestLanguage,
            array_key_last((new ReflectionProperty(System::class, 'arrLanguageFiles'))->getValue()['default'])
        );

        foreach (array_unique(array_column($expectedMessages, 2)) as $messageKey) {
            self::assertNotSame($this->readLanguageText('de', $messageKey), $this->readLanguageText('en', $messageKey), $messageKey);
        }
    }

    /**
     * Spec 27, Abschnitt 10.1; ADR 0032: Ein Aufruf mit einem Verstoß wird abgelehnt; kein
     * Datensatz des Aufrufs ist geschrieben, auch keine gültige Zeile. Das gilt auch für den
     * Durchlauf gegen das Elternprodukt. Derselbe Aufruf ohne den Verstoß wird geschrieben.
     */
    public function testCallWithOneViolationIsRejectedAndNoRecordOfTheCallIsWritten(): void
    {
        $validRows = [
            $this->productRow('A', ['enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
            $this->variantRow('A-1', 'A', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
            $this->productRow('B'),
        ];

        $apiReceiver = $this->callWriteProductData([...$validRows, $this->productRow('C', ['lgnMode' => 'sometimes'])]);

        self::assertSame('error', $apiReceiver->status);
        self::assertSame(
            $this->expectedMessages([[4, 'lgnMode', 'lgnModeInvalid']]),
            $this->normalizeMessages($apiReceiver->message)
        );
        self::assertSame(0, $this->countTestRecords());

        $apiReceiver = $this->callWriteProductData([
            $this->productRow('D', ['lgnMode' => 'never']),
            $this->productRow('E'),
            $this->variantRow('D-1', 'D', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
        ]);

        self::assertSame('error', $apiReceiver->status);
        self::assertSame(
            $this->expectedMessages([[3, 'garanMode', 'variantGuaranteeOverrideWithParentLgnModeNever']]),
            $this->normalizeMessages($apiReceiver->message)
        );
        self::assertSame(0, $this->countTestRecords());

        $this->writeRows($validRows);
        self::assertSame(3, $this->countTestRecords());
    }

    /**
     * Spec 27, Abschnitte 3.1 und 10.1 bis 10.3: Die Werte werden ohne Korrektur und ohne
     * Vorbelegung geschrieben; `enableGaran` wie `preprocess_pseudoBoolean()`, leere Dauer als
     * `NULL`.
     */
    public function testValidCallWritesValuesUnchanged(): void
    {
        $brandWithSpecialCharacters = ' Söhne & <Partner> "Q" ';

        $this->writeRows([
            $this->productRow('A', [
                'producer' => 'Hersteller GmbH',
                'lgnMode' => 'always',
                'enableGaran' => 'x',
                'guaranteeDurationMonths' => 360,
                'guaranteeBrand' => $brandWithSpecialCharacters,
                'guaranteeModelIdentifier' => 'X/100',
            ]),
            $this->variantRow('A-1', 'A', [
                'garanMode' => 'disabled',
                'guaranteeDurationMonths' => '30',
                'guaranteeBrand' => 'Variantenmarke',
                'guaranteeModelIdentifier' => 4711,
            ]),
            $this->variantRow('A-2', 'A', ['garanMode' => null]),
            $this->productRow('B', ['producer' => 'Hersteller GmbH', 'lgnMode' => 'never', 'enableGaran' => false]),
        ]);

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
            ['garanMode' => 'disabled', 'guaranteeDurationMonths' => 30, 'guaranteeBrand' => 'Variantenmarke', 'guaranteeModelIdentifier' => '4711'],
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
     * @return iterable<string, array{string, string}>
     */
    public static function emptyFieldProvider(): iterable
    {
        foreach (self::LEGAL_GUARANTEE_FIELDS as $fieldName) {
            yield $fieldName . ', leerer Wert' => [$fieldName, 'leer'];
            yield $fieldName . ', Feld fehlt' => [$fieldName, 'fehlt'];
            yield $fieldName . ', null' => [$fieldName, 'null'];
        }
    }

    /**
     * Spec 27, Abschnitte 10.1 und 10.5; ADR 0032: Leerer Wert, fehlendes Feld und `null`
     * setzen je Feld den Default beziehungsweise einen leeren Wert, auch über einen
     * gespeicherten Wert hinweg. Dauer, Marke und Modell werden dafür mit ausgeschalteter
     * Herstellergarantie und `disabled` übertragen, weil sie sonst Pflicht sind.
     */
    #[DataProvider('emptyFieldProvider')]
    public function testEmptyValueOrMissingFieldOverwritesStoredValue(string $fieldName, string $emptyMode): void
    {
        $storedProductValues = ['lgnMode' => 'always', 'enableGaran' => '1', 'guaranteeDurationMonths' => '48', 'guaranteeBrand' => 'ACME', 'guaranteeModelIdentifier' => 'M-1'];
        $storedVariantValues = ['garanMode' => 'override', 'guaranteeDurationMonths' => '60', 'guaranteeBrand' => 'Variantenmarke', 'guaranteeModelIdentifier' => 'V-1'];

        $this->writeRows([
            $this->productRow('A', $storedProductValues),
            $this->variantRow('A-1', 'A', $storedVariantValues),
        ]);

        $productValues = $storedProductValues;
        $variantValues = $storedVariantValues;

        if (in_array($fieldName, ['guaranteeDurationMonths', 'guaranteeBrand', 'guaranteeModelIdentifier'], true)) {
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

        $productRow = $this->productRow('A', $productValues);
        $variantRow = $this->variantRow('A-1', 'A', $variantValues);

        foreach ([[&$productRow, &$expectedProduct], [&$variantRow, &$expectedVariant]] as [&$row, &$expectedValues]) {
            if (!array_key_exists($fieldName, $expectedValues)) {
                continue;
            }

            if ($emptyMode === 'fehlt') {
                unset($row[$fieldName]);
            } else {
                $row[$fieldName] = $emptyMode === 'null' ? null : '';
            }

            $expectedValues[$fieldName] = $resetValues[$fieldName];
        }

        unset($row, $expectedValues);

        $this->writeRows([$productRow, $variantRow]);

        self::assertSame($expectedProduct, $this->fetchProduct('A'));
        self::assertSame($expectedVariant, $this->fetchVariant('A-1'));
    }

    /**
     * Spec 27, Abschnitte 10.1 und 10.4: Die Einstellung der Standard-Benutzergruppe
     * (auch angepasst über den Hook `getGroupSettingsForUser`) und gespeicherte Varianten auf
     * `override` gehen nicht in die Prüfung ein.
     */
    public function testValidationDoesNotDependOnStandardMemberGroupOrStoredVariants(): void
    {
        $this->writeRows([
            $this->productRow('A', ['lgnMode' => 'always']),
            $this->variantRow('A-1', 'A', ['garanMode' => 'override'] + self::COMPLETE_GUARANTEE),
        ]);
        self::assertSame('override', $this->fetchVariant('A-1')['garanMode']);

        $GLOBALS['MERCONIS_HOOKS']['getGroupSettingsForUser'] = [[LegalGuaranteeProductManagementApiTestHook::class, 'adjustGroupSettings']];

        $standardMemberGroupId = (int) $this->connection->fetchOne('SELECT id FROM tl_member_group ORDER BY id LIMIT 1');
        self::assertGreaterThan(0, $standardMemberGroupId);
        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = $standardMemberGroupId;

        $validRows = [
            $this->productRow('A', ['lgnMode' => 'never']),
            $this->productRow('B', ['lgnMode' => 'inherit', 'enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
            $this->productRow('C', ['lgnMode' => 'always', 'enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
        ];
        $invalidRows = [
            $this->productRow('B', ['lgnMode' => 'never', 'enableGaran' => '1'] + self::COMPLETE_GUARANTEE),
        ];
        $expectedInvalidMessages = $this->expectedMessages([[1, 'enableGaran', 'productGuaranteeWithLgnModeNever']]);

        foreach (['on', 'off', 'blocked', '', null] as $standardMemberGroupLgnMode) {
            $this->connection->executeStatement(
                'UPDATE tl_member_group SET lsShopLgnMode = ? WHERE id = ?',
                [$standardMemberGroupLgnMode ?? '', $standardMemberGroupId]
            );

            if ($standardMemberGroupLgnMode === null) {
                unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
            }

            $caseLabel = $standardMemberGroupLgnMode ?? 'keine Standard-Benutzergruppe';
            self::assertSame([], $this->normalizeMessages($this->preprocess($validRows)['arr_messages']), $caseLabel);
            self::assertSame($expectedInvalidMessages, $this->normalizeMessages($this->preprocess($invalidRows)['arr_messages']), $caseLabel);
        }

        $this->writeRows($validRows);
        self::assertSame('never', $this->fetchProduct('A')['lgnMode']);
        self::assertSame('override', $this->fetchVariant('A-1')['garanMode']);
    }

    /**
     * Spec 27, Abschnitt 10: Ein Feld `enableGll` hat keine Wirkung, weder in der Prüfung noch
     * beim Schreiben.
     */
    public function testEnableGllFieldHasNoEffect(): void
    {
        $rows = [
            $this->productRow('A', ['enableGll' => '1', 'lgnMode' => '']),
            $this->productRow('B', ['enableGll' => '', 'lgnMode' => 'always']),
        ];
        $preprocessingResult = $this->preprocess([
            ...$rows,
            $this->productRow('C', ['enableGll' => '1', 'enableGaran' => '1', 'lgnMode' => 'never'] + self::COMPLETE_GUARANTEE),
        ]);

        self::assertSame(
            $this->expectedMessages([[3, 'enableGaran', 'productGuaranteeWithLgnModeNever']]),
            $this->normalizeMessages($preprocessingResult['arr_messages'])
        );
        self::assertArrayNotHasKey('enableGll', $preprocessingResult['arr_preprocessedDataRows'][0]);

        $this->writeRows($rows);

        self::assertSame('inherit', $this->fetchProduct('A')['lgnMode']);
        self::assertSame('always', $this->fetchProduct('B')['lgnMode']);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
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
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
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
                'price' => '0',
                'priceType' => 'independent',
                'weightType' => 'independent',
            ],
            $values
        );
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
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
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
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
     * Vorprüfung wie in `apiResource_writeProductData()`; die Datensätze durchlaufen
     * `json_encode()` und `json_decode()` wie im Request. `JSON_PRESERVE_ZERO_FRACTION` hält
     * `36.0` als Gleitkommazahl.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return array{bln_hasError: bool, arr_messages: array<int, array<string, string>>, arr_preprocessedDataRows: list<array<string, mixed>>}
     */
    private function preprocess(array $rows): array
    {
        /*
         * Bestehende Preprocessor-Funktionen übergeben fehlende Felder an `trim()` und melden
         * dafür Deprecations ("Passing null to parameter"); die Funktionen der LGN- und
         * GARAN-Felder lesen alle Werte mit `??`.
         */
        return @ls_shop_productManagementApiPreprocessor::preprocess(
            json_decode(json_encode($rows, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION), true),
            self::CONTEXT
        );
    }

    /**
     * Ruft die Ressource `apiResource_writeProductData` über `processRequest()` auf. Nur für
     * abgelehnte Aufrufe: Ein erfolgreicher Aufruf schreibt am Ende den Zeitstempel der
     * letzten Datenänderung in die `localconfig` und läge damit außerhalb der Transaktion.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function callWriteProductData(array $rows): LegalGuaranteeProductManagementApiTestReceiver
    {
        $apiReceiver = new LegalGuaranteeProductManagementApiTestReceiver();
        $_POST['data'] = json_encode($rows, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        @ls_shop_apiController_productManagement::getInstance()->processRequest(self::CONTEXT, $apiReceiver);

        self::assertNotSame('success', $apiReceiver->status, 'Der Aufruf hätte abgelehnt werden müssen.');

        return $apiReceiver;
    }

    /**
     * Wie `apiResource_writeProductData()` ohne den abschließenden Zeitstempel: Vorprüfung,
     * Abbruch bei einem Verstoß, sonst `performImport()`.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function writeRows(array $rows): void
    {
        $preprocessingResult = $this->preprocess($rows);

        self::assertSame([], $this->normalizeMessages($preprocessingResult['arr_messages']));
        self::assertFalse($preprocessingResult['bln_hasError']);

        $importResult = (new ReflectionMethod(ls_shop_apiController_productManagement::class, 'performImport'))
            ->invoke(ls_shop_apiController_productManagement::getInstance(), $preprocessingResult['arr_preprocessedDataRows']);

        self::assertSame(['bln_hasError' => false, 'arr_messages' => []], $importResult);
    }

    /**
     * @param list<array{int, string, string}> $messageAssignments Datensatznummer, Feld, Schlüssel
     *
     * @return array<int, array<string, string>>
     */
    private function expectedMessages(array $messageAssignments, string $language = 'de'): array
    {
        $messages = [];

        foreach ($messageAssignments as [$rowNumber, $fieldName, $messageKey]) {
            $messages[$rowNumber][$fieldName] = $this->readLanguageText($language, $messageKey);
        }

        return $this->normalizeMessages($messages);
    }

    /**
     * @param array<int, array<string, string>>|mixed $messages
     *
     * @return array<int, array<string, string>>
     */
    private function normalizeMessages(mixed $messages): array
    {
        self::assertIsArray($messages);
        ksort($messages);

        foreach ($messages as &$fieldMessages) {
            ksort($fieldMessages);
        }

        return $messages;
    }

    /**
     * Text aus der Sprachdatei des Bundles, unabhängig vom Sprachzustand von Contao; ein leerer
     * Text ließe den Vergleich mit einer fehlenden Meldung bestehen.
     */
    private function readLanguageText(string $language, string $messageKey): string
    {
        $originalTlLang = $GLOBALS['TL_LANG'] ?? [];
        $GLOBALS['TL_LANG'] = [];

        require self::LANGUAGE_BASE_PATH . $language . '/default.php';

        $messageText = $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['productManagementApiErrors'][$messageKey] ?? '';
        $GLOBALS['TL_LANG'] = $originalTlLang;

        self::assertNotSame('', $messageText, $language . '.' . $messageKey);

        return $messageText;
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string|int|null>
     */
    private function toStoredValues(array $values): array
    {
        $storedValues = [];

        foreach ($values as $fieldName => $value) {
            $storedValues[$fieldName] = match ($fieldName) {
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

/**
 * Nimmt die Antwort von `ls_shop_apiController_productManagement` auf, wie der API-Empfänger.
 */
final class LegalGuaranteeProductManagementApiTestReceiver
{
    public ?string $status = null;
    public mixed $message = null;

    public function requireScope(array $scopes): void
    {
    }

    public function requireUser(array $userTypes): void
    {
    }

    public function error(): void
    {
        $this->status = 'error';
    }

    public function success(): void
    {
        $this->status = 'success';
    }

    public function set_message(mixed $message): void
    {
        $this->message = $message;
    }

    public function set_httpResponseCode(int $httpResponseCode): void
    {
    }

    public function set_data(mixed $data): void
    {
    }
}

final class LegalGuaranteeProductManagementApiTestHook
{
    /**
     * @param array<string, mixed> $groupSettings
     *
     * @return array<string, mixed>
     */
    public function adjustGroupSettings(array $groupSettings): array
    {
        $groupSettings['lsShopLgnMode'] = 'blocked';

        return $groupSettings;
    }
}
