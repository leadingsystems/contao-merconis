<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationApplier;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use Merconis\Core\ls_shop_export;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Resources/contao/classes/ls_shop_export.php';

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

    public function testApiPreprocessorAndHelperExposeGuaranteeFields(): void
    {
        $preprocessorContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_productManagementApiPreprocessor.php'
        );
        $helperContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/helpers/ls_shop_productManagementApiHelper.php'
        );

        self::assertStringContainsString("'lgnMode' => [\n\t\t\t\t\t'preprocessor' => 'preprocess_lgnMode'", $preprocessorContents);
        self::assertStringContainsString("'garanMode'", $preprocessorContents);
        self::assertStringContainsString("'guaranteeDurationMonths'", $preprocessorContents);
        self::assertStringContainsString('applyLegalGuaranteeProductData', $helperContents);
        self::assertStringContainsString('applyLegalGuaranteeVariantData', $helperContents);
        self::assertStringContainsString('formatWriteErrorMessage', $helperContents);
        self::assertSame(2, substr_count($helperContents, '`lgnMode` = ?'));
        self::assertSame(2, substr_count($helperContents, "\$arr_preprocessedDataRow['lgnMode']"));
        self::assertStringContainsString("SELECT\t\t`lgnMode`,", $helperContents);
        self::assertStringContainsString('`garanMode` = ?', $helperContents);
    }

    public function testImportAndExportWireGuaranteeProcessing(): void
    {
        $importContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_importController.php'
        );
        $exportContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_export.php'
        );

        self::assertStringContainsString('logLegalGuaranteeImportWarnings', $importContents);
        self::assertStringContainsString('hasBlockingWriteMessages', $importContents);
        self::assertSame(2, substr_count($importContents, '`lgnMode` = ?'));
        self::assertSame(2, substr_count($importContents, "\$row['lgnMode']"));
        self::assertStringContainsString('`garanMode` = ?', $importContents);
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
                'LegalGuarantee/ProductData/ProductGuaranteeConfigurationApplier.php',
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
     * Der Export liefert den gespeicherten Wert `lgnMode` über die bestehende Spaltenermittlung
     * (Produktfeld, daher `p_lgnMode`); der Reimport dieses Werts in die Spalte `lgnMode` ergibt
     * denselben Wert. Eine physisch noch vorhandene Spalte `enableGll` erscheint im Export und
     * hat beim Reimport keine Wirkung.
     */
    public function testLgnModeIsExportedAndReimportedWithoutLoss(): void
    {
        $productsById = [];

        foreach (['inherit', 'always', 'never'] as $productIndex => $storedLgnMode) {
            $productId = $productIndex + 1;
            $productsById[$productId] = [
                'id' => $productId,
                'pid' => 0,
                'title' => 'Produkt ' . $productId,
                'lsShopProductCode' => 'ART-' . $productId,
                'lgnMode' => $storedLgnMode,
                'enableGll' => $storedLgnMode === 'never' ? '1' : '',
                'enableGaran' => '',
                'guaranteeDurationMonths' => null,
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
                'numVariants' => $productId === 1 ? 1 : 0,
            ];
        }

        $exportRows = $this->combineProductAndVariantData([
            'arr_products' => $productsById,
            'arr_variants' => [
                1 => [
                    11 => [
                        'id' => 11,
                        'pid' => 1,
                        'title' => 'Variante',
                        'lsShopVariantCode' => 'ART-1-V',
                        'garanMode' => 'inherit',
                        'guaranteeDurationMonths' => null,
                        'guaranteeBrand' => '',
                        'guaranteeModelIdentifier' => '',
                    ],
                ],
            ],
        ]);
        $productExportRows = array_values(array_filter(
            $exportRows,
            static fn (array $exportRow): bool => (int) $exportRow['pid'] === 0
        ));
        $applier = new ProductGuaranteeConfigurationApplier(
            new ProductGuaranteeConfigurationValidator(
                standardMemberGroupLgnMode: new StandardMemberGroupLgnMode(
                    static fn (int $groupId): array => ['id' => $groupId, 'lsShopLgnMode' => 'on']
                )
            )
        );

        self::assertSame(['inherit', 'always', 'never'], array_column($productExportRows, 'p_lgnMode'));
        self::assertArrayHasKey('p_enableGll', $productExportRows[0]);
        self::assertNull($exportRows[1]['p_lgnMode']);

        foreach ($productExportRows as $productExportRow) {
            $reimportResult = $applier->applyToProductRow([
                'productcode' => $productExportRow['p_code'],
                'producer' => '',
                'lgnMode' => $productExportRow['p_lgnMode'],
                'enableGll' => $productExportRow['p_enableGll'] === '1' ? '' : '1',
                'enableGaran' => $productExportRow['p_enableGaran'],
                'guaranteeDurationMonths' => $productExportRow['guaranteeDurationMonths'],
                'guaranteeBrand' => $productExportRow['guaranteeBrand'],
                'guaranteeModelIdentifier' => $productExportRow['guaranteeModelIdentifier'],
            ]);

            self::assertSame([], $reimportResult['messages'], $productExportRow['p_code']);
            self::assertSame($productExportRow['p_lgnMode'], $reimportResult['row']['lgnMode'], $productExportRow['p_code']);
        }
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
