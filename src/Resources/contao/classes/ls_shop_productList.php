<?php

namespace Merconis\Core;

use Contao\System;
use Contao\Database;
use Contao\Input;
use Contao\Controller;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Pagination;
use LeadingSystems\Helpers\FlexWidget;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;

class ls_shop_productList
{
	protected $outputDefinition = array();
	protected $mode = 'standard';
	protected $fixedSorting = array();
	protected $productListID = 'standard';
	protected $currentPage = 1;
	
	protected $arrSearchCriteria = null;
	protected $blnUseFilter = false;
	
	protected $blnIsTruncated = false;
	protected $maxNumProducts = 0;
	protected $noOutputIfMoreThanMaxResults = false;
	
	protected $numProducts = 0;
	
	protected $blnIsFrontendSearch = false;
	protected $bln_showProductsFromSubordinatePages = false;
	protected $bln_considerUnpublishedPages = false;
	protected $bln_considerHiddenPages = false;
	protected $int_startLevel = 0;
	protected $int_stopLevel = 0;

	public function __construct($productListID = '', $bln_showProductsFromSubordinatePages = null, $bln_considerUnpublishedPages = null, $bln_considerHiddenPages = null, $int_startLevel = null, $int_stopLevel = null) {
		/** @var \PageModel $objPage */
		global $objPage;
		if ($productListID) {
			$this->productListID = $productListID;
		}

		$this->bln_showProductsFromSubordinatePages = isset($bln_showProductsFromSubordinatePages) ? $bln_showProductsFromSubordinatePages : $this->bln_showProductsFromSubordinatePages;
		$this->bln_considerUnpublishedPages = isset($bln_considerUnpublishedPages) ? $bln_considerUnpublishedPages : $this->bln_considerUnpublishedPages;
		$this->bln_considerHiddenPages = isset($bln_considerHiddenPages) ? $bln_considerHiddenPages : $this->bln_considerHiddenPages;
		$this->int_startLevel = isset($int_startLevel) ? $int_startLevel : $this->int_startLevel;
		$this->int_stopLevel = isset($int_stopLevel) ? $int_stopLevel : $this->int_stopLevel;

		if ($this->productListID == 'standard') {
			$this->blnUseFilter = (!isset($GLOBALS['merconis_globals']['ls_shop_activateFilter']) || !$GLOBALS['merconis_globals']['ls_shop_activateFilter']) ? false : (isset($GLOBALS['merconis_globals']['ls_shop_useFilterInStandardProductlist']) && $GLOBALS['merconis_globals']['ls_shop_useFilterInStandardProductlist'] ? true : false);
		} else {
			/*
			 * In case of a CrossSeller productlist we have to check
			 * the CrossSeller's settings to know whether the productlist
			 * should be filtered.
			 */
			if (preg_match('/crossSeller_(\d*)/', $this->productListID, $arrMatches)) {
				if ($arrMatches[1]) {
					$objCrossSeller = Database::getInstance()->prepare("
						SELECT		`canBeFiltered`
						FROM		`tl_ls_shop_cross_seller`
						WHERE		`id` = ?
							AND		`published` = '1'
					")
					->execute($arrMatches[1]);
					
					if ($objCrossSeller->numRows) {
						$objCrossSeller->first();
						$this->blnUseFilter = (!isset($GLOBALS['merconis_globals']['ls_shop_activateFilter']) || !$GLOBALS['merconis_globals']['ls_shop_activateFilter']) ? false : ($objCrossSeller->canBeFiltered ? true : false);
					}
				}
			}
		}
		
		$this->currentPage = Input::get('page_'.$this->productListID) ? Input::get('page_'.$this->productListID) : 1;
		
		$this->outputDefinition = ls_shop_generalHelper::getOutputDefinition();

		if ($this->bln_showProductsFromSubordinatePages) {
            $this->arrSearchCriteria = array('pages' => ls_shop_generalHelper::flattenSubPageIdsArray(ls_shop_generalHelper::getSubPageIdsRecursively(ls_shop_languageHelper::getMainlanguagePageIDForPageID($objPage->id), $this->bln_considerUnpublishedPages, $this->bln_considerHiddenPages), $this->int_startLevel, $this->int_stopLevel));
        } else {
            $this->arrSearchCriteria = array('pages' => ls_shop_languageHelper::getMainlanguagePageIDForPageID($objPage->id));
        }
	}
	
	public function __get($what) {
		switch ($what) {
			case 'outputDefinition':
				return $this->outputDefinition;
				break;
				
			case 'blnIsTruncated':
				return $this->blnIsTruncated;
				break;
			
			case 'numProducts':
				return $this->numProducts;
				break;
		}

		return null;
	}
	
	public function __set($key, $value) {
		switch ($key) {
			case 'outputDefinition':
				$this->outputDefinition = $value;
				break;

			case 'mode':
				$this->mode = $value;
				$this->outputDefinition = ls_shop_generalHelper::getOutputDefinition(false, $this->mode);
				break;
				
			case 'arrSearchCriteria':
				$this->arrSearchCriteria = $value;
				break;
				
			case 'fixedSorting':
				$this->fixedSorting = $value;
				break;
			
			case 'maxNumProducts':
				$this->maxNumProducts = $value;
				break;
				
			case 'noOutputIfMoreThanMaxResults':
				$this->noOutputIfMoreThanMaxResults = $value;
				break;
				
			case 'blnIsFrontendSearch':
				$this->blnIsFrontendSearch = $value;
				break;
		}
	}
	
	public function parseOutput() {
		// Verarbeiten einer übergebenen Sortiervorgabe (User-Sortierung)
		if (
				Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == 'userSorting'
			&&	Input::post('identifyCorrespondingOutputDefinition') == $this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID
		) {
			$_SESSION['lsShop']['userSortingDefinition'][$this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID] = html_entity_decode(Input::post('userSortingSelection'));
			Controller::redirect(Environment::get('request'));
		}

		/*
		 * Durchführen der Suche
		 */
		if ($this->blnIsFrontendSearch) {
			if (isset($GLOBALS['MERCONIS_HOOKS']['beforeSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeSearch'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['beforeSearch'] as $mccb) {
					$objMccb = System::importStatic($mccb[0]);
					$this->arrSearchCriteria = $objMccb->{$mccb[1]}($this->arrSearchCriteria);
				}
			}
		}


        /** @var Adapter $productSearchAdapter */
        $productSearchAdapter = System::getContainer()->get('LeadingSystems\MerconisBundle\ProductSearch\Adapter');
        $productSearchAdapter->initialize($this->blnUseFilter, $this->productListID);

		foreach ($this->arrSearchCriteria as $searchCriteriaFieldName => $searchCriteriaValue) {
            $productSearchAdapter->setSearchCriterion($searchCriteriaFieldName, $searchCriteriaValue);
		}

        $productSearchAdapter->setNumPerPage($this->outputDefinition['overviewPagination'] ?: 0);

        $productSearchAdapter->setCurrentPage($this->currentPage);

		$sortingDefinition = $this->outputDefinition['overviewSorting'];
		if ($this->outputDefinition['overviewUserSorting'] == 'yes' && isset($_SESSION['lsShop']['userSortingDefinition'][$this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID])) {
			$sortingDefinition = $_SESSION['lsShop']['userSortingDefinition'][$this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID];
		}

		$sortingField = 'title';
		$sortingDirection = 'ASC';

		if ($sortingDefinition) {
			$tmpSplitSortingDefinition = explode('_sortDir_', $sortingDefinition);
			if ($tmpSplitSortingDefinition[0] && $tmpSplitSortingDefinition[1]) {
				$sortingField = $tmpSplitSortingDefinition[0];
				$sortingDirection = $tmpSplitSortingDefinition[1];
			}
		}

		$arrSortingDefinition = array(
			0 => array('field' => $sortingField, 'direction' => $sortingDirection)
		);

		/*
		 * MerconisCache: cache the final parsed HTML of the product list based on all
		 * input parameters and UI-affecting settings to ensure correct variation.
		 * Try a quick cache hit before running the expensive search/rendering.
		 */
		$__handle = null;
		$__container = System::getContainer();
		$__registry = $__container->has(\LeadingSystems\ContaoCacheBundle\Cache\HandlerRegistry::class) ? $__container->get(\LeadingSystems\ContaoCacheBundle\Cache\HandlerRegistry::class) : null;
		$__handler = $__registry?->getHandler('merconis.fragment');
		if ($__handler) {
			$__ttl = max(0, (int) ($GLOBALS['TL_CONFIG']['ls_shop_searchCacheLifetimeSec'] ?? 60));
			$__tags = array(
				'ns' => 'merconis.product_list.html',
				'productListID' => $this->productListID,
				'mode' => $this->mode,
				'outputDefinition' => array(
					'id' => $this->outputDefinition['outputDefinitionID'] ?? null,
					'mode' => $this->outputDefinition['outputDefinitionMode'] ?? null,
					'overviewPagination' => $this->outputDefinition['overviewPagination'] ?? 0,
					'overviewUserSorting' => $this->outputDefinition['overviewUserSorting'] ?? null,
					'overviewUserSortingFields' => $this->outputDefinition['overviewUserSortingFields'] ?? null
				),
				'allowUserSorting' => ($this->outputDefinition['overviewUserSorting'] == 'yes' && !count($this->fixedSorting)) ? true : false,
				'pagination' => array(
					'currentPage' => $this->currentPage,
					'perPage' => $this->outputDefinition['overviewPagination'] ?? 0,
					'maxPaginationLinks' => $GLOBALS['TL_CONFIG']['maxPaginationLinks'] ?? null
				),
				'sorting' => $arrSortingDefinition,
				'fixedSorting' => $this->fixedSorting,
				'arrSearchCriteria' => $this->arrSearchCriteria,
				'blnUseFilter' => $this->blnUseFilter,
				'filterCriteria' => $this->blnUseFilter ? ($_SESSION['lsShop']['filter']['criteria'] ?? null) : null,
				'filterModeSettingsByAttributes' => $this->blnUseFilter ? ($_SESSION['lsShop']['filter']['filterModeSettingsByAttributes'] ?? null) : null,
				'filterModeSettingsByFlexContentsLI' => $this->blnUseFilter ? ($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLI'] ?? null) : null,
				'filterModeSettingsByFlexContentsLD' => $this->blnUseFilter ? ($_SESSION['lsShop']['filter']['filterModeSettingsByFlexContentsLD'] ?? null) : null,
				'language' => ($GLOBALS['TL_LANGUAGE'] ?? null),
				'outputPriceType' => ls_shop_generalHelper::getOutputPriceType(),
				'checkVATID' => ls_shop_generalHelper::checkVATID(),
				'customerCountry' => ls_shop_generalHelper::getCustomerCountry(),
				'customerGroupId' => (ls_shop_generalHelper::getGroupSettings4User()['id'] ?? null),
				'lastBackendDataChange' => $GLOBALS['TL_CONFIG']['ls_shop_lastBackendDataChange'] ?? 0,
				'maxNumProducts' => $this->maxNumProducts,
				'noOutputIfMoreThanMaxResults' => $this->noOutputIfMoreThanMaxResults,
				'blnIsFrontendSearch' => $this->blnIsFrontendSearch
			);

			$__handle = $__handler->create($__ttl, $__tags);
			list($__hit, $__payload) = $__handle->getValueOrStart();

            /*
             * Do me! Actually activate caching only if a solution for handling
             *  user specific prices is implemented. Until then: Deactivate!
             */
			if (false && $__hit) {
				if (is_array($__payload) && isset($__payload['html'])) {
					if (!empty($__payload['blnUseFilter'])) {
						if (!empty($__payload['criteriaToUseInFilterFormHasBeenSet'])) {
							$GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'] = true;
						}
						if (array_key_exists('arrCriteriaToUseInFilterForm', $__payload)) {
							$_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm'] = $__payload['arrCriteriaToUseInFilterForm'];
						}
						if (array_key_exists('criteriaToActuallyFilterWith', $__payload)) {
							$_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'] = $__payload['criteriaToActuallyFilterWith'];
						}
						if (array_key_exists('matchedProducts', $__payload)) {
							$_SESSION['lsShop']['filter']['matchedProducts'] = $__payload['matchedProducts'];
						}
						if (array_key_exists('matchedVariants', $__payload)) {
							$_SESSION['lsShop']['filter']['matchedVariants'] = $__payload['matchedVariants'];
						}
						if (array_key_exists('matchEstimates', $__payload)) {
							$_SESSION['lsShop']['filter']['matchEstimates'] = $__payload['matchEstimates'];
						}
						if (array_key_exists('relevantProducerSet', $__payload)) {
							$_SESSION['lsShop']['filter']['relevantProducerSet'] = $__payload['relevantProducerSet'];
						}
						if (array_key_exists('relevantAttributeValueSet', $__payload)) {
							$_SESSION['lsShop']['filter']['relevantAttributeValueSet'] = $__payload['relevantAttributeValueSet'];
						}
						if (array_key_exists('attributeRelevanceCounts', $__payload)) {
							$_SESSION['lsShop']['filter']['attributeRelevanceCounts'] = $__payload['attributeRelevanceCounts'];
						}
					}
					$__currentToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
					$__htmlOut = preg_replace_callback(
						'/<input\s+[^>]*\bname=("|\')REQUEST_TOKEN\1[^>]*>/i',
						function ($m) use ($__currentToken) {
							$tag = $m[0];
							if (preg_match('/\bvalue=("|\')[^"\']*\1/i', $tag)) {
								$tag = preg_replace('/\bvalue=("|\')[^"\']*\1/i', 'value="'.htmlspecialchars($__currentToken, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5).'"', $tag);
							} else {
								$tag = rtrim($tag, '>');
								$tag .= ' value="'.htmlspecialchars($__currentToken, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5).'">';
							}
							return $tag;
						},
						(string) $__payload['html']
					);
					return $__htmlOut;
				}
			}
		}

        $productSearchAdapter->setSortingCriteria($arrSortingDefinition);

        $productSearchAdapter->setFixedSorting($this->fixedSorting);


		###
		#.
		if ($this->maxNumProducts > 0) {
            $productSearchAdapter->setTruncateResultsIfMoreThan($this->maxNumProducts);
		}

		if($this->noOutputIfMoreThanMaxResults) {
            $productSearchAdapter->setCancelSearchIfMoreThanTruncateLimit(true);
		}
		#.
		###

        $productSearchAdapter->search();

        $arrProducts = $productSearchAdapter->getProductResultsCurrentPage();

        if ($this->blnUseFilter) {
            $_SESSION['lsShop']['filter']['productsCurrentlyDisplayed'] = $arrProducts;
        }

        $this->numProducts = $productSearchAdapter->getNumProductsUnfiltered();
		
		if ($this->blnIsFrontendSearch) {
			if (isset($GLOBALS['MERCONIS_HOOKS']['afterSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterSearch'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['afterSearch'] as $mccb) {
					$objMccb = System::importStatic($mccb[0]);
					$arrProducts = $objMccb->{$mccb[1]}($this->arrSearchCriteria, $arrProducts);
				}
			}
		}
		
		###
		#.
		
		if ($this->maxNumProducts > 0 && $this->maxNumProducts < $this->numProducts) {
			$this->blnIsTruncated = true;
		}
		#.
		###
				
		if (isset($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutput']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutput'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['beforeProductlistOutput'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$arrProducts = $objMccb->{$mccb[1]}($this->productListID, $arrProducts);
			}
		}

		/*
		 * Ende Durchführen der Suche
		 */

		if ((!is_array($arrProducts) || !count($arrProducts)) && (!$this->blnUseFilter || !$productSearchAdapter->hasUnmatchedProducts())) {
			return '';
		}
		
		$objTemplate = new FrontendTemplate('productList');

        $objTemplate->filterUI = $this->blnUseFilter ? $productSearchAdapter->getFilterUI() : '';

		$objTemplate->blnUseFilter = $this->blnUseFilter;

		$objTemplate->blnNotAllProductsMatchFilter = $productSearchAdapter->hasUnmatchedProducts();

		$objTemplate->numProductsNotMatching = $productSearchAdapter->getNumUnmatchedProducts();

		$objTemplate->numProductsBeforeFilter = $productSearchAdapter->getNumProductsUnfiltered();

		$obj_paginationTemplate = new FrontendTemplate('merconisPagination');
		$obj_paginationTemplate->productListID = $this->productListID;

        //		$objPagination = new \Pagination($objProductSearch->numResultsComplete, $this->outputDefinition['overviewPagination'], $GLOBALS['TL_CONFIG']['maxPaginationLinks'], 'page_'.$this->productListID, $obj_paginationTemplate);
		$objPagination = new Pagination($productSearchAdapter->getNumResultsComplete(), $this->outputDefinition['overviewPagination'], $GLOBALS['TL_CONFIG']['maxPaginationLinks'], 'page_'.$this->productListID, $obj_paginationTemplate);

		$paginationHTML = $objPagination->generate(' ');
				
		$objTemplate->pagination = $paginationHTML;
		
		$objTemplate->allowUserSorting = $this->outputDefinition['overviewUserSorting'] == 'yes' && !count($this->fixedSorting) ? true : false;
		
		System::loadLanguageFile('tl_ls_shop_output_definitions');
		
		$objTemplate->identifyCorrespondingOutputDefinition = $this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID;

		$obj_FlexWidget_sorting = new FlexWidget(
			array(
				'str_uniqueName' => 'userSortingSelection',
				'bln_multipleWidgetsWithSameNameAllowed' => true,
				'str_template' => 'ls_flexWidget_defaultSelect',
				'arr_moreData' => array(
					'arr_options' => $this->outputDefinition['overviewUserSortingFields']
				),
				'str_allowedRequestMethod' => 'post',
				'var_value' => ($_SESSION['lsShop']['userSortingDefinition'][$this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID] ?? null) ?: $this->outputDefinition['overviewSorting']
			)
		);

		$objTemplate->fflSorting = $obj_FlexWidget_sorting->getOutput();

		$productOutput = '';
		
		$count = 0;
		$numProducts = count($arrProducts);
		
		/*
		 * Unset the position counter so that counting restarts with each product list.
		 */
		unset($GLOBALS['merconis_globals']['productNrInOrder']);
		
		foreach ($arrProducts as $productID) {
			$count++;
			$additionalClass = '';
			if ($count == 1) {
				$additionalClass = 'first';
			}

			if ($count == $numProducts) {
				$additionalClass = 'last';
			}
			
			$objProductOutput = new ls_shop_productOutput($productID, 'overview', '', $this->mode, $additionalClass, $this->blnUseFilter);
			$productOutput .= $objProductOutput->parseOutput();
		}
		
		$objTemplate->products = $productOutput;
		$objTemplate->productListID = $this->productListID;
		
		$__html = $objTemplate->parse();
			if ($__handle) {
			$__payloadToStore = array('html' => $__html);
			if ($this->blnUseFilter) {
				$__payloadToStore['blnUseFilter'] = true;
				$__payloadToStore['criteriaToUseInFilterFormHasBeenSet'] = isset($GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet']) && $GLOBALS['merconis_globals']['criteriaToUseInFilterFormHasBeenSet'];
				$__payloadToStore['arrCriteriaToUseInFilterForm'] = $_SESSION['lsShop']['filter']['arrCriteriaToUseInFilterForm'] ?? null;
				$__payloadToStore['criteriaToActuallyFilterWith'] = $_SESSION['lsShop']['filter']['criteriaToActuallyFilterWith'] ?? null;
				$__payloadToStore['matchedProducts'] = $_SESSION['lsShop']['filter']['matchedProducts'] ?? null;
				$__payloadToStore['matchedVariants'] = $_SESSION['lsShop']['filter']['matchedVariants'] ?? null;
				$__payloadToStore['matchEstimates'] = $_SESSION['lsShop']['filter']['matchEstimates'] ?? null;
					$__payloadToStore['relevantProducerSet'] = $_SESSION['lsShop']['filter']['relevantProducerSet'] ?? null;
					$__payloadToStore['relevantAttributeValueSet'] = $_SESSION['lsShop']['filter']['relevantAttributeValueSet'] ?? null;
					$__payloadToStore['attributeRelevanceCounts'] = $_SESSION['lsShop']['filter']['attributeRelevanceCounts'] ?? null;
			}
			$__handle->storeValue($__payloadToStore);
		}
		return $__html;
	}
}