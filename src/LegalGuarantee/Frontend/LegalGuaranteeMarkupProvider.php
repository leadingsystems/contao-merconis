<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\GuaranteeDurationFormatter;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class LegalGuaranteeMarkupProvider
{
    /**
     * @var array<string, GaranLabelRendererInterface>
     */
    private array $garanLabelRenderersByVersion = [];

    /**
     * @param iterable<GaranLabelRendererInterface> $garanLabelRenderers
     */
    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
        private readonly ProductGuaranteeDisplayResolver $displayResolver,
        private readonly GaranLabelGrouping $garanLabelGrouping,
        private readonly LegalGuaranteeMarkupBuilder $markupBuilder,
        private readonly LoggerInterface $contaoErrorLogger,
        iterable $garanLabelRenderers,
    ) {
        foreach ($garanLabelRenderers as $renderer) {
            $this->garanLabelRenderersByVersion[$renderer->getVersion()] = $renderer;
        }
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
        $renderedSvg = $this->getCurrentGaranLabelRenderer()->render(
            $garanData['brand'],
            $garanData['modelIdentifier'],
            $garanData['durationMonths']
        );
        $translations = $this->getTranslations();

        return $this->markupBuilder->buildLabelMarkup(
            $renderedSvg,
            $this->buildGaranTitle(
                $garanData['brand'],
                $garanData['modelIdentifier'],
                $garanData['durationMonths']
            ),
            $this->assetLocator->resolveGaranEuUrl(),
            $translations['garanEuLinkText']
        );
    }

    public function checkoutGllNotice(): string
    {
        foreach (ls_shop_cartX::getInstance()->itemsExtended as $item) {
            /** @var array{objProduct: ls_shop_product} $item */
            $displayData = $this->displayResolver->resolve($item['objProduct']->mainData);

            if ($displayData['showGll']) {
                return $this->renderCurrentLanguageGllNotice();
            }
        }

        return '';
    }

    public function checkoutGaranBlock(): string
    {
        $entries = [];

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

            $garanData = $displayData['garan'];
            $entries[] = [
                'productId' => (string) $product->ls_ID,
                'garanVersion' => OfficialGuaranteeAssetLocator::GARAN_VERSION,
                'brand' => $garanData['brand'],
                'modelIdentifier' => $garanData['modelIdentifier'],
                'durationMonths' => $garanData['durationMonths'],
                'productName' => $this->buildCheckoutProductName($product),
                'articleNumber' => $this->extractCheckoutArticleNumber($product),
            ];
        }

        $articleNumbersByGroupKey = [];

        foreach ($entries as $entry) {
            $groupKey = $this->buildGaranGroupingKey(
                $entry['productId'],
                $entry['garanVersion'],
                $entry['brand'],
                $entry['modelIdentifier'],
                $entry['durationMonths']
            );

            $articleNumbersByGroupKey[$groupKey][] = $entry['articleNumber'];
        }

        $groups = $this->garanLabelGrouping->groupEntries($entries);

        if ([] === $groups) {
            return '';
        }

        $this->ensureStylesheetRegistered();
        $translations = $this->getTranslations();
        $items = [];

        foreach ($groups as $group) {
            $groupKey = $this->buildGaranGroupingKey(
                (string) $group['productId'],
                (string) $group['garanVersion'],
                (string) $group['brand'],
                (string) $group['modelIdentifier'],
                (string) $group['durationMonths']
            );
            $articleNumbers = array_values(array_filter(
                $articleNumbersByGroupKey[$groupKey] ?? [],
                static fn (string $articleNumber): bool => $articleNumber !== ''
            ));

            try {
                $items[] = [
                    'productNames' => $group['productNames'],
                    'labelMarkup' => $this->markupBuilder->buildLabelMarkup(
                        $this->getCurrentGaranLabelRenderer()->render(
                            $group['brand'],
                            $group['modelIdentifier'],
                            $group['durationMonths']
                        ),
                        $this->buildGaranTitle(
                            $group['brand'],
                            $group['modelIdentifier'],
                            $group['durationMonths']
                        ),
                        $this->assetLocator->resolveGaranEuUrl(),
                        $translations['garanEuLinkText']
                    ),
                ];
            } catch (Throwable $exception) {
                $this->logUnavailableCheckoutLabel($articleNumbers, $exception);
            }
        }

        if ([] === $items) {
            return '';
        }

        return $this->markupBuilder->buildCheckoutGaranBlock($items, $translations);
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

    private function extractCheckoutArticleNumber(ls_shop_product $product): string
    {
        if ($product->_variantIsSelected) {
            /** @var ls_shop_variant $selectedVariant */
            $selectedVariant = $product->_selectedVariant;

            return trim((string) $selectedVariant->_code);
        }

        return trim((string) $product->_code);
    }

    private function buildCheckoutProductName(ls_shop_product $product): string
    {
        $productTitle = trim((string) $product->_title);
        $variantTitle = trim($this->getSelectedVariantTitle($product));
        $articleNumber = $this->extractCheckoutArticleNumber($product);
        $productName = $productTitle;

        if ('' !== $variantTitle) {
            $productName = sprintf('%s (%s)', $productTitle, $variantTitle);
        }

        if ('' === $articleNumber) {
            return $productName;
        }

        return sprintf('%s - %s', $productName, $articleNumber);
    }

    private function buildGaranGroupingKey(
        string $productId,
        string $garanVersion,
        string $brand,
        string $modelIdentifier,
        string $durationMonths
    ): string {
        return json_encode([
            trim($productId),
            trim($garanVersion),
            trim($brand),
            trim($modelIdentifier),
            trim($durationMonths),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<string> $articleNumbers
     */
    private function logUnavailableCheckoutLabel(array $articleNumbers, Throwable $exception): void
    {
        $normalisedArticleNumbers = array_values(array_filter(array_map(
            static fn (string $articleNumber): string => trim($articleNumber),
            $articleNumbers
        ), static fn (string $articleNumber): bool => $articleNumber !== ''));

        $this->contaoErrorLogger->error(sprintf(
            'GARAN checkout label rendering failed for article numbers [%s]: %s',
            [] !== $normalisedArticleNumbers ? implode(', ', $normalisedArticleNumbers) : 'unknown',
            $exception->getMessage()
        ));
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

    private function buildGaranTitle(
        string $brand,
        string $modelIdentifier,
        string $durationMonths
    ): string
    {
        $formattedDuration = GuaranteeDurationFormatter::formatMonthsForDisplay($durationMonths);
        $translations = $this->getTranslations();

        return sprintf(
            $translations['garanTitlePattern'],
            $brand,
            $modelIdentifier,
            $formattedDuration
        );
    }

    private function getCurrentLocale(): string
    {
        $pageLanguage = $GLOBALS['objPage']->language ?? null;

        if (is_string($pageLanguage) && '' !== $pageLanguage) {
            return $pageLanguage;
        }

        return 'en';
    }

    private function getCurrentGaranLabelRenderer(): GaranLabelRendererInterface
    {
        $renderer = $this->garanLabelRenderersByVersion[OfficialGuaranteeAssetLocator::GARAN_VERSION] ?? null;

        if (null === $renderer) {
            throw new RuntimeException(sprintf(
                'No GARAN label renderer registered for version "%s".',
                OfficialGuaranteeAssetLocator::GARAN_VERSION
            ));
        }

        return $renderer;
    }

    private function ensureStylesheetRegistered(): void
    {
        $stylesheetPath = 'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee-fonts.css';

        if (!isset($GLOBALS['TL_CSS']) || !is_array($GLOBALS['TL_CSS'])) {
            $GLOBALS['TL_CSS'] = [];
        }

        if (!in_array($stylesheetPath, $GLOBALS['TL_CSS'], true)) {
            $GLOBALS['TL_CSS'][] = $stylesheetPath;
        }
    }
}
