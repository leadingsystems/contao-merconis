<?php

namespace Merconis\Core;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\StringUtil;
use Contao\System;

class ls_shop_cross_seller
{
    protected $ls_id = 0;
    protected $ls_details = array();
    protected $truncatedResultsMsg = '';
    protected $ls_template = 'template_crossSeller_default';

    protected $ls_currentProductInDetailMode = false;
    protected $blnIsTruncated = false;
    protected $crossSellersCalledInChain = array();

    protected $arrProducts = array();


    public function __construct($crossSellerID = 0, $crossSellersCalledInChain = array()) {
        if (!$crossSellerID) {
            /*
             * Do not throw an exception in backend mode because in this case it would not be possible
             * to select another/correct CrossSeller id for a CrossSeller CTE.
             */
            if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
                \System::getContainer()->get('monolog.logger.contao')->info('MERCONIS: Trying to show a CrossSeller without given id', ['contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_ERROR)]);
                return;
            }

            throw new \Exception('no crossSeller ID given');
        }

        $this->ls_id = $crossSellerID;
        $this->crossSellersCalledInChain = $crossSellersCalledInChain;
        $this->crossSellersCalledInChain[] = $this->ls_id;

        $this->ls_getDetails();
        $this->getCurrentProductInDetailMode();
    }

    protected function setArrProducts($arrProducts = null, $bln_removeCurrentProductInDetailMode = true) {
        if (!isset($arrProducts) || !is_array($arrProducts)) {
            $this->arrProducts = array();
            return;
        }

        $arrProducts = ls_shop_generalHelper::ls_array_unique($arrProducts);
        if ($bln_removeCurrentProductInDetailMode) {
            $arrProducts = $this->removeCurrentProductInDetailMode($arrProducts);
        }

        if ($this->ls_details['maxNumProducts'] > 0 && $this->ls_details['maxNumProducts'] < count($arrProducts)) {
            $arrProducts = array_slice($arrProducts, 0, $this->ls_details['maxNumProducts']);
        }

        $this->arrProducts = $arrProducts;
    }

    public function parseCrossSeller() {
        /*
         * Skip this crossSeller if it's a frontendProductSearch and if Post data is set
         * which requires a new search. This will prevent a double execution of the search
         * (before and after the reload)
         */
        if ($this->ls_details['productSelectionType'] == 'frontendProductSearch') {
            if (\Input::post('FORM_SUBMIT') == 'merconisProductSearch' || (\Input::post('isAjax') == 1 && \Input::post('action') == 'getPossibleHits')) {
                return $GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText113'];
            }
        }

        $GLOBALS['lsShopProductViewContext'] = 'crossSeller_'.$this->ls_id;

        $this->ls_template = $this->ls_details['template'];
        $obj_template =  new \FrontendTemplate($this->ls_template);

        $productListOutput = '';

        switch ($this->ls_details['productSelectionType']) {
            case 'hookSelection':
                $this->setArrProducts($this->getCrossSellerHookSelection());
                if (!count($this->arrProducts)) {
                    return $this->getFallback();
                }

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = array('id' => $this->arrProducts);
                $productListOutput = $objProductList->parseOutput();
                if (!$productListOutput) {
                    return $this->getFallback();
                }
                break;

            case 'directSelection':
                // $arrProducts available directly
                $this->setArrProducts($this->ls_getDirectSelection());
                if (!count($this->arrProducts)) {
                    return $this->getFallback();
                }

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = array('id' => $this->arrProducts);
                $objProductList->fixedSorting = $this->arrProducts;
                $productListOutput = $objProductList->parseOutput();
                if (!$productListOutput) {
                    return $this->getFallback();
                }
                break;

            case 'lastSeen':
                // $arrProducts available directly
                $this->setArrProducts($this->ls_getLastSeenSelection());
                if (!count($this->arrProducts)) {
                    return $this->getFallback();
                }

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = array('id' => $this->arrProducts);
                $objProductList->fixedSorting = $this->arrProducts;
                $productListOutput = $objProductList->parseOutput();
                if (!$productListOutput) {
                    return $this->getFallback();
                }
                break;

            case 'favorites':
                // $arrProducts available directly
                $this->setArrProducts($this->ls_getFavoritesSelection(), false);
                if (!count($this->arrProducts)) {
                    return $this->getFallback();
                }

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = array('id' => $this->arrProducts);
                $productListOutput = $objProductList->parseOutput();
                if (!$productListOutput) {
                    return $this->getFallback();
                }
                break;

            case 'restockInfoList':
                // $arrProducts available directly
                $this->setArrProducts($this->ls_getRestockInfoListSelection(), false);
                if (!count($this->arrProducts)) {
                    return $this->getFallback();
                }

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = array('id' => $this->arrProducts);
                $productListOutput = $objProductList->parseOutput();
                if (!$productListOutput) {
                    return $this->getFallback();
                }
                break;

            case 'recommendedProducts':
                // $arrProducts available directly
                $this->setArrProducts($this->ls_getRecommendedProductsSelection());
                if (!count($this->arrProducts)) {
                    return $this->getFallback();
                }

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = array('id' => $this->arrProducts);
                $objProductList->fixedSorting = $this->arrProducts;
                $productListOutput = $objProductList->parseOutput();
                if (!$productListOutput) {
                    return $this->getFallback();
                }
                break;



            case 'searchSelection':
                // search criteria available
                $arrSearchCriteria = $this->ls_getSearchSelection();

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = $arrSearchCriteria;
                $objProductList->maxNumProducts = $this->ls_details['maxNumProducts'];
                $productListOutput = $objProductList->parseOutput();
                if (!$productListOutput) {
                    return $this->getFallback();
                }
                break;

            case 'frontendProductSearch':
                // search criteria available
                $arrSearchCriteria = $this->ls_getFrontendSearchSelection();

                $objProductList = new ls_shop_productList($GLOBALS['lsShopProductViewContext']);
                $objProductList->mode = $this->ls_details['doNotUseCrossSellerOutputDefinitions'] ? 'standard': 'crossSeller';
                $objProductList->arrSearchCriteria = $arrSearchCriteria;
                $objProductList->maxNumProducts = $this->ls_details['maxNumProducts'];
                $objProductList->noOutputIfMoreThanMaxResults = $this->ls_details['noOutputIfMoreThanMaxResults'];
                $objProductList->blnIsFrontendSearch = true;
                $productListOutput = $objProductList->parseOutput();

                if ($objProductList->blnIsTruncated) {
                    if (!$this->ls_details['noOutputIfMoreThanMaxResults']) {
                        $this->truncatedResultsMsg = sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['truncatedResultsMsg1'], $this->ls_details['maxNumProducts'], $objProductList->numProducts);
                    } else {
                        $this->truncatedResultsMsg = sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['truncatedResultsMsg2'], count($this->arrProducts));
                    }
                } else {
                    if (!$productListOutput) {
                        return $this->getFallback();
                    }
                }
                break;
        }

        $obj_template->output = $productListOutput;
        $obj_template->text01 = $this->ls_details['text01'];
        $obj_template->text02 = $this->ls_details['text02'];
        $obj_template->truncatedResultsMsg = $this->truncatedResultsMsg;


        $obj_template->outputContext = $GLOBALS['lsShopProductViewContext'];
        $obj_template->crossSellerType = $this->ls_details['productSelectionType'];

        $return = $obj_template->parse();
        unset($GLOBALS['lsShopProductViewContext']);
        return $return;
    }

    protected function getFallback() {
        /*
         * Verwenden des alternativen CrossSellers, falls der aktuelle CrossSeller
         * keine darzustellenden Produkte liefert. Wurde der Fallback-CrossSeller bereits
         * in der aktuellen Fallback-Kette aufgerufen, so wird er nicht erneut aufgerufen,
         * es wird stattdessen die alternative Direkt-Ausgabe verwendet. Die alternative
         * Direkt-Ausgabe kommt auch zum Einsatz, wenn überhaupt kein Fallback-CrossSeller
         * angegeben ist.
         */
        if ($this->ls_details['fallbackCrossSeller'] && !in_array($this->ls_details['fallbackCrossSeller'], $this->crossSellersCalledInChain)) {
            $objCrossSeller = new ls_shop_cross_seller($this->ls_details['fallbackCrossSeller'], $this->crossSellersCalledInChain);
            return $objCrossSeller->parseCrossSeller();
        } else {
            return $this->ls_details['fallbackOutput'];
        }
    }

    protected function ls_getSearchSelection() {
        /** @var \PageModel $objPage */
        global $objPage;
        /*
         * Erstellung des Suchkriterien-Arrays für productSearcher
         */
        $arrSearchCriteria = array('published' => '1');

        if ($this->ls_details['activateSearchSelectionNewProduct']) {
            $arrSearchCriteria['lsShopProductIsNew'] = $this->ls_details['searchSelectionNewProduct'] == 'new' ? '1' : '';
        }

        if ($this->ls_details['activateSearchSelectionSpecialPrice']) {
            $arrSearchCriteria['lsShopProductIsOnSale'] = $this->ls_details['searchSelectionSpecialPrice'] == 'specialPrice' ? '1' : '';
        }

        if ($this->ls_details['activateSearchSelectionCategory']) {
            $pageIDs = StringUtil::deserialize($this->ls_details['searchSelectionCategory']);
            if (!is_array($pageIDs)) {
                $pageIDs = array();
            }
            if (!count($pageIDs)) {
                $pageIDs = array(ls_shop_languageHelper::getMainlanguagePageIDForPageID($objPage->id));
            }
            $arrSearchCriteria['pages'] = $pageIDs;
        }

        if ($this->ls_details['activateSearchSelectionProducer']) {
            $arrSearchCriteria['lsShopProductProducer'] = $this->ls_replaceWildcards($this->ls_details['searchSelectionProducer']);
        }

        if ($this->ls_details['activateSearchSelectionProductName']) {
            $arrSearchCriteria['title'] = $this->ls_replaceWildcards($this->ls_details['searchSelectionProductName']);
        }

        if ($this->ls_details['activateSearchSelectionArticleNr']) {
            $arrSearchCriteria['lsShopProductCode'] = $this->ls_replaceWildcards($this->ls_details['searchSelectionArticleNr']);
        }

        if ($this->ls_details['activateSearchSelectionTags']) {
            $arrSearchCriteria['keywords'] = $this->ls_replaceWildcards($this->ls_details['searchSelectionTags']);
        }
        /*
         * Ende Erstellung des Suchkriterien-Arrays für productSearcher
         */

        return $arrSearchCriteria;
    }

    protected function ls_getFrontendSearchSelection() {
        /*
         * Erstellung des Suchkriterien-Arrays für productSearcher
         */
        $arrSearchCriteria = array(
            'published' => '1',
            'fulltext' => $_SESSION['lsShop']['productSearch']['searchWord'],
            'searchType' => $_SESSION['lsShop']['productSearch']['searchType'],
        );
        /*
         * Ende Erstellung des Suchkriterien-Arrays für productSearcher
         */

        return $arrSearchCriteria;
    }



    ####################################
    ####################################
    ####################################


    protected function ls_getDetails() {
        /** @var \PageModel $objPage */
        global $objPage;

        $objCrossSeller = \Database::getInstance()->prepare("
			SELECT			*
			FROM			`tl_ls_shop_cross_seller`
			WHERE			`id` = ?
				AND			`published` = '1'
		")
            ->execute($this->ls_id);

        if (!$objCrossSeller->numRows) {
            return;
        }

        $objCrossSeller->first();
        $this->ls_details = ls_shop_languageHelper::getMultilanguageDataRowInSpecificLanguage($objCrossSeller->row(), $objPage->language);
    }

    protected function ls_getDirectSelection() {
        $arrProducts = StringUtil::deserialize($this->ls_details['productDirectSelection']);
        if (count($arrProducts) == 1 && !$arrProducts[0]) {
            $arrProducts = array();
        }
        return $arrProducts;
    }

    protected function getCrossSellerHookSelection() {
        $arr_products = [];

        if (isset($GLOBALS['MERCONIS_HOOKS']['crossSellerHookSelection']) && is_array($GLOBALS['MERCONIS_HOOKS']['crossSellerHookSelection'])) {
            foreach ($GLOBALS['MERCONIS_HOOKS']['crossSellerHookSelection'] as $mccb) {
                $objMccb = \System::importStatic($mccb[0]);
                $arr_products = $objMccb->{$mccb[1]}($GLOBALS['lsShopProductViewContext'], $this->ls_currentProductInDetailMode);
            }
        }

        return $arr_products;
    }

    protected function ls_getLastSeenSelection() {
        $lastSeenProducts = isset($_SESSION['lsShop']['lastSeenProducts']) && is_array($_SESSION['lsShop']['lastSeenProducts']) ? $_SESSION['lsShop']['lastSeenProducts'] : array();
        $lastSeenProducts = ls_shop_generalHelper::ls_array_unique($lastSeenProducts);
        return $lastSeenProducts;
    }

    protected function ls_getFavoritesSelection() {
        $obj_user = \System::importStatic('FrontendUser');
        $strFavorites = isset($obj_user->merconis_favoriteProducts) ? $obj_user->merconis_favoriteProducts : '';
        $arrFavorites = $strFavorites ? StringUtil::deserialize($strFavorites) : array();
        $arrFavorites = is_array($arrFavorites) ? $arrFavorites : array();
        $arrFavorites = ls_shop_generalHelper::ls_array_unique($arrFavorites);
        return $arrFavorites;
    }

    protected function ls_getRestockInfoListSelection() {
        $obj_user = \System::importStatic('FrontendUser');

        $obj_dbres_restockInfoListRecords = \Database::getInstance()
            ->prepare("
                SELECT      productVariantId
                FROM        tl_ls_shop_restock_info_list
                WHERE       memberId = ?
            ")
            ->execute(
                $obj_user->id
            );

        $arr_restockInfoList = $obj_dbres_restockInfoListRecords->fetchEach('productVariantId');

        return $arr_restockInfoList;
    }

    protected function ls_getRecommendedProductsSelection() {
        $arrProducts = array();
        if (is_object($this->ls_currentProductInDetailMode)) {
            $arrProducts = StringUtil::deserialize($this->ls_currentProductInDetailMode->lsShopProductRecommendedProducts);
        }
        if (
            !is_array($arrProducts)
            || (count($arrProducts) == 1 && !$arrProducts[0])
        ) {
            $arrProducts = array();
        }
        return $arrProducts;
    }

    protected function getCurrentProductInDetailMode() {
        /** @var \PageModel $objPage */
        global $objPage;
        if (\Input::get('product') || ($GLOBALS['merconis_globals']['str_currentProductAliasForCrossSeller'] ?? null)) {
            $objCurrentProduct = \Database::getInstance()->prepare("
				SELECT			*
				FROM			`tl_ls_shop_product`
				WHERE			`alias_".$objPage->language."` = ?
			")
                ->limit(1)
                ->execute(\Input::get('product') ? \Input::get('product') : $GLOBALS['merconis_globals']['str_currentProductAliasForCrossSeller']);

            if ($objCurrentProduct->numRows) {
                $this->ls_currentProductInDetailMode = $objCurrentProduct->first();
            }
        }
    }

    protected function removeCurrentProductInDetailMode($arrProducts) {
        if (is_object($this->ls_currentProductInDetailMode)) {
            $key = array_search($this->ls_currentProductInDetailMode->id, $arrProducts);
            if ($key !== false) {
                unset($arrProducts[$key]);
            }
        }
        return $arrProducts;
    }

    protected function ls_replaceWildcards($str) {
        if (is_object($this->ls_currentProductInDetailMode)) {
            $str = preg_replace('/\{\{currentProduct_name\}\}/siU', $this->ls_currentProductInDetailMode->title, $str);
            $str = preg_replace('/\{\{currentProduct_articleNr\}\}/siU', $this->ls_currentProductInDetailMode->lsShopProductCode, $str);
            $str = preg_replace('/\{\{currentProduct_producer\}\}/siU', $this->ls_currentProductInDetailMode->lsShopProductProducer, $str);
        }
        return $str;
    }
}