<?php

namespace Merconis\Core;

	use Contao\Widget;

    class ls_shop_custom_regexp
	{
	    /*
	     * contao.hook: addCustomRegexp
	     */
		public function customRegexp($strRegexp, &$varValue, Widget $objWidget) {
			if ($strRegexp == 'oneNumber') {
				if (!preg_match('/^[0-9]{1}$/siU', $varValue)) {
					$objWidget->addError(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['oneNumber'], $objWidget->label));
				}
				return true;
			} else if ($strRegexp == 'numberWithDecimals') {
				if (preg_match('/[^-0-9\.]/siU', $varValue)) {
					$objWidget->addError(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimals'], $objWidget->label));
				}
				return true;
			} else if ($strRegexp == 'numberWithDecimalsLeftAndRight') {
				$arr_values = json_decode($varValue);
				if ($arr_values === null) {
					$arr_values = [];
				}
				foreach ($arr_values as $arr_value) {
					foreach ($arr_value as $str_value) {
						if (preg_match('/[^-0-9\.]/siU', $str_value)) {
							$objWidget->addError(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimalsLeftAndRight'], $objWidget->label));
							return false;
						}
					}
				}
				return true;
			} else if ($strRegexp == 'numberWithDecimalsAndHashsignLeftTextRight') {
				$arr_values = json_decode($varValue);
				if ($arr_values === null) {
					$arr_values = [];
				}

				foreach ($arr_values as $arr_value) {
					/*
					 * We check the value in the left field (key 0) for a number with decimals and possibly a hash sign.
					 * We check the value in the right field (key 1) for alphabetic characters and commas.
					 */
					if (preg_match('/[^-0-9\.#]/siU', $arr_value[0])) {
						$objWidget->addError(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimalsAndHashsignLeftTextRight'], $objWidget->label));
						return false;
					}
					if (preg_match('/[^a-z,]/siU', $arr_value[1])) {
						$objWidget->addError(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimalsAndHashsignLeftTextRight'], $objWidget->label));
						return false;
					}
				}
				return true;
			} else if ($strRegexp == 'feeFormula') {
				/*
				 * Da eine feeFormula per eval verarbeitet wird, wollen wir verhindern, dass dieser "De-Facto-PHP-Code" etwas enthalten
				 * kann, das zu etwas Anderem als dem wirklichen Zweck missbraucht werden kann. Abgesehen von den Platzhaltern sollen
				 * keine weiteren Worte erlaubt sein, um das Ausführen von Befehlen zu verhindern.
				 */
				$formula = html_entity_decode($varValue);
				
				/*
				 * Replace the allowed wildcards with the numeric value "10", which means that after that, the formula must not contain
				 * any further alphabetic characters or characters that could be misused in the eval'd string and that the formula must
				 * be calculable without any php errors.
				 */
				$formula = preg_replace('/##totalValueOfGoods##|##totalWeightOfGoods##|##totalValueOfCoupons##|##shippingFee##/siU', '10', $formula);
				
				if (preg_match('/[^-0-9\.\(\)\/\*\+\?\:\=\>\<\s]/siU', $formula)) {
					$objWidget->addError(sprintf('x'.$GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['feeFormula'], $objWidget->label));
					return false;
				}
				
				if (@eval('$priceFromFormula = '.$formula.';') === false) {
					$objWidget->addError(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['feeFormula'], $objWidget->label));
					return false;
				}
				
				return true;
			}
			return false;
		}
	}
?>