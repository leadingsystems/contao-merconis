<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_mul;

final class MinimumOrderQuantityCalculator
{
    public static function normalizeQuantityValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $normalizedValue = trim((string) $value);
        if ($normalizedValue === '') {
            return '0';
        }

        $normalizedValue = str_replace(',', '.', $normalizedValue);
        $isNegative = str_starts_with($normalizedValue, '-');
        if ($isNegative) {
            $normalizedValue = substr($normalizedValue, 1);
        }

        $valueParts = explode('.', $normalizedValue, 2);
        $wholePart = ltrim($valueParts[0], '0');
        $wholePart = $wholePart !== '' ? $wholePart : '0';
        $fractionalPart = rtrim($valueParts[1] ?? '', '0');

        $normalizedValue = $fractionalPart !== ''
            ? $wholePart . '.' . $fractionalPart
            : $wholePart;

        if ($normalizedValue === '0') {
            return '0';
        }

        return $isNegative ? '-' . $normalizedValue : $normalizedValue;
    }

    public static function hasActiveMinimumOrderQuantity(mixed $value): bool
    {
        return self::normalizeQuantityValue($value) !== '0';
    }

    public static function getMinimumOrderQuantityStockHandlingMode(): string
    {
        $stockHandlingMode = $GLOBALS['TL_CONFIG']['ls_shop_minimumOrderQuantityStockHandling'] ?? 'denyOrder';

        return in_array($stockHandlingMode, ['denyOrder', 'allowAvailableQuantity'], true)
            ? $stockHandlingMode
            : 'denyOrder';
    }

    public static function hasStockConflictWithMinimumOrderQuantity(
        mixed $minimumOrderQuantity,
        mixed $availableQuantity,
        int $quantityDecimals
    ): bool {
        $normalizedMinimumOrderQuantity = self::normalizeQuantityValue($minimumOrderQuantity);
        if (!self::hasActiveMinimumOrderQuantity($normalizedMinimumOrderQuantity)) {
            return false;
        }

        $normalizedAvailableQuantity = self::normalizeQuantityValue($availableQuantity);

        return self::compareQuantities($normalizedAvailableQuantity, '0', $quantityDecimals) > 0
            && self::compareQuantities(
                $normalizedAvailableQuantity,
                $normalizedMinimumOrderQuantity,
                $quantityDecimals
            ) < 0;
    }

    public static function shouldDenyOrderDueToStockConflict(
        mixed $minimumOrderQuantity,
        mixed $availableQuantity,
        int $quantityDecimals,
        ?string $stockHandlingMode = null
    ): bool {
        $resolvedStockHandlingMode = $stockHandlingMode ?? self::getMinimumOrderQuantityStockHandlingMode();

        if ($resolvedStockHandlingMode !== 'denyOrder') {
            return false;
        }

        return self::hasStockConflictWithMinimumOrderQuantity(
            $minimumOrderQuantity,
            $availableQuantity,
            $quantityDecimals
        );
    }

    public static function resolveMinimumOrderQuantityForStockHandling(
        mixed $minimumOrderQuantity,
        mixed $availableQuantity,
        int $quantityDecimals,
        ?string $stockHandlingMode = null
    ): string {
        $normalizedMinimumOrderQuantity = self::normalizeQuantityValue($minimumOrderQuantity);
        $resolvedStockHandlingMode = $stockHandlingMode ?? self::getMinimumOrderQuantityStockHandlingMode();

        if (
            $resolvedStockHandlingMode !== 'allowAvailableQuantity'
            || !self::hasStockConflictWithMinimumOrderQuantity(
                $normalizedMinimumOrderQuantity,
                $availableQuantity,
                $quantityDecimals
            )
        ) {
            return $normalizedMinimumOrderQuantity;
        }

        return self::normalizeQuantityValue($availableQuantity);
    }

    public static function getEffectiveDisplayMinimumQuantity(
        mixed $minimumOrderQuantity,
        int $salesUnitSize,
        int $quantityDecimals
    ): string {
        $normalizedMinimumOrderQuantity = self::normalizeQuantityValue($minimumOrderQuantity);
        if (!self::hasActiveMinimumOrderQuantity($normalizedMinimumOrderQuantity)) {
            return '0';
        }

        $displayQuantity = $salesUnitSize > 0
            ? self::normalizeQuantityValue(ls_mul($normalizedMinimumOrderQuantity, $salesUnitSize))
            : $normalizedMinimumOrderQuantity;
        $displayStep = self::getDisplayStepValue($salesUnitSize, $quantityDecimals);
        $scalePrecision = self::getScalePrecision($quantityDecimals);

        $scaledDisplayQuantity = self::scaleQuantityToIntegerDomain($displayQuantity, $scalePrecision);
        $scaledDisplayStep = self::scaleQuantityToIntegerDomain($displayStep, $scalePrecision);

        if (
            $scaledDisplayQuantity === null
            || $scaledDisplayStep === null
            || $scaledDisplayStep <= 0
        ) {
            return $displayQuantity;
        }

        if ($scaledDisplayQuantity % $scaledDisplayStep === 0) {
            return $displayQuantity;
        }

        $roundedScaledDisplayQuantity = (int) (
            intdiv($scaledDisplayQuantity + $scaledDisplayStep - 1, $scaledDisplayStep)
            * $scaledDisplayStep
        );

        return self::formatScaledQuantity($roundedScaledDisplayQuantity, $scalePrecision);
    }

    public static function getDisplayStepValue(int $salesUnitSize, int $quantityDecimals): string
    {
        $scaleFactor = max(1, 10 ** max(0, $quantityDecimals));

        if ($salesUnitSize > 0) {
            return self::normalizeQuantityValue(ls_div($salesUnitSize, $scaleFactor));
        }

        return self::normalizeQuantityValue(ls_div(1, $scaleFactor));
    }

    public static function getContextualDisplayMinimumValue(
        string $displayStep,
        string $effectiveMinimumQuantity,
        bool $hasActiveMinimumOrderQuantity,
        bool $cartItemAlreadyExists,
        bool $isCartContext,
        ?string $currentDisplayQuantity,
        int $quantityDecimals
    ): string {
        $normalizedDisplayStep = self::normalizeQuantityValue($displayStep);
        $normalizedEffectiveMinimumQuantity = self::normalizeQuantityValue($effectiveMinimumQuantity);
        $normalizedCurrentDisplayQuantity = $currentDisplayQuantity !== null
            ? self::normalizeQuantityValue($currentDisplayQuantity)
            : null;

        if (!$hasActiveMinimumOrderQuantity) {
            return $normalizedDisplayStep;
        }

        if ($isCartContext) {
            if (
                $normalizedCurrentDisplayQuantity !== null
                && self::compareQuantities(
                    $normalizedCurrentDisplayQuantity,
                    $normalizedEffectiveMinimumQuantity,
                    $quantityDecimals
                ) < 0
            ) {
                return self::roundUpToStep(
                    $normalizedCurrentDisplayQuantity,
                    $normalizedDisplayStep,
                    $quantityDecimals
                );
            }

            return $normalizedEffectiveMinimumQuantity;
        }

        if ($cartItemAlreadyExists) {
            return $normalizedDisplayStep;
        }

        return $normalizedEffectiveMinimumQuantity;
    }

    public static function getContextualDisplayInitialValue(
        string $displayStep,
        string $effectiveMinimumQuantity,
        bool $hasActiveMinimumOrderQuantity,
        bool $cartItemAlreadyExists,
        bool $isCartContext,
        ?string $currentDisplayQuantity,
        int $quantityDecimals,
        ?string $inactiveProductPageDefaultValue = null
    ): string {
        $normalizedCurrentDisplayQuantity = $currentDisplayQuantity !== null
            ? self::normalizeQuantityValue($currentDisplayQuantity)
            : null;

        if ($isCartContext && $normalizedCurrentDisplayQuantity !== null) {
            return $normalizedCurrentDisplayQuantity;
        }

        if (
            !$hasActiveMinimumOrderQuantity
            && !$isCartContext
            && $inactiveProductPageDefaultValue !== null
        ) {
            return $inactiveProductPageDefaultValue;
        }

        return self::getContextualDisplayMinimumValue(
            $displayStep,
            $effectiveMinimumQuantity,
            $hasActiveMinimumOrderQuantity,
            $cartItemAlreadyExists,
            $isCartContext,
            $currentDisplayQuantity,
            $quantityDecimals
        );
    }

    private static function getScalePrecision(int $quantityDecimals): int
    {
        return max(4, max(0, $quantityDecimals));
    }

    public static function compareQuantities(
        string $leftQuantity,
        string $rightQuantity,
        int $quantityDecimals
    ): int {
        $scalePrecision = self::getScalePrecision($quantityDecimals);
        $scaledLeftQuantity = self::scaleQuantityToIntegerDomain($leftQuantity, $scalePrecision);
        $scaledRightQuantity = self::scaleQuantityToIntegerDomain($rightQuantity, $scalePrecision);

        if ($scaledLeftQuantity === null || $scaledRightQuantity === null) {
            return (float) $leftQuantity <=> (float) $rightQuantity;
        }

        return $scaledLeftQuantity <=> $scaledRightQuantity;
    }

    private static function roundUpToStep(
        string $quantity,
        string $step,
        int $quantityDecimals
    ): string {
        $scalePrecision = self::getScalePrecision($quantityDecimals);
        $scaledQuantity = self::scaleQuantityToIntegerDomain($quantity, $scalePrecision);
        $scaledStep = self::scaleQuantityToIntegerDomain($step, $scalePrecision);

        if ($scaledQuantity === null || $scaledStep === null || $scaledStep <= 0) {
            return $quantity;
        }

        if ($scaledQuantity % $scaledStep === 0) {
            return $quantity;
        }

        $roundedScaledQuantity = (int) (
            intdiv($scaledQuantity + $scaledStep - 1, $scaledStep)
            * $scaledStep
        );

        return self::formatScaledQuantity($roundedScaledQuantity, $scalePrecision);
    }

    private static function scaleQuantityToIntegerDomain(
        string $normalizedQuantity,
        int $maxDecimals
    ): ?int {
        $sign = 1;
        if (str_starts_with($normalizedQuantity, '-')) {
            $sign = -1;
            $normalizedQuantity = substr($normalizedQuantity, 1);
        }

        $quantityParts = explode('.', $normalizedQuantity, 2);
        $wholePart = $quantityParts[0] !== '' ? $quantityParts[0] : '0';
        $fractionalPart = $quantityParts[1] ?? '';

        if (strlen($fractionalPart) > $maxDecimals) {
            return null;
        }

        if ($maxDecimals > 0) {
            $fractionalPart = str_pad($fractionalPart, $maxDecimals, '0');
        } elseif ($fractionalPart !== '') {
            return null;
        }

        return $sign * (int) ($wholePart . $fractionalPart);
    }

    private static function formatScaledQuantity(int $scaledQuantity, int $quantityDecimals): string
    {
        if ($quantityDecimals === 0) {
            return (string) $scaledQuantity;
        }

        $sign = $scaledQuantity < 0 ? '-' : '';
        $normalizedQuantity = str_pad(
            (string) abs($scaledQuantity),
            $quantityDecimals + 1,
            '0',
            STR_PAD_LEFT
        );
        $wholePart = substr($normalizedQuantity, 0, -$quantityDecimals);
        $fractionalPart = substr($normalizedQuantity, -$quantityDecimals);

        return self::normalizeQuantityValue(
            $sign . $wholePart . '.' . $fractionalPart
        );
    }
}
