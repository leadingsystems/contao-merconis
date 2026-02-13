<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\License;

use Contao\Config;
use Contao\Environment;
use Contao\System;

/**
 * License-Key-Parsing + Signaturprüfung (Public Key embedded),
 * Dual-Format Validierung (neues Key-Format + Legacy-Seriennummern-Validierung) sowie
 * Feature-Checks per `featureAllowed()`.
 *
 */
final class LicenseKeyValidator
{
    private const SIGNING_PUBLIC_KEY_RELATIVE_PATH = '/../Resources/keys/merconis_signing_public.pem';

    /**
     * @return array{allowed: bool, reasonCode: string, payload?: array<string, mixed>}
     */
    public static function featureAllowed(string $featureIdentifier): array
    {
        $featureIdentifier = trim($featureIdentifier);
        if ($featureIdentifier === '') {
            return ['allowed' => false, 'reasonCode' => 'INVALID_FEATURE'];
        }

        $licenseValue = self::getConfiguredLicenseValue();
        $technicalValidity = self::verifyTechnicalValidity($licenseValue);

        if (!$technicalValidity->isValid || $technicalValidity->payload === null) {
            return ['allowed' => false, 'reasonCode' => 'INVALID_KEY'];
        }

        $payload = $technicalValidity->payload;
        $features = self::getPayloadStringList($payload['feat'] ?? null);

        if (!in_array($featureIdentifier, $features, true)) {
            return ['allowed' => false, 'reasonCode' => 'FEATURE_NOT_ALLOWED', 'payload' => $payload];
        }

        // Premium-Checks: sobald überhaupt Features aktiviert sind, müssen Domains passen.
        if ($features !== []) {
            $domains = self::getPayloadStringList($payload['dom'] ?? null);
            if ($domains === []) {
                return ['allowed' => false, 'reasonCode' => 'DOMAIN_MISSING', 'payload' => $payload];
            }

            $currentHost = self::getCurrentHost();
            $normalizedHost = self::normalizeHost($currentHost);

            if ($normalizedHost === '') {
                return ['allowed' => false, 'reasonCode' => 'DOMAIN_INVALID', 'payload' => $payload];
            }

            $normalizedDomains = [];
            foreach ($domains as $domain) {
                $normalizedDomains[] = self::normalizeDomainEntry($domain);
            }

            foreach ($normalizedDomains as $domain) {
                if (self::domainMatches($normalizedHost, $domain)) {
                    return ['allowed' => true, 'reasonCode' => 'ALLOWED', 'payload' => $payload];
                }
            }

            return ['allowed' => false, 'reasonCode' => 'DOMAIN_NOT_ALLOWED', 'payload' => $payload];
        }

        return ['allowed' => true, 'reasonCode' => 'ALLOWED', 'payload' => $payload];
    }

    public static function isConfiguredLicenseTechnicallyValid(): bool
    {
        return self::verifyTechnicalValidity(self::getConfiguredLicenseValue())->isValid;
    }

    public static function verifyTechnicalValidity(string $value): LicenseKeyVerificationResult
    {
        $value = trim($value);

        if ($value === '') {
            return new LicenseKeyVerificationResult(false, 'MISSING_KEY');
        }

        // Format: <payload_b64u>.<signature_b64u>
        if (str_contains($value, '.')) {
            return self::verifySignedLicenseKey($value);
        }

        // Legacy: Seriennummer
        return self::verifyLegacySerial($value)
            ? new LicenseKeyVerificationResult(true, 'LEGACY_VALID')
            : new LicenseKeyVerificationResult(false, 'LEGACY_INVALID');
    }

    public static function verifySignedLicenseKey(string $licenseKey): LicenseKeyVerificationResult
    {
        // Erlaube Copy/Paste aus Mails/Backends, bei denen Whitespaces/Linebreaks enthalten sein können.
        $licenseKey = preg_replace('~\s+~', '', $licenseKey) ?? $licenseKey;

        $parts = explode('.', $licenseKey);
        if (count($parts) !== 2) {
            return new LicenseKeyVerificationResult(false, 'INVALID_FORMAT');
        }

        [$payloadB64u, $signatureB64u] = $parts;

        $payloadB64u = trim($payloadB64u);
        $signatureB64u = trim($signatureB64u);

        if ($payloadB64u === '' || $signatureB64u === '') {
            return new LicenseKeyVerificationResult(false, 'INVALID_FORMAT');
        }

        $signatureRaw = self::base64UrlDecode($signatureB64u);
        if ($signatureRaw === null) {
            return new LicenseKeyVerificationResult(false, 'INVALID_SIGNATURE_ENCODING');
        }

        $publicKeyPem = self::getSigningPublicKeyPem();
        if ($publicKeyPem === '') {
            return new LicenseKeyVerificationResult(false, 'PUBLIC_KEY_MISSING');
        }

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return new LicenseKeyVerificationResult(false, 'INVALID_PUBLIC_KEY');
        }

        $verifyOk = openssl_verify($payloadB64u, $signatureRaw, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verifyOk !== 1) {
            if ($verifyOk === -1) {
                return new LicenseKeyVerificationResult(false, 'VERIFY_ERROR');
            }
            return new LicenseKeyVerificationResult(false, 'INVALID_SIGNATURE');
        }

        $payloadJson = self::base64UrlDecode($payloadB64u);
        if ($payloadJson === null) {
            return new LicenseKeyVerificationResult(false, 'INVALID_PAYLOAD_ENCODING');
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return new LicenseKeyVerificationResult(false, 'INVALID_PAYLOAD_JSON');
        }

        if (!isset($payload['v']) || (int) $payload['v'] !== 1) {
            return new LicenseKeyVerificationResult(false, 'UNSUPPORTED_VERSION');
        }

        if (!isset($payload['license_id']) || !is_string($payload['license_id']) || trim($payload['license_id']) === '') {
            return new LicenseKeyVerificationResult(false, 'MISSING_LICENSE_ID');
        }

        if (!isset($payload['iat']) || !is_string($payload['iat']) || trim($payload['iat']) === '') {
            return new LicenseKeyVerificationResult(false, 'MISSING_IAT');
        }

        // Ensure lists exist (for downstream code)
        $payload['feat'] = self::getPayloadStringList($payload['feat'] ?? null);
        $payload['dom'] = self::getPayloadStringList($payload['dom'] ?? null);

        /** @var array<string, mixed> $payload */
        return new LicenseKeyVerificationResult(true, 'VALID', $payload);
    }

    public static function verifyLegacySerial(string $serialNumber): bool
    {
        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            return false;
        }

        $parts = explode('-', $serialNumber);
        if (count($parts) < 2) {
            return false;
        }

        $hashSuffix = array_pop($parts);
        if (!is_string($hashSuffix) || $hashSuffix === '') {
            return false;
        }

        $serialCore = implode('', $parts);
        if ($serialCore === '') {
            return false;
        }

        return substr(md5($serialCore), 0, 5) === $hashSuffix;
    }

    public static function migrateLegacySerialToLicenseKeyIfNeeded(): void
    {
        $current = trim((string) Config::get('merconis_licenseKey'));
        if ($current !== '') {
            return;
        }

        $legacy = trim((string) Config::get('ls_shop_serial'));
        if ($legacy === '') {
            return;
        }

        Config::set('merconis_licenseKey', $legacy);
        Config::persist('merconis_licenseKey', $legacy);
    }

    private static function getConfiguredLicenseValue(): string
    {
        $licenseValue = (string) Config::get('merconis_licenseKey');

        // Fallback für Update-Situationen, bevor die Migration gelaufen ist
        if (trim($licenseValue) === '') {
            $licenseValue = (string) Config::get('ls_shop_serial');
        }

        return $licenseValue;
    }

    private static function getCurrentHost(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if ($request !== null) {
            return (string) $request->getHost();
        }

        return (string) Environment::get('host');
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function getPayloadStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $item = trim($item);
                if ($item !== '') {
                    $result[] = $item;
                }
            }
        }

        $result = array_values(array_unique($result));
        sort($result);

        return $result;
    }

    private static function normalizeHost(string $host): string
    {
        $host = trim($host);
        if ($host === '') {
            return '';
        }

        $urlToParse = str_contains($host, '://') ? $host : ('http://' . $host);
        $parsed = parse_url($urlToParse);

        $parsedHost = is_array($parsed) ? ($parsed['host'] ?? null) : null;
        if (!is_string($parsedHost) || $parsedHost === '') {
            $parsedHost = explode('/', $host, 2)[0] ?? '';
        }

        $parsedHost = preg_replace('~:\d+$~', '', $parsedHost) ?? $parsedHost;
        $parsedHost = strtolower($parsedHost);

        return self::normalizeIdnToAscii($parsedHost);
    }

    private static function normalizeDomainEntry(string $domain): string
    {
        $domain = trim($domain);

        $isWildcard = str_starts_with($domain, '*.');
        if ($isWildcard) {
            $domain = substr($domain, 2);
        }

        $host = self::normalizeHost($domain);
        if ($host === '') {
            return '';
        }

        return $isWildcard ? ('*.' . $host) : $host;
    }

    private static function domainMatches(string $normalizedHost, string $normalizedDomainEntry): bool
    {
        if ($normalizedHost === '' || $normalizedDomainEntry === '') {
            return false;
        }

        if (!str_starts_with($normalizedDomainEntry, '*.')) {
            return $normalizedHost === $normalizedDomainEntry;
        }

        $base = substr($normalizedDomainEntry, 2);
        if ($base === '') {
            return false;
        }

        return $normalizedHost === $base || str_ends_with($normalizedHost, '.' . $base);
    }

    private static function normalizeIdnToAscii(string $host): string
    {
        if (!function_exists('idn_to_ascii')) {
            return $host;
        }

        /** @var int $variant */
        $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
        $ascii = idn_to_ascii($host, IDNA_DEFAULT, $variant);

        return is_string($ascii) && $ascii !== '' ? $ascii : $host;
    }

    private static function base64UrlDecode(string $b64u): ?string
    {
        $b64u = trim($b64u);
        if ($b64u === '') {
            return null;
        }

        $b64 = strtr($b64u, '-_', '+/');
        $padding = strlen($b64) % 4;
        if ($padding !== 0) {
            $b64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($b64, true);

        return is_string($decoded) ? $decoded : null;
    }

    private static function getSigningPublicKeyPem(): string
    {
        static $cachedPem = null;

        if (is_string($cachedPem)) {
            return $cachedPem;
        }

        $path = __DIR__ . self::SIGNING_PUBLIC_KEY_RELATIVE_PATH;
        $pem = @file_get_contents($path);
        $cachedPem = is_string($pem) ? trim($pem) : '';

        return $cachedPem;
    }
}

