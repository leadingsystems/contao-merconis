<?php
namespace Merconis\Core;

class ls_shop_filterHelper {
    public static function getFilterSummary() {
        global $objPage;

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

        $arr_filterSummary = [
            'arr_attributes' => [],
            'arr_producers' => $session_lsShopCart['filter']['criteriaToActuallyFilterWith']['producers'] ?? null,
            'arr_price' => $session_lsShopCart['filter']['criteriaToActuallyFilterWith']['price'] ?? null,
        ];

        $arr_filterAllFields = [
            'arr_attributes' => [],
            'arr_producers' => $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['producers'],
            'arr_price' => $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['price'],
        ];

        if (is_array($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['attributes'] ?? null)) {
            foreach ($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['attributes'] as $int_filterAttributeId => $arr_filterValues) {
                $str_filterAttributeName = ls_shop_languageHelper::getMultiLanguage($int_filterAttributeId, 'tl_ls_shop_attributes', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                $arr_filterSummary['arr_attributes'][$int_filterAttributeId] = [
                    'str_title' => $str_filterAttributeName,
                    'arr_values' => [],
                    'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$session_lsShopCart['filter']['filterModeSettingsByAttributes'][$int_filterAttributeId]]
                ];

                foreach ($arr_filterValues as $int_filterValueId) {
                    $str_filterValueName = ls_shop_languageHelper::getMultiLanguage($int_filterValueId, 'tl_ls_shop_attribute_values', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                    $arr_filterSummary['arr_attributes'][$int_filterAttributeId]['arr_values'][$int_filterValueId] = $str_filterValueName;
                }
            }
        }

        $arrFilterFieldInfos = ls_shop_filterHelper::getFilterFieldInfos();

        foreach ($arrFilterFieldInfos as $filterFieldID => $arrFilterFieldInfo) {
            if ($arrFilterFieldInfo['dataSource'] !== 'attribute') {
                continue;
            }

            /*
             * If based on the current product list there are no attributes to be used as criteria in the filter form
             * or no values for the current attribute, we don't create a summary item
             */
            if (
                !is_array($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'])
                || !count($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'])
                || !isset($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
                || !is_array($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
                || !count($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
            ) {
                continue;
            }

            $int_filterAttributeId = $arrFilterFieldInfo['sourceAttribute'];
            $arr_filterValues = $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$int_filterAttributeId];

            $str_filterAttributeName = ls_shop_languageHelper::getMultiLanguage($int_filterAttributeId, 'tl_ls_shop_attributes', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
            $arr_filterAllFields['arr_attributes'][$int_filterAttributeId] = [
                'str_title' => $str_filterAttributeName,
                'arr_values' => [],
                'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$session_lsShopCart['filter']['filterModeSettingsByAttributes'][$int_filterAttributeId] ?? null] ?? null
            ];

            foreach ($arr_filterValues as $int_filterValueId) {
                $str_filterValueName = ls_shop_languageHelper::getMultiLanguage($int_filterValueId, 'tl_ls_shop_attribute_values', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                $arr_filterAllFields['arr_attributes'][$int_filterAttributeId]['arr_values'][$int_filterValueId] = $str_filterValueName;
            }

        }

        $bln_attributesFilterCurrentlyAvailable = is_array($arr_filterAllFields['arr_attributes']) && count($arr_filterAllFields['arr_attributes']);
        $bln_poducerFilterCurrentlyAvailable = is_array($arr_filterAllFields['arr_producers']) && count($arr_filterAllFields['arr_producers']);
        $bln_priceFilterCurrentlyAvailable = (
            is_array($arr_filterAllFields['arr_price'])
            && (
                (isset($arr_filterAllFields['arr_price']['low']) && $arr_filterAllFields['arr_price']['low'])
                || (isset($arr_filterAllFields['arr_price']['high']) && $arr_filterAllFields['arr_price']['high'])
            )
        );

        $bln_currentlyFilteringByAttributes = is_array($arr_filterSummary['arr_attributes']) && count($arr_filterSummary['arr_attributes']);
        $bln_currentlyFilteringByProducer = is_array($arr_filterSummary['arr_producers']) && count($arr_filterSummary['arr_producers']);
        $bln_currentlyFilteringByPrice = (
            is_array($arr_filterSummary['arr_price'])
            && (
                (isset($arr_filterSummary['arr_price']['low']) && $arr_filterSummary['arr_price']['low'])
                || (isset($arr_filterSummary['arr_price']['high']) && $arr_filterSummary['arr_price']['high'])
            )
        );



        /*
         * Handle sorting by priority -->
         */
        $arr_filterFieldSortingNumbers = [];
        $arr_filterFieldPriorities = [];

        $obj_dbres_filterFieldPriorities = \Database::getInstance()
            ->prepare("
                SELECT  id,
                        sourceAttribute,
                        dataSource,
                        priority
                FROM    tl_ls_shop_filter_fields
            ")
            ->execute();

        while ($obj_dbres_filterFieldPriorities->next()) {
            $arr_filterFieldPriorities[$obj_dbres_filterFieldPriorities->dataSource . ($obj_dbres_filterFieldPriorities->dataSource === 'attribute' ? '_' . $obj_dbres_filterFieldPriorities->sourceAttribute : '')] = $obj_dbres_filterFieldPriorities->priority;
        }

        foreach (array_keys($arr_filterAllFields['arr_attributes']) as $int_filterAttributeId) {
            $arr_filterFieldSortingNumbers['attribute_' . $int_filterAttributeId] = $arr_filterFieldPriorities['attribute_' . $int_filterAttributeId];
        }

        if ($bln_poducerFilterCurrentlyAvailable) {
            $arr_filterFieldSortingNumbers['producer'] = $arr_filterFieldPriorities['producer'];
        }

        if ($bln_priceFilterCurrentlyAvailable) {
            $arr_filterFieldSortingNumbers['price'] = $arr_filterFieldPriorities['price'];
        }

        arsort($arr_filterFieldSortingNumbers);

        $int_countSorting = 0;
        foreach (array_keys($arr_filterFieldSortingNumbers) as $str_filterFieldSortingNumbersKey) {
            $int_countSorting++;
            $arr_filterFieldSortingNumbers[$str_filterFieldSortingNumbersKey] = $int_countSorting;
        }
        /*
         * <--
         */

        return [
            'arr_filterSummary' => $arr_filterSummary,
            'arr_filterAllFields' => $arr_filterAllFields,
            'int_numAvailableFilterFields' => ($bln_poducerFilterCurrentlyAvailable ? 1 : 0) + ($bln_priceFilterCurrentlyAvailable ? 1 : 0) + count($arr_filterAllFields['arr_attributes']),
            'bln_attributesFilterCurrentlyAvailable' => $bln_attributesFilterCurrentlyAvailable,
            'bln_poducerFilterCurrentlyAvailable' => $bln_poducerFilterCurrentlyAvailable,
            'bln_priceFilterCurrentlyAvailable' => $bln_priceFilterCurrentlyAvailable,
            'bln_currentlyFilteringByAttributes' => $bln_currentlyFilteringByAttributes,
            'bln_currentlyFilteringByProducer' => $bln_currentlyFilteringByProducer,
            'bln_currentlyFilteringByPrice' => $bln_currentlyFilteringByPrice,
            'arr_filterFieldSortingNumbers' => $arr_filterFieldSortingNumbers
        ];
    }

    public static function getFilterSummaryHtml($objFEModule = null) {
        if (!is_object($objFEModule)) {
            return '';
        }

        $arr_summaryData = self::getFilterSummary();

        $obj_template = new \FrontendTemplate($objFEModule->ls_shop_filterSummary_template);
        $obj_template->arr_filterSummary = $arr_summaryData['arr_filterSummary'];
        $obj_template->arr_filterAllFields = $arr_summaryData['arr_filterAllFields'];
        $obj_template->int_numAvailableFilterFields = $arr_summaryData['int_numAvailableFilterFields'];
        $obj_template->bln_attributesFilterCurrentlyAvailable = $arr_summaryData['bln_attributesFilterCurrentlyAvailable'];
        $obj_template->bln_poducerFilterCurrentlyAvailable = $arr_summaryData['bln_poducerFilterCurrentlyAvailable'];
        $obj_template->bln_priceFilterCurrentlyAvailable = $arr_summaryData['bln_priceFilterCurrentlyAvailable'];
        $obj_template->bln_currentlyFilteringByAttributes = $arr_summaryData['bln_currentlyFilteringByAttributes'];
        $obj_template->bln_currentlyFilteringByProducer = $arr_summaryData['bln_currentlyFilteringByProducer'];
        $obj_template->bln_currentlyFilteringByPrice = $arr_summaryData['bln_currentlyFilteringByPrice'];
        $obj_template->arr_filterFieldSortingNumbers = $arr_summaryData['arr_filterFieldSortingNumbers'];
        return $obj_template->parse();
    }

	public static function createEmptyFilterSession() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

        $session_lsShopCart['filter'] = array(
			'criteria' => array(
				'attributes' => array(),
				'price' => array(
					'low' => 0,
					'high' => 0
				),
				'producers' => array()
			),
			'arrCriteriaToUseInFilterForm' => array(
				'attributes' => array(),
				'price' => array(
					'low' => null,
					'high' => null
				),
				'producers' => array()
			),
			'matchedProducts' => array(),
			'matchedVariants' => array(),
			'matchEstimates' => array(
				'attributeValues' => array(),
				'producers' => array()
			),
			'lastResetTimestamp' => time(),
			'noMatchEstimatesDetermined' => false
		);
        $session->set('lsShop', $session_lsShopCart);
	}

	public static function getFilterFieldValues($arrFilterFieldInfo = null) {
		if (!$arrFilterFieldInfo) {
			return array();
		}

		$arrFilterFieldValues = array();

		switch ($arrFilterFieldInfo['dataSource']) {
			case 'producer':
				$objFilterFields = \Database::getInstance()
					->prepare("
						SELECT		*
						FROM		`tl_ls_shop_filter_field_values`
						WHERE		`pid` = ?
						ORDER BY	`sorting` ASC
					")
					->execute($arrFilterFieldInfo['id']);

				if ($objFilterFields->numRows) {
					$arrFilterFieldValues = $objFilterFields->fetchAllAssoc();
				}
				break;

			case 'attribute':
				$arrAttributeValues = ls_shop_generalHelper::getAttributeValues($arrFilterFieldInfo['sourceAttribute']);
				foreach ($arrAttributeValues as $attributeValueID => $arrAttributeValue) {
					$tmpFilterFieldValue = $arrAttributeValue;
					$tmpFilterFieldValue['filterValue'] = $attributeValueID;
					$arrFilterFieldValues[] = $tmpFilterFieldValue;
				}
				break;
		}

		return $arrFilterFieldValues;
	}

	public static function getFilterFieldInfos() {
		/** @var \PageModel $objPage */
		global $objPage;

		if (!isset($GLOBALS['merconis_globals']['filterFieldInfos'])) {
			$arrFilterFields = array();

			$objFilterFields = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_filter_fields`
				WHERE		`published` = '1'
				ORDER BY	`priority` DESC
			")
				->execute();

			while ($objFilterFields->next()) {
				$arrFilterFields[$objFilterFields->id] = $objFilterFields->row();
				$arrFilterFields[$objFilterFields->id]['title'] = ls_shop_languageHelper::getMultiLanguage($objFilterFields->id, 'tl_ls_shop_filter_fields', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
				$arrFilterFields[$objFilterFields->id]['fieldValues'] = ls_shop_filterHelper::getFilterFieldValues($arrFilterFields[$objFilterFields->id]);
			}

			$GLOBALS['merconis_globals']['filterFieldInfos'] = $arrFilterFields;
		}

		return $GLOBALS['merconis_globals']['filterFieldInfos'];
	}

	public static function resetCriteriaToUseInFilterForm() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

        $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm'] = array(
			'attributes' => array(),
			'price' => array(
				'low' => null,
				'high' => null
			),
			'producers' => array()
		);
        $session->set('lsShop', $session_lsShopCart);
	}

	public static function addPriceToCriteriaUsedInFilterForm($price, $where = 'arrCriteriaToUseInFilterForm') {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		if ($session_lsShopCart['filter'][$where]['price']['low'] === null || $price < $session_lsShopCart['filter'][$where]['price']['low']) {
            $session_lsShopCart['filter'][$where]['price']['low'] = $price;
		}
		if ($session_lsShopCart['filter'][$where]['price']['high'] === null || $price > $session_lsShopCart['filter'][$where]['price']['high']) {
            $session_lsShopCart['filter'][$where]['price']['high'] = $price;
		}

        $session->set('lsShop', $session_lsShopCart);
	}

	public static function addProducerToCriteriaUsedInFilterForm($strProducer = '', $where = 'arrCriteriaToUseInFilterForm') {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		if (!$strProducer || in_array($strProducer, $session_lsShopCart['filter'][$where]['producers'])) {
			return;
		}

        $session_lsShopCart['filter'][$where]['producers'][] = $strProducer;
        $session->set('lsShop', $session_lsShopCart);
	}

	public static function addAttributeValueToCriteriaUsedInFilterForm($attributeID = null, $varAttributeValueID = null, $where = 'arrCriteriaToUseInFilterForm') {
		if (!$attributeID || !$varAttributeValueID) {
			return;
		}

		if (is_array($varAttributeValueID)) {
			foreach ($varAttributeValueID as $attributeValueID) {
				self::addAttributeValueToCriteriaUsedInFilterForm($attributeID, $attributeValueID, $where);
			}
			return;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		if (!isset($session_lsShopCart['filter'][$where]['attributes'][$attributeID])) {
            $session_lsShopCart['filter'][$where]['attributes'][$attributeID] = array();
		}

		if (!in_array($varAttributeValueID, $session_lsShopCart['filter'][$where]['attributes'][$attributeID])) {
            $session_lsShopCart['filter'][$where]['attributes'][$attributeID][] = $varAttributeValueID;
		}

        $session->set('lsShop', $session_lsShopCart);
	}

	/*
	 * This function checks whether a product matches the filter or not. Several
	 * checks are performed in this function and as soon as one filter criteria
	 * doesn't match, this function returns false, indicating that the product
	 * should be filtered out. If that happens other filter criteria will not
	 * be checked. There's one exception though: If a product has variants and the product
	 * itself does not match the filter criteria, the variants will be checked
	 * because if one ore more variants match, the product has to be shown in
	 * product lists as well.
	 */
	public static function checkIfProductMatchesFilter($arrProductInfo = null, $arrCriteriaToFilterWith = null, $blnStoreProductAndVariantMatchesInSession = true, &$numVariantMatches = 0) {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		if (!$arrCriteriaToFilterWith) {
			$arrCriteriaToFilterWith = $session_lsShopCart['filter']['criteriaToActuallyFilterWith'];
		}

		$blnWholeProductCouldStillMatch = true;
		$blnVariantsCouldStillMatch = true;

		/*
		 * Check the producer first if it is set as a filter criteria
		 * because if it doesn't match, the product is already filtered
		 * out and we don't even need to check it's variants
		 */
		if (is_array($arrCriteriaToFilterWith['producers']) && count($arrCriteriaToFilterWith['producers'])) {
			if (!in_array($arrProductInfo['lsShopProductProducer'], $arrCriteriaToFilterWith['producers'])) {
				$blnWholeProductCouldStillMatch = false;
				$blnVariantsCouldStillMatch = false;
			}
		}

		/*
		 * Check the product's attributes
		 */
		if ($blnWholeProductCouldStillMatch) {
			if (is_array($arrCriteriaToFilterWith['attributes'])) {
				foreach ($arrCriteriaToFilterWith['attributes'] as $attributeID => $attributeValueIDs) {
					/*
					 * The array returned by array_intersect() contains the requested attributeValueIDs which
					 * are also included in the product's attributeValueIDs.
					 *
					 * In filterMode "or": If we get at least one attributeValueID
					 * that matches, the product is a match for this attribute,
					 * otherwise it's not and we return false.
					 *
					 * In filterMode "and": If all the attributeValueIDs match,
					 * the product is a match for this attribute, otherwise it's
					 * not and we return false.
					 */
					if (($session_lsShopCart['filter']['filterModeSettingsByAttributes'][$attributeID] ?? null) === 'and') {
						if (count(array_intersect($attributeValueIDs, $arrProductInfo['attributeValueIDs'])) !== count($attributeValueIDs)) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					} else {
						if (!count(array_intersect($attributeValueIDs, $arrProductInfo['attributeValueIDs']))) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					}
				}
			}
		}

		/*
		 * Check the prices
		 */
		if ($blnWholeProductCouldStillMatch) {
			/*
			 * Ignore the price filter if the high filter price is not higher than 0 and not as least as high as the low filter price.
			 * This way it is possible to skip the price filter part by setting both filter parameters to 0.
			 */
			if ($arrCriteriaToFilterWith['price']['high'] >= $arrCriteriaToFilterWith['price']['low'] && $arrCriteriaToFilterWith['price']['high'] > 0) {
				if (!count($arrProductInfo['variants'])) {
					/*
					 * If the product doesn't have variants, the product's price has to be checked
					 */
					if ($arrProductInfo['price'] < $arrCriteriaToFilterWith['price']['low'] || $arrProductInfo['price'] > $arrCriteriaToFilterWith['price']['high']) {
						$blnWholeProductCouldStillMatch = false;
					}
				} else {
					/*
					 * If the product has variants, we have to use it's highest and lowest price to see,
					 * if it is possible to match or filter out the whole product or if we have to
					 * check each variant separately.
					 */
					if ($arrProductInfo['lowestPrice'] > $arrCriteriaToFilterWith['price']['low'] && $arrProductInfo['highestPrice'] < $arrCriteriaToFilterWith['price']['high']) {
						/*
						 * If the product's lowest price is higher than the low filter limit and the product's highest price is lower than the high filter limit,
						 * this means that all product variants must be within the price range and, regarding the price, the product matches as a whole.
						 */
					} else if ($arrProductInfo['highestPrice'] < $arrCriteriaToFilterWith['price']['low'] || $arrProductInfo['lowestPrice'] > $arrCriteriaToFilterWith['price']['high']) {
						/*
						 * If even the product's highest price is lower than the low filter limit or it's lowest
						 * price is higher than the high filter limit, this means that none of it's variants
						 * has a price within the range and the product has to be filtered out as a whole.
						 */
						$blnWholeProductCouldStillMatch = false;
						$blnVariantsCouldStillMatch = false;
					} else {
						/*
						 * If none of the above is true, this means that there's definitely a variant that has a price outside the range
						 * but there could be one ore more variants that have a price within.
						 */
						$blnWholeProductCouldStillMatch = false;
						$blnVariantsCouldStillMatch = true;
					}
				}
			}
		}

		if ($blnWholeProductCouldStillMatch) {
			/*
			 * If the product could still match as a whole and there is no filter criteria left
			 * to check for, the complete product actually matches the filter
			 */

			/*
			 * Count all variants as variant matches
			 */
			$numVariantMatches += count ($arrProductInfo['variants']);
			if ($blnStoreProductAndVariantMatchesInSession) {
                $session_lsShopCart['filter']['matchedProducts'][$arrProductInfo['id']] = 'complete';
                $session->set('lsShop', $session_lsShopCart);
			}
			return true;
		} else if ($blnVariantsCouldStillMatch) {
			/*
			 * If the variants of the product could still match while the product itself couldn't,
			 * we have to perform the filter checks for the product's variants
			 */

			$blnPartialMatchForProductConfirmed = false;
			$numMatchingVariants = 0;

			/*
			 * IMPORTANT: Since we have not memorized which attributes might have matched for the
			 * whole product and because a variant could possibly fail to match the filter because
			 * it doesn't have a requested attribute although the product itself has it and should
			 * pass it to the variants, we have to actually pass all of the product's attributes
			 * to the variants! We do this by merging the product's attributeValueIDs with
			 * the variant's attributeValueIDs.
			 */

			foreach ($arrProductInfo['variants'] as $arrVariantInfo) {
				$blnThisVariantCouldStillMatch = true;

				$arrVariantInfo['mergedProductAndVariantAttributeValueIDs'] = array_merge($arrProductInfo['attributeValueIDs'], $arrVariantInfo['attributeValueIDs']);

				/*
				 * Check the variant's attributes
				 */
				if ($blnThisVariantCouldStillMatch) {
					if (is_array($arrCriteriaToFilterWith['attributes'])) {
						foreach ($arrCriteriaToFilterWith['attributes'] as $attributeID => $attributeValueIDs) {
							/*
							 * The array returned by array_intersect() contains
							 * the requested attributeValueIDs which are also included
							 * in the variant's attributeValueIDs.
							 *
							 * In filterMode "or": If we get at least one attributeValueID
							 * that matches, the variant is a match for this attribute,
							 * otherwise it's not and we return false.
							 *
							 * In filterMode "and": If all the attributeValueIDs
							 * match, the variant is a match for this attribute,
							 * otherwise it's not and we return false.
							 */

							if (($session_lsShopCart['filter']['filterModeSettingsByAttributes'][$attributeID] ?? null) === 'and') {
								if (count(array_intersect($attributeValueIDs, $arrVariantInfo['mergedProductAndVariantAttributeValueIDs'])) !== count($attributeValueIDs)) {
									$blnThisVariantCouldStillMatch = false;
									break;
								}
							} else {
								if (!count(array_intersect($attributeValueIDs, $arrVariantInfo['mergedProductAndVariantAttributeValueIDs']))) {
									$blnThisVariantCouldStillMatch = false;
									break;
								}
							}
						}
					}
				}

				if ($blnThisVariantCouldStillMatch) {
					/*
					 * Ignore the price filter if the high filter price is not higher than 0 and not as least as high as the low filter price.
					 * This way it is possible to skip the price filter part by setting both filter parameters to 0.
					 */
					if ($arrCriteriaToFilterWith['price']['high'] >= $arrCriteriaToFilterWith['price']['low'] && $arrCriteriaToFilterWith['price']['high'] > 0) {
						if ($arrVariantInfo['price'] < $arrCriteriaToFilterWith['price']['low'] || $arrVariantInfo['price'] > $arrCriteriaToFilterWith['price']['high']) {
							$blnThisVariantCouldStillMatch = false;
						}
					}
				}

				/*
				 * If this variant could still match and there's nothing left to check,
				 * this means that the variant actually matches. In this case we definitely
				 * have a partial match for the product but we have to check the other
				 * variants as well because we have to store the information about
				 * which variants matched and which didn't.
				 */
				if ($blnThisVariantCouldStillMatch) {
					/*
					 * Count this variant as match
					 */
					$numVariantMatches++;

					if ($blnStoreProductAndVariantMatchesInSession) {
                        $session_lsShopCart['filter']['matchedVariants'][$arrVariantInfo['id']] = true;
                        $session->set('lsShop', $session_lsShopCart);
					}
					$blnPartialMatchForProductConfirmed = true;
					$numMatchingVariants++;
				} else {
					if ($blnStoreProductAndVariantMatchesInSession) {
                        $session_lsShopCart['filter']['matchedVariants'][$arrVariantInfo['id']] = false;
                        $session->set('lsShop', $session_lsShopCart);
					}
				}
			}

			/*
			 * If we don't have partial match for the product or, in other words, none of the product's
			 * variants matched, the whole product must be filtered out. If we do have a partial match
			 * we have to return true to prevent the product from being filtered out but we have to
			 * store the information that the match was only partial.
			 */
			if (!$blnPartialMatchForProductConfirmed) {
				if ($blnStoreProductAndVariantMatchesInSession) {
                    $session_lsShopCart['filter']['matchedProducts'][$arrProductInfo['id']] = 'none';
                    $session->set('lsShop', $session_lsShopCart);
				}
				return false;
			} else {
				/*
				 * If we have a partial match which means that the product itself didn't match but
				 * one or more variants did, we have to check if perhaps all of the product's variants
				 * matched because in this case we evaluate this as a complete match because the
				 * product itself can not be ordered and since all of it's variants match we can
				 * consider the product a full match even though the matching criteria is not completely
				 * part of the product's main data.
				 */
				if ($numMatchingVariants < count($arrProductInfo['variants'])) {
					if ($blnStoreProductAndVariantMatchesInSession) {
                        $session_lsShopCart['filter']['matchedProducts'][$arrProductInfo['id']] = 'partial';
                        $session->set('lsShop', $session_lsShopCart);
					}
				} else {
					if ($blnStoreProductAndVariantMatchesInSession) {
                        $session_lsShopCart['filter']['matchedProducts'][$arrProductInfo['id']] = 'complete';
                        $session->set('lsShop', $session_lsShopCart);
					}
				}
				return true;
			}

		} else {
			/*
			 * If neither the product nor it's variants could match the filter anymore
			 * we can only return false here and filter out the whole product
			 */
			if ($blnStoreProductAndVariantMatchesInSession) {
                $session_lsShopCart['filter']['matchedProducts'][$arrProductInfo['id']] = 'none';
                $session->set('lsShop', $session_lsShopCart);
			}
			return false;
		}
	}

	public static function resetMatchedProductsAndVariants() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

        $session_lsShopCart['filter']['matchedProducts'] = array();
        $session_lsShopCart['filter']['matchedVariants'] = array();

        $session->set('lsShop', $session_lsShopCart);
	}

	public static function adaptFilterCriteriaToCurrentFilterFormCriteria() {
		/*
		 * If the filter settings get altered, we have to reset the matchedProducts
		 * and matchedVariants because these cached filter results were related
		 * to the previous filter settings.
		 */
		ls_shop_filterHelper::resetMatchedProductsAndVariants();

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		/*
		 * Get the attributes that are actually relevant for the current filtering process
		 *
		 */
        $session_lsShopCart['filter']['criteriaToActuallyFilterWith'] = $session_lsShopCart['filter']['criteria'];

		/*
		 * Walk through each attribute in the filter
		 */
		foreach ($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['attributes'] as $attributeID => $arrAttributeValues) {
			/*
			 * Check for each attributeID if it is part of the criteria to use in the filter form
			 */
			if (!isset($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID])) {
				/*
				 * and if it's not, remove the entire attribute
				 */
				unset ($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]);
			} else {
				/*
				 * and if it is, we walk through each attributeValue and check if it exists in
				 * the criteria to use in the filter form.
				 */
				foreach ($arrAttributeValues as $k => $attributeValueID) {
					if (!in_array($attributeValueID, $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID])) {
						// and if it's not...
						if (count($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]) > 1) {
							/*
							 * we have to remove the attributeValue from the filter
							 */
							unset ($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID][$k]);
						} else {
							/*
							 * or we have to remove the entire attribute because this was it's only attributeValue in the filter
							 */
							unset ($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]);
						}
					}
				}
			}
		};

		/*
		 * Reset the producers that are no longer available in the filter form
		 */
		foreach ($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['producers'] as $k => $producer) {
			if (!in_array($producer, $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['producers'])) {
				unset ($session_lsShopCart['filter']['criteriaToActuallyFilterWith']['producers'][$k]);
			}
		};

		/*
		 * Reset the price range if it is no longer in the filter form
		 */
		if ($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['price']['low'] == $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['price']['high']) {
            $session_lsShopCart['filter']['criteriaToActuallyFilterWith']['price'] = array(
				'low' => null,
				'high' => null
			);
		}
        $session->set('lsShop', $session_lsShopCart);
	}

	public static function setCriteriaToUseInFilterForm($arrProductsComplete = array()) {
		if (!isset($GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'])) {
			$GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'] = true;
		}
		if (!is_array($arrProductsComplete)) {
			return;
		}

		ls_shop_filterHelper::resetCriteriaToUseInFilterForm();

		foreach ($arrProductsComplete as $arrProduct) {
			if (!$arrProduct['lowestPrice']) {
				ls_shop_filterHelper::addPriceToCriteriaUsedInFilterForm($arrProduct['price']);
			} else {
				ls_shop_filterHelper::addPriceToCriteriaUsedInFilterForm($arrProduct['lowestPrice']);
				ls_shop_filterHelper::addPriceToCriteriaUsedInFilterForm($arrProduct['highestPrice']);
			}

			ls_shop_filterHelper::addProducerToCriteriaUsedInFilterForm($arrProduct['lsShopProductProducer']);

			foreach ($arrProduct['attributeAndValueIDs'] as $intAttributeID => $arrValueIDs) {
				ls_shop_filterHelper::addAttributeValueToCriteriaUsedInFilterForm($intAttributeID, $arrValueIDs);
			}
			foreach ($arrProduct['variants'] as $arrVariant) {
				foreach ($arrVariant['attributeAndValueIDs'] as $intAttributeID => $arrValueIDs) {
					ls_shop_filterHelper::addAttributeValueToCriteriaUsedInFilterForm($intAttributeID, $arrValueIDs);
				}
			}
		}

		/*
		 * #######################################
		 * Remove filter criteria from the filter form if they don't make any sense, e.g. attributes, if there
		 * is only one possible value and producers if there is only one possible producer
		 */
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		foreach ($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $attributeID => $arrAttributeValueIDs) {
			if (count($arrAttributeValueIDs) < 2) {
				unset($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID]);
			}
		}

		if (count($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['producers']) < 2) {
            $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['producers'] = array();
		}
        $session->set('lsShop', $session_lsShopCart);
		/*
		 * #######################################
		 */

		ls_shop_filterHelper::adaptFilterCriteriaToCurrentFilterFormCriteria();
	}

	public static function filterReload() {
	    $str_targetUrl = \Environment::get('request');

	    /*
	     * Remove a possibly existing cajaxCall parameter
	     */
	    $str_targetUrl = ls_shop_generalHelper::removeGetParametersFromUrl($str_targetUrl, 'cajaxCall');

	    /*
	     * If a specific page is given in the url, replace it with page 1.
	     * This is necessary because the filtered product list could be shorter and the currently selected page
	     * might not exist anymore.
	     */
	    $str_targetUrl = preg_replace('/(page_(?:crossSeller|standard).*?=)(.*?[0-9]*?)([^0-9]|&|$)/', '${1}1$3', $str_targetUrl);

		\Controller::redirect($str_targetUrl);
	}

	public static function resetFilter() {
		ls_shop_filterHelper::createEmptyFilterSession();
		ls_shop_filterHelper::filterReload();
	}

	public static function handleFilterModeSettings() {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		if (!isset($session_lsShopCart['filter']['filterModeSettingsByAttributes'])) {
            $session_lsShopCart['filter']['filterModeSettingsByAttributes'] = array();
		}

		$arr_filterModeInput = \Input::post('filterModeForAttribute');

		if (is_array($arr_filterModeInput)) {
			foreach ($arr_filterModeInput as $var_attribute => $str_filterMode) {
                $session_lsShopCart['filter']['filterModeSettingsByAttributes'][$var_attribute] = $str_filterMode;
			}
		}
        $session->set('lsShop', $session_lsShopCart);
	}

	public static function setFilter($what = '', $varValue) {
		if (!$what) {
			return;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

		/*
		 * If the filter settings get altered, we have to reset the matchedProducts
		 * and matchedVariants because these cached filter results were related
		 * to the previous filter settings.
		 */
		ls_shop_filterHelper::resetMatchedProductsAndVariants();

		switch ($what) {
			case 'attributes':
				if (!$varValue['value']) {
					unset($session_lsShopCart['filter']['criteria']['attributes'][$varValue['attributeID']]);
				} else {
					$varValue['value'] = is_array($varValue['value']) ? $varValue['value'] : array($varValue['value']);

					/*
					 * Attribute values that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					if (isset($session_lsShopCart['filter']['criteria']['attributes'][$varValue['attributeID']]) && is_array($session_lsShopCart['filter']['criteria']['attributes'][$varValue['attributeID']])) {
						foreach ($session_lsShopCart['filter']['criteria']['attributes'][$varValue['attributeID']] as $attributeValueIDCurrentlyInFilter) {
							if (
								!in_array($attributeValueIDCurrentlyInFilter, $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'][$varValue['attributeID']])
								&&	!in_array($attributeValueIDCurrentlyInFilter, $varValue['value'])
							) {
								$varValue['value'][] = $attributeValueIDCurrentlyInFilter;
							}
						}
					}

					foreach($varValue['value'] as $k => $v) {
						if (!$v || $v == '--reset--' || $v == '--checkall--') {
							unset($varValue['value'][$k]);
						}
					}

					if (!count($varValue['value'])) {
						unset($session_lsShopCart['filter']['criteria']['attributes'][$varValue['attributeID']]);
						break;
					}

                    $session_lsShopCart['filter']['criteria']['attributes'][$varValue['attributeID']] = $varValue['value'];
				}
				break;

			case 'price':
                $session_lsShopCart['filter']['criteria']['price']['low'] = $varValue['low'];
                $session_lsShopCart['filter']['criteria']['price']['high'] = $varValue['high'];
				break;

			case 'producers':
				if (!$varValue) {
                    $session_lsShopCart['filter']['criteria']['producers'] = array();
				} else {
					$varValue = is_array($varValue) ? $varValue : array($varValue);

					/*
					 * Producers that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					foreach ($session_lsShopCart['filter']['criteria']['producers'] as $producerCurrentlyInFilter) {
						if (
							!in_array($producerCurrentlyInFilter, $session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['producers'])
							&&	!in_array($producerCurrentlyInFilter, $varValue)
						) {
							$varValue[] = $producerCurrentlyInFilter;
						}
					}

					foreach($varValue as $k => $v) {
						if (!$v || $v == '--reset--' || $v == '--checkall--') {
							unset($varValue[$k]);
						}
					}

                    $session_lsShopCart['filter']['criteria']['producers'] = $varValue;
				}
				break;
		}
        $session->set('lsShop', $session_lsShopCart);
	}

	public static function getMatchesInProductResultSet($arrProductsResultSet = array(), $arrCriteriaToFilterWith = null, $blnStoreProductAndVariantMatchesInSession = true) {
		if (!is_array($arrProductsResultSet)) {
			return null;
		}

		$arrFilterMatches = array(
			'numMatching' => 0,
			'numNotMatching' => 0,
			'arrMatchingProducts' => array(),
			'numVariantsMatching' => 0
		);
		foreach ($arrProductsResultSet as $rowProduct) {
			if (ls_shop_filterHelper::checkIfProductMatchesFilter($rowProduct, $arrCriteriaToFilterWith, $blnStoreProductAndVariantMatchesInSession, $arrFilterMatches['numVariantsMatching'])) {
				$arrFilterMatches['numMatching']++;
				$arrFilterMatches['arrMatchingProducts'][] = $rowProduct;
			} else {
				$arrFilterMatches['numNotMatching']++;
			}
		}

		return $arrFilterMatches;
	}

	/*
	 * In this function we determine how many matches a selected criteria would have.
	 */
	public static function getEstimatedMatchNumbers($arrProductsResultSet = array()) {
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShopCart =  $session->get('lsShop', []);

        $session_lsShopCart['filter']['matchEstimates']['attributeValues'] = array();
        $session_lsShopCart['filter']['matchEstimates']['producers'] = array();
		if (!isset($GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates']) || !$GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates']) {
            $session->set('lsShop', $session_lsShopCart);
			return;
		}

        $session_lsShopCart['filter']['noMatchEstimatesDetermined'] = false;
		if (
			isset($GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'])
			&&	$GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'] > 0
			&& count($arrProductsResultSet) > $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts']
		) {
            $session_lsShopCart['filter']['noMatchEstimatesDetermined'] = true;
            $session->set('lsShop', $session_lsShopCart);
			return;
		}

		$numFilterValuesToDetermineEstimatesFor = 0;
		foreach ($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $arrAttributeValues) {
			$numFilterValuesToDetermineEstimatesFor += count($arrAttributeValues);
		}
		$numFilterValuesToDetermineEstimatesFor += count($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['producers']);

		if (
			isset($GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'])
			&&	$GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'] > 0
			&& $numFilterValuesToDetermineEstimatesFor > $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues']
		) {
            $session_lsShopCart['filter']['noMatchEstimatesDetermined'] = true;
            $session->set('lsShop', $session_lsShopCart);
			return;
		}

		/*
		 * Getting the estimates for the attributes
		 */
		/*
		 * Walk through all attributes used in the filter form and create an array with filter criteria that does not
		 * include the current attribute
		 */
		foreach ($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $attributeID => $arrAttributeValues) {
			$tmpCriteriaToFilterWith = $session_lsShopCart['filter']['criteriaToActuallyFilterWith'];

			/*
			 * Remove the current attribute from the criteria array
			 */
			if (isset($tmpCriteriaToFilterWith['attributes'][$attributeID])) {
				unset($tmpCriteriaToFilterWith['attributes'][$attributeID]);
			}

			/*
			 * Walk through all the attribute values and create a temporary filter criteria array in which the current
			 * attribute value is added
			 */
			foreach ($arrAttributeValues as $attributeValueID) {
				$tmpCriteriaToFilterWithPlusCurrentValue = $tmpCriteriaToFilterWith;
				$tmpCriteriaToFilterWithPlusCurrentValue['attributes'][$attributeID] = array($attributeValueID);

				/*
				 * Filter the previously created result set using only the current attribute value
				 */
				$arrFilterMatches = ls_shop_filterHelper::getMatchesInProductResultSet($arrProductsResultSet, $tmpCriteriaToFilterWithPlusCurrentValue, false);

				/*
				 * Storing the number of matches
				 */
                $session_lsShopCart['filter']['matchEstimates']['attributeValues'][$attributeValueID] = array(
					'products' => $arrFilterMatches['numMatching'],
					'variants' => $arrFilterMatches['numVariantsMatching']
				);
			}
		}

		/*
		 * Getting the estimates for the producers
		 */
		$tmpCriteriaToFilterWith = $session_lsShopCart['filter']['criteriaToActuallyFilterWith'];

		/*
		 * Remove the producers from the criteria array
		 */
		$tmpCriteriaToFilterWith['producers'] = array();

		/*
		 * Walk through all the producers and create a temporary filter criteria array in which the current
		 * producer is added
		 */
		foreach ($session_lsShopCart['filter']['arrCriteriaToUseInFilterForm']['producers'] as $producerValue) {
			$tmpCriteriaToFilterWithPlusCurrentValue = $tmpCriteriaToFilterWith;
			$tmpCriteriaToFilterWithPlusCurrentValue['producers'] = array($producerValue);

			/*
			 * Filter the previously created result set using only the current producer value
			 */
			$arrFilterMatches = ls_shop_filterHelper::getMatchesInProductResultSet($arrProductsResultSet, $tmpCriteriaToFilterWithPlusCurrentValue, false);

			/*
			 * Storing the number of matches
			 */
            $session_lsShopCart['filter']['matchEstimates']['producers'][md5($producerValue)] = array(
				'products' => $arrFilterMatches['numMatching'],
				'variants' => $arrFilterMatches['numVariantsMatching']
			);
		}
        $session->set('lsShop', $session_lsShopCart);
	}
}