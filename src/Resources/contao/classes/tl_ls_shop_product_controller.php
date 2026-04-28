<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\StringUtil;

class tl_ls_shop_product_controller extends \Backend
{
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    public function generateAlias($str_value, DataContainer $dc)
    {
        if (strpos($dc->field, '_') === false) {
            return $str_value;
        }

        $arrFieldParts = explode('_', $dc->field);
        $str_fieldLanguage = end($arrFieldParts);

        $str_titleToUseForAutoAlias =
            (
                    isset($dc->activeRecord->{'title_' . $str_fieldLanguage})
                &&  $dc->activeRecord->{'title_' . $str_fieldLanguage}
            )
            ?   $dc->activeRecord->{'title_' . $str_fieldLanguage}
            :   $dc->activeRecord->title;

        $bln_createAutoAlias = false;

        if ($str_value == '') {
            $bln_createAutoAlias = true;
            $str_value = StringUtil::generateAlias($str_titleToUseForAutoAlias);
        }

        $str_value = substr($str_value, 0, 128);

        $obj_dbres_recordForAlias = \Database::getInstance()->prepare("
            SELECT      `id`
            FROM        `tl_ls_shop_product`
            WHERE       `id` = ?
                OR      `" . $dc->field . "` = ?
        ")
        ->execute(
            $dc->id,
            $str_value
        );

        if ($obj_dbres_recordForAlias->numRows > 1) {
            if (!$bln_createAutoAlias) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $str_value));
            }

            $str_aliasSuffix = '-' . $dc->id;
            $str_value = substr($str_value, 0, 128 - strlen($str_aliasSuffix)) . $str_aliasSuffix;
        }

        return $str_value;
    }

    public function insertAttributeValueAllocationsInAllocationTable($str_value, DataContainer $dc)
    {
        ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable(
            json_decode($str_value),
            $dc->id,
            0
        );

        return $str_value;
    }

    public function createLabel($row, $label)
    {
        $this->loadLanguageFile('be_productSearch');
        $objProductOutput = new ls_shop_productOutput($row['id'], '', 'template_productBackendOverview_03');

        return '<div class="productViewBEList">' . $objProductOutput->parseOutput() . '</div>';
    }

    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (strlen(\Input::get('tid'))) {
            $this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
            $this->redirect($this->getReferer());
        }

        if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_product::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . \Image::getHtml($icon, $label) . '</a> ';
    }

    public function toggleVisibility($intId, $blnVisible)
    {
        if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_product::published', 'alexf')) {
            \System::log(
                'Not enough permissions to publish/unpublish product ID "' . $intId . '"',
                'tl_ls_shop_product toggleVisibility',
                TL_ERROR
            );
            $this->redirect('contao/main.php?act=error');
        }

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if (is_array($GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['published']['save_callback'] as $callback) {
                $this->import($callback[0]);
                $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $this);
            }
        }

        \Database::getInstance()->prepare(
            'UPDATE tl_ls_shop_product SET tstamp=' . time() . ", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?"
        )
            ->execute($intId);
    }

    public function convertPageSelection($value)
    {
        if (!is_array($value)) {
            $value = StringUtil::deserialize($value, true);
        }

        $arrPageSelection = [];

        foreach ($value as $selctedPageID) {
            $tmpMainLanguageID = ls_shop_languageHelper::getMainlanguagePageIDForPageID($selctedPageID);

            if (!$tmpMainLanguageID) {
                continue;
            }

            $arrPageSelection[] = (string) $tmpMainLanguageID;
        }

        return serialize($arrPageSelection);
    }

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


