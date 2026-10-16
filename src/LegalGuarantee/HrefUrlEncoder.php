<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class HrefUrlEncoder
{
    public static function encode(string $url): string
    {
        return preg_replace_callback(
            '/[^\x00-\x7F]+/u',
            static fn (array $matches): string => rawurlencode($matches[0]),
            $url
        ) ?? $url;
    }
}
