<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class MinimumOrderQuantityBackwardCompatibilityCoverageTest extends TestCase
{
    public function testCheckoutSnapshotDoesNotPersistMinimumOrderQuantity(): void
    {
        $checkoutContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/classes/ls_shop_checkout.php'
        );
        $orderItemsDcaContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_orders_items.php'
        );

        self::assertStringNotContainsString('minimumOrderQuantity', $checkoutContents);
        self::assertStringNotContainsString('minimumOrderQuantity', $orderItemsDcaContents);

        self::assertStringContainsString('`salesUnitSize` = ?,', $checkoutContents);
        self::assertStringContainsString('`displayQuantity` = ?,', $checkoutContents);
        self::assertStringContainsString('`displayQuantityUnit` = ?,', $checkoutContents);
        self::assertStringContainsString("'salesUnitSize' => [", $orderItemsDcaContents);
        self::assertStringContainsString("'displayQuantity' => [", $orderItemsDcaContents);
        self::assertStringContainsString("'displayQuantityUnit' => [", $orderItemsDcaContents);
    }

    public function testWithdrawalRemainsBoundToSnapshotAndDisplayStepData(): void
    {
        $moduleContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/frontendModules/ModuleWithdrawal.php'
        );
        $processorContents = (string) file_get_contents(
            __DIR__ . '/../../src/Helpers/WithdrawalScreenBProcessor.php'
        );
        $withdrawalItemsDcaContents = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_withdrawal_items.php'
        );

        self::assertStringNotContainsString('minimumOrderQuantity', $moduleContents);
        self::assertStringNotContainsString('minimumOrderQuantity', $processorContents);
        self::assertStringNotContainsString('minimumOrderQuantity', $withdrawalItemsDcaContents);

        self::assertStringContainsString(
            '$processor->getDisplayMinimumQuantity($salesUnitSize, $quantityDecimals)',
            $moduleContents
        );
        self::assertStringContainsString(
            '$processor->getOrderedDisplayQuantity($orderItem)',
            $moduleContents
        );
        self::assertStringContainsString("'snapshotSalesUnitSize' => \$salesUnitSize", $processorContents);
        self::assertStringContainsString(
            "'snapshotQuantityDecimals' => max(0, (int) (\$orderItem['quantityDecimals'] ?? 0))",
            $processorContents
        );
        self::assertStringContainsString(
            'public function getDisplayMinimumQuantity(int $salesUnitSize, int $quantityDecimals): float',
            $processorContents
        );
    }
}
