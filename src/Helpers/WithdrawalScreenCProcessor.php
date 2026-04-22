<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Helpers;

final class WithdrawalScreenCProcessor
{
    public const STATUS_IDLE = 'idle';
    public const STATUS_BOT_DISCARDED = 'bot_discarded';
    public const STATUS_RATE_LIMITED = 'rate_limited';
    public const STATUS_SUCCESS = 'success';

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

    public function hasExceededAnyRateLimit(
        int $ipHitCount,
        int $ipThreshold,
        int $recipientHitCount,
        int $recipientThreshold
    ): bool {
        return $this->isRateLimitExceeded($ipHitCount, $ipThreshold)
            || $this->isRateLimitExceeded($recipientHitCount, $recipientThreshold);
    }
}
