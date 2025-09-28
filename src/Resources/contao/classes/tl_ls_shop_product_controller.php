<?php

namespace Merconis\Core;

use Contao\DataContainer;

class tl_ls_shop_product_controller
{
    public function syncPageMapOnSubmit(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }
        $obj = \Database::getInstance()->prepare("SELECT pages FROM tl_ls_shop_product WHERE id=?")
            ->limit(1)
            ->execute($dc->id);
        if ($obj->numRows) {
            ls_shop_generalHelper::syncProductPageMap($dc->id, $obj->pages);
        }
    }

    public function deleteFromPageMap(DataContainer $dc): void
    {
        if ($dc->id) {
            ls_shop_generalHelper::deleteProductFromPageMap($dc->id);
        }
    }
}


