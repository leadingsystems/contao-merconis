<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use Merconis\Core\ls_shop_export;
use Merconis\Core\ls_shop_productManagementApiPreprocessor;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Resources/contao/classes/ls_shop_export.php';
require_once __DIR__ . '/../../src/Resources/contao/classes/ls_shop_productManagementApiPreprocessor.php';

final class LegalGuaranteeImportExportWiringTest extends TestCase
{
    private const SRC_BASE_PATH = __DIR__ . '/../../src/';

    private mixed $originalTlConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = 3;
    }

    protected function tearDown(): void
    {
        if ($this->originalTlConfig === null) {
            unset($GLOBALS['TL_CONFIG']);
        } else {
            $GLOBALS['TL_CONFIG'] = $this->originalTlConfig;
        }

        parent::tearDown();
    }

    /**
     * Die Product Management API prüft in der Vorprüfung (Preprocessor-Funktionen der sechs
     * Felder) und schreibt die Spalten in den vier Schreibabfragen des Helpers; sie verwendet
     * weder die Backend-Regelklassen noch die Meldungen unter `legalGuaranteeImport`
     * (Implementierungsentscheidung 0034). Das Verhalten prüft
     * `Functional\LegalGuaranteeProductManagementApiTest`.
     */
    public function testApiPreprocessorAndHelperWireGuaranteeProcessing(): void
    {
        $preprocessorContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_productManagementApiPreprocessor.php'
        );
        $helperContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/helpers/ls_shop_productManagementApiHelper.php'
        );

        foreach (
            [
                'lgnMode' => 'preprocess_lgnMode',
                'enableGaran' => 'preprocess_enableGaran',
                'garanMode' => 'preprocess_garanMode',
                'guaranteeDurationMonths' => 'preprocess_guaranteeDurationMonths',
                'guaranteeBrand' => 'preprocess_guaranteeBrand',
                'guaranteeModelIdentifier' => 'preprocess_guaranteeModelIdentifier',
            ] as $fieldName => $preprocessorName
        ) {
            self::assertStringContainsString("'" . $fieldName . "' => [\n\t\t\t\t\t'preprocessor' => '" . $preprocessorName . "'", $preprocessorContents);
            self::assertSame(1, substr_count($preprocessorContents, 'protected static function ' . $preprocessorName . '('), $preprocessorName);
        }

        self::assertStringContainsString("System::loadLanguageFile('default');", $preprocessorContents);
        self::assertStringContainsString("['productManagementApiErrors']", $preprocessorContents);

        foreach (
            [
                'ProductGuaranteeConfigurationApplier',
                'ProductGuaranteeConfigurationValidator',
                'ProductGuaranteeValidationHelper',
                'StandardMemberGroupLgnMode',
                'legalGuaranteeImport',
            ] as $formerApiPathFragment
        ) {
            self::assertStringNotContainsString($formerApiPathFragment, $preprocessorContents);
            self::assertStringNotContainsString($formerApiPathFragment, $helperContents);
        }

        foreach (['applyLegalGuaranteeProductData', 'applyLegalGuaranteeVariantData', 'getLegalGuaranteeProductDataById', 'getLegalGuaranteeVariantDataByProductId', 'throwLegalGuaranteeWriteError'] as $formerHelperMethod) {
            self::assertStringNotContainsString($formerHelperMethod, $helperContents);
        }

        foreach (['lgnMode', 'enableGaran', 'garanMode'] as $columnName) {
            self::assertSame(2, substr_count($helperContents, '`' . $columnName . '` = ?'), $columnName);
        }

        foreach (['guaranteeDurationMonths', 'guaranteeBrand', 'guaranteeModelIdentifier'] as $columnName) {
            self::assertSame(4, substr_count($helperContents, '`' . $columnName . '` = ?'), $columnName);
        }

        self::assertFileDoesNotExist(self::SRC_BASE_PATH . 'LegalGuarantee/ProductData/ProductGuaranteeConfigurationApplier.php');
    }

    /**
     * Jede neue Preprocessor-Funktion beschreibt sich wie die bestehenden über ihren
     * Doc-Kommentar (`getPreprocessorDescriptions()`); die Hilfsfunktionen erscheinen dort nicht.
     */
    public function testApiPreprocessorFunctionsOfGuaranteeFieldsAreDescribed(): void
    {
        $originalMerconisGlobals = $GLOBALS['merconis_globals'] ?? null;
        unset($GLOBALS['merconis_globals']['cache']['arr_productManagementApiPreprocessorDescriptions']);

        try {
            $preprocessorDescriptions = ls_shop_productManagementApiPreprocessor::getPreprocessorDescriptions();
        } finally {
            if ($originalMerconisGlobals === null) {
                unset($GLOBALS['merconis_globals']);
            } else {
                $GLOBALS['merconis_globals'] = $originalMerconisGlobals;
            }
        }

        foreach (['preprocess_lgnMode', 'preprocess_enableGaran', 'preprocess_garanMode', 'preprocess_guaranteeDurationMonths', 'preprocess_guaranteeBrand', 'preprocess_guaranteeModelIdentifier'] as $preprocessorName) {
            self::assertArrayHasKey($preprocessorName, $preprocessorDescriptions);

            foreach (['Expected input: ', 'Accepted input: ', 'Normalization: '] as $descriptionPart) {
                self::assertSame(1, substr_count($preprocessorDescriptions[$preprocessorName], $descriptionPart), $preprocessorName . ' ' . $descriptionPart);
            }
        }

        foreach (['getLegalGuaranteeStringValue', 'throwIfRequiredGuaranteeValueIsEmpty', 'getLegalGuaranteeMessage'] as $helperName) {
            self::assertArrayNotHasKey($helperName, $preprocessorDescriptions);
        }
    }

    /**
     * Der Importer prüft in der Dateivalidierung und schreibt die Spalten in den vier
     * Schreibabfragen; er verwendet weder die Backend-Regelklassen noch die Meldungen unter
     * `legalGuaranteeImport` (Implementierungsentscheidung 0034). Das Verhalten prüft
     * `Functional\LegalGuaranteeImportTest`.
     */
    public function testImportAndExportWireGuaranteeProcessing(): void
    {
        $importContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_importController.php'
        );
        $exportContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_export.php'
        );

        foreach (
            [
                'ProductGuaranteeConfigurationApplier',
                'ProductGuaranteeConfigurationValidator',
                'ProductGuaranteeValidationHelper',
                'StandardMemberGroupLgnMode',
                'legalGuaranteeImport',
            ] as $formerImportPathFragment
        ) {
            self::assertStringNotContainsString($formerImportPathFragment, $importContents);
        }

        foreach (['lgnMode', 'enableGaran', 'garanMode'] as $columnName) {
            self::assertSame(2, substr_count($importContents, '`' . $columnName . '` = ?'), $columnName);
        }

        foreach (['guaranteeDurationMonths', 'guaranteeBrand', 'guaranteeModelIdentifier'] as $columnName) {
            self::assertSame(4, substr_count($importContents, '`' . $columnName . '` = ?'), $columnName);
        }

        self::assertStringContainsString("'product_lgnModeAndDeletion'", $importContents);
        self::assertStringContainsString('$arr_productData[$str_fieldName] ?? null', $exportContents);
        self::assertStringContainsString('$arr_variantData[$str_fieldName] ?? null', $exportContents);
    }

    /**
     * Import, Product Management API und Validator lesen und schreiben `enableGll` nicht mehr
     * (Spec 27, Abschnitt 10); die Spalte hat keine Wirkung.
     */
    public function testImportAndProductManagementApiDoNotUseEnableGll(): void
    {
        foreach (
            [
                'Resources/contao/classes/ls_shop_importController.php',
                'Resources/contao/classes/ls_shop_productManagementApiPreprocessor.php',
                'Resources/contao/helpers/ls_shop_productManagementApiHelper.php',
                'LegalGuarantee/ProductData/ProductGuaranteeConfigurationValidator.php',
                'LegalGuarantee/ProductData/ProductGuaranteeValidationHelper.php',
                'Resources/contao/classes/ls_shop_export.php',
            ] as $relativePath
        ) {
            self::assertStringNotContainsString(
                'enableGll',
                (string) file_get_contents(self::SRC_BASE_PATH . $relativePath),
                $relativePath
            );
        }
    }

    /**
     * Der Export liefert die gespeicherten Werte von `lgnMode`, `enableGaran`, `garanMode`,
     * Dauer, Marke und Modell über die bestehende Spaltenermittlung, nicht die effektiven Werte
     * (Spec 27, Abschnitt 10; ADR 0011). Produktfelder heißen `p_<Feld>`, Variantenfelder
     * `v_<Feld>`, gemeinsame Felder behalten ihren Namen. Der Importer schreibt diese Werte
     * unverändert zurück (`Functional\LegalGuaranteeImportTest`). Eine physisch noch vorhandene
     * Spalte `enableGll` erscheint im Export.
     */
    public function testExportDeliversStoredGuaranteeValues(): void
    {
        $storedProductValues = [
            1 => ['lgnMode' => 'inherit', 'enableGll' => '', 'enableGaran' => '1', 'guaranteeDurationMonths' => 36, 'guaranteeBrand' => 'Söhne & <Partner>', 'guaranteeModelIdentifier' => 'X/100'],
            2 => ['lgnMode' => 'always', 'enableGll' => '', 'enableGaran' => '', 'guaranteeDurationMonths' => null, 'guaranteeBrand' => '', 'guaranteeModelIdentifier' => ''],
            3 => ['lgnMode' => 'never', 'enableGll' => '1', 'enableGaran' => '', 'guaranteeDurationMonths' => 48, 'guaranteeBrand' => 'ACME', 'guaranteeModelIdentifier' => 'M-1'],
        ];
        $productsById = [];

        foreach ($storedProductValues as $productId => $productValues) {
            $productsById[$productId] = [
                'id' => $productId,
                'pid' => 0,
                'title' => 'Produkt ' . $productId,
                'lsShopProductCode' => 'ART-' . $productId,
                ...$productValues,
                'numVariants' => $productId === 1 ? 2 : 0,
            ];
        }

        $exportRows = $this->combineProductAndVariantData([
            'arr_products' => $productsById,
            'arr_variants' => [
                1 => [
                    11 => [
                        'id' => 11,
                        'pid' => 1,
                        'title' => 'Variante 1',
                        'lsShopVariantCode' => 'ART-1-V1',
                        'garanMode' => 'override',
                        'guaranteeDurationMonths' => 60,
                        'guaranteeBrand' => 'Variantenmarke',
                        'guaranteeModelIdentifier' => 'V-1',
                    ],
                    12 => [
                        'id' => 12,
                        'pid' => 1,
                        'title' => 'Variante 2',
                        'lsShopVariantCode' => 'ART-1-V2',
                        'garanMode' => 'disabled',
                        'guaranteeDurationMonths' => 30,
                        'guaranteeBrand' => 'Gespeichert',
                        'guaranteeModelIdentifier' => 'G-1',
                    ],
                ],
            ],
        ]);
        $exportedGuaranteeValues = array_map(
            static fn (array $exportRow): array => [
                'code' => $exportRow['p_code'] ?? $exportRow['v_code'],
                'p_lgnMode' => $exportRow['p_lgnMode'],
                'p_enableGaran' => $exportRow['p_enableGaran'],
                'v_garanMode' => $exportRow['v_garanMode'],
                'guaranteeDurationMonths' => $exportRow['guaranteeDurationMonths'],
                'guaranteeBrand' => $exportRow['guaranteeBrand'],
                'guaranteeModelIdentifier' => $exportRow['guaranteeModelIdentifier'],
            ],
            $exportRows
        );

        self::assertSame(
            [
                ['code' => 'ART-1', 'p_lgnMode' => 'inherit', 'p_enableGaran' => '1', 'v_garanMode' => null, 'guaranteeDurationMonths' => 36, 'guaranteeBrand' => 'Söhne & <Partner>', 'guaranteeModelIdentifier' => 'X/100'],
                ['code' => 'ART-1-V1', 'p_lgnMode' => null, 'p_enableGaran' => null, 'v_garanMode' => 'override', 'guaranteeDurationMonths' => 60, 'guaranteeBrand' => 'Variantenmarke', 'guaranteeModelIdentifier' => 'V-1'],
                ['code' => 'ART-1-V2', 'p_lgnMode' => null, 'p_enableGaran' => null, 'v_garanMode' => 'disabled', 'guaranteeDurationMonths' => 30, 'guaranteeBrand' => 'Gespeichert', 'guaranteeModelIdentifier' => 'G-1'],
                ['code' => 'ART-2', 'p_lgnMode' => 'always', 'p_enableGaran' => '', 'v_garanMode' => null, 'guaranteeDurationMonths' => null, 'guaranteeBrand' => '', 'guaranteeModelIdentifier' => ''],
                ['code' => 'ART-3', 'p_lgnMode' => 'never', 'p_enableGaran' => '', 'v_garanMode' => null, 'guaranteeDurationMonths' => 48, 'guaranteeBrand' => 'ACME', 'guaranteeModelIdentifier' => 'M-1'],
            ],
            $exportedGuaranteeValues
        );
        self::assertSame(['', '1'], [$exportRows[0]['p_enableGll'], $exportRows[4]['p_enableGll']]);
    }

    /**
     * @param array<string, mixed> $productAndVariantData
     *
     * @return list<array<string, mixed>>
     */
    private function combineProductAndVariantData(array $productAndVariantData): array
    {
        $reflectionClass = new \ReflectionClass(ls_shop_export::class);
        $export = $reflectionClass->newInstanceWithoutConstructor();
        $method = $reflectionClass->getMethod('combineProductAndVariantData');

        /*
         * `combineProductAndVariantData()` übergibt Funktionsergebnisse per Referenz; PHP meldet
         * das als Notice, die zum bestehenden Exportcode gehört.
         */
        return @$method->invoke($export, $productAndVariantData);
    }
}
