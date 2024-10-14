<?php

namespace Merconis\Core;

use Contao\StringUtil;
use Contao\System;

use function LeadingSystems\Helpers\createMultidimensionalArray;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_export
{
	public $arr_exportRecord = array();

	protected $obj_segmentizer = null;

	public $obj_template = null;
	protected $str_mode = 'feed'; // 'feed' or 'file'
	
	public function __construct($var_identificationToken, $str_identificationTokenFieldName = 'id', $str_mode = '') {
		$this->str_mode = $str_mode ? $str_mode : $this->str_mode;

		$this->getExportRecordForIdentificationToken($var_identificationToken, $str_identificationTokenFieldName);

		if (!count($this->arr_exportRecord)) {
			\System::log('MERCONIS: Trying to show an export but could not find an export record for the given identification token "'.$var_identificationToken.'" in identification token field "'.$str_identificationTokenFieldName.'".', 'MERCONIS MESSAGES', TL_MERCONIS_ERROR);

			/*
			 * Do not throw an exception in backend mode because in this case it would not be possible
			 * to select another/correct export id for an export CTE.
			 */
			if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
				return;
			}
			
			throw new \Exception('Trying to show an export but could not find an export record for the given identification token "'.$var_identificationToken.'" in identification token field "'.$str_identificationTokenFieldName.'".');
		}

		if ($this->arr_exportRecord['template']) {
			$this->obj_template = new \FrontendTemplate($this->arr_exportRecord['template']);
		}

		if ($this->arr_exportRecord['useSegmentedOutput'] && $this->arr_exportRecord['numberOfRecordsPerSegment']) {
			$this->obj_segmentizer = new \LeadingSystems\Helpers\ls_helpers_segmentizer('export_'.$this->arr_exportRecord['id'].'_'.$this->getSegmentationToken(), '', 0, $this->arr_exportRecord['finishSegmentedOutputWithExtraSegment'] ? true : false);
		}
	}



	public function __get($str_what) {
		switch ($str_what) {
			case 'obj_segmentizer':
				return $this->obj_segmentizer;
				break;

			case 'bln_useSegmentation':
				return is_object($this->obj_segmentizer);
				break;
		}
	}

	protected function getExportRecordForIdentificationToken($var_identificationToken, $str_identificationTokenFieldName = 'id') {
		if (!\Database::getInstance()->fieldExists($str_identificationTokenFieldName, 'tl_ls_shop_export')) {
			return;
		}

		$obj_dbresExport = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_export`
			WHERE		`".$str_identificationTokenFieldName."` = ?
		")
		->limit(1)
		->execute($var_identificationToken);

		if (!$obj_dbresExport->numRows) {
			return;
		}

		$this->arr_exportRecord = $obj_dbresExport->first()->row();
	}

	public function parseExport() {
		/* ->
		 * If the hooked function returns something other than null, its return value will only be passed through
		 * and the regular export routine will be skipped.
		 * Therefore, using the hook "exporter_parseExport" it is possible to create an entirely customized export routine.
		 */
		$str_output = null;

		if (isset($GLOBALS['MERCONIS_HOOKS']['exporter_parseExport']) && is_array($GLOBALS['MERCONIS_HOOKS']['exporter_parseExport'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['exporter_parseExport'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$str_output = $objMccb->{$mccb[1]}($this);
			}
		}

		if ($str_output !== null) {
			return $str_output;
		}
		/*
		 * <-
		 */

		if ($this->arr_exportRecord['dataSource'] !== 'dataTable' && $this->arr_exportRecord['simulateGroup']) {
			$var_tmp_standardGroup = $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'];
			$GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = $this->arr_exportRecord['simulateGroup'];

			unset($GLOBALS['merconis_globals']['checkoutData']);
			unset($GLOBALS['merconis_globals']['configurator']);
			unset($GLOBALS['merconis_globals']['customerCountry']);
			unset($GLOBALS['merconis_globals']['outputPriceType']);
			unset($GLOBALS['merconis_globals']['groupInfo']);
			unset($GLOBALS['merconis_globals']['getCurrentTax']);
		}

		$arr_data = $this->getExportData();

		if (is_object($this->obj_segmentizer)) {
			header('merconisExport-useSegmentation: yes');
			header('merconisExport-currentSegment: '.$this->obj_segmentizer->currentSegment);
			header('merconisExport-numSegmentsTotal: '.$this->obj_segmentizer->numSegmentsTotal);
			header('merconisExport-isLastSegment: '.($this->obj_segmentizer->isLastSegment ? 'yes' : 'no'));
		} else {
			header('merconisExport-useSegmentation: no');
		}

		if ($this->obj_template === null) {
			return $arr_data;
		}

		$this->obj_template->arr_data = $arr_data;
		$this->obj_template->str_mode = $this->str_mode;
		$this->obj_template->arr_flex_parameters = createMultidimensionalArray(\LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray(json_decode($this->arr_exportRecord['flex_parameters'])), 2, 1);

		if (is_object($this->obj_segmentizer)) {
			$this->obj_template->useSegmentation = true;
			$this->obj_template->currentSegment = $this->obj_segmentizer->currentSegment;
			$this->obj_template->numSegmentsTotal = $this->obj_segmentizer->numSegmentsTotal;
			$this->obj_template->isLastSegment = $this->obj_segmentizer->isLastSegment;
		} else {
			$this->obj_template->useSegmentation = false;
		}

		$str_output = $this->obj_template->parse();

		if ($this->arr_exportRecord['dataSource'] !== 'dataTable' && $this->arr_exportRecord['simulateGroup']) {
			$GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = $var_tmp_standardGroup;

			unset($GLOBALS['merconis_globals']['checkoutData']);
			unset($GLOBALS['merconis_globals']['configurator']);
			unset($GLOBALS['merconis_globals']['customerCountry']);
			unset($GLOBALS['merconis_globals']['outputPriceType']);
			unset($GLOBALS['merconis_globals']['groupInfo']);
			unset($GLOBALS['merconis_globals']['getCurrentTax']);
		}

		if ($this->arr_exportRecord['template'] === 'template_export_standardJson') {
			return $arr_data;
		}

		return $str_output;
	}

	public function getExportData() {
		switch($this->arr_exportRecord['dataSource']) {
			case 'dataOrders':
				$arr_data = $this->getOrderData();

				if (
						$this->arr_exportRecord['activateAutomaticChangeStatus01']
					||	$this->arr_exportRecord['activateAutomaticChangeStatus02']
					||	$this->arr_exportRecord['activateAutomaticChangeStatus03']
					||	$this->arr_exportRecord['activateAutomaticChangeStatus04']
					||	$this->arr_exportRecord['activateAutomaticChangeStatus05']
				) {
					$this->performAutomaticOrderStatusChange($arr_data);
				}
				break;

			case 'directSelection':
			case 'searchSelection':
				$arr_data = $this->getProductData();
				break;

			case 'dataTable':
			default:
				$arr_data = $this->getTableData();
				break;
		}

        /*
         * Use a customLogic file if one exists
         */
        $str_pathToCustomLogicFile = ls_getFilePathFromVariableSources($this->arr_exportRecord['customLogicFile']);

        if ($str_pathToCustomLogicFile && file_exists(TL_ROOT."/".$str_pathToCustomLogicFile) && is_file(TL_ROOT."/".$str_pathToCustomLogicFile)) {
            require_once(TL_ROOT."/".$str_pathToCustomLogicFile);
            $str_customLogicClassName = '\Merconis\Core\\'.preg_replace('/(^.*\/)([^\/\.]*)(\.php$)/', '\\2', $str_pathToCustomLogicFile);

            $obj_customLogic = new $str_customLogicClassName($this);

            $arr_data = $obj_customLogic->prepareData($arr_data, $this);
        }


		if (isset($GLOBALS['MERCONIS_HOOKS']['exporter_manipulateData']) && is_array($GLOBALS['MERCONIS_HOOKS']['exporter_manipulateData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['exporter_manipulateData'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$arr_data = $objMccb->{$mccb[1]}($arr_data, $this);
			}
		}

		return $arr_data;
	}

	public function getOrderData() {
		$arr_data = array();

		$arr_queryParameters = array();
		$str_additionalWhereClause = '';

		if ($this->arr_exportRecord['changedWithinMinutes'] != 9999999) {
			$obj_dateNow = new \DateTime();
			$obj_dateNow->sub(new \DateInterval('PT' . $this->arr_exportRecord['changedWithinMinutes'] . 'M'));
			$arr_queryParameters[] = $obj_dateNow->format('U');
		} else {
			$arr_queryParameters[] = 0;
		}

		/*
		 * The following could be used if the DateTime class was not available.
		 * It is uncertain, however, whether or not strtotime handles DST correctly.
		 *
		$arr_queryParameters[] = strtotime('- '.$this->arr_exportRecord['changedWithinMinutes'].' minutes')
		/* */

		for ($i = 1; $i <= 5; $i++) {
			if ($this->arr_exportRecord['activateFilterByStatus'.str_pad($i, 2, 0, STR_PAD_LEFT)]) {
				$arr_statusValuesToFilterBy = StringUtil::deserialize($this->arr_exportRecord['filterByStatus'.str_pad($i, 2, 0, STR_PAD_LEFT)], true);

				if (is_array($arr_statusValuesToFilterBy) && count($arr_statusValuesToFilterBy)) {
					$str_additionalWhereClauseFilterByStatus = "";
					foreach ($arr_statusValuesToFilterBy as $str_statusValueToFilterBy) {
						if ($str_additionalWhereClauseFilterByStatus) {
							$str_additionalWhereClauseFilterByStatus .= " OR ";
						}
						$str_additionalWhereClauseFilterByStatus .= "status".str_pad($i, 2, 0, STR_PAD_LEFT)." = ?";
						$arr_queryParameters[] = $str_statusValueToFilterBy;
					}
					$str_additionalWhereClause .= "AND (" . $str_additionalWhereClauseFilterByStatus . ")\r\n";
				}
			}
		}


		if ($this->arr_exportRecord['activateFilterByShippingMethod']) {
			$arr_shippingMethodsToFilterBy = StringUtil::deserialize($this->arr_exportRecord['filterByShippingMethod'], true);

			if (is_array($arr_shippingMethodsToFilterBy) && count($arr_shippingMethodsToFilterBy)) {
				$str_additionalWhereClauseFilterByShipping = "";
				foreach ($arr_shippingMethodsToFilterBy as $int_shippingMethodToFilterBy) {
					if ($str_additionalWhereClauseFilterByShipping) {
						$str_additionalWhereClauseFilterByShipping .= " OR ";
					}
					$str_additionalWhereClauseFilterByShipping .= "shippingMethod_id = ?";
					$arr_queryParameters[] = $int_shippingMethodToFilterBy;
				}
				$str_additionalWhereClause .= "AND (".$str_additionalWhereClauseFilterByShipping.")\r\n";
			}
		}

		if ($this->arr_exportRecord['activateFilterByPaymentMethod']) {
			$arr_paymentMethodsToFilterBy = StringUtil::deserialize($this->arr_exportRecord['filterByPaymentMethod'], true);

			if (is_array($arr_paymentMethodsToFilterBy) && count($arr_paymentMethodsToFilterBy)) {
				$str_additionalWhereClauseFilterByPayment = "";
				foreach ($arr_paymentMethodsToFilterBy as $int_paymentMethodToFilterBy) {
					if ($str_additionalWhereClauseFilterByPayment) {
						$str_additionalWhereClauseFilterByPayment .= " OR ";
					}
					$str_additionalWhereClauseFilterByPayment .= "paymentMethod_id = ?";
					$arr_queryParameters[] = $int_paymentMethodToFilterBy;
				}
				$str_additionalWhereClause .= "AND (".$str_additionalWhereClauseFilterByPayment.")\r\n";
			}
		}

		if (is_object($this->obj_segmentizer)) {
			/*
			 * Get the total number of records (required for segmentation)
			 */
			$obj_dbres_numTotal = \Database::getInstance()
				->prepare("
					SELECT		COUNT(*) as `numTotal`
					FROM		`tl_ls_shop_orders`
					WHERE		`tstamp` >= ?
					".($str_additionalWhereClause ? $str_additionalWhereClause : "")."
				")
				->execute(
					$arr_queryParameters
				);

			$this->obj_segmentizer->numSegmentsTotal = ceil($obj_dbres_numTotal->first()->numTotal / $this->arr_exportRecord['numberOfRecordsPerSegment']);
		}
		$obj_dbres_order = \Database::getInstance()
			->prepare("
				SELECT		*
				FROM		`tl_ls_shop_orders`
				WHERE		`tstamp` >= ?
				".($str_additionalWhereClause ? $str_additionalWhereClause : "")."
				ORDER BY	`orderDateUnixTimestamp` DESC
			");

		if (is_object($this->obj_segmentizer)) {
			$obj_dbres_order = $obj_dbres_order->limit($this->arr_exportRecord['numberOfRecordsPerSegment'], ($this->obj_segmentizer->currentSegment - 1) * $this->arr_exportRecord['numberOfRecordsPerSegment']);
		}

		$obj_dbres_order = $obj_dbres_order->execute($arr_queryParameters);

		while ($obj_dbres_order->next()) {
			$arr_orderRecord = $obj_dbres_order->row();
			$arr_orderItems = $this->getOrderItemsForOrderId($obj_dbres_order->id);
			$arr_customerData = $this->getCustomerDataForOrderId($obj_dbres_order->id);

			/*
			 * Remove stuff that does not make sense in an export
			 */
			unset($arr_orderRecord['personalDataReview']);
			unset($arr_orderRecord['personalDataReview_customerLanguage']);
			unset($arr_orderRecord['paymentDataReview']);
			unset($arr_orderRecord['paymentDataReview_customerLanguage']);
			unset($arr_orderRecord['shippingDataReview']);
			unset($arr_orderRecord['shippingDataReview_customerLanguage']);
			unset($arr_orderRecord['paymentMethod_infoAfterCheckout']);
			unset($arr_orderRecord['paymentMethod_infoAfterCheckout_customerLanguage']);
			unset($arr_orderRecord['paymentMethod_additionalInfo']);
			unset($arr_orderRecord['paymentMethod_additionalInfo_customerLanguage']);
			unset($arr_orderRecord['shippingMethod_infoAfterCheckout']);
			unset($arr_orderRecord['shippingMethod_infoAfterCheckout_customerLanguage']);
			unset($arr_orderRecord['shippingMethod_additionalInfo']);
			unset($arr_orderRecord['shippingMethod_additionalInfo_customerLanguage']);

			/*
			 * Translate stuff that needs to be translated
			 */
			$arr_orderRecord['totalValueOfGoodsTaxedWith'] = StringUtil::deserialize($arr_orderRecord['totalValueOfGoodsTaxedWith']);
			$arr_orderRecord['paymentMethod_amountTaxedWith'] = StringUtil::deserialize($arr_orderRecord['paymentMethod_amountTaxedWith']);
			$arr_orderRecord['shippingMethod_amountTaxedWith'] = StringUtil::deserialize($arr_orderRecord['shippingMethod_amountTaxedWith']);
			$arr_orderRecord['totalTaxedWith'] = StringUtil::deserialize($arr_orderRecord['totalTaxedWith']);
			$arr_orderRecord['tax'] = StringUtil::deserialize($arr_orderRecord['tax']);
			$arr_orderRecord['miscData'] = StringUtil::deserialize($arr_orderRecord['miscData']);

			foreach ($arr_orderItems as $int_orderItemKey => $arr_orderItem) {
				$arr_orderItems[$int_orderItemKey]['configurator_merchantRepresentation'] = trim($arr_orderItems[$int_orderItemKey]['configurator_merchantRepresentation']);
				$arr_orderItems[$int_orderItemKey]['configurator_cartRepresentation'] = trim($arr_orderItems[$int_orderItemKey]['configurator_cartRepresentation']);
				$arr_orderItems[$int_orderItemKey]['extendedInfo'] = StringUtil::deserialize($arr_orderItems[$int_orderItemKey]['extendedInfo']);
			}



			$arr_data[] = array(
				'arr_orderRecord' => $arr_orderRecord,
				'arr_orderItems' => $arr_orderItems,
				'arr_customerData' => $arr_customerData
			);
		}

		return $arr_data;
	}

	protected function getOrderItemsForOrderId($int_orderId) {
		$arr_orderItems = array();

		$obj_dbres_orderItems = \Database::getInstance()
			->prepare("
					SELECT		*
					FROM		`tl_ls_shop_orders_items`
					WHERE		`pid` = ?
					ORDER BY	`itemPosition` ASC
				")
			->execute($int_orderId);

		while ($obj_dbres_orderItems->next()) {
			$arr_orderItems[] = $obj_dbres_orderItems->row();
		}

		return $arr_orderItems;
	}

	protected function getCustomerDataForOrderId($int_orderId) {
		$arr_customerData = array();

		$obj_dbres_customerData = \Database::getInstance()
			->prepare("
				SELECT		*
				FROM		`tl_ls_shop_orders_customer_data`
				WHERE		`pid` = ?
				ORDER BY	`id` ASC
			")
			->execute($int_orderId);

		while ($obj_dbres_customerData->next()) {
			if (!key_exists($obj_dbres_customerData->dataType, $arr_customerData)) {
				$arr_customerData[$obj_dbres_customerData->dataType] = array();
			}

			$arr_customerData[$obj_dbres_customerData->dataType][$obj_dbres_customerData->fieldName] = $obj_dbres_customerData->fieldValue;
		}

		return $arr_customerData;
	}

	public function getTableData() {
		/* ->
		 * If the hooked function returns something other than null, its return value will only be passed through
		 * and the regular getTableData method will be skipped.
		 */
		$arr_data = null;

		if (isset($GLOBALS['MERCONIS_HOOKS']['exporter_getTableData']) && is_array($GLOBALS['MERCONIS_HOOKS']['exporter_getTableData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['exporter_getTableData'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$arr_data = $objMccb->{$mccb[1]}($this);
			}
		}

		if ($arr_data !== null) {
			return $arr_data;
		}
		/*
		 * <-
		 */

		$arr_data = array();

		if ($this->arr_exportRecord['tableName']) {
			if (is_object($this->obj_segmentizer)) {
				/*
				 * Get the total number of records (required for segmentation)
				 */
				$obj_dbres_numTotal = \Database::getInstance()
					->prepare("
					SELECT		COUNT(*) as `numTotal`
					FROM		`" . $this->arr_exportRecord['tableName'] . "`
				")
					->execute();

				$this->obj_segmentizer->numSegmentsTotal = ceil($obj_dbres_numTotal->first()->numTotal / $this->arr_exportRecord['numberOfRecordsPerSegment']);
			}

			$obj_dbres_data = \Database::getInstance()
			->prepare("
				SELECT		*
				FROM		`" . $this->arr_exportRecord['tableName'] . "`
		  	");

			if (is_object($this->obj_segmentizer)) {
				$obj_dbres_data = $obj_dbres_data->limit($this->arr_exportRecord['numberOfRecordsPerSegment'], ($this->obj_segmentizer->currentSegment - 1) * $this->arr_exportRecord['numberOfRecordsPerSegment']);
			}

			$obj_dbres_data = $obj_dbres_data->execute();

			if ($obj_dbres_data->numRows) {
				$arr_data = $obj_dbres_data->fetchAllAssoc();
			}
		}

		return $arr_data;
	}

	public function getProductData() {
		/* ->
		 * If the hooked function returns something other than null, its return value will only be passed through
		 * and the regular getTableData method will be skipped.
		 */
		$arr_data = null;

		if (isset($GLOBALS['MERCONIS_HOOKS']['exporter_getProductData']) && is_array($GLOBALS['MERCONIS_HOOKS']['exporter_getProductData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['exporter_getProductData'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$arr_data = $objMccb->{$mccb[1]}($this);
			}
		}

		if ($arr_data !== null) {
			return $arr_data;
		}
		/*
		 * <-
		 */

		$arr_data = array();
		$arr_searchCriteria = array();

		$obj_productSearch = new ls_shop_productSearcher();
		
		switch ($this->arr_exportRecord['dataSource']) {
			case 'directSelection':
				$arr_products = $this->ls_getDirectSelection();
				$arr_searchCriteria = array('id' => $arr_products);
				$obj_productSearch->fixedSorting = $arr_products;
				break;
				
			case 'searchSelection':
				// search criteria available
				$arr_searchCriteria = $this->ls_getSearchSelection();
				break;
		}

		$obj_productSearch->setSearchCriteria($arr_searchCriteria);

		if (is_object($this->obj_segmentizer)) {
			$obj_productSearch->numPerPage = $this->arr_exportRecord['numberOfRecordsPerSegment'];
			$obj_productSearch->currentPage = $this->obj_segmentizer->currentSegment;
		}

		if (isset($GLOBALS['MERCONIS_HOOKS']['exporter_manipulateProductSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['exporter_manipulateProductSearch'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['exporter_manipulateProductSearch'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$objMccb->{$mccb[1]}($obj_productSearch, $arr_searchCriteria);
			}
		}

		$obj_productSearch->search();

		if (is_object($this->obj_segmentizer)) {
			$this->obj_segmentizer->numSegmentsTotal = $obj_productSearch->numPagesTotal;
			$arr_productIds = $obj_productSearch->productResultsCurrentPage;

			/*
			 *  ->
			 * Force an empty output if we currently have to deliver the segmentizer's extra segment
			 */
			if ($this->obj_segmentizer->currentSegment > $obj_productSearch->numPagesTotal) {
				$arr_productIds = array();
			}
			/*
			 * <-
			 */
		} else {
			$arr_productIds = $obj_productSearch->productResultsComplete;
		}

		if ($this->arr_exportRecord['createProductObjects']) {
			foreach ($arr_productIds as $int_productId) {
				$arr_data[$int_productId] = ls_shop_generalHelper::getObjProduct($int_productId, __METHOD__);
			}
		} else {
			$arr_data = array(
				'arr_products' => array(),
				'arr_variants' => array()
			);

			$obj_dbres_products = \Database::getInstance()
			->prepare("
				SELECT		*
				FROM		`tl_ls_shop_product`
				WHERE		`id` IN (".implode(',', $arr_productIds).")
			")
			->execute();

			if ($obj_dbres_products->numRows) {
				while ($obj_dbres_products->next()) {
					$arr_data['arr_products'][$obj_dbres_products->id] = $obj_dbres_products->row();
					$arr_data['arr_products'][$obj_dbres_products->id]['numVariants'] = 0;
				}
			}

			$obj_dbres_variants = \Database::getInstance()
			->prepare("
				SELECT		*
				FROM		`tl_ls_shop_variant`
				WHERE		`pid` IN (".implode(',', $arr_productIds).")
			")
			->execute();

			if ($obj_dbres_variants->numRows) {
				while ($obj_dbres_variants->next()) {
					$arr_data['arr_products'][$obj_dbres_variants->pid]['numVariants']++;
					$arr_data['arr_variants'][$obj_dbres_variants->pid][$obj_dbres_variants->id] = $obj_dbres_variants->row();
				}
			}

			$arr_data = $this->combineProductAndVariantData($arr_data);
		}

		return $arr_data;
	}

	public function ls_getSearchSelection() {
		/** @var \PageModel $objPage */
		global $objPage;
		/*
		 * Erstellung des Suchkriterien-Arrays für productSearcher
		 */
		$arr_searchCriteria = array('published' => '1');
		
		if ($this->arr_exportRecord['activateSearchSelectionNewProduct']) {
			$arr_searchCriteria['lsShopProductIsNew'] = $this->arr_exportRecord['searchSelectionNewProduct'] == 'new' ? '1' : '';
		}
		
		if ($this->arr_exportRecord['activateSearchSelectionSpecialPrice']) {
			$arr_searchCriteria['lsShopProductIsOnSale'] = $this->arr_exportRecord['searchSelectionSpecialPrice'] == 'specialPrice' ? '1' : '';
		}

		if ($this->arr_exportRecord['activateSearchSelectionCategory']) {
			$pageIDs = StringUtil::deserialize($this->arr_exportRecord['searchSelectionCategory']);
			if (!is_array($pageIDs)) {
				$pageIDs = array();
			}
			if (!count($pageIDs)) {
				$pageIDs = array(ls_shop_languageHelper::getMainlanguagePageIDForPageID($objPage->id));
			}
			$arr_searchCriteria['pages'] = $pageIDs;
		}
		
		if ($this->arr_exportRecord['activateSearchSelectionProducer']) {
			$arr_searchCriteria['lsShopProductProducer'] = $this->arr_exportRecord['searchSelectionProducer'];
		}
		
		if ($this->arr_exportRecord['activateSearchSelectionProductName']) {
			$arr_searchCriteria['title'] = $this->arr_exportRecord['searchSelectionProductName'];
		}

		if ($this->arr_exportRecord['activateSearchSelectionArticleNr']) {
			$arr_searchCriteria['lsShopProductCode'] = $this->arr_exportRecord['searchSelectionArticleNr'];
		}
		
		if ($this->arr_exportRecord['activateSearchSelectionTags']) {
			$arr_searchCriteria['keywords'] = $this->arr_exportRecord['searchSelectionTags'];
		}		
		/*
		 * Ende Erstellung des Suchkriterien-Arrays für productSearcher
		 */
		
		return $arr_searchCriteria;
	}

	public function ls_getDirectSelection() {
		$arr_products = StringUtil::deserialize($this->arr_exportRecord['productDirectSelection']);
		if (count($arr_products) == 1 && !$arr_products[0]) {
			$arr_products = array();
		}
		return $arr_products;
	}

	protected function combineProductAndVariantData($arr_productAndVariantData) {
		/*
		 * Determine all field names required for a combined output of product and variant data
		 * and also create simplified field names because the names of the actual database table rows
		 * are much too inconvenient.
		 */
		$arr_productFieldNames = array_keys(array_shift(array_values($arr_productAndVariantData['arr_products'])));
        if (!empty($arr_productAndVariantData['arr_variants'])) {
            $arr_variantFieldNames = array_keys(reset(array_shift(array_values($arr_productAndVariantData['arr_variants']))));
        } else {
            $arr_variantFieldNames = [];
        }
		$arr_fieldNamesInProductsAndVariants = array_intersect($arr_productFieldNames, $arr_variantFieldNames);
		$arr_fieldNamesOnlyInProducts = array_diff($arr_productFieldNames, $arr_variantFieldNames);
		$arr_fieldNamesOnlyInVariants = array_diff($arr_variantFieldNames, $arr_productFieldNames);
		$arr_allFieldNames = array('id', 'pid', 'title'); //  make sure id and title are the first two fields
		$arr_allFieldNamesSimplified = array('id', 'pid', 'title'); //  make sure id and title are the first two fields

		foreach ($arr_fieldNamesOnlyInProducts as $str_fieldName) {
			if (!in_array($str_fieldName, $arr_allFieldNames)) {
				$arr_allFieldNames[] = $str_fieldName;
				$arr_allFieldNamesSimplified[] = 'p_'.lcfirst(str_replace('lsShopProduct', '', $str_fieldName));
			}
		}

		foreach ($arr_fieldNamesOnlyInVariants as $str_fieldName) {
			if (!in_array($str_fieldName, $arr_allFieldNames)) {
				$arr_allFieldNames[] = $str_fieldName;
				$arr_allFieldNamesSimplified[] = 'v_'.lcfirst(str_replace('lsShopProductVariant', '', str_replace('lsShopVariant', '', $str_fieldName)));
			}
		}

		foreach ($arr_fieldNamesInProductsAndVariants as $str_fieldName) {
			if (!in_array($str_fieldName, $arr_allFieldNames)) {
				$arr_allFieldNames[] = $str_fieldName;
				$arr_allFieldNamesSimplified[] = lcfirst($str_fieldName);
			}
		}

		if (count($arr_allFieldNames) !== count(array_unique($arr_allFieldNames))) {
			throw new \Exception('$arr_allFieldNames contains ambiguous values/field names');
		}

		if (count($arr_allFieldNamesSimplified) !== count(array_unique($arr_allFieldNamesSimplified))) {
			throw new \Exception('$arr_allFieldNamesSimplified contains ambiguous values/field names');
		}

		$arr_allFieldNamesSimplified = array_flip($arr_allFieldNamesSimplified);

		$arr_combinedData = array();
		foreach ($arr_productAndVariantData['arr_products'] as $arr_productData) {
			$arr_row = array();

			foreach (array_keys($arr_allFieldNamesSimplified) as $str_fieldNameSimplified) {
				$arr_row[$str_fieldNameSimplified] = $arr_productData[$arr_allFieldNames[$arr_allFieldNamesSimplified[$str_fieldNameSimplified]]];
			}

			$arr_combinedData[] = $arr_row;

			if ($arr_productData['numVariants'] > 0) {
				foreach ($arr_productAndVariantData['arr_variants'][$arr_productData['id']] as $arr_variantData) {
					$arr_row = array();

					foreach (array_keys($arr_allFieldNamesSimplified) as $str_fieldNameSimplified) {
						$arr_row[$str_fieldNameSimplified] = $arr_variantData[$arr_allFieldNames[$arr_allFieldNamesSimplified[$str_fieldNameSimplified]]];
					}

					$arr_combinedData[] = $arr_row;
				}
			}
		}

		return $arr_combinedData;
	}

	protected function getSegmentationToken()
	{
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$obj_user = \System::importStatic('BackendUser');
		} else {
			$obj_user = \System::importStatic('FrontendUser');
		}

		/*
		 * If a segmentation token is given explicitly as a get parameter, we use it
		 */
		if (\Input::get('stok')) {
			return \Input::get('stok');
		}

		/*
		 * If the user is logged in to the front or back end, we us the frontend or backend abbreviation "FE" or "BE"
		 * and the user's id as the segmentation token
		 */
		if ($obj_user->id !== null) {
			return System::getContainer()->get('merconis.routing.scope')->getTLMode() . '_' . $obj_user->id;
		}

		/*
		 * Since multiple users could share the same ip or one user could swap ips,
		 * the ip is obviously not the best segmentation token but we use it as a fallback.
		 */
		return sha1(\Environment::get('ip'));
	}

	protected function performAutomaticOrderStatusChange($arr_data) {
		if (!is_array($arr_data) || !count($arr_data)) {
			return;
		}

		$arr_orderIds = array();
		foreach ($arr_data as $arr_order) {
			$arr_orderIds[] = $arr_order['arr_orderRecord']['id'];
		}

		$arr_queryParameters = array();
		$str_sqlSetStatement = '';

		for ($i = 1; $i <= 5; $i++) {
			if ($this->arr_exportRecord['activateAutomaticChangeStatus'.str_pad($i, 2, 0, STR_PAD_LEFT)]) {
				if ($str_sqlSetStatement) {
					$str_sqlSetStatement .= ", ";
				}
				$str_sqlSetStatement .= "status".str_pad($i, 2, 0, STR_PAD_LEFT)." = ?";
				$arr_queryParameters[] = $this->arr_exportRecord['automaticChangeStatus'.str_pad($i, 2, 0, STR_PAD_LEFT)];
			}
		}

		$obj_dbqueryUpdateOrderStatus = \Database::getInstance()->prepare("
			UPDATE		`tl_ls_shop_orders`
			SET			".$str_sqlSetStatement."
			WHERE		`id` IN (".implode(',', $arr_orderIds).")
		")
		->execute(
			$arr_queryParameters
		);

		if ($this->arr_exportRecord['sendOrderMailsOnStatusChange']) {
			foreach ($arr_orderIds as $int_orderId) {
				$obj_orderMessages = new ls_shop_messages(
                    'onStatusChangeImmediately',
                    'sendWhen',
                    $int_orderId,
                    null,
                );
				$obj_orderMessages->sendMessages();
			}
		}
	}
}