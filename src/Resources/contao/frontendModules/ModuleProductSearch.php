<?php

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Image\ResizeConfiguration;
use Contao\Input;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use LeadingSystems\Helpers\FlexWidget;

/**
 * If the form that has just been submitted can be identified as the merconisProductSearch form, it's
 * data will be stored in the SESSION in order to make it accessible by a crossSeller.
 */
class ModuleProductSearch extends Module {
	public $arrLiveHitFields = array();
	
	public function generate() {
		if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
			$this->import('Contao\FrontendUser', 'User');
		}
		
		$this->arrLiveHitFields = [
            '_mainImage',
            '_priceAfterTaxFormatted',
            '_linkToProduct',
            '_title',
            '_shortDescription',
            '_code'
        ];

		if (
			Input::post('isAjax') == 1
			&&	(
				Input::post('requestedClass') == __CLASS__
				||	html_entity_decode(Input::post('requestedClass')) == __CLASS__
				||	'Merconis\\Core\\'.Input::post('requestedClass') == __CLASS__
			)
		) {
			/*
			 * In case of an ajax request the function generateAjax() is called. This function
			 * checks the mandatory "action" parameter and returns the corresponding ajax response.
			 */
			echo $this->generateAjax();
			exit; // IMPORTANT, otherwise the whole page content would be rendered and returned as the ajax response
		}
		
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS ProductSearch ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}
	
	/*
	 * This function returns the json_encoded response to the current ajax request.
	 */
	public function generateAjax() {
		$response = array(
			'success' => null,
			'value' => null,
			'error' => null
		);
		
		if (!Input::post('action')) {
			$response['error'] = 'no action defined';
		} else {
			switch (Input::post('action')) {
				case 'getLiveHitsConfiguration':
					$response['value'] = array(
						'ls_shop_liveHitsMinLengthSearchTerm' => isset($GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm']) && $GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm'] ? $GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm'] : 0
					);
					$response['success'] = true;
					break;
					
				case 'getPossibleHits':
					/*
					 * Erstellung des Suchkriterien-Arrays für productSearcher
					 */
					$arrSearchCriteria = array(
						'published' => '1',
						'fulltext' => ls_shop_generalHelper::handleSearchWordMinLength(Input::post('searchWord'), $GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm'])
					);
					
					if (isset($GLOBALS['MERCONIS_HOOKS']['beforeAjaxSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeAjaxSearch'])) {
						foreach ($GLOBALS['MERCONIS_HOOKS']['beforeAjaxSearch'] as $mccb) {
							$objMccb = System::importStatic($mccb[0]);
							$arrSearchCriteria = $objMccb->{$mccb[1]}($arrSearchCriteria);
						}
					}

					/*
					 * Ende Erstellung des Suchkriterien-Arrays für productSearcher
					 */
					$objProductSearch = new ls_shop_productSearcher();
					$objProductSearch->setSearchCriteria($arrSearchCriteria);					
					$objProductSearch->arrRequestFields = array('id');
					
					/*
					 * FIXME: Making this sorting definition user-adjustable (probably in the merconis settings)
					 * might be a good idea!
					 */
					$objProductSearch->sorting = array(
						0 => array('field' => 'priority', 'direction' => 'DESC')
					);

					$objProductSearch->search();
					$arrProducts = $objProductSearch->productResultsComplete;
					
					if (isset($GLOBALS['MERCONIS_HOOKS']['afterAjaxSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterAjaxSearch'])) {
						foreach ($GLOBALS['MERCONIS_HOOKS']['afterAjaxSearch'] as $mccb) {
							$objMccb = System::importStatic($mccb[0]);
							$arrProducts = $objMccb->{$mccb[1]}($arrSearchCriteria, $arrProducts);
						}
					}
					
					$arrProductsTmp = $arrProducts;
					$arrProducts = array();
					
					$count = 0;
					$numProducts = count($arrProductsTmp);
					
					foreach ($arrProductsTmp as $productID) {
						$count++;
						if ($count > $GLOBALS['TL_CONFIG']['ls_shop_liveHitsMaxNumHits']) {
						    break;
                        }
						$objProduct = ls_shop_generalHelper::getObjProduct($productID, '', false);
						
						$arrHit = array();
						$arrHit['_class'] = array();
						
						if ($count == 1) {
							$arrHit['_class'][] = 'first';
						}

						if ($count == $numProducts) {
							$arrHit['_class'][] = 'last';
						}
						
						foreach ($this->arrLiveHitFields as $liveHitField) {
							switch ($liveHitField) {
								case '_mainImage':
                                    $img = new Image($objProduct->{$liveHitField});
                                    $arrHit[$liveHitField] = $img->getSrc([$GLOBALS['TL_CONFIG']['ls_shop_liveHitImageSizeWidth'], $GLOBALS['TL_CONFIG']['ls_shop_liveHitImageSizeHeight'], ResizeConfiguration::MODE_BOX]);
									break;
									
								case '_priceAfterTaxFormatted':
									$arrHit[$liveHitField] = ($objProduct->_unscaledPricesAreDifferent ? $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['from'].' ' : '').$objProduct->_unscaledPriceMinimumAfterTaxFormatted.($objProduct->_hasQuantityUnit ? '/'.$objProduct->_quantityUnit : '');
									break;
									
								case '_linkToProduct':
									$arrHit[$liveHitField] = Environment::get('base').$objProduct->_linkToProduct;
									break;
									
								default:
									$arrHit[$liveHitField] = System::getContainer()->get('contao.insert_tag.parser')->replace($objProduct->{$liveHitField});
									break;
							}
						}

						$arrProducts[] = $arrHit;
					}
					
					$response['value'] = $arrProducts;
					$response['success'] = true;
					break;
			}
		}
		
		return json_encode($response);
	}
	
	public function compile() {
		$this->strTemplate = $this->ls_shop_productSearch_template;
		$this->Template = new FrontendTemplate($this->strTemplate);
		
		$this->Template->action = StringUtil::ampersand(Environment::get('request'));
		$this->Template->blnUseLiveHits = isset($this->arrLiveHitFields) && is_array($this->arrLiveHitFields) && count($this->arrLiveHitFields);

        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop');

		$obj_flexWidget_input = new FlexWidget(
			array(
				'str_uniqueName' => 'merconis_searchWord',
				'bln_multipleWidgetsWithSameNameAllowed' => true,
				'str_label' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText112'],
				'int_minLength' => $this->ls_shop_productSearch_minlengthInput,
				'arr_validationFunctions' => array(
					array(
						'str_className' => '\Merconis\Core\FlexWidgetValidator',
						'str_methodName' => 'searchWordMinLength'
					)
				),
				'var_value' => isset($session_lsShop['productSearch']['searchWord']) ? $session_lsShop['productSearch']['searchWord'] : ''
			)
		);

		if (Input::post('FORM_SUBMIT') == 'merconisProductSearch') {
			if (!$obj_flexWidget_input->bln_hasErrors) {
                $session_lsShop['productSearch'] = array(
                    'searchWord' => ls_shop_generalHelper::handleSearchWordMinLength($obj_flexWidget_input->getValue(), $this->ls_shop_productSearch_minlengthInput),
                    'searchType' => Input::post('searchType')
                );
                $session->set('lsShop', $session_lsShop);

				$this->redirect(ls_shop_languageHelper::getLanguagePage('ls_shop_searchResultPages', false));
			}
		}

		$this->Template->str_widget_searchWord = $obj_flexWidget_input->getOutput();
	}
}
?>