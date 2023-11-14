<?php

namespace Merconis\Core;

use Contao\StringUtil;

class ls_shop_beModule_stockManagement extends \BackendModule {
	protected $strTemplate = 'beModule_stockManagement';
	protected $intDefaultNumPerPage = 10;
	protected $defaultSortingField = 'title';
	protected $arrFieldsToShow = array('id','lsShopProductCode','title','published');

	protected function compile() {
		$this->loadLanguageFile('tl_ls_shop_product');
		$this->loadLanguageFile('be_stockManagement');

		$this->Template->request = StringUtil::ampersand(\Environment::get('request'), true);

		$objWidgets = array();
		$widgets = array();
		
		/*
		 * ANFANG Verarbeiten einer per POST erhaltenen changeStock-Änderungsanforderung
		 */
		if (\Input::post('FORM_SUBMIT') == 'changeStock') {
			$quantity = \Input::post('changeStockQuantity');
			
			if (!preg_match('/^(\+|-)?[0-9]*\.?[0-9]*$/', $quantity)) {
				// Fehlerbehandlung, der angegebene Wert nicht gültig ist
				$GLOBALS['merconis_globals']['stockManagement']['errorMsg'][\Input::post('productVariantID')] = $GLOBALS['TL_LANG']['be_stockManagement']['text017'];
			} else {
				$blnDoNotCalculate = false;
				
				/*
				 * Gibt es kein führendes Plus- oder Minuszeichen (beginnt die Quantity also direkt mit einer Zahl),
				 * so wird das doNotCalculate-Flag auf true gesetzt, weil dann der fixe Wert gesetzt werden soll
				 */
				if (preg_match('/^[0-9]/', $quantity)) {
					$blnDoNotCalculate = true;
				}
				
				// Ein eventuell führendes Pluszeichen entfernen, da es nicht benötigt wird
				$quantity = preg_replace('/^\+/', '', $quantity);

				// Ansonsten Änderung durchführen
				$objChangeStockProduct = ls_shop_generalHelper::getObjProduct(\Input::post('productVariantID'), __METHOD__);
				$blnStockChangeCarriedOut = $objChangeStockProduct->changeStock($quantity, $blnDoNotCalculate, true);
				
				if ($blnStockChangeCarriedOut) {
					$_SESSION['lsShop']['stockManagement']['successMsg'][\Input::post('productVariantID')] =  $GLOBALS['TL_LANG']['be_stockManagement']['text018'];
					$this->reload();
				} else {
					$GLOBALS['merconis_globals']['stockManagement']['errorMsg'][\Input::post('productVariantID')] =  $GLOBALS['TL_LANG']['be_stockManagement']['text019'];
				}
			}
		}
		/*
		 * ENDE
		 */
		
		/*
		 * Erzeugen der Widgets für die Suchfelder
		 * sowie Verarbeitung evtl. übergebener Werte
		 */
		$objWidgets['title'] = new \TextField();
		$objWidgets['title']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['title'][0];
		$objWidgets['title']->id = 'title';
		$objWidgets['title']->name = 'title';
		$objWidgets['title']->value = \Input::post('title') ? \Input::post('title') : (isset($_SESSION['lsShop']['beModule_productSearch']['values']['title']) ? $_SESSION['lsShop']['beModule_productSearch']['values']['title'] : '');

		$objWidgets['productCode'] = new \TextField();
		$objWidgets['productCode']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductCode'][0];
		$objWidgets['productCode']->id = 'productCode';
		$objWidgets['productCode']->name = 'productCode';
		$objWidgets['productCode']->value = \Input::post('productCode') ? \Input::post('productCode') : (isset($_SESSION['lsShop']['beModule_productSearch']['values']['lsShopProductCode']) ? $_SESSION['lsShop']['beModule_productSearch']['values']['lsShopProductCode'] : '');

		$objWidgets['keywords'] = new \TextField();
		$objWidgets['keywords']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['keywords'][0];
		$objWidgets['keywords']->id = 'keywords';
		$objWidgets['keywords']->name = 'keywords';
		$objWidgets['keywords']->value = \Input::post('keywords') ? \Input::post('keywords') : (isset($_SESSION['lsShop']['beModule_productSearch']['values']['keywords']) ? $_SESSION['lsShop']['beModule_productSearch']['values']['keywords'] : '');

		$objWidgets['pages'] = new \SelectMenu();
		$objWidgets['pages']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['pages'][0];
		$objWidgets['pages']->id = 'pages';
		$objWidgets['pages']->name = 'pages';
		$objWidgets['pages']->options = ls_shop_generalHelper::getMainLanguagePagesAsOptions(true);
		$objWidgets['pages']->value = \Input::post('pages') ? \Input::post('pages') : (isset($_SESSION['lsShop']['beModule_productSearch']['values']['pages']) ? $_SESSION['lsShop']['beModule_productSearch']['values']['pages'] : '');

		if (\Input::post('FORM_SUBMIT') == 'beModule_productSearch') {
			$_SESSION['lsShop']['beModule_productSearch']['values']['title'] = \Input::post('title') ? \Input::post('title') : '';
			$_SESSION['lsShop']['beModule_productSearch']['values']['keywords'] = \Input::post('keywords') ? \Input::post('keywords') : '';
			$_SESSION['lsShop']['beModule_productSearch']['values']['lsShopProductCode'] = \Input::post('productCode') ? \Input::post('productCode') : '';
			$_SESSION['lsShop']['beModule_productSearch']['values']['pages'] = \Input::post('pages') ? \Input::post('pages') : '';
			
			$this->redirect(ls_shop_generalHelper::getUrl(false, array('page')));
		}

		$widgets['title']['widget'] = $objWidgets['title']->parse();
		$widgets['productCode']['widget'] = $objWidgets['productCode']->parse();
		$widgets['keywords']['widget'] = $objWidgets['keywords']->parse();
		$widgets['pages']['widget'] = $objWidgets['pages']->parse();
		
		$this->Template->widgets = $widgets;
		/*
		 * Ende Erzeugen der Widgets
		 */
		
		/*
		 * Sortierung
		 */
		$cleanRequest = ls_shop_generalHelper::getUrl(false, array('page', 'sortingField'));
		
		if (!isset($_SESSION['lsShop']['beModule_productSearch']['sorting'])) {
			$_SESSION['lsShop']['beModule_productSearch']['sorting'] = array(
				'field' => $this->defaultSortingField,
				'direction' => 'ASC'
			);
		}
		
		if (\Input::get('sortingField')) {
			$_SESSION['lsShop']['beModule_productSearch']['sorting'] = array(
				'field' => \Input::get('sortingField'),
				'direction' => $_SESSION['lsShop']['beModule_productSearch']['sorting']['field'] == \Input::get('sortingField') ? ($_SESSION['lsShop']['beModule_productSearch']['sorting']['direction'] == 'DESC' ? 'ASC' : 'DESC') : 'ASC'
			);
			$this->redirect(ls_shop_generalHelper::getUrl(false, array('sortingField')));
		}

		$sortingImageClasses = array();
		$sortingHrefs = array();
		foreach ($this->arrFieldsToShow as $fieldToShow) {
			$sortingImageClasses[$fieldToShow] = $_SESSION['lsShop']['beModule_productSearch']['sorting']['field'] == $fieldToShow ? ($_SESSION['lsShop']['beModule_productSearch']['sorting']['direction'] == 'ASC' ? 'sorting_asc' : 'sorting_desc') : 'sorting_asc_inactive';
			$sortingHrefs[$fieldToShow] = $cleanRequest.'&sortingField='.$fieldToShow;
		}
		$this->Template->sortingImageClasses = $sortingImageClasses;
		$this->Template->sortingHrefs = $sortingHrefs;
		/*
		 * Ende Sortierung
		 */
		
		/*
		 * Durchführen der Suche
		 */
		$objProductSearch = new ls_shop_productSearcher();
		
		// Standardmäßig das Suchkriterium für published auf Wildcard setzen, damit der ProductSearcher auch unveröffentlichte Produkte findet
		$objProductSearch->setSearchCriterion('published', '%');
		
		if (is_array($_SESSION['lsShop']['beModule_productSearch']['values'])) {
			foreach ($_SESSION['lsShop']['beModule_productSearch']['values'] as $searchCriteriaFieldName => $searchCriteriaValue) {
				$objProductSearch->setSearchCriterion($searchCriteriaFieldName, $searchCriteriaValue);
			}
		}

		$objProductSearch->numPerPage = ($_SESSION['lsShop']['beModule_productSearch']['numPerPage'] ?? null) ? $_SESSION['lsShop']['beModule_productSearch']['numPerPage'] : $this->intDefaultNumPerPage;
		$objProductSearch->currentPage = \Input::get('page') ? \Input::get('page') : 1;

		if (is_array($_SESSION['lsShop']['beModule_productSearch']['sorting'])) {
			$objProductSearch->sorting = array($_SESSION['lsShop']['beModule_productSearch']['sorting']);
		}
		
		$objProductSearch->emptyFieldMatchesPerDefault = true;
		$objProductSearch->search();
		$arrProducts = $objProductSearch->productResultsCurrentPage;
		
		$this->Template->msgNumSearchResults = sprintf($objProductSearch->numResultsComplete == 1 ? $GLOBALS['TL_LANG']['be_stockManagement']['text011'] : $GLOBALS['TL_LANG']['be_stockManagement']['text012'], $objProductSearch->numResultsComplete);
		/*
		 * Ende Durchführen der Suche
		 */
		
		/*
		 * Pagination
		 */
		$objWidgetNumPerPage = new \SelectMenu();
		$objWidgetNumPerPage->name = 'numPerPage';
		$objWidgetNumPerPage->options = array(array('label' => 1, 'value' => 1), array('label' => 2, 'value' => 2), array('label' => 3, 'value' => 3), array('label' => 4, 'value' => 4), array('label' => 5, 'value' => 5), array('label' => 10, 'value' => 10), array('label' => 20, 'value' => 20), array('label' => 50, 'value' => 50), array('label' => 100, 'value' => 100));
		$objWidgetNumPerPage->value = ($_SESSION['lsShop']['beModule_productSearch']['numPerPage'] ?? null) ? $_SESSION['lsShop']['beModule_productSearch']['numPerPage'] : $this->intDefaultNumPerPage;
		$this->Template->fflNumPerPage = $objWidgetNumPerPage->generate();
		
		if (\Input::post('FORM_SUBMIT') == 'beModule_productSearch_numPerPage') {
			$_SESSION['lsShop']['beModule_productSearch']['numPerPage'] = \Input::post('numPerPage') ? \Input::post('numPerPage') : $this->intDefaultNumPerPage;
			$this->redirect(ls_shop_generalHelper::getUrl(false, array('page')));
		}
		
		$objPagination = new \Pagination($objProductSearch->numResultsComplete, isset($_SESSION['lsShop']['beModule_productSearch']['numPerPage']) ? $_SESSION['lsShop']['beModule_productSearch']['numPerPage'] : 10);
		$this->Template->pagination = $objPagination->generate();

		/*
		 * Ende Erzeugen der Pagination
		 */

		/*
		 * Erzeugen der Produktausgaben
		 */
		$arrProductsOutput = array();
		foreach ($arrProducts as $productID) {
			$objProductOutput = new ls_shop_productOutput($productID, '', 'template_productBackendOverview_04');
			$objProductOutput->obj_template->action = \Environment::get('request');
			$arrProductsOutput[$productID] = $objProductOutput->parseOutput();
		}
		
		$this->Template->arrProductsOutput = $arrProductsOutput;
	}
}