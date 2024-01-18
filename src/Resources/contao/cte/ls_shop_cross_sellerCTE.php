<?php

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\System;

class ls_shop_cross_sellerCTE extends ContentElement {

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'cte_crossSeller';

	public function generate() {
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS CrossSeller ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}

	/**
	 * Generate module
	 */
	protected function compile() {
		$objCrossSeller = new ls_shop_cross_seller($this->lsShopCrossSeller);
		$this->Template->output = $objCrossSeller->parseCrossSeller();
	}
}