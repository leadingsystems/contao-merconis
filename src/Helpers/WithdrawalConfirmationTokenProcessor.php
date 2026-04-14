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

    public function createToken(string|int $withdrawalReference, int $issuedTimestamp, string $secret): string
    {
        $normalizedReference = trim((string) $withdrawalReference);
        $signature = hash_hmac('sha256', $normalizedReference . $issuedTimestamp, $secret);

        return $normalizedReference . '-' . $issuedTimestamp . '-' . $signature;
    }

    /**
     * @return array{status: string, reference?: string, primaryKey?: int, timestamp?: int}
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

        if (!preg_match('/^(.+)-([0-9]+)-([a-f0-9]{64})$/', $trimmedTokenValue, $matches)) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        $withdrawalReference = trim((string) ($matches[1] ?? ''));
        $timestampPart = (string) ($matches[2] ?? '');
        $hmacPart = (string) ($matches[3] ?? '');

        if ($withdrawalReference === '' || !ctype_digit($timestampPart)) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        if (!preg_match('/^[a-f0-9]{64}$/', $hmacPart)) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        $issuedTimestamp = (int) $timestampPart;
        if ($issuedTimestamp <= 0) {
            return ['status' => self::STATUS_INVALID_FORMAT];
        }

        $expectedSignature = hash_hmac('sha256', $withdrawalReference . $issuedTimestamp, $secret);
        if (!hash_equals($expectedSignature, $hmacPart)) {
            return ['status' => self::STATUS_INVALID_SIGNATURE];
        }

        $tokenAgeInSeconds = $currentTimestamp - $issuedTimestamp;
        if ($tokenAgeInSeconds < 0 || $tokenAgeInSeconds > $maxAgeSeconds) {
            return ['status' => self::STATUS_EXPIRED];
        }

        $result = [
            'status' => self::STATUS_SUCCESS,
            'reference' => $withdrawalReference,
            'timestamp' => $issuedTimestamp,
        ];

        if (ctype_digit($withdrawalReference) && (int) $withdrawalReference > 0) {
            $result['primaryKey'] = (int) $withdrawalReference;
        }

        return $result;
    }
}
