<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class WithdrawalSnapshotReferenceFieldsTest extends TestCase
{
    private const DCA_BASE_PATH = __DIR__ . '/../../src/Resources/contao/dca/';

    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_items']);
    }

    public function testSnapshotConfiguratorReferenceNumberFieldExistsInDca(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_withdrawal_items.php';

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_items'];

        self::assertArrayHasKey('snapshotConfiguratorReferenceNumber', $tableConfig['fields']);
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotConfiguratorReferenceNumber']['sql']
        );
    }

    public function testSnapshotCustomizerReferenceNumberFieldExistsInDca(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_withdrawal_items.php';

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_items'];

        self::assertArrayHasKey('snapshotCustomizerReferenceNumber', $tableConfig['fields']);
        self::assertSame(
            "varchar(255) NOT NULL default ''",
            $tableConfig['fields']['snapshotCustomizerReferenceNumber']['sql']
        );
    }

    public function testSnapshotSalesUnitSizeFieldExistsInDca(): void
    {
        require self::DCA_BASE_PATH . 'tl_ls_shop_withdrawal_items.php';

        $tableConfig = $GLOBALS['TL_DCA']['tl_ls_shop_withdrawal_items'];

        self::assertArrayHasKey('snapshotSalesUnitSize', $tableConfig['fields']);
        self::assertSame(
            "int(10) unsigned NOT NULL default '0'",
            $tableConfig['fields']['snapshotSalesUnitSize']['sql']
        );
    }

    public function testWithdrawalItemsInsertStatementContainsSnapshotReferenceFields(): void
    {
        $moduleContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/frontendModules/ModuleWithdrawal.php'
        );

        self::assertStringContainsString(
            '`snapshotConfiguratorReferenceNumber`',
            $moduleContents
        );
        self::assertStringContainsString(
            '`snapshotCustomizerReferenceNumber`',
            $moduleContents
        );
        self::assertStringContainsString(
            "childSnapshot['snapshotConfiguratorReferenceNumber']",
            $moduleContents
        );
        self::assertStringContainsString(
            "childSnapshot['snapshotCustomizerReferenceNumber']",
            $moduleContents
        );
        self::assertStringContainsString(
            '`snapshotSalesUnitSize`',
            $moduleContents
        );
        self::assertStringContainsString(
            "childSnapshot['snapshotSalesUnitSize']",
            $moduleContents
        );
    }

    public function testProcessorContainsReferenceNumberResolutionMethods(): void
    {
        $processorContents = (string) file_get_contents(
            __DIR__ . '/../../src/Helpers/WithdrawalScreenBProcessor.php'
        );

        self::assertStringContainsString('resolveConfiguratorReferenceNumber', $processorContents);
        self::assertStringContainsString('resolveCustomizerReferenceNumber', $processorContents);
        self::assertStringContainsString('configurator_hasValue', $processorContents);
        self::assertStringContainsString('customizer_hasCustomization', $processorContents);
        self::assertStringContainsString(
            "strtoupper(substr(md5(",
            $processorContents
        );
    }

    public function testChildSnapshotArrayContainsReferenceNumberKeys(): void
    {
        $processorContents = (string) file_get_contents(
            __DIR__ . '/../../src/Helpers/WithdrawalScreenBProcessor.php'
        );

        self::assertStringContainsString(
            "'snapshotConfiguratorReferenceNumber' => \$this->resolveConfiguratorReferenceNumber(",
            $processorContents
        );
        self::assertStringContainsString(
            "'snapshotCustomizerReferenceNumber' => \$this->resolveCustomizerReferenceNumber(",
            $processorContents
        );
        self::assertStringContainsString(
            "'snapshotSalesUnitSize' => \$salesUnitSize",
            $processorContents
        );
    }

    public function testScreenBTemplateActivatesNumberStepperForSalesUnits(): void
    {
        $templateContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/templates/mod_ls_shop_withdrawal_screenB.html5'
        );

        self::assertStringContainsString('useNumberStepper', $templateContents);
        self::assertStringContainsString("['minimumQuantity']", $templateContents);
    }

    public function testScreenBInitializesWithdrawnQuantitiesFromDisplayQuantities(): void
    {
        $moduleContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/frontendModules/ModuleWithdrawal.php'
        );

        self::assertStringContainsString(
            '$withdrawnQuantities[$orderItemId] = (float) $processor->getOrderedDisplayQuantity($orderItem);',
            $moduleContents
        );
    }
}
