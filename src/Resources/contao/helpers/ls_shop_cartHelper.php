<?php
namespace Merconis\Core;

use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;

class ls_shop_cartHelper {
	public static function initializeEmptyCart() {
		if (!$_SESSION['lsShopCart'] || !is_array($_SESSION['lsShopCart'])) {
			$_SESSION['lsShopCart'] = array();
		}

		if (!$_SESSION['lsShopCart']['items'] || !is_array($_SESSION['lsShopCart']['items'])) {
			$_SESSION['lsShopCart']['items'] = array();
		}
	}

	/*
	 * Diese Funktion bereinigt die eingegebene Menge entsprechend der für das Produkt definierten Bestimmungen.
	 * Diese Funktion gibt dabei vor allem eine für Berechnungen verwertbare Zahl zurück. Tausendertrennzeichen
	 * werden dabei verworfen.
	 * Diese Funktion kann mit allen Werten gefüttert werden, die der reguläre Ausdruck des Widgets durchlässt,
	 * also Zahlen mit Punkt oder Komma als Dezimaltrenner, sowie auch vorgenullte Zahlen.
	 * Es wird geprüft, welche Mengenangaben für ein Produkt erlaubt sind und die eingegebene Menge dementsprechend korrigiert.
	 *
	 * Bsp. (Produkt, das nur in ganzen Stück verkauft wird): Aus "10,6" wird "11", "10.6" wird "11", aus "056,4" wird "56" usw.
	 */
	public static function cleanQuantity(&$objProduct, $quantity) {
		if ($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator']) {
			$quantity = preg_replace('/\\'.$GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'].'/siU', '', $quantity); // Entfernen des Tausendertrennzeichens
		}
		$quantity = preg_replace('/\\'.$GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'].'/siU', '.', $quantity); // Ersetzen der Dezimaltrennzeichen durch den Punkt
		$quantity = number_format($quantity, $objProduct->_quantityDecimals, '.', '');
		return $quantity;
	}

	/*
	 * Diese Funktion gibt die Gesamtmenge zurück, die von einer Produkt-Varianten-ID verteilt auf verschiedene
	 * Warenkorb-Positionen insgesamt im Warenkorb enthalten ist.
	 * Als optionaler zweiter Parameter kann ein cartKey angegeben werden, der aus der Kumulierung ausgeschlossen wird.
	 */
	public static function getCumulatedCartQuantityForCartItemsWithSameProductVariantID($productVariantID = null, $cartKey = null) {
		if (!$productVariantID) {
			return 0;
		}

		$cumulatedQuantity = 0;
		foreach ($_SESSION['lsShopCart']['items'] as $cartItemProductCartKey => $arrCartItem) {
			if (ls_shop_generalHelper::getProductVariantIDFromCartKey($cartItemProductCartKey) != $productVariantID) {
				continue;
			}
			if ($cartKey && $cartKey == $cartItemProductCartKey) {
				continue;
			}
			$cumulatedQuantity = ls_add($cumulatedQuantity, $arrCartItem['quantity']);
		}
		return $cumulatedQuantity;
	}

	/*
	 * Diese Funktion prüft, ob das gewünschte Produkt bzw. die gewünschte Variante in der gewünschten Menge verfügbar ist
	 * und gibt (wenn genügend Lagerbestand verfügbar ist) die gewünschte Menge oder die maximal verfügbare Menge zurück.
	 *
	 * ACHTUNG: Bei dieser Funktion macht es einen Unterschied, ob die verfügbare Menge für eine einzelne Warenkorb-Position
	 * ermittelt werden soll oder für die kumulierte Menge aller Warenkorbpositionen derselben Produkt-Varianten-ID. Im ersten
	 * Fall muss vom Lagerbestand der Produkt-Varianten-ID die Menge der anderen Warenkorb-Positionen, die von der selben
	 * Produkt-Varianten-ID sind, abgezogen werden, um eben den noch verfügbaren Anteil für die zu prüfende Warenkorb-Position
	 * zu ermitteln. Im zweiten Fall bezieht sich die ganze Prüfung ja von vorneherein auf eine Produkt-Varianten-ID als
	 * Ganzes, sodass die Verteilung auf einzelne Warenkorb-Positionen nicht berücksichtigt werden muss (und auch NICHT DARF,
	 * da dies sonst zu einem Fehler führen würde.)
	 */
	public static function getAvailableQuantity($cartKey, $desiredQuantity, $cartPositionMode = true) {
		$objProduct = ls_shop_generalHelper::getObjProduct($cartKey, __METHOD__);
		$objProductOrVariant = $objProduct->_variantIsSelected ? $objProduct->_selectedVariant : $objProduct;

		if (!$objProductOrVariant->_useStock || $objProductOrVariant->_allowOrdersWithInsufficientStock) {
			/*
			 * Soll der Lagerbestand nicht berücksichtigt werden oder ist die Bestellung auch ohne ausreichenden Lagerbestand
			 * erlaubt, so wird die gewünschte Menge als verfügbare Menge zurückgegeben
			 */
			return $desiredQuantity;
		}

		if ($cartPositionMode) {
			/*
			 * Da die verfügbare Menge eines Produktes sich auf mehrere cartItems (Warenkorbpositionen) verteilt und da hier die Menge zurückgegeben
			 * werden soll, die für das aktuelle cartItem noch verfügbar ist, muss hier berücksichtigt werden, welche Menge derselben Produkt-Varianten-ID
			 * bereits auf andere Warenkorb-Positionen verteilt ist.
			 */
			$quantityInCartForOtherCartItems = ls_shop_cartHelper::getCumulatedCartQuantityForCartItemsWithSameProductVariantID($objProductOrVariant->_productVariantID, $cartKey);

			/*
			 * Der für die aktuelle Prüfung relevante, noch verfügbare Lagerbestand errechnet sich also aus dem Lagerbestand für die Produkt-Varianten-ID
			 * abzüglich der auf andere Warenkorb-Positionen bereits verteilten Menge derselben Produkt-Varianten-ID
			 */
			$stockMinusQuantityInCartForOtherCartItems = $objProductOrVariant->_stock - $quantityInCartForOtherCartItems;

			$stock = $stockMinusQuantityInCartForOtherCartItems;
		} else {
			/*
			 * Ohne die spezielle Berücksichtigung einzelner Warenkorb-Positionen wird als für die Prüfung relevanter Lagerbestand der tatsächliche
			 * Lagerbestand der Produkt-Varianten-ID herangezogen.
			 */
			$stock = $objProductOrVariant->_stock;
		}


		/*
		 * Ist die gewünschte Menge kleiner als die verfügbare Menge, so wird die gewünschte Menge unverändert zurückgegeben,
		 * ist sie nicht kleiner, so kann also maximal die verfügbare Menge bestellt werden, es wird also die im Lager vorhandene
		 * Menge zurückgegeben, kleinstenfalls aber 0.
		 */
		$availableQuantity = $desiredQuantity < $stock ? $desiredQuantity : ($stock > 0 ? $stock : 0);

		return $availableQuantity;
	}

	/*
	 * Diese Funktion trägt zu einer Warenkorb-Position die Menge ein und sorgt bei
	 * Übergabe einer negativen Menge für die Löschung der Position
	 */
	public static function setItemQuantity($productCartKey, $quantity, $checkStock = true) {
		/*
		 * Nur wenn die gewünschte Menge größer oder gleich 0 ist (Nullmengen sind im Warenkorb erlaubt, um bei lagerbestandsbedingten
		 * Mengenanpassungen keine Position komplett verwerfen zu müssen) wird die verfügbare Menge ermittelt und
		 * die Menge der Warenkorbposition aktualisiert
		 */
		if ($quantity >= 0) {
			$availableQuantity = $checkStock ? ls_shop_cartHelper::getAvailableQuantity($productCartKey, $quantity) : $quantity;
			$_SESSION['lsShopCart']['items'][$productCartKey]['quantity'] = $availableQuantity;
		}

		if (isset($availableQuantity) && $availableQuantity != $quantity) {
			$objProduct4Msg = ls_shop_generalHelper::getObjProduct($productCartKey, __METHOD__);
			ls_shop_msg::setMsg(array(
				'class' => 'setItemQuantity',
				'reference' => $productCartKey,
				'arrDetails' => array(
					'desiredQuantity' => $quantity,
					'availableQuantity' => $availableQuantity,
					'quantityUnit' => $objProduct4Msg->_quantityUnit,
					'quantityDecimals' => $objProduct4Msg->_quantityDecimals
				)
			));
		}

		/*
		 * Ist die übergebene Menge negativ (Lösch-Anforderung!) oder die ermittelte verfügbare Menge (eigentlich unmöglich),
		 * so wird die Position aus dem Warenkorb entfernt und - da von dieser Funktion die verfügbare Menge als Rückgabewert
		 * erwartet wird - 0 zurückgegeben.
		 */
		if ($quantity < 0 || $availableQuantity < 0) {
			unset($_SESSION['lsShopCart']['items'][$productCartKey]);
			return 0;
		}

		return $availableQuantity;
	}

	/*
	 * Diese Funktion fügt ein Produkt bzw. eine Variante eines Produktes dem Warenkorb in der
	 * gewünschten Menge hinzu. Sofern das Flag 'checkStock' nicht explizit als false übergeben wird,
	 * so findet die Prüfung des Lagerbestandes statt, sodass nur die wirklich verfügbare Menge
	 * dem Warenkorb hinzugefügt wird.
	 *
	 * Ist ein Produkt bzw. eine Variante eines Produktes bereits im Warenkorb enthalten, so wird
	 * die gewünschte Menge hinzugefügt.
	 *
	 * Die Funktion gibt ein Array mit Informationen über die gewünschte und tatsächlich hinzugefügte Menge zurück
	 */
	public static function addToCart($productVariantID, $quantity, $checkStock = true) {
        /**
         * @var ls_shop_product $objProduct
         */
		$objProduct = ls_shop_generalHelper::getObjProduct($productVariantID, __METHOD__);

		$desiredQuantity = ls_shop_cartHelper::cleanQuantity($objProduct, $quantity);
		/*
		 * Ist der aktuelle cartKey der ProduktVarianten-ID noch nicht im Warenkorb enthalten,
		 * so wird sie eingetragen, ist sie schon vorhanden, so wird nur die Menge geupdatet.
		 */
		if (!isset($_SESSION['lsShopCart']['items'][$objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_cartKey : $objProduct->_cartKey])) {
			$arrItemInfoToAddToCart = array(
				'quantity' => 0,
				'scalePriceKeyword' => $objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_scalePriceKeyword : $objProduct->_scalePriceKeyword
			);

			if (isset($GLOBALS['MERCONIS_HOOKS']['beforeAddToCart']) && is_array($GLOBALS['MERCONIS_HOOKS']['beforeAddToCart'])) {
				foreach ($GLOBALS['MERCONIS_HOOKS']['beforeAddToCart'] as $mccb) {
					$objMccb = \System::importStatic($mccb[0]);
					$arrItemInfoToAddToCart = $objMccb->{$mccb[1]}($arrItemInfoToAddToCart, $objProduct);
				}
			}

			$_SESSION['lsShopCart']['items'][$objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_cartKey : $objProduct->_cartKey] = $arrItemInfoToAddToCart;

			if ($objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_hasCustomizer : $objProduct->_hasCustomizer) {
                $objProduct->saveCustomizerForCurrentCartKey();
            } else if ($objProduct->_hasConfigurator) {
                $objProduct->saveConfiguratorForCurrentCartKey();
            }
		}

		/*
		 * Ermitteln der für dieses Produkt im Warenkorb zu hinterlegenden Menge
		 */
		$newQuantity = ls_add($_SESSION['lsShopCart']['items'][$objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_cartKey : $objProduct->_cartKey]['quantity'], $desiredQuantity);
		$quantityCurrentlyInCart = ls_shop_cartHelper::setItemQuantity($objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_cartKey : $objProduct->_cartKey, $newQuantity, $checkStock);
		$quantityPutInCart = ls_sub($desiredQuantity, ls_sub($newQuantity,$quantityCurrentlyInCart));

		ls_shop_msg::setMsg(array(
			'class' => 'addedToCart',
			'reference' => $productVariantID,
			'arrDetails' => array(
				'desiredQuantity' => $desiredQuantity,
				'quantityPutInCart' => $quantityPutInCart,
				'quantityCurrentlyInCart' => $quantityCurrentlyInCart,
				'stockNotSufficient' => $desiredQuantity != $quantityPutInCart ? true : false,
				'cartKeyCurrentlyPutInCart' => $objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_cartKey : $objProduct->_cartKey
			)
		));

		if (isset($GLOBALS['MERCONIS_HOOKS']['addToCart']) && is_array($GLOBALS['MERCONIS_HOOKS']['addToCart'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['addToCart'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$objMccb->{$mccb[1]}($objProduct, $desiredQuantity, $quantityPutInCart);
			}
		}

		// Zurückgegeben wird die Info, welche Menge gewünscht war und welche tatsächlich in den Warenkorb gelegt wurde
		return array(
			'desiredQuantity' => $desiredQuantity,
			'quantityPutInCart' => $quantityPutInCart,
			'stockNotSufficient' => $desiredQuantity != $quantityPutInCart ? true : false,
			'cartKeyCurrentlyPutInCart' => $objProduct->_variantIsSelected ? $objProduct->_selectedVariant->_cartKey : $objProduct->_cartKey
		);
	}

	/**
	 * Hier wird geprüft, ob alle enthaltenen Produkte bestellt werden können,
	 * oder ob eines der Produkte nicht "orderAllowed" true hat.
	 */
	public static function validateOrderPermissionOfCartPositions() {
		$blnValid = true;
		if (isset($_SESSION['lsShopCart']['items']) && is_array($_SESSION['lsShopCart']['items'])) {
			foreach ($_SESSION['lsShopCart']['items'] as $cartItemProductCartKey => $arrCartItem) {
				/*
				 * Remove validation messages that might still exist from a previous validation
				 */
				ls_shop_msg::delMsg('cartPositionOrderNotAllowed', $cartItemProductCartKey);

				$objProduct = ls_shop_generalHelper::getObjProduct($cartItemProductCartKey, __METHOD__);
				if (!$objProduct->_orderAllowed || $arrCartItem['quantity'] <= 0) {
					$blnValid = false;
					ls_shop_msg::setMsg(array(
						'class' => 'cartPositionOrderNotAllowed',
						'reference' => $cartItemProductCartKey
					));
				}
			}
		}
		return $blnValid;
	}

	/**
	 * Diese Funktion prüft für alle Positionen im Warenkorb, ob der Lagerbestand ausreicht, und gibt false zurück, falls
	 * dies für mindestens eine Position nicht zutrifft.
	 */
	public static function checkCartPositionsStockSufficient() {
		$stockNotSufficientForAtLeastOneItem = false;

		/*
		 * Zunächst müssen die Warenkorbpositionen nach tatsächlichen Produkten/Varianten gruppiert werden.
		 * Mehrere durch unterschiedliche Konfigurator-Einstellungen entstandene Instanzen desselben Produkts
		 * bzw. derselben Variante dürfen nicht einzeln geprüft werden, da sie allesamt vom gleichen Lagerbestand
		 * abgehen. Bei der einzelnen Prüfung würde unter Umständen bei jeder einzelnen Position ein ausreichender
		 * Lagerbestand ermittelt werden, obwohl der Lagerbestand für die Summe der gleichen Produkte nicht ausreicht.
		 * Das liegt daran, dass der Lagerbestand nicht sofort nach einer erfolgreichen Prüfung reduziert wird,
		 * weil die Bestellung ja in diesem Moment noch gar nicht abgeschlossen wird, sondern erst, wenn alle
		 * Prüfungen positiv waren.
		 */
		$tmpArrCartItemsGrouped = array();
		foreach ($_SESSION['lsShopCart']['items'] as $cartItemProductCartKey => $arrCartItem) {
			$productVariantID = ls_shop_generalHelper::getProductVariantIDFromCartKey($cartItemProductCartKey);
			if (!isset($tmpArrCartItemsGrouped[$productVariantID])) {
				$tmpArrCartItemsGrouped[$productVariantID] = array(
					'requiredQuantity' => 0,
					'availableQuantity' => 0,
					'cartKeysForThisProductVariantID' => array(),
				);
			}

			// Summieren der von der Produkt-Varianten-ID benötigten Menge
			$tmpArrCartItemsGrouped[$productVariantID]['requiredQuantity'] = ls_add($tmpArrCartItemsGrouped[$productVariantID]['requiredQuantity'], $arrCartItem['quantity']);

			// Festhalten der Warenkorb-Positionen, die sich auf diese Produkt-Varianten-ID beziehen
			$tmpArrCartItemsGrouped[$productVariantID]['cartKeysForThisProductVariantID'][] = $cartItemProductCartKey;
		}


		/*
		 * Durchlaufen des temporären, gruppierten Arrays. Für jede Produkt-Varianten-ID wird geprüft,
		 * wieviel noch auf Lager ist, welche Menge also tatsächlich zur Verfügung steht.
		 * Diese Information wird auch im temporären Array festgehalten.
		 */
		foreach ($tmpArrCartItemsGrouped as $productVariantID => $cartItemGroupInfo) {
			$tmpArrCartItemsGrouped[$productVariantID]['availableQuantity'] = ls_shop_cartHelper::getAvailableQuantity($productVariantID, $cartItemGroupInfo['requiredQuantity'], false);
		}

		/*
		 * Erneut durchlaufen des temporären, gruppierten Arrays. Prüfen, für welche Produkt-Varianten-ID die
		 * verfügbare Menge ggf. nicht ausreicht.
		 */
		foreach ($tmpArrCartItemsGrouped as $productVariantID => $cartItemGroupInfo) {
			if ($cartItemGroupInfo['availableQuantity'] >= $cartItemGroupInfo['requiredQuantity']) {
				// Weiter zur nächsten Produkt-Varianten-ID, wenn die verfügbare Menge ausreicht
				continue;
			} else {
				// Rückgabewert setzen falls die verfügbare Menge für diese Produkt-Varianten-ID nicht ausreicht
				$stockNotSufficientForAtLeastOneItem = true;

				/*
				 * Durchlaufen der dieser Produkt-Varianten-ID zugeordneten cartKeys, um bei den einzelnen
				 * Warenkorb-Positionen die Korrektur der Bestellmenge vorzunehmen.
				 */
				foreach ($cartItemGroupInfo['cartKeysForThisProductVariantID'] as $cartKey) {
					/*
					 * Von der verfügbaren Menge wird die Menge der Warenkorb-Position abgezogen. Ist die verfügbare Menge
					 * danach noch größer/gleich 0, so war die verfügbare Menge für diese Warenkorb-Position noch ausreichend
					 * und es kann direkt mit der nächsten fortgefahren werden (continue).
					 *
					 * Ist die verfügbare Menge nach Abzug der Menge der Warenkorb-Position negativ, so ist diese negative
					 * Menge die Fehlmenge, die von der Menge der Warenkorb-Position abgezogen werden muss.
					 */

					// Abziehen der Warenkorb-Positions-Menge von der verfügbaren Menge
					$cartItemGroupInfo['availableQuantity'] = $cartItemGroupInfo['availableQuantity'] - $_SESSION['lsShopCart']['items'][$cartKey]['quantity'];

					if ($cartItemGroupInfo['availableQuantity'] >= 0) {
						// Verbleibende verfügbare Menge größer/gleich 0, die verfügbare Menge war also ausreichend für diese Warenkorb-Position
						continue;
					} else {
						/*
						 * Verbleibende verfügbare Menge kleiner 0, also Fehlmenge.
						 * Um die Menge der Warenkorb-Position auf die tatsächlich verfügbare Menge zu aktualisieren,
						 * muss von der Warenkorb-Positions-Menge die Fehlmenge abgezogen werden.
						 */

						// Die Fehlmenge ist der Betrag der negativen verfügbaren Menge
						$fehlmenge = $cartItemGroupInfo['availableQuantity'] * -1;

						// Die verfügbare Menge wird auf 0 gesetzt, da die Fehlmenge von der Warekorb-Position abgezogen wird und den Lagerbestand nicht tatsächlich reduziert
						$cartItemGroupInfo['availableQuantity'] = 0;

						/*
						 * Setzen der Warkorb-Positions-Menge auf die Menge, die für diese Position tatsächlich verfügbar ist. Hierzu
						 * wird von der aktuell hinterlegten Menge die Fehlmenge abgezogen.
						 *
						 * Da die ermittelte verfügbare Menge vor dem Abzug der Warenkorb-Positionsmenge nicht kleiner 0 sein kann,
						 * muss als neue Warenkorb-Positions-Menge ein Wert von mindestens 0 errechnet werden. Da ein negativer
						 * Wert aber auf keinen Fall erlaubt wäre, wird hier vorsichtshalber dennoch sichergestellt, dass im Falle
						 * eines ermittelten negativen Wertes die Warenkorb-Positions-Menge auf 0 und nicht weniger gesetzt wird.
						 */
						$newItemQuantity = $_SESSION['lsShopCart']['items'][$cartKey]['quantity'] - $fehlmenge;
						if ($newItemQuantity < 0) {
							$newItemQuantity = 0;
						}

						$objProduct4Msg = ls_shop_generalHelper::getObjProduct($cartKey, __METHOD__);
						ls_shop_msg::setMsg(array(
							'class' => 'checkCartPositionsStockSufficient',
							'reference' => $cartKey,
							'arrDetails' => array(
								'originalQuantity' => $_SESSION['lsShopCart']['items'][$cartKey]['quantity'],
								'shortage' => $fehlmenge,
								'newQuantity' => $newItemQuantity,
								'quantityUnit' => $objProduct4Msg->_quantityUnit,
								'quantityDecimals' => $objProduct4Msg->_quantityDecimals

							)
						));

						ls_shop_cartHelper::setItemQuantity($cartKey, $newItemQuantity);
					}
				}
			}
		}

		if ($stockNotSufficientForAtLeastOneItem) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Diese Funktion reduziert den Lagerbestand für alle im Warenkorb enthaltenen Artikel um die im Warenkorb enthaltene Menge
	 */
	public static function reduceStockForCartPositions() {
		foreach ($_SESSION['lsShopCart']['items'] as $cartItemProductCartKey => $arrCartItem) {
			$objProduct = ls_shop_generalHelper::getObjProduct($cartItemProductCartKey, __METHOD__, true);
			if ($arrCartItem['quantity'] > 0) {
				$objProduct->changeStock($arrCartItem['quantity'] * -1);
			}
		}
	}

	/**
	 * Diese Funktion zählt den Bestellungszähler für die im Warenkorb enthaltenen Produkte um eins hoch
	 */
	public static function countSalesForCartPositions() {
		foreach ($_SESSION['lsShopCart']['items'] as $cartItemProductCartKey => $arrCartItem) {
			$objProduct = ls_shop_generalHelper::getObjProduct($cartItemProductCartKey, __METHOD__);
			$objProduct->countSale();
		}
	}

	/*
	 * Diese Funktion aktualisiert die Menge einer Warenkorb-Position
	 */
	public static function updateCartItem($productCartKey, $quantity) {
		$objProduct = ls_shop_generalHelper::getObjProduct($productCartKey, __METHOD__);
		/*
		 * Ist die Quantity < 0, so wird die Position aus dem Warenkorb entfernt
		 */
		ls_shop_cartHelper::setItemQuantity($productCartKey, ls_shop_cartHelper::cleanQuantity($objProduct, $quantity));
	}

	/*
	 * Diese Funktion prüft einen Coupon auf seine Gültigkeit
	 */
	public static function validateCoupon($couponID = false, $getCouponBy = 'id') {
		if (!$couponID) {
			error_log('no couponID given');
			return false;
		}

		$arrErrors = array(
			'doesNotExist' => false,
			'minimumOrderValueNotReached' => false,
			'notYetValid' => false,
			'noLongerValid' => false,
			'onlyOneCouponAllowed' => false,
			'notValidForCustomerGroup' => false
		);

		$getByField = 'id';
		if ($getCouponBy == 'couponCode') {
			$getByField = 'couponCode';
		}

		$objCoupon = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_coupon`
			WHERE		`".$getByField."` = ?
		")
			->limit(1)
			->execute($couponID);

		if (!$objCoupon->numRows) {
			$arrErrors['doesNotExist'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text004'];
			return $arrErrors;
		}
		$objCoupon->first();

		if (!$objCoupon->published) {
			$arrErrors['doesNotExist'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text004'];
			return $arrErrors;
		}

		/*
		 * Prüfen, ob das Einlösen des Gutscheins eine Gutschein-Kombination bedeutet (sprich: ob damit 2 oder mehr Gutscheine
		 * gleichzeitig eingetragen sind) und diese erlaubt ist
		 */
		/*
		 * FIXME: An dieser Stelle wird eine localconfig-Grundeinstellung geprüft, die zunächst aber absichtlich noch gar nicht angeboten wird,
		 * es wird deshalb mit dem angenommenen Standardwert gearbeitet.
		 */
		if (!isset($GLOBALS['TL_CONFIG']['ls_shop_allowCouponCombinations'])) {
			$GLOBALS['TL_CONFIG']['ls_shop_allowCouponCombinations'] = false;
		}
		if (!$GLOBALS['TL_CONFIG']['ls_shop_allowCouponCombinations']) {
			if (is_array($_SESSION['lsShopCart']['couponsUsed'])) {
				$arrCouponArrayKeys = array_keys($_SESSION['lsShopCart']['couponsUsed']);
				if (count($_SESSION['lsShopCart']['couponsUsed']) && $arrCouponArrayKeys[0] != $objCoupon->id) {
					$arrErrors['onlyOneCouponAllowed'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text008'];
					return $arrErrors;
				}
			}
		}

		if ($objCoupon->limitNumAvailable && $objCoupon->numAvailable < 1) {
			$arrErrors['numAvailableNotOk'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text010'];
		}

		/*
		 * Prüfen, ob Gültigkeitszeitraum okay
		 */
		if ($objCoupon->start > time()) {
			$arrErrors['notYetValid'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text005'];
		} else if (time() > $objCoupon->stop) {
			$arrErrors['noLongerValid'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text006'];
		}

		/*
		 * Prüfen, ob Mindestbestellwert erreicht
		 */
		if (!ls_shop_generalHelper::check_minimumOrderValueIsReached($objCoupon->minimumOrderValue)) {
			$arrErrors['minimumOrderValueNotReached'] = sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text007'], ls_shop_generalHelper::outputPrice($objCoupon->minimumOrderValue));
		}

		/*
		 * Prüfen, ob für Kundengruppe des aktuellen Users gültig
		 */
		$groupInfo = ls_shop_generalHelper::getGroupSettings4User();
		if (!in_array($groupInfo['id'], deserialize($objCoupon->allowedForGroups))) {
			$arrErrors['minimumOrderValueNotReached'] = $GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text009'];
		}

		return $arrErrors;
	}

	/*
	 * Diese Funktion gibt das Array zurück, mit dem der jeweilige Coupon in der Cart-Session-Variable abgelegt wird.
	 */
	public static function getCouponRepresentationForCart($couponID = false) {
		/** @var \PageModel $objPage */
		global $objPage;

		$couponInfo = array(
			'title' => '',
			'description',
			'errors' => array(),
			'deleteUrl' => '',
			'extendedInfo' => array()
		);

		if (!$couponID) {
			return $couponInfo;
		}

		$objCoupon = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_coupon`
			WHERE		`id` = ?
		")
			->limit(1)
			->execute($couponID);

		if (!$objCoupon->numRows) {
			return $couponInfo;
		}

		$objCoupon->first();

		$arrCouponMultilanguageInfo = ls_shop_languageHelper::getMultiLanguage($couponID, 'tl_ls_shop_coupon_languages', array('title', 'description'), array($objPage->language));

		$couponInfo['title'] = $arrCouponMultilanguageInfo['title'];
		$couponInfo['description'] = $arrCouponMultilanguageInfo['description'];
		$couponInfo['hasErrors'] = false;
		$couponInfo['errors'] = ls_shop_cartHelper::validateCoupon($couponID);
		foreach ($couponInfo['errors'] as $tmpError) {
			if ($tmpError) {
				$couponInfo['hasErrors'] = true;
			}
		}
		$couponInfo['deleteUrl'] = is_object($objPage) ? \Controller::generateFrontendUrl($objPage->row(), '/deleteCoupon/'.$couponID) : '';
		$couponInfo['extendedInfo'] = $objCoupon->row();
		$couponInfo['extendedInfo']['discountOutput'] = '- '.($objCoupon->couponValueType == 'percentaged' ? $objCoupon->couponValue.' %' : ls_shop_generalHelper::outputPrice($objCoupon->couponValue));

		return $couponInfo;
	}

	/**
	 * Diese Funktion prüft aktuell im Warenkorb hinterlegte Gutscheine daraufhin, ob sie
	 * aktuell noch gültig sind (es könnte sich ja im Warenkorb etwas verändert haben) oder
	 * der Gutschein durch eine Wartezeit nicht mehr gültig oder verfügbar sein.
	 */
	public static function revalidateCouponsUsed() {
		if (!is_array($_SESSION['lsShopCart']['couponsUsed'])) {
			return false;
		}

		foreach ($_SESSION['lsShopCart']['couponsUsed'] as $couponID => $arrCouponInfo) {
			$_SESSION['lsShopCart']['couponsUsed'][$couponID] = ls_shop_cartHelper::getCouponRepresentationForCart($couponID);
		}
	}

	public static function check_couponIsValid($int_couponID = false, $str_getCouponBy = 'id') {
		$arr_couponErrors = ls_shop_cartHelper::validateCoupon($int_couponID, $str_getCouponBy);
		if (!is_array($arr_couponErrors)) {
			return false;
		}

		foreach ($arr_couponErrors as $str_errorMsg) {
			if ($str_errorMsg !== false) {
				return false;
			}
		}

		return true;
	}

	/*
	 * Diese Funktion hinterlegt einen gültigen Gutschein im Warenkorb
	 */
	public static function useCoupon($couponCode = '') {
		if (!$couponCode) {
			return false;
		}

		$objCoupon = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_coupon`
			WHERE		`couponCode` = ?
		")
			->limit(1)
			->execute($couponCode);

		if (!$objCoupon->numRows) {
			return false;
		}
		$objCoupon->first();

		$_SESSION['lsShopCart']['couponsUsed'][$objCoupon->id] = ls_shop_cartHelper::getCouponRepresentationForCart($objCoupon->id);
	}

	public static function deleteUsedCoupon($couponID) {
		if (!$couponID) {
			return false;
		}

		if (isset($_SESSION['lsShopCart']['couponsUsed'][$couponID])) {
			unset($_SESSION['lsShopCart']['couponsUsed'][$couponID]);
		}

		return true;
	}
}