<?php

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\System;

class ModuleCrossSeller extends Module {
	public function generate() {
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS CrossSeller ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}
	
	public function compile() {
		$objCrossSeller = new ls_shop_cross_seller($this->ls_shop_cross_seller);
		$this->Template = new FrontendTemplate('cte_crossSeller');
		$this->Template->output = $objCrossSeller->parseCrossSeller();
	}
}