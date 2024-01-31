<?php

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\Pagination;
use Contao\SelectMenu;
use Contao\StringUtil;
use Contao\System;

class ModuleMyOrders extends Module {
	protected $intDefaultNumPerPage = 10;
	protected $strDefaultSorting = 'orderDateUnixTimestamp';
	protected $strDefaultSortingDirection = 'DESC';
	
	public function generate() {
		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
			$this->import('FrontendUser', 'User');
		}
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS My Orders ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}
	
	public function compile() {
		$this->strTemplate = $this->ls_shop_myOrders_template;
		$this->Template = new FrontendTemplate($this->strTemplate);
		$this->Template->arrOrders = array();
		
		/*
		 * Get the user's order from the database
		 */
		if (!System::getContainer()->get('contao.security.token_checker')->hasFrontendUser() || !$this->User->id) {
			return;
		}
				
		$objOrdersAll = Database::getInstance()->prepare("
			SELECT		`orderIdentificationHash`
			FROM		`tl_ls_shop_orders`
			WHERE		`customerNr` = ?
			ORDER BY	`orderDateUnixTimestamp` ASC
		")
		->execute($this->User->id);
		
		if (!isset($_SESSION['lsShop']['myOrders']['sorting'])) {
			$_SESSION['lsShop']['myOrders']['sorting'] = $this->strDefaultSorting;
		}
		
		if (!isset($_SESSION['lsShop']['myOrders']['sortingDirection'])) {
			$_SESSION['lsShop']['myOrders']['sortingDirection'] = $this->strDefaultSortingDirection;
		}
		
		
		if (!is_array($this->ls_shop_myOrders_sortingOptions)) {
			$this->ls_shop_myOrders_sortingOptions = StringUtil::deserialize($this->ls_shop_myOrders_sortingOptions, true);
		}
		
		if (!is_array($this->ls_shop_myOrders_sortingOptions) || !count($this->ls_shop_myOrders_sortingOptions)) {
			$this->Template->useSortingWidget = false;
		} else {
			/*
			 * Create the sorting options widget
			 */
			$this->Template->useSortingWidget = true;
			$objWidgetSorting = new SelectMenu();
			$objWidgetSorting->name = 'sorting';

			$tmpArrSortingOptions = array();
			foreach ($this->ls_shop_myOrders_sortingOptions as $sortingOption) {
				$tmpArrSortingOptions[] = array('label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['orderSortingOptions'][$sortingOption], 'value' => $sortingOption);
			}
			$objWidgetSorting->options = $tmpArrSortingOptions;
			
			$objWidgetSorting->value = $_SESSION['lsShop']['myOrders']['sorting'];
			$this->Template->fflSorting = $objWidgetSorting->generate();
			
			/*
			 * Create the sorting directions widget
			 */
			$objWidgetSortingDirection = new SelectMenu();
			$objWidgetSortingDirection->name = 'sortingDirection';
			$objWidgetSortingDirection->options = array(array('label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText093'], 'value' => 'ASC'), array('label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText094'], 'value' => 'DESC'));
			$objWidgetSortingDirection->value = $_SESSION['lsShop']['myOrders']['sortingDirection'];
			$this->Template->fflSortingDirection = $objWidgetSortingDirection->generate();
		}		

		
		if (!isset($_SESSION['lsShop']['myOrders']['numPerPage'])) {
			$_SESSION['lsShop']['myOrders']['numPerPage'] = $this->intDefaultNumPerPage;
		}
		
		$objWidgetNumPerPage = new SelectMenu();
		$objWidgetNumPerPage->name = 'numPerPage';
		$objWidgetNumPerPage->options = array(array('label' => 1, 'value' => 1), array('label' => 2, 'value' => 2), array('label' => 3, 'value' => 3), array('label' => 10, 'value' => 10), array('label' => 20, 'value' => 20), array('label' => 50, 'value' => 50), array('label' => 100, 'value' => 100));
		$objWidgetNumPerPage->value = $_SESSION['lsShop']['myOrders']['numPerPage'];
		$this->Template->fflNumPerPage = $objWidgetNumPerPage->generate();
		
		if (Input::post('FORM_SUBMIT') == 'myOrders_numPerPage') {
			$_SESSION['lsShop']['myOrders']['numPerPage'] = Input::post('numPerPage') ? Input::post('numPerPage') : $this->intDefaultNumPerPage;
			$_SESSION['lsShop']['myOrders']['sorting'] = Input::post('sorting') ? Input::post('sorting') : $this->strDefaultSorting;
			$_SESSION['lsShop']['myOrders']['sortingDirection'] = Input::post('sortingDirection') ? Input::post('sortingDirection') : $this->strDefaultSortingDirection;
			$this->redirect(ls_shop_generalHelper::getUrl(false, array('page')));
		}

		$objPagination = new Pagination($objOrdersAll->numRows, $_SESSION['lsShop']['myOrders']['numPerPage'], 7, 'page', new FrontendTemplate('merconisPagination'));
		$this->Template->pagination = $objPagination->generate();
		$this->Template->request = StringUtil::ampersand(Environment::get('request'), true);
		
		$currentPageOffset = Input::get('page') ? Input::get('page') - 1 : 0;
		
		/*
		 * If we sort by status the value to sort is not actually the value that is held in the database field
		 * but it's language representation which is defined in the language array. Therefore we have to create
		 * a select statement that uses a temporary language field for each status field which holds the
		 * language value and which is used for sorting.
		 */
		$statusStatement01 = "WHEN `status01` = '' THEN ''"."\r\n";
		$statusStatement02 = "WHEN `status02` = '' THEN ''"."\r\n";
		$statusStatement03 = "WHEN `status03` = '' THEN ''"."\r\n";
		$statusStatement04 = "WHEN `status04` = '' THEN ''"."\r\n";
		$statusStatement05 = "WHEN `status05` = '' THEN ''"."\r\n";
		foreach ($GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'] as $statusKey => $statusLanguageName) {
			$statusStatement01 .= "WHEN `status01` = '".$statusKey."' THEN '".$statusLanguageName."'"."\r\n";
			$statusStatement02 .= "WHEN `status02` = '".$statusKey."' THEN '".$statusLanguageName."'"."\r\n";
			$statusStatement03 .= "WHEN `status03` = '".$statusKey."' THEN '".$statusLanguageName."'"."\r\n";
			$statusStatement04 .= "WHEN `status04` = '".$statusKey."' THEN '".$statusLanguageName."'"."\r\n";
			$statusStatement05 .= "WHEN `status05` = '".$statusKey."' THEN '".$statusLanguageName."'"."\r\n";
		}
		
		$objOrders = Database::getInstance()->prepare("
			SELECT		`orderIdentificationHash`,
						CASE\r\n".$statusStatement01." ELSE '' END AS `status01_language`,
						CASE\r\n".$statusStatement02." ELSE '' END AS `status02_language`,
						CASE\r\n".$statusStatement03." ELSE '' END AS `status03_language`,
						CASE\r\n".$statusStatement04." ELSE '' END AS `status04_language`,
						CASE\r\n".$statusStatement05." ELSE '' END AS `status05_language`
			FROM		`tl_ls_shop_orders`
			WHERE		`customerNr` = ?
			ORDER BY	`".$_SESSION['lsShop']['myOrders']['sorting'].(in_array($_SESSION['lsShop']['myOrders']['sorting'], array('status01', 'status02', 'status03', 'status04', 'status05')) ? '_language' : '')."` ".$_SESSION['lsShop']['myOrders']['sortingDirection']."
		")
		->limit($_SESSION['lsShop']['myOrders']['numPerPage'], $currentPageOffset * $_SESSION['lsShop']['myOrders']['numPerPage'])
		->execute($this->User->id);
		
		$arrOrders = array();
		while($objOrders->next()) {
			$arrOrder = ls_shop_generalHelper::getOrder($objOrders->orderIdentificationHash, 'orderIdentificationHash');
			$arrOrder['linkToDetails'] = ls_shop_languageHelper::getLanguagePage('ls_shop_myOrderDetailsPages').(preg_match('/\?/', ls_shop_languageHelper::getLanguagePage('ls_shop_myOrderDetailsPages')) ? '&' : '?').'oih='.$arrOrder['orderIdentificationHash'];
			$arrOrders[] = $arrOrder;
		}
		$this->Template->arrOrders = $arrOrders;		
	}
}
?>