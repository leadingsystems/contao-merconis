<?php

namespace Merconis\Core;

use Contao\StringUtil;
use LeadingSystems\Helpers\FlexWidget;

/*
 * FIXME: The filter seems to have a few bugs.
 * - The "and" mode doesn't do what it should do
 * - The filter fields and the options inside these fields don't always match the properties of the products in the
 * product list.
 */
class ls_shop_filterController
{
	/**
	 * Current object instance (Singleton)
	 */
	protected static $objInstance;

	/**
	 * Prevent direct instantiation (Singleton)
	 */
	protected function __construct()
	{
		if (!isset($_SESSION['lsShop']['filter'])) {
			ls_shop_filterHelper::createEmptyFilterSession();
		}
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 */
	private function __clone()
	{
	}

	/**
	 * Return the current object instance (Singleton)
	 */
	public static function getInstance()
	{
		if (!is_object(self::$objInstance)) {
			self::$objInstance = new self();
		}
		return self::$objInstance;
	}

	/*
	 * This function gets called by the outputFrontendTemplate hook and actually generates and displays the filter form
	 * and finishes the job, the filter form frontend module started but could not complete itself. See comments in
	 * ModuleFilterForm.php for more detailed information.
	 *
	 * Note: This function works as if it was possible to use more than one filter form in the shop and
	 * as far as this function is concerend, it probably would be. Actually it isn't possible to use more than
	 * one filter form or more than one product list which uses the filter because both would produce unpredictable
	 * results and therefore is considered a misconfiguration.
	 */
	public function generateAndInsertFilterForms($strContent, $strTemplate)
	{
		if (!isset($GLOBALS['merconis_globals']['ls_shop_activateFilter']) || !$GLOBALS['merconis_globals']['ls_shop_activateFilter']) {
			return $strContent;
		}
		/*
		 * Look for filterFormPlaceholder wildcards and get the fronted module ids contained in the wildcard strings
		 */
		preg_match_all('/##filterFormPlaceholder::(.*)##/siU', $strContent, $arrMatches);
		$arrFilterFormFrontendModuleIDs = $arrMatches[1];

		/*
		 * Walk through each fronted module id, get the frontend module records from the database
		 * and call the function which generates the actual filter form html code. After that
		 * we replace the wildcard with this fronted module id with the generated filter form html code.
		 */
		foreach ($arrFilterFormFrontendModuleIDs as $filterFormFrontendModuleID) {
			$objFEModule = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_module`
				WHERE		`id` = ?
			")
				->execute($filterFormFrontendModuleID);

			if ($objFEModule->numRows != 1) {
				continue;
			}

			$objFEModule->first();

			$strFilterFormHTML = $this->generateFilterFormHTML($objFEModule);

			$strContent = preg_replace('/##filterFormPlaceholder::' . $filterFormFrontendModuleID . '##/', $strFilterFormHTML, $strContent);
		}

		return $strContent;
	}

	/*
	 * This function generates the filter form html code and does what ModuleFilterForm could not do itself.
	 * In this function we have the code that would normally be written in ModuleFilterForm if we didn't have
	 * the problem of the data that's not available there but is available here.
	 */
	public function generateFilterFormHTML($objFEModule = null)
	{
		if (!is_object($objFEModule)) {
			return '';
		}

		/*
		 * Create the template given in the frontend module record
		 */
		$obj_template = new \FrontendTemplate($objFEModule->ls_shop_filterForm_template);
		$obj_template->request = \Environment::get('request');
		$obj_template->arr_filterSummaryData = \Merconis\Core\ls_shop_filterHelper::getFilterSummary();
		$obj_template->str_filterSummaryHtml = trim(\Merconis\Core\ls_shop_filterHelper::getFilterSummaryHtml($objFEModule));

		$arrHeadline = StringUtil::deserialize($objFEModule->headline);
		$obj_template->headline = is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$obj_template->hl = is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';

		if (
			isset($GLOBALS['merconis_globals']['ls_shop_hideFilterFormInProductDetails'])
			&& $GLOBALS['merconis_globals']['ls_shop_hideFilterFormInProductDetails']
			&& \Input::get('product')
		) {
			/*
			 * If we are in a product details view (as indicated by the existing get parameter "product")
			 * and the filter form should be hidden, we set the template value "blnNothingToFilter" to true
			 */
			$obj_template->blnNothingToFilter = true;
		} else {
			$obj_template->blnNothingToFilter = !isset($GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet']);
		}

		/*
		 * Create the filter form widgets
		 */
		$arrFilterWidgetReturn = $this->createFilterWidgets($obj_template);

		$obj_template = $arrFilterWidgetReturn['objTemplate'];

		return $obj_template->parse();
	}

	protected function createFilterWidgets($obj_template = null)
	{
        global $objPage;
        $str_currentLanguage = ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();

		/*
		 * All widgets that base on a filter field entry will be
		 * stored in the array $arrObjWidgets_filterFields
		 */
		$arrObjWidgets_filterFields = array();

		/*
		 * Get all the information about all filter fields
		 */
		$arrFilterFieldInfos = ls_shop_filterHelper::getFilterFieldInfos();

		/*
		 * Walk through all filter fields and create the widgets
		 */
		foreach ($arrFilterFieldInfos as $filterFieldID => $arrFilterFieldInfo) {
			/*
			 * Depending on the data source the widgets have to be created in a different way
			 */
			switch ($arrFilterFieldInfo['dataSource']) {
				case 'producer':
					/*
					 * If based on the current product list there are no producers to be used as criteria in the filter form,
					 * we don't create a widget
					 */
					if (
						!is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])
						|| !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])
					) {
						continue 2;
					}

					/*
					 * Create the options array for this filter field ->
					 */
					$arrOptions = array();

					$fieldValuesAlreadyHandled = array();

					foreach ($arrFilterFieldInfo['fieldValues'] as $arrFieldValue) {

						$fieldValuesAlreadyHandled[] = $arrFieldValue['filterValue'];
						/*
						 * In the widget we only insert the values that should be used as filter criteria based on the current product list
						 */
						if (!in_array($arrFieldValue['filterValue'], $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])) {
							continue;
						}

						$md5Value = md5($arrFieldValue['filterValue']);
						$arrOptions[] = array(
							'value' => $arrFieldValue['filterValue'],
							'label' => $arrFieldValue['filterValue'],
							'class' => (isset($arrFieldValue['classForFilterFormField']) && $arrFieldValue['classForFilterFormField'] ? ' ' . $arrFieldValue['classForFilterFormField'] : ''),
							'important' => (isset($arrFieldValue['importantFieldValue']) && $arrFieldValue['importantFieldValue'] ? true : false),
							'matchEstimates' => isset($_SESSION['lsShop']['filter']['matchEstimates']['producers'][$md5Value]) ? $_SESSION['lsShop']['filter']['matchEstimates']['producers'][$md5Value] : null
						);
					}

					foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'] as $value) {
						/*
						 * values that exist as filter field values in the filter field values table
						 * are skipped because if they also exist in the criteria to use in the filter form
						 * they already are in the $arrOptions array.
						 */
						if (in_array($value, $fieldValuesAlreadyHandled)) {
							continue;
						}
						$md5Value = md5($value);
						$arrOptions[] = array(
							'value' => $value,
							'label' => $value,
							'class' => '',
							'important' => false,
							'matchEstimates' => isset($_SESSION['lsShop']['filter']['matchEstimates']['producers'][$md5Value]) ? $_SESSION['lsShop']['filter']['matchEstimates']['producers'][$md5Value] : null
						);
					}
					/*
					 * <- Create the options array for this filter field
					 */


					$arrObjWidgets_filterFields[$filterFieldID] = new FlexWidget(
						array(
							'str_uniqueName' => 'filterField_' . $filterFieldID,
							'str_template' => $arrFilterFieldInfo['templateToUse'] ? $arrFilterFieldInfo['templateToUse'] : 'template_formFilterField_standard',
							'str_label' => $arrFilterFieldInfo['title'],
							'str_allowedRequestMethod' => 'post',
							'arr_moreData' => array(
							    'filterSectionId' => $arrFilterFieldInfo['dataSource'],
								'arrOptions' => $arrOptions,
								'sourceAttribute' => null,
								'filterMode' => $arrFilterFieldInfo['filterMode'],
								'makeFilterModeUserAdjustable' => false,
								'arrFieldInfo' => $arrFilterFieldInfo,
								'alias' => isset($arrFilterFieldInfo['alias']) ? $arrFilterFieldInfo['alias'] : '',
								'classForFilterFormField' => isset($arrFilterFieldInfo['classForFilterFormField']) ? $arrFilterFieldInfo['classForFilterFormField'] : '',
								'numItemsInReducedMode' => isset($arrFilterFieldInfo['numItemsInReducedMode']) && $arrFilterFieldInfo['numItemsInReducedMode'] ? $arrFilterFieldInfo['numItemsInReducedMode'] : 0,
								'filterFormFieldType' => isset($arrFilterFieldInfo['filterFormFieldType']) && $arrFilterFieldInfo['filterFormFieldType'] ? $arrFilterFieldInfo['filterFormFieldType'] : 'checkbox'
							),
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['producers']) ? $_SESSION['lsShop']['filter']['criteria']['producers'] : ''
						)
					);
					break;

				case 'price':
					/*
					 * Skip the price field if there are no different prices (both are 0) in the result that should be filtered
					 */
					if (!$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['low'] && !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['high']) {
						continue 2;
					}

					$objFlexWidget_priceLow = new FlexWidget(
						array(
							'str_uniqueName' => 'priceLow',
							'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText098'],
							'str_allowedRequestMethod' => 'post',
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['price']['low']) ? $_SESSION['lsShop']['filter']['criteria']['price']['low'] : 0
						)
					);

					$objFlexWidget_priceHigh = new FlexWidget(
						array(
							'str_uniqueName' => 'priceHigh',
							'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText099'],
							'str_allowedRequestMethod' => 'post',
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['price']['high']) ? $_SESSION['lsShop']['filter']['criteria']['price']['high'] : 0
						)
					);

					$arrObjWidgets_filterFields[$filterFieldID] = array(
						'objWidget_priceLow' => $objFlexWidget_priceLow,
						'objWidget_priceHigh' => $objFlexWidget_priceHigh,
						'arrFilterFieldInfo' => $arrFilterFieldInfo,
                        'str_template' => $arrFilterFieldInfo['templateToUseForPriceField'] ? $arrFilterFieldInfo['templateToUseForPriceField'] : 'template_formPriceFilterField_standard',
                        'arr_moreData' => array(
                            'filterSectionId' => $arrFilterFieldInfo['dataSource'],
                            'minValue' => $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['low'],
                            'maxValue' => $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['high']
                        )
					);
					break;

                case 'flexContentLI':
                    /*
                     * If based on the current product list there are no flexContentsLI to be used as criteria in the filter form
                     * or no values for the current flexContentLI, we don't create a widget
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']])
                    ) {
                        continue 2;
                    }

                    /*
                     * Create the options array for this filter field ->
                     */
                    $arrOptions = array();

                    foreach ($arrFilterFieldInfo['fieldValues'] as $arrFieldValue) {

                        /*
                         * In the widget we only insert the values that should be used as filter criteria based on the current product list
                         */
                        if (!in_array($arrFieldValue['filterValue'], $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']])) {
                            continue;
                        }

                        $arrOptions[] = array(
                            'value' => $arrFieldValue['filterValue'],
                            'label' => $arrFieldValue['filterValue'],
                            'class' => (isset($arrFieldValue['classForFilterFormField']) && $arrFieldValue['classForFilterFormField'] ? ' ' . $arrFieldValue['classForFilterFormField'] : ''),
                            'important' => (isset($arrFieldValue['importantFieldValue']) && $arrFieldValue['importantFieldValue'] ? true : false),
                            'matchEstimates' => isset($_SESSION['lsShop']['filter']['matchEstimates']['flexContentLIValues'][$arrFieldValue['filterValue']]) ? $_SESSION['lsShop']['filter']['matchEstimates']['flexContentLIValues'][$arrFieldValue['filterValue']] : null
                        );
                    }
                    /*
                     * <- Create the options array for this filter field
                     */

                    $arrObjWidgets_filterFields[$filterFieldID] = new FlexWidget(
                        array(
                            'str_uniqueName' => 'filterField_' . $filterFieldID,
                            'str_template' => $arrFilterFieldInfo['templateToUseForFlexContentLIField'] ? $arrFilterFieldInfo['templateToUseForFlexContentLIField'] : 'template_formFlexContentLIFilterField_standard',
                            'str_label' => $arrFilterFieldInfo['title'],
                            'str_allowedRequestMethod' => 'post',
                            'arr_moreData' => array(
                                'filterSectionId' => $arrFilterFieldInfo['dataSource'] . '-' . $arrFilterFieldInfo['flexContentLIKey'],
                                'arrOptions' => $arrOptions,
                                'flexContentLIKey' => $arrFilterFieldInfo['flexContentLIKey'],
                                'filterMode' => isset($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']]) ? $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']] : $arrFilterFieldInfo['filterMode'],
                                'makeFilterModeUserAdjustable' => $arrFilterFieldInfo['makeFilterModeUserAdjustable'],
                                'arrFieldInfo' => $arrFilterFieldInfo,
                                'alias' => isset($arrFilterFieldInfo['alias']) ? $arrFilterFieldInfo['alias'] : '',
                                'classForFilterFormField' => isset($arrFilterFieldInfo['classForFilterFormField']) ? $arrFilterFieldInfo['classForFilterFormField'] : '',
                                'numItemsInReducedMode' => isset($arrFilterFieldInfo['numItemsInReducedMode']) && $arrFilterFieldInfo['numItemsInReducedMode'] ? $arrFilterFieldInfo['numItemsInReducedMode'] : 0,
                                'filterFormFieldType' => isset($arrFilterFieldInfo['filterFormFieldType']) && $arrFilterFieldInfo['filterFormFieldType'] ? $arrFilterFieldInfo['filterFormFieldType'] : 'checkbox'
                            ),
                            'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']]) ? $_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']] : ''
                        )
                    );

                    break;

                case 'flexContentLD':
                    /*
                     * If based on the current product list there are no flexContentsLD to be used as criteria in the filter form
                     * or no values for the current flexContentLD, we don't create a widget
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']])
                    ) {
                        continue 2;
                    }

                    /*
                     * Create the options array for this filter field ->
                     */
                    $arrOptions = array();

                    foreach ($arrFilterFieldInfo['fieldValues'] as $arrFieldValue) {

                        /*
                         * In the widget we only insert the values that should be used as filter criteria based on the current product list
                         */
                        if (!in_array($arrFieldValue['filterValue'], $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']])) {
                            continue;
                        }

                        $arrOptions[] = array(
                            'value' => $arrFieldValue['filterValue'],
                            'label' => $arrFieldValue['filterValue'],
                            'class' => (isset($arrFieldValue['classForFilterFormField']) && $arrFieldValue['classForFilterFormField'] ? ' ' . $arrFieldValue['classForFilterFormField'] : ''),
                            'important' => (isset($arrFieldValue['importantFieldValue']) && $arrFieldValue['importantFieldValue'] ? true : false),
                            'matchEstimates' => isset($_SESSION['lsShop']['filter']['matchEstimates']['flexContentLDValues'][$arrFieldValue['filterValue']]) ? $_SESSION['lsShop']['filter']['matchEstimates']['flexContentLDValues'][$arrFieldValue['filterValue']] : null
                        );
                    }
                    /*
                     * <- Create the options array for this filter field
                     */

                    $arrObjWidgets_filterFields[$filterFieldID] = new FlexWidget(
                        array(
                            'str_uniqueName' => 'filterField_' . $filterFieldID,
                            'str_template' => $arrFilterFieldInfo['templateToUseForFlexContentLDField'] ? $arrFilterFieldInfo['templateToUseForFlexContentLDField'] : 'template_formFlexContentLDFilterField_standard',
                            'str_label' => $arrFilterFieldInfo['title'],
                            'str_allowedRequestMethod' => 'post',
                            'arr_moreData' => array(
                                'filterSectionId' => $arrFilterFieldInfo['dataSource'] . '-' . $arrFilterFieldInfo['flexContentLDKey'],
                                'arrOptions' => $arrOptions,
                                'flexContentLDKey' => $arrFilterFieldInfo['flexContentLDKey'],
                                'filterMode' => isset($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'][$arrFilterFieldInfo['flexContentLDKey']]) ? $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'][$arrFilterFieldInfo['flexContentLDKey']] : $arrFilterFieldInfo['filterMode'],
                                'makeFilterModeUserAdjustable' => $arrFilterFieldInfo['makeFilterModeUserAdjustable'],
                                'arrFieldInfo' => $arrFilterFieldInfo,
                                'alias' => isset($arrFilterFieldInfo['alias']) ? $arrFilterFieldInfo['alias'] : '',
                                'classForFilterFormField' => isset($arrFilterFieldInfo['classForFilterFormField']) ? $arrFilterFieldInfo['classForFilterFormField'] : '',
                                'numItemsInReducedMode' => isset($arrFilterFieldInfo['numItemsInReducedMode']) && $arrFilterFieldInfo['numItemsInReducedMode'] ? $arrFilterFieldInfo['numItemsInReducedMode'] : 0,
                                'filterFormFieldType' => isset($arrFilterFieldInfo['filterFormFieldType']) && $arrFilterFieldInfo['filterFormFieldType'] ? $arrFilterFieldInfo['filterFormFieldType'] : 'checkbox'
                            ),
                            'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']]) ? $_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']] : ''
                        )
                    );

                    break;

                case 'flexContentLIMinMax':
                    /*
                     * If based on the current product list there are no flexContentsLIMinMax to be used as criteria in the filter form
                     * or no values for the current flexContentsLIMinMax, we don't create a widget
                     * Skip it if both rangevalues are 0
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']])
                        || (!$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['low']
                            && !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['high'])
                    ) {
                        continue 2;
                    }

					$objFlexWidget_ZFCLILow = new FlexWidget(
						array(
							'str_uniqueName' => $arrFilterFieldInfo['flexContentLIKey'].'_Low',
							'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText098'],
							'str_allowedRequestMethod' => 'post',
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['low'])
                                ? $_SESSION['lsShop']['filter']['criteria']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['low']
                                : 0
						)
					);

					$objFlexWidget_ZFCLIHigh = new FlexWidget(
						array(
							'str_uniqueName' => $arrFilterFieldInfo['flexContentLIKey'].'_High',
							'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText099'],
							'str_allowedRequestMethod' => 'post',
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['high'])
                                ? $_SESSION['lsShop']['filter']['criteria']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['high']
                                : 0
						)
					);

					$arrObjWidgets_filterFields[$filterFieldID] = array(
						'objWidget_ZFCLILow' => $objFlexWidget_ZFCLILow,
						'objWidget_ZFCLIHigh' => $objFlexWidget_ZFCLIHigh,
						'arrFilterFieldInfo' => $arrFilterFieldInfo,
                        'str_label' => $arrFilterFieldInfo['title'],
                        'str_template' => $arrFilterFieldInfo['templateToUseForFlexContentLIMinMaxField'] ?: 'template_formFlexContentLIMinMaxFilterField_standard',
                        'arr_moreData' => array(
                            'filterSectionId' => $arrFilterFieldInfo['dataSource'].'_'.$arrFilterFieldInfo['flexContentLIKey'],
                            'minValue' => $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['low'],
                            'maxValue' => $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']]['high']
                        )
					);

                    break;

				case 'attribute':
					/*
					 * If based on the current product list there are no attributes to be used as criteria in the filter form
					 * or no values for the current attribute, we don't create a widget
					 */
					if (
						!is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'])
						|| !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'])
						|| !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
						|| !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
						|| !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
					) {
						continue 2;
					}

					/*
					 * Create the options array for this filter field ->
					 */
					$arrOptions = array();

					foreach ($arrFilterFieldInfo['fieldValues'] as $arrFieldValue) {

						/*
						 * In the widget we only insert the values that should be used as filter criteria based on the current product list
						 */
						if (!in_array($arrFieldValue['filterValue'], $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])) {
							continue;
						}

						$arrOptions[] = array(
							'value' => $arrFieldValue['filterValue'],
							'label' => $arrFieldValue['title'],
							'class' => (isset($arrFieldValue['classForFilterFormField']) && $arrFieldValue['classForFilterFormField'] ? ' ' . $arrFieldValue['classForFilterFormField'] : ''),
							'important' => (isset($arrFieldValue['importantFieldValue']) && $arrFieldValue['importantFieldValue'] ? true : false),
							'matchEstimates' => isset($_SESSION['lsShop']['filter']['matchEstimates']['attributeValues'][$arrFieldValue['filterValue']]) ? $_SESSION['lsShop']['filter']['matchEstimates']['attributeValues'][$arrFieldValue['filterValue']] : null
						);
					}
					/*
					 * <- Create the options array for this filter field
					 */

					$arrObjWidgets_filterFields[$filterFieldID] = new FlexWidget(
						array(
							'str_uniqueName' => 'filterField_' . $filterFieldID,
							'str_template' => $arrFilterFieldInfo['templateToUse'] ? $arrFilterFieldInfo['templateToUse'] : 'template_formFilterField_standard',
							'str_label' => $arrFilterFieldInfo['title'],
							'str_allowedRequestMethod' => 'post',
							'arr_moreData' => array(
                                'filterSectionId' => $arrFilterFieldInfo['dataSource'] . '-' . $arrFilterFieldInfo['sourceAttribute'],
								'arrOptions' => $arrOptions,
								'sourceAttribute' => $arrFilterFieldInfo['sourceAttribute'],
								'filterMode' => isset($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$arrFilterFieldInfo['sourceAttribute']]) ? $_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$arrFilterFieldInfo['sourceAttribute']] : $arrFilterFieldInfo['filterMode'],
								'makeFilterModeUserAdjustable' => $arrFilterFieldInfo['makeFilterModeUserAdjustable'],
								'arrFieldInfo' => $arrFilterFieldInfo,
								'alias' => isset($arrFilterFieldInfo['alias']) ? $arrFilterFieldInfo['alias'] : '',
								'classForFilterFormField' => isset($arrFilterFieldInfo['classForFilterFormField']) ? $arrFilterFieldInfo['classForFilterFormField'] : '',
								'numItemsInReducedMode' => isset($arrFilterFieldInfo['numItemsInReducedMode']) && $arrFilterFieldInfo['numItemsInReducedMode'] ? $arrFilterFieldInfo['numItemsInReducedMode'] : 0,
								'filterFormFieldType' => isset($arrFilterFieldInfo['filterFormFieldType']) && $arrFilterFieldInfo['filterFormFieldType'] ? $arrFilterFieldInfo['filterFormFieldType'] : 'checkbox'
							),
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['attributes'][$arrFilterFieldInfo['sourceAttribute']]) ? $_SESSION['lsShop']['filter']['criteria']['attributes'][$arrFilterFieldInfo['sourceAttribute']] : ''
						)
					);

					break;

                case 'attributesMinMax':
                    /*
                     * If based on the current product list there are no attributesMinMax to be used as criteria in the filter form
                     * or no values for the current attributesMinMax, we don't create a widget
                     * Skip it if both rangevalues are 0
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']])
                        || (!$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['low']
                            && !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['high'])
                    ) {
                        continue 2;
                    }

					$objFlexWidget_attributesMinMaxLow = new FlexWidget(
						array(
							'str_uniqueName' => $arrFilterFieldInfo['sourceAttribute'].'_Low',
							'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText098'],
							'str_allowedRequestMethod' => 'post',
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['low'])
                                ? $_SESSION['lsShop']['filter']['criteria']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['low']
                                : 0
						)
					);

					$objFlexWidget_attributesMinMaxHigh = new FlexWidget(
						array(
							'str_uniqueName' => $arrFilterFieldInfo['sourceAttribute'].'_High',
							'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText099'],
							'str_allowedRequestMethod' => 'post',
							'var_value' => isset($_SESSION['lsShop']['filter']['criteria']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['high'])
                                ? $_SESSION['lsShop']['filter']['criteria']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['high']
                                : 0
						)
					);

					$arrObjWidgets_filterFields[$filterFieldID] = array(
						'objWidget_attributesMinMaxLow' => $objFlexWidget_attributesMinMaxLow,
						'objWidget_attributesMinMaxHigh' => $objFlexWidget_attributesMinMaxHigh,
						'arrFilterFieldInfo' => $arrFilterFieldInfo,
                        'str_label' => $arrFilterFieldInfo['title'],
                        'str_template' => $arrFilterFieldInfo['templateToUseForRangeField'] ?: 'template_formAttributesMinMaxFilterField_standard',
                        'arr_moreData' => array(
                            'filterSectionId' => $arrFilterFieldInfo['dataSource'].'_'.$arrFilterFieldInfo['sourceAttribute'],
                            'minValue' => $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['low'],
                            'maxValue' => $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']]['high']
                        )
					);

                    break;
			}
		}

		/*
		 * Generate the widgets and assign them to the template
		 */
		$arrWidgets_filterFields = array();
		foreach ($arrObjWidgets_filterFields as $filterFieldID => $objWidget_filterField) {
			if (
				!is_object($objWidget_filterField)
				&& is_array($objWidget_filterField)
				&& isset($objWidget_filterField['objWidget_priceLow'])
				&& isset($objWidget_filterField['objWidget_priceHigh'])
			) {
				/*
				 * Price widget
				 */
				$obj_template_priceFilterField = new \FrontendTemplate($objWidget_filterField['str_template']);
				$obj_template_priceFilterField->objWidget_filterField = $objWidget_filterField;
				$arrWidgets_filterFields[] = $obj_template_priceFilterField->parse();
				continue;
			}

            //ZFCLI
            if (
				!is_object($objWidget_filterField)
				&& is_array($objWidget_filterField)
				&& isset($objWidget_filterField['objWidget_ZFCLILow'])
				&& isset($objWidget_filterField['objWidget_ZFCLIHigh'])
			) {
				/*
				 * FlexContent LI MinMax widget
				 */
				$obj_template_ZFCLIFilterField = new \FrontendTemplate($objWidget_filterField['str_template']);
				$obj_template_ZFCLIFilterField->objWidget_filterField = $objWidget_filterField;
				$arrWidgets_filterFields[] = $obj_template_ZFCLIFilterField->parse();
				continue;
			}

            //attributesMinMax
            if (
				!is_object($objWidget_filterField)
				&& is_array($objWidget_filterField)
                && isset($objWidget_filterField['objWidget_attributesMinMaxLow'])
				&& isset($objWidget_filterField['objWidget_attributesMinMaxHigh'])
			) {
				/*
				 * attributes MinMax widget
				 */
				$obj_template_attributesMinMaxFilterField = new \FrontendTemplate($objWidget_filterField['str_template']);
				$obj_template_attributesMinMaxFilterField->objWidget_filterField = $objWidget_filterField;
				$arrWidgets_filterFields[] = $obj_template_attributesMinMaxFilterField->parse();
				continue;
			}

			$arrWidgets_filterFields[] = $objWidget_filterField->getOutput();
		}

		if ($obj_template !== null) {
			$obj_template->arrWidgets_filterFields = $arrWidgets_filterFields;
		}

		return array(
			'objTemplate' => $obj_template,
			'arrObjWidgets_filterFields' => $arrObjWidgets_filterFields
		);
	}

	/**
	 * Process filter settings that have just been sent
	 * via the filter form
	 */
	public function processSentFilterSettings()
	{
		if (\Input::post('FORM_SUBMIT') == 'filterForm') {
			if (\Input::post('resetFilter')) {
				ls_shop_filterHelper::resetFilter();
				return;
			}

			ls_shop_filterHelper::handleFilterModeSettings();

			$arrFilterWidgetReturn = $this->createFilterWidgets();

			$blnFormHasErrors = false;
			foreach ($arrFilterWidgetReturn['arrObjWidgets_filterFields'] as $filterFieldID => $objWidget_filterField) {
				if (
					!is_object($objWidget_filterField)
					&& is_array($objWidget_filterField)
					&& isset($objWidget_filterField['objWidget_priceLow'])
					&& isset($objWidget_filterField['objWidget_priceHigh'])
				) {
					/*
					 * Price widget
					 */
					if ($objWidget_filterField['objWidget_priceLow']->bln_hasErrors) {
						$blnFormHasErrors = true;
					}

					if ($objWidget_filterField['objWidget_priceHigh']->bln_hasErrors) {
						$blnFormHasErrors = true;
					}

					continue;
				}

                if (
					!is_object($objWidget_filterField)
					&& is_array($objWidget_filterField)
					&& isset($objWidget_filterField['objWidget_ZFCLILow'])
					&& isset($objWidget_filterField['objWidget_ZFCLIHigh'])
				) {
					/*
					 * ZFCLI widget
					 */
					if ($objWidget_filterField['objWidget_ZFCLILow']->bln_hasErrors) {
						$blnFormHasErrors = true;
					}

					if ($objWidget_filterField['objWidget_ZFCLIHigh']->bln_hasErrors) {
						$blnFormHasErrors = true;
					}

					continue;
				}

               if (
					!is_object($objWidget_filterField)
					&& is_array($objWidget_filterField)
					&& isset($objWidget_filterField['objWidget_attributesMinMaxLow'])
					&& isset($objWidget_filterField['objWidget_attributesMinMaxHigh'])
				) {
					/*
					 * attributesMinMax widget
					 */
					if ($objWidget_filterField['objWidget_attributesMinMaxLow']->bln_hasErrors) {
						$blnFormHasErrors = true;
					}

					if ($objWidget_filterField['objWidget_attributesMinMaxHigh']->bln_hasErrors) {
						$blnFormHasErrors = true;
					}

					continue;
				}

				if ($objWidget_filterField->bln_hasErrors) {
					$blnFormHasErrors = true;
				}
			}

			if (!$blnFormHasErrors) {
				/*
				 * The filter form does not have any errors, so we set the filter with the submitted values
				 */

				$arrFilterFieldInfos = ls_shop_filterHelper::getFilterFieldInfos();

				foreach ($arrFilterWidgetReturn['arrObjWidgets_filterFields'] as $filterFieldID => $objWidget_filterField) {
					switch ($arrFilterFieldInfos[$filterFieldID]['dataSource']) {
						case 'attribute':
							ls_shop_filterHelper::setFilter('attributes', array('attributeID' => $arrFilterFieldInfos[$filterFieldID]['sourceAttribute'], 'value' => $objWidget_filterField->getValue()));
							break;

						case 'attributesMinMax':
							ls_shop_filterHelper::setFilter('attributesMinMax', array('attributeID' => $arrFilterFieldInfos[$filterFieldID]['sourceAttribute'], 'low' => $objWidget_filterField['objWidget_attributesMinMaxLow']->getValue(), 'high' => $objWidget_filterField['objWidget_attributesMinMaxHigh']->getValue()));
							break;

						case 'flexContentLI':
							ls_shop_filterHelper::setFilter('flexContentsLI', array('flexContentLIKey' => $arrFilterFieldInfos[$filterFieldID]['flexContentLIKey'], 'value' => $objWidget_filterField->getValue()));
							break;

						case 'flexContentLD':
							ls_shop_filterHelper::setFilter('flexContentsLD', array('flexContentLDKey' => $arrFilterFieldInfos[$filterFieldID]['flexContentLDKey'], 'value' => $objWidget_filterField->getValue()));
							break;

						case 'flexContentLIMinMax':
							ls_shop_filterHelper::setFilter('flexContentsLIMinMax', array('flexContentLIKey' => $arrFilterFieldInfos[$filterFieldID]['flexContentLIKey'], 'low' => $objWidget_filterField['objWidget_ZFCLILow']->getValue(), 'high' => $objWidget_filterField['objWidget_ZFCLIHigh']->getValue()));
							break;

						case 'producer':
							ls_shop_filterHelper::setFilter('producers', $objWidget_filterField->getValue());
							break;

						case 'price':
							ls_shop_filterHelper::setFilter('price', array('low' => $objWidget_filterField['objWidget_priceLow']->getValue(), 'high' => $objWidget_filterField['objWidget_priceHigh']->getValue()));
							break;
					}
				}

				ls_shop_filterHelper::filterReload();
			}
		}
	}
}