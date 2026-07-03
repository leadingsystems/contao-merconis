<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class FilterRangeSliderStage001SchemaTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';
    private const LANGUAGE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/languages/';
    private const TEMPLATE_BASE_PATH = __DIR__ . '/../../src/Resources/contao/templates/';
    private const CONFIG_BASE_PATH = __DIR__ . '/../../src/Resources/config/';
    private const MIGRATION_BASE_PATH = __DIR__ . '/../../src/Migration/';

    public function testFilterFieldDcaContainsRangeSliderConfigurationAndSubpalettes(): void
    {
        $dcaContents = (string) file_get_contents(self::DCA_BASE_PATH . 'tl_ls_shop_filter_fields.php');

        self::assertStringContainsString("'__selector__' => array('dataSource', 'filterDisplayMode')", $dcaContents);
        self::assertStringContainsString("'filterDisplayMode_showMoreLess' => 'numItemsInReducedMode'", $dcaContents);
        self::assertStringContainsString("'filterDisplayMode_sliderRange' => 'rangeSliderDecimalSeparator,rangeSliderThousandSeparator,rangeSliderMinOptionCount,rangeSliderInitialOptionCount,rangeSliderInitialPosition'", $dcaContents);
        self::assertStringContainsString("'filterDisplayMode_sliderDirect' => 'rangeSliderDecimalSeparator,rangeSliderThousandSeparator,rangeSliderMinOptionCount,rangeSliderAutoOptionVisibility'", $dcaContents);
        self::assertStringContainsString("'filterDisplayMode' => [", $dcaContents);
        self::assertStringContainsString("'rangeSliderDecimalSeparator' => [", $dcaContents);
        self::assertStringContainsString("'rangeSliderThousandSeparator' => [", $dcaContents);
        self::assertStringContainsString("'rangeSliderMinOptionCount' => [", $dcaContents);
        self::assertStringContainsString("'rangeSliderInitialOptionCount' => [", $dcaContents);
        self::assertStringContainsString("'rangeSliderInitialPosition' => [", $dcaContents);
        self::assertStringContainsString("'rangeSliderAutoOptionVisibility' => [", $dcaContents);
        self::assertStringContainsString("'options_callback' => array('Merconis\\Core\\ls_shop_filter_fields', 'getFilterDisplayModeOptions')", $dcaContents);
        self::assertStringContainsString("'save_callback' => [", $dcaContents);
        self::assertStringContainsString("public static function determineFilterDisplayModeOptions(\$filterFormFieldType)", $dcaContents);
        self::assertStringContainsString("public static function normalizeFilterDisplayModeForFieldType(\$value, \$filterFormFieldType)", $dcaContents);
    }

    public function testLanguageFilesContainRangeSliderLabelsAndOptions(): void
    {
        $languageDe = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'de/tl_ls_shop_filter_fields.php');
        $languageEn = (string) file_get_contents(self::LANGUAGE_BASE_PATH . 'en/tl_ls_shop_filter_fields.php');

        self::assertStringContainsString("['filterDisplayMode']", $languageDe);
        self::assertStringContainsString("['rangeSliderDecimalSeparator']", $languageDe);
        self::assertStringContainsString("['rangeSliderAutoOptionVisibility']", $languageDe);
        self::assertStringContainsString("'sliderDirect' => 'Slider: Direktfilter'", $languageDe);
        self::assertStringContainsString("'middle' => 'Mittig'", $languageDe);

        self::assertStringContainsString("['filterDisplayMode']", $languageEn);
        self::assertStringContainsString("['rangeSliderDecimalSeparator']", $languageEn);
        self::assertStringContainsString("['rangeSliderAutoOptionVisibility']", $languageEn);
        self::assertStringContainsString("'sliderDirect' => 'Slider: direct filter'", $languageEn);
        self::assertStringContainsString("'middle' => 'Middle'", $languageEn);
    }

    public function testTemplatesExposeRangeSliderDataAttributes(): void
    {
        $templateFiles = [
            'template_formFilterField_standard.html5',
            'template_formFlexContentLIFilterField_standard.html5',
            'template_formFlexContentLDFilterField_standard.html5',
            'template_fastFilterField_options.html5',
        ];

        foreach ($templateFiles as $templateFile) {
            $templateContents = (string) file_get_contents(self::TEMPLATE_BASE_PATH . $templateFile);

            self::assertStringContainsString('data-filter-display-mode=', $templateContents);
            self::assertStringContainsString('data-range-slider-decimal-separator=', $templateContents);
            self::assertStringContainsString('data-range-slider-thousand-separator=', $templateContents);
            self::assertStringContainsString('data-range-slider-min-option-count=', $templateContents);
            self::assertStringContainsString('data-range-slider-initial-option-count=', $templateContents);
            self::assertStringContainsString('data-range-slider-initial-position=', $templateContents);
            self::assertStringContainsString('data-range-slider-auto-option-visibility=', $templateContents);
        }
    }

    public function testFastFilterServicesAndMigrationAreRegistered(): void
    {
        $serviceContents = (string) file_get_contents(self::CONFIG_BASE_PATH . 'services.yml');
        $migrationContents = (string) file_get_contents(self::MIGRATION_BASE_PATH . 'BackfillFilterDisplayModeMigration.php');
        $formServiceContents = (string) file_get_contents(__DIR__ . '/../../src/FastFilter/FastFilterFormService.php');

        self::assertStringContainsString(
            'merconis.migration.backfill_filter_display_mode_migration:',
            $serviceContents
        );
        self::assertStringContainsString(
            'LeadingSystems\\MerconisBundle\\Migration\\BackfillFilterDisplayModeMigration',
            $serviceContents
        );
        self::assertStringContainsString(
            'Existing filter fields were backfilled to the showMoreLess display mode.',
            $migrationContents
        );
        self::assertStringContainsString("'filterDisplayMode' => \$filterDisplayMode", $formServiceContents);
        self::assertStringContainsString("'rangeSliderInitialPosition' => (string) (\$field['rangeSliderInitialPosition'] ?? 'bottom')", $formServiceContents);
    }
}
