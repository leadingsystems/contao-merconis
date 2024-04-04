<?php
namespace Merconis\Core;
use Contao\StringUtil;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_productManagementApiHelper {
	public static $int_numImportableGroupPrices = 5;
	public static $int_numImportableAttributesAndValues = 20;
	public static $dataRowTypesInOrderToProcess = array('product', 'variant', 'productLanguage', 'variantLanguage');
	public static $modificationTypesTranslationMap = array('independent' => 'standalone', 'percentaged' => 'adjustmentPercentaged', 'fixed' => 'adjustmentFix');
	public static $arr_scalePriceQuantityDetectionMethods = array('separatedVariantsAndConfigurations', 'separatedVariants', 'separatedProducts', 'separatedScalePriceKeywords');
	public static $arr_scalePriceTypes = array('scalePriceStandalone', 'scalePricePercentaged', 'scalePriceFixedAdjustment');
    public static array $arr_customFieldsForProducts = [];
    public static array $arr_customFieldsForVariants = [];

	/*
	 * Diese Funktion erwartet als Parameter einen String, der kommasepariert die Aliase
	 * der als Kategorien zu verwendenden Seiten enthält und ermittelt dazu die passenden
	 * Seiten-IDs und gibt diese als serialisiertes Array zurück, sodass dieses direkt
	 * als Wert in das DB-Feld `pages` der Produkt-Tabelle eingetragen werden kann.
	 */
	public static function generatePageListFromCategoryValue($str_categories = '') {
		if (!isset($GLOBALS['merconis_globals']['generatePageListFromCategoryValue'][$str_categories])) {
			$arr_categories = explode(',', $str_categories);
			$arr_pageIDs = array();

			if (is_array($arr_categories)) {
				foreach ($arr_categories as $str_category) {
					$str_category = trim($str_category);
					if (!$str_category) {
						continue;
					}
					$obj_dbres_page = \Database::getInstance()
					->prepare("
						SELECT		`id`
						FROM		`tl_page`
						WHERE		`alias` = ?
					")
					->execute($str_category);

					if (!$obj_dbres_page->numRows) {
						continue;
					}
					$obj_dbres_page->first();
					$arr_pageIDs[] = (string) $obj_dbres_page->id;
				}
			}

			$GLOBALS['merconis_globals']['generatePageListFromCategoryValue'][$str_categories] = serialize($arr_pageIDs);
		}

		return $GLOBALS['merconis_globals']['generatePageListFromCategoryValue'][$str_categories];
	}

	/*
	 * Diese Funktion liest anhand des Alias die ID aus und gibt diese zurück
	 */
	public static function getTaxClassID($str_alias = '') {
        if ($str_alias === '' || $str_alias === null) {
			return 0;
		}

		if (!isset($GLOBALS['merconis_globals']['getTaxClassID'][$str_alias])) {
			$obj_dbres_row = \Database::getInstance()
			->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_steuersaetze`
				WHERE		`alias` = ?
			")
			->execute($str_alias);

			if ($obj_dbres_row->numRows) {
				$GLOBALS['merconis_globals']['getTaxClassID'][$str_alias] = $obj_dbres_row->id;
			} else {
				$GLOBALS['merconis_globals']['getTaxClassID'][$str_alias] = 0;
			}
		}

		return $GLOBALS['merconis_globals']['getTaxClassID'][$str_alias];
	}

	/*
	 * Diese Funktion liest anhand des Alias die ID aus und gibt diese zurück
	 */
	public static function getConfiguratorID($str_alias = '') {
		if (!$str_alias) {
			return 0;
		}

		/*
		 * If the given alias contains ".php" we assume that the value is not actually meant to be a configurator
		 * alias but instead a customizer logic file path. Therefore we don't try to get a configurator id but
		 * return 0 instantly.
		 */
        if (strpos($str_alias, '.php') !== false) {
            return 0;
        }

		if (!isset($GLOBALS['merconis_globals']['getConfiguratorID'][$str_alias])) {
			$obj_dbres_row = \Database::getInstance()
			->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_configurator`
				WHERE		`alias` = ?
			")
			->execute($str_alias);

			if ($obj_dbres_row->numRows) {
				$GLOBALS['merconis_globals']['getConfiguratorID'][$str_alias] = $obj_dbres_row->id;
			} else {
				$GLOBALS['merconis_globals']['getConfiguratorID'][$str_alias] = 0;
			}
		}

		return $GLOBALS['merconis_globals']['getConfiguratorID'][$str_alias];
	}

    public static function getCustomizerLogicFileReference($str_filePath) {
	    if (!$str_filePath) {
	        return null;
        }

	    /*
	     * If the given file path does not contain ".php" we assume that the value is not actually meant to be a file
	     * path to a customizer logic file but instead it might be meant as a configurator alias. Therefore, we return
	     * null.
	     */
        if (strpos($str_filePath, '.php') === false) {
            return null;
        }

        $fileModel = \FilesModel::findByPath($str_filePath);
        $fileReference = $fileModel->uuid;
        return $fileReference;
    }

	/*
	 * Diese Funktion liest anhand des Alias die ID aus und gibt diese zurück
	 */
	public static function getDeliveryInfoSetID($str_alias = '') {
		if (!$str_alias) {
			return 0;
		}

		if (!isset($GLOBALS['merconis_globals']['getDeliveryInfoSetID'][$str_alias])) {
			$obj_dbres_row = \Database::getInstance()
			->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_delivery_info`
				WHERE		`alias` = ?
			")
			->execute($str_alias);

			if ($obj_dbres_row->numRows) {
				$GLOBALS['merconis_globals']['getDeliveryInfoSetID'][$str_alias] = $obj_dbres_row->id;
			} else {
				$GLOBALS['merconis_globals']['getDeliveryInfoSetID'][$str_alias] = 0;
			}
		}

		return $GLOBALS['merconis_globals']['getDeliveryInfoSetID'][$str_alias];
	}

	/*
	 * Diese Funktion stellt allen kommasepariert angegebenen Bildnamen den Standardpfad voran
	 * und gibt alle Bilder als serialisiertes Array zurück, sodass dieser Wert sofort in
	 * die Produkttabelle eingetragen werden kann.
	 */
	public static function prepareMoreImages($str_images = '') {
		$arr_images = explode(',' ,$str_images);
		$arr_preparedImages = array();

		if (is_array($arr_images)) {
			foreach ($arr_images as $str_image) {
				$str_image = trim($str_image);

				if (!$str_image) {
					continue;
				}

				$obj_models = \FilesModel::findMultipleByPaths(array(ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImageFolder']).'/'.$str_image));
				if ($obj_models !== null) {
					$str_image = $obj_models->first()->uuid;
				} else {
					continue;
				}

				$arr_preparedImages[] = $str_image;
			}
		}
		return serialize($arr_preparedImages);
	}

	public static function generateFlexContentsString($arr_row) {
		$arr_flexContents = array();
		foreach (self::getImportFlexFieldKeys() as $str_flexImportFieldKey) {
			$arr_flexContents[] = $str_flexImportFieldKey;
			$arr_flexContents[] = $arr_row[$str_flexImportFieldKey];
		}

		/*
		 * Since Merconis 4 we store this value JSON encoded and as a two-dimensional array whereas it previously was
		 * one-dimensional.
		 */
		$arr_flexContents = \LeadingSystems\Helpers\createMultidimensionalArray($arr_flexContents, 2);

		return json_encode($arr_flexContents);
	}

	public static function generateFlexContentsStringLanguageIndependent($arr_row) {
		$arr_flexContents = array();
		foreach (self::getImportFlexFieldKeysLanguageIndependent() as $str_flexImportFieldKey) {
			$arr_flexContents[] = $str_flexImportFieldKey;
			$arr_flexContents[] = $arr_row[$str_flexImportFieldKey];
		}

		/*
		 * Since Merconis 4 we store this value JSON encoded and as a two-dimensional array whereas it previously was
		 * one-dimensional.
		 */
		$arr_flexContents = \LeadingSystems\Helpers\createMultidimensionalArray($arr_flexContents, 2);

		return json_encode($arr_flexContents);
	}

	public static function getImportFlexFieldKeys() {
		if (!isset($GLOBALS['merconis_globals']['getImportFlexFieldKeys'])) {
			$GLOBALS['merconis_globals']['getImportFlexFieldKeys'] = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $GLOBALS['TL_CONFIG']['ls_shop_importFlexFieldKeys']);
		}
		return $GLOBALS['merconis_globals']['getImportFlexFieldKeys'];
	}

	public static function getImportFlexFieldKeysLanguageIndependent() {
		if (!isset($GLOBALS['merconis_globals']['getImportFlexFieldKeysLanguageIndependent'])) {
			$GLOBALS['merconis_globals']['getImportFlexFieldKeysLanguageIndependent'] = ls_shop_generalHelper::explodeWithoutBlanksAndSpaces(',', $GLOBALS['TL_CONFIG']['ls_shop_importFlexFieldKeysLanguageIndependent']);
		}
		return $GLOBALS['merconis_globals']['getImportFlexFieldKeysLanguageIndependent'];
	}

	public static function getAttributeAndValueAliases() {
		if (!isset($GLOBALS['merconis_globals']['attributeAndValueAliases'])) {
			$GLOBALS['merconis_globals']['attributeAndValueAliases'] = array(
				'attributeAliases' => array(),
				'attributeValueAliases' => array()
			);

			$obj_dbres_attributes = \Database::getInstance()
			->prepare("
				SELECT		`alias`
				FROM		`tl_ls_shop_attributes`
			")
			->execute();

			while($obj_dbres_attributes->next()) {
				$GLOBALS['merconis_globals']['attributeAndValueAliases']['attributeAliases'][] = $obj_dbres_attributes->alias;
			}

			$obj_attributeValues = \Database::getInstance()
			->prepare("
				SELECT		`alias`
				FROM		`tl_ls_shop_attribute_values`
			")
			->execute();

			while($obj_attributeValues->next()) {
				$GLOBALS['merconis_globals']['attributeAndValueAliases']['attributeValueAliases'][] = $obj_attributeValues->alias;
			}
		}

		return $GLOBALS['merconis_globals']['attributeAndValueAliases'];
	}

	public static function getAttributeAndValueAliasesInRelation() {
		if (!isset($GLOBALS['merconis_globals']['attributeAndValueAliasesInRelation'])) {
			$GLOBALS['merconis_globals']['attributeAndValueAliasesInRelation'] = array();

			$obj_dbres_attributes = \Database::getInstance()
			->prepare("
				SELECT		`id`,
							`alias`
				FROM		`tl_ls_shop_attributes`
			")
			->execute();

			while($obj_dbres_attributes->next()) {
				$obj_attributeValues = \Database::getInstance()
				->prepare("
					SELECT		`alias`
					FROM		`tl_ls_shop_attribute_values`
					WHERE		`pid` = ?
				")
				->execute($obj_dbres_attributes->id);

				while($obj_attributeValues->next()) {
					$GLOBALS['merconis_globals']['attributeAndValueAliasesInRelation'][$obj_dbres_attributes->alias][] = $obj_attributeValues->alias;
				}
			}
		}

		return $GLOBALS['merconis_globals']['attributeAndValueAliasesInRelation'];
	}

	public static function getAttributeIDForAlias($str_alias = '') {
		if (!$str_alias) {
			return null;
		}

		if (!isset($GLOBALS['merconis_globals']['getAttributeIDForAlias'][$str_alias])) {
			$obj_dbres_attribute = \Database::getInstance()
			->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_attributes`
				WHERE		`alias` = ?
			")
			->execute($str_alias);

			if (!$obj_dbres_attribute->numRows) {
				$GLOBALS['merconis_globals']['getAttributeIDForAlias'][$str_alias] = 0;
				return $GLOBALS['merconis_globals']['getAttributeIDForAlias'][$str_alias];
			}

			$obj_dbres_attribute->first();

			$GLOBALS['merconis_globals']['getAttributeIDForAlias'][$str_alias] = $obj_dbres_attribute->id;
		}

		return $GLOBALS['merconis_globals']['getAttributeIDForAlias'][$str_alias];
	}

	public static function getAttributeValueIDForAlias($str_alias = '') {
		if (!$str_alias) {
			return null;
		}

		if (!isset($GLOBALS['merconis_globals']['getAttributeValueIDForAlias'][$str_alias])) {
			$obj_dbres_attributeValue = \Database::getInstance()
			->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_attribute_values`
				WHERE		`alias` = ?
			")
			->execute($str_alias);

			if (!$obj_dbres_attributeValue->numRows) {
				$GLOBALS['merconis_globals']['getAttributeValueIDForAlias'][$str_alias] = 0;
				return $GLOBALS['merconis_globals']['getAttributeValueIDForAlias'][$str_alias];
			}

			$obj_dbres_attributeValue->first();

			$GLOBALS['merconis_globals']['getAttributeValueIDForAlias'][$str_alias] = $obj_dbres_attributeValue->id;
		}

		return $GLOBALS['merconis_globals']['getAttributeValueIDForAlias'][$str_alias];
	}

    public static function getPageAliases($bln_considerOnlyPagesMarkedAsCategoriesForErp = false) {
		if (!isset($GLOBALS['merconis_globals']['pageAliases'])) {
			$GLOBALS['merconis_globals']['pageAliases'] = array();

			$obj_dbres_pages = \Database::getInstance()
			->prepare("
				SELECT		`id`,
                            `alias`
				FROM		`tl_page`
				".($bln_considerOnlyPagesMarkedAsCategoriesForErp ? "WHERE       `ls_shop_useAsCategoryForErp` = '1'" : "")."
			")
			->execute();

            while ($obj_dbres_pages->next()) {
                // Check whether root page is fallback language or not and only then add the page to the pageAliases array
                $obj_pageDetails = \PageModel::findWithDetails($obj_dbres_pages->id);
                $obj_rootPage = \Database::getInstance()
                    ->prepare("
                        SELECT * FROM `tl_page` WHERE `id` = ?
                    ")
                    ->limit(1)
                    ->execute($obj_pageDetails->rootId);

                if ($obj_rootPage->fallback) {
                    $GLOBALS['merconis_globals']['pageAliases'][] = $obj_dbres_pages->alias;
                }
            }
		}
		return $GLOBALS['merconis_globals']['pageAliases'];
	}

	public static function getDeliveryInfoTypeAliases() {
		if (!isset($GLOBALS['merconis_globals']['deliveryInfoTypeAliases'])) {
			$GLOBALS['merconis_globals']['deliveryInfoTypeAliases'] = array();

			$obj_dbres_deliveryInfoTypes = \Database::getInstance()
			->prepare("
				SELECT		`alias`
				FROM		`tl_ls_shop_delivery_info`
			")
			->execute();

			while ($obj_dbres_deliveryInfoTypes->next()) {
				$GLOBALS['merconis_globals']['deliveryInfoTypeAliases'][] = $obj_dbres_deliveryInfoTypes->alias;
			}
		}
		return $GLOBALS['merconis_globals']['deliveryInfoTypeAliases'];
	}

	public static function getTaxClassAliases() {
		if (!isset($GLOBALS['merconis_globals']['cache']['taxClassAliases'])) {
			$GLOBALS['merconis_globals']['cache']['taxClassAliases'] = array();

			$obj_dbres_taxClasses = \Database::getInstance()
			->prepare("
				SELECT		`alias`
				FROM		`tl_ls_shop_steuersaetze`
			")
			->execute();

			while ($obj_dbres_taxClasses->next()) {
				$GLOBALS['merconis_globals']['cache']['taxClassAliases'][] = $obj_dbres_taxClasses->alias;
			}
		}
		return $GLOBALS['merconis_globals']['cache']['taxClassAliases'];
	}

	public static function getConfiguratorAliases() {
		if (!isset($GLOBALS['merconis_globals']['cache']['configuratorAliases'])) {
			$GLOBALS['merconis_globals']['cache']['configuratorAliases'] = array();

			$obj_dbres_configurators = \Database::getInstance()
			->prepare("
				SELECT		`alias`
				FROM		`tl_ls_shop_configurator`
			")
			->execute();

			while ($obj_dbres_configurators->next()) {
				$GLOBALS['merconis_globals']['cache']['configuratorAliases'][] = $obj_dbres_configurators->alias;
			}
		}
		return $GLOBALS['merconis_globals']['cache']['configuratorAliases'];
	}

	public static function generateScalePriceArray($str_scalePrice) {
		$arr_scalePrice = array();
		$arr_steps = explode(';', $str_scalePrice);

		if (is_array($arr_steps)) {
			foreach ($arr_steps as $str_step) {
				$str_step = trim($str_step);
				if (!$str_step) {
					continue;
				}
				$arr_step = explode('=', $str_step);
				if (is_array($arr_step)) {
					if (isset($arr_step[0])) {
						$arr_scalePrice[] = trim($arr_step[0]);
					}
					if (isset($arr_step[1])) {
						$arr_scalePrice[] = trim($arr_step[1]);
					}
				}
			}
		}

		/*
		 * Since Merconis 4 we store this value JSON encoded and as a two-dimensional array whereas it previously was
		 * one-dimensional.
		 */
		$arr_scalePrice = \LeadingSystems\Helpers\createMultidimensionalArray($arr_scalePrice, 2);

		return json_encode($arr_scalePrice);
	}

	public static function preparePropertiesAndValues($arr_row = null) {
		if (!is_array($arr_row)) {
			return array();
		}

		/*
		 * Aus den Inhalten der property/value-Feldern muss ein entsprechendes Array erstellt werden. Die property-Felder
		 * enthalten einen Alias als eindeutige Referenz auf die Tabelle tl_ls_shop_attributes, die Werte müssen entsprechend
		 * übersetzt werden. Die value-Felder enthalten einen Alias als eindeutige Referenz auf die Tabelle tl_ls_shop_attribute_values
		 * und müssen ebenfalls entsprechend übersetzt werden.
		 */
		$arr_propertiesAndValues = array();
		$arr_propertyAndValue = array();
		$bln_skipValue = false;

		foreach ($arr_row as $str_fieldName => $str_fieldValue) {
			if (preg_match('/property[0-9]*$/', $str_fieldName)) {
				$int_attributeId = self::getAttributeIDForAlias($str_fieldValue);

				if (!$int_attributeId) {
					$bln_skipValue = true;
					continue;
				}

				$arr_propertyAndValue[] = $int_attributeId;
			} else if (preg_match('/value[0-9]*$/', $str_fieldName)) {
				if ($bln_skipValue) {
					$bln_skipValue = false;
					continue;
				}
				$int_attributeValueId = self::getAttributeValueIDForAlias($str_fieldValue);
				$int_attributeValueId = $int_attributeValueId ? $int_attributeValueId : 0;

				$arr_propertyAndValue[] = $int_attributeValueId;
				$arr_propertiesAndValues[] = $arr_propertyAndValue;
				$arr_propertyAndValue = array();
			}
		}
		return json_encode($arr_propertiesAndValues);
	}

	/*
	 * Diese Funktion erwartet als Parameter einen String, der die Artikelnummern aller empfohlenen Produkte
	 * kommasepariert enthält. Die Artikelnummern werden getrennt und als serialisiertes Array zurückgegeben.
	 * Dieses Array kann dann direkt gespeichert werden, muss aber in einem zweiten Schritt nach vollständigem Import,
	 * sobald also zu allen Artikelnummern definitiv DB-IDs existieren, in die IDs übersetzt werden.
	 *
	 * WICHTIG: Da Artikelnummern nicht zuverlässig von echten IDs unterschieden werden können (wenn Artikelnummern rein
	 * numerisch sind) muss das Array, welches Artikelnummern enthält, entsprechend gekennzeichnet sein, was durch
	 * die Verwendung des Keys "productCodes" geschieht.
	 */
	public static function prepareRecommendedProducts($str_recommendedProductCodes = '') {
		$arr_recommendedProductCodes = explode(',' ,$str_recommendedProductCodes);
		$arr_preparedRecommendedProductCodes = array(
			'productCodes' => array()
		);
		if (is_array($arr_recommendedProductCodes)) {
			foreach ($arr_recommendedProductCodes as $str_recommendedProductCode) {
				$str_recommendedProductCode = trim($str_recommendedProductCode);
				if (!$str_recommendedProductCode) {
					continue;
				}
				$arr_preparedRecommendedProductCodes['productCodes'][] = $str_recommendedProductCode;
			}
		}
		return serialize($arr_preparedRecommendedProductCodes);
	}

	/*
	 * Diese Funktion muss direkt nach dem vollständig durchgeführten Import aller Produkte ausgeführt werden
	 * und durchläuft dann alle Produkt-Datensätze und prüft sie daraufhin, ob als empfohlene Produkte ggf.
	 * ein serialisiertes Array mit dem Key "productCodes" anstelle des regulären Arrays mit Produkt-IDs hinterlegt ist.
	 * Ist dies der Fall, so müssen die im Array enthaltenen Artikelnummern in die dazugehörigen Produkt-IDs
	 * übersetzt werden, welche dann in einem Array hinterlegt und in diesem serialisiert wieder direkt
	 * in den Produktdatensatz geschrieben werden.
	 */
	public static function translateRecommendedProductCodesInIDs() {
		$obj_dbres_prodResults = \Database::getInstance()
		->prepare("
			SELECT		`id`,
						`lsShopProductRecommendedProducts`
			FROM		`tl_ls_shop_product`
		")
		->execute();

		while ($obj_dbres_prodResults->next()) {
			$arr_recommendedProducts = StringUtil::deserialize($obj_dbres_prodResults->lsShopProductRecommendedProducts);
			$arr_recommendedProductIDs = array();

			/*
			 * Ist im Array der empfohlenen Produkte der Key 'productCodes' vorhanden,
			 * so ist dies die Bestätigung, dass keine IDs sondern eben noch productCodes
			 * hinterlegt sind. Nur in diesem Fall muss eine Übersetzung stattfinden, andernfalls
			 * bleibt der Wert, wie er ist.
			 */
			if (!isset($arr_recommendedProducts['productCodes'])) {
				continue;
			} else {
				foreach ($arr_recommendedProducts['productCodes'] as $str_productCode) {
					$obj_dbres_prodId = \Database::getInstance()
					->prepare("
						SELECT		`id`
						FROM		`tl_ls_shop_product`
						WHERE		`lsShopProductCode` = ?
					")
					->execute($str_productCode);

					if (!$obj_dbres_prodId->numRows) {
						continue;
					}
					$obj_dbres_prodId->first();
					$arr_recommendedProductIDs[] = $obj_dbres_prodId->id;
				}

				\Database::getInstance()
				->prepare("
					UPDATE		`tl_ls_shop_product`
					SET			`lsShopProductRecommendedProducts` = ?
					WHERE		`id` = ?
				")
				->limit(1)
				->execute(serialize($arr_recommendedProductIDs), $obj_dbres_prodResults->id);
			}
		}
	}

    /*
     * Returns a list of fields with question mark parameters for an SQL query that match
     * group prices of a product or variant (depending on the parameter)
     * For further SQL queries 'on duplicate key update', it also returns in the optional return parameters
     * the separate field names and a list with question marks.
     *
     *  @param  {string}    $str_productOrVariant               either 'product' or something else for variant
     *  @param  {string}    $fieldnames                         a list containing the field names without question marks
     *  @param  {string}    $questionMarks                      a list containing the question marks without the field names (for the values part)
     *  @return {string}    $str_addGroupPriceFieldsToQuery     a list with field names and question marks
     */
	public static function createGroupPriceFieldsForQuery($str_productOrVariant = 'product', ?string &$fieldnames = null, ?string &$questionMarks = null)
    {
		$str_addGroupPriceFieldsToQuery = "";

		for ($i=1; $i <= self::$int_numImportableGroupPrices; $i++) {
			$str_addGroupPriceFieldsToQuery .= ",
				
				`useGroupPrices_".$i."` = ?,
				`priceForGroups_".$i."` = ?,
				".($str_productOrVariant === 'product' ? "`lsShopProductPrice_".$i."`" : "`lsShopVariantPrice_".$i."`")." = ?,
				".($str_productOrVariant === 'product' ? "" : "`lsShopVariantPriceType_".$i."` = ?,")."
				`useScalePrice_".$i."` = ?,
				`scalePriceType_".$i."` = ?,
				`scalePriceQuantityDetectionMethod_".$i."` = ?,
				`scalePriceQuantityDetectionAlwaysSeparateConfigurations_".$i."` = ?,
				`scalePriceKeyword_".$i."` = ?,
				`scalePrice_".$i."` = ?,
				".($str_productOrVariant === 'product' ? "`lsShopProductPriceOld_".$i."`" : "`lsShopVariantPriceOld_".$i."`")." = ?,
				".($str_productOrVariant === 'product' ? "" : "`lsShopVariantPriceTypeOld_".$i."` = ?,")."
				`useOldPrice_".$i."` = ?
			";

			$fieldnames .= ",
				
				`useGroupPrices_".$i."`,
				`priceForGroups_".$i."`,
				".($str_productOrVariant === 'product' ? "`lsShopProductPrice_".$i."`" : "`lsShopVariantPrice_".$i."`").",
				".($str_productOrVariant === 'product' ? "" : "`lsShopVariantPriceType_".$i."`,")."
				`useScalePrice_".$i."`,
				`scalePriceType_".$i."`,
				`scalePriceQuantityDetectionMethod_".$i."`,
				`scalePriceQuantityDetectionAlwaysSeparateConfigurations_".$i."`,
				`scalePriceKeyword_".$i."`,
				`scalePrice_".$i."`,
				".($str_productOrVariant === 'product' ? "`lsShopProductPriceOld_".$i."`" : "`lsShopVariantPriceOld_".$i."`").",
				".($str_productOrVariant === 'product' ? "" : "`lsShopVariantPriceTypeOld_".$i."`,")."
				`useOldPrice_".$i."`
			";

			$questionMarks .=",
				
				?,
				?,
				?,
				".($str_productOrVariant === 'product' ? "" : "?,")."
				?,
				?,
				?,
				?,
				?,
				?,
				?,
                ".($str_productOrVariant === 'product' ? "" : "?,")."
				?
			";
		}

		return $str_addGroupPriceFieldsToQuery;
	}

	public static function addGroupPriceFieldsToQueryParam($arr_queryParams, $row, $str_productOrVariant = 'product') {
		for ($i=1; $i <= self::$int_numImportableGroupPrices; $i++) {
			$arr_queryParams[] = $row['useGroupPrices_'.$i] ? '1' : ''; // 1 or ''

			$arr_queryParams[] = $row['priceForGroups_'.$i]; // blob, translated, check unclear

			$arr_queryParams[] = $row['price_'.$i] ? $row['price_'.$i] : 0; // decimal, empty = 0

			if ($str_productOrVariant === 'variant') {
				$arr_queryParams[] = $row['priceType_'.$i]; // String, maxlength 255
			}
			$arr_queryParams[] = $row['useScalePrice_'.$i] ? '1' : ''; // 1 or ''
			$arr_queryParams[] = $row['scalePriceType_'.$i]; // String, maxlength 255
			$arr_queryParams[] = $row['scalePriceQuantityDetectionMethod_'.$i]; // String, maxlength 255
			$arr_queryParams[] = $row['scalePriceQuantityDetectionAlwaysSeparateConfigurations_'.$i] ? '1' : ''; // 1 or ''
			$arr_queryParams[] = $row['scalePriceKeyword_'.$i]; // String, maxlength 255
			$arr_queryParams[] = $row['scalePrice_'.$i]; // blob, translated, check unclear
			$arr_queryParams[] = $row['oldPrice_'.$i] ? $row['oldPrice_'.$i] : 0; // decimal, empty = 0

			if ($str_productOrVariant === 'variant') {
				$arr_queryParams[] = $row['oldPriceType_'.$i]; // String, maxlength 255
			}

			$arr_queryParams[] = $row['useOldPrice_'.$i] ? '1' : ''; // 1 or ''
		}

		return $arr_queryParams;
	}

	public static function generateProductAlias($str_title, $str_givenAlias = '', $int_productId = 0, $str_language = '', $bln_alwaysAddIdToAlias = 'default') {
	    if ($bln_alwaysAddIdToAlias === 'default') {
	        $bln_alwaysAddIdToAlias = isset($GLOBALS['TL_CONFIG']['ls_shop_alwaysAddIdToAliasDuringProductImport']) && $GLOBALS['TL_CONFIG']['ls_shop_alwaysAddIdToAliasDuringProductImport'];
        }

		$str_alias = $str_givenAlias ? $str_givenAlias : \StringUtil::generateAlias($str_title);

		/*
		 * The alias must not be longer than 128 characters
		 */
		$str_alias = substr($str_alias, 0, 128);

        if (!$bln_alwaysAddIdToAlias) {
            /*
             * Check whether the alias already exists
             */
            $obj_dbres_recordForAlias = \Database::getInstance()
                ->prepare("
                    SELECT		`id`
                    FROM		`tl_ls_shop_product`
                    WHERE		`alias" . ($str_language ? '_' . $str_language : '') . "` = ?
                        AND		`id` != ?
                ")
                ->execute(
                    $str_alias,
                    $int_productId
                );
        }

		if ($bln_alwaysAddIdToAlias || $obj_dbres_recordForAlias->numRows) {
			/*
			 * Even with the suffix added to the alias, it must not be longer
			 * than 128 characters
			 */
			$str_aliasSuffix = '-'.($int_productId ? $int_productId : md5($str_title . $str_givenAlias . microtime() . rand(0, 999999)));
			$str_alias = substr($str_alias, 0, 128 - strlen($str_aliasSuffix)).$str_aliasSuffix;
		}

		return $str_alias;
	}

	public static function generateVariantAlias($str_title, $str_givenAlias = '', $int_variantId = 0, $str_language = '', $bln_alwaysAddIdToAlias = true) {
		$str_alias = $str_givenAlias ? $str_givenAlias : \StringUtil::generateAlias($str_title);

		/*
		 * The alias must not be longer than 128 characters
		 */
		$str_alias = substr($str_alias, 0, 128);

        if (!$bln_alwaysAddIdToAlias) {
            /*
             * Check whether the alias already exists
             */
            $obj_dbres_recordForAlias = \Database::getInstance()
                ->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_variant`
			WHERE		`alias" . ($str_language ? '_' . $str_language : '') . "` = ?
				AND		`id` != ?
		")
                ->execute(
                    $str_alias,
                    $int_variantId
                );
        }

		if ($bln_alwaysAddIdToAlias || $obj_dbres_recordForAlias->numRows) {
			/*
			 * Even with the suffix added to the alias, it must not be longer
			 * than 128 characters
			 */
			$str_aliasSuffix = '-'.$int_variantId;
			$str_alias = substr($str_alias, 0, 128 - strlen($str_aliasSuffix)).$str_aliasSuffix;
		}

		return $str_alias;
	}

	public static function getProductIdForProductCode($str_productcode) {
		$int_productId = 0;

		$obj_dbres_product = \Database::getInstance()
		->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_product`
			WHERE		`lsShopProductCode` = ?
		")
		->execute(
			$str_productcode
		);

		if ($obj_dbres_product->numRows) {
			$int_productId = $obj_dbres_product->first()->id;
		}

		return $int_productId;
	}

	public static function getVariantIdForProductCode($str_productcode) {
		$int_variantId = 0;

		$obj_dbres_variant = \Database::getInstance()
		->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_variant`
			WHERE		`lsShopVariantCode` = ?
		")
		->execute(
			$str_productcode
		);

		if ($obj_dbres_variant->numRows) {
			$int_variantId = $obj_dbres_variant->first()->id;
		}

		return $int_variantId;
	}

	public static function insertOrUpdateProductRecord($arr_preprocessedDataRow) {
		// Prüfen, ob es ein Produkt mit der Artikelnummer bereits gibt


        $groupPriceFieldNames = '';
        $groupPriceQuestionMarks = '';
        $customFieldsFieldNames = '';
        $customFieldsQuestionMarks = '';
        $str_addGroupPriceFieldsToQuery = self::createGroupPriceFieldsForQuery('product', $groupPriceFieldNames, $groupPriceQuestionMarks);
        $str_customFieldsQueryExtension = self::createCustomFieldsQueryExtension('product', $customFieldsFieldNames, $customFieldsQuestionMarks);



        $obj_dbres_prod = \Database::getInstance()
			->prepare("
            INSERT INTO `tl_ls_shop_product` (
                `lsShopProductCode`, 
                `tstamp`,
                `title`,
                `alias`,
                `sorting`,
                `keywords`,
                `shortDescription`,
                `description`,
                `published`,
                `pages`,
                `lsShopProductPrice`,
                `lsShopProductPriceOld`,
                `useOldPrice`,
                `lsShopProductWeight`,
                `lsShopProductSteuersatz`,
                `lsShopProductQuantityUnit`,
                `lsShopProductQuantityDecimals`,
                `lsShopProductMengenvergleichUnit`,
                `lsShopProductMengenvergleichDivisor`,
                `lsShopProductMainImage`,
                `lsShopProductMoreImages`,
                `lsShopProductDetailsTemplate`,
                `lsShopProductIsNew`,
                `lsShopProductIsOnSale`,
                `lsShopProductRecommendedProducts`,
                `lsShopProductDeliveryInfoSet`,
                `lsShopProductProducer`,
                `configurator`,
                `flex_contents`,
                `flex_contentsLanguageIndependent`,
                `lsShopProductAttributesValues`,
                `useScalePrice`,
                `scalePriceType`,
                `scalePriceQuantityDetectionMethod`,
                `scalePriceQuantityDetectionAlwaysSeparateConfigurations`,
                `scalePriceKeyword`,
                `scalePrice`
                ".$groupPriceFieldNames."
                ".$customFieldsFieldNames."
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ".$groupPriceQuestionMarks."
            ".$customFieldsQuestionMarks."
            )
            ON DUPLICATE KEY UPDATE
                `title` = ?,
                `alias` = `alias`,
                `sorting` = ?,
                `keywords` = ?,
                `shortDescription` = ?,
                `description` = ?,
                `published` = ?,
                `pages` = ?,
                `lsShopProductPrice` = ?,
                `lsShopProductPriceOld` = ?,
                `useOldPrice` = ?,
                `lsShopProductWeight` = ?,
                `lsShopProductSteuersatz` = ?,
                `lsShopProductQuantityUnit` = ?,
                `lsShopProductQuantityDecimals` = ?,
                `lsShopProductMengenvergleichUnit` = ?,
                `lsShopProductMengenvergleichDivisor` = ?,
                `lsShopProductMainImage` = ?,
                `lsShopProductMoreImages` = ?,
                `lsShopProductDetailsTemplate` = ?,
                `lsShopProductIsNew` = ?,
                `lsShopProductIsOnSale` = ?,
                `lsShopProductRecommendedProducts` = ?,
                `lsShopProductDeliveryInfoSet` = ?,
                `lsShopProductProducer` = ?,
                `configurator` = ?,
                `flex_contents` = ?,
                `flex_contentsLanguageIndependent` = ?,
                `lsShopProductAttributesValues` = ?,
                `useScalePrice` = ?,
                `scalePriceType` = ?,
                `scalePriceQuantityDetectionMethod` = ?,
                `scalePriceQuantityDetectionAlwaysSeparateConfigurations` = ?,
                `scalePriceKeyword` = ?,
                `scalePrice` = ?
                ".$str_addGroupPriceFieldsToQuery."
                ".$str_customFieldsQueryExtension."
		");


        $arr_queryParams = array(
            $arr_preprocessedDataRow['productcode'], // String, maxlength 255
            time(),
            $arr_preprocessedDataRow['name'], // String, maxlength 255
            self::generateProductAlias($arr_preprocessedDataRow['name'], $arr_preprocessedDataRow['alias']),
            $arr_preprocessedDataRow['sorting'] && $arr_preprocessedDataRow['sorting'] > 0 ? $arr_preprocessedDataRow['sorting'] : 0, // int empty = 0
            $arr_preprocessedDataRow['keywords'], // text
            $arr_preprocessedDataRow['shortDescription'], // text
            $arr_preprocessedDataRow['description'], // text
            $arr_preprocessedDataRow['publish'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['category'], // blob
            $arr_preprocessedDataRow['price'] ? $arr_preprocessedDataRow['price'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['oldPrice'] ? $arr_preprocessedDataRow['oldPrice'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['useOldPrice'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['weight'] ? $arr_preprocessedDataRow['weight'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['taxclass'] ? $arr_preprocessedDataRow['taxclass'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['unit'], // String, maxlength 255
            $arr_preprocessedDataRow['quantityDecimals'] && $arr_preprocessedDataRow['quantityDecimals'] > 0 ? $arr_preprocessedDataRow['quantityDecimals'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['quantityComparisonUnit'], // String, maxlength 255
            $arr_preprocessedDataRow['quantityComparisonDivisor'] ? $arr_preprocessedDataRow['quantityComparisonDivisor'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['image'], // binary(16), translated, check unclear
            $arr_preprocessedDataRow['moreImages'], // blob, translated, check unclear
            $arr_preprocessedDataRow['template'], // String, maxlength 64
            $arr_preprocessedDataRow['new'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['onSale'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['recommendedProducts'], // blob, translated, check unclear
            $arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] ? $arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['producer'], // String, maxlength 255
            $arr_preprocessedDataRow['configurator'] ? $arr_preprocessedDataRow['configurator'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['flex_contents'], // blob, translated, check unclear
            $arr_preprocessedDataRow['flex_contentsLanguageIndependent'], // blob, translated, check unclear
            $arr_preprocessedDataRow['propertiesAndValues'], // blob, translated, check unclear
            $arr_preprocessedDataRow['useScalePrice'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['scalePriceType'], // String, maxlength 255
            $arr_preprocessedDataRow['scalePriceQuantityDetectionMethod'], // String, maxlength 255
            $arr_preprocessedDataRow['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['scalePriceKeyword'], // String, maxlength 255
            $arr_preprocessedDataRow['scalePrice'], // blob, translated, check unclear
        );

        $arr_queryParams = self::addGroupPriceFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'product');
        $arr_queryParams = self::addCustomFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'product');


        $arr_queryParams = array_merge($arr_queryParams, array(
            $arr_preprocessedDataRow['name'], // String, maxlength 255
            $arr_preprocessedDataRow['sorting'] && $arr_preprocessedDataRow['sorting'] > 0 ? $arr_preprocessedDataRow['sorting'] : 0, // int empty = 0
            $arr_preprocessedDataRow['keywords'], // text
            $arr_preprocessedDataRow['shortDescription'], // text
            $arr_preprocessedDataRow['description'], // text
            $arr_preprocessedDataRow['publish'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['category'], // blob
            $arr_preprocessedDataRow['price'] ? $arr_preprocessedDataRow['price'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['oldPrice'] ? $arr_preprocessedDataRow['oldPrice'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['useOldPrice'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['weight'] ? $arr_preprocessedDataRow['weight'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['taxclass'] ? $arr_preprocessedDataRow['taxclass'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['unit'], // String, maxlength 255
            $arr_preprocessedDataRow['quantityDecimals'] && $arr_preprocessedDataRow['quantityDecimals'] > 0 ? $arr_preprocessedDataRow['quantityDecimals'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['quantityComparisonUnit'], // String, maxlength 255
            $arr_preprocessedDataRow['quantityComparisonDivisor'] ? $arr_preprocessedDataRow['quantityComparisonDivisor'] : 0, // decimal, empty = 0
            $arr_preprocessedDataRow['image'], // binary(16), translated, check unclear
            $arr_preprocessedDataRow['moreImages'], // blob, translated, check unclear
            $arr_preprocessedDataRow['template'], // String, maxlength 64
            $arr_preprocessedDataRow['new'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['onSale'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['recommendedProducts'], // blob, translated, check unclear
            $arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] ? $arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['producer'], // String, maxlength 255
            $arr_preprocessedDataRow['configurator'] ? $arr_preprocessedDataRow['configurator'] : 0, // int, empty = 0
            $arr_preprocessedDataRow['flex_contents'], // blob, translated, check unclear
            $arr_preprocessedDataRow['flex_contentsLanguageIndependent'], // blob, translated, check unclear
            $arr_preprocessedDataRow['propertiesAndValues'], // blob, translated, check unclear
            $arr_preprocessedDataRow['useScalePrice'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['scalePriceType'], // String, maxlength 255
            $arr_preprocessedDataRow['scalePriceQuantityDetectionMethod'], // String, maxlength 255
            $arr_preprocessedDataRow['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
            $arr_preprocessedDataRow['scalePriceKeyword'], // String, maxlength 255
            $arr_preprocessedDataRow['scalePrice'] // blob, translated, check unclear
        ));

        $arr_queryParams = self::addGroupPriceFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'product');
        $arr_queryParams = self::addCustomFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'product');

        $obj_dbres_prod->execute($arr_queryParams);
        $productID = (int) $obj_dbres_prod->insertId;


        //Either an insert, in which case there is a $productID, or an update with changes
        //For an update without real changes, there is no $productID
        $identifier = ($productID) ? null : array('lsShopProductCode' => $arr_preprocessedDataRow['productcode']);



        //Attributes
        ls_shop_generalHelper::insertAttributeValueAllocations_byShopProductCode(
            $arr_preprocessedDataRow['propertiesAndValues']
            , $arr_preprocessedDataRow['productcode'], 0);


        //Writing language entries
        ls_shop_languageHelper::saveMultilanguageValue(
            $productID,
            $arr_preprocessedDataRow['language'],
            'tl_ls_shop_product_languages',
            array(
                'title',
                'alias',
                'keywords',
                'description',
                'lsShopProductQuantityUnit',
                'lsShopProductMengenvergleichUnit',
                'shortDescription',
                'flex_contents'
            ),
            array(
                $arr_preprocessedDataRow['name'],
                self::generateProductAlias(
                    $arr_preprocessedDataRow['name'],
                    $arr_preprocessedDataRow['alias'],
                    $productID,
                    $arr_preprocessedDataRow['language']
                ),
                $arr_preprocessedDataRow['keywords'],
                $arr_preprocessedDataRow['description'],
                $arr_preprocessedDataRow['unit'],
                $arr_preprocessedDataRow['quantityComparisonUnit'],
                $arr_preprocessedDataRow['shortDescription'],
                $arr_preprocessedDataRow['flex_contents']
            )
            , $identifier
        );


        /*
        * Carry out the stock change, provided the field is not actually empty. An entered "0" also leads to the corresponding stock change.
        * stock change. If the field value contains anything other than numbers from 0-9 and a point, a plus or a minus sign, then
        * the stock change is not carried out. If it contains a plus or minus sign, the calculation is carried out, if not
        * the value is entered as a fixed value.
        */
        if (
            $arr_preprocessedDataRow['changeStock'] !== ''
            &&	$arr_preprocessedDataRow['changeStock'] !== null
            &&	$arr_preprocessedDataRow['changeStock'] !== false
            &&	!preg_match('/[^0-9+-.]/', $arr_preprocessedDataRow['changeStock'])
        ) {
            ls_shop_generalHelper::changeStockDirectly('product', $productID, $arr_preprocessedDataRow['changeStock'], $arr_preprocessedDataRow['productcode']);
        }

	}

	public static function insertOrUpdateVariantRecord($arr_preprocessedDataRow) {
		// Prüfen, ob es eine Variante mit der Artikelnummer bereits gibt
		$int_alreadyExistsAsID = false;

		$obj_dbres_variant = \Database::getInstance()
			->prepare("
				SELECT		`id`
				FROM		`tl_ls_shop_variant`
				WHERE		`lsShopVariantCode` = ?
			")
			->execute($arr_preprocessedDataRow['productcode']);

		if ($obj_dbres_variant->numRows) {
			$obj_dbres_variant->first();
			$int_alreadyExistsAsID = $obj_dbres_variant->id;
		}

		/*
		 * Update the variant record if a variant with this product code already exists
		 */
		if ($int_alreadyExistsAsID) {
			$str_addGroupPriceFieldsToQuery = self::createGroupPriceFieldsForQuery('variant');
            $str_customFieldsQueryExtension = self::createCustomFieldsQueryExtension('variant');

			$obj_dbquery_updateVariant = \Database::getInstance()
				->prepare("
					UPDATE		`tl_ls_shop_variant`
					SET			`title` = ?,
								`alias` = ?,
								`sorting` = ?,
								`shortDescription` = ?,
								`description` = ?,
								`published` = ?,
								`lsShopProductVariantAttributesValues` = ?,
								`lsShopVariantPrice` = ?,
								`lsShopVariantPriceType` = ?,
								`lsShopVariantPriceOld` = ?,
								`lsShopVariantPriceTypeOld` = ?,
								`useOldPrice` = ?,
								`lsShopVariantWeight` = ?,
								`lsShopVariantWeightType` = ?,
								`lsShopVariantQuantityUnit` = ?,
								`lsShopVariantMengenvergleichUnit` = ?,
								`lsShopVariantMengenvergleichDivisor` = ?,
								`lsShopProductVariantMainImage` = ?,
								`lsShopProductVariantMoreImages` = ?,
								`lsShopVariantDeliveryInfoSet` = ?,
								`configurator` = ?,
								`flex_contents` = ?,
								`flex_contentsLanguageIndependent` = ?,
								`useScalePrice` = ?,
								`scalePriceType` = ?,
								`scalePriceQuantityDetectionMethod` = ?,
								`scalePriceQuantityDetectionAlwaysSeparateConfigurations` = ?,
								`scalePriceKeyword` = ?,
								`scalePrice` = ?
								".$str_addGroupPriceFieldsToQuery."
								".$str_customFieldsQueryExtension."
					WHERE		`id` = ?
				")
				->limit(1);

			$arr_queryParams = array(
				$arr_preprocessedDataRow['name'], // String, maxlength 255
				self::generateVariantAlias($arr_preprocessedDataRow['name'], $arr_preprocessedDataRow['alias'], $int_alreadyExistsAsID),
				$arr_preprocessedDataRow['sorting'] && $arr_preprocessedDataRow['sorting'] > 0 ? $arr_preprocessedDataRow['sorting'] : 0, // int empty = 0
				$arr_preprocessedDataRow['shortDescription'], // text
				$arr_preprocessedDataRow['description'], // text
				$arr_preprocessedDataRow['publish'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['propertiesAndValues'], // blob, translated, check unclear
				$arr_preprocessedDataRow['price'] ? $arr_preprocessedDataRow['price'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['priceType'], // String, maxlength 255
				$arr_preprocessedDataRow['oldPrice'] ? $arr_preprocessedDataRow['oldPrice'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['oldPriceType'], // String, maxlength 255
				$arr_preprocessedDataRow['useOldPrice'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['weight'] ? $arr_preprocessedDataRow['weight'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['weightType'], // String, maxlength 255
				$arr_preprocessedDataRow['unit'], // String, maxlength 255
				$arr_preprocessedDataRow['quantityComparisonUnit'], // String, maxlength 255
				$arr_preprocessedDataRow['quantityComparisonDivisor'] ? $arr_preprocessedDataRow['quantityComparisonDivisor'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['image'], // binary(16), translated, check unclear
				$arr_preprocessedDataRow['moreImages'], // blob, translated, check unclear
				$arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] ? $arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
				$arr_preprocessedDataRow['configurator'] ? $arr_preprocessedDataRow['configurator'] : 0, // int, empty = 0
				$arr_preprocessedDataRow['flex_contents'], // blob, translated, check unclear
				$arr_preprocessedDataRow['flex_contentsLanguageIndependent'], // blob, translated, check unclear
				$arr_preprocessedDataRow['useScalePrice'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['scalePriceType'], // String, maxlength 255
				$arr_preprocessedDataRow['scalePriceQuantityDetectionMethod'], // String, maxlength 255
				$arr_preprocessedDataRow['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['scalePriceKeyword'], // String, maxlength 255
				$arr_preprocessedDataRow['scalePrice'] // blob, translated, check unclear
			);

			$arr_queryParams = self::addGroupPriceFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'variant');
            $arr_queryParams = self::addCustomFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'variant');

			// Must be the last parameter in the array
			$arr_queryParams[] = $int_alreadyExistsAsID;

			$obj_dbquery_updateVariant->execute($arr_queryParams);

			ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable($arr_preprocessedDataRow['propertiesAndValues'], $int_alreadyExistsAsID, 1);

			/*
			 * Durchführen der Lagerbestandsänderung, sofern das Feld nicht wirklich leer ist. Eine eingetragene "0" führt auch zur entsprechenden
			 * Lagerbestandsänderung. Enthält der Feldwert etwas anderes als Zahlen von 0-9 und einen Punkt, ein Plus- bzw. ein Minuszeichen, so
			 * wird die Lagerbestandsänderung nicht durchgeführt. Ist ein Plus- oder Minuszeichen enthalten, so wird berechnet, falls nicht, dann
			 * wird der Wert fest eingetragen.
			 */
			if (
				$arr_preprocessedDataRow['changeStock'] !== ''
				&&	$arr_preprocessedDataRow['changeStock'] !== null
				&&	$arr_preprocessedDataRow['changeStock'] !== false
				&&	!preg_match('/[^0-9+-.]/', $arr_preprocessedDataRow['changeStock'])
			) {
				ls_shop_generalHelper::changeStockDirectly('variant', $int_alreadyExistsAsID, $arr_preprocessedDataRow['changeStock']);
			}

			/*
			 * Spracheinträge schreiben
			 */
			ls_shop_languageHelper::saveMultilanguageValue(
				$int_alreadyExistsAsID,
				$arr_preprocessedDataRow['language'],
				'tl_ls_shop_variant_languages',
				array(
					'title',
					'alias',
					'description',
					'lsShopVariantQuantityUnit',
					'lsShopVariantMengenvergleichUnit',
					'shortDescription',
					'flex_contents'
				),
				array(
					$arr_preprocessedDataRow['name'],
					self::generateVariantAlias(
						$arr_preprocessedDataRow['name'],
						$arr_preprocessedDataRow['alias'],
						$int_alreadyExistsAsID,
						$arr_preprocessedDataRow['language']
					),
					$arr_preprocessedDataRow['description'],
					$arr_preprocessedDataRow['unit'],
					$arr_preprocessedDataRow['quantityComparisonUnit'],
					$arr_preprocessedDataRow['shortDescription'],
					$arr_preprocessedDataRow['flex_contents']
				)
			);

		}

		/*
		 * Insert a variant record if no variant with this product code exists
		 */
		else {
			$int_parentProductId = self::getProductIdForProductCode($arr_preprocessedDataRow['parentProductcode']);

			if (!$int_parentProductId) {
				throw new \Exception('no parent product found with product code '.$arr_preprocessedDataRow['parentProductcode'].' for variant with product code '.$arr_preprocessedDataRow['productcode']);
			}

			$str_addGroupPriceFieldsToQuery = self::createGroupPriceFieldsForQuery('variant');
            $str_customFieldsQueryExtension = self::createCustomFieldsQueryExtension('variant');

			$obj_dbquery_insertVariant = \Database::getInstance()
				->prepare("
				INSERT INTO	`tl_ls_shop_variant`
				SET			`tstamp` = ?,
							`pid` = ?,
							`title` = ?,
							`alias` = ?,
							`sorting` = ?,
							`lsShopVariantCode` = ?,
							`shortDescription` = ?,
							`description` = ?,
							`published` = ?,
							`lsShopProductVariantAttributesValues` = ?,
							`lsShopVariantPrice` = ?,
							`lsShopVariantPriceType` = ?,
							`lsShopVariantPriceOld` = ?,
							`lsShopVariantPriceTypeOld` = ?,
							`useOldPrice` = ?,
							`lsShopVariantWeight` = ?,
							`lsShopVariantWeightType` = ?,
							`lsShopVariantQuantityUnit` = ?,
							`lsShopVariantMengenvergleichUnit` = ?,
							`lsShopVariantMengenvergleichDivisor` = ?,
							`lsShopProductVariantMainImage` = ?,
							`lsShopProductVariantMoreImages` = ?,
							`lsShopVariantDeliveryInfoSet` = ?,
							`configurator` = ?,
							`flex_contents` = ?,
							`flex_contentsLanguageIndependent` = ?,
							`useScalePrice` = ?,
							`scalePriceType` = ?,
							`scalePriceQuantityDetectionMethod` = ?,
							`scalePriceQuantityDetectionAlwaysSeparateConfigurations` = ?,
							`scalePriceKeyword` = ?,
							`scalePrice` = ?
							".$str_addGroupPriceFieldsToQuery."
							".$str_customFieldsQueryExtension."
			");

			$arr_queryParams = array(
				time(),
				$int_parentProductId,
				$arr_preprocessedDataRow['name'], // String, maxlength 255
				self::generateVariantAlias($arr_preprocessedDataRow['name'], $arr_preprocessedDataRow['alias']),
				$arr_preprocessedDataRow['sorting'] && $arr_preprocessedDataRow['sorting'] > 0 ? $arr_preprocessedDataRow['sorting'] : 0, // int empty = 0
				$arr_preprocessedDataRow['productcode'], // String, maxlength 255
				$arr_preprocessedDataRow['shortDescription'], // text
				$arr_preprocessedDataRow['description'], // text
				$arr_preprocessedDataRow['publish'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['propertiesAndValues'], // blob, translated, check unclear
				$arr_preprocessedDataRow['price'] ? $arr_preprocessedDataRow['price'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['priceType'], // String, maxlength 255
				$arr_preprocessedDataRow['oldPrice'] ? $arr_preprocessedDataRow['oldPrice'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['oldPriceType'], // String, maxlength 255
				$arr_preprocessedDataRow['useOldPrice'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['weight'] ? $arr_preprocessedDataRow['weight'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['weightType'], // String, maxlength 255
				$arr_preprocessedDataRow['unit'], // String, maxlength 255
				$arr_preprocessedDataRow['quantityComparisonUnit'], // String, maxlength 255
				$arr_preprocessedDataRow['quantityComparisonDivisor'] ? $arr_preprocessedDataRow['quantityComparisonDivisor'] : 0, // decimal, empty = 0
				$arr_preprocessedDataRow['image'], // binary(16), translated, check unclear
				$arr_preprocessedDataRow['moreImages'], // blob, translated, check unclear
				$arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] ? $arr_preprocessedDataRow['settingsForStockAndDeliveryTime'] : 0, // int, empty = 0
				$arr_preprocessedDataRow['configurator'] ? $arr_preprocessedDataRow['configurator'] : 0, // int, empty = 0
				$arr_preprocessedDataRow['flex_contents'], // blob, translated, check unclear
				$arr_preprocessedDataRow['flex_contentsLanguageIndependent'], // blob, translated, check unclear
				$arr_preprocessedDataRow['useScalePrice'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['scalePriceType'], // String, maxlength 255
				$arr_preprocessedDataRow['scalePriceQuantityDetectionMethod'], // String, maxlength 255
				$arr_preprocessedDataRow['scalePriceQuantityDetectionAlwaysSeparateConfigurations'] ? '1' : '', // 1 or ''
				$arr_preprocessedDataRow['scalePriceKeyword'], // String, maxlength 255
				$arr_preprocessedDataRow['scalePrice'] // blob, translated, check unclear
			);

			$arr_queryParams = self::addGroupPriceFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'variant');
            $arr_queryParams = self::addCustomFieldsToQueryParam($arr_queryParams, $arr_preprocessedDataRow, 'variant');

			$obj_dbquery_insertVariant->execute($arr_queryParams);

			$int_newVariantId = $obj_dbquery_insertVariant->insertId;

			ls_shop_generalHelper::insertAttributeValueAllocationsInAllocationTable($arr_preprocessedDataRow['propertiesAndValues'], $int_newVariantId, 1);

			/*
			 * Durchführen der Lagerbestandsänderung, sofern das Feld nicht wirklich leer ist. Eine eingetragene "0" führt auch zur entsprechenden
			 * Lagerbestandsänderung. Enthält der Feldwert etwas anderes als Zahlen von 0-9 und einen Punkt, ein Plus- bzw. ein Minuszeichen, so
			 * wird die Lagerbestandsänderung nicht durchgeführt. Ist ein Plus- oder Minuszeichen enthalten, so wird berechnet, falls nicht, dann
			 * wird der Wert fest eingetragen.
			 */
			if (
				$arr_preprocessedDataRow['changeStock'] !== ''
				&&	$arr_preprocessedDataRow['changeStock'] !== null
				&&	$arr_preprocessedDataRow['changeStock'] !== false
				&&	!preg_match('/[^0-9+-.]/', $arr_preprocessedDataRow['changeStock'])
			) {
				ls_shop_generalHelper::changeStockDirectly('variant', $int_newVariantId, $arr_preprocessedDataRow['changeStock']);
			}

			/*
			 * Spracheinträge schreiben
			 */
			ls_shop_languageHelper::saveMultilanguageValue(
				$int_newVariantId,
				$arr_preprocessedDataRow['language'],
				'tl_ls_shop_variant_languages',
				array(
					'title',
					'alias',
					'description',
					'lsShopVariantQuantityUnit',
					'lsShopVariantMengenvergleichUnit',
					'shortDescription',
					'flex_contents'
				),
				array(
					$arr_preprocessedDataRow['name'],
					self::generateVariantAlias(
						$arr_preprocessedDataRow['name'],
						$arr_preprocessedDataRow['alias'],
						$int_newVariantId,
						$arr_preprocessedDataRow['language']
					),
					$arr_preprocessedDataRow['description'],
					$arr_preprocessedDataRow['unit'],
					$arr_preprocessedDataRow['quantityComparisonUnit'],
					$arr_preprocessedDataRow['shortDescription'],
					$arr_preprocessedDataRow['flex_contents']
				)
			);

		}
	}

	public static function writeProductLanguageData($arr_preprocessedDataRow) {
		$int_parentProductId = self::getProductIdForProductCode($arr_preprocessedDataRow['parentProductcode']);

		if (!$int_parentProductId) {
			throw new \Exception('no parent product found with product code '.$arr_preprocessedDataRow['parentProductcode'].' for product language data');
		}

		ls_shop_languageHelper::saveMultilanguageValue(
			$int_parentProductId,
			$arr_preprocessedDataRow['language'],
			'tl_ls_shop_product_languages',
			array(
				'title',
				'alias',
				'keywords',
				'description',
				'lsShopProductQuantityUnit',
				'lsShopProductMengenvergleichUnit',
				'shortDescription',
				'flex_contents'
			),
			array(
				$arr_preprocessedDataRow['name'],
				self::generateProductAlias(
					$arr_preprocessedDataRow['name'],
					$arr_preprocessedDataRow['alias'],
					$int_parentProductId,
					$arr_preprocessedDataRow['language']
				),
				$arr_preprocessedDataRow['keywords'],
				$arr_preprocessedDataRow['description'],
				$arr_preprocessedDataRow['unit'],
				$arr_preprocessedDataRow['quantityComparisonUnit'],
				$arr_preprocessedDataRow['shortDescription'],
				$arr_preprocessedDataRow['flex_contents']
			)
		);
	}

	public static function writeVariantLanguageData($arr_preprocessedDataRow) {
		$int_parentVariantId = self::getVariantIdForProductCode($arr_preprocessedDataRow['parentProductcode']);

		if (!$int_parentVariantId) {
			throw new \Exception('no parent variant found with product code '.$arr_preprocessedDataRow['parentProductcode'].' for variant language data');
		}

		/*
		 * Datensatz in Sprachtabelle schreiben
		 */
		ls_shop_languageHelper::saveMultilanguageValue(
			$int_parentVariantId,
			$arr_preprocessedDataRow['language'],
			'tl_ls_shop_variant_languages',
			array(
				'title',
				'alias',
				'description',
				'lsShopVariantQuantityUnit',
				'lsShopVariantMengenvergleichUnit',
				'shortDescription',
				'flex_contents'
			),
			array(
				$arr_preprocessedDataRow['name'],
				self::generateVariantAlias(
					$arr_preprocessedDataRow['name'],
					$arr_preprocessedDataRow['alias'],
					$int_parentVariantId,
					$arr_preprocessedDataRow['language']
				),
				$arr_preprocessedDataRow['description'],
				$arr_preprocessedDataRow['unit'],
				$arr_preprocessedDataRow['quantityComparisonUnit'],
				$arr_preprocessedDataRow['shortDescription'],
				$arr_preprocessedDataRow['flex_contents']
			)
		);
	}

	public static function changeStockForProductWithCode($str_code, $var_stockChange)
	{
		$int_productId = ls_shop_generalHelper::getProductIdForCode($str_code);

		if (!$int_productId) {
			throw new \Exception('product could not be found');
		}

		self::changeStockForProductWithId($int_productId, $var_stockChange);
	}

	public static function changeStockForProductWithId($int_productId, $var_stockChange) {
		ls_shop_generalHelper::changeStockDirectly('product', $int_productId, $var_stockChange);
	}

	public static function changeStockForVariantWithCode($str_code, $var_stockChange)
	{
		$int_variantId = ls_shop_generalHelper::getVariantIdForCode($str_code);

		if (!$int_variantId) {
			throw new \Exception('variant could not be found');
		}

		self::changeStockForVariantWithId($int_variantId, $var_stockChange);
	}

	public static function changeStockForVariantWithId($int_variantId, $var_stockChange) {
		ls_shop_generalHelper::changeStockDirectly('variant', $int_variantId, $var_stockChange);
	}

	public static function deleteProductWithCode($str_code) {
		$int_productId = ls_shop_generalHelper::getProductIdForCode($str_code);

		if (!$int_productId) {
			throw new \Exception('product could not be found');
		}

		self::deleteProductWithId($int_productId);
	}

	public static function deleteProductLanguageWithCode($str_code, $str_language) {
		$int_productId = ls_shop_generalHelper::getProductIdForCode($str_code);

		if (!$int_productId) {
			throw new \Exception('product could not be found');
		}

		self::deleteProductLanguageWithId($int_productId, $str_language);
	}

	public static function deleteProductLanguageWithId($int_productId, $str_language) {
		ls_shop_languageHelper::deleteEntry($int_productId, 'tl_ls_shop_product', array($str_language));
	}

	public static function deleteProductWithId($int_productId) {
		if (!$int_productId) {
			throw new \Exception('no product id given.');
		}

		\Database::getInstance()
		->prepare("
			DELETE FROM	`tl_ls_shop_product`
			WHERE		`id` = ?
		")
		->limit(1)
		->execute($int_productId);

		self::deleteVariantsForProductWithId($int_productId);
	}

	public static function deleteVariantsForProductWithId($int_productId) {
		if (!$int_productId) {
			throw new \Exception('no product id given.');
		}

		$obj_dbres_variants = \Database::getInstance()
		->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_variant`
			WHERE		`pid` = ?
		")
		->execute($int_productId);

		while ($obj_dbres_variants->next()) {
			self::deleteVariantWithId($obj_dbres_variants->id);
		}
	}

	public static function deleteVariantWithCode($str_code) {
		$int_variantId = ls_shop_generalHelper::getVariantIdForCode($str_code);

		if (!$int_variantId) {
			throw new \Exception('variant could not be found');
		}

		self::deleteVariantWithId($int_variantId);
	}

	public static function deleteVariantWithId($int_variantId) {
		if (!$int_variantId) {
			throw new \Exception('no variant id given.');
		}

		\Database::getInstance()
		->prepare("
			DELETE FROM	`tl_ls_shop_variant`
			WHERE		`id` = ?
		")
		->limit(1)
		->execute($int_variantId);
	}

	public static function deleteVariantLanguageWithCode($str_code, $str_language) {
		$int_variantId = ls_shop_generalHelper::getVariantIdForCode($str_code);

		if (!$int_variantId) {
			throw new \Exception('variant could not be found');
		}

		self::deleteVariantLanguageWithId($int_variantId, $str_language);
	}

	public static function deleteVariantLanguageWithId($int_variantId, $str_language) {
		ls_shop_languageHelper::deleteEntry($int_variantId, 'tl_ls_shop_variant', array($str_language));
	}

	public static function getApiResourceDescriptions() {
		if (!isset($GLOBALS['merconis_globals']['cache']['arr_productManagementApiResourceDescriptions'])) {
			$arr_resourceDescriptions = array();

			$obj_reflection = new \ReflectionClass('Merconis\Core\ls_shop_apiController_productManagement');
			$arr_reflectionMethods = $obj_reflection->getMethods();

			if (is_array($arr_reflectionMethods)) {
				foreach ($arr_reflectionMethods as $obj_reflectionMethod) {
					/*
					 * Skip methods with unprefixed names
					 */
					if (strpos($obj_reflectionMethod->name, 'apiResource_') === false) {
						continue;
					}

					$str_resourceDescription = $obj_reflectionMethod->getDocComment();

					// clean the comment
					$str_resourceDescription = preg_replace('/\h\h+/', '', $str_resourceDescription);
					$str_resourceDescription = preg_replace('/\*\s?/', '', $str_resourceDescription);
					$str_resourceDescription = substr($str_resourceDescription, 1);
					$str_resourceDescription = substr($str_resourceDescription, 0, -1);
					$str_resourceDescription = trim($str_resourceDescription);

					$arr_resourceDescriptions[$obj_reflectionMethod->getName()] = $str_resourceDescription;
				}
			}

			$GLOBALS['merconis_globals']['cache']['arr_productManagementApiResourceDescriptions'] = $arr_resourceDescriptions;
		}

		return $GLOBALS['merconis_globals']['cache']['arr_productManagementApiResourceDescriptions'];
	}

	public static function getAvailableProductImages() {
		
	}

    /*
     * Returns a list of fields with question mark parameters for an SQL query that match
     * individual fields of a product or variant (depending on the parameter)
     * For further SQL queries 'on duplicate key update', it also returns in the optional return parameters
     * the separate field names and a list with question marks.
     *
     *  @param  {string}    $str_productOrVariant       either 'product' or something else for variant
     *  @param  {string}    $fieldnames                 a list containing the field names without question marks
     *  @param  {string}    $questionMarks              a list containing the question marks without the field names (for the values part)
     *  @return {string}    $queryExtension             a list with field names and question marks
     */
    private static function createCustomFieldsQueryExtension(string $str_productOrVariant = 'product', ?string &$fieldnames = null, ?string &$questionMarks = null): string
    {
        $queryExtension = '';
        $customFields = $str_productOrVariant === 'product' ? self::$arr_customFieldsForProducts : self::$arr_customFieldsForVariants;

        foreach ($customFields as $customFieldName) {
            $queryExtension .= ", `" . $customFieldName . "` = ?";
            $fieldnames .= ", `" . $customFieldName . "`";
            $questionMarks .= ", ?";
        }

        return $queryExtension;
    }

    private static function addCustomFieldsToQueryParam(array $arr_queryParams, array $arr_preprocessedDataRow, string $str_productOrVariant = 'product'): array
    {
        $customFields = $str_productOrVariant === 'product' ? self::$arr_customFieldsForProducts : self::$arr_customFieldsForVariants;

        foreach ($customFields as $customFieldName) {
            $arr_queryParams[] = $arr_preprocessedDataRow[$customFieldName] ?? '';
        }

        return $arr_queryParams;
    }
}
