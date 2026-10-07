<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use Contao\Database;
use Contao\System;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;

final class LegalGuaranteeMarkupProvider
{
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
        $translations = $this->getTranslations();

        return $this->markupBuilder->buildLabelMarkup(
            $svgMarkup,
            $translations['gllTitle'],
            $this->assetLocator->getGllEuUrlByLanguage($officialLanguage),
            $translations['gllEuLinkText'],
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
        $translations = $this->getTranslations();

        return $this->markupBuilder->buildLabelMarkup(
            $renderedSvg,
            $this->buildGaranTitle(
                $garanData['brand'],
                $garanData['modelIdentifier'],
                $garanData['durationYears']
            ),
            $this->assetLocator->resolveGaranEuUrl(),
            $translations['garanEuLinkText']
        );
    }

    public function renderCheckoutGllLinks(): string
    {
        foreach (ls_shop_cartX::getInstance()->itemsExtended as $item) {
            /** @var array{objProduct: ls_shop_product} $item */
            $displayData = $this->displayResolver->resolve($item['objProduct']->mainData);

            if ($displayData['showGll']) {
                $officialLanguage = $this->assetLocator->resolveGllSvgLanguage($this->getCurrentLocale());
                $pageData = $this->resolveGllInfoPage();

                return $this->markupBuilder->buildCheckoutGllLinks(
                    $pageData['url'] ?? null,
                    $pageData['title'] ?? null,
                    $this->assetLocator->getGllEuUrlByLanguage($officialLanguage),
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
            ];
        }

        return $this->markupBuilder->buildCheckoutGaranBlock($items, $this->getTranslations());
    }

    /**
     * @return iterable<string, mixed>|null
     */
    private function getSelectedVariantData(ls_shop_product $product): ?iterable
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
     *   garanEuLinkText: string,
     *   garanTitlePattern: string
     * }
     */
    private function getTranslations(): array
    {
        $translations = $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] ?? [];

        return [
            'gllTitle' => $translations['gllTitle'] ?? 'Legal guarantee, at least two years',
            'gllEuLinkText' => $translations['gllEuLinkText'] ?? 'EU information about the legal guarantee',
            'garanBlockHeadline' => $translations['garanBlockHeadline'] ?? 'Manufacturer\'s commercial guarantee for the following items:',
            'garanEuLinkText' => $translations['garanEuLinkText'] ?? 'EU information about the manufacturer\'s commercial guarantee',
            'garanTitlePattern' => $translations['garanTitlePattern'] ?? 'Manufacturer\'s commercial guarantee by %s for %s with %s years',
        ];
    }

    private function buildGaranTitle(string $brand, string $modelIdentifier, string $durationYears): string
    {
        $formattedDuration = $this->formatDurationForText($durationYears);
        $translations = $this->getTranslations();

        return sprintf(
            $translations['garanTitlePattern'],
            $brand,
            $modelIdentifier,
            $formattedDuration
        );
    }

    private function formatDurationForText(string $durationYears): string
    {
        $normalizedDuration = preg_replace('/\.0$/', '', $durationYears) ?? $durationYears;

        return str_replace('.', ',', $normalizedDuration);
    }

    /**
     * @return array{url: string, title: string}|null
     */
    private function resolveGllInfoPage(): ?array
    {
        $alias = $this->resolveCurrentGllInfoPageAlias();
        $rootId = (int) ($GLOBALS['objPage']->rootId ?? 0);

        if (null === $alias || $rootId <= 0) {
            return null;
        }

        $pageResult = Database::getInstance()
            ->prepare('SELECT id, title FROM tl_page WHERE alias = ?')
            ->execute($alias);

        if (!$pageResult->numRows) {
            return null;
        }

        $pageController = System::getContainer()->get('contao_helper.controller.page_controller');

        while ($pageResult->next()) {
            $pageModel = $pageController->getPageDetailsCached((int) $pageResult->id);

            if ((int) ($pageModel->rootId ?? 0) !== $rootId) {
                continue;
            }

            $pageUrl = ltrim(
                System::getContainer()->get('contao.routing.content_url_generator')->generate($pageModel),
                '/'
            );

            if ('' === $pageUrl) {
                return null;
            }

            return [
                'url' => $pageUrl,
                'title' => (string) $pageResult->title,
            ];
        }

        return null;
    }

    private function resolveCurrentGllInfoPageAlias(): ?string
    {
        return match (substr(strtolower($this->getCurrentLocale()), 0, 2)) {
            'de' => 'gewaehrleistungslabel',
            'en' => 'legal-guarantee',
            default => null,
        };
    }

    private function getCurrentLocale(): string
    {
        $pageLanguage = $GLOBALS['objPage']->language ?? null;

        if (is_string($pageLanguage) && '' !== $pageLanguage) {
            return $pageLanguage;
        }

        return 'en';
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
