<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

final class OfficialGuaranteeAssetLocator
{
    public const GLL_VERSION = 'v1.0';
    public const GARAN_VERSION = 'v1.0';

    private const GLL_BASE_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/gll/';
    private const GARAN_BASE_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/garan/';
    private const FONT_BASE_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/fonts/inter';
    private const STYLESHEET_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/legal-guarantee.css';
    private const GLL_SVG_FILENAME_PATTERN = 'legal-guarantee-notice-%s.svg';
    private const GLL_PDF_FILENAME_PATTERN = 'legal-guarantee-notice-%s.pdf';

    public function __construct(
        private readonly string $projectDir,
        private readonly OfficialGuaranteeLanguageResolver $languageResolver,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getAvailableGllSvgLanguages(): array
    {
        return $this->scanAvailableLanguages($this->getGllVersionDirectory(), 'svg');
    }

    /**
     * @return list<string>
     */
    public function getAvailableGllPdfLanguages(): array
    {
        return $this->scanAvailableLanguages($this->getGllVersionDirectory(), 'pdf');
    }

    public function resolveGllSvgLanguage(string $contaoLocale): string
    {
        return $this->languageResolver->resolveOfficialLanguage($contaoLocale, $this->getAvailableGllSvgLanguages());
    }

    public function resolveGllPdfLanguage(string $contaoLocale): ?string
    {
        $availableLanguages = $this->getAvailableGllPdfLanguages();

        if ([] === $availableLanguages) {
            return null;
        }

        return $this->languageResolver->resolveOfficialLanguage($contaoLocale, $availableLanguages);
    }

    public function resolveGllSvgPath(string $contaoLocale): string
    {
        return $this->getGllSvgPathByLanguage($this->resolveGllSvgLanguage($contaoLocale));
    }

    public function resolveGllPdfPath(string $contaoLocale): ?string
    {
        $resolvedLanguage = $this->resolveGllPdfLanguage($contaoLocale);

        if (null === $resolvedLanguage) {
            return null;
        }

        return $this->getGllPdfPathByLanguage($resolvedLanguage);
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
        return $this->getGaranVersionDirectory($version) . '/garan-label-colour-pdf.svg';
    }

    public function getGaranPdfTemplateNotePath(?string $version = null): string
    {
        return $this->getGaranVersionDirectory($version) . '/garan-label-colour-pdf.md';
    }

    public function getGaranNestedTemplatePath(?string $version = null): string
    {
        return $this->getGaranVersionDirectory($version) . '/garan-label-nested.svg';
    }

    public function getInterFontDirectory(): string
    {
        return $this->projectDir . '/' . self::FONT_BASE_RELATIVE_PATH;
    }

    public function getScopedStylesheetPath(): string
    {
        return $this->projectDir . '/' . self::STYLESHEET_RELATIVE_PATH;
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
}
