<?php
namespace Merconis\Core;

use LeadingSystems\Helpers\FlexWidget;

class FlexWidgetValidator {
	public static function quantityInput(FlexWidget $obj_flexWidget) {
		$decimalsSeparator = ($GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] ?? null) ?: '.';
		$arrMoreData = $obj_flexWidget->getMoreData();
		$allowNonPositiveQuantity = (bool) ($arrMoreData['allowNonPositiveQuantity'] ?? false);
		$normalizedQuantityInput = (string) self::normalizeQuantityInput($obj_flexWidget->getValue());

		if (
			preg_match(
				'/[^-0-9\\'.$decimalsSeparator.(($GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] ?? null) ? '\\'.$GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] : '').']/siU',
				$obj_flexWidget->getValue()
			)
		) {
			throw new \Exception(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimalsFE'], $obj_flexWidget->getLabel()));
		}

		if ($normalizedQuantityInput <= 0) {
			if ($allowNonPositiveQuantity) {
				return;
			}

			throw new \Exception(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['numberWithDecimalsFE'], $obj_flexWidget->getLabel()));
		}

		$quantityDecimals = max(0, (int) ($arrMoreData['quantityDecimals'] ?? $arrMoreData['decimalsAmount'] ?? 0));
		$scaledStep = self::determineScaledStep($arrMoreData, $quantityDecimals);
		$scaledQuantity = self::scaleQuantityToIntegerDomain(
			$normalizedQuantityInput,
			$quantityDecimals
		);

		if ($scaledQuantity === null || $scaledQuantity % $scaledStep !== 0) {
			$nextValidScaledQuantity = self::determineNextValidScaledQuantity(
				$normalizedQuantityInput,
				$quantityDecimals,
				$scaledStep
			);

			$scaledMinimumQuantity = null;
			if (($arrMoreData['min'] ?? '') !== '') {
				$scaledMinimumQuantity = self::scaleQuantityToIntegerDomain(
					(string) $arrMoreData['min'],
					$quantityDecimals
				);
			}

			if ($scaledMinimumQuantity !== null && $nextValidScaledQuantity < $scaledMinimumQuantity) {
				$nextValidScaledQuantity = $scaledMinimumQuantity;
			}

			throw new \Exception(
				sprintf(
					$GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['quantityStepMultipleFE'],
					$obj_flexWidget->getLabel(),
					self::formatScaledQuantity($nextValidScaledQuantity, $quantityDecimals)
				)
			);
		}
	}

	public static function searchWordMinLength(FlexWidget $obj_flexWidget) {
		$valueAfterHandleMinLength = ls_shop_generalHelper::handleSearchWordMinLength($obj_flexWidget->getValue(), $obj_flexWidget->getMinLength());
		if ($valueAfterHandleMinLength != $obj_flexWidget->getValue()) {
			throw new \Exception(sprintf($GLOBALS['TL_LANG']['MOD']['ls_shop']['rgxpErrorMessages']['stringHavingPartsWithMinimumLength'], $obj_flexWidget->getLabel(), $obj_flexWidget->getMinLength()));
		}
	}

	/*
	 * Diese Funktion prüft einen eingegebenen Gutschein-Code auf Gültigkeit und gibt
	 * das übergebene Widget im Fehlerfall mit hinterlegten Fehlern zurück
	 */
	public static function couponWidget(FlexWidget $obj_flexWidget) {
		if (!$obj_flexWidget->getValue()) {
			throw new \Exception($GLOBALS['TL_LANG']['MOD']['ls_shop']['coupon']['text003']);
		}

		$arrCouponErrors = ls_shop_cartHelper::validateCoupon($obj_flexWidget->getValue(), 'couponCode');

		if ($arrCouponErrors['doesNotExist']) {
			throw new \Exception($arrCouponErrors['doesNotExist']);
		}

		if ($arrCouponErrors['onlyOneCouponAllowed']) {
			throw new \Exception($arrCouponErrors['onlyOneCouponAllowed']);
		}

		if ($arrCouponErrors['notYetValid']) {
			throw new \Exception($arrCouponErrors['notYetValid']);
		}

		if ($arrCouponErrors['noLongerValid']) {
			throw new \Exception($arrCouponErrors['noLongerValid']);
		}

		if ($arrCouponErrors['minimumOrderValueNotReached']) {
			throw new \Exception($arrCouponErrors['minimumOrderValueNotReached']);
		}

		if ($arrCouponErrors['numAvailableNotOk']) {
			throw new \Exception($arrCouponErrors['numAvailableNotOk']);
		}


	}

	protected static function determineScaledStep(array $arrMoreData, int $quantityDecimals): int {
		$salesUnitSize = (int) ($arrMoreData['salesUnitSize'] ?? 0);

		if ($salesUnitSize > 0) {
			return $salesUnitSize;
		}

		return 1;
	}

	/*
	 * Der Mengenwert stammt aus einem `<input type="number">` und ist daher
	 * bereits kanonisch (Punkt als Dezimaltrenner, keine Gruppierung). Es ist
	 * keine Locale-Normalisierung erforderlich oder zulässig.
	 */
	protected static function normalizeQuantityInput($quantity) {
		return (string) $quantity;
	}

	protected static function scaleQuantityToIntegerDomain(string $normalizedQuantity, int $quantityDecimals): ?int {
		$sign = 1;
		if (str_starts_with($normalizedQuantity, '-')) {
			$sign = -1;
			$normalizedQuantity = substr($normalizedQuantity, 1);
		}

		$quantityParts = explode('.', $normalizedQuantity, 2);
		$wholePart = $quantityParts[0] !== '' ? $quantityParts[0] : '0';
		$fractionalPart = $quantityParts[1] ?? '';

		if (strlen($fractionalPart) > $quantityDecimals) {
			return null;
		}

		if ($quantityDecimals > 0) {
			$fractionalPart = str_pad($fractionalPart, $quantityDecimals, '0');
		} else if ($fractionalPart !== '') {
			return null;
		}

		return $sign * (int) ($wholePart . $fractionalPart);
	}

	protected static function formatScaledQuantity(int $scaledQuantity, int $quantityDecimals): string {
		if ($quantityDecimals === 0) {
			return (string) $scaledQuantity;
		}

		$sign = $scaledQuantity < 0 ? '-' : '';
		$normalizedQuantity = str_pad((string) abs($scaledQuantity), $quantityDecimals + 1, '0', STR_PAD_LEFT);
		$wholePart = substr($normalizedQuantity, 0, -$quantityDecimals);
		$fractionalPart = substr($normalizedQuantity, -$quantityDecimals);

		return $sign . rtrim(rtrim($wholePart . '.' . $fractionalPart, '0'), '.');
	}

	protected static function determineNextValidScaledQuantity(
		string $normalizedQuantity,
		int $quantityDecimals,
		int $scaledStep
	): int {
		$quantityParts = explode('.', ltrim($normalizedQuantity, '-'), 2);
		$wholePart = $quantityParts[0] !== '' ? $quantityParts[0] : '0';
		$fractionalPart = $quantityParts[1] ?? '';
		$rawDigits = (int) ($wholePart . $fractionalPart);
		$decimalDifference = $quantityDecimals - strlen($fractionalPart);

		if ($decimalDifference >= 0) {
			$scaledQuantity = $rawDigits * (10 ** $decimalDifference);
			return (int) (intdiv($scaledQuantity + $scaledStep - 1, $scaledStep) * $scaledStep);
		}

		$divisor = (10 ** abs($decimalDifference)) * $scaledStep;

		return (int) (intdiv($rawDigits + $divisor - 1, $divisor) * $scaledStep);
	}
}