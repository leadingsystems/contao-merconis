<?php

namespace Merconis\Core;

use Contao\System;

class ModuleProductOverview extends \Module {
	public function generate() {
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS ProductOverview ###';
			return $objTemplate->parse();
		}
		
		if (\Input::get('product')) {
			return '';
		}
		
		return parent::generate();
	}
	
	public function compile() {
		$objProductList = new ls_shop_productList(
		    '',
            $this->ls_shop_productOverviewShowProductsFromSubordinatePages,
            $this->ls_shop_productOverviewConsiderUnpublishedPages,
            $this->ls_shop_productOverviewConsiderHiddenPages,
            $this->ls_shop_productOverviewStartLevel,
            $this->ls_shop_productOverviewStopLevel
        );
		$this->Template = new \FrontendTemplate('productOverview');
		$this->Template->output = $objProductList->parseOutput();
	}
}
?>