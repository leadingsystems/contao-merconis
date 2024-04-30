<?php

namespace Merconis\Core;

use LeadingSystems\Helpers\FlexWidget;

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
					$objCrossSeller = \Database::getInstance()->prepare("
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
		
		$this->currentPage = \Input::get('page_'.$this->productListID) ? \Input::get('page_'.$this->productListID) : 1;
		
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
				\Input::post('FORM_SUBMIT') && \Input::post('FORM_SUBMIT') == 'userSorting'
			&&	\Input::post('identifyCorrespondingOutputDefinition') == $this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID
		) {
			$_SESSION['lsShop']['userSortingDefinition'][$this->outputDefinition['outputDefinitionID'].'-'.$this->outputDefinition['outputDefinitionMode'].'-'.$this->productListID] = html_entity_decode(\Input::post('userSortingSelection'));
			\Controller::redirect(\Environment::get('request'));
		}

		/*
		 * Durchführen der Suche
		 */
		if ($this->blnIsFrontendSearch) {
			if (isset($GLOBALS['MERCONIS_HOOKS']['beforeSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeSearch'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['beforeSearch'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
					$this->arrSearchCriteria = $objMccb->{$mccb[1]}($this->arrSearchCriteria);
				}
			}
		}
		
		$objProductSearch = new ls_shop_productSearcher($this->blnUseFilter, $this->productListID);
		
		foreach ($this->arrSearchCriteria as $searchCriteriaFieldName => $searchCriteriaValue) {
			$objProductSearch->setSearchCriterion($searchCriteriaFieldName, $searchCriteriaValue);
		}

		$objProductSearch->numPerPage = $this->outputDefinition['overviewPagination'] ? $this->outputDefinition['overviewPagination'] : 0;
		$objProductSearch->currentPage = $this->currentPage;

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
		
		$objProductSearch->sorting = $arrSortingDefinition;
		$objProductSearch->fixedSorting = $this->fixedSorting;
		
		
		###
		#.
		if ($this->maxNumProducts > 0) {
			$objProductSearch->truncateResultsIfMoreThan = $this->maxNumProducts;
		}
		
		if($this->noOutputIfMoreThanMaxResults) {
			$objProductSearch->cancelSearchIfMoreThanTruncateLimit = true;
		}
		#.
		###
		
		$objProductSearch->search();
		$arrProducts = $objProductSearch->productResultsCurrentPage;

        if ($this->blnUseFilter) {
            $_SESSION['lsShop']['filter']['productsCurrentlyDisplayed'] = $arrProducts;
        }

		$this->numProducts = $objProductSearch->numProductsBeforeFilter;
		
		if ($this->blnIsFrontendSearch) {
			if (isset($GLOBALS['MERCONIS_HOOKS']['afterSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterSearch'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['afterSearch'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
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
				$objMccb = \System::importStatic($mccb[0]);
				$arrProducts = $objMccb->{$mccb[1]}($this->productListID, $arrProducts);
			}
		}

		/*
		 * Ende Durchführen der Suche
		 */

		if ((!is_array($arrProducts) || !count($arrProducts)) && (!$this->blnUseFilter || !$objProductSearch->blnNotAllProductsMatch)) {
			return '';
		}
		
		$objTemplate = new \FrontendTemplate('productList');
		
		$objTemplate->blnUseFilter = $this->blnUseFilter;
		$objTemplate->blnNotAllProductsMatchFilter = $objProductSearch->blnNotAllProductsMatch;
		$objTemplate->numProductsNotMatching = $objProductSearch->numProductsNotMatching;
		$objTemplate->numProductsBeforeFilter = $objProductSearch->numProductsBeforeFilter;

		$obj_paginationTemplate = new \FrontendTemplate('merconisPagination');
		$obj_paginationTemplate->productListID = $this->productListID;
		$objPagination = new \Pagination($objProductSearch->numResultsComplete, $this->outputDefinition['overviewPagination'], $GLOBALS['TL_CONFIG']['maxPaginationLinks'], 'page_'.$this->productListID, $obj_paginationTemplate);
		$paginationHTML = $objPagination->generate(' ');
				
		$objTemplate->pagination = $paginationHTML;
		
		$objTemplate->allowUserSorting = $this->outputDefinition['overviewUserSorting'] == 'yes' && !count($this->fixedSorting) ? true : false;
		
		\System::loadLanguageFile('tl_ls_shop_output_definitions');
		
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
		
		return $objTemplate->parse();
	}
}