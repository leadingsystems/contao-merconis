<?php

namespace Merconis\Core;

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
		if (TL_MODE !== 'FE') {
			return;
		}

		if (!isset($_SESSION['lsShop']['persistentVariantSelection']) || !is_array($_SESSION['lsShop']['persistentVariantSelection'])) {
			$_SESSION['lsShop']['persistentVariantSelection'] = array();
		}

		if (
			\Input::get('selectVariantPersistently')
			&&	$this->ls_objProduct->_selectVariant(\Input::get('selectVariantPersistently'))
		) {
			$_SESSION['lsShop']['persistentVariantSelection'][$this->ls_productID] = \Input::get('selectVariantPersistently');
		}

		else if (
			isset($_SESSION['lsShop']['persistentVariantSelection'][$this->ls_productID]) && $_SESSION['lsShop']['persistentVariantSelection'][$this->ls_productID]
		) {
			$this->ls_objProduct->_selectVariant($_SESSION['lsShop']['persistentVariantSelection'][$this->ls_productID]);
		}
	}

	protected function prepareTemplate() {
		$this->obj_template = new \FrontendTemplate($this->ls_template);
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

		$this->obj_template->productPositionsClassString = $productPositionsClassString.($this->ls_additionalClass ? ' '.$this->ls_additionalClass : '');
		
		/*
		 * Informationen über das Template ans Template übergeben
		 */
		$this->obj_template->productTemplate = $this->ls_template;
		$this->obj_template->productTemplateType = preg_replace('/_[^_]*$/siU', '', $this->ls_template);
		
		$this->obj_template->outputContext = isset($GLOBALS['lsShopProductViewContext']) ? $GLOBALS['lsShopProductViewContext'] : 'standard';

		if (isset($GLOBALS['MERCONIS_HOOKS']['prepareProductTemplate']) && is_array($GLOBALS['MERCONIS_HOOKS']['prepareProductTemplate'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['prepareProductTemplate'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$objMccb->{$mccb[1]}($this->obj_template, $this->ls_template, $this->ls_objProduct);
			}
		}		
	}
	
	public function parseOutput() {
        if (TL_MODE === 'FE' && $this->ls_objProduct->_useGroupRestrictions) {
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
