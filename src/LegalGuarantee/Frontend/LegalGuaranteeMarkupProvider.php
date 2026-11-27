<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use Closure;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranNestedLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\GuaranteeDurationFormatter;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeProviderInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class LegalGuaranteeMarkupProvider
{
    public const UNIT_TEMPLATE = 'template_legalGuaranteeUnit';
    public const BUTTON_TEMPLATE = 'template_legalGuaranteeButton';
    public const ASSIGNMENT_TEMPLATE = 'template_legalGuaranteeAssignment';

    private const TYPE_LGN = 'lgn';
    private const TYPE_GARAN = 'garan';
    private const TYPE_GARAN_NESTED = 'garan-nested';
    private const CHECKOUT_REFERENCE = 'checkout';

    /**
     * Abmessungen der amtlichen LGN-Grafik (`viewBox` `0 0 595.28 841.89`) für das Bild
     * in der geöffneten Ansicht.
     */
    private const LGN_IMAGE_WIDTH = 595;
    private const LGN_IMAGE_HEIGHT = 842;

    /**
     * @var array<string, GaranLabelRendererInterface>
     */
    private array $garanLabelRenderersByVersion = [];

    /**
     * @var array<string, GaranNestedLabelRendererInterface>
     */
    private array $garanNestedLabelRenderersByVersion = [];

    /**
     * @param iterable<GaranLabelRendererInterface> $garanLabelRenderers
     * @param iterable<GaranNestedLabelRendererInterface> $garanNestedLabelRenderers
     */
    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
        private readonly ProductGuaranteeDisplayResolver $displayResolver,
        private readonly GaranLabelGrouping $garanLabelGrouping,
        private readonly LegalGuaranteeMarkupBuilder $markupBuilder,
        private readonly LegalGuaranteeTemplateRendererInterface $templateRenderer,
        private readonly LoggerInterface $contaoErrorLogger,
        private readonly MemberGroupLgnModeProviderInterface $memberGroupLgnModeProvider,
        iterable $garanLabelRenderers,
        iterable $garanNestedLabelRenderers,
    ) {
        foreach ($garanLabelRenderers as $renderer) {
            $this->garanLabelRenderersByVersion[$renderer->getVersion()] = $renderer;
        }

        foreach ($garanNestedLabelRenderers as $nestedRenderer) {
            $this->garanNestedLabelRenderersByVersion[$nestedRenderer->getVersion()] = $nestedRenderer;
        }
    }

    /**
     * Einheit `--lgn` als Inline-SVG für den Insert-Tag `{{shop_eu_lgn}}`.
     */
    public function renderCurrentLanguageLgnNotice(): string
    {
        return $this->renderLgnUnit(self::TYPE_LGN);
    }

    /**
     * Direkte Ausgabe der Produktdetailseite (`_lgnNotice`).
     */
    public function renderProductLgnNotice(ls_shop_product $product): string
    {
        if (!$this->isLgnShownForProduct($product)) {
            return '';
        }

        return $this->renderLgnUnit($this->buildProductIdScope(self::TYPE_LGN, $product));
    }

    /**
     * Button der Produktdetailseite (`_lgnNoticeButton`).
     */
    public function renderProductLgnNoticeButton(ls_shop_product $product): string
    {
        if (!$this->isLgnShownForProduct($product)) {
            return '';
        }

        return $this->renderLgnButton($this->buildProductIdScope(self::TYPE_LGN, $product));
    }

    /**
     * Direkte Ausgabe der Produktdetailseite (`_garanLabel`). Scheitert das Label, entfällt
     * es mit einem Log-Eintrag (Spec 27, Abschnitt 7.1).
     */
    public function renderProductGaranLabel(ls_shop_product $product): string
    {
        $garanData = $this->resolveProductGaranData($product);

        if (null === $garanData) {
            return '';
        }

        try {
            $unitData = $this->prepareGaranUnitData(
                $garanData,
                $this->buildProductIdScope(self::TYPE_GARAN, $product)
            );
        } catch (Throwable $exception) {
            $this->logUnavailableProductLabel('GARAN label', $product, $exception);

            return '';
        }

        return $this->templateRenderer->render(self::UNIT_TEMPLATE, $unitData);
    }

    /**
     * Button der Produktdetailseite (`_garanLabelButton`) mit dem Nested Label. Scheitert
     * eines der Labels, entfällt der ganze Button mit einem Log-Eintrag (Spec 27,
     * Abschnitt 7.1).
     */
    public function renderProductGaranLabelButton(ls_shop_product $product): string
    {
        $garanData = $this->resolveProductGaranData($product);

        if (null === $garanData) {
            return '';
        }

        try {
            $nestedSvgMarkup = $this->markupBuilder->buildDecorativeSvgMarkup(
                $this->getCurrentGaranNestedLabelRenderer()->render($garanData['durationMonths']),
                $this->buildProductIdScope(self::TYPE_GARAN_NESTED, $product)
            );
            $unitData = $this->prepareGaranUnitData(
                $garanData,
                $this->buildProductIdScope(self::TYPE_GARAN, $product)
            );
        } catch (Throwable $exception) {
            $this->logUnavailableProductLabel('GARAN label button', $product, $exception);

            return '';
        }

        $buttonTranslations = $this->getButtonTranslations();

        return $this->templateRenderer->render(self::BUTTON_TEMPLATE, [
            'type' => self::TYPE_GARAN,
            'popoverId' => $this->markupBuilder->reservePopoverId(
                $this->buildProductIdScope(self::TYPE_GARAN, $product)
            ),
            'triggerText' => '',
            'triggerLabel' => sprintf(
                $buttonTranslations['garanButtonLabel'],
                GuaranteeDurationFormatter::formatMonthsForDisplay($garanData['durationMonths'])
            ),
            'nestedSvgMarkup' => $nestedSvgMarkup,
            'closeLabel' => $buttonTranslations['popoverCloseLabel'],
            'unitMarkup' => $this->templateRenderer->render(self::UNIT_TEMPLATE, $unitData),
        ]);
    }

    /**
     * Direkte Ausgabe der letzten Bestellseite (`checkoutLgnNotice`).
     */
    public function checkoutLgnNotice(): string
    {
        if (!$this->isLgnShownForCart()) {
            return '';
        }

        return $this->renderLgnUnit($this->buildIdScope(self::TYPE_LGN, self::CHECKOUT_REFERENCE));
    }

    /**
     * Button der letzten Bestellseite (`checkoutLgnNoticeButton`).
     */
    public function checkoutLgnNoticeButton(): string
    {
        if (!$this->isLgnShownForCart()) {
            return '';
        }

        return $this->renderLgnButton($this->buildIdScope(self::TYPE_LGN, self::CHECKOUT_REFERENCE));
    }

    /**
     * Zuordnungsblock der letzten Bestellseite (`checkoutGaranBlock`, Spec 27,
     * Abschnitt 7.3). Scheitert ein Label, entfällt es mit seinen Positionen und einem
     * Log-Eintrag; entfallen alle, entfällt der Block.
     */
    public function checkoutGaranBlock(): string
    {
        $entries = [];
        $memberGroupLgnMode = $this->memberGroupLgnModeProvider->getCurrentMemberGroupLgnMode();

        foreach (ls_shop_cartX::getInstance()->itemsExtended as $item) {
            /** @var array{objProduct: ls_shop_product} $item */
            $product = $item['objProduct'];
            $displayData = $this->displayResolver->resolve(
                $memberGroupLgnMode,
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
                'position' => $this->buildCheckoutPosition($product),
            ];
        }

        $groups = $this->garanLabelGrouping->groupEntries($entries);

        if ([] === $groups) {
            return '';
        }

        $items = [];

        foreach ($groups as $group) {
            try {
                $unitData = $this->prepareGaranUnitData(
                    [
                        'brand' => $group['brand'],
                        'modelIdentifier' => $group['modelIdentifier'],
                        'durationMonths' => $group['durationMonths'],
                    ],
                    $this->buildIdScope(self::TYPE_GARAN, self::CHECKOUT_REFERENCE)
                );
            } catch (Throwable $exception) {
                $this->logUnavailableCheckoutLabel(
                    array_column($group['positions'], 'articleNumber'),
                    $exception
                );

                continue;
            }

            $items[] = [
                'positions' => $group['positions'],
                'unitMarkup' => $this->templateRenderer->render(self::UNIT_TEMPLATE, $unitData),
            ];
        }

        if ([] === $items) {
            return '';
        }

        return $this->templateRenderer->render(self::ASSIGNMENT_TEMPLATE, [
            'headline' => $this->getTranslations()['garanBlockHeadline'],
            'items' => $items,
        ]);
    }

    /**
     * Variablen der letzten Bestellseite als Closures. Contao ruft eine Template-Variable
     * auf, die ein Callable ist; jede Closure berechnet ihr Ergebnis erst beim ersten Lesen
     * und hält es danach fest, weil das Template eine Variable mehrfach liest.
     *
     * @return array{
     *   checkoutLgnNotice: Closure(): string,
     *   checkoutLgnNoticeButton: Closure(): string,
     *   checkoutGaranBlock: Closure(): string
     * }
     */
    public function createCheckoutTemplateVariables(): array
    {
        return [
            'checkoutLgnNotice' => $this->createMemoizedOutput(fn (): string => $this->checkoutLgnNotice()),
            'checkoutLgnNoticeButton' => $this->createMemoizedOutput(fn (): string => $this->checkoutLgnNoticeButton()),
            'checkoutGaranBlock' => $this->createMemoizedOutput(fn (): string => $this->checkoutGaranBlock()),
        ];
    }

    /**
     * @param Closure(): string $computeOutput
     *
     * @return Closure(): string
     */
    private function createMemoizedOutput(Closure $computeOutput): Closure
    {
        $isComputed = false;
        $output = '';

        return static function () use ($computeOutput, &$isComputed, &$output): string {
            if (!$isComputed) {
                $output = $computeOutput();
                $isComputed = true;
            }

            return $output;
        };
    }

    private function renderLgnUnit(string $idScope): string
    {
        $this->ensureStylesheetRegistered();

        $officialLanguage = $this->assetLocator->resolveLgnSvgLanguage($this->getCurrentLocale());
        $svgMarkup = (string) file_get_contents($this->assetLocator->getLgnSvgPathByLanguage($officialLanguage));
        $translations = $this->getTranslations();

        return $this->templateRenderer->render(self::UNIT_TEMPLATE, [
            'type' => self::TYPE_LGN,
            'svgMarkup' => $this->markupBuilder->buildSvgMarkup(
                $svgMarkup,
                $translations['lgnTitle'],
                $idScope,
                ['lang' => $officialLanguage]
            ),
            'image' => null,
            'euUrl' => $this->assetLocator->getLgnEuUrlByLanguage($officialLanguage),
            'euLinkText' => $translations['lgnEuLinkText'],
        ]);
    }

    /**
     * Die geöffnete Ansicht zeigt die öffentliche amtliche SVG als Bild, das erst beim
     * Öffnen geladen wird (Spec 27, Abschnitt 8.3).
     */
    private function renderLgnButton(string $idScope): string
    {
        $officialLanguage = $this->assetLocator->resolveLgnSvgLanguage($this->getCurrentLocale());
        $translations = $this->getTranslations();
        $buttonTranslations = $this->getButtonTranslations();

        return $this->templateRenderer->render(self::BUTTON_TEMPLATE, [
            'type' => self::TYPE_LGN,
            'popoverId' => $this->markupBuilder->reservePopoverId($idScope),
            'triggerText' => $buttonTranslations['lgnButtonText'],
            'triggerLabel' => '',
            'nestedSvgMarkup' => '',
            'closeLabel' => $buttonTranslations['popoverCloseLabel'],
            'unitMarkup' => $this->templateRenderer->render(self::UNIT_TEMPLATE, [
                'type' => self::TYPE_LGN,
                'svgMarkup' => '',
                'image' => [
                    'src' => $this->assetLocator->getLgnSvgPublicPathByLanguage($officialLanguage),
                    'alt' => $translations['lgnTitle'],
                    'width' => self::LGN_IMAGE_WIDTH,
                    'height' => self::LGN_IMAGE_HEIGHT,
                ],
                'euUrl' => $this->assetLocator->getLgnEuUrlByLanguage($officialLanguage),
                'euLinkText' => $translations['lgnEuLinkText'],
            ]),
        ]);
    }

    /**
     * @param array{brand: string, modelIdentifier: string, durationMonths: string} $garanData
     *
     * @return array{type: string, svgMarkup: string, image: null, euUrl: string, euLinkText: string}
     *
     * @throws RuntimeException Wenn kein Renderer registriert ist oder das Label nicht gerendert werden kann
     */
    private function prepareGaranUnitData(array $garanData, string $idScope): array
    {
        $this->ensureStylesheetRegistered();

        $renderedSvg = $this->getCurrentGaranLabelRenderer()->render(
            $garanData['brand'],
            $garanData['modelIdentifier'],
            $garanData['durationMonths']
        );

        return [
            'type' => self::TYPE_GARAN,
            'svgMarkup' => $this->markupBuilder->buildSvgMarkup(
                $renderedSvg,
                $this->buildGaranTitle(
                    $garanData['brand'],
                    $garanData['modelIdentifier'],
                    $garanData['durationMonths']
                ),
                $idScope
            ),
            'image' => null,
            'euUrl' => $this->assetLocator->resolveGaranEuUrl(),
            'euLinkText' => $this->getTranslations()['garanEuLinkText'],
        ];
    }

    private function isLgnShownForProduct(ls_shop_product $product): bool
    {
        $displayData = $this->displayResolver->resolve(
            $this->memberGroupLgnModeProvider->getCurrentMemberGroupLgnMode(),
            $product->mainData
        );

        return $displayData['showLgn'];
    }

    private function isLgnShownForCart(): bool
    {
        $memberGroupLgnMode = $this->memberGroupLgnModeProvider->getCurrentMemberGroupLgnMode();

        foreach (ls_shop_cartX::getInstance()->itemsExtended as $item) {
            /** @var array{objProduct: ls_shop_product} $item */
            $displayData = $this->displayResolver->resolve($memberGroupLgnMode, $item['objProduct']->mainData);

            if ($displayData['showLgn']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{brand: string, modelIdentifier: string, durationMonths: string}|null
     */
    private function resolveProductGaranData(ls_shop_product $product): ?array
    {
        $displayData = $this->displayResolver->resolve(
            $this->memberGroupLgnModeProvider->getCurrentMemberGroupLgnMode(),
            $product->mainData,
            $this->getSelectedVariantData($product)
        );

        if (!$displayData['showGaran']) {
            return null;
        }

        return $displayData['garan'];
    }

    private function buildProductIdScope(string $type, ls_shop_product $product): string
    {
        return $this->buildIdScope($type, (string) $product->ls_ID);
    }

    private function buildIdScope(string $type, string $reference): string
    {
        return $type . '-' . $reference;
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
     * Artikelnummer: `_code` der gewählten Variante, sonst des Produkts
     * (Implementierungsentscheidung 0022).
     */
    private function extractArticleNumber(ls_shop_product $product): string
    {
        if ($product->_variantIsSelected) {
            /** @var ls_shop_variant $selectedVariant */
            $selectedVariant = $product->_selectedVariant;

            return trim((string) $selectedVariant->_code);
        }

        return trim((string) $product->_code);
    }

    /**
     * @return array{productName: string, variantName: string, articleNumber: string}
     */
    private function buildCheckoutPosition(ls_shop_product $product): array
    {
        return [
            'productName' => trim((string) $product->_title),
            'variantName' => trim($this->getSelectedVariantTitle($product)),
            'articleNumber' => $this->extractArticleNumber($product),
        ];
    }

    private function logUnavailableProductLabel(string $outputName, ls_shop_product $product, Throwable $exception): void
    {
        $articleNumber = $this->extractArticleNumber($product);

        $this->contaoErrorLogger->error(sprintf(
            '%s rendering failed on the product detail page for article number %s: %s',
            $outputName,
            '' !== $articleNumber ? $articleNumber : 'unknown',
            $exception->getMessage()
        ));
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
     *   lgnTitle: string,
     *   lgnEuLinkText: string,
     *   garanBlockHeadline: string,
     *   garanEuLinkText: string,
     *   garanTitlePattern: string
     * }
     */
    private function getTranslations(): array
    {
        $translations = $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'];

        return [
            'lgnTitle' => $translations['lgnTitle'],
            'lgnEuLinkText' => $translations['lgnEuLinkText'],
            'garanBlockHeadline' => $translations['garanBlockHeadline'],
            'garanEuLinkText' => $translations['garanEuLinkText'],
            'garanTitlePattern' => $translations['garanTitlePattern'],
        ];
    }

    /**
     * @return array{lgnButtonText: string, garanButtonLabel: string, popoverCloseLabel: string}
     */
    private function getButtonTranslations(): array
    {
        $translations = $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'];

        return [
            'lgnButtonText' => $translations['lgnButtonText'],
            'garanButtonLabel' => $translations['garanButtonLabel'],
            'popoverCloseLabel' => $translations['popoverCloseLabel'],
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

    /**
     * @throws RuntimeException Wenn für `GARAN_VERSION` kein Renderer des vollen Labels registriert ist
     */
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

    /**
     * @throws RuntimeException Wenn für `GARAN_VERSION` kein Renderer des Nested Labels registriert ist
     */
    private function getCurrentGaranNestedLabelRenderer(): GaranNestedLabelRendererInterface
    {
        $renderer = $this->garanNestedLabelRenderersByVersion[OfficialGuaranteeAssetLocator::GARAN_VERSION] ?? null;

        if (null === $renderer) {
            throw new RuntimeException(sprintf(
                'No GARAN nested label renderer registered for version "%s".',
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
