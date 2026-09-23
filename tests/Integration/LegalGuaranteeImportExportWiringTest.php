<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class LegalGuaranteeImportExportWiringTest extends TestCase
{
    private const SRC_BASE_PATH = __DIR__ . '/../../src/';

    public function testApiPreprocessorAndHelperExposeGuaranteeFields(): void
    {
        $preprocessorContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/classes/ls_shop_productManagementApiPreprocessor.php'
        );
        $helperContents = (string) file_get_contents(
            self::SRC_BASE_PATH . 'Resources/contao/helpers/ls_shop_productManagementApiHelper.php'
        );

        self::assertStringContainsString("'enableGll'", $preprocessorContents);
        self::assertStringContainsString("'garanOverride'", $preprocessorContents);
        self::assertStringContainsString("'guaranteeDurationYears'", $preprocessorContents);
        self::assertStringContainsString('applyLegalGuaranteeProductData', $helperContents);
        self::assertStringContainsString('applyLegalGuaranteeVariantData', $helperContents);
        self::assertStringContainsString('`enableGll` = ?', $helperContents);
        self::assertStringContainsString('`garanOverride` = ?', $helperContents);
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
        self::assertStringContainsString('`enableGll` = ?', $importContents);
        self::assertStringContainsString('`garanOverride` = ?', $importContents);
        self::assertStringContainsString('buildExportColumns($arr_productData)', $exportContents);
        self::assertStringContainsString(
            'buildExportColumns($arr_productData, $arr_variantData)',
            $exportContents
        );
    }
}
