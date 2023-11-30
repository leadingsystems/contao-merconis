<?php

namespace Merconis\Core;

use Contao\StringUtil;
use Contao\System;

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
                if (!System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
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
                if (System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
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

            case 'IfAvailableBasedOnDate':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


                if (!$obj_productOrVariant->_isAvailableBasedOnDate) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfNotAvailableBasedOnDate':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


                if ($obj_productOrVariant->_isAvailableBasedOnDate) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfOrderAllowed':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


                if (!$obj_productOrVariant->_orderAllowed) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfOrderNotAllowed':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


                if ($obj_productOrVariant->_orderAllowed) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfIsPreorderable':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


                if (!$obj_productOrVariant->_isPreorderable) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'IfIsNotPreorderable':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];


                if ($obj_productOrVariant->_isPreorderable) {
                    for (; $_rit<$_cnt; $_rit+=2) {
                        if ($tags[$_rit+1] == 'shop' . $tag . '::end') {
                            break;
                        }
                    }
                }
                unset($arrCache[$strTag]);
                return null;
                break;

            case 'DeliveryDate':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];
                $str_deliveryDate = \Date::parse($objPage->dateFormat, time() + 86400 * $obj_productOrVariant->getDeliveryTimeDays($GLOBALS['merconis_globals']['arr_dataForInsertTags']['float_requestedQuantity']));
                return $str_deliveryDate;
                break;

            case 'DeliveryTimeDays':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];
                $int_deliveryTimeDays = $obj_productOrVariant->getDeliveryTimeDays($GLOBALS['merconis_globals']['arr_dataForInsertTags']['float_requestedQuantity']);
                return $int_deliveryTimeDays;
                break;

            case 'AvailableFrom':
                if (!is_array($GLOBALS['merconis_globals']['arr_dataForInsertTags'] ?? null)) {
                    System::log('Trying to render insert tag "' . $strTag . '" in wrong context. Its usage is only supported in delivery time messages.', 'MERCONIS INSERT TAGS', TL_MERCONIS_ERROR);
                    return '';
                }

                /** @var ls_shop_product|ls_shop_variant $obj_productOrVariant */
                $obj_productOrVariant = $GLOBALS['merconis_globals']['arr_dataForInsertTags']['obj_productOrVariant'];
                $str_availableFrom = \Date::parse($objPage->dateFormat, $obj_productOrVariant->_availableFrom);
                return $str_availableFrom;
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
                                /*
                                 * Determine the image size id with the given merconis_alias and replace the alias with
                                 * the id in the parameter string or remove the size parameter entirely if no image size
                                 * record could be found with the merconis_alias.
                                 */
                                $result = \Database::getInstance()->prepare("SELECT id FROM tl_image_size WHERE merconis_alias=?")->execute($value)->fetchAssoc();
                                if ($result) {
                                    $params = str_replace('size=' . $value, 'size=' . $result['id'], $params);
                                } else {
                                    $params = ls_shop_generalHelper::removeGetParametersFromUrl($params, 'size');
                                }
                                break;
                        }
                    }
                }
                return System::getContainer()->get('contao.insert_tag.parser')->replace('{{picture::'.$params.'}}');
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

                return System::getContainer()->get('contao.insert_tag.parser')->replace($obj_product->{$str_propertyToUse});
                break;
		}

		return false;
	}
}
