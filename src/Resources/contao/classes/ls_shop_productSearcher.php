<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\createMultidimensionalArray;

class ls_shop_productSearcher
{
    protected $bln_useGroupPrices = true;
    protected $bln_ignoreGroupRestrictions = true;
    protected $arr_groupSettingsForUser = null;

    protected $arrSearchCriteria = array('title' => '*', 'published' => '1');
    protected $intNumPerPage = 0;
    protected $intCurrentPage = 1;
    protected $blnEmptyFieldMatchesPerDefault = false;
    protected $fixedSorting = array();
    protected $arrRequestFields = array('id');
    protected $blnNotAllProductsMatch = false;
    protected $numProductsNotMatching = 0;
    protected $numProductsBeforeFilter = 0;
    protected $arrLimit = array(
        'rows' => 0,
        'offset' => 0
    );

    protected $blnEnoughProductsOrVariantsToFilterAvailable = false;
    protected $blnUseFilter = false;

    /*
     * Das Sorting-Array enthält die Felder, nach denen sortiert werden soll in der Reihenfolge, in der
     * sie in der SQL-Abfrage angewendet werden sollen. Jedes Feld wird als Array mit der Feldbezeichnung
     * und der gewünschten Sortierrichtung angegeben.
     */
    protected $arrSorting = array(
        0 => array('field' => 'title', 'direction' => 'ASC')
    );

    protected $arrProductResultsComplete = array();
    protected $arrProductResultsCurrentPage = null;

    protected $arrCache = null;
    protected $strCacheKey = null;
    protected $blnCacheCanBeUsed = null;
    protected $maxNumParallelCaches = 10;
    protected $cacheLifetimeSec = 60;
    protected $searchLanguage = null;
    protected $blnDoNotSpecialSort = false;
    protected $truncateResultsIfMoreThan = 0;
    protected $cancelSearchIfMoreThanTruncateLimit = false;
    protected $str_productListID = null;

    public function __construct($blnUseFilter = false, $str_productListID = null) {
        $this->getSearchLanguage();

        $this->arr_groupSettingsForUser = ls_shop_generalHelper::getGroupSettings4User();

        $this->blnUseFilter = $blnUseFilter ? true : false;
        $this->str_productListID = $str_productListID ? $str_productListID : null;

        $this->bln_useGroupPrices = isset($GLOBALS['TL_CONFIG']['ls_shop_considerGroupPricesInFilterAndSorting']) && $GLOBALS['TL_CONFIG']['ls_shop_considerGroupPricesInFilterAndSorting'];
        $this->bln_ignoreGroupRestrictions = isset($GLOBALS['TL_CONFIG']['ls_shop_ignoreGroupRestrictionsInSearch']) && $GLOBALS['TL_CONFIG']['ls_shop_ignoreGroupRestrictionsInSearch'];

        $this->maxNumParallelCaches = isset($GLOBALS['TL_CONFIG']['ls_shop_maxNumParallelSearchCaches']) ? $GLOBALS['TL_CONFIG']['ls_shop_maxNumParallelSearchCaches'] : 20;
        $this->cacheLifetimeSec = isset($GLOBALS['TL_CONFIG']['ls_shop_searchCacheLifetimeSec']) ? $GLOBALS['TL_CONFIG']['ls_shop_searchCacheLifetimeSec'] : 300;

        if ($this->blnUseFilter) {
            /*
             * Process potentially sent filter settings right here to make
             * sure that no search will actually be executed if there's going
             * to be a reload after the processing of the sent filter settings anyway.
             */
            ls_shop_filterController::getInstance()->processSentFilterSettings();
        }
    }

    public function __destruct() {
        $this->setCache();
    }

    protected function getCache() {
        $potentialCache = ($_SESSION['lsShop']['caches']['ls_shop_productSearcher'][$this->strCacheKey] ?? null) ?: null;
        $this->arrCache = time() - $potentialCache['tstamp'] < $this->cacheLifetimeSec ? $potentialCache : null;
    }

    protected function setCache() {
        if (!$this->strCacheKey) {
            return;
        }

        if (!$this->checkIfCacheCanBeUsed()) {
            /*
             * Only set the cache if a cache couldn't be used this time so that we have
             * a new result to cache now
             */
            $_SESSION['lsShop']['caches']['ls_shop_productSearcher'][$this->strCacheKey] = array(
                'tstamp' => time(),
                'productResultsComplete' => $this->productResultsComplete,
                'numResultsComplete' => $this->numResultsComplete,
                'blnNotAllProductsMatch' => $this->blnNotAllProductsMatch,
                'numProductsNotMatching' => $this->numProductsNotMatching,
                'numProductsBeforeFilter' => $this->numProductsBeforeFilter,
                'blnUseFilter' => $this->blnUseFilter,
                'criteriaToUseInFilterFormHasBeenSet' => isset($GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet']) && $GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'],
                'arrCriteriaToUseInFilterForm' => $this->blnUseFilter && isset($_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm']) ? $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm'] : null,
                'criteriaToActuallyFilterWith' => $this->blnUseFilter && isset($_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith']) ? $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'] : null,
                'matchedProducts' => $this->blnUseFilter && isset($_SESSION['lsShop']['filter']['matchedProducts']) ? $_SESSION['lsShop']['filter']['matchedProducts'] : null,
                'matchedVariants' => $this->blnUseFilter && isset($_SESSION['lsShop']['filter']['matchedVariants']) ? $_SESSION['lsShop']['filter']['matchedVariants'] : null,
                'matchEstimates' => $this->blnUseFilter && isset($_SESSION['lsShop']['filter']['matchEstimates']) ? $_SESSION['lsShop']['filter']['matchEstimates'] : null
            );
        } else {
            /*
             * If the cache has been used this time we don't set it completely because it can not have
             * changed but we have to update the timestamp to increase it's lifetime
             */
            if (isset($_SESSION['lsShop']['caches']['ls_shop_productSearcher'][$this->strCacheKey])) {
                $_SESSION['lsShop']['caches']['ls_shop_productSearcher'][$this->strCacheKey]['tstamp'] = time();
            }
        }

        /*
         * Determine whether there are caches that need to be removed
         */
        // Remove the oldest cache which automatically must be the one on first position in the array
        if (count($_SESSION['lsShop']['caches']['ls_shop_productSearcher']) > $this->maxNumParallelCaches) {
            reset($_SESSION['lsShop']['caches']['ls_shop_productSearcher']);
            unset($_SESSION['lsShop']['caches']['ls_shop_productSearcher'][key($_SESSION['lsShop']['caches']['ls_shop_productSearcher'])]);
        }

        if ($this->cacheLifetimeSec > 0) {
            foreach($_SESSION['lsShop']['caches']['ls_shop_productSearcher'] as $k => $v) {
                if ($v['tstamp'] < time() - $this->cacheLifetimeSec) {
                    unset($_SESSION['lsShop']['caches']['ls_shop_productSearcher'][$k]);
                }
            }
        }
    }

    protected function setCurrentCacheKey() {
        $arrSettings = array(
            'emptyFieldMatchesPerDefault' => $this->blnEmptyFieldMatchesPerDefault,
            'sorting' => $this->arrSorting,
            'fixedSorting' => $this->fixedSorting,
            'arrRequestFields' => $this->arrRequestFields,
            'arrSearchCriteria' => $this->arrSearchCriteria,
            'arrLimit' => $this->arrLimit,
            'filterCriteria' => $this->blnUseFilter ? $_SESSION['lsShop']['filter']['criteria'] : null,
            'filterModeSettings' => $this->blnUseFilter ? ($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'] ?? null) : null,
            'language' => $this->searchLanguage,
            'outputPriceType' => ls_shop_generalHelper::getOutputPriceType(),
            'filterFieldForPriceExists' => ls_shop_filterHelper::filterFieldForPriceExists(),
            'checkVATID' => ls_shop_generalHelper::checkVATID(),
            'customerCountry' => ls_shop_generalHelper::getCustomerCountry(),
            'lastBackendDataChange' => isset($GLOBALS['TL_CONFIG']['ls_shop_lastBackendDataChange']) ? $GLOBALS['TL_CONFIG']['ls_shop_lastBackendDataChange'] : 0,
            'lastResetTimestamp' => $_SESSION['lsShop']['filter']['lastResetTimestamp'] ?? null,
            'customerGroupId' => $this->arr_groupSettingsForUser['id']
        );

        $this->strCacheKey = md5(serialize($arrSettings));
    }

    /**
     * Check whether there's an existing cache that can be used
     */
    protected function checkIfCacheCanBeUsed() {
        if ($this->blnCacheCanBeUsed === null) {
            $this->getCache();
            $this->blnCacheCanBeUsed = $this->arrCache !== null;
        }

        if (isset($GLOBALS['MERCONIS_HOOKS']['checkIfCacheCanBeUsed']) && is_array($GLOBALS['MERCONIS_HOOKS']['checkIfCacheCanBeUsed'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['checkIfCacheCanBeUsed'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $this->blnCacheCanBeUsed = $objMccb->{$mccb[1]}($this->str_productListID, $this->blnCacheCanBeUsed);
            }
        }

        return $this->blnCacheCanBeUsed;
    }

    public function __get($what) {
        switch ($what) {
            case 'numPagesTotal':
                return $this->intNumPerPage > 0 ? ceil($this->numResultsComplete / $this->intNumPerPage) : 1;
                break;

            case 'productResultsComplete':
                return $this->checkIfCacheCanBeUsed() ? $this->arrCache['productResultsComplete'] : $this->arrProductResultsComplete;
                break;

            case 'productResultsCurrentPage':
                $this->getProductResultsCurrentPage();
                return $this->arrProductResultsCurrentPage;
                break;

            case 'numResultsComplete':
                return $this->checkIfCacheCanBeUsed() ? $this->arrCache['numResultsComplete'] : count($this->arrProductResultsComplete);
                break;

            case 'blnNotAllProductsMatch':
                return $this->checkIfCacheCanBeUsed() ? $this->arrCache['blnNotAllProductsMatch'] : $this->blnNotAllProductsMatch;
                break;

            case 'numProductsNotMatching':
                return $this->checkIfCacheCanBeUsed() ? $this->arrCache['numProductsNotMatching'] : $this->numProductsNotMatching;
                break;

            case 'numProductsBeforeFilter':
                return $this->checkIfCacheCanBeUsed() ? $this->arrCache['numProductsBeforeFilter'] : $this->numProductsBeforeFilter;
                break;

            case 'arrSplitSorting':
                $arrSplitSorting = array(
                    'dbFieldNames' => array(),
                    'db' => array(),
                    'phpFieldNames' => array(),
                    'php' => array()
                );

                if (is_array($this->arrSorting)) {
                    foreach ($this->arrSorting as $sortingField) {
                        if (
                            $sortingField['field'] == 'priority'
                            ||	$sortingField['field'] == 'title'
                            ||	$sortingField['field'] == 'lsShopProductPrice'
                            ||	$sortingField['field'] == 'lsShopProductCode'
                            ||	$sortingField['field'] == 'sorting'
                            ||	$sortingField['field'] == 'lsShopProductProducer'
                            ||	$sortingField['field'] == 'lsShopProductWeight'
                            ||	strpos($sortingField['field'], 'flex_contents') !== false
                            ||	strpos($sortingField['field'], 'lsShopProductAttributesValues') !== false
                        ) {
                            $arrTmpFieldParts = explode('=', $sortingField['field']);
                            $sortingField['field'] = $arrTmpFieldParts[0];
                            if (isset($arrTmpFieldParts[1]) && $arrTmpFieldParts[1]) {
                                $sortingField['variableFieldKey'] = $arrTmpFieldParts[1];
                            }
                            $arrSplitSorting['php'][] = $sortingField;
                            $arrSplitSorting['phpFieldNames'][] = $sortingField['field'];
                        } else {
                            $arrSplitSorting['db'][] = $sortingField;
                            $arrSplitSorting['dbFieldNames'][] = $sortingField['field'];
                        }
                    }
                }

                return $arrSplitSorting;
                break;
        }

        return null;
    }

    public function __set($key, $value) {
        switch ($key) {
            case 'numPerPage':
                $this->intNumPerPage = $value;
                break;

            case 'currentPage':
                $this->intCurrentPage = $value;
                break;

            case 'sorting':
                if (is_array($value) && count($value)) {
                    $this->arrSorting = $value;
                }
                break;

            case 'emptyFieldMatchesPerDefault':
                $this->blnEmptyFieldMatchesPerDefault = $value;
                break;

            case 'fixedSorting':
                $this->fixedSorting = $value;
                break;

            case 'arrRequestFields':
                $this->arrRequestFields = $value;
                break;

            case 'limitRows':
                $this->arrLimit['rows'] = $value;
                break;

            case 'limitOffset':
                $this->arrLimit['offset'] = $value;
                break;

            case 'doNotSpecialSort':
                $this->blnDoNotSpecialSort = $value ? true : false;
                break;

            case 'truncateResultsIfMoreThan':
                $this->truncateResultsIfMoreThan = $value;
                break;

            case 'cancelSearchIfMoreThanTruncateLimit':
                $this->cancelSearchIfMoreThanTruncateLimit = $value;
                break;
        }
    }

    public function setSearchCriterion($fieldName = '', $criteria = '') {
        if (!$fieldName) {
            return;
        }
        $this->arrSearchCriteria[$fieldName] = $criteria;
    }

    public function setSearchCriteria($arrSearchCriteria = array()) {
        if (!is_array($arrSearchCriteria) || !count($arrSearchCriteria)) {
            return;
        }
        $this->arrSearchCriteria = $arrSearchCriteria;
    }

    public function search() {
        $this->ls_performSearch();

        /*
         * This hook is only meant to be called in a product list context and therefore
         * we check for a given productListID. If the cache can be used we don't call
         * the hook since it wouldn't have any effect because the cached result would
         * always be returned.
         */
        if ($this->str_productListID && !$this->checkIfCacheCanBeUsed()) {
            if (isset($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutputBeforePagination']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutputBeforePagination'])) {
                foreach ($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutputBeforePagination'] as $mccb) {
                    $objMccb = \System::importStatic($mccb[0]);
                    $this->arrProductResultsComplete = $objMccb->{$mccb[1]}($this->str_productListID, $this->arrProductResultsComplete);
                }
            }
        }
    }

    protected function blnUsePriority() {
        foreach ($this->arrSorting as $sortingField) {
            if ($sortingField['field'] == 'priority') {
                return true;
            }
        }
        return false;
    }

    protected function getSearchLanguage() {
        // Use the fallback language for the search by default
        $this->searchLanguage = ls_shop_languageHelper::getFallbackLanguage();

        // Use the language of the current page if we have a fronted call
        if (TL_MODE == 'FE') {
            /** @var \PageModel $objPage */
            global $objPage;
            $this->searchLanguage = $objPage->language;
        }
    }

    protected function checkIfLanguageFieldsExist($searchLanguage) {
        /*
         * Check whether the language fields for the requested language
         * exist in the table `tl_ls_shop_product`. To do so the presence
         * of the title field with the respective language suffix is checked.
         * If it exists, all fields for this language must exist because the
         * routine that created this language specific title field would have
         * created all the other language specific fields for this languge
         * as well.
         */
        return \Database::getInstance()->fieldExists('title_'.$searchLanguage, 'tl_ls_shop_product');
    }

    protected function getQualifiedFieldName($fieldName) {
        $searchLanguage = $this->searchLanguage;

        /*
         * If fields for the requested language don't exist, no specific search language should
         * be used which means that the non language specific main field would be used for the search.
         */
        if (!$this->checkIfLanguageFieldsExist($searchLanguage)) {
            $searchLanguage = null;
        }


        switch($fieldName) {
            case 'title':
            case 'keywords':
            case 'shortDescription':
            case 'description':
            case 'lsShopProductQuantityUnit':
            case 'lsShopProductMengenvergleichUnit':
            case 'flex_contents':
                return "`tl_ls_shop_product`.`".$fieldName.($searchLanguage ? "_".$searchLanguage : "")."`";
                break;

            case 'priority':
                return "`".$fieldName."`";
                break;

            case 'attributeID':
            case 'attributeValueID':
                return "`tl_ls_shop_attribute_allocation`.`".$fieldName."`";

            default:
                return "`tl_ls_shop_product`.`".$fieldName."`";
                break;
        }
    }

    protected function checkIfValidCriteriaGiven() {
        $blnValid = true;

        /*
         * Check we have a "fulltext only" search with an empty fulltext criteria in which case the criteria
         * is invalid and the search should not be performed.
         */
        if (count($this->arrSearchCriteria) == 2 && isset($this->arrSearchCriteria['published']) && isset($this->arrSearchCriteria['fulltext']) && !$this->arrSearchCriteria['fulltext']) {
            $blnValid = false;
        }

        return $blnValid;
    }

    protected function ls_performSearch() {
        /*
         * Set the current cache key because if ls_performSearch() is being executed, all
         * settings affecting the results have been set completely
         */
        $this->setCurrentCacheKey();

        /*
         * Don't perform a new search if the cached result of the last search can be used
         */
        if ($this->checkIfCacheCanBeUsed()) {
            if ($this->blnUseFilter) {
                /*
                 * Set this flag because the filter needs it to decide whether or not to display the filter form
                 */
                if ($this->arrCache['criteriaToUseInFilterFormHasBeenSet']) {
                    $GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'] = true;
                }

                /*
                 * If we use a cached search result, we set some (most) filter values to the cached values
                 */
                if ($this->arrCache['arrCriteriaToUseInFilterForm']) {
                    $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm'] = $this->arrCache['arrCriteriaToUseInFilterForm'];
                }

                if ($this->arrCache['criteriaToActuallyFilterWith']) {
                    $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'] = $this->arrCache['criteriaToActuallyFilterWith'];
                }

                if ($this->arrCache['matchedProducts']) {
                    $_SESSION['lsShop']['filter']['matchedProducts'] = $this->arrCache['matchedProducts'];
                }

                if ($this->arrCache['matchedVariants']) {
                    $_SESSION['lsShop']['filter']['matchedVariants'] = $this->arrCache['matchedVariants'];
                }

                if ($this->arrCache['matchEstimates']) {
                    $_SESSION['lsShop']['filter']['matchEstimates'] = $this->arrCache['matchEstimates'];
                }
            }
            return;
        }


        if (!$this->checkIfValidCriteriaGiven()) {
            $this->arrProductResultsComplete = array();
            return;
        }

        /* ############################
         * Erstellung der Where-Bedingungen
         */
        if (!$this->bln_ignoreGroupRestrictions && TL_MODE === 'FE') {
            $searchCondition = "
                (
                    `useGroupRestrictions` != '1'
                    OR `allowedGroups` LIKE '%\"" . $this->arr_groupSettingsForUser['id'] . "\"%'
                )
            ";
            $searchConditionValues = array();
        } else {
            $searchCondition = "";
            $searchConditionValues = array();
        }

        foreach ($this->arrSearchCriteria as $criterionFieldName => $criterionValue) {
            if ($searchCondition) {
                $searchCondition .= "
					AND ";
            }

            // Creating the condition statement and manipulating the criterionValue if necessary and creating the condition values array
            switch ($criterionFieldName) {
                case 'id':
                    if (!is_array($criterionValue)) {
                        if ($criterionValue) {
                            $criterionValue = array($criterionValue);
                        } else {
                            // make sure that we can't get any results
                            $searchCondition .= "	(1 = 2)";
                            break;
                        }
                    }
                    $searchCondition .= "	(".$this->getQualifiedFieldName($criterionFieldName)." IN (".implode(',', $criterionValue)."))";
                    break;

                case 'pages':
                    if (!is_array($criterionValue)) {
                        $criterionValue = array($criterionValue);
                    }
                    $searchConditionPagesPart = '';
                    foreach ($criterionValue as $pageID) {
                        if ($searchConditionPagesPart) {
                            $searchConditionPagesPart .= "
								OR";
                        }
                        $searchConditionPagesPart .= $this->getQualifiedFieldName($criterionFieldName)." LIKE ?
						";

                        $searchConditionValues[] = $pageID ? '%%"'.$pageID.'"%' : ($this->blnEmptyFieldMatchesPerDefault ? '%' : '');
                    }
                    $searchCondition .= "	(".$searchConditionPagesPart.")";
                    break;

                case 'fulltext':
                    /*
                     * If the criterionValue is put in quotes it must not be splitted in separate
                     * criterionValues and the quotes must be removed
                     */
                    if (preg_match('/^&#34;(.*)&#34;$/', $criterionValue)) {
                        $criterionValue = preg_replace('/^&#34;(.*)&#34;$/', '\\1', $criterionValue);
                        $arrCriterionValues = array($criterionValue);
                    } else {
                        $arrCriterionValues = explode(' ', $criterionValue);
                    }

                    foreach ($arrCriterionValues as $k => $v) {
                        if (!$v) {
                            unset ($arrCriterionValues[$k]);
                            continue;
                        }
                        $arrCriterionValues[$k] = preg_replace('/&#34;/', '', $v);
                    }

                    /*
                     * There has to be at least one empty string in the array because
                     * otherwise there wouldn't be any searchCriteria
                     */
                    if (!count($arrCriterionValues)) {
                        $arrCriterionValues[] = '';
                    }

                    $searchConditionPart = '';

                    $addToSelectStatement = '';

                    if ($this->blnUsePriority()) {
                        $arr_searchResultWeighting = array(
                            'wholeSearchStringMatches' => array(
                                'wholeFieldMatches' => array(
                                    'title' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_title'] ?: 300,
                                    'keywords' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_keywords'] ?: 200,
                                    'shortDescription' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_shortDescription'] ?: 200,
                                    'description' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_description'] ?: 200,
                                    'productCode' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_productCode'] ?: 200,
                                    'producer' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_producer'] ?: 200
                                ),
                                'partOfFieldMatches' => array(
                                    'title' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_title'] ?: 30,
                                    'keywords' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_keywords'] ?: 20,
                                    'shortDescription' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_shortDescription'] ?: 20,
                                    'description' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_description'] ?: 20,
                                    'productCode' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_productCode'] ?: 20,
                                    'producer' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_producer'] ?: 20
                                )
                            ),
                            'partOfSearchStringMatches' => array(
                                'wholeFieldMatches' => array(
                                    'title' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_title'] ?: 30,
                                    'keywords' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_keywords'] ?: 10,
                                    'shortDescription' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_shortDescription'] ?: 10,
                                    'description' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_description'] ?: 10,
                                    'productCode' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_productCode'] ?: 20,
                                    'producer' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_producer'] ?: 20
                                ),
                                'partOfFieldMatches' => array(
                                    'title' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_title'] ?: 3,
                                    'keywords' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_keywords'] ?: 1,
                                    'shortDescription' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_shortDescription'] ?: 1,
                                    'description' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_description'] ?: 1,
                                    'productCode' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_productCode'] ?: 2,
                                    'producer' => $GLOBALS['TL_CONFIG']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_producer'] ?: 2
                                )
                            )
                        );

                        if (isset($GLOBALS['MERCONIS_HOOKS']['customizeSearchResultWeighting']) && is_array($GLOBALS['MERCONIS_HOOKS']['customizeSearchResultWeighting'])) {
                            foreach ($GLOBALS['MERCONIS_HOOKS']['customizeSearchResultWeighting'] as $mccb) {
                                $objMccb = \System::importStatic($mccb[0]);
                                $arr_searchResultWeighting = $objMccb->{$mccb[1]}($arr_searchResultWeighting);
                            }
                        }

                        /*
                         * add the searchConditionValues for the CASE statement at the beginning of the array because the wildcards
                         * for these replacement values are the first wildcards in the query
                         */

                        $addToSelectStatement = ', ';
                        $addToSelectStatementConditionValuesArrayInsertPosition = 0;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('title')." LIKE ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['partOfFieldMatches']['title']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('title')." = ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['wholeFieldMatches']['title']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('keywords')." LIKE ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['partOfFieldMatches']['keywords']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('keywords')." = ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['wholeFieldMatches']['keywords']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('shortDescription')." LIKE ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['partOfFieldMatches']['shortDescription']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('shortDescription')." = ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['wholeFieldMatches']['shortDescription']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('description')." LIKE ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['partOfFieldMatches']['description']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('description')." = ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['wholeFieldMatches']['description']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductCode')." LIKE ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['partOfFieldMatches']['productCode']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue.'%'));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductCode')." = ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['wholeFieldMatches']['productCode']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductProducer')." LIKE ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['partOfFieldMatches']['producer']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                        $addToSelectStatement .= " + ";
                        $addToSelectStatementConditionValuesArrayInsertPosition++;

                        $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductProducer')." = ? THEN ".$arr_searchResultWeighting['wholeSearchStringMatches']['wholeFieldMatches']['producer']." ELSE 0 END
						";
                        array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));
                    }

                    if (isset($arrCriterionValues) && is_array($arrCriterionValues)) {
                        foreach ($arrCriterionValues as $criterionValue) {
                            if ($searchConditionPart) {
                                $searchConditionPart .= "
									OR";
                            }
                            $searchConditionPart .= "(
									".$this->getQualifiedFieldName('title')." LIKE ?
								OR	".$this->getQualifiedFieldName('keywords')." LIKE ?
								OR	".$this->getQualifiedFieldName('shortDescription')." LIKE ?
								OR	".$this->getQualifiedFieldName('description')." LIKE ?
								OR	".$this->getQualifiedFieldName('lsShopProductCode')." LIKE ?
								OR	".$this->getQualifiedFieldName('lsShopProductProducer')." LIKE ?
							)";

                            $criterionValue = preg_replace('/%/siU', '*', $criterionValue);
                            $criterionValue = preg_replace('/\*/siU', '%%', $criterionValue);

                            if ($this->blnUsePriority()) {
                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('title')." LIKE ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['partOfFieldMatches']['title']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('title')." = ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['wholeFieldMatches']['title']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('keywords')." LIKE ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['partOfFieldMatches']['keywords']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('keywords')." = ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['wholeFieldMatches']['keywords']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('shortDescription')." LIKE ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['partOfFieldMatches']['shortDescription']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('shortDescription')." = ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['wholeFieldMatches']['shortDescription']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('description')." LIKE ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['partOfFieldMatches']['description']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('description')." = ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['wholeFieldMatches']['description']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductCode')." LIKE ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['partOfFieldMatches']['productCode']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue.'%'));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductCode')." = ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['wholeFieldMatches']['productCode']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductProducer')." LIKE ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['partOfFieldMatches']['producer']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array('%%'.$criterionValue.'%'));




                                $addToSelectStatement .= " + ";
                                $addToSelectStatementConditionValuesArrayInsertPosition++;

                                $addToSelectStatement .= "CASE WHEN ".$this->getQualifiedFieldName('lsShopProductProducer')." = ? THEN ".$arr_searchResultWeighting['partOfSearchStringMatches']['wholeFieldMatches']['producer']." ELSE 0 END
								";
                                array_insert($searchConditionValues, $addToSelectStatementConditionValuesArrayInsertPosition, array($criterionValue));
                            }

                            $searchConditionValues[] = '%%'.$criterionValue.'%';
                            $searchConditionValues[] = '%%'.$criterionValue.'%';
                            $searchConditionValues[] = '%%'.$criterionValue.'%';
                            $searchConditionValues[] = '%%'.$criterionValue.'%';
                            $searchConditionValues[] = $criterionValue.'%';
                            $searchConditionValues[] = '%%'.$criterionValue.'%';
                        }
                    }

                    if ($this->blnUsePriority()) {
                        $addToSelectStatement .= " as `priority`";
                    }

                    $searchCondition .= "	(".$searchConditionPart.")";
                    break;

                default:
                    $criterionValue = preg_replace('/%/siU', '*', $criterionValue);
                    $criterionValue = preg_replace('/\*/siU', '%%', $criterionValue);
                    $criterionValue = $criterionValue ? $criterionValue : ($this->blnEmptyFieldMatchesPerDefault ? '%' : '');

                    /*
                     * Make sure that a null value matches a search string that
                     * only consists of wildcards, just as an empty string would,
                     * by converting null to an empty string using "IFNULL".
                     * If the search string contains anything else than the wildcard (%)
                     * and therefore an empty string wouldn't match, we don't use
                     * IFNULL because it would have no effect but a lower performance.
                     */
                    if (preg_match('/[^%]/', $criterionValue)) {
                        $searchCondition .= $this->getQualifiedFieldName($criterionFieldName)." LIKE ?
						";
                    } else {
                        $searchCondition .= "IFNULL(".$this->getQualifiedFieldName($criterionFieldName).", '') LIKE ?
						";
                    }
                    $searchConditionValues[] = $criterionValue;
                    break;
            }
        }
        /*
         * Ende Erstellung der Where-Bedingungen
         * ############################
         */

        /*
         * Erstellung des ORDER-Statements
         */
        $orderStatement = '';
        if (is_array($this->arrSplitSorting['db']) && count($this->arrSplitSorting['db'])) {
            foreach ($this->arrSplitSorting['db'] as $sortingField) {
                if (!isset($sortingField['field']) || !$sortingField['field']) {
                    continue;
                }
                if ($orderStatement) {
                    $orderStatement .= ', ';
                }
                $orderStatement .= $this->getQualifiedFieldName($sortingField['field']).'  '.(isset($sortingField['direction']) && $sortingField['direction'] ? $sortingField['direction'] : 'ASC');
            }

            $orderStatement = "ORDER BY		".$orderStatement;
        } else {
            $orderStatement = "ORDER BY		".$this->getQualifiedFieldName('title')." ASC";
        }

        /*
         * Ende Erstellung des ORDER-Statements
         */

        /*
         * ###############################
         * Create the field selection part
         */
        $fieldSelectionPart = "";

        /*
         * If the filter should be used, we also request the attributeID and attributeValueID
         * and maybe some more fields depending on what functionality the filter actually provides.
         */
        $tmpRequestFields = $this->arrRequestFields;
        if ($this->blnUseFilter) {
            if (!in_array('lsShopProductAttributesValues', $this->arrRequestFields)) {
                $this->arrRequestFields[] = 'lsShopProductAttributesValues';
            }

            if (!in_array('lsShopProductPrice', $this->arrRequestFields)) {
                $this->arrRequestFields[] = 'lsShopProductPrice';
            }

            if (!in_array('lsShopProductSteuersatz', $this->arrRequestFields)) {
                $this->arrRequestFields[] = 'lsShopProductSteuersatz';
            }

            if (!in_array('lsShopProductProducer', $this->arrRequestFields)) {
                $this->arrRequestFields[] = 'lsShopProductProducer';
            }
        }

        if (count($this->arrSplitSorting['php'])) {
            if (in_array('lsShopProductPrice', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('lsShopProductPrice', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'lsShopProductPrice';
                }

                if (!in_array('lsShopProductSteuersatz', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'lsShopProductSteuersatz';
                }
            }

            if (in_array('flex_contentsLanguageIndependent', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('flex_contentsLanguageIndependent', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'flex_contentsLanguageIndependent';
                }
            }

            if (in_array('flex_contents', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('flex_contents', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'flex_contents';
                }
            }

            if (in_array('lsShopProductAttributesValues', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('lsShopProductAttributesValues', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'lsShopProductAttributesValues';
                }
            }

            if (in_array('title', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('title', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'title';
                }
            }

            if (in_array('lsShopProductCode', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('lsShopProductCode', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'lsShopProductCode';
                }
            }

            if (in_array('sorting', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('sorting', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'sorting';
                }
            }

            if (in_array('lsShopProductProducer', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('lsShopProductProducer', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'lsShopProductProducer';
                }
            }

            if (in_array('lsShopProductWeight', $this->arrSplitSorting['phpFieldNames'])) {
                if (!in_array('lsShopProductWeight', $this->arrRequestFields)) {
                    $this->arrRequestFields[] = 'lsShopProductWeight';
                }
            }
        }

        if ($this->bln_useGroupPrices) {
            if (in_array('lsShopProductPrice', $this->arrRequestFields)) {
                $this->arrRequestFields[] = 'useGroupPrices_1';
                $this->arrRequestFields[] = 'priceForGroups_1';
                $this->arrRequestFields[] = 'lsShopProductPrice_1';

                $this->arrRequestFields[] = 'useGroupPrices_2';
                $this->arrRequestFields[] = 'priceForGroups_2';
                $this->arrRequestFields[] = 'lsShopProductPrice_2';

                $this->arrRequestFields[] = 'useGroupPrices_3';
                $this->arrRequestFields[] = 'priceForGroups_3';
                $this->arrRequestFields[] = 'lsShopProductPrice_3';

                $this->arrRequestFields[] = 'useGroupPrices_4';
                $this->arrRequestFields[] = 'priceForGroups_4';
                $this->arrRequestFields[] = 'lsShopProductPrice_4';

                $this->arrRequestFields[] = 'useGroupPrices_5';
                $this->arrRequestFields[] = 'priceForGroups_5';
                $this->arrRequestFields[] = 'lsShopProductPrice_5';
            }
        }

        foreach ($this->arrRequestFields as $requestField) {
            if ($fieldSelectionPart) {
                $fieldSelectionPart .= ",
				";
            }
            $fieldSelectionPart .= $this->getQualifiedFieldName($requestField);
        }
        /*
         * ###############################
         */


        /**
         * In the statement we only include the left join part if we use
         * the filter because it is only required if we request attribute
         * allocations
         */
        $objProductsComplete = \Database::getInstance()->prepare("
			SELECT			".$fieldSelectionPart."
							".($addToSelectStatement ?? '')."
			FROM			`tl_ls_shop_product`
			WHERE			".$searchCondition."
			".$orderStatement."
		");

        if (is_array($this->arrLimit) && isset($this->arrLimit['rows']) && isset($this->arrLimit['offset']) && $this->arrLimit['rows'] > 0) {
            $objProductsComplete = $objProductsComplete->limit($this->arrLimit['rows'], $this->arrLimit['offset']);
        }

        $objProductsComplete = $objProductsComplete->execute($searchConditionValues);

        /*
         * If we use the filter or the special price sorting or maybe for some other reasons,
         * we requested more than just the id field, and those other
         * fields will be used in the filter checks. However, the productSearcher must still
         * only return the fields that have been requested originally on instantiation of
         * the productSearcher object. Therefore we restore the original requestFields using
         * the temporary variable we assigned previously.
         */
        $this->arrRequestFields = $tmpRequestFields;

        $arrProductsComplete = $objProductsComplete->fetchAllAssoc();

        /*
         * If we use the filter we need variant information for each product.
         *
         * We also add some other information to the product/variant data that could not be retrieved
         * directly from the database, e.g. calculated prices.
         */

        if ($this->blnUseFilter && count($arrProductsComplete)) {
            /*
             * We want each product to be easily accessible by its id when we're adding variant information later
             * and therefore we can't work with the database results array directly. Instead, we write all product
             * data in a temporary array which we index with the product ids and overwrite the original database
             * results array with that temporary array. Effectively, we re-index the database results array with
             * the product ids and add some information.
             */
            $tmpArrProductsComplete = [];
            foreach ($arrProductsComplete as $rowProductsComplete) {
                $productId = $rowProductsComplete['id'];
                $tmpArrProductsComplete[$productId] = $rowProductsComplete;
                $tmpArrProductsComplete[$productId]['variants'] = [];

                /*
                 * We create an array holding only the attribute IDs, another array holding only
                 * the attribute value IDs and one more array holding attribute IDs and attribute
                 * value IDs related to each other. That's because this way we can reduce workload
                 * while creating the filter form and while filtering, and we assume that this is
                 * worth the extra workload that we create here which should be smaller.
                 */
                $tmpArrProductsComplete[$productId]['attributeIDs'] = [];
                $tmpArrProductsComplete[$productId]['attributeValueIDs'] = [];
                $tmpArrProductsComplete[$productId]['attributeAndValueIDs'] = [];

                $attributesAndValues = json_decode($rowProductsComplete['lsShopProductAttributesValues'], true);
                if (is_array($attributesAndValues)) {
                    foreach ($attributesAndValues as $attributeAndValue) {
                        if (!ls_shop_filterHelper::filterFieldForAttributeExists($attributeAndValue[0])) {
                            continue;
                        }
                        $tmpArrProductsComplete[$productId]['attributeIDs'][] = $attributeAndValue[0];
                        $tmpArrProductsComplete[$productId]['attributeValueIDs'][] = $attributeAndValue[1];
                        $tmpArrProductsComplete[$productId]['attributeAndValueIDs'][$attributeAndValue[0]][] = $attributeAndValue[1];
                    }
                }

                if (ls_shop_filterHelper::filterFieldForPriceExists()) {
                    if ($this->bln_useGroupPrices) {
                        $rowProductsComplete = $this->updateProductRowWithGroupPrice($rowProductsComplete);
                    }
                    $tmpArrProductsComplete[$productId]['price'] = ls_shop_generalHelper::getDisplayPrice($rowProductsComplete['lsShopProductPrice'], $rowProductsComplete['lsShopProductSteuersatz']);
                    $tmpArrProductsComplete[$productId]['lowestPrice'] = null;
                    $tmpArrProductsComplete[$productId]['highestPrice'] = null;
                }
            }

            /*
             * Get all variants for the products from the database
             */
            $objVariants = \Database::getInstance()->prepare("
				SELECT			`tl_ls_shop_variant`.`id`,
								`tl_ls_shop_variant`.`pid`,
								`tl_ls_shop_variant`.`lsShopVariantPrice`,
								`tl_ls_shop_variant`.`lsShopVariantPriceType`,
				".
                (
                $this->bln_useGroupPrices
                    ?	"
								`tl_ls_shop_variant`.`useGroupPrices_1`,
								`tl_ls_shop_variant`.`priceForGroups_1`,
								`tl_ls_shop_variant`.`lsShopVariantPrice_1`,
								`tl_ls_shop_variant`.`lsShopVariantPriceType_1`,
								
								`tl_ls_shop_variant`.`useGroupPrices_2`,
								`tl_ls_shop_variant`.`priceForGroups_2`,
								`tl_ls_shop_variant`.`lsShopVariantPrice_2`,
								`tl_ls_shop_variant`.`lsShopVariantPriceType_2`,
								
								`tl_ls_shop_variant`.`useGroupPrices_3`,
								`tl_ls_shop_variant`.`priceForGroups_3`,
								`tl_ls_shop_variant`.`lsShopVariantPrice_3`,
								`tl_ls_shop_variant`.`lsShopVariantPriceType_3`,
								
								`tl_ls_shop_variant`.`useGroupPrices_4`,
								`tl_ls_shop_variant`.`priceForGroups_4`,
								`tl_ls_shop_variant`.`lsShopVariantPrice_4`,
								`tl_ls_shop_variant`.`lsShopVariantPriceType_4`,
								
								`tl_ls_shop_variant`.`useGroupPrices_5`,
								`tl_ls_shop_variant`.`priceForGroups_5`,
								`tl_ls_shop_variant`.`lsShopVariantPrice_5`,
								`tl_ls_shop_variant`.`lsShopVariantPriceType_5`,
								
						"
                    :	""
                )
                ."
								`tl_ls_shop_variant`.`lsShopProductVariantAttributesValues`
				FROM			`tl_ls_shop_variant`
				WHERE			`tl_ls_shop_variant`.`published` = '1'
					AND			`tl_ls_shop_variant`.`pid` IN (".implode(',', array_keys($tmpArrProductsComplete)).")
				ORDER BY		`tl_ls_shop_variant`.`pid` ASC, `tl_ls_shop_variant`.`sorting` ASC
			")
                ->execute();

            $arrVariants = $objVariants->fetchAllAssoc();

            foreach ($arrVariants as $rowVariants) {
                $productId = $rowVariants['pid'];
                $variantId = $rowVariants['id'];
                $tmpArrProductsComplete[$productId]['variants'][$variantId] = $rowVariants;

                $tmpArrProductsComplete[$productId]['variants'][$variantId]['attributeIDs'] = [];
                $tmpArrProductsComplete[$productId]['variants'][$variantId]['attributeValueIDs'] = [];
                $tmpArrProductsComplete[$productId]['variants'][$variantId]['attributeAndValueIDs'] = [];

                $attributesAndValues = json_decode($rowVariants['lsShopProductVariantAttributesValues'], true);
                if (is_array($attributesAndValues)) {
                    foreach ($attributesAndValues as $attributeAndValue) {
                        if (!ls_shop_filterHelper::filterFieldForAttributeExists($attributeAndValue[0])) {
                            continue;
                        }
                        $tmpArrProductsComplete[$productId]['variants'][$variantId]['attributeIDs'][] = $attributeAndValue[0];
                        $tmpArrProductsComplete[$productId]['variants'][$variantId]['attributeValueIDs'][] = $attributeAndValue[1];
                        $tmpArrProductsComplete[$productId]['variants'][$variantId]['attributeAndValueIDs'][$attributeAndValue[0]][] = $attributeAndValue[1];
                    }
                }

                if (ls_shop_filterHelper::filterFieldForPriceExists()) {
                    /*
                     * Get the variant's price
                     */
                    if ($this->bln_useGroupPrices) {
                        $rowVariants = $this->updateVariantRowWithGroupPrice($rowVariants);
                    }

                    $tmpArrProductsComplete[$productId]['variants'][$variantId]['price'] = ls_shop_generalHelper::getDisplayPrice(
                        ls_shop_generalHelper::ls_calculateVariantPriceRegardingPriceType(
                            $rowVariants['lsShopVariantPriceType'],
                            $tmpArrProductsComplete[$productId]['lsShopProductPrice'],
                            $rowVariants['lsShopVariantPrice']
                        ),
                        $tmpArrProductsComplete[$productId]['lsShopProductSteuersatz']
                    );

                    /*
                     * Store the variant's price as the product's lowest and highest price if it hasn't been set yet or if
                     * the current variant's price is lower/higher than the currently stored lowest/highest price.
                     */
                    if ($tmpArrProductsComplete[$productId]['lowestPrice'] === null || $tmpArrProductsComplete[$productId]['variants'][$variantId]['price'] < $tmpArrProductsComplete[$productId]['lowestPrice']) {
                        $tmpArrProductsComplete[$productId]['lowestPrice'] = $tmpArrProductsComplete[$productId]['variants'][$variantId]['price'];
                    }
                    if ($tmpArrProductsComplete[$productId]['highestPrice'] === null || $tmpArrProductsComplete[$productId]['variants'][$variantId]['price'] > $tmpArrProductsComplete[$productId]['highestPrice']) {
                        $tmpArrProductsComplete[$productId]['highestPrice'] = $tmpArrProductsComplete[$productId]['variants'][$variantId]['price'];
                    }
                }
            }

            $arrProductsComplete = $tmpArrProductsComplete;

            /*
             * This hook is only meant to be called in a product list context and therefore
             * we check for a given productListID. If the cache can be used we don't call
             * the hook since it wouldn't have any effect because the cached result would
             * always be returned.
             */
            if ($this->str_productListID && !$this->checkIfCacheCanBeUsed()) {
                if (isset($GLOBALS['MERCONIS_HOOKS']['afterProductSearchBeforeFilter']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterProductSearchBeforeFilter'])) {
                    foreach ($GLOBALS['MERCONIS_HOOKS']['afterProductSearchBeforeFilter'] as $mccb) {
                        $objMccb = \System::importStatic($mccb[0]);
                        $arrProductsComplete = $objMccb->{$mccb[1]}($this->str_productListID, $arrProductsComplete);
                    }
                }
            }

            if (count($arrProductsComplete) > 1 || (count($arrProductsComplete) == 1 && count($arrProductsComplete[key($arrProductsComplete)]['variants']))) {
                $this->blnEnoughProductsOrVariantsToFilterAvailable = true;
            }

            if ($this->blnEnoughProductsOrVariantsToFilterAvailable) {
                ls_shop_filterController::getInstance();
                ls_shop_filterHelper::setCriteriaToUseInFilterForm($arrProductsComplete);
            }
        }

        $this->numProductsBeforeFilter = !is_array($arrProductsComplete) ? 0 : count($arrProductsComplete);

        /*
         * If we have more results than the given truncate limit, the result array will be truncated
         */
        if ($this->truncateResultsIfMoreThan > 0 && $this->truncateResultsIfMoreThan < $this->numProductsBeforeFilter) {
            if ($this->cancelSearchIfMoreThanTruncateLimit) {
                $this->arrProductResultsComplete = array();
                return;
            }
            $arrProductsComplete = array_slice($arrProductsComplete, 0, $this->truncateResultsIfMoreThan);
        }

        if (is_array($arrProductsComplete)) {
            $arrProductsAfterFilter = array();
            foreach ($arrProductsComplete as $rowProductsComplete) {
                if ($this->blnUseFilter && $this->blnEnoughProductsOrVariantsToFilterAvailable) {
                    /*
                     * Here we walk through all products that the database request delivered. In order
                     * to filter these products we perform filter checks for each product (and the
                     * variants it includes) and if we find out that a product doesn't match the filter,
                     * we skip it and don't write it to $this->arrProductResultsComplete.
                     */
                    ls_shop_filterController::getInstance();
                    $blnProductMatches = ls_shop_filterHelper::checkIfProductMatchesFilter($rowProductsComplete);

                    if (!$blnProductMatches) {
                        $this->blnNotAllProductsMatch = true;
                        $this->numProductsNotMatching++;
                        continue;
                    }
                }
                $arrProductsAfterFilter[] = $rowProductsComplete;
            }

            if ($this->blnUseFilter && $this->blnEnoughProductsOrVariantsToFilterAvailable && is_array($arrProductsAfterFilter)) {
                ls_shop_filterController::getInstance();
                ls_shop_filterHelper::getEstimatedMatchNumbers($arrProductsComplete);
            }

            $this->specialSortResults($arrProductsAfterFilter);
            $firstRequestField = $this->arrRequestFields[0];
            foreach ($arrProductsAfterFilter as $rowProductAfterFilter) {
                $this->arrProductResultsComplete[] = count($this->arrRequestFields) > 1 ? $rowProductAfterFilter : $rowProductAfterFilter[$firstRequestField];
            }
        }

        /*
         * Vorgegebene Sortierung übernehmen, wenn keine spezielle Sortierung gewünscht ist und eine Produktauswahl direkt übergeben wurde
         */
        if (is_array($this->fixedSorting) && count($this->fixedSorting)) {
            $arrProductIDsTempComplete = array();

            foreach ($this->fixedSorting as $productID) {
                if (in_array($productID, $this->arrProductResultsComplete)) {
                    $arrProductIDsTempComplete[] = $productID;
                }
            }

            $this->arrProductResultsComplete = $arrProductIDsTempComplete;
        }
    }

    protected function getProductResultsCurrentPage() {
        if ($this->arrProductResultsCurrentPage === null) {
            if ($this->intNumPerPage && is_array($this->productResultsComplete) && count($this->productResultsComplete)) {
                /*
                 * Check whether the currently requested page contains any products. If the requested page is higher than what could possibly create
                 * any results given the number of $this->arrProductResultsComplete the biggest useful page number is calculated and then used.
                 */
                $tmpNumProductsRequiredToGetAResult = (($this->intCurrentPage - 1) * $this->intNumPerPage) + 1;
                $tmpNumDifferenceBetweenRequiredProductsAndExistingResults = $tmpNumProductsRequiredToGetAResult - count($this->productResultsComplete);

                /*
                 * The currently existing results are not enough to create a result for the requested page
                 */
                if ($tmpNumDifferenceBetweenRequiredProductsAndExistingResults > 0) {
                    $tmpNumPagesWithoutResults =  ceil($tmpNumDifferenceBetweenRequiredProductsAndExistingResults/$this->intNumPerPage);
                    $this->intCurrentPage = $this->intCurrentPage - $tmpNumPagesWithoutResults;
                }

                $this->arrProductResultsCurrentPage = array_slice($this->productResultsComplete, ($this->intCurrentPage - 1) * $this->intNumPerPage, $this->intNumPerPage);
            } else {
                $this->arrProductResultsCurrentPage = $this->productResultsComplete;
            }
        }
    }

    protected function specialSortResults(&$arrProductsAfterFilter) {
        if (
            $this->blnDoNotSpecialSort
            ||	!is_array($this->arrSplitSorting['php'])
            ||	!count($this->arrSplitSorting['php'])
            ||	!is_array($arrProductsAfterFilter)
            ||	!count($arrProductsAfterFilter)
            ||	(is_array($this->fixedSorting) && count($this->fixedSorting))
        ) {
            return;
        }

        $searchLanguage = $this->searchLanguage;

        /*
         * If fields for the requested language don't exist, no specific search language should
         * be used which means that the non language specific main field would be used for sorting.
         * However the non language specific main field has probably not been requested from the DB,
         * so without language, the sorting would probably fail. This should be okay, because normally
         * if a language can be requested, it must exist.
         */
        if (!$this->checkIfLanguageFieldsExist($searchLanguage)) {
            $searchLanguage = null;
        }

        foreach ($this->arrSplitSorting['php'] as $sortingField) {
            if (!isset($sortingField['field']) || !$sortingField['field']) {
                continue;
            }

            $sortingTypeFlag = SORT_REGULAR;

            $arrOrder = array();
            $arr_tmpProductsToSort = array();
            $arr_tmpProductsToPlaceBehindTheOthers = array();

            switch($sortingField['field']) {
                case 'lsShopProductPrice':
                    $sortingTypeFlag = SORT_NUMERIC;
                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        /*
                         * If the displayPrice already exists because it has been
                         * calculated before for the filter, we use it.
                         * If not, we calculate it now.
                         */
                        if (isset($arrProduct['price'])) {
                            $arrOrder[$k] = $arrProduct['price'];
                        } else {
                            if ($this->bln_useGroupPrices) {
                                $arrProduct = $this->updateProductRowWithGroupPrice($arrProduct);
                            }

                            $arrOrder[$k] = ls_shop_generalHelper::getDisplayPrice($arrProduct['lsShopProductPrice'], $arrProduct['lsShopProductSteuersatz']);
                        }

                        $arr_tmpProductsToSort[$k] = $arrProduct;
                    }
                    break;

                case 'flex_contents':
                    $sortingTypeFlag = SORT_STRING;
                    if (!isset($sortingField['variableFieldKey'])) {
                        return;
                    }
                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        $arrFlexContents = createMultidimensionalArray(\LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray(json_decode($arrProduct['flex_contents'.($searchLanguage ? "_".$searchLanguage : "")])), 2, 1);

                        if (!isset($arrFlexContents[$sortingField['variableFieldKey']])) {
                            $arr_tmpProductsToPlaceBehindTheOthers[$k] = $arrProduct;
                            continue;
                        }

                        $arrOrder[$k] = $this->fakeLocaleForSorting($arrFlexContents[$sortingField['variableFieldKey']]);
                        $arr_tmpProductsToSort[$k] = $arrProduct;
                    }
                    break;

                case 'flex_contentsLanguageIndependent':
                    $sortingTypeFlag = SORT_STRING;
                    if (!isset($sortingField['variableFieldKey'])) {
                        return;
                    }
                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        $arrFlexContents = createMultidimensionalArray(\LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray(json_decode($arrProduct['flex_contentsLanguageIndependent'])), 2, 1);

                        if (!isset($arrFlexContents[$sortingField['variableFieldKey']])) {
                            $arr_tmpProductsToPlaceBehindTheOthers[$k] = $arrProduct;
                            continue;
                        }

                        $arrOrder[$k] = $this->fakeLocaleForSorting($arrFlexContents[$sortingField['variableFieldKey']]);
                        $arr_tmpProductsToSort[$k] = $arrProduct;
                    }
                    break;

                case 'lsShopProductAttributesValues':
                    $sortingTypeFlag = SORT_STRING;
                    if (!isset($sortingField['variableFieldKey'])) {
                        return;
                    }

                    $arrAttributes = ls_shop_generalHelper::getProductAttributes();
                    $arrAttributeValues = ls_shop_generalHelper::getAttributeValues();

                    $attributeIDForSorting = 0;
                    foreach ($arrAttributes as $arrAttribute) {
                        if ($arrAttribute['alias'] == $sortingField['variableFieldKey']) {
                            $attributeIDForSorting = $arrAttribute['id'];
                        }
                    }
                    if (!$attributeIDForSorting) {
                        return;
                    }

                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        $arrAttributesValuesAllocation = json_decode($arrProduct['lsShopProductAttributesValues']);
                        $bln_foundRequiredAttributeValueAllocationForThisProduct = false;

                        foreach($arrAttributesValuesAllocation as $arrAttributeValueAllocation) {
                            if ($arrAttributeValueAllocation[0] == $attributeIDForSorting) {
                                $bln_foundRequiredAttributeValueAllocationForThisProduct = true;
                                $arrOrder[$k] = $arrAttributeValues[$arrAttributeValueAllocation[1]]['title'];
                                break;
                            }
                        }

                        if ($bln_foundRequiredAttributeValueAllocationForThisProduct) {
                            $arr_tmpProductsToSort[$k] = $arrProduct;
                        } else {
                            $arr_tmpProductsToPlaceBehindTheOthers[$k] = $arrProduct;
                        }
                    }
                    break;

                case 'title':
                    $sortingTypeFlag = SORT_STRING;
                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        $arrOrder[$k] = $arrProduct['title'.($searchLanguage ? "_".$searchLanguage : "")];
                        $arr_tmpProductsToSort[$k] = $arrProduct;
                    }
                    break;

                case 'priority':
                    $sortingTypeFlag = SORT_NUMERIC;
                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        $arrOrder[$k] = $arrProduct[$sortingField['field']];
                        $arr_tmpProductsToSort[$k] = $arrProduct;
                    }
                    break;

                case 'sorting':
                    $sortingTypeFlag = SORT_NUMERIC;
                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        $arrOrder[$k] = $arrProduct[$sortingField['field']];
                        $arr_tmpProductsToSort[$k] = $arrProduct;
                    }
                    break;

                /*
                 * The default should work for all standard product fields,
                 * where the field value in the database is directly stored
                 * as it is required for sorting and where the field name
                 * matches the given sorting field name (i.e. without language suffix).
                 * Should work for sorting by productCode etc.
                 */
                default:
                    $sortingTypeFlag = SORT_STRING;
                    foreach ($arrProductsAfterFilter as $k => $arrProduct) {
                        $arrOrder[$k] = $arrProduct[$sortingField['field']];
                        $arr_tmpProductsToSort[$k] = $arrProduct;
                    }
                    break;
            }

            /*
             * FIXME: If calling array_multisort independently multiple times with different contents
             * in $arrOrder doesn't result in a properly multi-level sorted array (hopefully it does), then we have
             * to create a single array_multisort statement which can surely do this but is a little
             * bit tricky to create because we most likely would have to use eval which isn't really cool.
             */
            array_multisort($arrOrder, isset($sortingField['direction']) && $sortingField['direction'] == 'ASC' ? SORT_ASC : SORT_DESC, $sortingTypeFlag, $arr_tmpProductsToSort);
            $arrProductsAfterFilter = array_merge($arr_tmpProductsToSort, $arr_tmpProductsToPlaceBehindTheOthers);
        }
    }

    /*
     * This seems to be necessary because php does not offer an actually working
     * way to correctly sort strings with umlauts (and probably other special characters).
     */
    protected function fakeLocaleForSorting($string = '') {
        if (!isset($GLOBALS['merconis_globals']['sortingCharacterTranslationTable'])) {
            $GLOBALS['merconis_globals']['sortingCharacterTranslationTable'] = array(
                'arrSearch' => array(),
                'arrReplace' => array()
            );

            $skipNextReplacement = false;
            $sortingCharacterTranslationTable = \LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray(json_decode($GLOBALS['TL_CONFIG']['ls_shop_sortingCharacterTranslationTable']));

            if (is_array($sortingCharacterTranslationTable)) {
                foreach ($sortingCharacterTranslationTable as $k => $v) {
                    $v = trim($v);
                    if ($k % 2 == 0) {
                        $key = 'arrSearch';
                        if (!strlen($v)) {
                            $skipNextReplacement = true;
                            continue;
                        }
                    } else {
                        $key = 'arrReplace';
                        if ($skipNextReplacement) {
                            $skipNextReplacement = false;
                            continue;
                        }
                    }
                    $GLOBALS['merconis_globals']['sortingCharacterTranslationTable'][$key][] = $v;
                }
            }
        }

        /*
         * If there is nothing to replace, just pass back the original string
         */
        if (!count($GLOBALS['merconis_globals']['sortingCharacterTranslationTable']['arrSearch'])) {
            return $string;
        }

        return str_replace($GLOBALS['merconis_globals']['sortingCharacterTranslationTable']['arrSearch'], $GLOBALS['merconis_globals']['sortingCharacterTranslationTable']['arrReplace'], $string);
    }

    protected function updateProductRowWithGroupPrice($arr_productData) {
        if (!is_array($arr_productData)) {
            return $arr_productData;
        }

        $arr_structuredGroupPrices = ls_shop_generalHelper::getStructuredGroupPrices($arr_productData, 'product');

        /*
         * If group price settings exist for the current member group, we
         * override the product's basic price settings with the group settings
         */
        if (isset($arr_structuredGroupPrices[$this->arr_groupSettingsForUser['id']])) {
            $arr_productData['lsShopProductPrice'] = $arr_structuredGroupPrices[$this->arr_groupSettingsForUser['id']]['lsShopProductPrice'];
        }

        return $arr_productData;
    }

    protected function updateVariantRowWithGroupPrice($arr_variantData) {
        if (!is_array($arr_variantData)) {
            return $arr_variantData;
        }

        $arr_structuredGroupPrices = ls_shop_generalHelper::getStructuredGroupPrices($arr_variantData, 'variant');

        /*
         * If group price settings exist for the current member group, we
         * override the variant's basic price settings with the group settings
         */
        if (isset($arr_structuredGroupPrices[$this->arr_groupSettingsForUser['id']])) {
            $arr_variantData['lsShopVariantPriceType'] = $arr_structuredGroupPrices[$this->arr_groupSettingsForUser['id']]['lsShopVariantPriceType'];
            $arr_variantData['lsShopVariantPrice'] = $arr_structuredGroupPrices[$this->arr_groupSettingsForUser['id']]['lsShopVariantPrice'];
        }

        return $arr_variantData;
    }
}