<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

use JsonException;
use RuntimeException;

final class OfficialGuaranteeAssetLocator
{
    public const GLL_VERSION = 'v1.0';
    public const GARAN_VERSION = 'v1.0';

    private const GLL_BASE_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/gll/';
    private const GARAN_BASE_RELATIVE_PATH = 'src/Resources/legal-guarantee/garan/';
    private const FONT_BASE_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/fonts/inter';
    private const FONT_STYLESHEET_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/legal-guarantee-fonts.css';
    private const GLL_SVG_FILENAME_PATTERN = 'legal-guarantee-notice-%s.svg';
    private const GLL_PDF_FILENAME_PATTERN = 'legal-guarantee-notice-%s.pdf';
    private const EU_TEXT_LINKS_FILENAME = 'eu-text-links.json';

    public function __construct(
        private readonly string $projectDir,
        private readonly OfficialGuaranteeLanguageResolver $languageResolver,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getAvailableGllSvgLanguages(?string $version = null): array
    {
        return $this->scanAvailableLanguages($this->getGllVersionDirectory($version), 'svg');
    }

    /**
     * @return list<string>
     */
    public function getAvailableGllPdfLanguages(?string $version = null): array
    {
        return $this->scanAvailableLanguages($this->getGllVersionDirectory($version), 'pdf');
    }

    public function resolveGllSvgLanguage(string $contaoLocale, ?string $version = null): string
    {
        return $this->languageResolver->resolveOfficialLanguage(
            $contaoLocale,
            $this->getAvailableGllSvgLanguages($version)
        );
    }

    public function resolveGllPdfLanguage(string $contaoLocale, ?string $version = null): ?string
    {
        $availableLanguages = $this->getAvailableGllPdfLanguages($version);

        if ([] === $availableLanguages) {
            return null;
        }

        return $this->languageResolver->resolveOfficialLanguage($contaoLocale, $availableLanguages);
    }

    public function resolveGllSvgPath(string $contaoLocale, ?string $version = null): string
    {
        return $this->getGllSvgPathByLanguage(
            $this->resolveGllSvgLanguage($contaoLocale, $version),
            $version
        );
    }

    public function resolveGllPdfPath(string $contaoLocale, ?string $version = null): ?string
    {
        $resolvedLanguage = $this->resolveGllPdfLanguage($contaoLocale, $version);

        if (null === $resolvedLanguage) {
            return null;
        }

        return $this->getGllPdfPathByLanguage($resolvedLanguage, $version);
    }

    public function getGllVersionDirectory(?string $version = null): string
    {
        return $this->projectDir . '/' . self::GLL_BASE_RELATIVE_PATH . ($version ?? self::GLL_VERSION);
    }

    public function getGllSvgPathByLanguage(string $officialLanguage, ?string $version = null): string
    {
        return $this->getGllVersionDirectory($version) . '/' . sprintf(
            self::GLL_SVG_FILENAME_PATTERN,
            $officialLanguage
        );
    }

    public function getGllPdfPathByLanguage(string $officialLanguage, ?string $version = null): ?string
    {
        $path = $this->getGllVersionDirectory($version) . '/' . sprintf(
            self::GLL_PDF_FILENAME_PATTERN,
            $officialLanguage
        );

        return is_file($path) ? $path : null;
    }

    public function getGllEuTextLinksPath(?string $version = null): string
    {
        return $this->getGllVersionDirectory($version) . '/' . self::EU_TEXT_LINKS_FILENAME;
    }

    public function resolveGllEuUrl(string $contaoLocale, ?string $version = null): string
    {
        return $this->getGllEuUrlByLanguage(
            $this->resolveGllSvgLanguage($contaoLocale),
            $version
        );
    }

    public function getGllEuUrlByLanguage(string $officialLanguage, ?string $version = null): string
    {
        return $this->readRequiredStringValue(
            $this->readRequiredJsonFile($this->getGllEuTextLinksPath($version)),
            $officialLanguage,
            $this->getGllEuTextLinksPath($version)
        );
    }

    public function getGaranVersionDirectory(?string $version = null): string
    {
        return $this->projectDir . '/' . self::GARAN_BASE_RELATIVE_PATH . ($version ?? self::GARAN_VERSION);
    }

    public function getGaranColourTemplatePath(?string $version = null): string
    {
        return $this->getGaranVersionDirectory($version) . '/garan-label-colour.svg';
    }

    public function getGaranPdfTemplatePath(?string $version = null): string
    {
        return $this->getGaranColourTemplatePath($version);
    }

    public function getGaranNestedTemplatePath(?string $version = null): string
    {
        return $this->getGaranVersionDirectory($version) . '/garan-label-nested.svg';
    }

    public function getGaranEuTextLinksPath(?string $version = null): string
    {
        return $this->getGaranVersionDirectory($version) . '/' . self::EU_TEXT_LINKS_FILENAME;
    }

    public function resolveGaranEuUrl(?string $version = null): string
    {
        return $this->readRequiredStringValue(
            $this->readRequiredJsonFile($this->getGaranEuTextLinksPath($version)),
            'url',
            $this->getGaranEuTextLinksPath($version)
        );
    }

    public function getInterFontDirectory(): string
    {
        return $this->projectDir . '/' . self::FONT_BASE_RELATIVE_PATH;
    }

    public function getFontStylesheetPath(): string
    {
        return $this->projectDir . '/' . self::FONT_STYLESHEET_RELATIVE_PATH;
    }

    /**
     * @return list<string>
     */
    private function scanAvailableLanguages(string $directory, string $extension): array
    {
        $pattern = $directory . '/' . sprintf(self::GLL_SVG_FILENAME_PATTERN, '*');

        if ('pdf' === $extension) {
            $pattern = $directory . '/' . sprintf(self::GLL_PDF_FILENAME_PATTERN, '*');
        }

        $languages = [];

        foreach (glob($pattern) ?: [] as $filePath) {
            $fileName = basename($filePath);

            if (!preg_match('/^legal-guarantee-notice-([a-z]{2})\.' . preg_quote($extension, '/') . '$/', $fileName, $matches)) {
                continue;
            }

            $languages[] = $matches[1];
        }

        sort($languages);

        return $languages;
    }

    /**
     * @return array<string, mixed>
     */
    private function readRequiredJsonFile(string $path): array
    {
        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new RuntimeException('Required asset metadata file could not be read: ' . $path);
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Required asset metadata file is invalid JSON: ' . $path, 0, $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException('Required asset metadata file must decode to an object: ' . $path);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function readRequiredStringValue(array $data, string $key, string $path): string
    {
        $value = $data[$key] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new RuntimeException(
                sprintf('Required asset metadata key "%s" is missing in %s.', $key, $path)
            );
        }

        return $value;
    }
}
