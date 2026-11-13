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
        self::assertStringContainsString("'garanMode'", $preprocessorContents);
        self::assertStringContainsString("'guaranteeDurationMonths'", $preprocessorContents);
        self::assertStringContainsString('applyLegalGuaranteeProductData', $helperContents);
        self::assertStringContainsString('applyLegalGuaranteeVariantData', $helperContents);
        self::assertStringContainsString('formatWriteErrorMessage', $helperContents);
        self::assertStringContainsString('`enableGll` = ?', $helperContents);
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
        self::assertStringContainsString('`enableGll` = ?', $importContents);
        self::assertStringContainsString('`garanMode` = ?', $importContents);
        self::assertStringContainsString('garan_override_gll_required', $importContents);
        self::assertStringContainsString('$arr_productData[$str_fieldName] ?? null', $exportContents);
        self::assertStringContainsString('$arr_variantData[$str_fieldName] ?? null', $exportContents);
    }
}
