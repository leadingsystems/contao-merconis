<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

class ls_shop_cartX {
	protected $items = array();
	protected $couponsUsed = array();
	protected $itemsExtended = array();
	protected $calculation = array();

	
	/*
	 * Current object instance (Singleton)
	 */
	protected static $objInstance;

	/**
	 * Prevent direct instantiation (Singleton)
	 */
	protected function __construct() {
		/*
		 * FIXME: The singleton architecture doesn't really work here because when processing getCartFromSession()
		 * somewhere in the program flow ls_shop_cartX::getInstance() is being called before the first call of
		 * ls_shop_cartX::getInstance was able to store the self-reference.
		 *
		 * Of course this is an unintended flaw in the program design and it should be rearranged, so that the
		 * singleton concept here actually works as intended. However, simply preventing the cascaded initialization
		 * causes problems, because other parts of the program somehow rely on this behaviour. For example, determining
		 * the cart price and detecting payment and shipping methods' price limits won't work as expected.
		 *
		 * So, for now we leave it as it is, but it should definitely be improved.
		 */
		$this->getCartFromSession();
	}


	/*
	 * Prevent cloning of the object (Singleton)
	 */
	final private function __clone() {}


	/*
	 * Return the current object instance (Singleton)
	 */
	public static function getInstance() {
		if (!is_object(self::$objInstance)) {
            self::$objInstance = new self();
            self::$objInstance->calculate();

            if (!isset($GLOBALS['merconis_globals']['groupRestrictionsAlreadyCheckedInCart']) || !$GLOBALS['merconis_globals']['groupRestrictionsAlreadyCheckedInCart']) {
                $bln_reloadRequiredForGroupRestrictions = false;
                $GLOBALS['merconis_globals']['groupRestrictionsAlreadyCheckedInCart'] = true;

                $arr_groupSettings = ls_shop_generalHelper::getGroupSettings4User();
                foreach (self::$objInstance->itemsExtended as $str_productCartKey => $arr_cartItem) {
                    if (
                        $arr_cartItem['objProduct']->_useGroupRestrictions
                        && !in_array($arr_groupSettings['id'], $arr_cartItem['objProduct']->_allowedGroups)
                    ) {
                        $bln_reloadRequiredForGroupRestrictions = true;
                        ls_shop_cartHelper::setItemQuantity($str_productCartKey, -1);
                    }
                }

                if ($bln_reloadRequiredForGroupRestrictions) {
                    \Controller::reload();
                }
            }


			if (!isset($GLOBALS['merconis_globals']['merconisHookInitializeCartControllerAlreadyProcessed']) || !$GLOBALS['merconis_globals']['merconisHookInitializeCartControllerAlreadyProcessed']) {
				$GLOBALS['merconis_globals']['merconisHookInitializeCartControllerAlreadyProcessed'] = true;
				if (isset($GLOBALS['MERCONIS_HOOKS']['initializeCartController']) && is_array($GLOBALS['MERCONIS_HOOKS']['initializeCartController'])) {
					foreach ($GLOBALS['MERCONIS_HOOKS']['initializeCartController'] as $mccb) {
						$objMccb = \System::importStatic($mccb[0]);
						$objMccb->{$mccb[1]}($_SESSION['lsShopCart'], self::$objInstance->itemsExtended, self::$objInstance->calculation);
					}
				}
			}
		}
		return self::$objInstance;
	}
	
	/**
	 * Gets the items that are currently saved in the cart session and extends
	 * the item information as far as necessary. For every cart item there will be 
	 * a product object put in the array so that there is direct access to all product
	 * information at any time
	 */
	public function getCartFromSession() {
		$this->items = $_SESSION['lsShopCart']['items'];
		
		if (isset($this->items) && is_array($this->items)) {
			foreach ($this->items as $productCartKey => $arrCartItem) {
				/*
				 * IMPORTANT: The refresh parameter must be set to true when calling ls_shop_generalHelper::getObjProduct
				 * because otherwise detecting payment and shipping methods' price limits won't work.
				 */
				$objProduct = ls_shop_generalHelper::getObjProduct($productCartKey, __METHOD__, true);
				$this->itemsExtended[$productCartKey] = array(
					'objProduct' => $objProduct,
					'price' => !$objProduct->_variantIsSelected ? $objProduct->_priceAfterTax : $objProduct->_selectedVariant->_priceAfterTax,
					'weight' => !$objProduct->_variantIsSelected ? $objProduct->_weight : $objProduct->_selectedVariant->_weight,
					'quantity' => $arrCartItem['quantity']
				);
				
				/*
				 * Update each item's scalePriceKeyword to make sure that it is the
				 * correct scalePriceKeyword for the current customer/member group.
				 */
				$this->items[$productCartKey]['scalePriceKeyword'] = $objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_scalePriceKeyword : $objProduct->_scalePriceKeyword;
			}
		}
		
		/*
		 * Update the cart items stored in the session to make sure that the
		 * session holds the correct scalePriceKeywords as well.
		 */
		$_SESSION['lsShopCart']['items'] = $this->items;
	}
	
	protected function getCouponsUsed() {
		$this->couponsUsed = isset($_SESSION['lsShopCart']['couponsUsed']) && is_array($_SESSION['lsShopCart']['couponsUsed']) ? $_SESSION['lsShopCart']['couponsUsed'] : array();
	}

	public function __get($what) {
		switch ($what) {
			case 'items':
				return $this->items;
				break;
				
			case 'numItems':
				return is_array($this->items) ? count($this->items) : 0;
				break;

			case 'itemsExtended':
				return $this->itemsExtended;
				break;

			case 'calculation':
				return $this->calculation;
				break;
				
			case 'isEmpty':
				return count($this->items) <= 0;
				break;

			case 'couponsUsed':
				return $this->couponsUsed;
				break;
		}
	}

	/**
	 * Performs the complete calculation and saves the relevant values in an array. This calculation
	 * also takes care of coupons and payment and shipping methods.
	 */
	public function calculate() {
		$this->calculation = array(
			'items' => array(
				/*
				0 => array(
					'productCode' => null,
					'price' => null,
					'weight' => null,
					'quantity' => null,
					'priceCumulative' => null,
					'weightCumulative' => null,
					'taxClass' => null,
					'taxPercentage' => null
				)
				 */
			),
			'totalValueOfGoods' => array(
				/* 
				 * this array contains the totalValueOfGoods as an array of tax class specific sums.
				 * The array keys are the tax class ids. The sum of all goods without concerning about tax class differences
				 * is held in key 0 (zero).
				 */
				/*
				0 => null,
				1 => null,
				2 => null
				// ...
				 */
			),
			'totalWeightOfGoods' => null, // this variable contains the cumulative weight of all goods in the cart
			'couponValues' => array(
				/* 
				 * this array contains the couponValues for all used coupons as an array of tax class
				 * specific sums (the coupon value is split into parts)
				 * The array keys are the tax class ids. The total coupon value is held in key 0 (zero).
				 * The top level keys are the coupon ids.
				 */
				/*
				1 => array(
					0 => null,
					1 => null,
					2 => null
					// ...
				),
				2 => array()
				// ...
				*/
			),
			'paymentFee' => array(
				/* 
				 * this array contains the fee as an array of tax class specific values.
				 * The array keys are the tax class ids. The fee without concerning about tax class
				 * is held in key 0 (zero).
				 */
				/*
				'info' => array(),
				0 => null,
				1 => null,
				2 => null
				// ...
				 */	
			),
			'shippingFee' => array(
				/* 
				 * this array contains the fee as an array of tax class specific values.
				 * The array keys are the tax class ids. The fee without concerning about tax class
				 * is held in key 0 (zero).
				 */
				/*
				'info' => array(),
				0 => null,
				1 => null,
				2 => null
				// ...
				 */	
			),
			'total' => array(
				/* 
				 * this array contains the totalValue as an array of tax class specific sums.
				 * The array keys are the tax class ids. The totalValue without concerning about tax class differences
				 * is held in key 0 (zero).
				 */
				/*
				0 => null,
				1 => null,
				2 => null
				 */
				// ...				
			),
			'tax' => array(
				/* 
				 * this array contains the total tax values for each tax class.
				 * The array keys are the tax class ids.
				 */
				/*
				0 => null, // holds the total tax amount (the sum of the different tax class amounts)
				1 => null,
				2 => null
				 */
				// ...				
			),
			/*
			 * this boolean variable is true if tax is already inclusive and false if tax is exclusive
			 */
			'taxInclusive' => null,
			'invoicedAmount' => null,
			'invoicedAmountNet' => null
		);
		
		// ###### ACHTUNG: REIHENFOLGE WICHTIG! ####################
		$this->calculation['items'] = $this->getCalculatedItems();
		$this->calculation['totalValueOfGoods'] = $this->getTotalValueOfGoods($this->calculation['items']);
		$this->calculation['totalWeightOfGoods'] = $this->getTotalWeightOfGoods($this->calculation['items']);
		$this->calculation['couponValues'] = $this->getCouponValues($this->calculation['totalValueOfGoods']);
		$this->calculation['shippingFee'] = $this->getShippingFee();
		$this->calculation['paymentFee'] = $this->getPaymentFee();
		$this->calculation['total'] = $this->getTotal();
		$this->calculation['taxInclusive'] = $this->checkWhetherTaxIsInclusive();
		$this->calculation['tax'] = $this->getTax();
		$this->calculation['invoicedAmount'] = $this->getInvoicedAmount();
		$this->calculation['invoicedAmountNet'] = $this->getInvoicedAmountNet();
		// #########################################################
		
		/* *
		echo '<pre>';
		print_r($arrCalc);
		echo '</pre>';
		echo '<hr />';
		/* */
	}

	protected function getPaymentFee() {
		$arrFee = array(0 => 0);
		
		$methodToCalculate = null;
		if (isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedPaymentMethod']) && ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedPaymentMethod']) {
			$methodToCalculate = ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedPaymentMethod'];
		} else if (isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['cheapestPossiblePaymentMethod']) && ls_shop_checkoutData::getInstance()->arrCheckoutData['cheapestPossiblePaymentMethod']) {
			$methodToCalculate = ls_shop_checkoutData::getInstance()->arrCheckoutData['cheapestPossiblePaymentMethod'];
		}
		
		if ($methodToCalculate) {
			$methodInfo = ls_shop_generalHelper::getPaymentMethodInfo($methodToCalculate);
//			print_r($methodInfo);
			$arrFee[0] = $methodInfo['feePrice'];
			$arrFee[$methodInfo['steuersatz']] = $methodInfo['feePrice'];
			$arrFee['info'] = $methodInfo;
		}		
		return $arrFee;
	}
	
	protected function getShippingFee() {
		$arrFee = array(0 => 0);
		
		$methodToCalculate = null;
		if (isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedShippingMethod']) && ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedShippingMethod']) {
			$methodToCalculate = ls_shop_checkoutData::getInstance()->arrCheckoutData['selectedShippingMethod'];
		} else if (isset(ls_shop_checkoutData::getInstance()->arrCheckoutData['cheapestPossibleShippingMethod']) && ls_shop_checkoutData::getInstance()->arrCheckoutData['cheapestPossibleShippingMethod']) {
			$methodToCalculate = ls_shop_checkoutData::getInstance()->arrCheckoutData['cheapestPossibleShippingMethod'];
		}
		
		if ($methodToCalculate) {
			$methodInfo = ls_shop_generalHelper::getShippingMethodInfo($methodToCalculate);
//			print_r($methodInfo);
			$arrFee[0] = $methodInfo['feePrice'];
			$arrFee[$methodInfo['steuersatz']] = $methodInfo['feePrice'];
			$arrFee['info'] = $methodInfo;
		}		
		return $arrFee;
	}
	
	protected function getCouponValues($arrTotalValueOfGoods) {
		ls_shop_cartHelper::revalidateCouponsUsed();
		$this->getCouponsUsed();

		$arrCouponValues = array();
		foreach ($this->couponsUsed as $couponID => $arrCouponInfo) {
			$couponValue = 0;
			
			// only if the coupon doesn't have any errors the coupon value is determined, otherwise it has to be 0
			if (!$arrCouponInfo['hasErrors']) {
				// get the coupon value. if it is a percentaged coupon, get the amount 
				$couponValue = ls_shop_generalHelper::ls_roundPrice(($arrCouponInfo['extendedInfo']['couponValueType'] == 'percentaged' ? $arrTotalValueOfGoods[0] / 100 * $arrCouponInfo['extendedInfo']['couponValue'] : $arrCouponInfo['extendedInfo']['couponValue']));
				
				// if the coupon value is bigger than the total value of goods the coupon value will be set equal to the total value of goods
				if ($couponValue > $arrTotalValueOfGoods[0]) {
					$couponValue = $arrTotalValueOfGoods[0];
				}
				
				// negate the coupon value becuase it has to be a subtraction
				$couponValue = $couponValue * -1;
			}
			
			$arrCouponValues[$couponID] = array(
				0 => $couponValue
			);
			
			// the coupon value will only be split in the tax class parts if it is unequal to 0
			if ($couponValue != 0) {
				foreach ($arrTotalValueOfGoods as $taxClassID => $value) {
					if ($taxClassID == 0) {
						continue;
					}
					$arrCouponValues[$couponID][$taxClassID] = ls_shop_generalHelper::ls_roundPrice($value / $arrTotalValueOfGoods[0] * $couponValue);
				}
				
				/*
				 * Check whether the sum of the split coupon values equals the original coupon value.
				 * If not, get the difference and add it to one of the split parts (simply the next best).
				 * This procedure is needed to make sure that the sum of the parts always adds up even
				 * if there are rounding inaccuracies.
				 */
				$sumOfSplitCouponParts = 0;
				$tmpFirstTaxClassID = 0;
				foreach ($arrCouponValues[$couponID] as $taxClassID => $value) {
					if ($taxClassID == 0) {
						continue;
					}
					if (!$tmpFirstTaxClassID) {
						$tmpFirstTaxClassID = $taxClassID;
					}
					$sumOfSplitCouponParts = $sumOfSplitCouponParts + $value;
				}
				if ($sumOfSplitCouponParts != $couponValue) {
					$arrCouponValues[$couponID][$tmpFirstTaxClassID] = $arrCouponValues[$couponID][$tmpFirstTaxClassID] + ($couponValue - $sumOfSplitCouponParts);
				}
				/* */
			}			
		}
		return $arrCouponValues;
	}

	protected function getTotalValueOfGoods($arrItems) {
		$arrTotalValueOfGoods = array();
		
		/*
		 * sum all cumulative item values without concerning about tax classes in array key 0
		 * and separated by tax classes in the array keys with the tax class ids
		 */
		$arrTotalValueOfGoods[0] = 0;
		foreach ($arrItems as $item) {
			$arrTotalValueOfGoods[0] = $arrTotalValueOfGoods[0] + $item['priceCumulative'];
			if (!isset($arrTotalValueOfGoods[$item['taxClass']])) {
				$arrTotalValueOfGoods[$item['taxClass']] = 0;
			}
			$arrTotalValueOfGoods[$item['taxClass']] = $arrTotalValueOfGoods[$item['taxClass']] + $item['priceCumulative'];
		}
		
		return $arrTotalValueOfGoods;
	}
	
	protected function getTotalWeightOfGoods($arrItems) {
		$totalWeightOfGoods = 0;
		foreach ($arrItems as $item) {
			$totalWeightOfGoods = $totalWeightOfGoods + $item['weightCumulative'];
		}
		return $totalWeightOfGoods;
	}
	
	protected function getTotal() {
		$arrTotal = array();

		foreach ($this->calculation['totalValueOfGoods'] as $taxClassID => $value) {
			if (!isset($arrTotal[$taxClassID])) {
				$arrTotal[$taxClassID] = 0;
			}
			$arrTotal[$taxClassID] = $arrTotal[$taxClassID] + $value;
		}
		
		foreach ($this->calculation['couponValues'] as $couponID => $couponValues) {
			foreach ($couponValues as $taxClassID => $value) {
				if (!isset($arrTotal[$taxClassID])) {
					$arrTotal[$taxClassID] = 0;
				}
				$arrTotal[$taxClassID] = $arrTotal[$taxClassID] + $value;
			}
		}
		
		foreach ($this->calculation['paymentFee'] as $key => $value) {
			if ($key === 'info') {
				continue;
			}
			$taxClassID = $key;
			if (!isset($arrTotal[$taxClassID])) {
				$arrTotal[$taxClassID] = 0;
			}
			$arrTotal[$taxClassID] = $arrTotal[$taxClassID] + $value;
		}
		
		foreach ($this->calculation['shippingFee'] as $key => $value) {
			if ($key === 'info') {
				continue;
			}
			$taxClassID = $key;
			if (!isset($arrTotal[$taxClassID])) {
				$arrTotal[$taxClassID] = 0;
			}
			$arrTotal[$taxClassID] = $arrTotal[$taxClassID] + $value;
		}
		
		return $arrTotal;
	}

	protected function checkWhetherTaxIsInclusive() {
		$outputPriceType = ls_shop_generalHelper::getOutputPriceType();
		$taxInclusive = $outputPriceType == 'brutto' ? true : false;
		return $taxInclusive;
	}

	protected function getTax() {
		$arrTax = array();
		$arrTax[0] = 0; // holds the total tax amount
		foreach ($this->calculation['total'] as $taxClassID => $value) {
			if ($taxClassID == 0) {
				continue;
			}
			$taxPercentage = ls_shop_generalHelper::getCurrentTax($taxClassID);
			$arrTax[$taxClassID] = ls_shop_generalHelper::ls_roundPrice($this->calculation['taxInclusive'] ? ls_mul(ls_div($value, ls_add(100, $taxPercentage)), $taxPercentage) : ls_mul(ls_div($value, 100), $taxPercentage));
			$arrTax[0] = ls_add($arrTax[0], $arrTax[$taxClassID]);
		}
		return $arrTax;
	}
	
	protected function getInvoicedAmount() {
		$invoicedAmount = $this->calculation['total'][0];
		if (!$this->calculation['taxInclusive']) {
			foreach ($this->calculation['tax'] as $taxClassID => $value) {
				if ($taxClassID == 0) {
					continue;
				}
				$invoicedAmount = ls_add($invoicedAmount, $value);
			}
		}
		return $invoicedAmount;
	}
	
	/**
	 * Diese Funktion gibt den Rechnungsbetrag netto zurück.
	 */
	protected function getInvoicedAmountNet() {
		$tax = 0;
		if (is_array($this->calculation['tax'])) {
			foreach ($this->calculation['tax'] as $taxClassID => $taxValue) {
				if ($taxClassID == 0) {
					continue;
				}
				$tax = ls_add($tax, $taxValue);
			}
		}
		return ls_shop_generalHelper::ls_roundPrice(ls_sub($this->calculation['invoicedAmount'], $tax));
	}

	protected function getCalculatedItems() {
		$arrItems = array();
		foreach ($this->itemsExtended as $productCartKey => $itemExtended) {
			$tmpPriceCumulative = ls_shop_generalHelper::ls_roundPrice(ls_mul($itemExtended['price'], $itemExtended['quantity']));
			$tmpWeightCumulative = ls_shop_generalHelper::ls_roundPrice(ls_mul($itemExtended['weight'], $itemExtended['quantity']));
			$arrItems[$productCartKey] = array(
				'productVariantID' => ls_shop_generalHelper::getProductVariantIDFromCartKey($productCartKey),
				'productCartKey' => $productCartKey,
				'price' => $itemExtended['price'],
				'weight' => $itemExtended['weight'],
				'quantity' => $itemExtended['quantity'],
				'priceCumulative' => $tmpPriceCumulative,
				'weightCumulative' => $tmpWeightCumulative,
				'taxClass' => $itemExtended['objProduct']->_steuersatz,
				'taxPercentage' => ls_shop_generalHelper::getCurrentTax($itemExtended['objProduct']->_steuersatz)
			);
		}
		return $arrItems;
	}
}