<?php

namespace Merconis\Core;

class ls_shop_beModule_productSearch extends \BackendModule
{
	protected $strTemplate = 'beModule_productSearch';
	protected $intDefaultNumPerPage = 10;
	protected $defaultSortingField = 'title';
	protected $arrFieldsToShow = array('id','lsShopProductCode','title','published');

	protected function compile() {
		\System::loadLanguageFile('be_productSearch');
		\System::loadLanguageFile('tl_ls_shop_product');
		$this->Template->request = ampersand(\Environment::get('request'), true);

		$objWidgets = array();
		$widgets = array();

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $arrLsShop =  $session->get('lsShop', []);
		
		/*
		 * Erzeugen der Widgets für die Suchfelder
		 * sowie Verarbeitung evtl. übergebener Werte
		 */
		$objWidgets['title'] = new \TextField();
		$objWidgets['title']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['title'][0];
		$objWidgets['title']->id = 'title';
		$objWidgets['title']->name = 'title';
		$objWidgets['title']->value = \Input::post('title') ? \Input::post('title') : (isset($arrLsShop['beModule_productSearch']['values']['title']) ? $arrLsShop['beModule_productSearch']['values']['title'] : '');

		$objWidgets['productCode'] = new \TextField();
		$objWidgets['productCode']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductCode'][0];
		$objWidgets['productCode']->id = 'productCode';
		$objWidgets['productCode']->name = 'productCode';
		$objWidgets['productCode']->value = \Input::post('productCode') ? \Input::post('productCode') : (isset($arrLsShop['beModule_productSearch']['values']['lsShopProductCode']) ? $arrLsShop['beModule_productSearch']['values']['lsShopProductCode'] : '');

		$objWidgets['keywords'] = new \TextField();
		$objWidgets['keywords']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['keywords'][0];
		$objWidgets['keywords']->id = 'keywords';
		$objWidgets['keywords']->name = 'keywords';
		$objWidgets['keywords']->value = \Input::post('keywords') ? \Input::post('keywords') : (isset($arrLsShop['beModule_productSearch']['values']['keywords']) ? $arrLsShop['beModule_productSearch']['values']['keywords'] : '');

		$objWidgets['pages'] = new \SelectMenu();
		$objWidgets['pages']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['pages'][0];
		$objWidgets['pages']->id = 'pages';
		$objWidgets['pages']->name = 'pages';
		$objWidgets['pages']->options = ls_shop_generalHelper::getMainLanguagePagesAsOptions(true);
		$objWidgets['pages']->value = \Input::post('pages') ? \Input::post('pages') : (isset($arrLsShop['beModule_productSearch']['values']['pages']) ? $arrLsShop['beModule_productSearch']['values']['pages'] : '');

		if (\Input::post('FORM_SUBMIT') == 'beModule_productSearch') {
            $arrLsShop['beModule_productSearch']['values']['title'] = \Input::post('title') ? \Input::post('title') : '';
            $arrLsShop['beModule_productSearch']['values']['keywords'] = \Input::post('keywords') ? \Input::post('keywords') : '';
            $arrLsShop['beModule_productSearch']['values']['lsShopProductCode'] = \Input::post('productCode') ? \Input::post('productCode') : '';
            $arrLsShop['beModule_productSearch']['values']['pages'] = \Input::post('pages') ? \Input::post('pages') : '';

            $session->set('lsShop', $arrLsShop);
			\Controller::redirect(ls_shop_generalHelper::getUrl(false, array('page')));
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
		
		if (!isset($arrLsShop['beModule_productSearch']['sorting'])) {
            $arrLsShop['beModule_productSearch']['sorting'] = array(
				'field' => $this->defaultSortingField,
				'direction' => 'ASC'
			);
		}
		
		if (\Input::get('sortingField')) {
            $arrLsShop['beModule_productSearch']['sorting'] = array(
				'field' => \Input::get('sortingField'),
				'direction' => $arrLsShop['beModule_productSearch']['sorting']['field'] == \Input::get('sortingField') ? ($arrLsShop['beModule_productSearch']['sorting']['direction'] == 'DESC' ? 'ASC' : 'DESC') : 'ASC'
			);
            $session->set('lsShop', $arrLsShop);
			\Controller::redirect(ls_shop_generalHelper::getUrl(false, array('sortingField')));
		}

		$sortingImageClasses = array();
		$sortingHrefs = array();
		foreach ($this->arrFieldsToShow as $fieldToShow) {
			$sortingImageClasses[$fieldToShow] = $arrLsShop['beModule_productSearch']['sorting']['field'] == $fieldToShow ? ($arrLsShop['beModule_productSearch']['sorting']['direction'] == 'ASC' ? 'sorting_asc' : 'sorting_desc') : 'sorting_asc_inactive';
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
		
		if (is_array($arrLsShop['beModule_productSearch']['values'] ?? null)) {
			foreach ($arrLsShop['beModule_productSearch']['values'] as $searchCriteriaFieldName => $searchCriteriaValue) {
				$objProductSearch->setSearchCriterion($searchCriteriaFieldName, $searchCriteriaValue);
			}
		}

		$objProductSearch->numPerPage = ($arrLsShop['beModule_productSearch']['numPerPage'] ?? null) ? $arrLsShop['beModule_productSearch']['numPerPage'] : $this->intDefaultNumPerPage;
		$objProductSearch->currentPage = \Input::get('page') ? \Input::get('page') : 1;

		if (is_array($arrLsShop['beModule_productSearch']['sorting'])) {
			$objProductSearch->sorting = array($arrLsShop['beModule_productSearch']['sorting']);
		}
		
		$objProductSearch->emptyFieldMatchesPerDefault = true;
		$objProductSearch->search();
		$arrProducts = $objProductSearch->productResultsCurrentPage;
		
		$this->Template->msgNumSearchResults = sprintf($objProductSearch->numResultsComplete == 1 ? $GLOBALS['TL_LANG']['be_productSearch']['text011'] : $GLOBALS['TL_LANG']['be_productSearch']['text012'], $objProductSearch->numResultsComplete);
		/*
		 * Ende Durchführen der Suche
		 */
		
		/*
		 * Pagination
		 */
		$objWidgetNumPerPage = new \SelectMenu();
		$objWidgetNumPerPage->name = 'numPerPage';
		$objWidgetNumPerPage->options = array(array('label' => 1, 'value' => 1), array('label' => 2, 'value' => 2), array('label' => 3, 'value' => 3), array('label' => 10, 'value' => 10), array('label' => 20, 'value' => 20), array('label' => 50, 'value' => 50), array('label' => 100, 'value' => 100));
		$objWidgetNumPerPage->value = ($arrLsShop['beModule_productSearch']['numPerPage'] ?? null) ? $arrLsShop['beModule_productSearch']['numPerPage'] : $this->intDefaultNumPerPage;
		$this->Template->fflNumPerPage = $objWidgetNumPerPage->generate();
		
		if (\Input::post('FORM_SUBMIT') == 'beModule_productSearch_numPerPage') {
            $arrLsShop['beModule_productSearch']['numPerPage'] = \Input::post('numPerPage') ? \Input::post('numPerPage') : $this->intDefaultNumPerPage;
            $session->set('lsShop', $arrLsShop);
			\Controller::redirect(ls_shop_generalHelper::getUrl(false, array('page')));
		}
		
		$objPagination = new \Pagination($objProductSearch->numResultsComplete, isset($arrLsShop['beModule_productSearch']['numPerPage']) ?$arrLsShop['beModule_productSearch']['numPerPage'] : 10);
		$this->Template->pagination = $objPagination->generate();

		/*
		 * Ende Erzeugen der Pagination
		 */

		/*
		 * Erzeugen der Produktausgaben
		 */
		$arrProductsOutput = array();
		foreach ($arrProducts as $productID) {
			$objProductOutput = new ls_shop_productOutput($productID, '', 'template_productBackendOverview_02');
			$objProductOutput->obj_template->mode = \Input::get('mode') ? \Input::get('mode') : '';
			$arrProductsOutput[$productID] = $objProductOutput->parseOutput();
		}
        $session->set('lsShop', $arrLsShop);
		$this->Template->arrProductsOutput = $arrProductsOutput;
	}
}