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

                if ($str_productVariantId === 'current') {
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

                $str_templateToUse = isset($arr_params[1]) && $arr_params[1] ? trim($arr_params[1]) : '';

                $objProductOutput = new ls_shop_productOutput($str_productVariantId, 'overview', $str_templateToUse);
                $str_productOutput = $objProductOutput->parseOutput();

                return \Controller::replaceInsertTags($str_productOutput);
                break;

            case 'ProductProperty':
                $arr_params = explode(',', $params);
                $str_productVariantId = trim($arr_params[0]);

                if ($str_productVariantId === 'current') {
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

                $str_propertyToUse = isset($arr_params[1]) && $arr_params[1] ? trim($arr_params[1]) : '';

                $obj_product = ls_shop_generalHelper::getObjProduct($str_productVariantId, __METHOD__);

                return \Controller::replaceInsertTags($obj_product->{$str_propertyToUse});
                break;
		}

		return false;
	}
}