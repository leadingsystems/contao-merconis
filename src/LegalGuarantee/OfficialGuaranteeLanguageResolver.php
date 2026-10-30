<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class OfficialGuaranteeLanguageResolver
{
    /**
     * @param list<string> $availableLanguages
     */
    public function resolveOfficialLanguage(string $contaoLocale, array $availableLanguages): string
    {
        $normalizedAvailableLanguages = [];

        foreach ($availableLanguages as $availableLanguage) {
            $normalizedAvailableLanguage = $this->normalizeLanguage($availableLanguage);

            if ('' === $normalizedAvailableLanguage) {
                continue;
            }

            $normalizedAvailableLanguages[$normalizedAvailableLanguage] = $normalizedAvailableLanguage;
        }

        $normalizedAvailableLanguages = array_values($normalizedAvailableLanguages);
        $preferredLanguage = $this->normalizeLanguage($contaoLocale);

        if (in_array($preferredLanguage, $normalizedAvailableLanguages, true)) {
            return $preferredLanguage;
        }

        if (in_array('en', $normalizedAvailableLanguages, true)) {
            return 'en';
        }

        return $normalizedAvailableLanguages[0] ?? 'en';
    }

    public function normalizeLanguage(string $language): string
    {
        $normalizedLanguage = strtolower(trim($language));
        $normalizedLanguage = str_replace('-', '_', $normalizedLanguage);

        if (preg_match('/^[a-z]{2}/', $normalizedLanguage, $matches)) {
            return $matches[0];
        }

        return '';
    }
}
