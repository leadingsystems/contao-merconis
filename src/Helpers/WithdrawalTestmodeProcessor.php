<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

final class WithdrawalTestmodeProcessor
{
    public const PLACEHOLDER_WITHDRAWAL_ID = 'W-00000';

    public function hasTestmodeParameter(string $testmodeValue): bool
    {
        return trim($testmodeValue) !== '';
    }

    /**
     * @param callable(string): ?array<string, mixed> $orderLoader
     * @return ?array<string, mixed>
     */
    public function resolveOrder(string $testmodeValue, callable $orderLoader): ?array
    {
        $normalizedTestmodeValue = trim($testmodeValue);
        if ($normalizedTestmodeValue === '') {
            return null;
        }

        $arrOrder = $orderLoader($normalizedTestmodeValue);

        return is_array($arrOrder) && count($arrOrder) ? $arrOrder : null;
    }

    public function isPlaceholderWithdrawalId(string $withdrawalReference): bool
    {
        return trim($withdrawalReference) === self::PLACEHOLDER_WITHDRAWAL_ID;
    }
}
