<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee;

use JsonException;
use RuntimeException;

final class OfficialGuaranteeAssetLocator
{
    public const LGN_VERSION = 'v1.0';
    public const GARAN_VERSION = 'v1.0';

    private const LGN_BASE_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/lgn/';
    private const LGN_PUBLIC_BASE_PATH = 'bundles/leadingsystemsmerconis/legal-guarantee/lgn/';
    private const GARAN_BASE_RELATIVE_PATH = 'src/Resources/legal-guarantee/garan/';
    private const FONT_BASE_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/fonts/inter';
    private const FONT_STYLESHEET_RELATIVE_PATH = 'src/Resources/public/legal-guarantee/legal-guarantee-fonts.css';
    private const LGN_SVG_FILENAME_PATTERN = 'legal-guarantee-notice-%s.svg';
    private const LGN_PDF_FILENAME_PATTERN = 'legal-guarantee-notice-%s.pdf';
    private const EU_TEXT_LINKS_FILENAME = 'eu-text-links.json';

    public function __construct(
        private readonly string $projectDir,
        private readonly OfficialGuaranteeLanguageResolver $languageResolver,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getAvailableLgnSvgLanguages(?string $version = null): array
    {
        return $this->scanAvailableLanguages($this->getLgnVersionDirectory($version), 'svg');
    }

    /**
     * @return list<string>
     */
    public function getAvailableLgnPdfLanguages(?string $version = null): array
    {
        return $this->scanAvailableLanguages($this->getLgnVersionDirectory($version), 'pdf');
    }

    public function resolveLgnSvgLanguage(string $contaoLocale, ?string $version = null): string
    {
        return $this->languageResolver->resolveOfficialLanguage(
            $contaoLocale,
            $this->getAvailableLgnSvgLanguages($version)
        );
    }

    public function resolveLgnPdfLanguage(string $contaoLocale, ?string $version = null): ?string
    {
        $availableLanguages = $this->getAvailableLgnPdfLanguages($version);

        if ([] === $availableLanguages) {
            return null;
        }

        return $this->languageResolver->resolveOfficialLanguage($contaoLocale, $availableLanguages);
    }

    public function resolveLgnSvgPath(string $contaoLocale, ?string $version = null): string
    {
        return $this->getLgnSvgPathByLanguage(
            $this->resolveLgnSvgLanguage($contaoLocale, $version),
            $version
        );
    }

    public function resolveLgnPdfPath(string $contaoLocale, ?string $version = null): ?string
    {
        $resolvedLanguage = $this->resolveLgnPdfLanguage($contaoLocale, $version);

        if (null === $resolvedLanguage) {
            return null;
        }

        return $this->getLgnPdfPathByLanguage($resolvedLanguage, $version);
    }

    public function getLgnVersionDirectory(?string $version = null): string
    {
        return $this->projectDir . '/' . self::LGN_BASE_RELATIVE_PATH . ($version ?? self::LGN_VERSION);
    }

    public function getLgnSvgPathByLanguage(string $officialLanguage, ?string $version = null): string
    {
        return $this->getLgnVersionDirectory($version) . '/' . sprintf(
            self::LGN_SVG_FILENAME_PATTERN,
            $officialLanguage
        );
    }

    /**
     * Öffentlicher Pfad der amtlichen LGN-SVG relativ zum Web-Root, wie ihn der Browser
     * anfordert (Spec 27, Abschnitt 5.4).
     */
    public function getLgnSvgPublicPathByLanguage(string $officialLanguage, ?string $version = null): string
    {
        return self::LGN_PUBLIC_BASE_PATH . ($version ?? self::LGN_VERSION) . '/' . sprintf(
            self::LGN_SVG_FILENAME_PATTERN,
            $officialLanguage
        );
    }

    public function getLgnPdfPathByLanguage(string $officialLanguage, ?string $version = null): ?string
    {
        $path = $this->getLgnVersionDirectory($version) . '/' . sprintf(
            self::LGN_PDF_FILENAME_PATTERN,
            $officialLanguage
        );

        return is_file($path) ? $path : null;
    }

    public function getLgnEuTextLinksPath(?string $version = null): string
    {
        return $this->getLgnVersionDirectory($version) . '/' . self::EU_TEXT_LINKS_FILENAME;
    }

    public function resolveLgnEuUrl(string $contaoLocale, ?string $version = null): string
    {
        return $this->getLgnEuUrlByLanguage(
            $this->resolveLgnSvgLanguage($contaoLocale),
            $version
        );
    }

    public function getLgnEuUrlByLanguage(string $officialLanguage, ?string $version = null): string
    {
        return $this->readRequiredStringValue(
            $this->readRequiredJsonFile($this->getLgnEuTextLinksPath($version)),
            $officialLanguage,
            $this->getLgnEuTextLinksPath($version)
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
        $pattern = $directory . '/' . sprintf(self::LGN_SVG_FILENAME_PATTERN, '*');

        if ('pdf' === $extension) {
            $pattern = $directory . '/' . sprintf(self::LGN_PDF_FILENAME_PATTERN, '*');
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
