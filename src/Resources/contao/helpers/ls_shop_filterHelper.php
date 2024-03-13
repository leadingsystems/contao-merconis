<?php
namespace Merconis\Core;

class ls_shop_filterHelper {
    public static function getFilterSummary() {
        global $objPage;
        $str_currentLanguage = ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();

        $arr_filterSummary = [
            'arr_attributes' => [],
            'arr_attributesMinMax' => $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributesMinMax'] ?? null,
            'arr_flexContentsLI' => [],
            'arr_flexContentsLD' => [],
            'arr_flexContentsLIMinMax' => $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['flexContentsLIMinMax'] ?? null,
            'arr_producers' => $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['producers'] ?? null,
            'arr_price' => $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['price'] ?? null,
        ];

        $arr_filterAllFields = [
            'arr_attributes' => [],
            'arr_attributesMinMax' => [],
            'arr_flexContentsLI' => [],
            'arr_flexContentsLD' => [],
            'arr_flexContentsLIMinMax' => [],
            'arr_producers' => [],
            'arr_price' => [],
        ];


        if (is_array($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'] ?? null)) {
            foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'] as $int_filterAttributeId => $arr_filterValues) {
                $str_filterAttributeName = ls_shop_languageHelper::getMultiLanguage($int_filterAttributeId, 'tl_ls_shop_attributes', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                $arr_filterSummary['arr_attributes'][$int_filterAttributeId] = [
                    'str_title' => $str_filterAttributeName,
                    'arr_values' => [],
                    'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$int_filterAttributeId]]
                ];

                foreach ($arr_filterValues as $int_filterValueId) {
                    $str_filterValueName = ls_shop_languageHelper::getMultiLanguage($int_filterValueId, 'tl_ls_shop_attribute_values', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                    $arr_filterSummary['arr_attributes'][$int_filterAttributeId]['arr_values'][$int_filterValueId] = $str_filterValueName;
                }
            }
        }

        if (is_array($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['flexContentsLI'] ?? null)) {
            foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['flexContentsLI'] as $str_flexContentLIKey => $arr_filterValues) {
                $arr_filterSummary['arr_flexContentsLI'][$str_flexContentLIKey] = [
                    'str_title' => $str_flexContentLIKey,
                    'arr_values' => [],
                    'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'][$str_flexContentLIKey]]
                ];

                foreach ($arr_filterValues as $str_filterValue) {
                    $arr_filterSummary['arr_flexContentsLI'][$str_flexContentLIKey]['arr_values'][$str_filterValue] = $str_filterValue;
                }
            }
        }

        if (is_array($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['flexContentsLD'][$str_currentLanguage] ?? null)) {
            foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['flexContentsLD'][$str_currentLanguage] as $str_flexContentLDKey => $arr_filterValues) {
                $arr_filterSummary['arr_flexContentsLD'][$str_flexContentLDKey] = [
                    'str_title' => $str_flexContentLDKey,
                    'arr_values' => [],
                    'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'][$str_flexContentLDKey]]
                ];

                foreach ($arr_filterValues as $str_filterValue) {
                    $arr_filterSummary['arr_flexContentsLD'][$str_flexContentLDKey]['arr_values'][$str_filterValue] = $str_filterValue;
                }
            }
        }

        $arrFilterFieldInfos = ls_shop_filterHelper::getFilterFieldInfos();

        foreach ($arrFilterFieldInfos as $filterFieldID => $arrFilterFieldInfo) {
            switch ($arrFilterFieldInfo['dataSource']) {
                case 'price':
                    /*
                     * If based on the current product list there are no prices to be used as criteria in the filter form
                     * or no values for the current price, we don't create a summary item
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['low'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['high'])
                        || !($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['high'])
                    ) {
                        break;
                    }
                    $arr_filterAllFields['arr_price'] = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price'];
                    break;

                case 'producer':
                    /*
                     * If based on the current product list there are no producers to be used as criteria in the filter form
                     * or no values for the current producer, we don't create a summary item
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])
                    ) {
                        break;
                    }
                    $arr_filterAllFields['arr_producers'] = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'];
                    break;

                case 'attribute':
                    /*
                     * If based on the current product list there are no attributes to be used as criteria in the filter form
                     * or no values for the current attribute, we don't create a summary item
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$arrFilterFieldInfo['sourceAttribute']])
                    ) {
                        break;
                    }

                    $int_filterAttributeId = $arrFilterFieldInfo['sourceAttribute'];
                    $arr_filterValues = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$int_filterAttributeId];

                    $str_filterAttributeName = ls_shop_languageHelper::getMultiLanguage($int_filterAttributeId, 'tl_ls_shop_attributes', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                    $arr_filterAllFields['arr_attributes'][$int_filterAttributeId] = [
                        'str_title' => $str_filterAttributeName,
                        'arr_values' => [],
                        'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$int_filterAttributeId] ?? null] ?? null
                    ];

                    foreach ($arr_filterValues as $int_filterValueId) {
                        $str_filterValueName = ls_shop_languageHelper::getMultiLanguage($int_filterValueId, 'tl_ls_shop_attribute_values', array('title'), array($objPage->language ? $objPage->language : ls_shop_languageHelper::getFallbackLanguage()));
                        $arr_filterAllFields['arr_attributes'][$int_filterAttributeId]['arr_values'][$int_filterValueId] = $str_filterValueName;
                    }
                    break;

                case 'flexContentLI':
                    /*
                     * If based on the current product list there are no flexContentsLI to be used as criteria in the filter form
                     * or no values for the current flexContentLI, we don't create a summary item
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']])
                    ) {
                        break;
                    }

                    $arr_filterValues = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']];

                    $arr_filterAllFields['arr_flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']] = [
                        'str_caption' => $arrFilterFieldInfo['title'],
                        'str_title' => $arrFilterFieldInfo['flexContentLIKey'],
                        'arr_values' => [],
                        'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']] ?? null] ?? null
                    ];

                    foreach ($arr_filterValues as $str_filterValue) {
                        $arr_filterAllFields['arr_flexContentsLI'][$arrFilterFieldInfo['flexContentLIKey']]['arr_values'][$str_filterValue] = $str_filterValue;
                    }
                    break;

                case 'flexContentLD':
                    /*
                     * If based on the current product list there are no flexContentsLD to be used as criteria in the filter form
                     * or no values for the current flexContentLD, we don't create a summary item
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']])
                    ) {
                        break;
                    }

                    $arr_filterValues = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$arrFilterFieldInfo['flexContentLDKey']];

                    $arr_filterAllFields['arr_flexContentsLD'][$arrFilterFieldInfo['flexContentLDKey']] = [
                        'str_caption' => $arrFilterFieldInfo['title'],
                        'str_title' => $arrFilterFieldInfo['flexContentLDKey'],
                        'arr_values' => [],
                        'str_logicalOperator' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['general'][$_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'][$arrFilterFieldInfo['flexContentLDKey']] ?? null] ?? null
                    ];

                    foreach ($arr_filterValues as $str_filterValue) {
                        $arr_filterAllFields['arr_flexContentsLD'][$arrFilterFieldInfo['flexContentLDKey']]['arr_values'][$str_filterValue] = $str_filterValue;
                    }
                    break;

                case 'flexContentLIMinMax':
                    /*
                     * If based on the current product list there are no flexContentLIMinMax to be used as criteria in the filter form
                     * or no values for the current flexContentLIMinMax, we don't create a summary item
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']])
                    ) {
                        break;
                    }

                    $arr_filterValues = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']];

                    $arr_filterAllFields['arr_flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']] = [
                        'str_caption' => $arrFilterFieldInfo['title'],
                        'str_title' => $arrFilterFieldInfo['flexContentLIKey'],
                    ];

                    $arr_filterAllFields['arr_flexContentsLIMinMax'][$arrFilterFieldInfo['flexContentLIKey']] += $arr_filterValues;
                    break;

                case 'attributesMinMax':
                    /*
                     * If based on the current product list there are no attributesMinMax to be used as criteria in the filter form
                     * or no values for the current attributesMinMax, we don't create a summary item
                     */
                    if (
                        !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'])
                        || !isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']])
                        || !is_array($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']])
                        || !count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']])
                    ) {
                        break;
                    }

                    $arr_filterValues = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']];

                    $arr_filterAllFields['arr_attributesMinMax'][$arrFilterFieldInfo['sourceAttribute']] = [
                        'str_caption' => $arrFilterFieldInfo['title'],
                        'str_title' => $arrFilterFieldInfo['sourceAttribute'],
                        'low' => $arr_filterValues['low'],
                        'high' => $arr_filterValues['high'],
                    ];
                    break;
            }

        }

        $bln_attributesFilterCurrentlyAvailable = is_array($arr_filterAllFields['arr_attributes']) && count($arr_filterAllFields['arr_attributes']);
        $bln_flexContentsLIFilterCurrentlyAvailable = is_array($arr_filterAllFields['arr_flexContentsLI']) && count($arr_filterAllFields['arr_flexContentsLI']);
        $bln_flexContentsLDFilterCurrentlyAvailable = is_array($arr_filterAllFields['arr_flexContentsLD']) && count($arr_filterAllFields['arr_flexContentsLD']);
        $bln_poducerFilterCurrentlyAvailable = is_array($arr_filterAllFields['arr_producers']) && count($arr_filterAllFields['arr_producers']);
        $bln_priceFilterCurrentlyAvailable = (
            is_array($arr_filterAllFields['arr_price'])
            && (
                (isset($arr_filterAllFields['arr_price']['low']) && $arr_filterAllFields['arr_price']['low'])
                || (isset($arr_filterAllFields['arr_price']['high']) && $arr_filterAllFields['arr_price']['high'])
            )
        );

        foreach($arr_filterAllFields['arr_flexContentsLIMinMax'] as $flexContentLIMinMaxKey => $flexContentLIMinMaxValues) {
            if (
                ($arr_filterAllFields['arr_flexContentsLIMinMax'][$flexContentLIMinMaxKey]['low'] ?? '')
                ||
                ($arr_filterAllFields['arr_flexContentsLIMinMax'][$flexContentLIMinMaxKey]['high'] ?? '')
                ) {
                $bln_flexContentsLIMinMaxFilterCurrentlyAvailable = true;
                break;
            }
        }

        $bln_attributesMinMaxFilterCurrentlyAvailable = false;
        foreach($arr_filterAllFields['arr_attributesMinMax'] as $attributeID => $attributesMinMaxValues) {
            if (
                ($arr_filterAllFields['arr_attributesMinMax'][$attributeID]['low'] ?? '')
                ||
                ($arr_filterAllFields['arr_attributesMinMax'][$attributeID]['high'] ?? '')
                ) {
                $bln_attributesMinMaxFilterCurrentlyAvailable = true;
                break;
            }
        }



        $bln_currentlyFilteringByAttributes = is_array($arr_filterSummary['arr_attributes']) && count($arr_filterSummary['arr_attributes']);
        $bln_currentlyFilteringByFlexContentsLI = is_array($arr_filterSummary['arr_flexContentsLI']) && count($arr_filterSummary['arr_flexContentsLI']);
        $bln_currentlyFilteringByFlexContentsLD = is_array($arr_filterSummary['arr_flexContentsLD']) && count($arr_filterSummary['arr_flexContentsLD']);
        $bln_currentlyFilteringByProducer = is_array($arr_filterSummary['arr_producers']) && count($arr_filterSummary['arr_producers']);
        $bln_currentlyFilteringByPrice = (
            is_array($arr_filterSummary['arr_price'])
            && (
                (isset($arr_filterSummary['arr_price']['low']) && $arr_filterSummary['arr_price']['low'])
                || (isset($arr_filterSummary['arr_price']['high']) && $arr_filterSummary['arr_price']['high'])
            )
        );

        $bln_currentlyFilteringByFlexContentsLIMinMax = false;
        foreach($arr_filterSummary['arr_flexContentsLIMinMax'] as $flexContentLIMinMaxKey => $flexContentLIMinMaxValues) {
            $arr_filterSummary['arr_flexContentsLIMinMax'][$flexContentLIMinMaxKey]['currentlyFiltering'] = false;
            if (
                ($arr_filterSummary['arr_flexContentsLIMinMax'][$flexContentLIMinMaxKey]['low'] ?? '')
                ||
                ($arr_filterSummary['arr_flexContentsLIMinMax'][$flexContentLIMinMaxKey]['high'] ?? '')
                ) {
                $arr_filterSummary['arr_flexContentsLIMinMax'][$flexContentLIMinMaxKey]['currentlyFiltering'] = true;
                $bln_currentlyFilteringByFlexContentsLIMinMax = true;
                break;
            }
        }

        $bln_currentlyFilteringByAttributesMinMax = false;
        foreach($arr_filterSummary['arr_attributesMinMax'] as $attributeID => $attributesMinMaxValues) {
            $arr_filterSummary['arr_attributesMinMax'][$attributeID]['currentlyFiltering'] = false;
            if (
                ($arr_filterSummary['arr_attributesMinMax'][$attributeID]['low'] ?? '')
                ||
                ($arr_filterSummary['arr_attributesMinMax'][$attributeID]['high'] ?? '')
                ) {
                $arr_filterSummary['arr_attributesMinMax'][$attributeID]['currentlyFiltering'] = true;
                $bln_currentlyFilteringByAttributesMinMax = true;
                break;
            }
        }



        /*
         * Handle sorting by priority -->
         */
        $arr_filterFieldSortingNumbers = [];
        $arr_filterFieldPriorities = [];

        $obj_dbres_filterFieldPriorities = \Database::getInstance()
            ->prepare("
                SELECT  id,
                        sourceAttribute,
                        flexContentLIKey,
                        flexContentLDKey,
                        dataSource,
                        priority
                FROM    tl_ls_shop_filter_fields
            ")
            ->execute();

        while ($obj_dbres_filterFieldPriorities->next()) {
            $str_priorityKeySuffix = '';
            switch ($obj_dbres_filterFieldPriorities->dataSource) {
                case 'attribute':
                    $str_priorityKeySuffix = '_' . $obj_dbres_filterFieldPriorities->sourceAttribute;
                    break;

                case 'flexContentLI':
                    $str_priorityKeySuffix = '_' . $obj_dbres_filterFieldPriorities->flexContentLIKey;
                    break;

                case 'flexContentLD':
                    $str_priorityKeySuffix = '_' . $obj_dbres_filterFieldPriorities->flexContentLDKey;
                    break;

                case 'flexContentLIMinMax':
                    $str_priorityKeySuffix = '_' . $obj_dbres_filterFieldPriorities->flexContentLIKey;
                    break;
            }

            $arr_filterFieldPriorities[$obj_dbres_filterFieldPriorities->dataSource . $str_priorityKeySuffix] = $obj_dbres_filterFieldPriorities->priority;
        }

        foreach (array_keys($arr_filterAllFields['arr_attributes']) as $int_filterAttributeId) {
            $arr_filterFieldSortingNumbers['attribute_' . $int_filterAttributeId] = $arr_filterFieldPriorities['attribute_' . $int_filterAttributeId];
        }

        foreach (array_keys($arr_filterAllFields['arr_flexContentsLI']) as $str_flexContentLIKey) {
            $arr_filterFieldSortingNumbers['flexContentLI_' . $str_flexContentLIKey] = $arr_filterFieldPriorities['flexContentLI_' . $str_flexContentLIKey];
        }

        foreach (array_keys($arr_filterAllFields['arr_flexContentsLD']) as $str_flexContentLDKey) {
            $arr_filterFieldSortingNumbers['flexContentLD_' . $str_flexContentLDKey] = $arr_filterFieldPriorities['flexContentLD_' . $str_flexContentLDKey];
        }

        foreach (array_keys($arr_filterAllFields['arr_flexContentsLIMinMax']) as $str_flexContentLIMinMaxKey) {
            $arr_filterFieldSortingNumbers['flexContentLIMinMax_' . $str_flexContentLIMinMaxKey] = $arr_filterFieldPriorities['flexContentLIMinMax_' . $str_flexContentLIMinMaxKey];
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
            'bln_attributesMinMaxFilterCurrentlyAvailable' => $bln_attributesMinMaxFilterCurrentlyAvailable,
            'bln_flexContentsLIFilterCurrentlyAvailable' => $bln_flexContentsLIFilterCurrentlyAvailable,
            'bln_flexContentsLDFilterCurrentlyAvailable' => $bln_flexContentsLDFilterCurrentlyAvailable,
            'bln_flexContentsLIMinMaxFilterCurrentlyAvailable' => $bln_flexContentsLIMinMaxFilterCurrentlyAvailable,
            'bln_poducerFilterCurrentlyAvailable' => $bln_poducerFilterCurrentlyAvailable,
            'bln_priceFilterCurrentlyAvailable' => $bln_priceFilterCurrentlyAvailable,
            'bln_currentlyFilteringByAttributes' => $bln_currentlyFilteringByAttributes,
            'bln_currentlyFilteringByAttributesMinMax' => $bln_currentlyFilteringByAttributesMinMax,
            'bln_currentlyFilteringByFlexContentsLI' => $bln_currentlyFilteringByFlexContentsLI,
            'bln_currentlyFilteringByFlexContentsLD' => $bln_currentlyFilteringByFlexContentsLD,
            'bln_currentlyFilteringByFlexContentsLIMinMax' => $bln_currentlyFilteringByFlexContentsLIMinMax,
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
        $obj_template->bln_flexContentsLIFilterCurrentlyAvailable = $arr_summaryData['bln_flexContentsLIFilterCurrentlyAvailable'];
        $obj_template->bln_flexContentsLDFilterCurrentlyAvailable = $arr_summaryData['bln_flexContentsLDFilterCurrentlyAvailable'];
        $obj_template->bln_flexContentsLIMinMaxFilterCurrentlyAvailable = $arr_summaryData['bln_flexContentsLIMinMaxFilterCurrentlyAvailable'];
        $obj_template->bln_poducerFilterCurrentlyAvailable = $arr_summaryData['bln_poducerFilterCurrentlyAvailable'];
        $obj_template->bln_priceFilterCurrentlyAvailable = $arr_summaryData['bln_priceFilterCurrentlyAvailable'];
        $obj_template->bln_currentlyFilteringByAttributes = $arr_summaryData['bln_currentlyFilteringByAttributes'];
        $obj_template->bln_currentlyFilteringByFlexContentsLI = $arr_summaryData['bln_currentlyFilteringByFlexContentsLI'];
        $obj_template->bln_currentlyFilteringByFlexContentsLD = $arr_summaryData['bln_currentlyFilteringByFlexContentsLD'];
        $obj_template->bln_currentlyFilteringByFlexContentsLIMinMax = $arr_summaryData['bln_currentlyFilteringByFlexContentsLIMinMax'];
        $obj_template->bln_currentlyFilteringByProducer = $arr_summaryData['bln_currentlyFilteringByProducer'];
        $obj_template->bln_currentlyFilteringByPrice = $arr_summaryData['bln_currentlyFilteringByPrice'];
        $obj_template->arr_filterFieldSortingNumbers = $arr_summaryData['arr_filterFieldSortingNumbers'];
        $str_filterSummaryHtml = $obj_template->parse();
        return $str_filterSummaryHtml;
    }

	public static function createEmptyFilterSession() {
		$_SESSION['lsShop']['filter'] = array(
			'criteria' => array(
				'attributes' => array(),
                'attributesMinMax' => array(),
				'flexContentsLI' => array(),
                'flexContentsLD' => array(),
                'flexContentsLIMinMax' => array(),
				'price' => array(
					'low' => 0,
					'high' => 0
				),
				'producers' => array()
			),
			'arrCriteriaToUseInFilterForm' => array(
				'attributes' => array(),
                'attributesMinMax' => array(),
				'flexContentsLI' => array(),
                'flexContentsLD' => array(),
                'flexContentsLIMinMax' => array(),
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
				'flexContentLIValues' => array(),
                'flexContentLDValues' => array(),
				'producers' => array()
			),
			'lastResetTimestamp' => time(),
			'noMatchEstimatesDetermined' => false
		);
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

			case 'flexContentLI':
				$arr_flexContentLIValues = ls_shop_generalHelper::getFlexContentLIValues($arrFilterFieldInfo['flexContentLIKey']);
				foreach ($arr_flexContentLIValues as $str_flexContentLIValue) {
					$arrFilterFieldValues[] = ['filterValue' => $str_flexContentLIValue];
				}
				break;

			case 'flexContentLD':
				$arr_flexContentLDValues = ls_shop_generalHelper::getFlexContentLDValues($arrFilterFieldInfo['flexContentLDKey']);
				foreach ($arr_flexContentLDValues as $str_flexContentLDValue) {
					$arrFilterFieldValues[] = ['filterValue' => $str_flexContentLDValue];
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
		$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm'] = array(
			'attributes' => array(),
            'attributesMinMax' => array(),
            'flexContentsLI' => array(),
            'flexContentsLD' => array(),
            'flexContentsLIMinMax' => array(
			),
			'price' => array(
				'low' => null,
				'high' => null
			),
			'producers' => array()
		);
	}

	public static function addPriceToCriteriaUsedInFilterForm($price, $where = 'arrCriteriaToUseInFilterForm') {
		if ($_SESSION['lsShop']['filter'][$where]['price']['low'] === null || $price < $_SESSION['lsShop']['filter'][$where]['price']['low']) {
			$_SESSION['lsShop']['filter'][$where]['price']['low'] = $price;
		}
		if ($_SESSION['lsShop']['filter'][$where]['price']['high'] === null || $price > $_SESSION['lsShop']['filter'][$where]['price']['high']) {
			$_SESSION['lsShop']['filter'][$where]['price']['high'] = $price;
		}
	}

	public static function addProducerToCriteriaUsedInFilterForm($strProducer = '', $where = 'arrCriteriaToUseInFilterForm') {
		if (!$strProducer || in_array($strProducer, $_SESSION['lsShop']['filter'][$where]['producers'])) {
			return;
		}

		$_SESSION['lsShop']['filter'][$where]['producers'][] = $strProducer;
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

		if (!isset($_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID])) {
			$_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID] = array();
		}

		if (!in_array($varAttributeValueID, $_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID])) {
			$_SESSION['lsShop']['filter'][$where]['attributes'][$attributeID][] = $varAttributeValueID;
		}
	}


    /** The DB values ​​of products are consolidated (for filter creation) into the product/variant structure.
     *  Since the data for standard FCLI and numerical FCLI are stored in the same field, the separation occurs here.
     *  Numerical FCLI must be numerical and the limit values ​​are determined immediately
     *
     * @param   array       $FCLIs              flexContent Language Independent of product or Variant
     * @param   string      $type               ´flex_contentsLIMinMax´ or ´flex_contentsLanguageIndependent´
     * @param   bool        $rangeForProduct    boolean, true if product
     * @return  array       $result             low and high values and lowest/highest for products
     */
    public static function processFCLI($FCLIs, $type, $rangeForProduct = false)
    {
        if (!is_array($FCLIs) ) {
            return;
        }
        $result = [];

        foreach ($FCLIs as $flexContentLIKey => $flexContentLIValues) {
            $min = null;
            $max = null;

            if ($type == 'flex_contentsLIMinMax' && self::isFCLIMinMax($flexContentLIKey)) {

                if (!is_array($flexContentLIValues)) {
                    $flexContentLIValues = [$flexContentLIValues];
                }

                //The filter must simply ignore non-numerical values in Z-FCLIs
                $flexContentLIValues = array_filter($flexContentLIValues, 'is_numeric');

                if (!count($flexContentLIValues)) {
                    continue;
                }

                foreach ($flexContentLIValues as $flexContentLIValue) {
                    self::determineMinMaxValues($flexContentLIValue, $min, $max);
                }

                $result += [$flexContentLIKey => ['low' => $min, 'high' => $max]];

                if ($rangeForProduct) {
                    $result[$flexContentLIKey] += ['lowestValue' => $min, 'highestValue' => $max];
                }

            } else if ($type == 'flex_contentsLanguageIndependent' && !self::isFCLIMinMax($flexContentLIKey)) {
                $result = [$flexContentLIKey => $flexContentLIValues];
            }
        }
        return $result;
    }

    /** Compares the LI key with the list of LI MinMax keys and returns true if it is included
     *
     * @param $flexContentLIKey         FCLI Key to compare
     * @return true|false               True if FCLI MinMax, false if standard FCLI
     */
    public static function isFCLIMinMax($flexContentLIKey)
    {
        if (!isset($_SESSION['lsShop']['filter']['flexContentLIKeys'])) {
            /**
            *  Since the information about FCLI as to whether it is of the min/max type is not stored with the
            *  product (or variant), the keys of the filter fields must be fetched and assigned subsequently
            */
            $_SESSION['lsShop']['filter']['flexContentLIKeys'] = ls_shop_generalHelper::getFlexContentLIMinMaxKeys();
        }

        return (in_array($flexContentLIKey, $_SESSION['lsShop']['filter']['flexContentLIKeys']));
    }


    public static function addFlexContentLIValueToCriteriaUsedInFilterForm($str_flexContentLIKey = null, $var_value = null, $where = 'arrCriteriaToUseInFilterForm') {
		if (!$str_flexContentLIKey || !$var_value) {
			return;
		}

		if (is_array($var_value)) {
			foreach ($var_value as $str_value) {
				self::addFlexContentLIValueToCriteriaUsedInFilterForm($str_flexContentLIKey, $str_value, $where);
			}
			return;
		}

		if (!isset($_SESSION['lsShop']['filter'][$where]['flexContentsLI'][$str_flexContentLIKey])) {
			$_SESSION['lsShop']['filter'][$where]['flexContentsLI'][$str_flexContentLIKey] = array();
		}

		if (!in_array($var_value, $_SESSION['lsShop']['filter'][$where]['flexContentsLI'][$str_flexContentLIKey])) {
			$_SESSION['lsShop']['filter'][$where]['flexContentsLI'][$str_flexContentLIKey][] = $var_value;
		}
	}


    public static function addFlexContentLIMinMaxValueToCriteriaUsedInFilterForm($str_flexContentLIKey = null, $var_value = null, $where = 'arrCriteriaToUseInFilterForm') {
		if (!$str_flexContentLIKey || $var_value === null) {
			return;
		}
        if (!isset($_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey])) {
            $_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey] = [];
        }
        if (empty($_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey]['low'])
            || $var_value < $_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey]['low']) {
            $_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey]['low'] = $var_value;
        }
        if (empty($_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey]['high'])
            || $var_value > $_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey]['high']) {
            $_SESSION['lsShop']['filter'][$where]['flexContentsLIMinMax'][$str_flexContentLIKey]['high'] = $var_value;
        }
	}

    /**
     * Each product and variant attribute and its numerical values ​​are compared with the overall filter array
     * and the highest or
     * lowest value are adopted.
     * It therefore represents the MinMax limit value determination for attribute areas.
     *
     * @param int           $attributeID        New value for the range limit
     * @param int           $value              numerischer Wert den es zu prüfen gilt
     * @param string        $where              Unterschlüssel für die lsShop-filter Sessionvariable
     * @return void
     */
    public static function addAttributeMinMaxToCriteriaUsedInFilterForm($attributeID = null, $value = null, $where = 'arrCriteriaToUseInFilterForm') {
		if (!$attributeID || $value === null) {
			return;
		}
        if (!isset($_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID])) {
            $_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID] = [];
        }
        if (empty($_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID]['low'])
            || $value < $_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID]['low']) {
            $_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID]['low'] = $value;
        }
        if (empty($_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID]['high'])
            || $value > $_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID]['high']) {
            $_SESSION['lsShop']['filter'][$where]['attributesMinMax'][$attributeID]['high'] = $value;
        }
	}

    /** This function determines a range limit by comparing the passed value with the current range limits and updating it accordingly
     *
     * @param $value                    New value for the range limit
     * @param $variableMin              Reference variable for the min value
     * @param $variableMax              Reference variable for the max value
     * @return void
     */
    public static function determineMinMaxValues($value, &$variableMin,&$variableMax
    #, $checkZero = false
    )
    {
        $value = (float) $value;

        if ($variableMin === null || $value < $variableMin) {
            $variableMin = $value;
        }
        if ($variableMax === null || $value > $variableMax) {
            $variableMax = $value;
        }


    }


	public static function addFlexContentLDValueToCriteriaUsedInFilterForm($str_flexContentLDKey = null, $var_value = null, $where = 'arrCriteriaToUseInFilterForm') {
        global $objPage;
        $str_currentLanguage = ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();

		if (!$str_flexContentLDKey || !$var_value) {
			return;
		}

		if (is_array($var_value)) {
			foreach ($var_value as $str_value) {
				self::addFlexContentLDValueToCriteriaUsedInFilterForm($str_flexContentLDKey, $str_value, $where);
			}
			return;
		}

		if (!isset($_SESSION['lsShop']['filter'][$where]['flexContentsLD'][$str_currentLanguage][$str_flexContentLDKey])) {
			$_SESSION['lsShop']['filter'][$where]['flexContentsLD'][$str_currentLanguage][$str_flexContentLDKey] = array();
		}

		if (!in_array($var_value, $_SESSION['lsShop']['filter'][$where]['flexContentsLD'][$str_currentLanguage][$str_flexContentLDKey])) {
			$_SESSION['lsShop']['filter'][$where]['flexContentsLD'][$str_currentLanguage][$str_flexContentLDKey][] = $var_value;
		}
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
        global $objPage;
        $str_currentLanguage = ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();

		if (!$arrCriteriaToFilterWith) {
			$arrCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];
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
					if (($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$attributeID] ?? null) === 'and') {
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
		 * Check the product's flexContentsLI
		 */
		if ($blnWholeProductCouldStillMatch) {
			if (is_array($arrCriteriaToFilterWith['flexContentsLI'])) {
				foreach ($arrCriteriaToFilterWith['flexContentsLI'] as $str_flexContentLIKey => $arr_flexContentLIValues) {
					/*
					 * The array returned by array_intersect() contains the requested flexContentLI values which
					 * are also included in the product's flexContentLI values for the respective flexContentLIKey.
					 *
					 * In filterMode "or": If we get at least one flexContentLI value
					 * that matches, the product is a match for this flexContentLI,
					 * otherwise it's not and we return false.
					 *
					 * In filterMode "and": If all the flexContentLI values match,
					 * the product is a match for this flexContentLI, otherwise it's
					 * not and we return false.
					 */

                    /*
                     * It is unclear whether the product actually has the relevant flexContentLI and if it has,
                     * the flexContentLI must not necessarily be an array. Therefore, we make sure that we always
                     * have a proper array for the filter check.
                     */
                    $arr_productFlexContentLIValuesToCompareWithFilterRequirements = (array) ($arrProductInfo['flex_contentsLanguageIndependent'][$str_flexContentLIKey] ?? []);

                    if (($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'][$str_flexContentLIKey] ?? null) === 'and') {
						if (count(array_intersect($arr_flexContentLIValues, $arr_productFlexContentLIValuesToCompareWithFilterRequirements)) !== count($arr_flexContentLIValues)) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					} else {
						if (!count(array_intersect($arr_flexContentLIValues, $arr_productFlexContentLIValuesToCompareWithFilterRequirements))) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					}
				}
			}
		}

		/*
		 * Check the product's flexContentsLD
		 */
		if ($blnWholeProductCouldStillMatch) {
			if (is_array($arrCriteriaToFilterWith['flexContentsLD']) && isset($arrCriteriaToFilterWith['flexContentsLD'][$str_currentLanguage])) {
				foreach ($arrCriteriaToFilterWith['flexContentsLD'][$str_currentLanguage] as $str_flexContentLDKey => $arr_flexContentLDValues) {
					/*
					 * The array returned by array_intersect() contains the requested flexContentLD values which
					 * are also included in the product's flexContentLD values for the respective flexContentLDKey.
					 *
					 * In filterMode "or": If we get at least one flexContentLD value
					 * that matches, the product is a match for this flexContentLD,
					 * otherwise it's not and we return false.
					 *
					 * In filterMode "and": If all the flexContentLD values match,
					 * the product is a match for this flexContentLD, otherwise it's
					 * not and we return false.
					 */

                    /*
                     * It is unclear whether the product actually has the relevant flexContentLD and if it has,
                     * the flexContentLD must not necessarily be an array. Therefore, we make sure that we always
                     * have a proper array for the filter check.
                     */
                    $arr_productFlexContentLDValuesToCompareWithFilterRequirements = (array) ($arrProductInfo['flex_contents_'.$str_currentLanguage][$str_flexContentLDKey] ?? []);

                    if (($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'][$str_flexContentLDKey] ?? null) === 'and') {
						if (count(array_intersect($arr_flexContentLDValues, $arr_productFlexContentLDValuesToCompareWithFilterRequirements)) !== count($arr_flexContentLDValues)) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					} else {
						if (!count(array_intersect($arr_flexContentLDValues, $arr_productFlexContentLDValuesToCompareWithFilterRequirements))) {
							$blnWholeProductCouldStillMatch = false;
							break;
						}
					}
				}
			}
		}

		/*
		 * Check the product's flexContentsLI MinMax
		 */
		if ($blnWholeProductCouldStillMatch) {
			if (is_array($arrCriteriaToFilterWith['flexContentsLIMinMax'])) {
				foreach ($arrCriteriaToFilterWith['flexContentsLIMinMax'] as $str_flexContentLIMinMaxKey => $arr_flexContentLIMinMaxValues) {
					/*
					 * The array returned by array_intersect() contains the requested flexContentLI values which
					 * are also included in the product's flexContentLI values for the respective flexContentLIKey.
					 *
					 */
                    /*
                     * Ignore the range filter if the high filter range is not higher than 0 and not as least as high as the low filter range.
                     * This way it is possible to skip the range filter part by setting both filter parameters to 0.
                     */
                    if ($arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['high']
                        >= $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['low']
                        && $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['high'] > 0) {
                        if (!count($arrProductInfo['variants'])) {
                            /*
                             * If the product doesn't have variants, the product's FCLI has to be checked
                             */
                            if (!isset($arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]) ||
                                $arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]['high'] < $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['low']
                                || $arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]['low'] > $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['high']) {
                                $blnWholeProductCouldStillMatch = false;
                            }
                        } else {
                            /*
                             * If the product has variants, we have to use it's highest and lowest FCLI to see,
                             * if it is possible to match or filter out the whole product or if we have to
                             * check each variant separately.
                             */

                            if (isset($arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]) &&
                                $arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]['lowestValue'] > $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['low']
                                && $arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]['highestValue'] < $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['high']) {
                                /*
                                 * If the product's lowest value is higher than the low filter limit and the product's highest value is lower than the high filter limit,
                                 * this means that all product variants must be within the value range and, regarding the value, the product matches as a whole.
                                 */
                            } else if (!isset($arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey])                                ||
                                $arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]['highestValue'] < $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['low']
                                || $arrProductInfo['flex_contentsLIMinMax'][$str_flexContentLIMinMaxKey]['lowestValue'] > $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['high']) {
                                /*
                                 * If even the product's highest value is lower than the low filter limit or it's lowest
                                 * value is higher than the high filter limit, this means that none of it's variants
                                 * has a value within the range and the product has to be filtered out as a whole.
                                 */
                                $blnWholeProductCouldStillMatch = false;
                                $blnVariantsCouldStillMatch = false;
                            } else {
                                /*
                                 * If none of the above is true, this means that there's definitely a variant that has a value outside the range
                                 * but there could be one ore more variants that have a value within.
                                 */
                                $blnWholeProductCouldStillMatch = false;
                                $blnVariantsCouldStillMatch = true;
                            }

                        }
                    }
				}
			}
		}



        /*
		 * Check the product's Attributes MinMax
		 */

        self::checkIfProductMatchesFilter_ranges( $blnWholeProductCouldStillMatch
            , $blnVariantsCouldStillMatch, $arrCriteriaToFilterWith, $arrProductInfo
            , 'attributesMinMax' );
/*
		if ($blnWholeProductCouldStillMatch) {
			if (is_array($arrCriteriaToFilterWith['attributesMinMax'])) {
				foreach ($arrCriteriaToFilterWith['attributesMinMax'] as $str_flexContentLIMinMaxKey => $arr_flexContentLIMinMaxValues) {

                    if ($arrCriteriaToFilterWith['attributesMinMax'][$str_flexContentLIMinMaxKey]['high']
                        >= $arrCriteriaToFilterWith['attributesMinMax'][$str_flexContentLIMinMaxKey]['low']
                        && $arrCriteriaToFilterWith['attributesMinMax'][$str_flexContentLIMinMaxKey]['high'] > 0) {
                        if (!count($arrProductInfo['variants'])) {

                            if (!isset($arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]) ||
                                $arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]['high'] < $arrCriteriaToFilterWith['attributesMinMax'][$str_flexContentLIMinMaxKey]['low']
                                || $arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]['low'] > $arrCriteriaToFilterWith['attributesMinMax'][$str_flexContentLIMinMaxKey]['high']) {
                                $blnWholeProductCouldStillMatch = false;
                            }
                        } else {


                            if (isset($arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]) &&
                                $arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]['lowestValue'] > $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['low']
                                && $arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]['highestValue'] < $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['high']) {

                            } else if (!isset($arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey])                                ||
                                $arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]['highestValue'] < $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['low']
                                || $arrProductInfo['attributesMinMax'][$str_flexContentLIMinMaxKey]['lowestValue'] > $arrCriteriaToFilterWith['flexContentsLIMinMax'][$str_flexContentLIMinMaxKey]['high']) {

                                $blnWholeProductCouldStillMatch = false;
                                $blnVariantsCouldStillMatch = false;
                            } else {

                                $blnWholeProductCouldStillMatch = false;
                                $blnVariantsCouldStillMatch = true;
                            }

                        }
                    }
				}
			}
		}
*/

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
				$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'complete';
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

							if (($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$attributeID] ?? null) === 'and') {
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

                /*
                 * Check the variant's flexContentsLI
                 */
                if ($blnThisVariantCouldStillMatch) {
                    if (is_array($arrCriteriaToFilterWith['flexContentsLI'])) {
                        foreach ($arrCriteriaToFilterWith['flexContentsLI'] as $str_flexContentLIKey => $arr_flexContentLIValues) {
                            /*
                             * The array returned by array_intersect() contains the requested flexContentLI values which
                             * are also included in the variant's flexContentLI values for the respective flexContentLIKey.
                             *
                             * In filterMode "or": If we get at least one flexContentLI value
                             * that matches, the variant is a match for this flexContentLI,
                             * otherwise it's not and we return false.
                             *
                             * In filterMode "and": If all the flexContentLI values match,
                             * the variant is a match for this flexContentLI, otherwise it's
                             * not and we return false.
                             */

                            /*
                             * It is unclear whether the variant actually has the relevant flexContentLI and if it has,
                             * the flexContentLI must not necessarily be an array. Therefore, we make sure that we always
                             * have a proper array for the filter check.
                             *
                             * Important: A variant could possibly fail to match the filter because it does not have
                             * a required flex content. However, if the product has the required flex content, we assume
                             * that the product's flex content can be considered as a "master value" that applies to
                             * all the product's variants as well. Therefore, we merge the product's flex content values
                             * into the variant's flex content values before performing the filter checks.
                             */
                            $arr_variantFlexContentLIValuesToCompareWithFilterRequirements = (array) ($arrVariantInfo['flex_contentsLanguageIndependent'][$str_flexContentLIKey] ?? []);
                            $arr_mergedProductAndVariantFlexContentLIValuesToCompareWithFilterRequirements = array_merge($arr_variantFlexContentLIValuesToCompareWithFilterRequirements, $arr_productFlexContentLIValuesToCompareWithFilterRequirements ?? []);

                            if (($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'][$str_flexContentLIKey] ?? null) === 'and') {
                                if (count(array_intersect($arr_flexContentLIValues, $arr_mergedProductAndVariantFlexContentLIValuesToCompareWithFilterRequirements)) !== count($arr_flexContentLIValues)) {
                                    $blnThisVariantCouldStillMatch = false;
                                    break;
                                }
                            } else {
                                if (!count(array_intersect($arr_flexContentLIValues, $arr_mergedProductAndVariantFlexContentLIValuesToCompareWithFilterRequirements))) {
                                    $blnThisVariantCouldStillMatch = false;
                                    break;
                                }
                            }
                        }
                    }
                }

                /*
                 * Check the variant's flexContentsLD
                 */
                if ($blnThisVariantCouldStillMatch) {
                    if (is_array($arrCriteriaToFilterWith['flexContentsLD']) && isset($arrCriteriaToFilterWith['flexContentsLD'][$str_currentLanguage])) {
                        foreach ($arrCriteriaToFilterWith['flexContentsLD'][$str_currentLanguage] as $str_flexContentLDKey => $arr_flexContentLDValues) {
                            /*
                             * The array returned by array_intersect() contains the requested flexContentLD values which
                             * are also included in the variant's flexContentLD values for the respective flexContentLDKey.
                             *
                             * In filterMode "or": If we get at least one flexContentLD value
                             * that matches, the variant is a match for this flexContentLD,
                             * otherwise it's not and we return false.
                             *
                             * In filterMode "and": If all the flexContentLD values match,
                             * the variant is a match for this flexContentLD, otherwise it's
                             * not and we return false.
                             */

                            /*
                             * It is unclear whether the variant actually has the relevant flexContentLD and if it has,
                             * the flexContentLD must not necessarily be an array. Therefore, we make sure that we always
                             * have a proper array for the filter check.
                             *
                             * Important: A variant could possibly fail to match the filter because it does not have
                             * a required flex content. However, if the product has the required flex content, we assume
                             * that the product's flex content can be considered as a "master value" that applies to
                             * all the product's variants as well. Therefore, we merge the product's flex content values
                             * into the variant's flex content values before performing the filter checks.
                             */
                            $arr_variantFlexContentLDValuesToCompareWithFilterRequirements = (array) ($arrVariantInfo['flex_contents_'.$str_currentLanguage][$str_flexContentLDKey] ?? []);
                            $arr_mergedProductAndVariantFlexContentLDValuesToCompareWithFilterRequirements = array_merge($arr_variantFlexContentLDValuesToCompareWithFilterRequirements, $arr_productFlexContentLDValuesToCompareWithFilterRequirements ?? []);

                            if (($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'][$str_flexContentLDKey] ?? null) === 'and') {
                                if (count(array_intersect($arr_flexContentLDValues, $arr_mergedProductAndVariantFlexContentLDValuesToCompareWithFilterRequirements)) !== count($arr_flexContentLDValues)) {
                                    $blnThisVariantCouldStillMatch = false;
                                    break;
                                }
                            } else {
                                if (!count(array_intersect($arr_flexContentLDValues, $arr_mergedProductAndVariantFlexContentLDValuesToCompareWithFilterRequirements))) {
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
						$_SESSION['lsShop']['filter']['matchedVariants'][$arrVariantInfo['id']] = true;
					}
					$blnPartialMatchForProductConfirmed = true;
					$numMatchingVariants++;
				} else {
					if ($blnStoreProductAndVariantMatchesInSession) {
						$_SESSION['lsShop']['filter']['matchedVariants'][$arrVariantInfo['id']] = false;
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
					$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'none';
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
						$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'partial';
					}
				} else {
					if ($blnStoreProductAndVariantMatchesInSession) {
						$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'complete';
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
				$_SESSION['lsShop']['filter']['matchedProducts'][$arrProductInfo['id']] = 'none';
			}
			return false;
		}
	}


    /*  Since the check for areas with FCLIMinMax and attributesMinMax is the same, they can be outsourced to a function
     *
     * */
    private static function checkIfProductMatchesFilter_ranges(&$blnWholeProductCouldStillMatch, &$blnVariantsCouldStillMatch
    , &$arrCriteriaToFilterWith, &$arrProductInfo
        , $criteriaKey
    )
    {
        /*
		 * Check the product's MinMax range
		 */
		if ($blnWholeProductCouldStillMatch) {
			if (is_array($arrCriteriaToFilterWith[$criteriaKey])) {
				foreach ($arrCriteriaToFilterWith[$criteriaKey] as $rangeKey => $rangeValues) {
					/*
					 * The array returned by array_intersect() contains the requested MinMax values which
					 * are also included in the product's MinMax values for the respective key.
					 *
					 */
                    /*
                     * Ignore the range filter if the high filter range is not higher than 0 and not as least as high as the low filter range.
                     * This way it is possible to skip the range filter part by setting both filter parameters to 0.
                     */
                    if ($arrCriteriaToFilterWith[$criteriaKey][$rangeKey]['high']
                        >= $arrCriteriaToFilterWith[$criteriaKey][$rangeKey]['low']
                        && $arrCriteriaToFilterWith[$criteriaKey][$rangeKey]['high'] > 0) {
                        if (!count($arrProductInfo['variants'])) {
                            /*
                             * If the product doesn't have variants, the product's Range has to be checked
                             */
                            if (!isset($arrProductInfo[$criteriaKey][$rangeKey]) ||
                                $arrProductInfo[$criteriaKey][$rangeKey]['high'] < $arrCriteriaToFilterWith[$criteriaKey][$rangeKey]['low']
                                || $arrProductInfo[$criteriaKey][$rangeKey]['low'] > $arrCriteriaToFilterWith[$criteriaKey][$rangeKey]['high']) {
                                $blnWholeProductCouldStillMatch = false;
                            }
                        } else {
                            /*
                             * If the product has variants, we have to use it's highest and lowest Rangevalues to see,
                             * if it is possible to match or filter out the whole product or if we have to
                             * check each variant separately.
                             */

                            if (isset($arrProductInfo[$criteriaKey][$rangeKey]) &&
                                $arrProductInfo[$criteriaKey][$rangeKey]['lowestValue'] > $arrCriteriaToFilterWith['flexContentsLIMinMax'][$rangeKey]['low']
                                && $arrProductInfo[$criteriaKey][$rangeKey]['highestValue'] < $arrCriteriaToFilterWith['flexContentsLIMinMax'][$rangeKey]['high']) {
                                /*
                                 * If the product's lowest value is higher than the low filter limit and the product's highest value is lower than the high filter limit,
                                 * this means that all product variants must be within the value range and, regarding the value, the product matches as a whole.
                                 */
                            } else if (!isset($arrProductInfo[$criteriaKey][$rangeKey])                                ||
                                $arrProductInfo[$criteriaKey][$rangeKey]['highestValue'] < $arrCriteriaToFilterWith['flexContentsLIMinMax'][$rangeKey]['low']
                                || $arrProductInfo[$criteriaKey][$rangeKey]['lowestValue'] > $arrCriteriaToFilterWith['flexContentsLIMinMax'][$rangeKey]['high']) {
                                /*
                                 * If even the product's highest value is lower than the low filter limit or it's lowest
                                 * value is higher than the high filter limit, this means that none of it's variants
                                 * has a value within the range and the product has to be filtered out as a whole.
                                 */
                                $blnWholeProductCouldStillMatch = false;
                                $blnVariantsCouldStillMatch = false;
                            } else {
                                /*
                                 * If none of the above is true, this means that there's definitely a variant that has a value outside the range
                                 * but there could be one ore more variants that have a value within.
                                 */
                                $blnWholeProductCouldStillMatch = false;
                                $blnVariantsCouldStillMatch = true;
                            }

                        }
                    }
				}
			}
		}
    }


	public static function resetMatchedProductsAndVariants() {
		$_SESSION['lsShop']['filter']['matchedProducts'] = array();
		$_SESSION['lsShop']['filter']['matchedVariants'] = array();
	}

	public static function adaptFilterCriteriaToCurrentFilterFormCriteria() {
		/*
		 * If the filter settings get altered, we have to reset the matchedProducts
		 * and matchedVariants because these cached filter results were related
		 * to the previous filter settings.
		 */
		ls_shop_filterHelper::resetMatchedProductsAndVariants();

		/*
		 * Get the attributes that are actually relevant for the current filtering process
		 *
		 */
		$_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'] = $_SESSION['lsShop']['filter']['criteria'];

		/*
		 * Walk through each attribute in the filter
		 */
		foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'] as $attributeID => $arrAttributeValues) {
			/*
			 * Check for each attributeID if it is part of the criteria to use in the filter form
			 */
			if (!isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID])) {
				/*
				 * and if it's not, remove the entire attribute
				 */
				unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]);
			} else {
				/*
				 * and if it is, we walk through each attributeValue and check if it exists in
				 * the criteria to use in the filter form.
				 */
				foreach ($arrAttributeValues as $k => $attributeValueID) {
					if (!in_array($attributeValueID, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID])) {
						// and if it's not...
						if (count($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]) > 1) {
							/*
							 * we have to remove the attributeValue from the filter
							 */
							unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID][$k]);
						} else {
							/*
							 * or we have to remove the entire attribute because this was it's only attributeValue in the filter
							 */
							unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributes'][$attributeID]);
						}
					}
				}
			}
		};

		/*
		 * Reset the producers that are no longer available in the filter form
		 */
		foreach ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['producers'] as $k => $producer) {
			if (!in_array($producer, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])) {
				unset ($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['producers'][$k]);
			}
		};

		/*
		 * Reset the price range if it is no longer in the filter form
		 */
		if (!$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['low'] && !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['price']['high']) {
			$_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['price'] = array(
				'low' => null,
				'high' => null
			);
		}

        foreach($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'] as $FCLIKey => $FCLIValues) {
            if (
                !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$FCLIKey]['low'] &&
                !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLIMinMax'][$FCLIKey]['high']
                ) {
                $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['flexContentsLIMinMax'][$FCLIKey]['low'] = array(
                    'low' => null,
				    'high' => null
                );
            }
        }

//TODO: diesen Block prüfen,
        foreach($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'] as $attributeID => $attributeValues) {
            if (
                !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$attributeID]['low'] &&
                !$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributesMinMax'][$attributeID]['high']
                ) {
                $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']['attributesMinMax'][$attributeID]['low'] = array(
                    'low' => null,
				    'high' => null
                );
            }
        }

	}

	public static function setCriteriaToUseInFilterForm($arrProductsComplete = array()) {

        global $objPage;
        $str_currentLanguage = ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();

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
			foreach ($arrProduct['attributesMinMax'] as $attributeID => $attributeValues) {
                if (!isset($attributeValues['lowestValue'])) {
                    ls_shop_filterHelper::addAttributeMinMaxToCriteriaUsedInFilterForm($attributeID, $attributeValues['low']);
                    ls_shop_filterHelper::addAttributeMinMaxToCriteriaUsedInFilterForm($attributeID, $attributeValues['high']);
                } else {
                    ls_shop_filterHelper::addAttributeMinMaxToCriteriaUsedInFilterForm($attributeID, $attributeValues['lowestValue']);
                    ls_shop_filterHelper::addAttributeMinMaxToCriteriaUsedInFilterForm($attributeID, $attributeValues['highestValue']);
                }
			}
			foreach ($arrProduct['flex_contentsLanguageIndependent'] as $str_flexContentLIKey => $str_flexContentLIValue) {
				ls_shop_filterHelper::addFlexContentLIValueToCriteriaUsedInFilterForm($str_flexContentLIKey, $str_flexContentLIValue);
			}
            foreach ($arrProduct['flex_contentsLIMinMax'] as $str_flexContentLIMinMaxKey => $str_flexContentLIMinMaxValue) {
                if (!isset($str_flexContentLIMinMaxValue['lowestValue'])) {
                    ls_shop_filterHelper::addFlexContentLIMinMaxValueToCriteriaUsedInFilterForm($str_flexContentLIMinMaxKey, $str_flexContentLIMinMaxValue['low']);
                    ls_shop_filterHelper::addFlexContentLIMinMaxValueToCriteriaUsedInFilterForm($str_flexContentLIMinMaxKey, $str_flexContentLIMinMaxValue['high']);
                } else {
                    ls_shop_filterHelper::addFlexContentLIMinMaxValueToCriteriaUsedInFilterForm($str_flexContentLIMinMaxKey, $str_flexContentLIMinMaxValue['lowestValue']);
                    ls_shop_filterHelper::addFlexContentLIMinMaxValueToCriteriaUsedInFilterForm($str_flexContentLIMinMaxKey, $str_flexContentLIMinMaxValue['highestValue']);
                }
            }
            foreach ($arrProduct['flex_contents_'.$str_currentLanguage] as $str_flexContentLDKey => $str_flexContentLDValue) {
				ls_shop_filterHelper::addFlexContentLDValueToCriteriaUsedInFilterForm($str_flexContentLDKey, $str_flexContentLDValue);
			}
			foreach ($arrProduct['variants'] as $arrVariant) {
				foreach ($arrVariant['attributeAndValueIDs'] as $intAttributeID => $arrValueIDs) {
					ls_shop_filterHelper::addAttributeValueToCriteriaUsedInFilterForm($intAttributeID, $arrValueIDs);
				}
                foreach ($arrVariant['attributesMinMax'] as $attributeID => $attributeValues) {
                    ls_shop_filterHelper::addAttributeMinMaxToCriteriaUsedInFilterForm($attributeID, $attributeValues['low']);
                    ls_shop_filterHelper::addAttributeMinMaxToCriteriaUsedInFilterForm($attributeID, $attributeValues['high']);
                }
                foreach ($arrVariant['flex_contentsLanguageIndependent'] as $str_flexContentLIKey => $str_flexContentLIValue) {
                    ls_shop_filterHelper::addFlexContentLIValueToCriteriaUsedInFilterForm($str_flexContentLIKey, $str_flexContentLIValue);
                }
                foreach ($arrVariant['flex_contentsLIMinMax'] as $str_flexContentLIMinMaxKey => $str_flexContentLIMinMaxValue) {
                    ls_shop_filterHelper::addFlexContentLIMinMaxValueToCriteriaUsedInFilterForm($str_flexContentLIMinMaxKey, $str_flexContentLIMinMaxValue['low']);
                    ls_shop_filterHelper::addFlexContentLIMinMaxValueToCriteriaUsedInFilterForm($str_flexContentLIMinMaxKey, $str_flexContentLIMinMaxValue['high']);
                }
                foreach ($arrVariant['flex_contents_'.$str_currentLanguage] as $str_flexContentLDKey => $str_flexContentLDValue) {
                    ls_shop_filterHelper::addFlexContentLDValueToCriteriaUsedInFilterForm($str_flexContentLDKey, $str_flexContentLDValue);
                }
			}
		}

		/*
		 * #######################################
		 * Remove filter criteria from the filter form if they don't make any sense, e.g. attributes, if there
		 * is only one possible value and producers if there is only one possible producer
		 */
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $attributeID => $arrAttributeValueIDs) {
			if (count($arrAttributeValueIDs) < 2) {
				unset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$attributeID]);
			}
		}

		if (count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers']) < 2) {
			$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'] = array();
		}
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
        self::handleFilterModeSettingsForAttributes();
        self::handleFilterModeSettingsForFlexContentsLI();
        self::handleFilterModeSettingsForFlexContentsLD();
    }

	public static function handleFilterModeSettingsForAttributes() {
		if (!isset($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'])) {
			$_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'] = array();
		}

		$arr_filterModeInput = \Input::post('filterModeForAttribute');

		if (is_array($arr_filterModeInput)) {
			foreach ($arr_filterModeInput as $var_attribute => $str_filterMode) {
				$_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'][$var_attribute] = $str_filterMode;
			}
		}
	}

	public static function handleFilterModeSettingsForFlexContentsLI() {
        if (!isset($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'])) {
            $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'] = array();
        }

        $arr_filterModeInput = \Input::post('filterModeForFlexContentLI');

        if (is_array($arr_filterModeInput)) {
            foreach ($arr_filterModeInput as $str_flexContentLIKey => $str_filterMode) {
                $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'][$str_flexContentLIKey] = $str_filterMode;
            }
        }
	}

	public static function handleFilterModeSettingsForFlexContentsLD() {
        if (!isset($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'])) {
            $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'] = array();
        }

        $arr_filterModeInput = \Input::post('filterModeForFlexContentLD');

        if (is_array($arr_filterModeInput)) {
            foreach ($arr_filterModeInput as $str_flexContentLDKey => $str_filterMode) {
                $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'][$str_flexContentLDKey] = $str_filterMode;
            }
        }
	}

    public static function handleFilterModeSettingsForFlexContentsLIMinMax() {
        if (!isset($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLIMinMax'])) {
            $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLIMinMax'] = array();
        }

        $arr_filterModeInput = \Input::post('filterModeForFlexContentLIMinMax');

        if (is_array($arr_filterModeInput)) {
            foreach ($arr_filterModeInput as $str_flexContentLIMinMaxKey => $str_filterMode) {
                $_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLIMinMax'][$str_flexContentLIMinMaxKey] = $str_filterMode;
            }
        }
	}

	public static function setFilter($what = '', $varValue) {
        global $objPage;
        $str_currentLanguage = ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();

		if (!$what) {
			return;
		}

		/*
		 * If the filter settings get altered, we have to reset the matchedProducts
		 * and matchedVariants because these cached filter results were related
		 * to the previous filter settings.
		 */
		ls_shop_filterHelper::resetMatchedProductsAndVariants();

		switch ($what) {
			case 'attributes':
				if (!$varValue['value']) {
					unset($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']]);
				} else {
					$varValue['value'] = is_array($varValue['value']) ? $varValue['value'] : array($varValue['value']);

					/*
					 * Attribute values that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					if (isset($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']]) && is_array($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']])) {
						foreach ($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']] as $attributeValueIDCurrentlyInFilter) {
							if (
								!in_array($attributeValueIDCurrentlyInFilter, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'][$varValue['attributeID']])
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
						unset($_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']]);
						break;
					}

					$_SESSION['lsShop']['filter']['criteria']['attributes'][$varValue['attributeID']] = $varValue['value'];
				}
				break;

			case 'flexContentsLI':
				if (!$varValue['value']) {
					unset($_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$varValue['flexContentLIKey']]);
				} else {
					$varValue['value'] = is_array($varValue['value']) ? $varValue['value'] : array($varValue['value']);

					/*
					 * Flex content values that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					if (isset($_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$varValue['flexContentLIKey']]) && is_array($_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$varValue['flexContentLIKey']])) {
						foreach ($_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$varValue['flexContentLIKey']] as $flexContentLIValueCurrentlyInFilter) {
							if (
								!in_array($flexContentLIValueCurrentlyInFilter, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'][$varValue['flexContentLIKey']])
								&&	!in_array($flexContentLIValueCurrentlyInFilter, $varValue['value'])
							) {
								$varValue['value'][] = $flexContentLIValueCurrentlyInFilter;
							}
						}
					}

					foreach($varValue['value'] as $k => $v) {
						if (!$v || $v == '--reset--' || $v == '--checkall--') {
							unset($varValue['value'][$k]);
						}
					}

					if (!count($varValue['value'])) {
						unset($_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$varValue['flexContentLIKey']]);
						break;
					}

					$_SESSION['lsShop']['filter']['criteria']['flexContentsLI'][$varValue['flexContentLIKey']] = $varValue['value'];
				}
				break;

            case 'flexContentsLD':
				if (!$varValue['value']) {
					unset($_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$str_currentLanguage][$varValue['flexContentLDKey']]);
				} else {
					$varValue['value'] = is_array($varValue['value']) ? $varValue['value'] : array($varValue['value']);

					/*
					 * Flex content values that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					if (isset($_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$str_currentLanguage][$varValue['flexContentLDKey']]) && is_array($_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$varValue['flexContentLDKey']])) {
						foreach ($_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$str_currentLanguage][$varValue['flexContentLDKey']] as $flexContentLDValueCurrentlyInFilter) {
							if (
								!in_array($flexContentLDValueCurrentlyInFilter, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage][$varValue['flexContentLDKey']])
								&&	!in_array($flexContentLDValueCurrentlyInFilter, $varValue['value'])
							) {
								$varValue['value'][] = $flexContentLDValueCurrentlyInFilter;
							}
						}
					}

					foreach($varValue['value'] as $k => $v) {
						if (!$v || $v == '--reset--' || $v == '--checkall--') {
							unset($varValue['value'][$k]);
						}
					}

					if (!count($varValue['value'])) {
						unset($_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$str_currentLanguage][$varValue['flexContentLDKey']]);
						break;
					}

					$_SESSION['lsShop']['filter']['criteria']['flexContentsLD'][$str_currentLanguage][$varValue['flexContentLDKey']] = $varValue['value'];
				}
				break;

			case 'flexContentsLIMinMax':
                $_SESSION['lsShop']['filter']['criteria']['flexContentsLIMinMax'][$varValue['flexContentLIKey']]['low'] = $varValue['low'];
				$_SESSION['lsShop']['filter']['criteria']['flexContentsLIMinMax'][$varValue['flexContentLIKey']]['high'] = $varValue['high'];

				break;

			case 'price':
				$_SESSION['lsShop']['filter']['criteria']['price']['low'] = $varValue['low'];
				$_SESSION['lsShop']['filter']['criteria']['price']['high'] = $varValue['high'];
				break;

			case 'producers':
				if (!$varValue) {
					$_SESSION['lsShop']['filter']['criteria']['producers'] = array();
				} else {
					$varValue = is_array($varValue) ? $varValue : array($varValue);

					/*
					 * Producers that are currently in the filter criteria but that have not been sent with the filter form
					 * because they weren't even part of the filter form should be added. The reason is, that we don't want filter criteria
					 * to be reset by submitting a filter form if the user didn't intentionally uncheck them.
					 */
					foreach ($_SESSION['lsShop']['filter']['criteria']['producers'] as $producerCurrentlyInFilter) {
						if (
							!in_array($producerCurrentlyInFilter, $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'])
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

					$_SESSION['lsShop']['filter']['criteria']['producers'] = $varValue;
				}
				break;
		}
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
        global $objPage;
        $str_currentLanguage = ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();

		$_SESSION['lsShop']['filter']['matchEstimates']['attributeValues'] = array();
		$_SESSION['lsShop']['filter']['matchEstimates']['flexContentLIValues'] = array();
        $_SESSION['lsShop']['filter']['matchEstimates']['flexContentLDValues'] = array();
		$_SESSION['lsShop']['filter']['matchEstimates']['producers'] = array();
		if (!isset($GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates']) || !$GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates']) {
			return;
		}

		$_SESSION['lsShop']['filter']['noMatchEstimatesDetermined'] = false;
		if (
			isset($GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'])
			&&	$GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'] > 0
			&& count($arrProductsResultSet) > $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts']
		) {
			$_SESSION['lsShop']['filter']['noMatchEstimatesDetermined'] = true;
			return;
		}

		$numFilterValuesToDetermineEstimatesFor = 0;
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $arrAttributeValues) {
			$numFilterValuesToDetermineEstimatesFor += count($arrAttributeValues);
		}
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'] as $arrFlexContentLIValues) {
			$numFilterValuesToDetermineEstimatesFor += count($arrFlexContentLIValues);
		}
        if (isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage])) {
            foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage] as $arrFlexContentLDValues) {
                $numFilterValuesToDetermineEstimatesFor += count($arrFlexContentLDValues);
            }
        }
		$numFilterValuesToDetermineEstimatesFor += count($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers']);

		if (
			isset($GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'])
			&&	$GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'] > 0
			&& $numFilterValuesToDetermineEstimatesFor > $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues']
		) {
			$_SESSION['lsShop']['filter']['noMatchEstimatesDetermined'] = true;
			return;
		}

		/*
		 * Getting the estimates for the attributes
		 */
		/*
		 * Walk through all attributes used in the filter form and create an array with filter criteria that does not
		 * include the current attribute
		 */
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['attributes'] as $attributeID => $arrAttributeValues) {
			$tmpCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];

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
				$_SESSION['lsShop']['filter']['matchEstimates']['attributeValues'][$attributeValueID] = array(
					'products' => $arrFilterMatches['numMatching'],
					'variants' => $arrFilterMatches['numVariantsMatching']
				);
			}
		}

		/*
		 * Getting the estimates for the flexContentsLI
		 */
		/*
		 * Walk through all flexContentsLI used in the filter form and create an array with filter criteria that does not
		 * include the current flexContentLI
		 */
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLI'] as $flexContentLIKey => $arrFlexContentLIValues) {
			$tmpCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];

			/*
			 * Remove the current flexContentLI from the criteria array
			 */
			if (isset($tmpCriteriaToFilterWith['flexContentsLI'][$flexContentLIKey])) {
				unset($tmpCriteriaToFilterWith['flexContentsLI'][$flexContentLIKey]);
			}

			/*
			 * Walk through all the flexContentLI values and create a temporary filter criteria array in which the current
			 * flexContentLI value is added
			 */
			foreach ($arrFlexContentLIValues as $flexContentLIValue) {
				$tmpCriteriaToFilterWithPlusCurrentValue = $tmpCriteriaToFilterWith;
				$tmpCriteriaToFilterWithPlusCurrentValue['flexContentsLI'][$flexContentLIKey] = array($flexContentLIValue);

				/*
				 * Filter the previously created result set using only the current attribute value
				 */
				$arrFilterMatches = ls_shop_filterHelper::getMatchesInProductResultSet($arrProductsResultSet, $tmpCriteriaToFilterWithPlusCurrentValue, false);

				/*
				 * Storing the number of matches
				 */
				$_SESSION['lsShop']['filter']['matchEstimates']['flexContentLIValues'][$flexContentLIValue] = array(
					'products' => $arrFilterMatches['numMatching'],
					'variants' => $arrFilterMatches['numVariantsMatching']
				);
			}
		}

		/*
		 * Getting the estimates for the flexContentsLD
		 */
		/*
		 * Walk through all flexContentsLD used in the filter form and create an array with filter criteria that does not
		 * include the current flexContentLD
		 */
        if (isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage])) {
            foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['flexContentsLD'][$str_currentLanguage] as $flexContentLDKey => $arrFlexContentLDValues) {
                $tmpCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];

                /*
                 * Remove the current flexContentLD from the criteria array
                 */
                if (isset($tmpCriteriaToFilterWith['flexContentsLD'][$str_currentLanguage][$flexContentLDKey])) {
                    unset($tmpCriteriaToFilterWith['flexContentsLD'][$str_currentLanguage][$flexContentLDKey]);
                }

                /*
                 * Walk through all the flexContentLD values and create a temporary filter criteria array in which the current
                 * flexContentLD value is added
                 */
                foreach ($arrFlexContentLDValues as $flexContentLDValue) {
                    $tmpCriteriaToFilterWithPlusCurrentValue = $tmpCriteriaToFilterWith;
                    $tmpCriteriaToFilterWithPlusCurrentValue['flexContentsLD'][$str_currentLanguage][$flexContentLDKey] = array($flexContentLDValue);

                    /*
                     * Filter the previously created result set using only the current attribute value
                     */
                    $arrFilterMatches = ls_shop_filterHelper::getMatchesInProductResultSet($arrProductsResultSet, $tmpCriteriaToFilterWithPlusCurrentValue, false);

                    /*
                     * Storing the number of matches
                     */
                    $_SESSION['lsShop']['filter']['matchEstimates']['flexContentLDValues'][$flexContentLDValue] = array(
                        'products' => $arrFilterMatches['numMatching'],
                        'variants' => $arrFilterMatches['numVariantsMatching']
                    );
                }
            }
        }
		/*
		 * Getting the estimates for the producers
		 */
		$tmpCriteriaToFilterWith = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'];

		/*
		 * Remove the producers from the criteria array
		 */
		$tmpCriteriaToFilterWith['producers'] = array();

		/*
		 * Walk through all the producers and create a temporary filter criteria array in which the current
		 * producer is added
		 */
		foreach ($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']['producers'] as $producerValue) {
			$tmpCriteriaToFilterWithPlusCurrentValue = $tmpCriteriaToFilterWith;
			$tmpCriteriaToFilterWithPlusCurrentValue['producers'] = array($producerValue);

			/*
			 * Filter the previously created result set using only the current producer value
			 */
			$arrFilterMatches = ls_shop_filterHelper::getMatchesInProductResultSet($arrProductsResultSet, $tmpCriteriaToFilterWithPlusCurrentValue, false);

			/*
			 * Storing the number of matches
			 */
			$_SESSION['lsShop']['filter']['matchEstimates']['producers'][md5($producerValue)] = array(
				'products' => $arrFilterMatches['numMatching'],
				'variants' => $arrFilterMatches['numVariantsMatching']
			);
		}
	}
}