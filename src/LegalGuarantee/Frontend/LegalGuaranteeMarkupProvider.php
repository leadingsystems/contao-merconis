<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use Contao\PageModel;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_languageHelper;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;

final class LegalGuaranteeMarkupProvider
{
    private const GARAN_EU_URL = 'https://europa.eu/youreurope/commercial-guarantee-durability/index.htm';
    private const GLL_EU_URLS = [
        'de' => 'https://europa.eu/youreurope/garantien',
        'en' => 'https://europa.eu/youreurope/business/dealing-with-customers/consumer-contracts-guarantees/eu-legal-guarantee-notice-and-garan-label/index_en.htm',
    ];

    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
        private readonly GaranLabelRenderer $garanLabelRenderer,
        private readonly ProductGuaranteeDisplayResolver $displayResolver,
        private readonly LegalGuaranteeMarkupBuilder $markupBuilder,
    ) {
    }

    public function renderCurrentLanguageGllNotice(): string
    {
        $this->ensureStylesheetRegistered();

        $officialLanguage = $this->assetLocator->resolveGllSvgLanguage($this->getCurrentLocale());
        $svgMarkup = (string) file_get_contents($this->assetLocator->resolveGllSvgPath($officialLanguage));

        return $this->markupBuilder->buildSvgMarkup(
            $svgMarkup,
            $this->getTranslations()['gllTitle'],
            ['lang' => $officialLanguage]
        );
    }

    public function renderProductGllNotice(ls_shop_product $product): string
    {
        $displayData = $this->displayResolver->resolve($product->mainData);

        if (!$displayData['showGll']) {
            return '';
        }

        return $this->renderCurrentLanguageGllNotice();
    }

    public function renderProductGaranLabel(ls_shop_product $product): string
    {
        $displayData = $this->displayResolver->resolve(
            $product->mainData,
            $this->getSelectedVariantData($product)
        );

        if (!$displayData['showGaran'] || null === $displayData['garan']) {
            return '';
        }

        $this->ensureStylesheetRegistered();

        $garanData = $displayData['garan'];
        $renderedSvg = $this->garanLabelRenderer->render(
            $garanData['brand'],
            $garanData['modelIdentifier'],
            $garanData['durationYears']
        );

        return $this->markupBuilder->buildSvgMarkup(
            $renderedSvg,
            $this->buildGaranTitle(
                $garanData['brand'],
                $garanData['modelIdentifier'],
                $garanData['durationYears']
            )
        );
    }

    public function renderCheckoutGllLinks(): string
    {
        foreach (ls_shop_cartX::getInstance()->itemsExtended as $item) {
            /** @var array{objProduct: ls_shop_product} $item */
            $displayData = $this->displayResolver->resolve($item['objProduct']->mainData);

            if ($displayData['showGll']) {
                $pageId = (int) ls_shop_languageHelper::getLanguagePage(
                    'ls_shop_legalGuaranteeInfoPages',
                    false,
                    'id'
                );
                $pageUrl = ls_shop_languageHelper::getLanguagePage('ls_shop_legalGuaranteeInfoPages');
                $pageTitle = PageModel::findByPk($pageId)?->title ?? $pageUrl;
                $officialLanguage = $this->assetLocator->resolveGllSvgLanguage($this->getCurrentLocale());

                return $this->markupBuilder->buildCheckoutGllLinks(
                    $pageUrl,
                    $pageTitle,
                    $this->resolveGllEuUrl($officialLanguage),
                    $this->getTranslations()
                );
            }
        }

        return '';
    }

    public function renderCheckoutGaranBlock(): string
    {
        $items = [];

        foreach (ls_shop_cartX::getInstance()->itemsExtended as $item) {
            /** @var array{objProduct: ls_shop_product} $item */
            $product = $item['objProduct'];
            $displayData = $this->displayResolver->resolve(
                $product->mainData,
                $this->getSelectedVariantData($product)
            );

            if (!$displayData['showGaran'] || null === $displayData['garan']) {
                continue;
            }

            $items[] = [
                'productTitle' => (string) $product->_title,
                'variantTitle' => $this->getSelectedVariantTitle($product),
                'labelMarkup' => $this->renderProductGaranLabel($product),
                'euUrl' => self::GARAN_EU_URL,
            ];
        }

        return $this->markupBuilder->buildCheckoutGaranBlock($items, $this->getTranslations());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getSelectedVariantData(ls_shop_product $product): ?array
    {
        if (!$product->_variantIsSelected) {
            return null;
        }

        /** @var ls_shop_variant $selectedVariant */
        $selectedVariant = $product->_selectedVariant;

        return $selectedVariant->mainData;
    }

    private function getSelectedVariantTitle(ls_shop_product $product): string
    {
        if (!$product->_variantIsSelected) {
            return '';
        }

        /** @var ls_shop_variant $selectedVariant */
        $selectedVariant = $product->_selectedVariant;

        return $selectedVariant->_hasOriginalTitle ? (string) $selectedVariant->_title : '';
    }

    /**
     * @return array{
     *   gllTitle: string,
     *   gllEuLinkText: string,
     *   garanBlockHeadline: string,
     *   garanEuLinkText: string
     * }
     */
    private function getTranslations(): array
    {
        $language = str_starts_with($this->getCurrentLocale(), 'de') ? 'de' : 'en';
        $translations = $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'][$language] ?? [];

        if ('de' === $language) {
            return [
                'gllTitle' => $translations['gllTitle'] ?? 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
                'gllEuLinkText' => $translations['gllEuLinkText'] ?? 'EU-Information zur gesetzlichen Gewährleistung',
                'garanBlockHeadline' => $translations['garanBlockHeadline'] ?? 'Herstellergarantie für folgende Artikel:',
                'garanEuLinkText' => $translations['garanEuLinkText'] ?? 'EU-Information zur Herstellergarantie',
            ];
        }

        return [
            'gllTitle' => $translations['gllTitle'] ?? 'Legal guarantee, at least two years',
            'gllEuLinkText' => $translations['gllEuLinkText'] ?? 'EU information about the legal guarantee',
            'garanBlockHeadline' => $translations['garanBlockHeadline'] ?? 'Manufacturer\'s commercial guarantee for the following items:',
            'garanEuLinkText' => $translations['garanEuLinkText'] ?? 'EU information about the manufacturer\'s commercial guarantee',
        ];
    }

    private function buildGaranTitle(string $brand, string $modelIdentifier, string $durationYears): string
    {
        $formattedDuration = $this->formatDurationForText($durationYears);

        if (str_starts_with($this->getCurrentLocale(), 'de')) {
            return sprintf(
                'Herstellergarantie von %s für %s mit %s Jahren',
                $brand,
                $modelIdentifier,
                $formattedDuration
            );
        }

        return sprintf(
            'Manufacturer\'s commercial guarantee by %s for %s with %s years',
            $brand,
            $modelIdentifier,
            $formattedDuration
        );
    }

    private function formatDurationForText(string $durationYears): string
    {
        $normalizedDuration = preg_replace('/\.0$/', '', $durationYears) ?? $durationYears;

        if (str_starts_with($this->getCurrentLocale(), 'de')) {
            return str_replace('.', ',', $normalizedDuration);
        }

        return $normalizedDuration;
    }

    private function resolveGllEuUrl(string $officialLanguage): string
    {
        return self::GLL_EU_URLS[$officialLanguage] ?? self::GLL_EU_URLS['en'];
    }

    private function getCurrentLocale(): string
    {
        $pageLanguage = $GLOBALS['objPage']->language ?? null;

        if (is_string($pageLanguage) && '' !== $pageLanguage) {
            return $pageLanguage;
        }

        return ls_shop_languageHelper::getFallbackLanguage();
    }

    private function ensureStylesheetRegistered(): void
    {
        $stylesheetPath = 'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee.css';

        if (!isset($GLOBALS['TL_CSS']) || !is_array($GLOBALS['TL_CSS'])) {
            $GLOBALS['TL_CSS'] = [];
        }

        if (!in_array($stylesheetPath, $GLOBALS['TL_CSS'], true)) {
            $GLOBALS['TL_CSS'][] = $stylesheetPath;
        }
    }
}
