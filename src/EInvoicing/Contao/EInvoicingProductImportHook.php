<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\Database;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;

final class EInvoicingProductImportHook
{
    /**
     * @param array<string, mixed> $row
     */
    public function afterInsertingProductData(int $productId, array $row = []): void
    {
        $this->persistUnitCode($productId, $row);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function afterUpdatingProductData(int $productId, array $row = []): void
    {
        $this->persistUnitCode($productId, $row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function persistUnitCode(int $productId, array $row): void
    {
        if ($productId <= 0 || !array_key_exists(EInvoicingSnapshotFields::PRODUCT_UNIT_CODE_FIELD, $row)) {
            return;
        }

        $unitCode = EInvoicingCodeLists::normalizeCode($row[EInvoicingSnapshotFields::PRODUCT_UNIT_CODE_FIELD]);

        if ($unitCode === null) {
            return;
        }

        if (!EInvoicingCodeLists::hasUnitCode($unitCode)) {
            throw new \RuntimeException(
                'Please provide a valid EN 16931 unit code from the official UN/ECE Rec. 20 code list.'
            );
        }

        Database::getInstance()
            ->prepare(
                'UPDATE `tl_ls_shop_product` SET `'
                . EInvoicingSnapshotFields::PRODUCT_UNIT_CODE_FIELD
                . '` = ? WHERE `id` = ?'
            )
            ->execute($unitCode, $productId);
    }
}
