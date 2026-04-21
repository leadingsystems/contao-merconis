<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

final class WithdrawalScreenAProcessor
{
    public const STATUS_IDLE = 'idle';
    public const STATUS_BOT_DISCARDED = 'bot_discarded';
    public const STATUS_RATE_LIMITED = 'rate_limited';
    public const STATUS_EMPTY = 'empty';
    public const STATUS_NOT_FOUND = 'not_found';
    public const STATUS_SUCCESS = 'success';

    private const FALLBACK_HIGHLIGHT_THRESHOLD = 3;

    /**
     * @param callable(string): ?string $lookupCallback
     * @return array{
     *     status: string,
     *     failedAttempts: int,
     *     highlightFallback: bool,
     *     normalizedIdentifier: string,
     *     canonicalIdentifier: ?string
     * }
     */
    public function process(
        string $identifierInput,
        string $honeypotValue,
        int $failedAttempts,
        int $rateLimitHitCount,
        int $rateLimitThreshold,
        callable $lookupCallback
    ): array {
        $sanitizedFailedAttempts = max(0, $failedAttempts);

        if ($this->isHoneypotTriggered($honeypotValue)) {
            return $this->result(
                self::STATUS_BOT_DISCARDED,
                $sanitizedFailedAttempts,
                '',
                null
            );
        }

        if ($this->isRateLimitExceeded($rateLimitHitCount, $rateLimitThreshold)) {
            return $this->result(
                self::STATUS_RATE_LIMITED,
                $sanitizedFailedAttempts,
                '',
                null
            );
        }

        if ($this->isIdentifierEmpty($identifierInput)) {
            return $this->result(
                self::STATUS_EMPTY,
                $sanitizedFailedAttempts,
                '',
                null
            );
        }

        $normalizedIdentifier = $this->normalizeIdentifier($identifierInput);
        $canonicalIdentifier = $lookupCallback($normalizedIdentifier);

        if (is_string($canonicalIdentifier) && $canonicalIdentifier !== '') {
            return $this->result(
                self::STATUS_SUCCESS,
                0,
                $normalizedIdentifier,
                $canonicalIdentifier
            );
        }

        return $this->result(
            self::STATUS_NOT_FOUND,
            $sanitizedFailedAttempts + 1,
            $normalizedIdentifier,
            null
        );
    }

    public function normalizeIdentifier(string $identifier): string
    {
        return strtoupper(str_replace('-', '', trim($identifier)));
    }

    public function isHoneypotTriggered(string $honeypotValue): bool
    {
        return trim($honeypotValue) !== '';
    }

    public function isRateLimitExceeded(int $rateLimitHitCount, int $rateLimitThreshold): bool
    {
        if ($rateLimitThreshold <= 0) {
            return false;
        }

        return $rateLimitHitCount >= $rateLimitThreshold;
    }

    private function isIdentifierEmpty(string $identifier): bool
    {
        return trim($identifier) === '';
    }

    /**
     * @return array{
     *     status: string,
     *     failedAttempts: int,
     *     highlightFallback: bool,
     *     normalizedIdentifier: string,
     *     canonicalIdentifier: ?string
     * }
     */
    private function result(
        string $status,
        int $failedAttempts,
        string $normalizedIdentifier,
        ?string $canonicalIdentifier
    ): array {
        return [
            'status' => $status,
            'failedAttempts' => $failedAttempts,
            'highlightFallback' => $failedAttempts >= self::FALLBACK_HIGHLIGHT_THRESHOLD,
            'normalizedIdentifier' => $normalizedIdentifier,
            'canonicalIdentifier' => $canonicalIdentifier,
        ];
    }
}
