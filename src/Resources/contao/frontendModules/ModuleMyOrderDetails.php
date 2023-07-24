<?php

namespace Merconis\Core;

class ModuleMyOrderDetails extends \Module {
	public function generate() {
		if (\System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
			$this->import('FrontendUser', 'User');
		}
		if (TL_MODE == 'BE') {
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS My order details ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}
	
	public function compile() {
		$this->strTemplate = $this->ls_shop_myOrderDetails_template;
		$this->Template = new \FrontendTemplate($this->strTemplate);

		if (!\System::getContainer()->get('contao.security.token_checker')->hasFrontendUser() || !$this->User->id) {
			return;
		}
				

		$arrOrder = ls_shop_generalHelper::getOrder(\Input::get('oih'), 'orderIdentificationHash');
		if (!is_array($arrOrder) || !count($arrOrder)) {
			return false;
		}
		
		$this->Template->arrOrder = $arrOrder;
		$this->Template->linkToOverview = ls_shop_languageHelper::getLanguagePage('ls_shop_myOrdersPages');
	}
}
?>