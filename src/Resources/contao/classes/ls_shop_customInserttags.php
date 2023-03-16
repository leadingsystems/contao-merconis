<?php

namespace Merconis\Core;

use Contao\StringUtil;

class ls_shop_customInserttags
{
	public function customInserttags($strTag, $blnCache, $var_cache, $flags, $tags, &$arrCache, &$_rit, &$_cnt) {
		/** @var \PageModel $objPage */
		global $objPage;
		if (!preg_match('/^shop([^:]*)(::(.*))?$/', $strTag, $matches)) {
			return false;
		}
		$tag = isset($matches[1]) ? $matches[1] : '';
		$params = isset($matches[3]) ? $matches[3] : '';

		switch ($tag) {
            case 'IfFeUserLoggedIn':
                if (!FE_USER_LOGGED_IN) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfFeUserNotLoggedIn':
                if (FE_USER_LOGGED_IN) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfOnCartPage':
                if ($objPage->id != ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id')) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfNotOnCartPage':
                if ($objPage->id == ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id')) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfInCheckout':
                if (!in_array(
                    $objPage->id,
                    [
                        ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_reviewPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_checkoutFinishPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_paymentAfterCheckoutPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id'),
                    ]
                )) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfNotInCheckout':
                if (in_array(
                    $objPage->id,
                    [
                        ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_reviewPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_checkoutFinishPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_paymentAfterCheckoutPages', false, 'id'),
                        ls_shop_languageHelper::getLanguagePage('ls_shop_cartPages', false, 'id'),
                    ]
                )) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'CurrentLanguage':
                global $objPage;
                return $objPage->language;
                break;

			case 'Calculation':
				switch ($params) {
					case 'invoicedAmount':
						return ls_shop_generalHelper::outputPrice(ls_shop_cartX::getInstance()->calculation['invoicedAmount']);
						break;
				}
				break;

			case 'Link':
				return ls_shop_languageHelper::getLanguagePage('ls_shop_'.$params.'s'); // Als Parameter wird z. B. "cartPage" angegeben, da das Feld in der localconfig allerdings in Mehrzahl benannt ist, wird das "s" angehängt.
				break;

			case 'CategoryLink':
				return \Controller::generateFrontendUrl($objPage->row());
				break;

			case 'CategoryLinkOrSearchResult':
				return \Input::get('calledBy') == 'searchResult' ? ls_shop_languageHelper::getLanguagePage('ls_shop_searchResultPages') : \Controller::generateFrontendUrl($objPage->row());
				break;

            case 'Picture':

                if (strpos($params, '?') !== false)
                {
                    $arrChunks = explode('?', urldecode($params), 2);
                    $strSource = StringUtil::decodeEntities($arrChunks[1]);
                    $strSource = str_replace('[&]', '&', $strSource);
                    $arrParams = explode('&', $strSource);

                    foreach ($arrParams as $strParam)
                    {
                        list($key, $value) = explode('=', $strParam);

                        switch ($key)
                        {
                            case 'size':
                                $result = \Database::getInstance()->prepare("SELECT id FROM tl_image_size WHERE merconis_alias=?")->execute($value)->fetchAssoc();
                                if($result){
                                    //replace text with id
                                    $params = str_replace('size='.$value, 'size='.$result['id'], $params);
                                }else{
                                    //if no if can be found remove size
                                    $params = str_replace('size='.$value, '', $params);
                                }
                                break;
                        }
                    }
                }
                return \Controller::replaceInsertTags( '{{picture::'.$params.'}}');
                break;

			case 'CrossSeller':
				$arrParams = explode(',', $params);
				$crossSellerID = trim($arrParams[0]);
				if ($arrParams[1]) {
					$GLOBALS['merconis_globals']['str_currentProductAliasForCrossSeller'] = trim($arrParams[1]);
				}
				$objCrossSeller = new ls_shop_cross_seller($crossSellerID);
				$str_output = $objCrossSeller->parseCrossSeller();
				if ($arrParams[1]) {
					unset($GLOBALS['merconis_globals']['str_currentProductAliasForCrossSeller']);
				}
				return $str_output;
				break;

            case 'ProductOutput':
                $arr_params = explode(',', $params);
                $str_productVariantId = trim($arr_params[0]);
                $str_productVariantId = $this->convertTagToId($str_productVariantId);

                $str_templateToUse = isset($arr_params[1]) && $arr_params[1] ? trim($arr_params[1]) : '';

                $objProductOutput = new ls_shop_productOutput($str_productVariantId, 'overview', $str_templateToUse);
                $str_productOutput = $objProductOutput->parseOutput();

                return \Controller::replaceInsertTags($str_productOutput);
                break;

            case 'ProductProperty':
                $arr_params = explode(',', $params);
                $str_productVariantId = trim($arr_params[0]);

                //if no parameter exists search for productId
                $what = ($arr_params[2] ? trim($arr_params[2]) : "productId");
                $str_productVariantId = $this->convertTagToId($str_productVariantId, $what);

                $str_propertyToUse = isset($arr_params[1]) && $arr_params[1] ? trim($arr_params[1]) : '';

                $obj_product = ls_shop_generalHelper::getObjProduct($str_productVariantId, __METHOD__);

                return \Controller::replaceInsertTags($obj_product->{$str_propertyToUse});
                break;
		}

		return false;
	}

    //converts tags(ids, codes, alias) to 'productId'-'variantId'
	private function convertTagToId($str_productTag, $what){

        //if current convert it to "productId-0"
        if ($str_productTag === 'current') {
            /*
             * Get product currently displayed in singleview if not productVariantId is given
             */
            $str_productAlias = \Input::get('product');
            $int_productId = ls_shop_generalHelper::getProductIdForAlias($str_productAlias);
            if (!$int_productId) {
                return '';
            }
            $str_productVariantId = $int_productId.'-0';
        }
        if($what === "productId") {
            $str_productVariantId = $str_productTag;
        }
        //if productAlias convert it to "productId-0"
        if($what === "productAlias"){
            $int_productId = ls_shop_generalHelper::getProductIdForAlias($str_productTag);
            if (!$int_productId) {
                return '';
            }
            $str_productVariantId = $int_productId.'-0';
        }
        //if productCode convert it to "productId-0"
        if($what === "productCode" ){
            $int_productId = ls_shop_generalHelper::getProductIdForCode($str_productTag);
            if (!$int_productId) {
                return '';
            }
            $str_productVariantId = $int_productId.'-0';
        }
        //if variantAlias convert it to "productId-variantId"
        if($what === "variantAlias"){
            $int_variantId = ls_shop_generalHelper::getVariantIdForAlias($str_productTag);
            if (!$int_variantId) {
                return '';
            }
            $str_productVariantId = ls_shop_generalHelper::getProductIdForVariantId($int_variantId).'-'.$int_variantId;
        }
        //if variantCode convert it to "productId-variantId"
        if($what === "variantCode"){
            $int_variantId = ls_shop_generalHelper::getVariantIdForCode($str_productTag);
            if (!$int_variantId) {
                return '';
            }
            $str_productVariantId = ls_shop_generalHelper::getProductIdForVariantId($int_variantId).'-'.$int_variantId;
        }
        return $str_productVariantId;
    }
}

