<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

final class WithdrawalConfirmationTokenProcessor
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_MISSING = 'missing';
    public const STATUS_INVALID_FORMAT = 'invalid_format';
    public const STATUS_INVALID_SIGNATURE = 'invalid_signature';
    public const STATUS_EXPIRED = 'expired';

    public function createToken(int $withdrawalPrimaryKey, int $issuedTimestamp, string $secret): string
    {
        $signature = hash_hmac('sha256', $withdrawalPrimaryKey . $issuedTimestamp, $secret);

        return $withdrawalPrimaryKey . '-' . $issuedTimestamp . '-' . $signature;
    }

    /**
     * @return array{status: string, primaryKey?: int, timestamp?: int}
     */
    public function validateToken(
        string $tokenValue,
        string $secret,
        int $currentTimestamp,
        int $maxAgeSeconds = 3600
    ): array {
        $trimmedTokenValue = trim($tokenValue);
        if ($trimmedTokenValue === '') {
            return ['status' => self::STATUS_MISSING];
        }

        $tokenParts = explode('-', $trimmedTokenValue, 3);
        if (count($tokenParts) !== 3) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        [$primaryKeyPart, $timestampPart, $hmacPart] = $tokenParts;
        if (!ctype_digit($primaryKeyPart) || !ctype_digit($timestampPart)) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        if (!preg_match('/^[a-f0-9]{64}$/', $hmacPart)) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        $withdrawalPrimaryKey = (int) $primaryKeyPart;
        $issuedTimestamp = (int) $timestampPart;
        if ($withdrawalPrimaryKey <= 0 || $issuedTimestamp <= 0) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        $expectedSignature = hash_hmac('sha256', $withdrawalPrimaryKey . $issuedTimestamp, $secret);
        if (!hash_equals($expectedSignature, $hmacPart)) {
            return ['status' => self::STATUS_INVALID_SIGNATURE];
        }

        $tokenAgeInSeconds = $currentTimestamp - $issuedTimestamp;
        if ($tokenAgeInSeconds < 0 || $tokenAgeInSeconds > $maxAgeSeconds) {
            return ['status' => self::STATUS_EXPIRED];
        }

        return [
            'status' => self::STATUS_SUCCESS,
            'primaryKey' => $withdrawalPrimaryKey,
            'timestamp' => $issuedTimestamp,
        ];
    }
}
