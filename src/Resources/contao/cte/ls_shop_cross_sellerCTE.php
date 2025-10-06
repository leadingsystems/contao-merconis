<?php

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\System;
use LeadingSystems\MerconisBundle\Cache\MerconisCacheHandler;

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
		/** @var MerconisCacheHandler $cacheHandler */
		$cacheHandler = System::getContainer()->get(MerconisCacheHandler::class);
		$session = $cacheHandler->create(600, array('id' => (string) $this->lsShopCrossSeller));
        /** @var MerconisCacheHandler $cacheHandler */
        $cacheHandler = System::getContainer()->get(MerconisCacheHandler::class);
        $cache = $cacheHandler->createForRecipe('cross_seller', array('id' => (string) $this->lsShopCrossSeller), 600);
		list($hit, $value) = $cache->getValueOrStart();
		if (!$hit) {
			$value = (new ls_shop_cross_seller($this->lsShopCrossSeller))->parseCrossSeller();
			$cache->storeValue($value);
		}
		$this->Template->output = $value;
	}
}