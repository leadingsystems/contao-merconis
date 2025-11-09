<?php

namespace Merconis\Core;

use Contao\StringUtil;
use Contao\System;
use LeadingSystems\Helpers\FlexWidget;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\ProductSearch\Enum\Mode;
use LeadingSystems\MerconisBundle\ProductSearch\Enum\MappingMode;

/**
 * If the form that has just been submitted can be identified as the merconisProductSearch form, it's
 * data will be stored in the SESSION in order to make it accessible by a crossSeller.
 */
class ModuleProductSearch extends \Module {
	public $arrLiveHitFields = array();

	/**
	 * Build a user-specific key without starting the session.
	 */
	private function getUserRequestKey() {
		$userKey = '';
		$session = System::getContainer()->get('session');
		if ($session && method_exists($session, 'isStarted') && $session->isStarted()) {
			$userKey = session_id();
		}

        if (!$userKey) {
            $ip = \Environment::get('ip');
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $userKey = hash('sha256', $ip.'|'.$ua);
        }

		return $userKey;
	}

	/**
	 * Fetch latest seq from a fast cache (APCu preferred, fallback to Symfony cache.app)
	 */
	private function fetchLatestSeq($userKey) {
		$cacheKey = 'livehits_latest_seq_'.md5($userKey);
		$container = System::getContainer();
		if ($container->has('cache.app')) {
			$cache = $container->get('cache.app');
			try {
				// Expect to work with PSR-6 CacheItemPoolInterface
				if ($cache instanceof \Psr\Cache\CacheItemPoolInterface) {
					$item = $cache->getItem($cacheKey);
					return $item->isHit() ? (int)$item->get() : 0;
				}
			} catch (\Throwable $e) {
				// ignore and fall through
			}
		}
		return 0;
	}

	/**
	 * Store latest seq into cache with short TTL
	 */
	private function storeLatestSeq($userKey, $seq) {
		$cacheKey = 'livehits_latest_seq_'.md5($userKey);
		$container = System::getContainer();
		if ($container->has('cache.app')) {
			$cache = $container->get('cache.app');
			try {
				// Expect to work with PSR-6 CacheItemPoolInterface for reliable set
				if ($cache instanceof \Psr\Cache\CacheItemPoolInterface) {
					$item = $cache->getItem($cacheKey);
					$current = $item->isHit() ? (int)$item->get() : 0;
					if ((int)$seq > $current) {
						$item->set((int)$seq);
						if (method_exists($item, 'expiresAfter')) {
                            $item->expiresAfter(10);
                        }
						$cache->save($item);
					}
					return;
				}
			} catch (\Throwable $e) {
				// ignore failures silently
			}
		}
	}

	public function generate() {
		if (\System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
			$this->import('FrontendUser', 'User');
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
			\Input::post('isAjax') == 1
			&&	(
				\Input::post('requestedClass') == __CLASS__
				||	html_entity_decode(\Input::post('requestedClass')) == __CLASS__
				||	'Merconis\\Core\\'.\Input::post('requestedClass') == __CLASS__
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
			$objTemplate = new \BackendTemplate('be_wildcard');
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

		if (!\Input::post('action')) {
			$response['error'] = 'no action defined';
		} else {
			switch (\Input::post('action')) {
				case 'getLiveHitsConfiguration':
					$response['value'] = array(
						'ls_shop_liveHitsMinLengthSearchTerm' => isset($GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm']) && $GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm'] ? $GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm'] : 0
					);
					$response['success'] = true;
					break;

				case 'getPossibleHits':
                    $userKey = $this->getUserRequestKey();

					// Release session lock early to avoid blocking subsequent AJAX requests
					$session = System::getContainer()->get('session');
					if ($session && method_exists($session, 'isStarted') && $session->isStarted()) {
						$session->save();
					}

					// Short-circuit superseded requests using seq
					$seqRaw = \Input::post('seq');
					if ($seqRaw === null || $seqRaw === '') {
						throw new \RuntimeException('Missing "seq" in live-hits request. The client must send a monotonically increasing seq parameter.');
					}
					$seq = (int)$seqRaw;
					$latestSeq = $this->fetchLatestSeq($userKey);
					$this->storeLatestSeq($userKey, $seq);

					/*
					 * Erstellung des Suchkriterien-Arrays für productSearcher
					 */
					$arrSearchCriteria = array(
						'published' => '1',
						'fulltext' => ls_shop_generalHelper::handleSearchWordMinLength(\Input::post('searchWord'), $GLOBALS['TL_CONFIG']['ls_shop_liveHitsMinLengthSearchTerm'])
					);

					if (isset($GLOBALS['MERCONIS_HOOKS']['beforeAjaxSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeAjaxSearch'])) {
						foreach ($GLOBALS['MERCONIS_HOOKS']['beforeAjaxSearch'] as $mccb) {
							$objMccb = \System::importStatic($mccb[0]);
							$arrSearchCriteria = $objMccb->{$mccb[1]}($arrSearchCriteria);
						}
					}

					/*
					 * Ende Erstellung des Suchkriterien-Arrays für productSearcher
					 */

                    /** @var Adapter $productSearchAdapter */
                    $productSearchAdapter = System::getContainer()->get('LeadingSystems\MerconisBundle\ProductSearch\Adapter');
                    $productSearchAdapter->initialize();
                    $productSearchAdapter->setMappingMode(MappingMode::Quick);

                    $productSearchAdapter->setSearchCriteria($arrSearchCriteria);

					/*
					 * TODO: Making this sorting definition user-adjustable (probably in the merconis settings)
					 *  might be a good idea!
					 */
                    $productSearchAdapter->setSortingCriteria(
                        [
                            0 => ['field' => 'priority', 'direction' => 'DESC']
                        ]
                    );

					// Grace window: allow slightly newer requests to publish their seq before starting search
					for ($i = 0; $i < 3; $i++) {
						$latestSeq = $this->fetchLatestSeq($userKey);
						if ($seq < $latestSeq) {
							return json_encode(array(
								'success' => true,
								'value' => array(),
								'error' => 'aborted 3'
							));
						}
						usleep(500000); // 500ms
					}

					$productSearchAdapter->search();

                    /*
                     * Check again before starting result processing which might be expensive if
                     * complex processing functionality is hooked via 'afterAjaxSearch'
                     */
                    $latestSeq = $this->fetchLatestSeq($userKey);
                    if ($seq < $latestSeq) {
                        return json_encode(array(
                            'success' => true,
                            'value' => array(),
                            'error' => 'aborted 5'
                        ));
                    }

                    $arrProducts = $productSearchAdapter->getProductResultsComplete();

					if (isset($GLOBALS['MERCONIS_HOOKS']['afterAjaxSearch']) && is_array($GLOBALS['MERCONIS_HOOKS']['afterAjaxSearch'])) {
						foreach ($GLOBALS['MERCONIS_HOOKS']['afterAjaxSearch'] as $mccb) {
							$objMccb = \System::importStatic($mccb[0]);
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
									$arrHit[$liveHitField] = \Image::get($objProduct->{$liveHitField}, $GLOBALS['TL_CONFIG']['ls_shop_liveHitImageSizeWidth'], $GLOBALS['TL_CONFIG']['ls_shop_liveHitImageSizeHeight'], 'box');
									break;

								case '_priceAfterTaxFormatted':
									$arrHit[$liveHitField] = ($objProduct->_unscaledPricesAreDifferent ? $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['from'].' ' : '').$objProduct->_unscaledPriceMinimumAfterTaxFormatted.($objProduct->_hasQuantityUnit ? '/'.$objProduct->_quantityUnit : '');
									break;

								case '_linkToProduct':
									$arrHit[$liveHitField] = \Environment::get('base').$objProduct->_linkToProduct;
									break;

								default:
									$arrHit[$liveHitField] = \Controller::replaceInsertTags($objProduct->{$liveHitField});
									break;
							}

                            if (isset($GLOBALS['MERCONIS_HOOKS']['manipulateLiveHit']) && is_array($GLOBALS['MERCONIS_HOOKS']['manipulateLiveHit'])) {
                                foreach ($GLOBALS['MERCONIS_HOOKS']['manipulateLiveHit'] as $mccb) {
                                    $objMccb = \System::importStatic($mccb[0]);
                                    $arrHit = $objMccb->{$mccb[1]}($arrHit, $objProduct);
                                }
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
		$this->Template = new \FrontendTemplate($this->strTemplate);

		$this->Template->action = StringUtil::ampersand(\Environment::get('request'));
		$this->Template->blnUseLiveHits = isset($this->arrLiveHitFields) && is_array($this->arrLiveHitFields) && count($this->arrLiveHitFields);

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
				'var_value' => isset($_SESSION['lsShop']['productSearch']['searchWord']) ? $_SESSION['lsShop']['productSearch']['searchWord'] : ''
			)
		);

		if (\Input::post('FORM_SUBMIT') == 'merconisProductSearch') {
			if (!$obj_flexWidget_input->bln_hasErrors) {
				$_SESSION['lsShop']['productSearch'] = array(
                    'searchWord' => ls_shop_generalHelper::handleSearchWordMinLength($obj_flexWidget_input->getValue(), $this->ls_shop_productSearch_minlengthInput)
                );

				$this->redirect(ls_shop_languageHelper::getLanguagePage('ls_shop_searchResultPages', false));
			}
		}

		$this->Template->str_widget_searchWord = $obj_flexWidget_input->getOutput();
	}
}
?>