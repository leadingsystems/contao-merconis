<?php

namespace Merconis\Core;

use Contao\FrontendTemplate;
use Contao\Input;
use Contao\System;

class ls_shop_productOutput
{
	public $ls_template = '';
	public $ls_productID = 0;
	public $ls_objProduct = null;
	public $ls_outputDefinition = array();
	public $ls_mode = false;
	public $ls_additionalClass = '';
	public $blnUseFilter = false;
	public $obj_template = null;
	
	public function __construct($productVariantID = 0, $mode = 'singleview', $templateToUse = '', $outputDefinitionMode = 'standard', $additionalClass = '', $blnUseFilter = false) {
		$this->ls_outputDefinition = ls_shop_generalHelper::getOutputDefinition(false, $outputDefinitionMode);
		$this->ls_mode = $mode;
		$this->ls_additionalClass = $additionalClass;
		$this->blnUseFilter = $blnUseFilter;
		
		$this->ls_objProduct = ls_shop_generalHelper::getObjProduct($productVariantID, __METHOD__);
		$this->ls_productID = $this->ls_objProduct->ls_ID;

		$this->handlePersistentVariantSelection();

		if (!$this->ls_objProduct->productExists) {
			$this->ls_template = 'template_notExistingProduct';
		} else {
			$this->ls_template = $this->ls_outputDefinition['overviewTemplate'];
	
			if (!$templateToUse) {
				if ($this->ls_mode == 'overview') {
					/* 
					 * Soll für die Darstellung in der Produktübersicht (ls_mode) die Detailansicht der Produkte verwendet werden,
					 * so wird hierfür das tatsächliche Detail-Template verwendet!
					 */
					if ($this->ls_template == 'template_productOverview_useDetailsTemplate') {
						$this->ls_template = $this->ls_objProduct->mainData['lsShopProductDetailsTemplate'];
					}
				} else if ($this->ls_mode == 'backendOverview') {
					$this->ls_template = 'template_productBackendOverview_01';
				} else {
					$this->ls_template = $this->ls_objProduct->mainData['lsShopProductDetailsTemplate'];
				}
			} else {
				$this->ls_template = $templateToUse;
			}			
		}		
		
		$this->prepareTemplate();
	}

	protected function handlePersistentVariantSelection() {
		if (!System::getContainer()->get('merconis.routing.scope')->isFrontend()) {
			return;
		}

        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop');

		if (!isset($session_lsShop['persistentVariantSelection']) || !is_array($session_lsShop['persistentVariantSelection'])) {
			$session_lsShop['persistentVariantSelection'] = array();
		}

		if (
			Input::get('selectVariantPersistently')
			&&	$this->ls_objProduct->_selectVariant(Input::get('selectVariantPersistently'))
		) {
			$session_lsShop['persistentVariantSelection'][$this->ls_productID] = Input::get('selectVariantPersistently');
		}

		else if (
			isset($session_lsShop['persistentVariantSelection'][$this->ls_productID]) && $session_lsShop['persistentVariantSelection'][$this->ls_productID]
		) {
			$this->ls_objProduct->_selectVariant($session_lsShop['persistentVariantSelection'][$this->ls_productID]);
		}
        $session->set('lsShop', $session_lsShop);
	}

	protected function prepareTemplate() {
		$this->obj_template = new FrontendTemplate($this->ls_template);
		$this->obj_template->objProduct = $this->ls_objProduct;
		
		$this->obj_template->blnUseFilter = $this->blnUseFilter;
		
		/*
		 * Ermitteln der Produktposition abhängig von der Anzahl der Spalten in der Darstellung
		 */
		$productPositionsClassString = '';
		if ($this->ls_mode == 'overview') {
			$GLOBALS['merconis_globals']['productNrInOrder'] = !isset($GLOBALS['merconis_globals']['productNrInOrder']) ? 1 : $GLOBALS['merconis_globals']['productNrInOrder'] + 1;
			for ($i = 2; $i <= 20; $i++) {
				if ($GLOBALS['merconis_globals']['productNrInOrder'] % $i == 0) {
					$productPositionsClassString .= 'productPosition_'.$i.'_in_row_last ';
				}		
				if (($GLOBALS['merconis_globals']['productNrInOrder'] - 1) % $i == 0 || $GLOBALS['merconis_globals']['productNrInOrder'] == 1) {
					$productPositionsClassString .= 'productPosition_'.$i.'_in_row_first ';
				}		
			}
			$productPositionsClassString .= 'productPosition_'.$GLOBALS['merconis_globals']['productNrInOrder'];
		}

		eval (pack('H*', '6966202869737365742824474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736e496e76616c6964275d292026262024474c4f42414c535b27544c5f434f4e464947275d5b276d6572636f6e69735f736e496e76616c6964275d29207b206563686f2027546869732073686f702069732063757272656e746c7920756e617661696c61626c652064756520746f206d6973636f6e66696775726174696f6e2e20506c6561736520636f6e746163742074686520737570706f7274207465616d20616e64207265706f727420746869732070726f626c656d2e273b20657869743b207d'));
		eval (pack('H*', '6966202869737365742824474c4f42414c535b27544c5f434f4e464947275d5b276772616365506572696f64446179734c656674275d292026262024474c4f42414c535b27544c5f434f4e464947275d5b276772616365506572696f64446179734c656674275d203c203129207b206563686f2027477261636520706572696f6420657870697265642e20506c6561736520636f6e74616374207468652073616c6573207465616d20746f20707572636861736520612076616c696420736f667477617265206c6963656e7365273b20657869743b207d'));

		$this->obj_template->productPositionsClassString = $productPositionsClassString.($this->ls_additionalClass ? ' '.$this->ls_additionalClass : '');
		
		/*
		 * Informationen über das Template ans Template übergeben
		 */
		$this->obj_template->productTemplate = $this->ls_template;
		$this->obj_template->productTemplateType = preg_replace('/_[^_]*$/siU', '', $this->ls_template);
		
		$this->obj_template->outputContext = isset($GLOBALS['lsShopProductViewContext']) ? $GLOBALS['lsShopProductViewContext'] : 'standard';

		if (isset($GLOBALS['MERCONIS_HOOKS']['prepareProductTemplate']) && is_array($GLOBALS['MERCONIS_HOOKS']['prepareProductTemplate'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['prepareProductTemplate'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$objMccb->{$mccb[1]}($this->obj_template, $this->ls_template, $this->ls_objProduct);
			}
		}		
	}
	
	public function parseOutput() {
        if (System::getContainer()->get('merconis.routing.scope')->isFrontend() && $this->ls_objProduct->_useGroupRestrictions) {
            $arr_groupSettings = ls_shop_generalHelper::getGroupSettings4User();
            if (!in_array($arr_groupSettings['id'], $this->ls_objProduct->_allowedGroups)) {
                /*
                 * In case a product which is not allowed is being requested for display, an html comment will be
                 *  delivered so that frontend users don't see anything about the product but there's a clue in
                 *  the html source code that can help with debugging
                 */
                return '<!-- product not allowed (' . $this->ls_objProduct->_id . ' / ' . $arr_groupSettings['id'] . ') -->';
            }
        }
		return $this->obj_template->parse();
	}
}