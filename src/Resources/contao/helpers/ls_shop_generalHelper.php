<?php

namespace Merconis\Core;

use LeadingSystems\Helpers\FlexWidget;

use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;
use function LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray;
use function LeadingSystems\Helpers\createMultidimensionalArray;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_generalHelper
{
    /*
     * This function takes the attribute value allocations as an array (possibly serialized)
     * and writes them into the allocation table
     */
    public static function insertAttributeValueAllocationsInAllocationTable($arr_allocations, $int_parentId = 0, $bln_parentIsVariant = 0)
    {
        if (!$int_parentId) {
            return;
        }

        /*
         * First, delete all entries related to the current product or variant
         */
        \Database::getInstance()
            ->prepare("
			DELETE FROM	`tl_ls_shop_attribute_allocation`
			WHERE		`pid` = ?
				AND		`parentIsVariant` = ?
		")
            ->execute(
                $int_parentId,
                ($bln_parentIsVariant ? '1' : '0')
            );


        $arr_allocations = is_array($arr_allocations) ? $arr_allocations : json_decode($arr_allocations, true);

        $int_sortingKey = 0;
        foreach ($arr_allocations as $arr_allocation) {
            if (!isset($arr_allocation[0]) || !$arr_allocation[0] || !isset($arr_allocation[1]) || !$arr_allocation[1]) {
                /*
                 * Skip attribute value allocations if either the attribute or the value
                 * is not defined (or both, of course)
                 */
                continue;
            }

            \Database::getInstance()
                ->prepare("
				INSERT INTO `tl_ls_shop_attribute_allocation`
				SET			`pid` = ?,
							`parentIsVariant` = ?,
							`attributeID` = ?,
							`attributeValueID` = ?,
							`sorting` = ?
			")
                ->execute(
                    $int_parentId,
                    ($bln_parentIsVariant ? '1' : '0'),
                    $arr_allocation[0],
                    $arr_allocation[1],
                    $int_sortingKey
                );

            $int_sortingKey++;
        }
    }

    public static function changeStockDirectly($str_productOrVariant = 'product', $int_id = 0, $str_stockChange = null)
    {
        if (
            $str_stockChange === null
            || $str_stockChange === ''
            || $str_stockChange === false
            || preg_match('/[^0-9+-.]/', $str_stockChange)
            || !$int_id
            || !in_array($str_productOrVariant, array('product', 'variant'))
        ) {
            return;
        }

        $str_tableName = $str_productOrVariant == 'product' ? 'tl_ls_shop_product' : 'tl_ls_shop_variant';
        $str_fieldName = $str_productOrVariant == 'product' ? 'lsShopProductStock' : 'lsShopVariantStock';

        $str_setStatement = strpos($str_stockChange, '-') !== false || strpos($str_stockChange, '+') !== false ? "`" . $str_fieldName . "` = `" . $str_fieldName . "` + ?" : "`" . $str_fieldName . "` =  + ?";

        \Database::getInstance()
            ->prepare("
			UPDATE		`" . $str_tableName . "`
			SET			" . $str_setStatement . "
			WHERE		`id` = ?
		")
            ->limit(1)
            ->execute(
                $str_stockChange,
                $int_id
            );
    }

    public static function getSQLFieldAttributes($var_table = null, $str_fieldName = null)
    {
        if (!$var_table || !$str_fieldName) {
            return null;
        }

        if (is_array($var_table)) {
            $arr_fields = $var_table;
        } else {
            if (!\Database::getInstance()->tableExists($var_table)) {
                return array();
            }

            $obj_dbres_fields = \Database::getInstance()
                ->prepare("
				SHOW COLUMNS FROM	`" . $var_table . "`
			")
                ->execute();

            if (!$obj_dbres_fields->numRows) {
                return null;
            }

            $arr_fields = $obj_dbres_fields->fetchAllAssoc();
            $arr_tmp_fields = array();
            foreach ($arr_fields as $arr_fieldInfo) {
                $arr_tmp_fields[$arr_fieldInfo['Field']] = $arr_fieldInfo;
            }
            $arr_fields = $arr_tmp_fields;
        }

        if (!isset($arr_fields[$str_fieldName])) {
            return null;
        }
        return $arr_fields[$str_fieldName]['Type'] . ' ' . ($arr_fields[$str_fieldName]['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ($arr_fields[$str_fieldName]['Default'] !== false && $arr_fields[$str_fieldName]['Default'] !== null ? " default '" . $arr_fields[$str_fieldName]['Default'] . "'" : "");
    }

    public static function checkIfAttributeAndValueBelongTogether($int_attributeID = 0, $int_attributeValueID = 0)
    {
        if (!$int_attributeID || !$int_attributeValueID) {
            return false;
        }

        if (isset($GLOBALS['merconis_globals']['cache']['checkIfAttributeAndValueBelongTogether'][$int_attributeID . '_' . $int_attributeValueID])) {
            return $GLOBALS['merconis_globals']['cache']['checkIfAttributeAndValueBelongTogether'][$int_attributeID . '_' . $int_attributeValueID];
        }

        $obj_dbres_data = \Database::getInstance()->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_attribute_values`
				WHERE		`id` = ?
					AND		`pid` = ?
			")
            ->execute($int_attributeValueID, $int_attributeID);

        /*
         * If we have a match, attribute and value obviously belong together
         */
        if ($obj_dbres_data->numRows) {
            $GLOBALS['merconis_globals']['cache']['checkIfAttributeAndValueBelongTogether'][$int_attributeID . '_' . $int_attributeValueID] = true;
        } else {
            $GLOBALS['merconis_globals']['cache']['checkIfAttributeAndValueBelongTogether'][$int_attributeID . '_' . $int_attributeValueID] = false;
        }

        return $GLOBALS['merconis_globals']['cache']['checkIfAttributeAndValueBelongTogether'][$int_attributeID . '_' . $int_attributeValueID];
    }

    public static function getProductIdForCode($str_code)
    {
        $int_productId = 0;

        $obj_dbres_product = \Database::getInstance()
            ->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_product`
				WHERE		`lsShopProductCode` = ?
			")
            ->execute($str_code);

        if ($obj_dbres_product->numRows) {
            $int_productId = $obj_dbres_product->first()->id;
        }

        return $int_productId;
    }

    public static function getVariantIdForCode($str_code)
    {
        $int_variantId = 0;

        $obj_dbres_variant = \Database::getInstance()
            ->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_variant`
				WHERE		`lsShopVariantCode` = ?
			")
            ->execute($str_code);

        if ($obj_dbres_variant->numRows) {
            $int_variantId = $obj_dbres_variant->first()->id;
        }

        return $int_variantId;
    }

    /*
     * This function returns all images that could be found in the default product image folder
     * for a given product or variant code.
     *
     * If the special value "__ALL_IMAGES__" is given instead of a product or variant code,
     * all images found in the folder will be returned, regardless of whether or not there's
     * a product to which the image belongs.
     */
    public static function getImagesFromStandardFolder($str_productOrVariantCode, $bln_addStandardImageFolderPath = true)
    {
        $arr_productImages = array();
        if (!$str_productOrVariantCode) {
            return $arr_productImages;
        }

        $str_pathToStandardProductImageFolder = ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImageFolder']);

        if (!file_exists(TL_ROOT . '/' . $str_pathToStandardProductImageFolder)) {
            error_log("the standard folder for product images possibly doesn't exist.");
            return $arr_productImages;
        }

        $arr_tmpImageFiles = scandir(TL_ROOT . '/' . $str_pathToStandardProductImageFolder);

        if (is_array($arr_tmpImageFiles)) {
            foreach ($arr_tmpImageFiles as $str_imageFile) {
                if (
                    $str_imageFile == '.'
                    || $str_imageFile == '..'
                ) {
                    continue;
                }

                if ($str_productOrVariantCode !== '__ALL_IMAGES__') {
                    // Determine the pure filename without suffix
                    $arr_tmpFilenameExploded = explode('.', $str_imageFile);
                    $str_tmpFilenameSuffix = '.' . $arr_tmpFilenameExploded[count($arr_tmpFilenameExploded) - 1];
                    $str_filenameWithoutSuffix = basename($str_imageFile, $str_tmpFilenameSuffix);

                    if (
                    !preg_match('/^' . preg_quote($str_productOrVariantCode, '/') . '(' . preg_quote($GLOBALS['TL_CONFIG']['ls_shop_standardProductImageDelimiter'], '/') . '|$)/', $str_filenameWithoutSuffix)
                    ) {
                        continue;
                    }
                }

                $arr_productImages[] = ($bln_addStandardImageFolderPath ? $str_pathToStandardProductImageFolder . '/' : '') . $str_imageFile;
            }
        }
        return $arr_productImages;
    }

    public static function getProductImageByPath($str_imagePath, $int_width = 20, $int_height = 20, $str_mode = '', $bln_force = false)
    {
        return \Image::get($str_imagePath, $int_width, $int_height, $str_mode, null, $bln_force);
    }

    /*
     * This function splits a combined productVariantId in the productId and variantId and returns them in an array.
     * If a configuratorHash is suffixed, it is detected as well and also returned in the array
     */
    public static function splitProductVariantID($str_productVariantId = '')
    {
        $arr_return = array(
            'productID' => false,
            'variantID' => false,
            'configuratorHash' => ''
        );

        $int_posUnderscore = strpos($str_productVariantId, '_');
        $int_posHyphen = strpos($str_productVariantId, '-');

        if ($int_posUnderscore !== false) {
            $arr_return['configuratorHash'] = substr($str_productVariantId, $int_posUnderscore + 1);
        } else {
            $int_posUnderscore = strlen($str_productVariantId);
        }

        if ($int_posHyphen !== false) {
            $arr_return['productID'] = substr($str_productVariantId, 0, $int_posHyphen);
            $arr_return['variantID'] = substr($str_productVariantId, $int_posHyphen + 1, $int_posUnderscore - ($int_posHyphen + 1));
        } else {
            $arr_return['productID'] = $str_productVariantId;
            $arr_return['variantID'] = 0;
        }

        return $arr_return;
    }

    public static function getProductIdForAlias($str_alias, $bln_useFallbackLanguage = false)
    {
        if (!$str_alias) {
            return null;
        }

        /** @var \PageModel $objPage */
        global $objPage;

        $str_aliasFieldName = 'alias' . (!$bln_useFallbackLanguage && is_object($objPage) && $objPage->language ? '_' . $objPage->language : '');

        $obj_dbres_product = \Database::getInstance()->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_product`
			WHERE		`" . $str_aliasFieldName . "` = ?
				AND		`published` = ?
		")
            ->limit(1)
            ->execute($str_alias, 1);

        if ($obj_dbres_product->numRows <= 0) {
            return null;
        }

        return $obj_dbres_product->first()->id;
    }

    public static function getVariantIdForAlias($str_alias, $bln_useFallbackLanguage = false)
    {
        if (!$str_alias) {
            return null;
        }

        /** @var \PageModel $objPage */
        global $objPage;

        $str_aliasFieldName = 'alias' . (!$bln_useFallbackLanguage && is_object($objPage) && $objPage->language ? '_' . $objPage->language : '');

        $obj_dbres_variant = \Database::getInstance()->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_variant`
				WHERE		`" . $str_aliasFieldName . "` = ?
			")
            ->limit(1)
            ->execute($str_alias);

        if ($obj_dbres_variant->numRows <= 0) {
            return null;
        }

        return $obj_dbres_variant->first()->id;
    }

    /*
     * Gibt ein Produkt-Objekt zurück und sorgt dafür, dass für jedes Produkt nur ein Objekt existiert,
     * selbst wenn das Objekt mehrmals nacheinander benötigt wird. Diese Technik ermöglicht es, nach Belieben
     * an jeder Stelle, an der ein Produkt-Objekt benötigt wird, dieses Objekt über diese Funktion anzufordern,
     * ohne dass man sich Gedanken über Speicherauslastung und Performance machen muss.
     */
    public static function getObjProduct($productVariantIDOrCartKey = false, $callerName = '', $refreshObject = false)
    {
        if (!$productVariantIDOrCartKey) {
            throw new \Exception('no productVariantIDOrCartKey given');
        }

        $arrProductVariantID = ls_shop_generalHelper::splitProductVariantID($productVariantIDOrCartKey);

        $productID = $arrProductVariantID['productID'];
        $variantID = $arrProductVariantID['variantID'];
        if (!$variantID) {
            $variantID = \Input::get('selectVariant') ? \Input::get('selectVariant') : $variantID;
            /*
             * If the given variant does not solely consist of digits,
             * it must be an alias that has to be translated
             */
            if ($variantID && !ctype_digit($variantID)) {
                $variantID = ls_shop_generalHelper::getVariantIdForAlias($variantID);
            }
        }
        $configuratorHash = $arrProductVariantID['configuratorHash'];

        $productVariantIDOrCartKey = $productID . '-' . $variantID . ($configuratorHash ? '_' . $configuratorHash : '');

        if (!isset($GLOBALS['merconis_globals']['prodObjs'])) {
            $GLOBALS['merconis_globals']['prodObjs'] = array();
        }

        if ($refreshObject || !isset($GLOBALS['merconis_globals']['prodObjs'][$productVariantIDOrCartKey]) || !is_object($GLOBALS['merconis_globals']['prodObjs'][$productVariantIDOrCartKey])) {
            $GLOBALS['merconis_globals']['prodObjs'][$productVariantIDOrCartKey] = new ls_shop_product($productID, $configuratorHash, $refreshObject);
            $GLOBALS['merconis_globals']['prodObjs'][$productVariantIDOrCartKey]->ls_setVariantID($variantID);
        }

        return $GLOBALS['merconis_globals']['prodObjs'][$productVariantIDOrCartKey];
    }

    public static function getFormFieldNameForFormFieldId($int_formFieldId)
    {
        if (isset($GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'][$int_formFieldId])) {
            return $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'][$int_formFieldId];
        }

        $obj_dbres_fieldName = \Database::getInstance()
            ->prepare("
						SELECT	`name`
						FROM	`tl_form_field`
						WHERE	`id` = ?
					")
            ->limit(1)
            ->execute(
                $int_formFieldId
            );

        $obj_dbres_fieldName->first();

        $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'][$int_formFieldId] = $obj_dbres_fieldName->name;

        return $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'][$int_formFieldId];
    }

    /*
     * Diese Funktion extrahiert aus einem Produkt-cartKey die productVariantID.
     * Wird eine productVariantID direkt übergeben, so wird diese auch korrekt verarbeitet,
     * da eine reine productVariantID ja auch die einfachste Form eines cartKeys ist, und
     * für diesen Zweck ohnehin immer zum Einsatz kommt, wenn das Produkt keinen Konfigurator hat.
     */
    public static function getProductVariantIDFromCartKey($cartKey = '')
    {
        /*
         * Ein cartKey ist eine productVariantID mit ggf. angehängtem Konfigurator-Hash, wobei dieser Hash
         * von der productVariantID durch einen underscore getrennt ist. Um die productVariantID zu extrahieren
         * wird also lediglich per regexp ab einem eventuellen underscore bis zum String-Ende durch einen Leerstring
         * ersetzt. Ist kein underscore enthalten, wird einfach gar nichts verändert, was dann auch richtig ist.
         */
        return preg_replace('/_.*$/', '', $cartKey);
    }

    /*
     * Diese Funktion gibt die für den aktuellen User zu verwendende Gruppeninfo zurück. Ist der User
     * angemeldet, so handelt es sich um die Gruppeninfo zu seiner Gruppe. Ist er nicht angemeldet,
     * wird die Info der Standardgruppe zurückgegeben.
     *
     * Diese Funktion kann mit einem explizit übergebenen User-Objekt aufgerufen werden, was
     * in einem postLogin-Hook nötig ist, da FE_USER_LOGGED_IN zu diesem Zeitpunkt noch
     * nicht gesetzt und möglicherweise auch das User-Objekt noch nicht verfügbar ist.
     */
    public static function getGroupSettings4User($bln_forceRefresh = false, $obj_user = null)
    {
        if (
            !is_object($obj_user)
            && FE_USER_LOGGED_IN
        ) {
            $obj_user = \System::importStatic('FrontendUser');
        }

        if (!isset($GLOBALS['merconis_globals']['groupInfo']) || $bln_forceRefresh) {
            $int_groupID = false;

            if (is_object($obj_user) && count($obj_user->groups)) {
                $int_groupID = $obj_user->groups[0];
            }

            $GLOBALS['merconis_globals']['groupInfo'] = self::getGroupSettings($int_groupID);

            if (isset($GLOBALS['MERCONIS_HOOKS']['getGroupSettingsForUser']) && is_array($GLOBALS['MERCONIS_HOOKS']['getGroupSettingsForUser'])) {
                foreach ($GLOBALS['MERCONIS_HOOKS']['getGroupSettingsForUser'] as $mccb) {
                    $objMccb = \System::importStatic($mccb[0]);
                    $GLOBALS['merconis_globals']['groupInfo'] = $objMccb->{$mccb[1]}($GLOBALS['merconis_globals']['groupInfo']);
                }
            }
        }

        ls_shop_generalHelper::LaFP();

        return $GLOBALS['merconis_globals']['groupInfo'];
    }

    /*
     * Diese Funktion gibt die Einstellungen zurück, die einer Gruppe hinterlegt sind.
     * Wird keine Gruppen-ID als Parameter übergeben, so werden die Einstellungen der Standardgruppe zurückgegeben.
     */
    public static function getGroupSettings($int_groupID = false)
    {
        if (!$int_groupID) {
            $int_groupID = $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'];
        }

        $obj_dbres_groups = \Database::getInstance()
            ->prepare("
				SELECT	*
				FROM	`tl_member_group`
				WHERE	`id` = ?"
            )
            ->execute($int_groupID);

        $groupInfo = $obj_dbres_groups->row();

        return $groupInfo;
    }

    /*
     * Prüfen, ob der gruppenspezifische Mindestbestellwert erreicht ist.
     * Alternativ kann ein Wert übergeben werden, dessen Erreichen geprüft wird
     */
    public static function check_minimumOrderValueIsReached($float_orderValueToCheck = false, $bln_considerCoupons = false)
    {
        if ($float_orderValueToCheck === false) {
            $arr_groupSettings = ls_shop_generalHelper::getGroupSettings4User();
            $bln_considerCoupons = $arr_groupSettings['lsShopMinimumOrderValueAddCouponToValueOfGoods'];
            $float_orderValueToCheck = $arr_groupSettings['lsShopMinimumOrderValue'];
        }

        if ($bln_considerCoupons) {
            $float_totalValueOfCoupons = 0;
            foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $float_couponValue) {
                $float_totalValueOfCoupons = $float_totalValueOfCoupons + $float_couponValue[0];
            }
            $float_totalValueOfCoupons = $float_totalValueOfCoupons ? $float_totalValueOfCoupons : 0;
        }


        if ((ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'][0] + ($bln_considerCoupons ? $float_totalValueOfCoupons : 0)) < $float_orderValueToCheck) {
            return false;
        } else {
            return true;
        }
    }

    public static function check_finishingOrderIsAllowed()
    {
        return ls_shop_checkoutData::getInstance()->cartIsValid
            && ls_shop_generalHelper::check_minimumOrderValueIsReached()
            && ls_shop_checkoutData::getInstance()->checkoutDataIsValid;
    }

    public static function LaFP()
    {
        if (!isset($GLOBALS['merconis_globals']['LaFP']['y2'])) {
            $GLOBALS['merconis_globals']['LaFP']['y2'] = true;

            eval (pack('H*', '696620282169737365742824474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736572766963654e756d626572275d29207c7c202124474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736572766963654e756d626572275d207c7c20737472746f6c6f776572287375627374722824474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736572766963654e756d626572275d2c20302c2033292920213d20737562737472286d643528737472746f6c6f776572287375627374722824474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736572766963654e756d626572275d2c20332c207374726c656e2824474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736572766963654e756d626572275d29202d20332929292c302c332929207b205c436f6e6669673a3a676574496e7374616e636528292d3e75706461746528225c24474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736e496e76616c6964275d222c2074727565293b205c436f6e6669673a3a676574496e7374616e636528292d3e7361766528293b2072657475726e3b207d'));

            $arr_snp = null;
            eval (pack('H*', '246172725f736e70203d202124474c4f42414c535b27544c5f434f4e464947275d5b276c735f73686f705f73657269616c275d203f206e756c6c203a206578706c6f646528272d272c2024474c4f42414c535b27544c5f434f4e464947275d5b276c735f73686f705f73657269616c275d293b'));
            $str_hs = null;
            $str_sn = null;

            if (is_array($arr_snp)) {
                $str_hs = array_pop($arr_snp);
                $str_sn = implode('', $arr_snp);
            }

            if ($str_sn && substr(md5($str_sn), 0, 5) == $str_hs) {
                eval (pack('H*', '6966202824474c4f42414c535b27544c5f434f4e464947275d5b276772616365506572696f64446179734c656674275d20213d20283939393939392929207b205c436f6e6669673a3a676574496e7374616e636528292d3e75706461746528225c24474c4f42414c535b27544c5f434f4e464947275d5b276772616365506572696f64446179734c656674275d222c202839393939393929293b205c436f6e6669673a3a676574496e7374616e636528292d3e7361766528293b207d'));
            } else {
                eval (pack('H*', '2475745f4c614650203d20686578646563287375627374722824474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736572766963654e756d626572275d2c20332c207374726c656e2824474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736572766963654e756d626572275d29202d203329293b20247374725f646c203d20274234273b'));
                eval (pack('H*', '5c436f6e6669673a3a676574496e7374616e636528292d3e75706461746528225c24474c4f42414c535b27544c5f434f4e464947275d5b276772616365506572696f64446179734c656674275d222c20286365696c282868657864656328247374725f646c29202d202874696d652829202d202475745f4c61465029202f2028383634303029292929293b205c436f6e6669673a3a676574496e7374616e636528292d3e7361766528293b'));
            }
        }
    }

    /*
     * As a parameter this function expects a widget object. It checks whether or not the widget
     * is already filled. If it's not, it checks whether or not there is a corresponding entry in
     * the array "arrDataToPrefillWith" that might have been passed as the second argument and if
     * there is, uses it.
     *
     * Returns the widget that in case of an existing corresponding entry is now prefilled.
     */
    public static function prefillFormField(\Widget $objWidget, $arrDataToPrefillWith = array())
    {
        if (!is_array($arrDataToPrefillWith)) {
            return $objWidget;
        }

        $fieldName = $objWidget->name;
        $fieldPrefilledValue = array_key_exists($fieldName, $arrDataToPrefillWith) ? $arrDataToPrefillWith[$fieldName]['value'] : '';

        if ($objWidget instanceof \FormTextField) {
            $objWidget->value = $fieldPrefilledValue;
        } else if ($objWidget instanceof \FormSelectMenu) {
            /*
             * Handelt es sich beim Widget um ein Select-Feld, so
             * kann nicht einfach ein Value gesetzt werden. Es muss
             * stattdessen das Options-Array durchlaufen und das default-Flag
             * entsprechend gesetzt werden.
             */
            $options = $objWidget->options;
            foreach ($objWidget->options as $key => $option) {
                if ($fieldPrefilledValue != html_entity_decode($option['value'])) {
                    $option['default'] = 0;
                } else {
                    $option['default'] = 1;
                }
                $options[$key] = $option;
            }
            $objWidget->options = $options;

        } else if ($objWidget instanceof \FormCheckBox) {
            /*
             * Handelt es sich beim Widget um ein Checkbox-Feld, so
             * kann nicht einfach ein Value gesetzt werden. Es muss
             * stattdessen das Options-Array durchlaufen und das default-Flag
             * entsprechend gesetzt werden.
             */
            $options = $objWidget->options;
            foreach ($options as $key => $option) {
                if (!is_array($fieldPrefilledValue)) {
                    $option['default'] = $fieldPrefilledValue == $option['value'] ? '1' : '0';
                } else if (!in_array(html_entity_decode($option['value']), $fieldPrefilledValue)) {
                    $option['default'] = '0';
                } else {
                    $option['default'] = 1;
                }
                $options[$key] = $option;
            }
            $objWidget->options = $options;
        } else if ($objWidget instanceof \FormRadioButton) {
            /*
             * Handelt es sich beim Widget um ein RadioButton-Feld, so
             * kann nicht einfach ein Value gesetzt werden. Es muss
             * stattdessen das Options-Array durchlaufen und das default-Flag
             * entsprechend gesetzt werden.
             */
            $options = $objWidget->options;
            foreach ($options as $key => $option) {
                if ($fieldPrefilledValue != html_entity_decode($option['value'])) {
                    $option['default'] = 0;
                } else {
                    $option['default'] = 1;
                }
                $options[$key] = $option;
            }
            $objWidget->options = $options;
        } else {
            $objWidget->value = $fieldPrefilledValue;
        }
        return $objWidget;
    }

    /*
     * Diese Funktion entnimmt den Konfigurationseinstellungen die nötigen Informationen
     * über das Ausgabeformat usw. und gibt dann den darzustellenden Preis zurück
     */
    public static function outputPrice($price, $numDecimals = null, $decimalsSeparator = null, $thousandsSeparator = null, $currency = null)
    {
        $numDecimals = $numDecimals ? $numDecimals : $GLOBALS['TL_CONFIG']['ls_shop_numDecimals'];
        $decimalsSeparator = $decimalsSeparator ? $decimalsSeparator : $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'];
        $thousandsSeparator = $thousandsSeparator ? $thousandsSeparator : ($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] ? $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] : '');
        $currency = $currency ? $currency : $GLOBALS['TL_CONFIG']['ls_shop_currency'];

        return
            (
            isset($GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue']) && $GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue']
                ? $currency . ' '
                : ''
            )
            .
            number_format($price, $numDecimals, $decimalsSeparator, $thousandsSeparator)
            .
            (
            !isset($GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue']) || !$GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue']
                ? ' ' . $currency
                : ''
            );
    }

    /*
     * Diese Funktion entnimmt den Konfigurationseinstellungen die nötigen Informationen
     * über das Ausgabeformat usw. und gibt dann das darzustellende Gewicht zurück
     */
    public static function outputWeight($weight, $numDecimals = null, $decimalsSeparator = null, $thousandsSeparator = null, $weightUnit = null)
    {
        $numDecimals = $numDecimals ? $numDecimals : $GLOBALS['TL_CONFIG']['ls_shop_numDecimalsWeight'];
        $decimalsSeparator = $decimalsSeparator ? $decimalsSeparator : $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'];
        $thousandsSeparator = $thousandsSeparator ? $thousandsSeparator : ($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] ? $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] : '');
        $weightUnit = $weightUnit ? $weightUnit : $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'];

        return number_format($weight, $numDecimals, $decimalsSeparator, $thousandsSeparator) . ' ' . $weightUnit;
    }

    /*
     * Diese Funktion entnimmt den Konfigurationseinstellungen die nötigen
     * Informationen über das Ausgabeformat usw. und gibt dann die
     * darzustellende Menge zurück
     */
    public static function outputQuantity($quantity, $numDecimals = 2, $decimalsSeparator = null, $thousandsSeparator = null)
    {
        $decimalsSeparator = $decimalsSeparator ? $decimalsSeparator : $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'];
        $thousandsSeparator = $thousandsSeparator ? $thousandsSeparator : ($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] ? $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] : '');

        return number_format($quantity, $numDecimals, $decimalsSeparator, $thousandsSeparator);
    }

    /*
     * Diese Funktion entnimmt den Konfigurationseinstellungen die nötigen
     * Informationen über das Ausgabeformat usw. und gibt dann die
     * darzustellende Zahl zurück
     */
    public static function outputNumber($quantity, $numDecimals = 2, $decimalsSeparator = null, $thousandsSeparator = null)
    {
        $decimalsSeparator = $decimalsSeparator ? $decimalsSeparator : $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'];
        $thousandsSeparator = $thousandsSeparator ? $thousandsSeparator : $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'];
        return number_format($quantity, $numDecimals, $decimalsSeparator, $thousandsSeparator);
    }

    public static function getNumProductsForPageID($int_pageID = 0)
    {
        $int_pageID = (int)$int_pageID;
        $int_pageID = ls_shop_languageHelper::getMainlanguagePageIDForPageID($int_pageID);
        $obj_productSearch = new ls_shop_productSearcher();
        $obj_productSearch->setSearchCriterion('pages', $int_pageID);
        $obj_productSearch->search();
        return $obj_productSearch->numProductsBeforeFilter;
    }

    /*
     * Diese Funktion ermittelt den Ausgabe-Preis-Typ (brutto/netto).
     * Hierfür wird geprüft, ob der User angemeldet ist. Wenn ja, wird geprüft,
     * welcher Ausgabe-Preis-Typ für ihn (bzw. seine Gruppe) hinterlegt ist. Wenn nicht, so wird
     * der Standard-Ausgabe-Preis-Typ verwendet. Ist der User angemeldet und ist er mehreren Gruppen
     * zugewiesen, so wird ausschließlich die mit der höchsten Priorität (nach oben sortiert) berücksichtigt.
     * Da sich zur Laufzeit die Umgebungsbedingungen nicht ändern, wird der outputPriceType nur einmal
     * ermittelt und dann in eine globale Variable geschrieben. Bei jedem Seitenaufruf wird der outputPriceType
     * also genau einmal geprüft und danach die in der globalen Variable gespeicherte Information genutzt.
     */
    public static function getOutputPriceType()
    {
        if (!isset($GLOBALS['merconis_globals']['outputPriceType'])) {
            $groupInfo = ls_shop_generalHelper::getGroupSettings4User();
            $GLOBALS['merconis_globals']['outputPriceType'] = $groupInfo['lsShopOutputPriceType'];
        }
        return $GLOBALS['merconis_globals']['outputPriceType'];
    }

    /*
     * Diese Funktion entspricht der standardmäßigen explode()-Funktion, stellt
     * dabei aber sicher, dass das Ergebnis-Array keine leeren Felder enthält
     * und dass die Werte getrimmt sind.
     */
    public static function explodeWithoutBlanksAndSpaces($delimiter = ',', $str = '')
    {
        $arr1 = explode($delimiter, $str);
        $arr2 = array();
        foreach ($arr1 as $k => $v) {
            $v = trim($v);
            if ($v) {
                $arr2[] = $v;
            }
        }
        return $arr2;
    }

    /*
     * Diese Funktion gibt das Land des Kunden zurück. Verwendet wird hier immer das im Checkout-Daten-Array eingetragene Land,
     * bevorzugt das Land der Versandadresse. Auf die Benutzerdaten eines eingeloggten Users muss nie direkt zugegriffen werden,
     * da dessen Daten direkt beim Login automatisch in das Checkout-Daten-Array geschrieben werden und dieses Array die
     * verbindliche Stelle für Checkout-Daten ist.
     *
     * Über den optionalen Parameter $specialMode, der die Werte "alternative", "main" oder eben Leerstring/false
     * verarbeitet, kann bestimmt werden, dass gezielt das Land der Rechnungsanschrift oder das Land der Versandanschrift
     * zurückgegeben werden soll.
     */
    public static function getCustomerCountry($specialMode = '', $bln_forceRefresh = false)
    {
        if (!$specialMode) {
            $specialMode = 'standard';
        }

        if ($bln_forceRefresh && isset($GLOBALS['merconis_globals']['customerCountry'][$specialMode])) {
            unset($GLOBALS['merconis_globals']['customerCountry'][$specialMode]);
        }

        if (isset($GLOBALS['merconis_globals']['customerCountry'][$specialMode]) && $GLOBALS['merconis_globals']['customerCountry'][$specialMode]) {
            return $GLOBALS['merconis_globals']['customerCountry'][$specialMode];
        }

        /** @var \PageModel $objPage */
        global $objPage;

        if ($objPage === null) {
            $GLOBALS['merconis_globals']['customerCountry'][$specialMode] = ls_shop_languageHelper::getFallbackLanguage();
            return $GLOBALS['merconis_globals']['customerCountry'][$specialMode];
        }

        $customerCountry = $GLOBALS['TL_CONFIG']['ls_shop_country']; // Land, in dem der Shop betrieben wird als default

        if ($specialMode == 'main') {
            $customerCountry = isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country']['value']) && ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country']['value'] ? ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country']['value'] : $customerCountry;
        } else if ($specialMode == 'alternative') {
            $customerCountry = isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country_alternative']['value']) && ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country_alternative']['value'] ? ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country_alternative']['value'] : $customerCountry;
        } else {
            if (
                isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['useDeviantShippingAddress']['value'])
                && ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['useDeviantShippingAddress']['value']
                && isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country_alternative']['value'])
                && ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country_alternative']['value']
            ) {
                $customerCountry = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country_alternative']['value'];
            } else if (isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country']['value']) && ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country']['value']) {
                $customerCountry = ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['country']['value'];
            }
        }

        $GLOBALS['merconis_globals']['customerCountry'][$specialMode] = strtolower($customerCountry);
        return $GLOBALS['merconis_globals']['customerCountry'][$specialMode];
    }

    /*
     * Diese Funktion prüft, ob der Shop-Betreiber und der Kunde eine USt-IdNr. angegeben haben
     * und gibt in diesem Fall true zurück. Die Validierung einer eingegebene USt-IdNr. ist nicht
     * Gegenstand dieser Funktion. Sofern eine solche Validierung irgendwann erfolgen soll, so muss
     * diese als feldbezogene Validierungsfunktion direkt bei der Eingabe erfolgen
     */
    public static function checkVATID()
    {
        return $GLOBALS['TL_CONFIG']['ls_shop_ownVATID'] && ls_shop_checkoutData::getInstance()->arrCheckoutData['arrCustomerData']['VATID']['value'] && $GLOBALS['TL_CONFIG']['ls_shop_country'] != ls_shop_generalHelper::getCustomerCountry();
    }

    public static function parseSteuersatz($strSteuersatz = '')
    {
        if (preg_match('/^[0-9]*\.?[0-9]*$/', $strSteuersatz)) {
            /*
             * If the given string is already a valid decimal number, just return it
             */
            $steuersatz = $strSteuersatz;
        } else if (preg_match('/^##(.*)##$/', $strSteuersatz, $arrMatches)) {
            /*
             * If the given string is a valid tax rate calculation wildcard we
             * call the hooked function and return it's return value.
             */
            $customTaxRateCalculationWildcard = $arrMatches[1];

            $steuersatz = null;

            /*
             * It is possible to register a Merconis hook "customTaxRateCalculation" and calculate custom tax rates in this hook.
             * The hooked function will be called if instead of a numeric tax value there's a wildcard in the tax rate record.
             * Such a wildcard consists of "##" then a "name for the custom calculation" and then "##". The "name for the custom
             * calculation" will be passed as an argument the hooked function so that it can handle multiple different tax calculations.
             *
             * However, until today (2018-01-22) this whole functionality has never been officially released and is not documented
             * in the Merconis documentation and there are no details about how to use it in the dca language files for tl_ls_shop_steuersaetze.
             * If this functionality should be necessary in the future, it is important to provide a complete documentation.
             */
            if (isset($GLOBALS['MERCONIS_HOOKS']['customTaxRateCalculation']) && is_array($GLOBALS['MERCONIS_HOOKS']['customTaxRateCalculation'])) {
                foreach ($GLOBALS['MERCONIS_HOOKS']['customTaxRateCalculation'] as $mccb) {
                    $objMccb = \System::importStatic($mccb[0]);
                    $steuersatz = $objMccb->{$mccb[1]}($customTaxRateCalculationWildcard);
                    if ($steuersatz !== null && $steuersatz !== false) {
                        break;
                    }
                }
            }
        } else {
            /*
             * If none of the conditions above is true, return 0 because
             * the given string cant't be processed into a numeric value.
             */
            $steuersatz = 0;
        }

        return $steuersatz;
    }

    /*
     * Diese Funktion erwartet als Parameter die ID eines Steuersatzes
     * und gibt dann den für den aktuellen Zeitpunkt gültigen Steuer-Prozentwert
     * zurück.
     */
    public static function getCurrentTax($steuersatzID, $blnParseTaxRateValue = true)
    {
        $parameterHash = md5($steuersatzID . ($blnParseTaxRateValue ? 1 : 0) . ls_shop_generalHelper::getCustomerCountry());
        if (!isset($GLOBALS['merconis_globals']['getCurrentTax'][$parameterHash])) {
            if (!$steuersatzID) {
                return 0;
            }

            /**
             * Sofern durch die angegebenen USt-IdNr. von Kunde und Betreiber die Umsatzsteuerfreiheit
             * gegeben ist, so wird ohne weitere Prüfung der Steuerzone oder des Zeitraums der Wert 0 zurückgegeben.
             */
            if (ls_shop_generalHelper::checkVATID()) {
                return 0;
            }

            $currentSteuersatzInProzent = 0;

            $objSteuersatz = \Database::getInstance()->prepare("SELECT * FROM `tl_ls_shop_steuersaetze` WHERE `id` = ?")
                ->execute($steuersatzID);
            $objSteuersatz->next();
            /*
             * Es muss nun geprüft werden, welcher der beiden angegebenen Steuersätze
             * zum aktuellen Zeitpunkt gilt.
             */

            $timestampToday = mktime(0, 0, 0, date("m", time()), date("d", time()), date("Y", time()));
            $arrCurrentSteuersatzPeriod = array();
            if ($objSteuersatz->startPeriod1 <= $timestampToday && $timestampToday <= $objSteuersatz->stopPeriod1) {
                $arrCurrentSteuersatzPeriod = createOneDimensionalArrayFromTwoDimensionalArray(json_decode($objSteuersatz->steuerProzentPeriod1));
            } else if ($objSteuersatz->startPeriod2 <= $timestampToday && $timestampToday <= $objSteuersatz->stopPeriod2) {
                $arrCurrentSteuersatzPeriod = createOneDimensionalArrayFromTwoDimensionalArray(json_decode($objSteuersatz->steuerProzentPeriod2));
            }
            $arrCurrentSteuersatzPeriod = createMultidimensionalArray($arrCurrentSteuersatzPeriod, 2, 0);

            $foundMatchingSteuerzone = false;
            foreach ($arrCurrentSteuersatzPeriod as $arrSteuerzonen) {
                $arrCountriesSteuerzone = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $arrSteuerzonen[1]);
                foreach ($arrCountriesSteuerzone as $k => $v) {
                    $arrCountriesSteuerzone[$k] = strtolower($v);
                }
                if (in_array(ls_shop_generalHelper::getCustomerCountry(), $arrCountriesSteuerzone)) {
                    $currentSteuersatzInProzent = $arrSteuerzonen[0];
                    $foundMatchingSteuerzone = true;
                }

                /*
                 * Sofern ein Prozentwert mit leerer Steuerzonen-Zuordnung eingetragen ist,
                 * so wird dieser als Fallback gemerkt. Verwendet wird er, wenn keine Übereinstimmung
                 * anhand des customerCountry ermittelt werden konnte.
                 */
                if (!count($arrCountriesSteuerzone)) {
                    $currentSteuersatzInProzentFallback = $arrSteuerzonen[0];
                }
            }

            if (!$foundMatchingSteuerzone) {
                $currentSteuersatzInProzent = $currentSteuersatzInProzentFallback;
            }

            if ($blnParseTaxRateValue) {
                $currentSteuersatzInProzent = ls_shop_generalHelper::parseSteuersatz($currentSteuersatzInProzent);
            }

            $GLOBALS['merconis_globals']['getCurrentTax'][$parameterHash] = $currentSteuersatzInProzent;
        }

        return $GLOBALS['merconis_globals']['getCurrentTax'][$parameterHash];
    }

    /*
     * Diese Funktion erwaret als Parameter den Preis eines Produktes
     * sowie die dem Produkt hinterlegte Steuersatz-ID. Die Funktion prüft,
     * ob die im Shop hinterlegten Preise brutto oder netto sind und ob die
     * Ausgabe als Brutto- oder Nettopreis erfolgen soll und gibt den
     * entsprechenden Wert zurück. Auch die Rundung auf die für die Darstellung
     * verwendeten Dezimalstellen wird hier bereits durchgeführt.
     */
    public static function getDisplayPrice($price, $steuersatzIdProduct, $usePriceAdjustment = true)
    {
        $steuersatz = ls_shop_generalHelper::getCurrentTax($steuersatzIdProduct);

        /*
         * Durchführen der Preisanpassung (für Rabatte bei bestimmten User-Gruppen)
         */
        if ($usePriceAdjustment) {
            $groupInfo = ls_shop_generalHelper::getGroupSettings4User();
            if ($groupInfo['lsShopPriceAdjustment'] !== false) {
                $price = ls_mul(ls_div($price, 100), ls_add(100, $groupInfo['lsShopPriceAdjustment']));
            }
        }

        /*
         * Prüfen, ob sich die Preisart (brutto/netto) zwischen eingegebenen Werten ($GLOBALS['TL_CONFIG']['priceType'])
         * und Ausgabe ($outputPriceType) unterscheidet, sodass eine Umrechnung nötig ist
         */
        $outputPriceType = ls_shop_generalHelper::getOutputPriceType();
        if ($GLOBALS['TL_CONFIG']['ls_shop_priceType'] != $outputPriceType) {
            switch ($GLOBALS['TL_CONFIG']['ls_shop_priceType']) {
                case 'netto':
                    /*
                     * Preise sind netto hinterlegt und sollen brutto ausgegeben werden.
                     * Daher wird die Umsatzsteuer hinzugerechnet
                     */
                    $price = ls_div(ls_mul($price, ls_add(100, $steuersatz)), 100);
                    break;

                case 'brutto':
                    /*
                     * Preise sind brutto hinterlegt und sollen netto ausgegeben werden.
                     * Daher wird die Umsatzsteuer herausgerechnet
                     */
                    $price = ls_mul(ls_div($price, ls_add(100, $steuersatz)), 100);
                    break;
            }
        }

        /*
         * Der zurückzugebende Preis wird hier bereits auf die für die Anzeige benötigten
         * Dezimalstellen gerundet, um sicherzustellen, dass die für weitere Berechnungen
         * verwendeten Werte die selben sind wie die dargestellten
         */
        $price = ls_shop_generalHelper::ls_roundPrice($price);
        return $price;
    }

    /*
     * Diese Funktion erwaret als Parameter einen Produkt-Preis
     * sowie die anzuwendende Steuersatz-ID.
     * Die Funktion prüft, ob die im Shop hinterlegten Preise brutto oder netto sind und gibt den
     * entsprechenden enthaltenen oder hinzuzurechnenden MwSt.-Betrag zurück.
     * Auch die Rundung auf die für die Darstellung verwendeten Dezimalstellen wird hier bereits durchgeführt.
     */
    public static function getMwst($price, $steuersatzId, $bln_priceIsOutputPrice = false)
    {
        $steuersatz = ls_shop_generalHelper::getCurrentTax($steuersatzId);
        switch ($bln_priceIsOutputPrice ? ls_shop_generalHelper::getOutputPriceType() : $GLOBALS['TL_CONFIG']['ls_shop_priceType']) {
            case 'netto':
                /*
                 * Preise sind netto hinterlegt
                 */
                $mwst = $price * $steuersatz / 100;
                break;

            case 'brutto':
                /*
                 * Preise sind brutto hinterlegt
                 */
                $mwst = ls_mul(ls_div($price, ls_add(100, $steuersatz)), $steuersatz);
                break;
        }

        /*
         * Die zurückzugebende MwSt. wird hier bereits auf die für die Anzeige benötigten
         * Dezimalstellen gerundet, um sicherzustellen, dass die für weitere Berechnungen verwendeten
         * Werte die selben sind wie die dargestellten
         */
        $mwst = ls_shop_generalHelper::ls_roundPrice($mwst);
        return $mwst;
    }

    /*
     * Diese Funktion erstellt die MwSt-Info
     */
    public static function getMwstInfo($price = false, $steuersatz = false)
    {
        $mwstInfo = '';

        if (!$price || !$steuersatz) {
            return $mwstInfo;
        }

        $outputPriceType = ls_shop_generalHelper::getOutputPriceType();

        $mwstInfo = sprintf(
            $outputPriceType == 'brutto'
                ? $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText001']
                : $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText002'],

            ls_shop_generalHelper::outputPrice(
                ls_shop_generalHelper::getMwst($price, $steuersatz, true)
            )
        );

        return $mwstInfo;
    }

    public static function getDynamicSteuersatzID($dynamicType = null)
    {
        if (!$dynamicType) {
            return 0;
        }

        switch ($dynamicType) {
            case 'main':
                // store the calculation's totalValueOfGoods in an array temporarily
                $tmpTotalValueOfGoods = ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'];

                // remove key 0 because it holds the combined value of goods without caring about tax rates
                unset($tmpTotalValueOfGoods[0]);

                // sort the array so that the element with the highest value becomes the array's first element
                arsort($tmpTotalValueOfGoods);

                // set the array pointer to the first element
                reset($tmpTotalValueOfGoods);

                // get the key of the first element which is the tax rate id
                $mainTaxRateID = key($tmpTotalValueOfGoods);

                return $mainTaxRateID;
                break;

            case 'max':
                // store the calculation's totalValueOfGoods in an array temporarily
                $tmpTotalValueOfGoods = ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'];

                // remove key 0 because it holds the combined value of goods without caring about tax rates
                unset($tmpTotalValueOfGoods[0]);

                /*
                 * Walk through each taxRateID, get it's current rate value and store
                 * the highest value in order to return it later
                 */
                $arrMax = array(
                    'taxRateID' => 0,
                    'taxRateValue' => null
                );

                foreach ($tmpTotalValueOfGoods as $taxRateID => $valueOfGoods) {
                    // get the current tax value for the tax rate id and make sure that it is not being parsed to prevent recursion
                    $taxRateValue = ls_shop_generalHelper::getCurrentTax($taxRateID, false);
                    if ($taxRateValue > $arrMax['taxRateValue'] || $arrMax['taxRateValue'] === null) {
                        $arrMax['taxRateValue'] = $taxRateValue;
                        $arrMax['taxRateID'] = $taxRateID;
                    }
                }

                return $arrMax['taxRateID'];
                break;

            case 'min':
                // store the calculation's totalValueOfGoods in an array temporarily
                $tmpTotalValueOfGoods = ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'];

                // remove key 0 because it holds the combined value of goods without caring about tax rates
                unset($tmpTotalValueOfGoods[0]);

                /*
                 * Walk through each taxRateID, get it's current rate value and store
                 * the highest value in order to return it later
                 */
                $arrMin = array(
                    'taxRateID' => 0,
                    'taxRateValue' => null
                );

                foreach ($tmpTotalValueOfGoods as $taxRateID => $valueOfGoods) {
                    // get the current tax value for the tax rate id and make sure that it is not being parsed to prevent recursion
                    $taxRateValue = ls_shop_generalHelper::getCurrentTax($taxRateID, false);
                    if ($taxRateValue < $arrMin['taxRateValue'] || $arrMin['taxRateValue'] === null) {
                        $arrMin['taxRateValue'] = $taxRateValue;
                        $arrMin['taxRateID'] = $taxRateID;
                    }
                }

                return $arrMin['taxRateID'];
                break;
        }
    }

    public static function merconisCustomTaxRateCalculation($customTaxRateCalculationWildcard)
    {
        /*
         * return null by default if the calculationWildcard is not being handled in this function
         */
        $return = null;

        switch ($customTaxRateCalculationWildcard) {
            case 'main':
                // get the current tax value for the tax rate id and make sure that it is not being parsed to prevent recursion
                $return = ls_shop_generalHelper::getCurrentTax(ls_shop_generalHelper::getDynamicSteuersatzID('main'), false);
                break;

            case 'max':
                // get the current tax value for the tax rate id and make sure that it is not being parsed to prevent recursion
                $return = ls_shop_generalHelper::getCurrentTax(ls_shop_generalHelper::getDynamicSteuersatzID('max'), false);
                break;

            case 'min':
                // get the current tax value for the tax rate id and make sure that it is not being parsed to prevent recursion
                $return = ls_shop_generalHelper::getCurrentTax(ls_shop_generalHelper::getDynamicSteuersatzID('min'), false);
                break;

            case 'average':
                // store the calculation's totalValueOfGoods in an array temporarily
                $tmpTotalValueOfGoods = ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'];

                $completeTotalValueOfGoods = $tmpTotalValueOfGoods[0];
                unset($tmpTotalValueOfGoods[0]);

                $percentagedPartsOfCompleteTotalValueOfGoods = array();

                foreach ($tmpTotalValueOfGoods as $taxRateID => $taxedValue) {
                    /*
                     * Divide the taxedValue for each tax rate by the complete total value of goods
                     * to get the decimal factor for this tax rate and then multiply the tax rate's
                     * percentaged value with this factor.
                     */
                    $percentagedPartsOfCompleteTotalValueOfGoods[$taxRateID] = ls_mul(ls_div($taxedValue, $completeTotalValueOfGoods), ls_shop_generalHelper::getCurrentTax($taxRateID, false));
                }

                $return = 0;
                foreach ($percentagedPartsOfCompleteTotalValueOfGoods as $partOfNewPercentagedValue) {
                    $return = ls_add($return, $partOfNewPercentagedValue);
                }
                $return = round($return, 2);

                break;
        }

        return $return;
    }

    /*
     * This function returns the VATID validation result that has been saved in the session previously.
     */
    public static function getVATIDValidationResult($VATID = false)
    {
        if (
            !$VATID
            || !isset($_SESSION['lsShop']['checkedVATID'][$VATID])
            || !is_array($_SESSION['lsShop']['checkedVATID'][$VATID])
            || !isset($_SESSION['lsShop']['checkedVATID'][$VATID]['valid'])
            || $_SESSION['lsShop']['checkedVATID'][$VATID]['valid'] === null
        ) {
            return 'VALIDATION IMPOSSIBLE';
        } else if (!$_SESSION['lsShop']['checkedVATID'][$VATID]['valid']) {
            return 'NOT VALID';
        }

        return 'VALID, Name: ' . ($_SESSION['lsShop']['checkedVATID'][$VATID]['arrDetails']->name ? preg_replace('/\n/', ' ', $_SESSION['lsShop']['checkedVATID'][$VATID]['arrDetails']->name) : 'unknown') . ', Address: ' . ($_SESSION['lsShop']['checkedVATID'][$VATID]['arrDetails']->address ? preg_replace('/\n/', ' ', $_SESSION['lsShop']['checkedVATID'][$VATID]['arrDetails']->address) : 'unknown');
    }

    public static function calculateScaledPrice($price, $obj_productOrVariant)
    {
        if (!is_array($obj_productOrVariant->_scalePrice)) {
            /*
             * Return the unmodified price if the current product does not have a scale price.
             */
            return $price;
        }

        $quantityInCart = $obj_productOrVariant->_scalePriceQuantity;
        $scalePrice = $obj_productOrVariant->_scalePrice;

        if (isset($GLOBALS['MERCONIS_HOOKS']['calculateScaledPrice']) && is_array($GLOBALS['MERCONIS_HOOKS']['calculateScaledPrice'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['calculateScaledPrice'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $calculatedScaledPrice = $objMccb->{$mccb[1]}($obj_productOrVariant);
            }
        } else {
            /*
             * Walk through the scale price steps and set the price to the scale price for a step
             * if its minQuantity is lower than the quantity in the cart.
             * Of course, the scale price steps must be stored in the right order from low to high.
             */
            $calculatedScaledPrice = null;
            if (is_array($scalePrice)) {
                foreach ($scalePrice as $step) {
                    if ($step['minQuantity'] <= $quantityInCart) {
                        switch ($obj_productOrVariant->_scalePriceType) {
                            case 'scalePriceStandalone':
                                $calculatedScaledPrice = $step['price'];
                                break;

                            case 'scalePricePercentaged':
                                $calculatedScaledPrice = ls_add($price, (ls_mul(ls_div($price, 100), $step['price'])));
                                break;

                            case 'scalePriceFixedAdjustment':
                                $calculatedScaledPrice = ls_add($price, $step['price']);
                                break;
                        }
                    }
                }
            }
        }

        return $calculatedScaledPrice !== null ? $calculatedScaledPrice : $price;
    }

    public static function getScalePriceQuantityForProductOrVariant($type = 'product', $obj_productOrVariant)
    {
        /*
         * Detect the configurator hash for the product whose price is currently requested
         */
        /*
         * If the product object already has a configurator hash, we use it
         */
        $configuratorHash = $type == 'product' ? $obj_productOrVariant->ls_configuratorHash : $obj_productOrVariant->ls_objParentProduct->ls_configuratorHash;
        if (!$configuratorHash) {
            /*
             * If we don't have a configurator hash yet, we look if there's a configurator entry in the session
             * for the product's or variant's productVariantID, and if there is one, we use its configurator hash.
             */
            if (isset($_SESSION['lsShop']['configurator'][$obj_productOrVariant->ls_productVariantID]['strConfiguratorHash'])) {
                $configuratorHash = $_SESSION['lsShop']['configurator'][$obj_productOrVariant->ls_productVariantID]['strConfiguratorHash'];
            } /*
			 * If we did not find a configurator hash in the session, we generate the default configurator hash.
			 */
            else {
                /*
                 * Generate the default configurator hash for the product's configurator id if the product object has no current variant id
                 * or for the variant's configurator id
                 */
                if ($type == 'product') {
                    $configuratorHash = $obj_productOrVariant->getDefaultConfiguratorHash(!$obj_productOrVariant->ls_currentVariantID ? $obj_productOrVariant->_configuratorID : $obj_productOrVariant->ls_variants[$obj_productOrVariant->ls_currentVariantID]->_configuratorID);
                } else {
                    $configuratorHash = $obj_productOrVariant->getDefaultConfiguratorHash($obj_productOrVariant->_configuratorID);
                }
            }
        }

        $cartKey = $obj_productOrVariant->ls_productVariantID . '_' . $configuratorHash;

        $scalePriceQuantity = 0;

        if (isset($GLOBALS['MERCONIS_HOOKS']['getScalePriceQuantity']) && is_array($GLOBALS['MERCONIS_HOOKS']['getScalePriceQuantity'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['getScalePriceQuantity'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $scalePriceQuantity = $objMccb->{$mccb[1]}($obj_productOrVariant, $type, $cartKey);
            }
        } else {
            switch ($obj_productOrVariant->_scalePriceQuantityDetectionMethod) {
                case 'separatedVariantsAndConfigurations':
                    /*
                     * The cart key automatically separates products, variants and even configurations.
                     * If we can't find a cart item with this exact cart key, it's quantity must be 0.
                     */
                    $scalePriceQuantity = key_exists($cartKey, $_SESSION['lsShopCart']['items']) ? $_SESSION['lsShopCart']['items'][$cartKey]['quantity'] : 0;
                    break;

                case 'separatedVariants':
                    $arrSplitCartKey = ls_shop_generalHelper::splitProductVariantID($cartKey);
                    foreach ($_SESSION['lsShopCart']['items'] as $itemCartKey => $arrItemInfo) {
                        $arrSplitItemCartKey = ls_shop_generalHelper::splitProductVariantID($itemCartKey);
                        if (
                            $arrSplitCartKey['productID'] == $arrSplitItemCartKey['productID']
                            && $arrSplitCartKey['variantID'] == $arrSplitItemCartKey['variantID']
                        ) {
                            if ($obj_productOrVariant->_scalePriceQuantityDetectionAlwaysSeparateConfigurations && $arrSplitCartKey['configuratorHash'] != $arrSplitItemCartKey['configuratorHash']) {
                                continue;
                            }
                            $scalePriceQuantity = $scalePriceQuantity + $arrItemInfo['quantity'];
                        }
                    }
                    break;

                case 'separatedProducts':
                    $arrSplitCartKey = ls_shop_generalHelper::splitProductVariantID($cartKey);
                    foreach ($_SESSION['lsShopCart']['items'] as $itemCartKey => $arrItemInfo) {
                        $arrSplitItemCartKey = ls_shop_generalHelper::splitProductVariantID($itemCartKey);
                        if ($arrSplitCartKey['productID'] == $arrSplitItemCartKey['productID']) {
                            if ($obj_productOrVariant->_scalePriceQuantityDetectionAlwaysSeparateConfigurations && $arrSplitCartKey['configuratorHash'] != $arrSplitItemCartKey['configuratorHash']) {
                                continue;
                            }
                            $scalePriceQuantity = $scalePriceQuantity + $arrItemInfo['quantity'];
                        }
                    }
                    break;

                case 'separatedScalePriceKeywords':
                    $arrSplitCartKey = ls_shop_generalHelper::splitProductVariantID($cartKey);
                    foreach ($_SESSION['lsShopCart']['items'] as $itemCartKey => $arrItemInfo) {
                        $arrSplitItemCartKey = ls_shop_generalHelper::splitProductVariantID($itemCartKey);
                        if ($obj_productOrVariant->_scalePriceKeyword == $arrItemInfo['scalePriceKeyword']) {
                            if ($obj_productOrVariant->_scalePriceQuantityDetectionAlwaysSeparateConfigurations && $arrSplitCartKey['configuratorHash'] != $arrSplitItemCartKey['configuratorHash']) {
                                continue;
                            }
                            $scalePriceQuantity = $scalePriceQuantity + $arrItemInfo['quantity'];
                        }
                    }
                    break;
            }
        }
        return $scalePriceQuantity;
    }

    /*
     * Diese Funktion gibt Informationen zu einer Zahlungs- oder Versandoption zurück.
     * Es ist wichtig, dass das Handling von Zahlungs- und Versandoptionen absolut identisch
     * funtioniert, damit hier mit einer einzigen Funtkion gearbeitet werden kann. Es wäre
     * äußerst unpraktisch, wenn aufgrund kleiner Unterschiede zwei verschiedene Funktionen
     * verwendet werden müssten, die aber größtenteils redundant wären.
     */
    public static function getPaymentAndShippingMethodInfo($methodID = false, $type = false, $bln_throwExceptionOnMissingMethod = false)
    {
        if (!isset($GLOBALS['merconis_globals']['getPaymentAndShippingMethodInfo']['request_' . $methodID . '_' . $type])) {
            /** @var \PageModel $objPage */
            global $objPage;

            if (!$methodID || !$type) {
                return array();
            }

            $objMethod = \Database::getInstance()->prepare("SELECT * FROM `tl_ls_shop_" . $type . "_methods` WHERE `id` = ?")
                ->limit(1)
                ->execute($methodID);
            if (!$objMethod->numRows) {
                if ($bln_throwExceptionOnMissingMethod) {
                    throw new \Exception($type . ' method with id ' . $methodID . ' does not exist.');
                }
                ls_shop_checkoutData::getInstance()->resetSelectedPaymentAndShippingMethod();
                \Controller::reload();
            }

            $objMethod->first();
            $methodInfo = $objMethod->row();

            /*
             * Passende Sprachversion der Bezeichnung ermitteln und hinterlegen
             */
            /*
             * FIXME: $objPage ist hier NULL, also nicht verfügbar. Kann sein, dass dieser Fehler nirgends Auswirkungen hat. Um das festzustellen, müsste in einer älteren stable-Version
             * geprüft werden, ob auch dort $objPage nicht verfügbar ist. Es sollte dann noch sichergestellt werden, dass der Rückgabewert, den die alte getMultiLanguage-Funktion
             * bei Übergabe eines fehlerhaften Sprach-Werts geliefert hat, mit dem aktuellen Rückgabewert in diesem Fall identisch oder zumindest kompatibel ist.
             */
            $methodInfo['title'] = ls_shop_languageHelper::getMultiLanguage($methodInfo['id'], "tl_ls_shop_" . $type . "_methods_languages", array('title'), array($objPage->language));
            $methodInfo['description'] = ls_shop_languageHelper::getMultiLanguage($methodInfo['id'], "tl_ls_shop_" . $type . "_methods_languages", array('description'), array($objPage->language));

            /*
             * Get dynamically selected tax rates
             */
            if ($methodInfo['dynamicSteuersatzType'] && $methodInfo['dynamicSteuersatzType'] != 'none') {
                $methodInfo['steuersatz'] = ls_shop_generalHelper::getDynamicSteuersatzID($methodInfo['dynamicSteuersatzType']);
            }

            /*
             * Steuersatz in Prozent auslesen
             */
            $methodInfo['taxPercentage'] = ls_shop_generalHelper::getCurrentTax($methodInfo['steuersatz']);

            /*
             * ------------------
             * Define some variables that are used later, e.g. because they are explicitly offered as wildcards in the formula
             * or because they hold the total value used for fee calculation or price limit check.
             */
            $totalValueOfGoods = ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'][0] ? ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'][0] : 0;
            $totalWeightOfGoods = ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods'] ? ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods'] : 0;

            $totalValueOfCoupons = 0;
            foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $couponValue) {
                $totalValueOfCoupons = $totalValueOfCoupons + $couponValue[0];
            }
            $totalValueOfCoupons = $totalValueOfCoupons ? $totalValueOfCoupons : 0;

            $shippingFee = $type == 'shipping' ? 0 : (ls_shop_cartX::getInstance()->calculation['shippingFee'][0] ? ls_shop_cartX::getInstance()->calculation['shippingFee'][0] : 0);

            $totalValueOfGoodsPlusCoupons = ls_add($totalValueOfGoods, $totalValueOfCoupons);
            $totalValueOfGoodsPlusShipping = ls_add($totalValueOfGoods, $shippingFee);
            $totalValueOfGoodsPlusCouponsAndShipping = ls_add(ls_add($totalValueOfGoods, $totalValueOfCoupons), $shippingFee);

            $totalValueUsedForFeeCalculation = $totalValueOfGoods;

            $totalValueForFeeCalculationMode = 'onlyTotal';

            if (isset($methodInfo['feeAddCouponToValueOfGoods']) && $methodInfo['feeAddCouponToValueOfGoods'] && isset($methodInfo['feeAddShippingToValueOfGoods']) && $methodInfo['feeAddShippingToValueOfGoods']) {
                $totalValueUsedForFeeCalculation = $totalValueOfGoodsPlusCouponsAndShipping;
                $totalValueForFeeCalculationMode = 'totalPlusCouponsAndShipping';
            } else if (isset($methodInfo['feeAddCouponToValueOfGoods']) && $methodInfo['feeAddCouponToValueOfGoods']) {
                $totalValueUsedForFeeCalculation = $totalValueOfGoodsPlusCoupons;
                $totalValueForFeeCalculationMode = 'totalPlusCoupons';
            } else if (isset($methodInfo['feeAddShippingToValueOfGoods']) && $methodInfo['feeAddShippingToValueOfGoods']) {
                $totalValueUsedForFeeCalculation = $totalValueOfGoodsPlusShipping;
                $totalValueForFeeCalculationMode = 'totalPlusShipping';
            }

            /*
             * ------------------
             */

            /*
             * Ermitteln des Gebühren-Betrags
             */
            $methodInfo['feePrice'] = 0;
            switch ($methodInfo['feeType']) {
                case 'formula':
                    $priceFromFormula = 0;

                    if ($methodInfo['feeFormula']) {
                        $feeFormula = preg_replace('/##(.*)##/siU', '$$1', html_entity_decode($methodInfo['feeFormula']));
                        eval('$priceFromFormula = ' . $feeFormula . ';');
                    }
                    $methodInfo['feePrice'] = $methodInfo['feeFormulaResultConvertToDisplayPrice'] ? ls_shop_generalHelper::getDisplayPrice($priceFromFormula, $methodInfo['steuersatz'], false) : ls_shop_generalHelper::ls_roundPrice($priceFromFormula);
                    break;

                case 'percentaged':
                    /*
                     * Handelt es sich bei der Gebühr um einen Prozentwert, so wird dieser Prozentwert vom Gesamtwarenwert (ggf. + coupons + shipping)
                     * im Cart berechnet, der Display-Preis wird hier nicht ermittelt, da der Wert als prozentualer Anteil
                     * einer Summe aus Display-Prices bereits korrekt sein muss
                     */
                    if ($totalValueUsedForFeeCalculation) {
                        $methodInfo['feePrice'] = ls_shop_generalHelper::ls_roundPrice(
                            ls_mul(
                                ls_div(
                                    $totalValueUsedForFeeCalculation,
                                    100
                                ),
                                $methodInfo['feeValue']
                            )
                        );
                    }
                    break;

                case 'weight':
                case 'price':
                case 'weightAndPrice':
                    $feePriceWeight = 0;
                    $feePricePrice = 0;

                    if ($methodInfo['feeType'] == 'weight' || $methodInfo['feeType'] == 'weightAndPrice') {
                        /*
                         * convert the onedimensional array from the listwizard in a multidimensional array, setting an associative index in the second dimension and sort by the
                         * key 'weight'
                         */
                        $methodInfo['feeWeightValues'] = createMultidimensionalArray(createOneDimensionalArrayFromTwoDimensionalArray(json_decode($methodInfo['feeWeightValues'])), 2, 0, array('weight', 'price'), 'weight');

                        $matched = false;
                        /*
                         * Die definierten Gewichts-Obergrenzen werden durchlaufen und sobald eine Gewichts-Obergrenze
                         * gefunden wurde, die höher liegt als das kumulierte Gewicht der Produkte im Warenkorb, wird
                         * der zu dieser Gewichts-Obergrenze zugeordnete Preis verwendet.
                         */
                        foreach ($methodInfo['feeWeightValues'] as $weightValue) {
                            if ($weightValue['weight'] >= ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods']) {
                                $matched = true;
                                $feePriceWeight = ls_shop_generalHelper::getDisplayPrice($weightValue['price'], $methodInfo['steuersatz'], false);
                                break;
                            }
                        }

                        /*
                         * Wurde kein passender Preis gefunden, weil z. B. für eine sehr hohe Menge nichts mehr definiert wurde,
                         * so wird der höchste Preis (also der letzte im Durchlauf) genommen.
                         */
                        if (!$matched) {
                            $feePriceWeight = ls_shop_generalHelper::getDisplayPrice($weightValue['price'], $methodInfo['steuersatz'], false);
                        }
                    }

                    if ($methodInfo['feeType'] == 'price' || $methodInfo['feeType'] == 'weightAndPrice') {
                        /*
                         * convert the onedimensional array from the listwizard in a multidimensional array, setting an associative index in the second dimension and sort by the
                         * key 'cartPrice'
                         */
                        $methodInfo['feePriceValues'] = createMultidimensionalArray(createOneDimensionalArrayFromTwoDimensionalArray(json_decode($methodInfo['feePriceValues'])), 2, 0, array('cartPrice', 'price'), 'cartPrice');

                        $matched = false;
                        /*
                         * Die definierten Warenwert-Obergrenzen werden durchlaufen und sobald eine Warenwert-Obergrenze
                         * gefunden wurde, die höher liegt als der kumulierte Warenwert der Produkte im Warenkorb, wird
                         * der zu dieser Warenwert-Obergrenze zugeordnete Preis verwendet.
                         */
                        foreach ($methodInfo['feePriceValues'] as $priceValue) {
                            if ($priceValue['cartPrice'] >= $totalValueUsedForFeeCalculation) {
                                $matched = true;
                                $feePricePrice = ls_shop_generalHelper::getDisplayPrice($priceValue['price'], $methodInfo['steuersatz'], false);
                                break;
                            }
                        }

                        /*
                         * Wurde kein passender Preis gefunden, weil z. B. für eine sehr hohe Menge nichts mehr definiert wurde,
                         * so wird der höchste Preis (also der letzte im Durchlauf) genommen.
                         */
                        if (!$matched) {
                            $feePricePrice = ls_shop_generalHelper::getDisplayPrice($priceValue['price'], $methodInfo['steuersatz'], false);
                        }
                    }

                    $methodInfo['feePrice'] = ls_add($feePriceWeight, $feePricePrice);
                    break;

                case 'none':
                    $methodInfo['feePrice'] = ls_shop_generalHelper::getDisplayPrice(0, $methodInfo['steuersatz'], false);
                    break;

                default:
                    /*
                     * Handelt es sich bei der Gebühr um einen Fixbetrag, so wird hiervon einfach der Display-Preis ermittelt
                     */
                    $methodInfo['feePrice'] = ls_shop_generalHelper::getDisplayPrice($methodInfo['feeValue'], $methodInfo['steuersatz'], false);
                    break;
            }

            /*
             * Erstellen der Gebühren-Info
             */
            $methodInfo['feeInfo'] = '';

            $groupSettings = ls_shop_generalHelper::getGroupSettings4User();
            $sumPriceLabel = '';
            switch ($groupSettings['lsShopOutputPriceType']) {
                case 'brutto':
                    switch ($totalValueForFeeCalculationMode) {
                        case 'totalPlusCouponsAndShipping':
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText047-03'];
                            break;

                        case 'totalPlusCoupons':
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText047-01'];
                            break;

                        case 'totalPlusShipping':
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText047-02'];
                            break;

                        case 'onlyTotal':
                        default:
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText047'];
                            break;
                    }
                    break;

                case 'netto':
                    switch ($totalValueForFeeCalculationMode) {
                        case 'totalPlusCouponsAndShipping':
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText048-03'];
                            break;

                        case 'totalPlusCoupons':
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText048-01'];
                            break;

                        case 'totalPlusShipping':
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText048-02'];
                            break;

                        case 'onlyTotal':
                        default:
                            $sumPriceLabel = &$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText048'];
                            break;
                    }
                    break;
            }

            if ($methodInfo['feePrice']) {
                switch ($methodInfo['feeType']) {
                    case 'none':
                    case 'fixed':
                    case 'formula':
                        $methodInfo['feeInfo'] = '';
                        break;

                    case 'percentaged':
                        $methodInfo['feeInfo'] = ' (' . $methodInfo['feeValue'] . ' %)';
                        break;

                    case 'weight':
                        $methodInfo['feeInfo'] = ' (' . ls_shop_generalHelper::outputQuantity(ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods'], 2) . ' ' . $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] . ')';
                        break;

                    case 'price':
                        $methodInfo['feeInfo'] = ' (' . $sumPriceLabel . ' ' . ls_shop_generalHelper::outputPrice($totalValueUsedForFeeCalculation) . ')';
                        break;

                    case 'weightAndPrice':
                        $methodInfo['feeInfo'] = ' (' . $sumPriceLabel . ' ' . ls_shop_generalHelper::outputPrice($totalValueUsedForFeeCalculation) . ' ' . $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['and'] . ' ' . ls_shop_generalHelper::outputQuantity(ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods'], 2) . ' ' . $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] . ')';
                        break;
                }
            }

            if (isset($GLOBALS['MERCONIS_HOOKS']['modifyPaymentOrShippingMethodInfo']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyPaymentOrShippingMethodInfo'])) {
                foreach ($GLOBALS['MERCONIS_HOOKS']['modifyPaymentOrShippingMethodInfo'] as $mccb) {
                    $objMccb = \System::importStatic($mccb[0]);
                    $methodInfo = $objMccb->{$mccb[1]}($methodInfo, $type);
                }
            }

            $GLOBALS['merconis_globals']['getPaymentAndShippingMethodInfo']['request_' . $methodID . '_' . $type] = $methodInfo;
        }

        return $GLOBALS['merconis_globals']['getPaymentAndShippingMethodInfo']['request_' . $methodID . '_' . $type];
    }


    /*
     * Diese Funktion gibt Informationen zu einer Zahlungsmethode zurück
     */
    public static function getPaymentMethodInfo($paymentMethodID = false, $bln_throwExceptionOnMissingMethod = false)
    {
        $paymentMethodInfo = ls_shop_generalHelper::getPaymentAndShippingMethodInfo($paymentMethodID, 'payment', $bln_throwExceptionOnMissingMethod);
        return $paymentMethodInfo;
    }

    /*
     * Diese Funktion gibt Informationen zu einer Versandmethode zurück
     */
    public static function getShippingMethodInfo($shippingMethodID = false, $bln_throwExceptionOnMissingMethod = false)
    {
        $shippingMethodInfo = ls_shop_generalHelper::getPaymentAndShippingMethodInfo($shippingMethodID, 'shipping', $bln_throwExceptionOnMissingMethod);
        return $shippingMethodInfo;
    }

    /*
     * Prüft, ob eine Zahlungs- oder Versandart erlaubt ist
     */
    public static function checkIfPaymentOrShippingMethodIsAllowed($method = false, $what = 'payment')
    {
        if ($method === false) {
            throw new \Exception('method called with wrong parameter type');
        }

        $groupInfo = ls_shop_generalHelper::getGroupSettings4User();

        /*
         * Ist die Methode nicht veröffentlicht? False!
         */
        if (!$method['published']) {
            return false;
        }

        /*
         * Ist die Methode für die aktuelle Gruppe nicht erlaubt? False!
         */
        if ($method['excludedGroups']) {
            $excludedGroups = deserialize($method['excludedGroups']);
            if (in_array($groupInfo['id'], $excludedGroups)) {
                return false;
            }
        }

        /*
         * Ist die Methode für das Gewicht nicht erlaubt? False!
         */
        if ($method['weightLimitMin'] != 0 && ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods'] < $method['weightLimitMin']) {
            return false;
        }
        if ($method['weightLimitMax'] != 0 && ls_shop_cartX::getInstance()->calculation['totalWeightOfGoods'] > $method['weightLimitMax']) {
            return false;
        }

        /*
         * Ist die Methode für den Warenwert nicht erlaubt? False!
         */
        $totalValueOfGoods = ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'][0] ? ls_shop_cartX::getInstance()->calculation['totalValueOfGoods'][0] : 0;

        $totalValueOfCoupons = 0;
        foreach (ls_shop_cartX::getInstance()->calculation['couponValues'] as $couponValue) {
            $totalValueOfCoupons = $totalValueOfCoupons + $couponValue[0];
        }
        $totalValueOfCoupons = $totalValueOfCoupons ? $totalValueOfCoupons : 0;

        $shippingFee = $what == 'shipping' ? 0 : (ls_shop_cartX::getInstance()->calculation['shippingFee'][0] ? ls_shop_cartX::getInstance()->calculation['shippingFee'][0] : 0);

        $totalValueOfGoodsPlusCoupons = ls_add($totalValueOfGoods, $totalValueOfCoupons);
        $totalValueOfGoodsPlusShipping = ls_add($totalValueOfGoods, $shippingFee);
        $totalValueOfGoodsPlusCouponsAndShipping = ls_add(ls_add($totalValueOfGoods, $totalValueOfCoupons), $shippingFee);

        $totalValueUsedForPriceLimit = $totalValueOfGoods;

        if (isset($method['priceLimitAddCouponToValueOfGoods']) && $method['priceLimitAddCouponToValueOfGoods'] && isset($method['priceLimitAddShippingToValueOfGoods']) && $method['priceLimitAddShippingToValueOfGoods']) {
            $totalValueUsedForPriceLimit = $totalValueOfGoodsPlusCouponsAndShipping;
        } else if (isset($method['priceLimitAddCouponToValueOfGoods']) && $method['priceLimitAddCouponToValueOfGoods']) {
            $totalValueUsedForPriceLimit = $totalValueOfGoodsPlusCoupons;
        } else if (isset($method['priceLimitAddShippingToValueOfGoods']) && $method['priceLimitAddShippingToValueOfGoods']) {
            $totalValueUsedForPriceLimit = $totalValueOfGoodsPlusShipping;
        }

        if ($method['priceLimitMin'] != 0 && $totalValueUsedForPriceLimit < $method['priceLimitMin']) {
            return false;
        }
        if ($method['priceLimitMax'] != 0 && $totalValueUsedForPriceLimit > $method['priceLimitMax']) {
            return false;
        }

        /*
         * Ist die Methode für das Land des Kunden nicht erlaubt? False!
         */
        // Auslesen des Kunden-Landes, wobei bei der Prüfung einer Zahlungsmethode explizit das "main"-Land angefordert wird
        $customerCountry = ls_shop_generalHelper::getCustomerCountry($what == 'payment' ? 'main' : '', true);
        $countries = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $method['countries']);
        if (
            !$customerCountry
            || (!$method['countriesAsBlacklist'] && !in_array($customerCountry, $countries))
            || ($method['countriesAsBlacklist'] && in_array($customerCountry, $countries))
        ) {
            return false;
        }

        if (isset($GLOBALS['MERCONIS_HOOKS']['checkIfPaymentOrShippingMethodIsAllowed']) && is_array($GLOBALS['MERCONIS_HOOKS']['checkIfPaymentOrShippingMethodIsAllowed'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['checkIfPaymentOrShippingMethodIsAllowed'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                if ($objMccb->{$mccb[1]}($method, $what) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /*
     * Diese Funktion ermittelt auf Basis der bekannten Informationen die günstigste verfügbare
     * Zahlungs-/Versandmethode
     */
    public static function getCheapestAvailableMethodID($type = false)
    {
        if (!$type) {
            return 0;
        }

        $cheapestMethod = null;

        $methodTable = $type == 'payment' ? 'tl_ls_shop_payment_methods' : 'tl_ls_shop_shipping_methods';

        $objMethods = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`" . $methodTable . "`
				WHERE		`published` = '1'
				ORDER BY	`sorting` ASC
			")
            ->execute();

        while ($objMethods->next()) {
            $tmpMethodInfo = ls_shop_generalHelper::getPaymentAndShippingMethodInfo($objMethods->id, $type);
            if (!ls_shop_generalHelper::checkIfPaymentOrShippingMethodIsAllowed($tmpMethodInfo, $type)) {
                continue;
            }
            if (!is_array($cheapestMethod) || $cheapestMethod['feePrice'] > $tmpMethodInfo['feePrice']) {
                $cheapestMethod = $tmpMethodInfo;
            }
        }

        return $cheapestMethod['id'];
    }

    /*
     * Gibt die Steuersätze als Options-Array zurück. Wird als Options-Callback in DCA-Konfigurationen verwendet
     */
    public static function getSteuersatzOptions()
    {
        $objSteuersaetze = \Database::getInstance()->prepare("
				SELECT	*
				FROM	`tl_ls_shop_steuersaetze`
			")
            ->execute();

        $arrSteuersatzOptions = array();

        if ($objSteuersaetze->numRows) {
            while ($objSteuersaetze->next()) {
                $arrSteuersatzOptions[$objSteuersaetze->id] = $objSteuersaetze->title;
            }
        }
        return $arrSteuersatzOptions;
    }

    /*
     * Returns tax classes as an options array. Dynamic tax classes (with ##customCalculation## wildcards)
     * are omitted. This function is used as an options callback in DCA configurations.
     */
    public static function getNonDynamicSteuersatzOptions()
    {
        $objSteuersaetze = \Database::getInstance()->prepare("
				SELECT	*
				FROM	`tl_ls_shop_steuersaetze`
				WHERE	`steuerProzentPeriod1` NOT LIKE '%#%'
					AND	`steuerProzentPeriod2` NOT LIKE '%#%'
			")
            ->execute();

        $arrSteuersatzOptions = array();

        if ($objSteuersaetze->numRows) {
            while ($objSteuersaetze->next()) {
                $arrSteuersatzOptions[$objSteuersaetze->id] = $objSteuersaetze->title;
            }
        }
        return $arrSteuersatzOptions;
    }

    public static function getOtherFieldsInFormAsOptions(\DataContainer $dc)
    {
        $arr_formFieldsAsOptions = array(
            0 => '-'
        );

        $obj_dbres_fieldsInForm = \Database::getInstance()
            ->prepare("
					SELECT		*
					FROM		`tl_form_field`
					WHERE		`pid` = ?
						AND 	`name` != ''
				")
            ->execute(
                $dc->activeRecord->pid
            );

        while ($obj_dbres_fieldsInForm->next()) {
            $arr_formFieldsAsOptions[$obj_dbres_fieldsInForm->id] = $obj_dbres_fieldsInForm->name;
        }

        return $arr_formFieldsAsOptions;
    }

    /*
     * Prüft, ob eine Zahlungsart erlaubt ist
     */
    public static function checkIfPaymentMethodIsAllowed($method)
    {
        if (!is_array($method)) {
            $method = ls_shop_generalHelper::getPaymentMethodInfo($method);
        }

        return ls_shop_generalHelper::checkIfPaymentOrShippingMethodIsAllowed($method, 'payment');
    }

    /*
     * Prüft, ob eine Versandart erlaubt ist
     */
    public static function checkIfShippingMethodIsAllowed($method)
    {
        if (!is_array($method)) {
            $method = ls_shop_generalHelper::getShippingMethodInfo($method);
        }

        return ls_shop_generalHelper::checkIfPaymentOrShippingMethodIsAllowed($method, 'shipping');
    }

    /*
     * Gibt alle Produktattribute als Array mit der ID als Key zurück
     */
    public static function getProductAttributes($str_languageToUse = '')
    {
        /** @var \PageModel $objPage */
        global $objPage;

        $str_languageToUse = $str_languageToUse ? $str_languageToUse : ($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage());

        if (!isset($GLOBALS['merconis_globals']['productAttributes'][$str_languageToUse])) {
            $objAttributes = \Database::getInstance()->prepare("
					SELECT		*
					FROM		`tl_ls_shop_attributes`
					ORDER BY	`id` ASC
				")
                ->execute();
            $arrAttributes = $objAttributes->fetchAllAssoc();
            $GLOBALS['merconis_globals']['productAttributes'][$str_languageToUse] = array();
            foreach ($arrAttributes as $attribute) {
                $attribute['title'] = ls_shop_languageHelper::getMultiLanguage($attribute['id'], 'tl_ls_shop_attributes_languages', array('title'), array($str_languageToUse));
                $GLOBALS['merconis_globals']['productAttributes'][$str_languageToUse][$attribute['id']] = $attribute;
            }
        }
        return $GLOBALS['merconis_globals']['productAttributes'][$str_languageToUse];
    }

    /*
     * Gibt alle Produktattribut-Werte als Array mit der ID als Key zurück
     */
    public static function getAttributeValues($attributeID = 0, $blnUncached = false, $str_languageToUse = '')
    {
        /** @var \PageModel $objPage */
        global $objPage;

        $str_languageToUse = $str_languageToUse ? $str_languageToUse : ($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage());

        if ($blnUncached || !isset($GLOBALS['merconis_globals']['productAttributeValues'][$attributeID][$str_languageToUse])) {
            $objAttributeValues = \Database::getInstance()->prepare("
					SELECT		*
					FROM		`tl_ls_shop_attribute_values`
				" . ($attributeID ? "WHERE `pid` = ?" : "") . "
					ORDER BY	`sorting` ASC
				"
            );

            if ($attributeID) {
                $objAttributeValues = $objAttributeValues->execute($attributeID);
            } else {
                $objAttributeValues = $objAttributeValues->execute();
            }

            $arrAttributeValues = $objAttributeValues->fetchAllAssoc();
            $GLOBALS['merconis_globals']['productAttributeValues'][$attributeID][$str_languageToUse] = array();
            foreach ($arrAttributeValues as $attributeValue) {
                $attributeValue['title'] = ls_shop_languageHelper::getMultiLanguage($attributeValue['id'], 'tl_ls_shop_attribute_values_languages', array('title'), array($str_languageToUse));
                $GLOBALS['merconis_globals']['productAttributeValues'][$attributeID][$str_languageToUse][$attributeValue['id']] = $attributeValue;
            }
        }
        return $GLOBALS['merconis_globals']['productAttributeValues'][$attributeID][$str_languageToUse];
    }

    public static function getProductAttributeValueIds($arr_productAttributesValues = array())
    {
        $arr_attributeValueIds = array();
        foreach ($arr_productAttributesValues as $arr_attributeValuePair) {
            if (!$arr_attributeValuePair[0]) {
                continue;
            }

            if (!isset($arr_attributeValueIds[$arr_attributeValuePair[0]])) {
                $arr_attributeValueIds[$arr_attributeValuePair[0]] = array();
            }
            $arr_attributeValueIds[$arr_attributeValuePair[0]][] = $arr_attributeValuePair[1];
        }
        return $arr_attributeValueIds;
    }

    /*
     * Change the structure of the attributes/values information because the way it's saved is not
     * the best structure for further processing.
     */
    public static function processProductAttributesValues($arrProductAttributesValues = array(), $str_languageToUse = '')
    {
        $productAttributes = ls_shop_generalHelper::getProductAttributes($str_languageToUse);
        $productAttributeValues = ls_shop_generalHelper::getAttributeValues(0, false, $str_languageToUse);

        $attributesValuesProcessed = array();

        if (is_array($arrProductAttributesValues)) {
            foreach ($arrProductAttributesValues as $arrProductAttributeValue) {
                if (!$arrProductAttributeValue[0]) {
                    continue;
                }
                if (!isset($attributesValuesProcessed[$arrProductAttributeValue[0]])) {
                    $attributesValuesProcessed[$arrProductAttributeValue[0]] = array();
                }
                $attributesValuesProcessed[$arrProductAttributeValue[0]][] = array(
                    'attributeID' => $arrProductAttributeValue[0],
                    'attributeTitle' => $productAttributes[$arrProductAttributeValue[0]]['title'],
                    'attributeAlias' => $productAttributes[$arrProductAttributeValue[0]]['alias'],
                    'valueID' => $arrProductAttributeValue[1],
                    'valueTitle' => $productAttributeValues[$arrProductAttributeValue[1]]['title'],
                    'valueAlias' => $productAttributeValues[$arrProductAttributeValue[1]]['alias']
                );
            }
        }

        return $attributesValuesProcessed;
    }

    /*
     * Takes a processed attributes values array and creates a string
     * representation of the attributes and values.
     */
    public static function createAttributesString($arr_attributesValuesProcessed = array())
    {
        $str_attributesString = '';
        if (!is_array($arr_attributesValuesProcessed) || !count($arr_attributesValuesProcessed)) {
            return $str_attributesString;
        }

        foreach ($arr_attributesValuesProcessed as $arr_attributes) {
            if (is_array($arr_attributes)) {
                foreach ($arr_attributes as $arr_value) {
                    if (!$arr_value['attributeTitle'] || !$arr_value['valueTitle']) {
                        continue;
                    }
                    $str_attributesString .= $arr_value['attributeTitle'] . ': ' . $arr_value['valueTitle'] . ', ';
                }
            }
        }
        $str_attributesString = substr($str_attributesString, 0, -2);

        return $str_attributesString;
    }

    /*
     * Gibt ein Configurator-Objekt zurück und sorgt dafür, dass für jeden Configurator nur ein Objekt existiert,
     * selbst wenn das Objekt mehrmals nacheinander benötigt wird. Das "Eindeutigkeitsmerkmal" eines Configurator-Objektes
     * ist die ProductVariantID des Produktes bzw. der Variante, für die es verwendet wird sowie ein optional übergebener
     * Configurator-Hash.
     */
    public static function getObjConfigurator($configuratorID = false, $productVariantID = false, $arrProductMainData = false, $configuratorHash = '', &$objProductOrVariant = null)
    {
        if ($configuratorID === false || !$productVariantID || !is_array($arrProductMainData)) {
            throw new \Exception('insufficient parameters given');
        }

        $cacheKey = $configuratorID . '_' . $productVariantID . ($configuratorHash ? '|' . $configuratorHash : '');

        if (!isset($GLOBALS['merconis_globals']['configuratorObjs'])) {
            $GLOBALS['merconis_globals']['configuratorObjs'] = array();
        }

        if (!isset($GLOBALS['merconis_globals']['configuratorObjs'][$cacheKey]) || !is_object($GLOBALS['merconis_globals']['configuratorObjs'][$cacheKey])) {
            $GLOBALS['merconis_globals']['configuratorObjs'][$cacheKey] = new ls_shop_productConfigurator($configuratorID, $productVariantID, $arrProductMainData, $configuratorHash, $objProductOrVariant);
        }

        return $GLOBALS['merconis_globals']['configuratorObjs'][$cacheKey];
    }

    /*
     * Diese Funktion erstellt die Versandkosten-Info
     */
    public static function getVersandkostenInfo()
    {
        /*
         * Erstellen des Versand-Info-Links (wird dann in einer globalen Variable abgelegt)
         */
        ls_shop_languageHelper::getLanguagePage('ls_shop_shippingInfoPages');
        return '<a href="' . $GLOBALS['merconis_globals']['ls_shop_shippingInfoPagesUrl'] . '">' . ($GLOBALS['TL_CONFIG']['ls_shop_versandkostenType'] == 'excl' ? $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText003'] : $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText004']) . '</a>';
    }

    /*
     * Diese Funktion gibt den Pfad eines in den Shop-Grundeinstellungen (TL_CONFIG) hinterlegten System-Bildes zurück
     */
    public static function getSystemImage($imgName)
    {
        $tmpImgName = ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_systemImages_' . $imgName]);
        if (isset($tmpImgName) && is_file($tmpImgName)) {
            return $tmpImgName;
        } else {
            return false;
        }
    }

    /*
     * Diese Funktion wird benötigt, um Arrays, die zu wenige Keys besitzen, weitere Keys mit einem vordefinierten Wert hinzuzufügen.
     * Wird im Zusammenhang mit __call-Methoden verwendet
     */
    public static function setArrayLength($arr, $numRequiredKeys = 0, $defaultValue = false)
    {
        for ($i = 0; $i < $numRequiredKeys; $i++) {
            if (!isset($arr[$i])) {
                $arr[$i] = $defaultValue;
            }
        }
        return $arr;
    }

    /*
     * Diese Funktion gibt die Darstellungsvorgaben für eine spezielle Seite zurück
     */
    public static function getOutputDefinition($pageID = false, $mode = 'standard')
    {
        /** @var \PageModel $objPage */
        global $objPage;

        $outputDefinition = array(
            'outputDefinitionID' => 0,

            'overviewTemplate' => 'template_productOverview_01',
            'overviewSorting' => 'name_asc',
            'overviewUserSorting' => false,
            'overviewUserSortingFields' => array(),
            'overviewPagination' => 0,

            'overviewTemplate_crossSeller' => 'template_productOverview_01',
            'overviewSorting_crossSeller' => 'name_asc',
            'overviewUserSorting_crossSeller' => false,
            'overviewUserSortingFields_crossSeller' => array(),
            'overviewPagination_crossSeller' => 0
        );

        if (!$pageID) {
            $pageID = $objPage->id;
        }

        if (!isset($GLOBALS['merconis_globals']['outputDefinitions'][$pageID])) {
            $outputDefinitionSet = ls_shop_generalHelper::getOutputDefinitionSetForPageRecursive($pageID);

            if (!$outputDefinitionSet) {
                $outputDefinitionSet = $GLOBALS['TL_CONFIG']['ls_shop_output_definitionset'];
            }

            if (!$outputDefinitionSet) {
                return $outputDefinition;
            }

            $objOutputDefinitionSet = \Database::getInstance()->prepare("
					SELECT			*
					FROM			`tl_ls_shop_output_definitions`
					WHERE			`id` = ?
				")
                ->execute($outputDefinitionSet);

            if (!$objOutputDefinitionSet->numRows) {
                return $outputDefinition;
            }

            $arrOutputDefinitionSet = $objOutputDefinitionSet->fetchAllAssoc();
            $arrOutputDefinitionSet = $arrOutputDefinitionSet[0];

            $outputDefinition['outputDefinitionID'] = $arrOutputDefinitionSet['id'];


            $outputDefinition['overviewTemplate'] = $arrOutputDefinitionSet['lsShopProductTemplate'];
            $outputDefinition['overviewSorting'] = $arrOutputDefinitionSet['lsShopProductOverviewSorting'];

            if ($arrOutputDefinitionSet['lsShopProductOverviewSortingKeyOrAlias']) {
                $outputDefinition['overviewSorting'] = str_replace('KEYORALIAS', '=' . $arrOutputDefinitionSet['lsShopProductOverviewSortingKeyOrAlias'], $outputDefinition['overviewSorting']);
            }

            $outputDefinition['overviewUserSorting'] = $arrOutputDefinitionSet['lsShopProductOverviewUserSorting'];
            $outputDefinition['overviewUserSortingFields'] = json_decode($arrOutputDefinitionSet['lsShopProductOverviewUserSortingFields']);
            if (is_array($outputDefinition['overviewUserSortingFields'])) {
                foreach ($outputDefinition['overviewUserSortingFields'] as $k => $v) {
                    $outputDefinition['overviewUserSortingFields'][$k] = array();
                    $outputDefinition['overviewUserSortingFields'][$k]['value'] = $v[1] ? str_replace('KEYORALIAS', '=' . $v[1], $v[0]) : $v[0];
                    $outputDefinition['overviewUserSortingFields'][$k]['label'] = $v[2] ? $v[2] : $outputDefinition['overviewUserSortingFields'][$k]['value'];
                }
            }
            $outputDefinition['overviewPagination'] = $arrOutputDefinitionSet['lsShopProductOverviewPagination'];


            $outputDefinition['overviewTemplate_crossSeller'] = $arrOutputDefinitionSet['lsShopProductTemplate_crossSeller'];
            $outputDefinition['overviewSorting_crossSeller'] = $arrOutputDefinitionSet['lsShopProductOverviewSorting_crossSeller'];

            if ($arrOutputDefinitionSet['lsShopProductOverviewSortingKeyOrAlias_crossSeller']) {
                $outputDefinition['overviewSorting_crossSeller'] = str_replace('KEYORALIAS', '=' . $arrOutputDefinitionSet['lsShopProductOverviewSortingKeyOrAlias_crossSeller'], $outputDefinition['overviewSorting_crossSeller']);
            }

            $outputDefinition['overviewUserSorting_crossSeller'] = $arrOutputDefinitionSet['lsShopProductOverviewUserSorting_crossSeller'];
            $outputDefinition['overviewUserSortingFields_crossSeller'] = json_decode($arrOutputDefinitionSet['lsShopProductOverviewUserSortingFields_crossSeller']);
            if (is_array($outputDefinition['overviewUserSortingFields_crossSeller'])) {
                foreach ($outputDefinition['overviewUserSortingFields_crossSeller'] as $k => $v) {
                    $outputDefinition['overviewUserSortingFields_crossSeller'][$k] = array();
                    $outputDefinition['overviewUserSortingFields_crossSeller'][$k]['value'] = $v[1] ? str_replace('KEYORALIAS', '=' . $v[1], $v[0]) : $v[0];
                    $outputDefinition['overviewUserSortingFields_crossSeller'][$k]['label'] = $v[2] ? $v[2] : $outputDefinition['overviewUserSortingFields_crossSeller'][$k]['value'];
                }
            }
            $outputDefinition['overviewPagination_crossSeller'] = $arrOutputDefinitionSet['lsShopProductOverviewPagination_crossSeller'];

            $GLOBALS['merconis_globals']['outputDefinitions'][$pageID] = $outputDefinition;
        }

        if ($mode == 'complete') {
            $returnOutputDefinition = $GLOBALS['merconis_globals']['outputDefinitions'][$pageID];
        } else {
            $returnOutputDefinition = array(
                'outputDefinitionID' => $GLOBALS['merconis_globals']['outputDefinitions'][$pageID]['outputDefinitionID'],
                'overviewTemplate' => $GLOBALS['merconis_globals']['outputDefinitions'][$pageID]['overviewTemplate' . ($mode == 'standard' ? '' : '_' . $mode)],
                'overviewSorting' => $GLOBALS['merconis_globals']['outputDefinitions'][$pageID]['overviewSorting' . ($mode == 'standard' ? '' : '_' . $mode)],
                'overviewUserSorting' => $GLOBALS['merconis_globals']['outputDefinitions'][$pageID]['overviewUserSorting' . ($mode == 'standard' ? '' : '_' . $mode)],
                'overviewUserSortingFields' => $GLOBALS['merconis_globals']['outputDefinitions'][$pageID]['overviewUserSortingFields' . ($mode == 'standard' ? '' : '_' . $mode)],
                'overviewPagination' => $GLOBALS['merconis_globals']['outputDefinitions'][$pageID]['overviewPagination' . ($mode == 'standard' ? '' : '_' . $mode)]
            );
        }

        $returnOutputDefinition['outputDefinitionMode'] = $mode;

        return $returnOutputDefinition;
    }

    /*
     * Diese Funktion gibt das für eine Seite passende OutputDefinitionSet zurück
     */
    public static function getOutputDefinitionSetForPageRecursive($pageID = false)
    {
        if ($pageID === false) {
            return false;
        }

        $objData = \Database::getInstance()->prepare("
				SELECT		`lsShopOutputDefinitionSet`,
							`pid`
				FROM		`tl_page`
				WHERE		`id` = ?
			")
            ->execute($pageID);

        if (!$objData->numRows) {
            return false;
        }

        $objData->first();

        if ($objData->lsShopOutputDefinitionSet) {
            return $objData->lsShopOutputDefinitionSet;
        } else {
            return self::getOutputDefinitionSetForPageRecursive($objData->pid);
        }
    }

    /*
     * this function returns an array which holds the ids of outputDefinitions
     * used in the shop settings and in pages
     */
    public static function getOutputDefinitionsCurrentlyInUse()
    {
        if (!isset($GLOBALS['merconis_globals']['arrOutputDefinitionIDs'])) {
            // Add the id of the outputDefinition used in the shop settings
            $GLOBALS['merconis_globals']['arrOutputDefinitionIDs'] = array($GLOBALS['TL_CONFIG']['ls_shop_output_definitionset']);

            $objPages = \Database::getInstance()->prepare("
					SELECT		`lsShopOutputDefinitionSet`
					FROM		`tl_page`
				")
                ->execute();

            if ($objPages->numRows) {
                while ($objPages->next()) {
                    if ($objPages->lsShopOutputDefinitionSet && !in_array($objPages->lsShopOutputDefinitionSet, $GLOBALS['merconis_globals']['arrOutputDefinitionIDs'])) {
                        $GLOBALS['merconis_globals']['arrOutputDefinitionIDs'][] = $objPages->lsShopOutputDefinitionSet;
                    }
                }
            }
        }
        return $GLOBALS['merconis_globals']['arrOutputDefinitionIDs'];
    }

    /*
     * Diese Funktion gibt die Einstellungen des für die übergebene Variante oder das übergebene Produkt
     * passenden deliveryInfoSets zurück, also alle Informationen zum Handling von Lagerbestand
     * und Lieferzeit.
     *
     * Ist der Variante oder dem Produkt kein spzielles deliveryInfoSet hinterlegt, so wird das entsprechende
     * deliveryInfoSet der nächst höheren Instanz (Variante->Produkt->Shop-Grundeinstellungen) zurückgegeben
     */
    public static function getDeliveryInfo($id = 0, $type = 'product', $blnUseMainLanguage = false)
    {
        /** @var \PageModel $objPage */
        global $objPage;

        if (!$id) {
            return false;
        }

        $deliveryInfoSetID = false;

        /*
         * Soll die DeliveryInfo für eine Variante zurückgegeben werden, so wird zunächst ausgelesen,
         * was der Variante selbst hinterlegt ist. Ist ihr kein deliverySet explizit hinterlegt,
         * so wird $id auf die übergeordnete Produkt-ID (pid) gesetzt, um das Auslesen der entsprechenden
         * Information für das Produkt zu ermöglichen
         */
        if ($type == 'variant') {
            if (!isset($GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_01_' . $id]) || !$GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_01_' . $id]) {
                $objData = \Database::getInstance()->prepare("
						SELECT		`lsShopVariantDeliveryInfoSet`,
									`pid`
						FROM		`tl_ls_shop_variant`
						WHERE		`id` = ?
					")
                    ->execute($id);
                $GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_01_' . $id] = $objData->numRows ? $objData->first()->lsShopVariantDeliveryInfoSet : false;
            }
            $deliveryInfoSetID = $GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_01_' . $id];
            if (!$deliveryInfoSetID) {
                $id = $objData->pid;
            }
        }

        /*
         * Soll die DeliveryInfo für ein Produkt zurückgegeben werden oder ist zumindest bislang
         * noch keine deliveryInfoSetID für die Variante ermittelt worden, so wird diese Information
         * nun für das Produkt ausgelesen
         */
        if ($type == 'product' || !$deliveryInfoSetID) {
            if (!isset($GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_02_' . $id]) || !$GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_02_' . $id]) {
                $objData = \Database::getInstance()->prepare("
						SELECT		`lsShopProductDeliveryInfoSet`
						FROM		`tl_ls_shop_product`
						WHERE		`id` = ?
					")
                    ->execute($id);
                $GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_02_' . $id] = $objData->numRows ? $objData->first()->lsShopProductDeliveryInfoSet : false;
            }
            $deliveryInfoSetID = $GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_02_' . $id];
        }

        /*
         * Konnte bislang weder für die Variante noch für das Produkt eine deliveryInfoSetID ermittelt
         * werden, so wird die Grundeinstellung verwendet.
         */
        if (!$deliveryInfoSetID) {
            $deliveryInfoSetID = $GLOBALS['TL_CONFIG']['ls_shop_delivery_infoSet'];
        }

        /*
         * Der Datensatz zur ermittelten deliveryInfoSetID wird nun ausgelesen und zurückgegeben
         */
        if (!isset($GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_03_' . $deliveryInfoSetID]) || !$GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_03_' . $deliveryInfoSetID]) {
            $objDeliveryInfoSet = \Database::getInstance()->prepare("
					SELECT			*
					FROM			`tl_ls_shop_delivery_info`
					WHERE			`id` = ?
				")
                ->execute($deliveryInfoSetID);

            if (!$objDeliveryInfoSet->numRows) {
                return false;
            }

            $GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_03_' . $deliveryInfoSetID] = $objDeliveryInfoSet->fetchAllAssoc();
        }
        $deliveryInfoSet = $GLOBALS['merconis_globals']['DBResults']['getDeliveryInfo_03_' . $deliveryInfoSetID];

        $arrDeliveryInfoSetMultilanguage = ls_shop_languageHelper::getMultiLanguage($deliveryInfoSetID, 'tl_ls_shop_delivery_info_languages', array('title', 'deliveryTimeMessageWithSufficientStock', 'deliveryTimeMessageWithInsufficientStock'), array($blnUseMainLanguage ? ls_shop_languageHelper::getFallbackLanguage() : $objPage->language));

        $deliveryInfoSet[0]['title'] = $arrDeliveryInfoSetMultilanguage['title'];
        $deliveryInfoSet[0]['deliveryTimeMessageWithSufficientStock'] = $arrDeliveryInfoSetMultilanguage['deliveryTimeMessageWithSufficientStock'];
        $deliveryInfoSet[0]['deliveryTimeMessageWithInsufficientStock'] = $arrDeliveryInfoSetMultilanguage['deliveryTimeMessageWithInsufficientStock'];
        return $deliveryInfoSet[0];
    }

    public static function sendStockNotification($stock, $obj_productOrVariant)
    {
        if ($obj_productOrVariant->_deliveryInfo['alertWhenLowerThanMinimumStock'] && $stock < $obj_productOrVariant->_deliveryInfo['minimumStock']) {

            if (!\Validator::isEmail(\Idna::encodeEmail($GLOBALS['TL_CONFIG']['ls_shop_ownEmailAddress']))) {
                // log an error if the address is invalid
                \System::log('MERCONIS: Stock notification could not be sent because address "' . $GLOBALS['TL_CONFIG']['ls_shop_ownEmailAddress'] . '" is invalid', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
                return;
            }

            $objEmail = new \Email();
            $objEmail->from = $GLOBALS['TL_CONFIG']['ls_shop_ownEmailAddress'];

            $text = $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['stockNotificationText'];
            $text = preg_replace('/\{\{productName\}\}/siU', get_class($obj_productOrVariant) == 'ls_shop_variant' ? $obj_productOrVariant->_productTitle . ' - ' . $obj_productOrVariant->_title : $obj_productOrVariant->_title, $text);
            $text = preg_replace('/\{\{currentStock\}\}/siU', $stock, $text);
            $text = preg_replace('/\{\{minimumStock\}\}/siU', $obj_productOrVariant->_deliveryInfo['minimumStock'], $text);
            $text = preg_replace('/\{\{quantityUnit\}\}/siU', $obj_productOrVariant->_quantityUnit, $text);
            $text = preg_replace('/\{\{productCode\}\}/siU', $obj_productOrVariant->_code, $text);
            $objEmail->text = $text;
            $objEmail->subject = $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['stockNotificationSubject'];

            try {
                $objEmail->sendTo($GLOBALS['TL_CONFIG']['ls_shop_ownEmailAddress']);
            } catch (\Exception $e) {
            }
        }
    }

    /*
     * Diese Funktion wird beim Aufbauen des Suchindex aufgerufen und ergänzt das übergebene Array der in den Index aufzunehmenden Seiten/URLs
     * um die ebenfalls aufzunehmenden Produkt-Seiten/-URLs.
     */
    public static function getSearchablePages($arrPages, $intRoot = 0, $blnIsSitemap = false)
    {
        $objProducts = \Database::getInstance()->prepare("
				SELECT			*
				FROM			`tl_ls_shop_product`
				WHERE			`published` = 1
			")
            ->execute();

        while ($objProducts->next()) {
            $whereConditionPages = '';
            $whereConditionValues = array();

            $objProducts->pages = deserialize($objProducts->pages);
            if (!is_array($objProducts->pages) || !count($objProducts->pages)) {
                continue;
            }
            foreach ($objProducts->pages as $page) {
                if ($whereConditionPages) {
                    $whereConditionPages .= ' OR ';
                }
                $whereConditionPages .= "`id` = ?";
                $whereConditionValues[] = $page;
            }
            if (!$whereConditionPages || !count($whereConditionValues)) {
                continue;
            }

            $time = time();
            $objPagesForProduct = \Database::getInstance()->prepare("
					SELECT			id,
									alias
					FROM 			tl_page
					WHERE			(" . $whereConditionPages . ")
						AND			(start = '' OR start < " . $time . ")
						AND			(stop = '' OR stop > " . $time . ")
						AND			published = 1
						AND			noSearch != 1" . ($blnIsSitemap ? " AND sitemap!='map_never'" : "")
            )
                ->execute($whereConditionValues);

            // Determine domain
            if (!$objPagesForProduct->numRows) {
                continue;
            } else {
                while ($objPagesForProduct->next()) {
                    $domain = \Environment::get('base');
                    $arrLanguagePages = ls_shop_languageHelper::getLanguagePages($objPagesForProduct->id);
                    foreach ($arrLanguagePages as $languagePageInfo) {
                        $objPageForProduct = \PageModel::findWithDetails($languagePageInfo['id']);
                        if ($objPageForProduct->domain != '') {
                            $domain = (\Environment::get('ssl') ? 'https://' : 'http://') . $objPageForProduct->domain . TL_PATH . '/';
                        }
                        $arrPages[] = $domain . \Controller::generateFrontendUrl($objPageForProduct->row(), '/product/' . $objProducts->alias, $objPageForProduct->language);
                    }
                }
            }
        }
        return $arrPages;
    }

    public static function addToLastSeenProducts($productID)
    {
        if (!isset($_SESSION['lsShop']['lastSeenProducts'])) {
            $_SESSION['lsShop']['lastSeenProducts'] = array();
        }

        array_insert($_SESSION['lsShop']['lastSeenProducts'], 0, array($productID));
    }

    /*
     * Diese Funktion entfernt doppelte Werte aus dem Array und stellt dabei sicher, dass die Reihenfolge nicht verändert wird,
     * wobei die Keys neu durchnummeriert werden.
     */
    public static function ls_array_unique($arr1)
    {
        $arr2 = array();
        foreach ($arr1 as $value) {
            if (!in_array($value, $arr2)) {
                $arr2[] = $value;
            }
        }
        return $arr2;
    }

    public static function getAlternativeCrossSellerOptions($arg1)
    {
        $objCrossSellers = \Database::getInstance()->prepare("SELECT * FROM `tl_ls_shop_cross_seller` WHERE `published` = '1'")
            ->execute();

        $arrCrossSellerOptions = array('' => '-');

        if ($objCrossSellers->numRows) {
            while ($objCrossSellers->next()) {
                if ($objCrossSellers->id == $arg1->activeRecord->id) {
                    continue;
                }
                $arrCrossSellerOptions[$objCrossSellers->id] = $objCrossSellers->title;
            }
        }
        return $arrCrossSellerOptions;
    }

    public static function simpleHTMLOutputForBE(\DataContainer $dc)
    {
        $arrData = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field];

        return sprintf('%s<div class="beWidgetSimpleHTMLOutput">%s</div>%s',
            $arrData['eval']['outputBefore'],
            $arrData['eval']['output'],
            $arrData['eval']['outputAfter']);
    }

    public static function rawOutputForBackendDCA(\DataContainer $dc)
    {
        $arrData = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field];
        return $arrData['eval']['output'];
    }

    /*
     * Diese Funktion dient dem Zweck, die aktuelle URL ohne
     * bestimmte oder alle GET-Parameter zu erhalten.
     */
    public static function getUrl($blnEncode = true, $removeKeys = array(), $keepKeys = array())
    {
        return \LeadingSystems\Helpers\ls_helpers_controller::getUrl($blnEncode, $removeKeys, $keepKeys);
    }

    /*
     * Diese Funktion liefert den Wizard
     */
    public static function beValuePickerWizard(\DataContainer $dc)
    {
        $headline = isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['merconis_picker_headline']) ? $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['merconis_picker_headline'] : '';
        $requestedTable = $dc->table;
        $requestedValue = $dc->field;
        return ' ' . \Image::getHtml('bundles/leadingsystemsmerconis/images/inputHelp.gif', $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['inputHelp'], 'style="vertical-align:top;cursor:pointer" onclick="ls_shop_backend.pickValue(\'ctrl_' . $dc->inputName . '\', \'' . $requestedTable . '\', \'' . $requestedValue . '\', \'' . specialchars($headline) . '\')"');
    }

    public static function createValueList($requestedTable = false, $requestedValue = false, $requestedLanguage = false)
    {
        if (!$requestedValue || !$requestedValue) {
            return '';
        }

        $requestedValue = $requestedValue . ($requestedLanguage ? "_" . $requestedLanguage : "");

        $objValues = \Database::getInstance()->prepare("
				SELECT		`" . $requestedValue . "`
				FROM		`" . $requestedTable . "`
				GROUP BY	`" . $requestedValue . "`
				ORDER BY	`" . $requestedValue . "` ASC
			")
            ->execute();

        $strOptions = '';
        while ($objValues->next()) {
            $strOptions .= sprintf('<option value="%s"%s>%s</option>', specialchars($objValues->{$requestedValue}), (($objValues->{$requestedValue} == \Input::get('value')) ? ' selected="selected"' : ''), specialchars($objValues->{$requestedValue}));
        }
        return $strOptions;
    }

    /*
     * Diese Funktion wird als Hook (getContentElement) aufgerufen und prüft, ob für ein
     * CTE eine Ausgabebedingung hinterlegt ist und gibt einen Leerstring zurück, falls
     * eine eventuell hinterlegte Bedingung nicht zutrifft.
     */
    public static function conditionalCTEOutput($objElement, $strBuffer)
    {
        if (TL_MODE == 'BE' || !$objElement->lsShopOutputCondition) {
            return $strBuffer;
        }

        switch ($objElement->lsShopOutputCondition) {
            case 'always':
                return $strBuffer;
                break;

            case 'onlyInOverview':
                if (\Input::get('product')) {
                    return '';
                }
                break;

            case 'onlyInSingleview':
                if (!\Input::get('product')) {
                    return '';
                }
                break;

            case 'onlyIfCartNotEmpty':
                if (ls_shop_cartX::getInstance()->isEmpty) {
                    return '';
                }
                break;

            case 'onlyIfCartEmpty':
                if (!ls_shop_cartX::getInstance()->isEmpty) {
                    return '';
                }
                break;
        }

        return $strBuffer;
    }

    /*
     * Diese Funktion erwartet als Parameter einen Produkt- oder Varianten-Code sowie das Hauptbild als String
     * und die weiteren Bilder als Array.
     *
     * Zurückgegeben wird ein Array, das alle Bilder des Produktes enthält und dabei das Hauptbild auf erster
     * Position platziert und die automatisch ermittelten Standardbilder berücksichtigt.
     */
    public static function getAllProductImages($productOrVariantCode = false, $mainImage = '', $moreImages = array())
    {
        $globalCacheKey = sha1($productOrVariantCode . $mainImage . serialize($moreImages));
        if (!isset($GLOBALS['merconis_globals']['getAllProductImages'][$globalCacheKey])) {
            $standardImages = ls_shop_generalHelper::getImagesFromStandardFolder($productOrVariantCode);
            if (!is_array($moreImages)) {
                $moreImages = deserialize($moreImages);
                if (!is_array($moreImages)) {
                    $moreImages = array();
                }
            }

            $allImages = array();

            if ($mainImage) {
                $tmpImgPath = ls_getFilePathFromVariableSources($mainImage);
                if ($tmpImgPath) {
                    $allImages[] = $tmpImgPath;
                }
            }

            if (is_array($moreImages)) {
                foreach ($moreImages as $image) {
                    if (!in_array($image, $allImages)) {
                        $tmpImgPath = ls_getFilePathFromVariableSources($image);
                        if (!$tmpImgPath) {
                            continue;
                        }
                        $allImages[] = $tmpImgPath;
                    }
                }
            }

            if (is_array($standardImages)) {
                foreach ($standardImages as $image) {
                    if (!in_array($image, $allImages)) {
                        $allImages[] = $image;
                    }
                }
            }

            $GLOBALS['merconis_globals']['getAllProductImages'][$globalCacheKey] = $allImages;
        }

        return $GLOBALS['merconis_globals']['getAllProductImages'][$globalCacheKey];
    }

    public static function checkForUniqueProductCode($varValue, \DataContainer $dc)
    {
        if ($varValue == '') {
            throw new \Exception($GLOBALS['TL_LANG']['MSC']['ls_shop']['validationMessages']['productCode01']);
        }

        /*
         * Prüfen, ob die Artikelnummer in der Produkttabelle vorkommt, Exception aber nur werfen, wenn die Artikelnummer nicht bereits dem aktuell
         * aufgerufenen Datensatz hinterlegt ist
         */
        $objProductCode = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_product WHERE `lsShopProductCode`=?")
            ->execute($varValue);
        while ($objProductCode->next()) {
            if ($dc->table == 'tl_ls_shop_product' && $objProductCode->id == $dc->id) {
                continue;
            }
            throw new \Exception($GLOBALS['TL_LANG']['MSC']['ls_shop']['validationMessages']['productCode02']);
        }

        /*
         * Prüfen, ob die Artikelnummer in der Variantentabelle vorkommt, Exception aber nur werfen, wenn die Artikelnummer nicht bereits dem aktuell
         * aufgerufenen Datensatz hinterlegt ist
         */
        $objProductCodeVariant = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_variant WHERE `lsShopVariantCode`=?")
            ->execute($varValue);
        while ($objProductCodeVariant->next()) {
            if ($dc->table == 'tl_ls_shop_variant' && $objProductCodeVariant->id == $dc->id) {
                continue;
            }
            throw new \Exception($GLOBALS['TL_LANG']['MSC']['ls_shop']['validationMessages']['productCode02']);
        }

        return $varValue;
    }

    /*
     * this function returns an array which holds the ids of all attributes
     * and values that are currently used by one or more variants
     */
    public static function getPaymentOrShippingMethodsUsedInOrders($str_what = 'payment')
    {
        $str_what = in_array($str_what, array('payment', 'shipping')) ? $str_what : 'payment';
        $arr_methodIDs = array();

        $obj_dbres_orders = \Database::getInstance()->prepare("
				SELECT		`" . $str_what . "Method_id` AS `int_methodID`
				FROM		`tl_ls_shop_orders`
				GROUP BY	`" . $str_what . "Method_id`
			")
            ->execute();

        while ($obj_dbres_orders->next()) {
            $arr_methodIDs[] = $obj_dbres_orders->int_methodID;
        }

        return $arr_methodIDs;
    }

    /*
     * this function returns an array which holds the ids of all attributes
     * and values that are currently used by one or more variants
     */
    public static function getAttributesAndValuesCurrentlyInUse()
    {
        if (!isset($GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse'])) {
            $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse'] = array(
                'arrAttributeIDs' => array(),
                'arrValueIDs' => array()
            );
            $objVariants = \Database::getInstance()->prepare("
					SELECT		`lsShopProductVariantAttributesValues`
					FROM		`tl_ls_shop_variant`
				")
                ->execute();

            if ($objVariants->numRows) {
                while ($objVariants->next()) {
                    $arrAttributesAndValues = ls_shop_generalHelper::processProductAttributesValues(deserialize($objVariants->lsShopProductVariantAttributesValues));
                    foreach ($arrAttributesAndValues as $arrAttributeAndValues) {
                        if (is_array($arrAttributeAndValues)) {
                            foreach ($arrAttributeAndValues as $arrAttributeAndValue) {
                                if (!in_array($arrAttributeAndValue['attributeID'], $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrAttributeIDs'])) {
                                    $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrAttributeIDs'][] = $arrAttributeAndValue['attributeID'];
                                }
                                if (!in_array($arrAttributeAndValue['valueID'], $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrValueIDs'])) {
                                    $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrValueIDs'][] = $arrAttributeAndValue['valueID'];
                                }
                            }
                        }
                    }
                }
            }

            $objProducts = \Database::getInstance()->prepare("
					SELECT		`lsShopProductAttributesValues`
					FROM		`tl_ls_shop_product`
				")
                ->execute();

            if ($objProducts->numRows) {
                while ($objProducts->next()) {
                    $arrAttributesAndValues = ls_shop_generalHelper::processProductAttributesValues(deserialize($objProducts->lsShopProductAttributesValues));
                    foreach ($arrAttributesAndValues as $arrAttributeAndValues) {
                        if (is_array($arrAttributeAndValues)) {
                            foreach ($arrAttributeAndValues as $arrAttributeAndValue) {
                                if (!in_array($arrAttributeAndValue['attributeID'], $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrAttributeIDs'])) {
                                    $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrAttributeIDs'][] = $arrAttributeAndValue['attributeID'];
                                }
                                if (!in_array($arrAttributeAndValue['valueID'], $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrValueIDs'])) {
                                    $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse']['arrValueIDs'][] = $arrAttributeAndValue['valueID'];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $GLOBALS['merconis_globals']['attributesAndValuesCurrentlyInUse'];
    }

    /*
     * this function returns an array which holds the ids of configurators
     * used in products and variants
     */
    public static function getConfiguratorsCurrentlyInUse()
    {
        if (!isset($GLOBALS['merconis_globals']['arrConfiguratorIDs'])) {
            $GLOBALS['merconis_globals']['arrConfiguratorIDs'] = array();

            $objProducts = \Database::getInstance()->prepare("
					SELECT		`configurator`
					FROM		`tl_ls_shop_product`
				")
                ->execute();
            if ($objProducts->numRows) {
                while ($objProducts->next()) {
                    if ($objProducts->configurator && !in_array($objProducts->configurator, $GLOBALS['merconis_globals']['arrConfiguratorIDs'])) {
                        $GLOBALS['merconis_globals']['arrConfiguratorIDs'][] = $objProducts->configurator;
                    }
                }
            }

            $objVariants = \Database::getInstance()->prepare("
					SELECT		`configurator`
					FROM		`tl_ls_shop_variant`
				")
                ->execute();
            if ($objVariants->numRows) {
                while ($objVariants->next()) {
                    if ($objVariants->configurator && !in_array($objVariants->configurator, $GLOBALS['merconis_globals']['arrConfiguratorIDs'])) {
                        $GLOBALS['merconis_globals']['arrConfiguratorIDs'][] = $objVariants->configurator;
                    }
                }
            }
        }
        return $GLOBALS['merconis_globals']['arrConfiguratorIDs'];
    }

    public static function getPaymentOrShippingMethods($what = 'payment')
    {
        $arrMethods = array();

        if ($what != 'payment' && $what != 'shipping') {
            return $arrMethods;
        }

        $objMethods = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_" . $what . "_methods`
				WHERE		`published` = 1
				ORDER BY	`sorting` ASC
			")
            ->execute();

        if (!$objMethods->numRows) {
            return $arrMethods;
        }

        while ($objMethods->next()) {
            switch ($what) {
                case 'payment':
                    if (ls_shop_generalHelper::checkIfPaymentMethodIsAllowed($objMethods->id)) {
                        $arrMethods[$objMethods->id] = ls_shop_generalHelper::getPaymentAndShippingMethodInfo($objMethods->id, $what);
                    }
                    break;

                case 'shipping':
                    if (ls_shop_generalHelper::checkIfShippingMethodIsAllowed($objMethods->id)) {
                        $arrMethods[$objMethods->id] = ls_shop_generalHelper::getPaymentAndShippingMethodInfo($objMethods->id, $what);
                    }
                    break;

                default:
                    return $arrMethods;
                    break;
            }
        }

        if (isset($GLOBALS['MERCONIS_HOOKS']['sortPaymentOrShippingMethods']) && is_array($GLOBALS['MERCONIS_HOOKS']['sortPaymentOrShippingMethods'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['sortPaymentOrShippingMethods'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $arrMethods = $objMccb->{$mccb[1]}($arrMethods, $what);
            }
        }

        return $arrMethods;
    }

    public static function getMainLanguagePagesAsOptions($addBlankOption = false)
    {
        $options = array();

        if ($addBlankOption) {
            $options[0] = array('label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText055'], 'value' => 0);
        }

        $objPages = \Database::getInstance()
            ->prepare("
					SELECT		*
					FROM		`tl_page`
					ORDER BY	`sorting` ASC
				")
            ->execute();

        if (!$objPages->numRows) {
            return $options;
        }

        while ($objPages->next()) {
            // Check whether root page is fallback language or not and only then add the page to the options array
            $objPageDetails = \PageModel::findWithDetails($objPages->id);
            $objRootPage = \Database::getInstance()->prepare("
					SELECT * FROM `tl_page` WHERE `id` = ?
				")
                ->limit(1)
                ->execute($objPageDetails->rootId);

            if ($objRootPage->fallback) {
                $options[$objPages->id] = array('label' => $objPages->title, 'value' => $objPages->id);
            }
        }
        return $options;
    }

    public static function handleMandatoryOnCondition(\Widget $objWidget, $intId, $arrForm)
    {
        $obj_dbres_mandatoryOnConditionSettings = \Database::getInstance()
            ->prepare("
					SELECT	`lsShop_mandatoryOnConditionField`,
							`lsShop_mandatoryOnConditionValue`
					FROM	`tl_form_field`
					WHERE	`id` = ?
				")
            ->limit(1)
            ->execute(
                $objWidget->id
            );

        $obj_dbres_mandatoryOnConditionSettings->first();

        if ($obj_dbres_mandatoryOnConditionSettings->lsShop_mandatoryOnConditionField) {
            if (\Input::post(ls_shop_generalHelper::getFormFieldNameForFormFieldId($obj_dbres_mandatoryOnConditionSettings->lsShop_mandatoryOnConditionField)) != $obj_dbres_mandatoryOnConditionSettings->lsShop_mandatoryOnConditionValue) {
                $objWidget->{'data-misc-required'} = $objWidget->mandatory;
                $objWidget->mandatory = '';
            }
        }

        return $objWidget;
    }

    /*
     * Diese Funktion gibt die in einem Array enthaltenen Formular-Daten so zurück, dass sie für eine
     * Ausgabe einfach verwendet werden können. Felder, deren Wert eine ID o. Ä. ist und die sich deshalb
     * nicht direkt ausgeben lassen, werden entsprechend verarbeitet, sodass ein ausgabefähiger Wert
     * zurückgegeben wird.
     *
     * Zu beachten ist die Array-Struktur, mit der die Formular-Daten geliefert werden müssen:
     *
     * In erster Ebene werden die Namen der Formularfelder als Key verwendet. Das Array für ein Formularfeld
     * enthält drei Keys, wobei "name" ebenfalls den Namen des Formularfelds enthält, "arrData" die vollständigen
     * Informationen zur Konfiguration des Formularfelds, wobei "type" von besonderer Bedeutung ist, und "value"
     * den für dieses Formularfeld bestätigten Wert.

    Array
    (
    [firstname] => Array
    (
    [name] => firstname
    [arrData] => Array
    (
    [id] => 44
    [pid] => 6
    [sorting] => 384
    [tstamp] => 1351873944
    [invisible] =>
    [type] => text
    [name] => firstname
    [label] => {{iflng::en}}Firstname{{iflng}}{{iflng::de}}Vorname{{iflng}}:
    [text] =>
    [html] =>
    [options] =>
    [mandatory] => 1
    [rgxp] =>
    [maxlength] => 0
    [size] => a:2:{i:0;i:4;i:1;i:40;}
    [fSize] => 0
    [multiple] =>
    [mSize] => 0
    [extensions] => jpg,jpeg,gif,png,pdf,doc,xls,ppt
    [storeFile] =>
    [uploadFolder] =>
    [useHomeDir] =>
    [doNotOverwrite] =>
    [fsType] => fsStart
    [value] =>
    [placeholder] =>
    [class] =>
    [accesskey] =>
    [tabindex] => 0
    [addSubmit] =>
    [slabel] =>
    [imageSubmit] =>
    [singleSRC] =>
    )

    [value] => Volker
    )
    )

     */
    public static function getArrDataReview($arrInput = array(), $bln_getOnlyOriginalOptionValues = false)
    {
        $arrOutput = array();
        if (!is_array($arrInput)) {
            return $arrOutput;
        }

        if (isset($GLOBALS['MERCONIS_HOOKS']['modifyDataReviewInput']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyDataReviewInput'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['modifyDataReviewInput'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $arrInput = $objMccb->{$mccb[1]}($arrInput);
            }
        }

        foreach ($arrInput as $fieldName => $fieldInfo) {
            switch ($fieldInfo['arrData']['type']) {
                /*
                 * Bei Feldern mit mehreren Optionen aber einer eindeutigen Auswahl (Select-Felder, Radio-Buttons) wird der Wert der gewählten Option
                 * in dessen Label, also den menschenlesbaren Wert, übersetzt.
                 */
                case 'select':
                case 'radio':
                    if (!$bln_getOnlyOriginalOptionValues) {
                        $tmpArrOptions = deserialize($fieldInfo['arrData']['options']);
                        foreach ($tmpArrOptions as $arrOption) {
                            if ($arrOption['value'] == $fieldInfo['value']) {
                                $fieldInfo['value'] = $arrOption['label'];
                                break;
                            }
                        }
                    }
                    break;

                /*
                 * Bei Feldern mit mehreren Optionen und möglicher Mehrfachauswahl (Checkbox-Menüs) wird der Wert der gewählten Optionen in deren
                 * Labels, also deren menschenlesbaren Wert, übersetzt. Ist nur eine einfache Auswahl erfolgt - egal, ob nur eine Option zur Auswahl stand
                 * oder mehrere -, so wird einfach ihr übersetzter Wert zurückgegeben. Ist eine Mehrfachauswahl erfolgt, so werden die übersetzten
                 * Werte in Quotes gesetzt und kommagetrennt aneinander gefügt. Die Gesamtauswahl wird so immer durch einen String repräsentiert.
                 */
                case 'checkbox':
                    $quoteStart = '';
                    $quoteEnd = '';
                    $tmpArrOptions = deserialize($fieldInfo['arrData']['options']);

                    if (!is_array($fieldInfo['value'])) {
                        $fieldInfo['value'] = array($fieldInfo['value']);
                    }

                    if (!$bln_getOnlyOriginalOptionValues) {
                        foreach ($fieldInfo['value'] as $key => $value) {
                            foreach ($tmpArrOptions as $arrOption) {
                                if ($arrOption['value'] == $value) {
                                    $fieldInfo['value'][$key] = $arrOption['label'];
                                    break;
                                }
                            }
                        }
                    }

                    if (count($fieldInfo['value']) > 1) {
                        $quoteStart = '"';
                        $quoteEnd = '"';
                    }

                    $tmpValueCombined = '';
                    foreach ($fieldInfo['value'] as $value) {
                        if ($tmpValueCombined) {
                            $tmpValueCombined .= ', ';
                        }
                        $tmpValueCombined .= $quoteStart . $value . $quoteEnd;
                    }

                    $fieldInfo['value'] = $tmpValueCombined;
                    break;

                default:
                    if ($bln_getOnlyOriginalOptionValues) {
                        continue 2;
                    }
                    break;
            }

            $arrOutput[$fieldName] = $fieldInfo['value'];
        }

        if (isset($GLOBALS['MERCONIS_HOOKS']['modifyDataReviewOutput']) && is_array($GLOBALS['MERCONIS_HOOKS']['modifyDataReviewOutput'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['modifyDataReviewOutput'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $arrOutput = $objMccb->{$mccb[1]}($arrOutput);
            }
        }

        return $arrOutput;
    }

    /*
     * Diese Funktion prüft, ob die von einem Formular erfassten und gespeicherten Daten valide sind. Hier passiert im Grunde dasselbe,
     * was die contaoeigene Funktion bei der Verarbeitung von Formulardaten auch direkt macht. Allerdings geht es hier darum, nachträglich
     * prüfen zu können, ob Daten, die zu einem früheren Zeitpunkt mit einem Formular gesammelt wurden, noch immer valide sind.
     *
     * DIESE FUNKTION BEANTWORTET DIE FRAGE:
     * "WÄREN DIE VORLIEGENDEN DATEN VALIDE, WENN SIE JETZT MIT DEM IHRER ERFASSUNG ZU GRUNDE LIEGENDEN FORMULAR ERNEUT ERFASST WÜRDEN?"
     * ODER KONKRET
     * "SIND DIE VORLIEGENDEN DATEN JETZT IM MOMENT NOCH VALIDE?"
     *
     * Ein beispielhafter Anwendungszweck:
     * Mit einem Formular werden die Checkout-Daten erfasst. Nur wenn das Formular erfolgreich validiert werden konnte, werden die Daten
     * in der Session gespeichert. Wenn nun einige Minuten später der tatsächliche Checkout stattfinden soll, müssen die Daten, die erfasst wurden,
     * definitiv valide sein. Hat sich weder am Formular bzw. seinen Feldern eine Einstellung geändert und wurden die in der Session abgelegten
     * Daten nicht manipuliert, so könnte davon ausgegangen werden, dass die Daten valide sind.
     *
     * In der Praxis ist es allerdings nicht akzeptabel, sich darauf zu verlassen. Zwischen Datenerfassung und Datenverwendung könnten sich
     * Formulareinstellungen eben doch ändern (durch Admin-Eingriff im Contao-Backend) und es könnten auch jederzeit in der Session gespeicherte
     * Daten durch irgendeine Aktion - sowohl absichtlich als auch als Resultat eines Fehlers - verändert oder entfernt werden.
     *
     *
     * Zu beachten ist die Array-Struktur, mit der die Formular-Daten geliefert werden müssen:
     *
     * In erster Ebene werden die Namen der Formularfelder als Key verwendet. Das Array für ein Formularfeld
     * enthält drei Keys, wobei "name" ebenfalls den Namen des Formularfelds enthält, "arrData" die vollständigen
     * Informationen zur Konfiguration des Formularfelds, wobei "type" von besonderer Bedeutung ist, und "value"
     * den für dieses Formularfeld bestätigten Wert.

    Array
    (
    [firstname] => Array
    (
    [name] => firstname
    [arrData] => Array
    (
    [id] => 44
    [pid] => 6
    [sorting] => 384
    [tstamp] => 1351873944
    [invisible] =>
    [type] => text
    [name] => firstname
    [label] => {{iflng::en}}Firstname{{iflng}}{{iflng::de}}Vorname{{iflng}}:
    [text] =>
    [html] =>
    [options] =>
    [mandatory] => 1
    [rgxp] =>
    [maxlength] => 0
    [size] => a:2:{i:0;i:4;i:1;i:40;}
    [fSize] => 0
    [multiple] =>
    [mSize] => 0
    [extensions] => jpg,jpeg,gif,png,pdf,doc,xls,ppt
    [storeFile] =>
    [uploadFolder] =>
    [useHomeDir] =>
    [doNotOverwrite] =>
    [fsType] => fsStart
    [value] =>
    [placeholder] =>
    [class] =>
    [accesskey] =>
    [tabindex] => 0
    [addSubmit] =>
    [slabel] =>
    [imageSubmit] =>
    [singleSRC] =>
    )

    [value] => Volker
    )
    )

     */
    public static function validateCollectedFormData($arrValidateData, $formID)
    {
        if (TL_MODE == 'BE') {
            return true;
        }

        // Sofern noch kein Formularfeld als invalide identifiziert worden ist, gilt der Gesamtzustand als valide
        $blnIsValid = true;

        // Auslesen der Fomularinformationen des Formulars, mit dem die zu prüfenden Daten erfasst wurden.
        $objForm = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_form`
				WHERE		`id` = ?
			")
            ->execute($formID);
        if (!$objForm->numRows) {
            /*
             * Kann kein Formular gefunden werden, so wird dieser nicht erwünschte Fall im Errorlog festgehalten,
             * die Formularvalidierung aber als erfolgreich abgeschlossen (keine Formularfelder, die befüllt werden
             * müssen, die "Nicht-Eingaben" passen also im Grunde zum "Nicht-Formular")
             */
            error_log('Required form not found (formID: ' . $formID . ')');
            return $blnIsValid;
//				throw new \Exception('Required form not found (formID: '.$formID.')');
        }
        $objForm->first();

        /*
         * Durchlaufen der gesammelten Daten und Erstellen eines Widgets für jedes Feld
         */
        foreach ($arrValidateData as $fieldName => $arrFieldData) {
            $strClass = '\\' . $GLOBALS['TL_FFL'][$arrFieldData['arrData']['type']];

            // Continue if the class is not defined
            if (!class_exists($strClass)) {
                continue;
            }

            $arrFieldData['arrData']['decodeEntities'] = true;
            $arrFieldData['arrData']['allowHtml'] = $objForm->allowTags;
            $arrFieldData['arrData']['tableless'] = $objForm->tableless;

            /*
             * -->
             * Consider the "mandatoryOnCondition" settings
             */
            if (
                $arrFieldData['arrData']['lsShop_mandatoryOnConditionField']
                && $arrFieldData['arrData']['lsShop_mandatoryOnConditionValue'] != $arrValidateData[ls_shop_generalHelper::getFormFieldNameForFormFieldId($arrFieldData['arrData']['lsShop_mandatoryOnConditionField'])]['value']
            ) {
                $arrFieldData['arrData']['mandatory'] = false;
            }
            /*
             * <--
             */

            $objWidget = new $strClass($arrFieldData['arrData']);
            $objWidget->required = $arrFieldData['arrData']['mandatory'] ? true : false;

            /*
             * Da an dieser Stelle keine per POST übermittelten Werte geprüft werden sollen, sondern die als Parameter übergebenen Werte,
             * werden diese Werte hier temporär als POST-Werte gesetzt. Die Widget-
             * Validierung prüft nämlich nur POST-Werte. Nach der Validierung wird der ursprüngliche POST-Wert wieder zurück-
             * gesetzt, da die Validierung keinen Einfluss auf tatsächlich gesendete Daten haben soll.
             */
            $tmpPostValue = isset($_POST[$fieldName]) ? \Input::post($fieldName) : null;
            \Input::setPost($fieldName, $arrValidateData[$fieldName]['value']);

            $objWidget->validate();

            \Input::setPost($fieldName, $tmpPostValue);

            if ($objWidget->hasErrors()) {
                $blnIsValid = false;
            }
        }
        return $blnIsValid;
    }

    /*
     * Diese Funktion ermittelt die eingaberelevanten Felder eines Formulars. Hierfür wird das Formular analysiert.
     * Nur Felder, die nicht "invisible" sind und deren "name"-Attribut gesetzt ist, sind relevant.
     *
     * Als Parameter werden die ID des zu prüfenden Formulars erwartet sowie ggf. das Daten-Array, falls es
     * bereits analyziert und ggf. sogar schon mit Formulareingaben gefüllt wurde.
     */
    public static function analyzeRequiredDataFields($formID, $arrData = array(), $considerDefaultFormFieldValues = false)
    {
        $objFormFields = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_form_field`
				WHERE		`pid` = ?
					AND		`invisible` != 1
					AND		`name` != ''
				ORDER BY	`sorting`
			")
            ->execute($formID);

        if (!$objFormFields->numRows) {
            return array();
        }

        /*
         * Um sicherzustellen, dass ein Feld, welches zuvor als "requiredDataField" ermittelt wurde,
         * mittlerweile aber nicht mehr dazu gehört (nicht mehr in DB vorhanden, ausgeblendet, etc.), auch tatsächlich
         * aus dem Array entfernt wird, bzw. dass die Informationen zu einem Feld auch tatsächlich aktuell sind,
         * wird der bisherige Zustand des Arrays temporär festgehalten
         * und das Original-Array dann komplett geleert und unter Bezugnahme auf das temporäre Array (zur Beibehaltung eines ) erneut gefüllt.
         */
        $tmpArrDataOld = $arrData;
        $arrData = array();

        while ($objFormFields->next()) {
            $value = isset($tmpArrDataOld[$objFormFields->name]['value']) ? $tmpArrDataOld[$objFormFields->name]['value'] : '';

            /*
             * Berücksichtigen der Feld-Default-Werte
             */
            if (!$value && $considerDefaultFormFieldValues) {
                switch ($objFormFields->type) {
                    case 'select':
                        $arrOptions = deserialize($objFormFields->options);
                        foreach ($arrOptions as $option) {
                            if ($option['default']) {
                                if ($objFormFields->multiple) {
                                    if (!is_array($value)) {
                                        $value = array();
                                    }
                                    $value[] = $option['value'];
                                } else {
                                    $value = $option['value'];
                                }
                            }
                        }
                        break;

                    case 'radio':
                        $arrOptions = deserialize($objFormFields->options);
                        foreach ($arrOptions as $option) {
                            if ($option['default']) {
                                $value = $option['value'];
                            }
                        }
                        break;

                    case 'checkbox':
                        $arrOptions = deserialize($objFormFields->options);
                        foreach ($arrOptions as $option) {
                            if ($option['default']) {
                                if (!is_array($value)) {
                                    $value = array();
                                }
                                $value[] = $option['value'];
                            }
                        }
                        break;
                }
            }

            $arrData[$objFormFields->name] = array(
                'name' => $objFormFields->name,
                'arrData' => $objFormFields->row(),
                'value' => $value
            );
        }

        return $arrData;
    }

    public static function getDefaultConfiguratorHash($configuratorID)
    {
        $objConfiguratorData = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_configurator`
				WHERE		`id` = ?
			")
            ->execute($configuratorID);

        if ($objConfiguratorData->numRows) {
            $objConfiguratorData->first();
            return sha1(serialize(ls_shop_generalHelper::analyzeRequiredDataFields($objConfiguratorData->form, array(), true)));
        } else {
            return 'unknownConfiguratorHash';
        }
    }

    /*
     * This function provides data required by javascript.
     */
    public static function ls_shop_provideInfosForJS()
    {
        // get the url of the merconis ajax page
        $str_ajaxUrl = ls_shop_languageHelper::getLanguagePage('ls_shop_ajaxPages');
        $int_minicartID = isset($GLOBALS['TL_CONFIG']['ls_shop_miniCartModuleID']) ? $GLOBALS['TL_CONFIG']['ls_shop_miniCartModuleID'] : 0;

        ob_start();
        ?>
        <script type="text/javascript">
            window.addEvent('domready', function () {
                <?php
                /*
                 * LSJS ->
                 */
                if (isset($GLOBALS['lsjs4c_globals']['lsjs4c_loadLsjs']) && $GLOBALS['lsjs4c_globals']['lsjs4c_loadLsjs']) {
                ?>
                if (lsjs.__appHelpers.merconisApp !== undefined && lsjs.__appHelpers.merconisApp !== null) {
                    lsjs.__appHelpers.merconisApp.obj_config.REQUEST_TOKEN = '<?php echo REQUEST_TOKEN; ?>';
                    lsjs.__appHelpers.merconisApp.obj_config.str_ajaxUrl = '<?php echo $str_ajaxUrl; ?>';
                    lsjs.__appHelpers.merconisApp.obj_config.int_minicartID = '<?php echo $int_minicartID; ?>';
                    lsjs.__appHelpers.merconisApp.start();
                }
                <?php
                }
                ?>
            });
        </script>
        <?php
        $GLOBALS['TL_HEAD'][] = ob_get_clean();
    }

    public static function getStatusValues()
    {
        $arrStatusValues = array(
            'status01' => ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $GLOBALS['TL_CONFIG']['ls_shop_orderStatusValues01']),
            'status02' => ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $GLOBALS['TL_CONFIG']['ls_shop_orderStatusValues02']),
            'status03' => ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $GLOBALS['TL_CONFIG']['ls_shop_orderStatusValues03']),
            'status04' => ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $GLOBALS['TL_CONFIG']['ls_shop_orderStatusValues04']),
            'status05' => ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $GLOBALS['TL_CONFIG']['ls_shop_orderStatusValues05'])
        );
        return $arrStatusValues;
    }

    public static function getStatusValues01AsOptions()
    {
        $arrStatusValues = ls_shop_generalHelper::getStatusValues();
        $arrOptions = $arrStatusValues['status01'];
        return $arrOptions;
    }

    public static function getStatusValues02AsOptions()
    {
        $arrStatusValues = ls_shop_generalHelper::getStatusValues();
        $arrOptions = $arrStatusValues['status02'];
        return $arrOptions;
    }

    public static function getStatusValues03AsOptions()
    {
        $arrStatusValues = ls_shop_generalHelper::getStatusValues();
        $arrOptions = $arrStatusValues['status03'];
        return $arrOptions;
    }

    public static function getStatusValues04AsOptions()
    {
        $arrStatusValues = ls_shop_generalHelper::getStatusValues();
        $arrOptions = $arrStatusValues['status04'];
        return $arrOptions;
    }

    public static function getStatusValues05AsOptions()
    {
        $arrStatusValues = ls_shop_generalHelper::getStatusValues();
        $arrOptions = $arrStatusValues['status05'];
        return $arrOptions;
    }

    public static function ls_replaceOrderWildcards($text, $arrOrder)
    {
        /** @var \PageModel $objPage */
        global $objPage;

        if (!is_array($arrOrder['miscData'])) {
            $arrOrder['miscData'] = deserialize($arrOrder['miscData'], true);
        }

        /*
         * Replace wildcards using the ##dataType::fieldName## syntax with the corresponding customerData values.
         * Examples: ##personalData::firstname##, ##paymentData::accountHolder##, ##shippingData::postBox##
         */
        foreach ($arrOrder['customerData'] as $dataType => $arrData) {
            foreach ($arrData as $fieldName => $fieldValue) {
                $text = preg_replace('/(&#35;&#35;' . $dataType . '::' . $fieldName . '&#35;&#35;)|(##' . $dataType . '::' . $fieldName . '##)/siU', $fieldValue, $text);
            }
        }

        /*
         * Replace the order identification hash wildcard
         */
        if ($arrOrder['orderIdentificationHash']) {
            $text = preg_replace('/(&#35;&#35;orderIdentificationHash&#35;&#35;)|(##orderIdentificationHash##)/siU', $arrOrder['orderIdentificationHash'], $text);
        }

        /*
         * Replace the wildcards for the link to the after checkout page using the order identification hash
         */
        if ($arrOrder['orderIdentificationHash']) {
            $afterCheckoutUrl = ($arrOrder['miscData']['domain'] ?: \Environment::get('base')) . ls_shop_languageHelper::getLanguagePage('ls_shop_afterCheckoutPages') . (preg_match('/\?/', ls_shop_languageHelper::getLanguagePage('ls_shop_afterCheckoutPages')) ? '&' : '?') . 'oih=' . $arrOrder['orderIdentificationHash'];
            $text = preg_replace('/(&#35;&#35;afterCheckoutUrl&#35;&#35;)|(##afterCheckoutUrl##)/siU', $afterCheckoutUrl, $text);
        }

        /*
         * Replace the orderNr wildcard
         */
        if ($arrOrder['orderNr']) {
            $text = preg_replace('/(&#35;&#35;orderNr&#35;&#35;)|(##orderNr##)/siU', $arrOrder['orderNr'], $text);
        }

        /*
         * Replace the orderDate wildcard
         */
        if ($arrOrder['orderDateUnixTimestamp']) {
            $text = preg_replace('/(&#35;&#35;orderDate&#35;&#35;)|(##orderDate##)/siU', \Date::parse($GLOBALS['TL_CONFIG']['dateFormat'], $arrOrder['orderDateUnixTimestamp']), $text);
        }

        /*
         * Replace the wildcards for the payment and shipping method's after checkout information
         */
        if ($arrOrder['paymentMethod_infoAfterCheckout']) {
            $text = preg_replace('/(&#35;&#35;paymentMethod_infoAfterCheckout&#35;&#35;)|(##paymentMethod_infoAfterCheckout##)/siU', $arrOrder['paymentMethod_infoAfterCheckout'], $text);
        }
        if ($arrOrder['paymentMethod_infoAfterCheckout_customerLanguage']) {
            $text = preg_replace('/(&#35;&#35;paymentMethod_infoAfterCheckout_customerLanguage&#35;&#35;)|(##paymentMethod_infoAfterCheckout_customerLanguage##)/siU', $arrOrder['paymentMethod_infoAfterCheckout_customerLanguage'], $text);
        }

        if ($arrOrder['shippingMethod_infoAfterCheckout']) {
            $text = preg_replace('/(&#35;&#35;shippingMethod_infoAfterCheckout&#35;&#35;)|(##shippingMethod_infoAfterCheckout##)/siU', $arrOrder['shippingMethod_infoAfterCheckout'], $text);
        }
        if ($arrOrder['shippingMethod_infoAfterCheckout_customerLanguage']) {
            $text = preg_replace('/(&#35;&#35;shippingMethod_infoAfterCheckout_customerLanguage&#35;&#35;)|(##shippingMethod_infoAfterCheckout_customerLanguage##)/siU', $arrOrder['shippingMethod_infoAfterCheckout_customerLanguage'], $text);
        }

        /*
         * Replace the tracking nr and tracking url wildcards
         */
        if ($arrOrder['shippingTrackingNr']) {
            $text = preg_replace('/(&#35;&#35;shippingTrackingNr&#35;&#35;)|(##shippingTrackingNr##)/siU', $arrOrder['shippingTrackingNr'], $text);
        }
        if ($arrOrder['shippingTrackingUrl']) {
            $text = preg_replace('/(&#35;&#35;shippingTrackingUrl&#35;&#35;)|(##shippingTrackingUrl##)/siU', $arrOrder['shippingTrackingUrl'], $text);
        }

        /*
         * Look for Template wildcards
         */
        $matches = array();
        $arrTemplates = array();

        preg_match_all('/(&#35;&#35;template::(.*)&#35;&#35;)|(##template::(.*)##)/siU', $text, $matches);

        if (!count($arrTemplates) && is_array($matches[2]) && count($matches[2])) {
            foreach ($matches[2] as $value) {
                if (empty($value)) {
                    continue;
                }
                $arrTemplates[] = $value;
            }
        }

        if (!count($arrTemplates) && is_array($matches[4]) && count($matches[4])) {
            foreach ($matches[4] as $value) {
                if (empty($value)) {
                    continue;
                }
                $arrTemplates[] = $value;
            }
        }

        foreach ($arrTemplates as $strTemplate) {
            $wildcardTemplateReplacement = '';

            /*
             * Only if the template file exists in the required output format, it can be used. Otherwise it will not be used and a log entry will be created.
             */
            try {
                $objWildcardTemplate = new \FrontendTemplate($strTemplate);
                $objWildcardTemplate->arrOrder = $arrOrder;
                $wildcardTemplateReplacement = $objWildcardTemplate->parse();
            } catch (\Exception $e) {
                \System::log('MERCONIS: Template "' . $strTemplate . '" does not exist (at least not in the required output format "' . (isset($objPage) && is_object($objPage) ? $objPage->outputFormat : 'html5'), 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);
            }

            $text = preg_replace('/(&#35;&#35;template::' . $strTemplate . '&#35;&#35;)|(##template::' . $strTemplate . '##)/siU', $wildcardTemplateReplacement, $text);
        }

        /*
         * Remove all wildcards that are not yet replaced.
         */
        $text = preg_replace('/(&#35;&#35;.*&#35;&#35;)|(##.*##)/siU', '', $text);
        return $text;
    }

    public static function getOrder($identificationToken, $searchBy = 'id', $blnForceRefresh = false)
    {
        if ($searchBy != 'id' && $searchBy != 'orderIdentificationHash') {
            return false;
        }

        if (!isset($GLOBALS['merconis_globals']['order'][$identificationToken]) || $blnForceRefresh) {
            $arrOrder = array();
            $objOrder = \Database::getInstance()->prepare("
					SELECT		*
					FROM 		`tl_ls_shop_orders`
					WHERE		`" . $searchBy . "` = ?
				")
                ->limit(1)
                ->execute($identificationToken);

            if (!$objOrder->numRows) {
                return $arrOrder;
            }

            $arrOrder = $objOrder->first()->row();

            $arrOrder['totalValueOfGoodsTaxedWith'] = deserialize($arrOrder['totalValueOfGoodsTaxedWith']);
            $arrOrder['couponsUsed'] = deserialize($arrOrder['couponsUsed']);
            $arrOrder['paymentMethod_amountTaxedWith'] = deserialize($arrOrder['paymentMethod_amountTaxedWith']);
            $arrOrder['shippingMethod_amountTaxedWith'] = deserialize($arrOrder['shippingMethod_amountTaxedWith']);
            $arrOrder['totalTaxedWith'] = deserialize($arrOrder['totalTaxedWith']);
            $arrOrder['tax'] = deserialize($arrOrder['tax']);

            $arrOrder['customerData'] = array();

            $objCustomerData = \Database::getInstance()->prepare("
					SELECT		*
					FROM		`tl_ls_shop_orders_customer_data`
					WHERE		`pid` = ?
				")
                ->execute($arrOrder['id']);

            while ($objCustomerData->next()) {
                $arrOrder['customerData'][$objCustomerData->dataType][$objCustomerData->fieldName] = $objCustomerData->fieldValue;
            }

            $arrOrder['items'] = array();

            $objItems = \Database::getInstance()->prepare("
					SELECT		*
					FROM		`tl_ls_shop_orders_items`
					WHERE		`pid` = ?
				")
                ->execute($arrOrder['id']);

            while ($objItems->next()) {
                $arrOrder['items'][$objItems->itemPosition] = $objItems->row();
                $arrOrder['items'][$objItems->itemPosition]['extendedInfo'] = deserialize($arrOrder['items'][$objItems->itemPosition]['extendedInfo']);
            }


            $arrOrder['messageTypesSent'] = array();

            $objMessageTypesSent = \Database::getInstance()->prepare("
					SELECT		`messageTypeID`
					FROM		`tl_ls_shop_messages_sent`
					WHERE		`orderID` = ?
					GROUP BY	`messageTypeID`
				")
                ->execute($arrOrder['id']);

            while ($objMessageTypesSent->next()) {
                $arrOrder['messageTypesSent'][$objMessageTypesSent->messageTypeID] = $objMessageTypesSent->messageTypeID;
            }

            $arrOrder['paymentMethod_infoAfterCheckout'] = ls_shop_generalHelper::ls_replaceOrderWildcards($arrOrder['paymentMethod_infoAfterCheckout'], $arrOrder);
            $arrOrder['paymentMethod_infoAfterCheckout_customerLanguage'] = ls_shop_generalHelper::ls_replaceOrderWildcards($arrOrder['paymentMethod_infoAfterCheckout_customerLanguage'], $arrOrder);

            $arrOrder['shippingMethod_infoAfterCheckout'] = ls_shop_generalHelper::ls_replaceOrderWildcards($arrOrder['shippingMethod_infoAfterCheckout'], $arrOrder);
            $arrOrder['shippingMethod_infoAfterCheckout_customerLanguage'] = ls_shop_generalHelper::ls_replaceOrderWildcards($arrOrder['shippingMethod_infoAfterCheckout_customerLanguage'], $arrOrder);


            $GLOBALS['merconis_globals']['order'][$identificationToken] = $arrOrder;
        }

        return $GLOBALS['merconis_globals']['order'][$identificationToken];
    }

    public static function getMessageSent($identificationToken, $searchBy = 'id', $blnForceRefresh = false)
    {
        if ($searchBy != 'id') {
            return false;
        }

        if (!isset($GLOBALS['merconis_globals']['messageSent'][$identificationToken]) || $blnForceRefresh) {
            $arrMessageSent = array();
            $objOrder = \Database::getInstance()->prepare("
					SELECT		*
					FROM 		`tl_ls_shop_messages_sent`
					WHERE		`" . $searchBy . "` = ?
				")
                ->limit(1)
                ->execute($identificationToken);

            if (!$objOrder->numRows) {
                return $arrMessageSent;
            }

            $arrMessageSent = $objOrder->first()->row();

            $arrMessageSent['messageTypeTitle'] = ls_shop_languageHelper::getMultiLanguage($arrMessageSent['messageTypeID'], "tl_ls_shop_message_type_languages", array('title'), array($GLOBALS['TL_LANGUAGE']));
            $arrMessageSent['tstampFormatted'] = \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $arrMessageSent['tstamp']);

            $GLOBALS['merconis_globals']['messageSent'][$identificationToken] = $arrMessageSent;
        }
        return $GLOBALS['merconis_globals']['messageSent'][$identificationToken];
    }

    public static function callback_outputFrontendTemplate($strContent, $strTemplate)
    {
        /*
         * The contao hook has to call this intermediary function because the hook requires the content to be
         * returned and of course the message function must not have anything to do with meeting this requirement.
         */
        ls_shop_msg::decreaseLifetime();
        return $strContent;
    }

    public static function getTemplates_beOrderOverview(\DataContainer $dc)
    {
        return \Controller::getTemplateGroup('template_beOrderRepresentationOverview_');
    }

    public static function getTemplates_beOrderDetails(\DataContainer $dc)
    {
        return \Controller::getTemplateGroup('template_beOrderRepresentationDetails_');
    }

    public static function getMessageTypesForOrderOverview($arrOrder = null, $isAjax = false)
    {
        $arrMessageTypes = array();

        if (!is_array($arrOrder) || !count($arrOrder)) {
            return $arrMessageTypes;
        }

        $objMessageTypes = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_message_type`
				WHERE		`sendWhen` != ?
					AND		`sendWhen` != ?
					AND		(
								SELECT	COUNT(*)
								FROM	`tl_ls_shop_message_model`
								WHERE	`tl_ls_shop_message_model`.`pid` = `tl_ls_shop_message_type`.`id`
									AND	`tl_ls_shop_message_model`.`published` = '1'
									AND	`tl_ls_shop_message_model`.`member_group` LIKE ?
							) > 0
			")
            ->execute('asOrderConfirmation', 'asOrderNotice', '%%"' . $arrOrder['memberGroupInfo_id'] . '"%');

        if (!$objMessageTypes->numRows) {
            return $arrMessageTypes;
        }

        while ($objMessageTypes->next()) {
            $arrMessageTypes[$objMessageTypes->id] = $objMessageTypes->row();
            $arrMessageTypes[$objMessageTypes->id]['multilanguage']['title'] = ls_shop_languageHelper::getMultiLanguage($objMessageTypes->id, "tl_ls_shop_message_type_languages", array('title'), array($GLOBALS['TL_LANGUAGE']));
            $objTemplateMessageTypeButton = new \BackendTemplate('template_beMessageTypeButton_default');

            $objTemplateMessageTypeButton->messageType = $arrMessageTypes[$objMessageTypes->id];
            $objTemplateMessageTypeButton->arrOrder = $arrOrder;
            $objTemplateMessageTypeButton->isAjax = $isAjax;
            $arrMessageTypes[$objMessageTypes->id]['button'] = $objTemplateMessageTypeButton->parse();
        }

        return $arrMessageTypes;
    }

    public static function sendMessagesOnStatusChangeCronDaily()
    {
        $objOrders = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_orders`
			")
            ->execute();

        while ($objOrders->next()) {
            $objOrderMessages = new ls_shop_orderMessages($objOrders->id, 'onStatusChangeCronDaily', 'sendWhen', null, true);
            $objOrderMessages->sendMessages();
        }
    }

    public static function sendMessagesOnStatusChangeCronHourly()
    {
        $objOrders = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_orders`
			")
            ->execute();

        while ($objOrders->next()) {
            $objOrderMessages = new ls_shop_orderMessages($objOrders->id, 'onStatusChangeCronHourly', 'sendWhen', null, true);
            $objOrderMessages->sendMessages();
        }
    }

    /*
     * This function gets called whenever a DCA configuration is loaded. Some fields
     * which should not be offered in the editAll mode will be removed in this function
     */
    public static function removeFieldsForEditAll($strDCAName)
    {
        if (TL_MODE == 'BE' && \Input::get('act') != 'editAll' && \Input::get('act') != 'overrideAll') {
            /*
             * Don't do anything if we're not in editAll or overrideAll mode
             */
            return;
        }

        if (is_array($GLOBALS['TL_DCA'][$strDCAName]['fields'])) {
            foreach ($GLOBALS['TL_DCA'][$strDCAName]['fields'] as $fieldKey => $arrFieldDefinition) {
                if (preg_match('/group.*SearchSelection/', $fieldKey)) {
                    unset($GLOBALS['TL_DCA'][$strDCAName]['fields'][$fieldKey]);
                }
            }
        }
    }

    public static function getAllAttributesAndValues()
    {
        $arr_attributesAndValues = array();
        $arr_attributesRaw = ls_shop_generalHelper::getProductAttributes();

        foreach ($arr_attributesRaw as $arr_attribute) {
            $arr_valuesRaw = ls_shop_generalHelper::getAttributeValues($arr_attribute['id'], true);
            $arr_values = [];
            foreach ($arr_valuesRaw as $arr_valueRaw) {
                $arr_values[] = [
                    'id' => $arr_valueRaw['id'],
                    'label' => ls_shop_languageHelper::getMultiLanguage($arr_valueRaw['id'], 'tl_ls_shop_attribute_values_languages', array('title'), array($GLOBALS['TL_LANGUAGE']), false, false) . ' (' . $arr_valueRaw['alias'] . ')'
                ];
            }
            $arr_attributesAndValues[] = [
                'id' => $arr_attribute['id'],
                'label' => ls_shop_languageHelper::getMultiLanguage($arr_attribute['id'], 'tl_ls_shop_attributes_languages', array('title'), array($GLOBALS['TL_LANGUAGE']), false, false) . ' (' . $arr_attribute['alias'] . ')',
                'values' => $arr_values
            ];
        }

        return $arr_attributesAndValues;
    }

    public static function getAttributesAsOptions()
    {
        $attributes = ls_shop_generalHelper::getProductAttributes();
        $attributesOptions = array();
        $attributesOptions[] = array('value' => 0, 'label' => '-');
        if (is_array($attributes)) {
            foreach ($attributes as $attributeInfo) {
                $attributesOptions[] = array('value' => $attributeInfo['id'], 'label' => ls_shop_languageHelper::getMultiLanguage($attributeInfo['id'], 'tl_ls_shop_attributes_languages', array('title'), array($GLOBALS['TL_LANGUAGE']), false, false) . ' (' . $attributeInfo['alias'] . ')');
            }
        }

        return $attributesOptions;
    }

    public static function getAttributeValuesAsOptions($attributeID = 0)
    {
        $attributeValuesOptions = array();
        if (!$attributeID) {
            return $attributeValuesOptions;
        }

        $attributeValues = ls_shop_generalHelper::getAttributeValues($attributeID, true);

        if (is_array($attributeValues)) {
            foreach ($attributeValues as $attributeValueInfo) {
                $attributeValuesOptions[] = array('value' => $attributeValueInfo['id'], 'label' => ls_shop_languageHelper::getMultiLanguage($attributeValueInfo['id'], 'tl_ls_shop_attribute_values_languages', array('title'), array($GLOBALS['TL_LANGUAGE']), false, false) . ' (' . $attributeValueInfo['alias'] . ')');
            }
        }

        return $attributeValuesOptions;
    }

    /*
     * This function copies the attribute value allocation entries if a product/variant has been copied.
     * If this function is called for a product it also copies it's child variants because we need to
     * do it on our own since the contao automatism that would copy the variants as child records automatically
     * if we would not explicitly prevent that from happening, does not trigger the oncopy_callback for
     * the copied variants.
     */
    public static function attributeValueAllocationCopy($insertID, $dc)
    {
        /*
         * Determine whether the parent of the allocations that have to be copied is a variant
         */
        $parentIsVariant = $dc->table == 'tl_ls_shop_variant' ? '1' : '0';

        /*
         * Get the attribute variant allocations from the allocation table
         * for the copied product/variant
         */
        $objAllocations = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_attribute_allocation`
				WHERE		`pid` = ?
					AND		`parentIsVariant` = ?
				ORDER BY	`sorting` ASC
			")
            ->execute($dc->id, $parentIsVariant);

        while ($objAllocations->next()) {
            /*
             * Insert each allocation for the new product/variant
             */
            $objInsert = \Database::getInstance()->prepare("
					INSERT INTO `tl_ls_shop_attribute_allocation`
					SET			`pid` = ?,
								`parentIsVariant` = ?,
								`attributeID` = ?,
								`attributeValueID` = ?,
								`sorting` = ?
				")
                ->execute($insertID, $parentIsVariant, $objAllocations->attributeID, $objAllocations->attributeValueID, $objAllocations->sorting);
        }

        /*
         * Copy the variants if the callback is called for a product.
         * Copying a variant this way causes it's oncopy callback to be triggered
         * which is exactly what we want to achieve with this approach
         */
        if ($dc->table == 'tl_ls_shop_product') {
            $objVariants = \Database::getInstance()->prepare("
					SELECT		*
					FROM		`tl_ls_shop_variant`
					WHERE		`pid` = ?
					ORDER BY	`sorting`
				")
                ->execute($dc->id);

            while ($objVariants->next()) {
                /*
                 * Maybe we don't need to do that
                 *
                // Verhindern, dass beim Kopieren der Zusatz "(Kopie)" angefügt wird
                $tmpCopyOfText = $GLOBALS['TL_LANG']['MSC']['copyOf'];
                $GLOBALS['TL_LANG']['MSC']['copyOf'] = '%s';
                 */

                /*
                 * Fake a regular call for this variant where it's id
                 * would be a get parameter
                 */
                $tmpGetID = \Input::get('id');
                \Input::setGet('id', $objVariants->id);

                $dcVariant = new \DC_Table('tl_ls_shop_variant');

                /*
                 * Copy the variant record by calling the DC_Table copy function and prevent
                 * the redirection that would occur in the copy function by setting
                 * the "blnDoNotRedirect" parameter to true
                 */
                $variantInsertID = $dcVariant->copy(true);

                /*
                 * After copying the variant it is still assigned to it's former parent
                 * but we need it to be assigend to the new parent i.e. the product that
                 * has just been copied. So we update the pid with the new product's id.
                 */
                $objUpdatePID = \Database::getInstance()->prepare("
						UPDATE 		`tl_ls_shop_variant`
						SET			`pid` = ?
						WHERE		`id` = ?
					")
                    ->limit(1)
                    ->execute($insertID, $variantInsertID);

                /*
                 * Resetting the temporarily faked get parameter 'id'
                 */
                \Input::setGet('id', $tmpGetID);

                /*
                 * Maybe we don't need to do that
                 *
                $GLOBALS['TL_LANG']['MSC']['copyOf'] = $tmpCopyOfText;
                 */
            }

        }
    }

    /*
     * Whenever a product/variant has been deleted, this function deletes it's allocation rows
     */
    public static function attributeValueAllocationRemoveOrphanedRecords($dc)
    {
        $parentIsVariant = $dc->table == 'tl_ls_shop_variant' ? '1' : '0';

        /*
         * Delete the attribute variant allocations from the allocation table
         * for the deleted product/variant
         */
        $objDelete = \Database::getInstance()->prepare("
				DELETE FROM	`tl_ls_shop_attribute_allocation`
				WHERE		`pid` = ?
					AND		`parentIsVariant` = ?
			")
            ->execute($dc->id, $parentIsVariant);

        /*
         * If this callback is called for a product then it's variants will also be
         * deleted although they are not deleted at this moment. The ondelete_callback
         * of the variants will not be called if they are deleted "passively" because
         * their parent product is being deleted. Therefore we have to delete their
         * attribute value allocations here.
         */
        if ($dc->table == 'tl_ls_shop_product') {
            /*
             * Getting the ids of the child variants of the product that's being deleted right now
             * because we want to delete all attribute value allocations that are assigend to
             * these variants.
             */
            $objVariants = \Database::getInstance()->prepare("
					SELECT		*
					FROM		`tl_ls_shop_variant`
					WHERE		`pid` = ?
					ORDER BY	`sorting`
				")
                ->execute($dc->id);

            while ($objVariants->next()) {
                /*
                 * Delete the attribute variant allocations from the allocation table
                 * for this variant (which will itself be deleted soon)
                 */
                $objDelete = \Database::getInstance()->prepare("
						DELETE FROM	`tl_ls_shop_attribute_allocation`
						WHERE		`pid` = ?
							AND		`parentIsVariant` = ?
					")
                    ->execute($objVariants->id, '1');
            }
        }


        /*
         * Also delete all orphaned records that exist in the allocation table
         */
        \Database::getInstance()->prepare("
				DELETE		`tl_ls_shop_attribute_allocation` FROM `tl_ls_shop_attribute_allocation`
				LEFT JOIN	`tl_ls_shop_product`
					ON		`tl_ls_shop_attribute_allocation`.`pid` = `tl_ls_shop_product`.`id`
				WHERE		`tl_ls_shop_product`.`id` IS NULL
					AND		`parentIsVariant` = ?
			")
            ->execute('0');

        \Database::getInstance()->prepare("
				DELETE		`tl_ls_shop_attribute_allocation` FROM `tl_ls_shop_attribute_allocation`
				LEFT JOIN	`tl_ls_shop_variant`
					ON		`tl_ls_shop_attribute_allocation`.`pid` = `tl_ls_shop_variant`.`id`
				WHERE		`tl_ls_shop_variant`.`id` IS NULL
					AND		`parentIsVariant` = ?
			")
            ->execute('1');
    }

    public static function ls_calculateVariantPriceRegardingPriceType($priceType = false, $productPrice = false, $variantPrice = false)
    {
        if ($priceType === false || $productPrice === false || $variantPrice === false) {
            /*
             * Hier wird als Rückgabewert eine Zahl erwartet, ein false würde als 0 interpretiert werden und de facto bedeuten,
             * dass etwas kostenlos ist. Da das nicht passieren darf, wird hier eine völlig unplausible Zahl ausgegeben, die - wenn
             * sie als Preis gehandhabt wird - für niemanden zu einem Verlust führen wird, da niemand eine Bestellung mit einem
             * solchen Betrag abschließen würde und selbst wenn, könnte man locker stornieren. Würde mit einer 0 gearbeitet werden,
             * wäre das schon schwieriger, weil der Kunde darauf bestehen könnte, dass das Produkt kostenlos angeboten wurde.
             */
            return 9999999999;
        }

        switch ($priceType) {
            case 'adjustmentPercentaged':
                /*				$variantPrice = $productPrice + $productPrice * $variantPrice / 100; */
                $variantPrice = ls_add($productPrice, ls_div(ls_mul($productPrice, $variantPrice), 100));
                break;

            case 'adjustmentFix':
                /*				$variantPrice = $productPrice + $variantPrice; */
                $variantPrice = ls_add($productPrice, $variantPrice);
                break;

            case 'standalone':
            default:
                /* Variantenpreis bleibt, wie er ist */
                break;
        }
        return $variantPrice;
    }

    public static function handleSearchWordMinLength($searchWord = '', $minLength = 0)
    {
        /*
         * Check if each part of the search term is long enough
         */
        if ($minLength > 0 && !preg_match('/^"(.*)"$/', $searchWord)) {
            $arrValues = explode(' ', $searchWord);

            foreach ($arrValues as $k => $v) {
                if (strlen($v) < $minLength) {
                    unset($arrValues[$k]);
                }
            }

            $searchWord = implode(' ', $arrValues);
        }

        return $searchWord;
    }

    public static function saveLastBackendDataChangeTimestamp()
    {
        \Config::getInstance()->update("\$GLOBALS['TL_CONFIG']['ls_shop_lastBackendDataChange']", time());

        ls_shop_generalHelper::LaFP();
    }

    /*
     * In order to be able to switch the layout in product singleview, we
     * would like to use \PageRegular::getPageLayout() but unfortunately
     * we can't do this because it is protected. Therefore, this function here
     * is a duplicate of the protected function. Maybe there are some parts that
     * we don't necessarily need but as long as we can work with an identical
     * duplicate of the original function, we appreciate it, because it keeps
     * things simpler if Contao ever changes this function and we would need
     * to follow the changes.
     */
    public static function merconis_getPageLayout($objPage)
    {
        $blnMobile = ($objPage->mobileLayout && \Environment::get('agent')->mobile);

        // Set the cookie
        if (isset($_GET['toggle_view'])) {
            if (\Input::get('toggle_view') == 'mobile') {
                \System::setCookie('TL_VIEW', 'mobile', 0);
            } else {
                \System::setCookie('TL_VIEW', 'desktop', 0);
            }

            \Controller::redirect(\System::getReferer());
        }

        // Override the autodetected value
        if (\Input::cookie('TL_VIEW') == 'mobile') {
            $blnMobile = true;
        } elseif (\Input::cookie('TL_VIEW') == 'desktop') {
            $blnMobile = false;
        }

        $intId = ($blnMobile && $objPage->mobileLayout) ? $objPage->mobileLayout : $objPage->layout;
        $objLayout = \LayoutModel::findByPk($intId);

        // Die if there is no layout
        if (null === $objLayout) {
            header('HTTP/1.1 501 Not Implemented');
            \System::log('Could not find layout ID "' . $intId . '"', __METHOD__, TL_ERROR);
            die_nicely('be_no_layout', 'No layout specified');
        }

        $objPage->hasJQuery = $objLayout->addJQuery;
        $objPage->hasMooTools = $objLayout->addMooTools;
        $objPage->isMobile = $blnMobile;

        return $objLayout;
    }

    /*
     * This function checks if we are on a product detail page and if we are,
     * it checks if the page has different layout settings for the details view
     * and if it has, it overwrites the page's regular layout settings
     */
    public static function ls_shop_switchTemplateInDetailsViewIfNecessary(\PageModel &$objPage, \LayoutModel &$objLayout, \PageRegular $objPageRegular)
    {

        if (!\Input::get('product')) {
            /*
             * We don't have to deal with different layouts because we are
             * not on a product details page
             */
            return;
        }

        $int_layout = $objPage->lsShopIncludeLayoutForDetailsView ? $objPage->lsShopLayoutForDetailsView : false;
        $int_layoutMobile = $objPage->lsShopIncludeLayoutForDetailsView ? $objPage->lsShopMobileLayoutForDetailsView : false;

        if ($objPage->type != 'root') {
            $int_pid = $objPage->pid;
            $str_type = $objPage->type;
            $objParentPage = \PageModel::findParentsById($int_pid);

            if ($objParentPage !== null) {
                while ($int_pid > 0 && $str_type != 'root' && $objParentPage->next()) {
                    $int_pid = $objParentPage->pid;
                    $str_type = $objParentPage->type;

                    if ($objParentPage->lsShopIncludeLayoutForDetailsView) {
                        if ($int_layout === false) {
                            $int_layout = $objParentPage->lsShopLayoutForDetailsView;
                        }
                        if ($int_layoutMobile === false) {
                            $int_layoutMobile = $objParentPage->lsShopMobileLayoutForDetailsView;
                        }
                    }
                }
            }
        }

        if ($int_layout === false && $int_layoutMobile === false) {
            /*
             * We don't have to consider different layouts
             */
            return;
        }
        $objPage->layout = $int_layout !== false ? $int_layout : $objPage->layout;
        $objPage->mobileLayout = $int_layoutMobile !== false ? $int_layoutMobile : $objPage->mobileLayout;

        $objLayout = ls_shop_generalHelper::merconis_getPageLayout($objPage);
    }

    public static function ls_shop_getThemeDataForID($int_themeID = null)
    {
        $arr_themeData = array();

        if (!$int_themeID) {
            return $arr_themeData;
        }

        $obj_dbres_theme = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_theme`
				WHERE		`id` = ?
			")
            ->limit(1)
            ->execute($int_themeID);

        if ($obj_dbres_theme->numRows) {
            $arr_themeData = $obj_dbres_theme->first()->row();
        }

        return $arr_themeData;
    }

    public static function ls_shop_getPageDataForID($int_pageID = null)
    {
        $arr_pageData = array();

        if (!$int_pageID) {
            return $arr_pageData;
        }

        $obj_dbres_page = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_page`
				WHERE		`id` = ?
			")
            ->limit(1)
            ->execute($int_pageID);

        if ($obj_dbres_page->numRows) {
            $arr_pageData = $obj_dbres_page->first()->row();
        }

        return $arr_pageData;
    }

    public static function merconis_getLayoutSettingsForGlobalUse(\PageModel $objPage, \LayoutModel $objLayout, \PageRegular $objPageRegular)
    {
        $GLOBALS['merconis_globals']['layoutID'] = $objLayout->id;
        $GLOBALS['merconis_globals']['layoutName'] = $objLayout->name;
        $GLOBALS['merconis_globals']['ls_shop_activateFilter'] = $objLayout->ls_shop_activateFilter;
        $GLOBALS['merconis_globals']['ls_shop_useFilterInStandardProductlist'] = $objLayout->ls_shop_useFilterInStandardProductlist;
        $GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates'] = $objLayout->ls_shop_useFilterMatchEstimates;
        $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'] = $objLayout->ls_shop_matchEstimatesMaxNumProducts;
        $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'] = $objLayout->ls_shop_matchEstimatesMaxFilterValues;
        $GLOBALS['merconis_globals']['ls_shop_useFilterInProductDetails'] = $objLayout->ls_shop_useFilterInProductDetails;
        $GLOBALS['merconis_globals']['ls_shop_hideFilterFormInProductDetails'] = $objLayout->ls_shop_hideFilterFormInProductDetails;

        $arr_themeData = ls_shop_generalHelper::ls_shop_getThemeDataForID($objLayout->pid);
        $GLOBALS['merconis_globals']['contaoThemeFolders'] = isset($arr_themeData) ? deserialize($arr_themeData['folders'], true) : array();

        $GLOBALS['merconis_globals']['int_rootPageId'] = $objPage->rootId;
        $arr_pageData = ls_shop_generalHelper::ls_shop_getPageDataForID($objPage->rootId);

        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = $arr_pageData['ls_shop_decimalsSeparator'];
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = $arr_pageData['ls_shop_thousandsSeparator'];
        $GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue'] = $arr_pageData['ls_shop_currencyBeforeValue'];
    }

    public static function ls_shop_loadThemeLanguageFiles($filename, $language)
    {
        $themesPath = 'files/merconisfiles/themes';
        if (!file_exists(TL_ROOT . '/' . $themesPath) || !is_dir(TL_ROOT . '/' . $themesPath)) {
            return;
        }
        $themeFolders = array_diff(scandir(TL_ROOT . '/' . $themesPath), array('.', '..'));
        if (is_array($themeFolders)) {
            foreach ($themeFolders as $themeFolder) {
                $languageFileToLoad = $themesPath . '/' . $themeFolder . '/languages/' . $language . '/' . $filename . '.php';
                if (file_exists(TL_ROOT . '/' . $languageFileToLoad)) {
                    include(TL_ROOT . '/' . $languageFileToLoad);
                }
            }
        }
    }

    /*
     * Generates a random hex string with a given length
     */
    public static function generateRandomHex($minLength = 1, $maxLength = 15)
    {
        $strHex = '';
        $targetlength = rand($minLength, $maxLength);
        for ($i = 1; $i <= $targetlength; $i++) {
            $strHex = $strHex . dechex(rand(0, 15));
        }
        return $strHex;
    }

    /*
     * Encodes an id (or any integer) into an oix string.
     *
     * IMPORTANT: This is just a simple function to hide an id in a much longer hex string with variable positions.
     * It is not a secure encoding because the encoding and decoding functions could be found in the code and anyone
     * who knows the algorithm could read the id from an oix.
     *
     * The perspective of the oix encoding is to make it harder to read ids in situations where the id itself
     * is not extremely confidential but still should not be presented directly.
     *
     *
     * An oix string consists of the hex converted id, a random hex string with a length between 1 and 15
     * as a prefix and a random hex string with a length between 1 and 15 as a suffix and the hex converted
     * length of the prefix in the added in front of the string and the hex converted length of the suffix
     * added at the end of the string.
     *
     * Example:
     *
     * Given ID: 459 -> Hex: 1CB
     * Random Prefix: E7CBAD02
     * Length of the Prefix: 8 -> Hex: 8
     * Random Suffix: 1613A3D297D55
     * Length of the Suffix: 13 -> Hex: D
     * OIX: 8 E7CBAD02 1CB 1613A3D297D55 D => 8E7CBAD021CB1613A3D297D55D
     */
    public static function encodeOix($id)
    {
        $prefix = ls_shop_generalHelper::generateRandomHex(1, 15);
        $lengthPrefix = dechex(strlen($prefix));

        $suffix = ls_shop_generalHelper::generateRandomHex(1, 15);
        $lengthSuffix = dechex(strlen($suffix));

        $idHex = dechex($id);

        $oix = $lengthPrefix . $prefix . $idHex . $suffix . $lengthSuffix;

        return $oix;
    }

    /*
     * Decodes an oix and returns the previously encoded id
     * (see the comment of the encodeOix function for further information)
     */
    public static function decodeOix($oix)
    {
        $lengthPrefix = hexdec(substr($oix, 0, 1));
        $lengthSuffix = hexdec(substr($oix, -1, 1));
        $idHex = substr(substr($oix, $lengthPrefix + 1), 0, ($lengthSuffix + 1) * -1);
        return hexdec($idHex);
    }

    /*
     * This function takes an array holding the product's or variant's data
     * with the group price data the way it comes from the database and then
     * structures the group price data in a way that makes the prices for a
     * specific group easily accessible.
     */
    public static function getStructuredGroupPrices($arr_productData, $str_productOrVariant = '')
    {
        /*
         * This number has to match the number of group prices that can be
         * entered for a product or variant
         */
        $int_numGroupPrices = 5;

        $arr_structuredGroupPrices = array();

        for ($i = 1; $i <= $int_numGroupPrices; $i++) {
            if (!$arr_productData['useGroupPrices_' . $i]) {
                /*
                 * Skip this price group if it should not be used
                 */
                continue;
            }

            $arr_validGroups = deserialize($arr_productData['priceForGroups_' . $i], true);
            if (!count($arr_validGroups)) {
                /*
                 * Skip this price group if it is not assigned to any
                 * member groups
                 */
                continue;
            }

            $arr_groupPriceData = array(
                'useScalePrice' => $arr_productData['useScalePrice_' . $i],
                'scalePriceType' => $arr_productData['scalePriceType_' . $i],
                'scalePriceQuantityDetectionMethod' => $arr_productData['scalePriceQuantityDetectionMethod_' . $i],
                'scalePriceQuantityDetectionAlwaysSeparateConfigurations' => $arr_productData['scalePriceQuantityDetectionAlwaysSeparateConfigurations_' . $i],
                'scalePriceKeyword' => $arr_productData['scalePriceKeyword_' . $i],
                'scalePrice' => $arr_productData['scalePrice_' . $i],
                'useOldPrice' => $arr_productData['useOldPrice_' . $i],
            );

            if ($str_productOrVariant === 'product' || $str_productOrVariant === '') {
                $arr_groupPriceData['lsShopProductPrice'] = $arr_productData['lsShopProductPrice_' . $i];
                $arr_groupPriceData['lsShopProductPriceOld'] = $arr_productData['lsShopProductPriceOld_' . $i];
            }

            if ($str_productOrVariant === 'variant' || $str_productOrVariant === '') {
                $arr_groupPriceData['lsShopVariantPrice'] = $arr_productData['lsShopVariantPrice_' . $i];
                $arr_groupPriceData['lsShopVariantPriceType'] = $arr_productData['lsShopVariantPriceType_' . $i];
                $arr_groupPriceData['lsShopVariantPriceOld'] = $arr_productData['lsShopVariantPriceOld_' . $i];
                $arr_groupPriceData['lsShopVariantPriceTypeOld'] = $arr_productData['lsShopVariantPriceTypeOld_' . $i];
            }

            /*
             * If multiple price groups have the same member group(s) assigned
             * to it, the later group settings override the previous. Since this
             * scenario only occurs because of a misconfiguration, this is absolutely
             * okay.
             */
            foreach ($arr_validGroups as $int_groupId) {
                $arr_structuredGroupPrices[$int_groupId] = $arr_groupPriceData;
            }
        }
        return $arr_structuredGroupPrices;
    }

    /*
     * This function reads the current merconis version from the changelog.md
     */
    public static function getMerconisFilesVersion($bln_removeInternalBuildNumber = false)
    {
        $objFile_ls_version = new \File('vendor/leadingsystems/contao-merconis/CHANGELOG.md');
        $str_fileContent = $objFile_ls_version->getContent();

        preg_match('/###\s*(.*?)\s*\(\d{4}-\d{2}-\d{2}\)/', $str_fileContent, $arr_matches);

        $str_merconisVersion = $arr_matches[1];

        return $str_merconisVersion;
    }

    /*
     * This function is called by contao's "initializeSystem" hook and its
     * purpose is to bypass the referer token check under certain circumstances.
     * For example in case of a status push by payone, post data is sent to
     * contao/merconis without a proper request_token. This call would fail
     * unless we make an exception. Contao has a built in referer whitelist
     * which unfortunately can only be used with domain names. There can be
     * situations in which we need a whitelist using IP addresses. In fact
     * that's the case with the payone status pushes.
     */
    public static function bypassRefererCheckIfNecessary()
    {
        if (
            !isset($GLOBALS['TL_CONFIG']['ls_shop_ipWhitelist'])
            && !isset($GLOBALS['TL_CONFIG']['ls_shop_urlWhitelist'])
        ) {
            return;
        }

        $arr_allowedIpAddresses = array_map('trim', explode(',', $GLOBALS['TL_CONFIG']['ls_shop_ipWhitelist']));

        if (in_array($_SERVER['REMOTE_ADDR'], $arr_allowedIpAddresses)) {
            define('BYPASS_TOKEN_CHECK', true);
        } else if (strlen($GLOBALS['TL_CONFIG']['ls_shop_urlWhitelist']) > 2) {
            if (preg_match($GLOBALS['TL_CONFIG']['ls_shop_urlWhitelist'], \Environment::get('request'))) {
                define('BYPASS_TOKEN_CHECK', true);
            }
        }
    }

    public static function ls_roundPrice($a = 0, $b = false)
    {
        if ($GLOBALS['TL_CONFIG']['ls_shop_priceRoundingFactor'] && $GLOBALS['TL_CONFIG']['ls_shop_priceRoundingFactor'] != 100) {
            $a = (round($a * $GLOBALS['TL_CONFIG']['ls_shop_priceRoundingFactor']) / $GLOBALS['TL_CONFIG']['ls_shop_priceRoundingFactor']);
        }

        if ($b === false) {
            $b = $GLOBALS['TL_CONFIG']['ls_shop_numDecimals'];
        }
        return round($a, $b);
    }

    public static function getQuantityInput($obj_productOrVariant)
    {
        if ($obj_productOrVariant->_objectType === 'product') {
            $productID = $obj_productOrVariant->ls_ID;
            $variantID = $obj_productOrVariant->ls_currentVariantID;
        } else if ($obj_productOrVariant->_objectType === 'variant') {
            $productID = $obj_productOrVariant->ls_productID;
            $variantID = $obj_productOrVariant->ls_ID;
        } else {
            throw new \Exception('product or variant object required.');
        }

        /*-->
         * Erstellen des Mengen-Eingabefeldes wenn es sich entweder um einen variantenbezogenen Aufruf handelt
         * oder das Produkt keine Varianten hat.
         <--*/
        $quantityInput = '';
        if ($obj_productOrVariant->_objectType === 'variant' || !$obj_productOrVariant->_hasVariants) {
            $objQuantityInputTemplate = new \FrontendTemplate('quantityInput');
            $str_formSubmitValue = 'product_form_' . $productID . '-' . $variantID;
            $objQuantityInputTemplate->str_formSubmitValue = $str_formSubmitValue;
            $objQuantityInputTemplate->str_productVariantId = $productID . '-' . $variantID;

            $objQuantityInputTemplate->showInputQuantity = true;

            /*-->
             * Erstellen des Quantity-Feldes
             <--*/
            $obj_flexWidget_inputQuantity = new FlexWidget(
                array(
                    'str_uniqueName' => 'quantity_' . $productID . '-' . $variantID,
                    'arr_validationFunctions' => array(
                        array(
                            'str_className' => '\Merconis\Core\FlexWidgetValidator',
                            'str_methodName' => 'quantityInput'
                        )
                    ),
                    'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText016'],
                    'str_allowedRequestMethod' => 'post',
                    'var_value' => isset($GLOBALS['TL_CONFIG']['ls_shop_quantityDefault']) ? $GLOBALS['TL_CONFIG']['ls_shop_quantityDefault'] : ''
                )
            );

            if (\Input::post('FORM_SUBMIT') == $str_formSubmitValue) {
                if (
                    !$obj_flexWidget_inputQuantity->bln_hasErrors

                    /*
                     * don't process the submitted form if the product object has been created with a configurator hash
                     * because in this case the product is already in the cart
                     */
                    && !$obj_productOrVariant->ls_configuratorHash
                ) {
                    $productVariantIDToPutInCart = \Input::post('productVariantID');
                    \Input::setPost('productVariantID', false); // Verhindert, dass bei späteren Initialisierungen des Produktes erneut in den Warenkorb gesteckt wird

                    if (
                        !$obj_productOrVariant->_orderAllowed
                        || preg_replace('/\\' . $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] . '/siU', '.', $obj_flexWidget_inputQuantity->getValue()) <= 0
                    ) {
                        ls_shop_msg::setMsg(array(
                            'class' => 'couldNotBePutInCart',
                            'reference' => $productVariantIDToPutInCart
                        ));
                    } else {
                        $cartKeyToPutInCart = $obj_productOrVariant->_objectType === 'product' ? $obj_productOrVariant->_cartKey : $obj_productOrVariant->_objParentProduct->_cartKey;

                        /*--> Prüfen, ob das Produkt vorher schon im Warenkorb ist <--*/
                        $tmpBlnCartKeyAlreadyInCart = false;
                        if (isset($_SESSION['lsShopCart']['items'][$cartKeyToPutInCart])) {
                            $tmpBlnCartKeyAlreadyInCart = true;
                        }

                        $arrAddToCartResponse = ls_shop_cartHelper::addToCart($productVariantIDToPutInCart, $obj_flexWidget_inputQuantity->getValue());

                        /*--> Ist das Produkt gar nicht mehr verfügbar, so wird es aus dem Warenkorb entfernt, es sei denn, es war schon vorher drin <--*/
                        if (!$tmpBlnCartKeyAlreadyInCart && $arrAddToCartResponse['quantityPutInCart'] == 0) {
                            ls_shop_cartHelper::updateCartItem($cartKeyToPutInCart, -1);
                        }
                    }
                    if (!\Input::post('isAjax')) {
                        \Controller::redirect(\Environment::get('request') . '#p_' . $productID . '-' . $variantID);
                    }
                }
            }

            $objQuantityInputTemplate->str_widget_inputQuantity = $obj_flexWidget_inputQuantity->getOutput();

            $quantityInput = $objQuantityInputTemplate->parse();
        }

        return $quantityInput;
    }

    public static function getFavoritesForm($obj_product)
    {
        $obj_user = \System::importStatic('FrontendUser');
        $objTemplate = new \FrontendTemplate('template_addToFavoritesForm');
        $str_formSubmitValue = 'favoriteProduct_form_' . $obj_product->_id;
        $objTemplate->str_formSubmitValue = $str_formSubmitValue;
        $objTemplate->str_favoriteProductId = $obj_product->_id;
        $objTemplate->bln_isFavorite = $obj_product->_isFavorite;

        if (
            \Input::post('FORM_SUBMIT')
            && \Input::post('FORM_SUBMIT') == 'favoriteProduct_form_' . $obj_product->_id
            && \Input::post('favoriteProductID')
            && \Input::post('favoriteProductID') == $obj_product->_id
        ) {
            $strFavorites = isset($obj_user->merconis_favoriteProducts) ? $obj_user->merconis_favoriteProducts : '';
            $arrFavorites = $strFavorites ? deserialize($strFavorites) : array();
            $arrFavorites = is_array($arrFavorites) ? $arrFavorites : array();

            if (!$obj_product->_isFavorite) {
                /*
                 * Add the product to the favorites
                 */
                $arrFavorites[] = $obj_product->_id;

                ls_shop_msg::setMsg(array(
                    'class' => 'addedToFavorites',
                    'reference' => $obj_product->_id
                ));
            } else {
                /*
                 * Remove the product from the favorites
                 */
                unset($arrFavorites[array_search($obj_product->_id, $arrFavorites)]);

                ls_shop_msg::setMsg(array(
                    'class' => 'removedFromFavorites',
                    'reference' => $obj_product->_id
                ));
            }

            \Database::getInstance()->prepare("
				UPDATE		`tl_member`
				SET			`merconis_favoriteProducts` = ?
				WHERE		`id` = ?
			")
                ->limit(1)
                ->execute(serialize($arrFavorites), $obj_user->id);

            if (!\Input::post('isAjax')) {
                \Controller::reload();
            }
        }

        $objTemplate->objProduct = $obj_product;
        $favoritesForm = $objTemplate->parse();
        return $favoritesForm;
    }

    public function manipulateBackendNavigation($arr_modules, $blnShowAll)
    {
        \System::loadLanguageFile('lsm_installer');

        if (
            !isset($GLOBALS['TL_CONFIG']['ls_shop_installedCompletely'])
            || !$GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']
        ) {
            foreach (array_keys($arr_modules['merconis']['modules']) as $str_moduleName) {
                if ($str_moduleName !== 'ls_shop_dashboard') {
                    unset($arr_modules['merconis']['modules'][$str_moduleName]);
                }
            }
        }

        return $arr_modules;
    }

    public static function getMerconisSystemMessages()
    {
        ob_start();

        if (isset($GLOBALS['TL_CONFIG']['merconis_snInvalid']) && $GLOBALS['TL_CONFIG']['merconis_snInvalid']) {
            ?>
            <h2 class="invalidServiceNumberMessage"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['invalidServiceNumberMessage']; ?></h2>
            <?php
        } else {
            if (isset($GLOBALS['TL_CONFIG']['gracePeriodDaysLeft']) && $GLOBALS['TL_CONFIG']['gracePeriodDaysLeft'] != 999999) {
                if ($GLOBALS['TL_CONFIG']['gracePeriodDaysLeft'] > 0) {
                    ?>
                    <h2 class="gracePeriodMessage"><?php echo sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['gracePeriodMessage'], $GLOBALS['TL_CONFIG']['gracePeriodDaysLeft']); ?></h2>
                    <?php

                } else {
                    ?>
                    <h2 class="gracePeriodExpiredMessage"><?php echo $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['gracePeriodExpiredMessage']; ?></h2>
                    <?php
                }
            }
        }

        return ob_get_clean();
    }

    public static function getSubPageIdsRecursively($int_pageId = 0, $bln_considerUnpublishedPages = false, $bln_considerHiddenPages = false, $int_currentLevel = 0) {
        $arr_pageIds = [];

        $obj_dbres_subPageIds = \Database::getInstance()
            ->prepare("
                SELECT      `id`
                FROM        `tl_page`
                WHERE       `".($int_currentLevel === 0 ? "id" : "pid")."` = ?
                  ".($bln_considerUnpublishedPages ? "" : "AND       `published` = '1'")."
                  ".($bln_considerHiddenPages ? "" : "AND       `hide` = ''")."
            ")
            ->execute(
                $int_pageId
            );

        $int_nextLevel = $int_currentLevel + 1;

        while ($obj_dbres_subPageIds->next()) {
            $arr_pageIds[$obj_dbres_subPageIds->id] = [
                'pageId' => $obj_dbres_subPageIds->id,
                'subPageIds' => self::getSubPageIdsRecursively(($int_currentLevel === 0 ? $int_pageId : $obj_dbres_subPageIds->id), $bln_considerUnpublishedPages, $bln_considerHiddenPages, $int_nextLevel)
            ];
        }

        return $arr_pageIds;
    }

    public static function flattenSubPageIdsArray($arr_structuredPageIds = [], $int_startLevel = 0, $int_stopLevel = 0, $int_currentLevel = 0) {
        $arr_pageIds = [];

        $int_nextLevel = $int_currentLevel + 1;

        foreach ($arr_structuredPageIds as $arr_page) {
            if (
                    $int_currentLevel >= $int_startLevel
                    && (
                        !$int_stopLevel
                        || $int_currentLevel <= $int_stopLevel
                    )
            ) {
                $arr_pageIds[] = $arr_page['pageId'];
            }
            $arr_subPageIds = self::flattenSubPageIdsArray($arr_page['subPageIds'], $int_startLevel, $int_stopLevel, $int_nextLevel);
            $arr_pageIds = array_merge($arr_pageIds, $arr_subPageIds);
        }

        return $arr_pageIds;
    }

    public static function merconis_getBackendLsjs($str_content, $str_template) {
        if ($str_template !== 'be_main') {
            return $str_content;
        }

        ob_start();
        ?>
        <script src="assets/lsjs/core/appBinder/binder.php?output=js&pathToApp=<?php echo urldecode('_dup4_/web/bundles/leadingsystemsmerconis/js/lsjs/backend/app'); ?>&includeCore=no&includeCoreModules=no<?php echo ($GLOBALS['TL_CONFIG']['ls_shop_lsjsDebugMode'] ? '&debug=1' : '').($GLOBALS['TL_CONFIG']['ls_shop_lsjsNoCacheMode'] ? '&no-cache=1' : '').($GLOBALS['TL_CONFIG']['ls_shop_lsjsNoMinifierMode'] ? '&&no-minifier=1' : '');?>"></script>
        <script type="text/javascript">
            window.addEvent('domready', function () {
                if (lsjs.__appHelpers.merconisBackendApp !== undefined && lsjs.__appHelpers.merconisBackendApp !== null) {
                    lsjs.__appHelpers.merconisBackendApp.obj_config.REQUEST_TOKEN = '<?php echo \RequestToken::get(); ?>';
                    lsjs.__appHelpers.merconisBackendApp.obj_config.API_KEY = '<?php echo $GLOBALS['TL_CONFIG']['ls_api_key']; ?>';
                    lsjs.__appHelpers.merconisBackendApp.start();
                }
            });
        </script>

        <?php
        $obj_combiner = new \Combiner();
        $obj_combiner->add('vendor/leadingsystems/contao-merconis/src/Resources/public/js/lsjs/backend/app/styles/appMainStyles.less');
        ?>
        <link rel="stylesheet" href="<?php echo $obj_combiner->getCombinedFile(); ?>">
        <?php

        return str_replace('</head>', ob_get_clean()."\r\n</head>", $str_content);
    }
}