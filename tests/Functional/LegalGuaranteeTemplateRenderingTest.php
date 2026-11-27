<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../Support/ContaoTestApplication.php';
require_once __DIR__ . '/../Support/RecordingLegalGuaranteeTemplateRenderer.php';

use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeTemplateRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranNestedLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeProviderInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\Tests\Support\ContaoTestApplication;
use LeadingSystems\MerconisBundle\Tests\Support\RecordingLegalGuaranteeTemplateRenderer;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use Contao\FrontendTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionProperty;

/**
 * Rendert die Bausteine der Label-Ausgabe über die Template-Auflösung von Contao (ADR 0028,
 * Entscheidung 6; Implementierungsentscheidung 0031, Abschnitt "Tests").
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LegalGuaranteeTemplateRenderingTest extends TestCase
{
    private const OVERRIDE_MARKER_CLASS = 'merconis-legal-guarantee-test-override';
    private const ORDER_REVIEW_EXCERPT_TEMPLATE = 'template_legalGuaranteeTestOrderReviewExcerpt';
    private const PRODUCT_ID = 6560;

    /**
     * @var list<string>
     */
    private array $temporaryTemplatePaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        ContaoTestApplication::boot('de');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryTemplatePaths as $temporaryTemplatePath) {
            if (is_file($temporaryTemplatePath)) {
                unlink($temporaryTemplatePath);
            }
        }

        $instanceProperty = new ReflectionProperty(ls_shop_cartX::class, 'objInstance');
        $instanceProperty->setValue(null, null);

        parent::tearDown();
    }

    public function testUnitTemplateRendersLgnUnitWithInlineSvgAndEuLinkBelow(): void
    {
        $xpath = ContaoTestApplication::createXPath($this->createProvider()->renderCurrentLanguageLgnNotice());
        $unit = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-unit');
        $children = $this->getElementChildren($unit);
        $svg = $this->findSingleElement($xpath, './/svg', $children[0]);
        $title = $this->findSingleElement($xpath, './/title', $svg);
        $link = $this->findSingleElement($xpath, './/a', $children[1]);

        self::assertSame('merconis-legal-guarantee-unit merconis-legal-guarantee-unit--lgn', $unit->getAttribute('class'));
        self::assertCount(2, $children);
        self::assertSame('merconis-legal-guarantee-unit__label', $children[0]->getAttribute('class'));
        self::assertSame('merconis-legal-guarantee-unit__link', $children[1]->getAttribute('class'));
        self::assertSame('p', $children[1]->tagName);
        self::assertSame('img', $svg->getAttribute('role'));
        self::assertSame($title->getAttribute('id'), $svg->getAttribute('aria-labelledby'));
        self::assertSame('Gesetzliche Gewährleistung, mindestens zwei Jahre', $title->textContent);
        self::assertSame('de', $svg->getAttribute('lang'));
        self::assertSame($this->createAssetLocator()->getLgnEuUrlByLanguage('de'), $link->getAttribute('href'));
        self::assertSame('_blank', $link->getAttribute('target'));
        self::assertSame('noreferrer noopener', $link->getAttribute('rel'));
        self::assertSame('EU-Information zur gesetzlichen Gewährleistung', $link->textContent);
    }

    public function testUnitTemplateEscapesTextsAndValuesButKeepsThePreparedSvg(): void
    {
        $renderer = new LegalGuaranteeTemplateRenderer();

        $svgUnitMarkup = $renderer->render(LegalGuaranteeMarkupProvider::UNIT_TEMPLATE, [
            'type' => 'garan',
            'svgMarkup' => '<svg xmlns="http://www.w3.org/2000/svg"><text data-field="brand">A&amp;B</text></svg>',
            'image' => null,
            'euUrl' => 'https://example.org/?a=1&b=2',
            'euLinkText' => 'A&B "x" <y>',
        ]);
        $imageUnitMarkup = $renderer->render(LegalGuaranteeMarkupProvider::UNIT_TEMPLATE, [
            'type' => 'lgn',
            'svgMarkup' => '',
            'image' => ['src' => 'bundles/x.svg?a=1&b=2', 'alt' => 'Alt "text" <b>', 'width' => 595, 'height' => 842],
            'euUrl' => 'https://example.org/',
            'euLinkText' => 'Link',
        ]);
        $svgXPath = ContaoTestApplication::createXPath($svgUnitMarkup);
        $imageXPath = ContaoTestApplication::createXPath($imageUnitMarkup);
        $image = $this->findSingleElement($imageXPath, '//img');

        self::assertStringContainsString('>A&amp;B &quot;x&quot; &lt;y&gt;</a>', $svgUnitMarkup);
        self::assertStringContainsString('href="https://example.org/?a=1&amp;b=2"', $svgUnitMarkup);
        self::assertSame('A&B "x" <y>', $this->findSingleElement($svgXPath, '//a')->textContent);
        self::assertSame('A&B', $this->findSingleElement($svgXPath, '//svg//text')->textContent);
        self::assertSame('Alt "text" <b>', $image->getAttribute('alt'));
        self::assertSame('bundles/x.svg?a=1&b=2', $image->getAttribute('src'));
        self::assertCount(0, $imageXPath->query('//svg') ?: []);
    }

    public function testLgnButtonTemplateRendersTextTriggerAndLazyImageInPopover(): void
    {
        $product = $this->createProduct($this->createGaranMainData('36'));
        $xpath = ContaoTestApplication::createXPath($this->createProvider()->renderProductLgnNoticeButton($product));
        $button = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button');
        $trigger = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__trigger');
        $popover = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__popover');
        $close = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__close');
        $unit = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-unit', $popover);
        $image = $this->findSingleElement($xpath, './/img', $unit);

        self::assertSame('merconis-legal-guarantee-button merconis-legal-guarantee-button--lgn', $button->getAttribute('class'));
        self::assertSame('button', $trigger->tagName);
        self::assertSame('button', $trigger->getAttribute('type'));
        self::assertSame('Ihre gesetzlichen Gewährleistungsrechte', $trigger->textContent);
        self::assertNotContains('no-button', $this->getClassNames($trigger));
        self::assertContains('no-button', $this->getClassNames($close));
        self::assertFalse($trigger->hasAttribute('aria-label'));
        self::assertSame('merconis-legal-guarantee-popover-lgn-' . self::PRODUCT_ID, $trigger->getAttribute('popovertarget'));
        self::assertSame($trigger->getAttribute('popovertarget'), $popover->getAttribute('id'));
        self::assertTrue($popover->hasAttribute('popover'));
        self::assertSame($popover->getAttribute('id'), $close->getAttribute('popovertarget'));
        self::assertSame('hide', $close->getAttribute('popovertargetaction'));
        self::assertSame('Schließen', $close->getAttribute('aria-label'));
        self::assertSame('×', $close->textContent);
        self::assertSame('merconis-legal-guarantee-unit merconis-legal-guarantee-unit--lgn', $unit->getAttribute('class'));
        self::assertSame(
            'bundles/leadingsystemsmerconis/legal-guarantee/lgn/v1.0/legal-guarantee-notice-de.svg',
            $image->getAttribute('src')
        );
        self::assertSame('Gesetzliche Gewährleistung, mindestens zwei Jahre', $image->getAttribute('alt'));
        self::assertSame('595', $image->getAttribute('width'));
        self::assertSame('842', $image->getAttribute('height'));
        self::assertSame('lazy', $image->getAttribute('loading'));
        self::assertSame('async', $image->getAttribute('decoding'));
        self::assertCount(0, $xpath->query('//svg') ?: []);
        self::assertSame(
            'EU-Information zur gesetzlichen Gewährleistung',
            $this->findSingleByClass($xpath, 'merconis-legal-guarantee-unit__link', $unit)->textContent
        );
    }

    public function testGaranButtonTemplateRendersHiddenNestedLabelAndNamedTriggerAndFullLabelInPopover(): void
    {
        $product = $this->createProduct($this->createGaranMainData('30'));
        $xpath = ContaoTestApplication::createXPath($this->createProvider()->renderProductGaranLabelButton($product));
        $button = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button');
        $trigger = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__trigger');
        $nested = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__nested', $trigger);
        $nestedSvg = $this->findSingleElement($xpath, './svg', $nested);
        $popover = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__popover');
        $close = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__close', $popover);
        $unit = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-unit', $popover);
        $fullSvg = $this->findSingleElement($xpath, './/svg', $unit);

        self::assertSame('merconis-legal-guarantee-button merconis-legal-guarantee-button--garan', $button->getAttribute('class'));
        self::assertContains('no-button', $this->getClassNames($trigger));
        self::assertContains('no-button', $this->getClassNames($close));
        self::assertSame('span', $nested->tagName);
        self::assertSame('Herstellergarantie 2,5 Jahre', $trigger->getAttribute('aria-label'));
        self::assertSame('merconis-legal-guarantee-popover-garan-' . self::PRODUCT_ID, $trigger->getAttribute('popovertarget'));
        self::assertSame($trigger->getAttribute('popovertarget'), $popover->getAttribute('id'));
        self::assertSame('true', $nestedSvg->getAttribute('aria-hidden'));
        self::assertFalse($nestedSvg->hasAttribute('role'));
        self::assertCount(0, $xpath->query('.//title', $nestedSvg) ?: []);
        self::assertSame('2,5', $this->findSingleElement($xpath, './/*[@data-field="duration"]', $nestedSvg)->textContent);
        self::assertSame('merconis-legal-guarantee-unit merconis-legal-guarantee-unit--garan', $unit->getAttribute('class'));
        self::assertSame('img', $fullSvg->getAttribute('role'));
        self::assertSame(
            'Herstellergarantie von ACME für X100 mit 2,5 Jahren',
            $this->findSingleElement($xpath, './/title', $fullSvg)->textContent
        );
        self::assertSame('ACME', $this->findSingleElement($xpath, './/*[@data-field="brand"]', $fullSvg)->textContent);
        self::assertSame(
            'https://europa.eu/youreurope/commercial-guarantee-durability/',
            $this->findSingleElement($xpath, './/a', $unit)->getAttribute('href')
        );
        self::assertSame('EU-Information zur Herstellergarantie', $this->findSingleElement($xpath, './/a', $unit)->textContent);
    }

    public function testAllOutputsOfAProductPageUseDistinctPopoverIdsAndUniqueResolvableFragmentIds(): void
    {
        $product = $this->createProduct($this->createGaranMainData('36'));
        $provider = $this->createProvider();
        $xpath = ContaoTestApplication::createXPath(
            $provider->renderProductGaranLabelButton($product)
            . $provider->renderProductLgnNoticeButton($product)
            . $provider->renderProductGaranLabel($product)
            . $provider->renderProductLgnNotice($product)
        );
        $ids = ContaoTestApplication::collectIds($xpath);
        $triggers = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-button__trigger');

        self::assertSame(array_values(array_unique($ids)), $ids);
        self::assertCount(2, $triggers);
        self::assertSame(
            [
                'merconis-legal-guarantee-popover-garan-' . self::PRODUCT_ID,
                'merconis-legal-guarantee-popover-lgn-' . self::PRODUCT_ID,
            ],
            array_map(static fn (DOMElement $trigger): string => $trigger->getAttribute('popovertarget'), $triggers)
        );

        foreach ($triggers as $trigger) {
            $popover = $this->findSingleElement($xpath, sprintf('//*[@id="%s"]', $trigger->getAttribute('popovertarget')));

            self::assertTrue($popover->hasAttribute('popover'));
        }

        foreach ($xpath->query('//*[@aria-labelledby]') ?: [] as $labelledElement) {
            self::assertInstanceOf(DOMElement::class, $labelledElement);
            self::assertContains($labelledElement->getAttribute('aria-labelledby'), $ids);
        }

        self::assertSame(4, $xpath->query('//svg')?->length);
        self::assertSame(3, $xpath->query('//svg/title')?->length);
    }

    public function testLgnButtonKeepsItsPopoverIdWhetherOrNotTheGaranButtonIsRendered(): void
    {
        $productWithGaran = $this->createProduct($this->createGaranMainData('36'));
        $productWithoutGaran = $this->createProduct(['lgnMode' => 'inherit', 'enableGaran' => '']);
        $firstRequestProvider = $this->createProvider();
        $secondRequestProvider = $this->createProvider();

        $firstRendering = $firstRequestProvider->renderProductGaranLabelButton($productWithGaran)
            . $firstRequestProvider->renderProductLgnNoticeButton($productWithGaran);
        $secondRendering = $secondRequestProvider->renderProductGaranLabelButton($productWithoutGaran)
            . $secondRequestProvider->renderProductLgnNoticeButton($productWithoutGaran);

        $firstXPath = ContaoTestApplication::createXPath($firstRendering);
        $secondXPath = ContaoTestApplication::createXPath($secondRendering);
        $firstLgnButton = $this->findSingleByClass($firstXPath, 'merconis-legal-guarantee-button--lgn');
        $secondLgnButton = $this->findSingleByClass($secondXPath, 'merconis-legal-guarantee-button--lgn');

        self::assertCount(1, ContaoTestApplication::findByClass($firstXPath, 'merconis-legal-guarantee-button--garan'));
        self::assertCount(0, ContaoTestApplication::findByClass($secondXPath, 'merconis-legal-guarantee-button--garan'));
        self::assertSame(
            $this->findSingleByClass($firstXPath, 'merconis-legal-guarantee-button__popover', $firstLgnButton)->getAttribute('id'),
            $this->findSingleByClass($secondXPath, 'merconis-legal-guarantee-button__popover', $secondLgnButton)->getAttribute('id')
        );
        self::assertStringContainsString('id="merconis-legal-guarantee-popover-lgn-' . self::PRODUCT_ID . '"', $firstRendering);
        self::assertStringContainsString('id="merconis-legal-guarantee-popover-lgn-' . self::PRODUCT_ID . '"', $secondRendering);
    }

    public function testCheckoutLgnNoticeButtonUsesCheckoutPopoverId(): void
    {
        $this->setCartItems([$this->createProduct(['lgnMode' => 'inherit', 'enableGaran' => ''])]);

        $xpath = ContaoTestApplication::createXPath($this->createProvider()->checkoutLgnNoticeButton());

        self::assertSame(
            'merconis-legal-guarantee-popover-lgn-checkout',
            $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button__popover')->getAttribute('id')
        );
        self::assertSame(
            'merconis-legal-guarantee-button merconis-legal-guarantee-button--lgn',
            $this->findSingleByClass($xpath, 'merconis-legal-guarantee-button')->getAttribute('class')
        );
    }

    public function testAssignmentTemplateRendersOrderedItemsWithPositionPartsAndTextPerPosition(): void
    {
        $this->setCartItems([
            $this->createProduct($this->createGaranMainData('36'), 10, 'Stehlampe', $this->createVariant(101, 'Alternative 1', 'WMBE140040011-01')),
            $this->createProduct($this->createGaranMainData('36'), 20, 'Decke', null, 'ART-200'),
            $this->createProduct($this->createGaranMainData('36'), 10, 'Stehlampe', $this->createVariant(103, 'Alternative 3', 'WMBE140040011-03')),
            $this->createProduct($this->createGaranMainData('48'), 30, 'Kissen'),
            $this->createProduct($this->createGaranMainData('48'), 40, 'Sessel', $this->createVariant(401, 'Grau')),
        ]);

        $xpath = ContaoTestApplication::createXPath($this->createProvider()->checkoutGaranBlock());
        $assignment = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-assignment');
        $children = $this->getElementChildren($assignment);
        $items = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__item');

        self::assertSame('merconis-legal-guarantee-assignment', $assignment->getAttribute('class'));
        self::assertSame('h3', $children[0]->tagName);
        self::assertSame('merconis-legal-guarantee-assignment__headline', $children[0]->getAttribute('class'));
        self::assertSame('Herstellergarantie für folgende Artikel:', $children[0]->textContent);
        self::assertSame('merconis-legal-guarantee-assignment__items', $children[1]->getAttribute('class'));
        self::assertSame('ul', $children[1]->tagName);
        self::assertCount(4, $items);
        self::assertSame(
            [
                ['Stehlampe (Alternative 1) - WMBE140040011-01', 'Stehlampe (Alternative 3) - WMBE140040011-03'],
                ['Decke - ART-200'],
                ['Kissen'],
                ['Sessel (Grau)'],
            ],
            array_map(fn (DOMElement $item): array => $this->readPositionTexts($xpath, $item), $items)
        );

        $firstPosition = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__position', $items[0])[0];

        self::assertSame('Stehlampe', $this->findSingleByClass($xpath, 'merconis-legal-guarantee-assignment__position-name', $firstPosition)->textContent);
        self::assertSame('(Alternative 1)', $this->findSingleByClass($xpath, 'merconis-legal-guarantee-assignment__position-variant', $firstPosition)->textContent);
        self::assertSame('-', $this->findSingleByClass($xpath, 'merconis-legal-guarantee-assignment__position-separator', $firstPosition)->textContent);
        self::assertSame('WMBE140040011-01', $this->findSingleByClass($xpath, 'merconis-legal-guarantee-assignment__position-article-number', $firstPosition)->textContent);

        $positionWithoutVariantAndArticleNumber = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__position', $items[2])[0];

        foreach (['variant', 'separator', 'article-number'] as $missingPart) {
            self::assertCount(0, ContaoTestApplication::findByClass(
                $xpath,
                'merconis-legal-guarantee-assignment__position-' . $missingPart,
                $positionWithoutVariantAndArticleNumber
            ));
        }

        $positionWithoutArticleNumber = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__position', $items[3])[0];

        self::assertCount(1, ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__position-variant', $positionWithoutArticleNumber));
        self::assertCount(0, ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__position-separator', $positionWithoutArticleNumber));

        foreach ($items as $item) {
            $itemChildren = $this->getElementChildren($item);
            $label = $itemChildren[1];
            $unit = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-unit', $label);

            self::assertSame('merconis-legal-guarantee-assignment__positions', $itemChildren[0]->getAttribute('class'));
            self::assertSame('merconis-legal-guarantee-assignment__label', $label->getAttribute('class'));
            self::assertSame('merconis-legal-guarantee-unit merconis-legal-guarantee-unit--garan', $unit->getAttribute('class'));
            self::assertSame(1, $xpath->query('.//svg', $unit)?->length);
            self::assertSame(
                'EU-Information zur Herstellergarantie',
                $this->findSingleByClass($xpath, 'merconis-legal-guarantee-unit__link', $unit)->textContent
            );
        }

        $ids = ContaoTestApplication::collectIds($xpath);

        self::assertSame(array_values(array_unique($ids)), $ids);
    }

    public function testStandardOrderReviewSectionRendersAssignmentBeforeLgnButtonAndComputesEachVariableOnce(): void
    {
        $this->setCartItems([
            $this->createProduct($this->createGaranMainData('36'), 10, 'Kissen', null, 'ART-100'),
        ]);
        $recordingRenderer = new RecordingLegalGuaranteeTemplateRenderer(new LegalGuaranteeTemplateRenderer());
        $this->writeOrderReviewExcerptTemplate();

        $template = new FrontendTemplate(self::ORDER_REVIEW_EXCERPT_TEMPLATE);

        foreach ($this->createProvider($recordingRenderer)->createCheckoutTemplateVariables() as $variableName => $closureOutput) {
            $template->{$variableName} = $closureOutput;
        }

        $xpath = ContaoTestApplication::createXPath($template->parse());
        $wrapper = $this->findSingleByClass($xpath, 'merconis-legal-guarantee-checkout');
        $children = $this->getElementChildren($wrapper);

        self::assertSame('merconis-legal-guarantee-checkout', $wrapper->getAttribute('class'));
        self::assertCount(2, $children);
        self::assertSame('merconis-legal-guarantee-assignment', $children[0]->getAttribute('class'));
        self::assertSame('merconis-legal-guarantee-button merconis-legal-guarantee-button--lgn', $children[1]->getAttribute('class'));
        self::assertCount(1, $recordingRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::ASSIGNMENT_TEMPLATE));
        self::assertCount(1, $recordingRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::BUTTON_TEMPLATE));
        self::assertSame(
            [],
            array_filter(
                $recordingRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::UNIT_TEMPLATE),
                static fn (array $unitData): bool => 'lgn' === $unitData['type'] && '' !== $unitData['svgMarkup']
            ),
            'The direct LGN output checkoutLgnNotice is not read by the standard template and must not be computed.'
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: int}>
     */
    public static function overridableTemplateProvider(): iterable
    {
        yield 'unit' => [LegalGuaranteeMarkupProvider::UNIT_TEMPLATE, 4];
        yield 'button' => [LegalGuaranteeMarkupProvider::BUTTON_TEMPLATE, 2];
        yield 'assignment' => [LegalGuaranteeMarkupProvider::ASSIGNMENT_TEMPLATE, 1];
    }

    #[DataProvider('overridableTemplateProvider')]
    public function testSameNamedTemplateInHostTemplatesFolderReplacesTheBuildingBlock(
        string $templateName,
        int $expectedOverrideCount
    ): void {
        $defaultClass = [
            LegalGuaranteeMarkupProvider::UNIT_TEMPLATE => 'merconis-legal-guarantee-unit',
            LegalGuaranteeMarkupProvider::BUTTON_TEMPLATE => 'merconis-legal-guarantee-button',
            LegalGuaranteeMarkupProvider::ASSIGNMENT_TEMPLATE => 'merconis-legal-guarantee-assignment',
        ][$templateName];
        $product = $this->createProduct($this->createGaranMainData('36'));
        $this->setCartItems([$this->createProduct($this->createGaranMainData('36'), 10, 'Kissen', null, 'ART-100')]);
        $overridePath = ContaoTestApplication::getProjectDir() . '/templates/' . $templateName . '.html5';

        self::assertFileDoesNotExist($overridePath);

        $this->temporaryTemplatePaths[] = $overridePath;
        file_put_contents(
            $overridePath,
            '<div class="' . self::OVERRIDE_MARKER_CLASS . '"></div>'
        );

        $overriddenXPath = ContaoTestApplication::createXPath($this->renderAllOutputs($product));

        unlink($overridePath);

        $defaultXPath = ContaoTestApplication::createXPath($this->renderAllOutputs($product));

        self::assertCount($expectedOverrideCount, ContaoTestApplication::findByClass($overriddenXPath, self::OVERRIDE_MARKER_CLASS));
        self::assertCount(0, ContaoTestApplication::findByClass($overriddenXPath, $defaultClass));
        self::assertCount(0, ContaoTestApplication::findByClass($defaultXPath, self::OVERRIDE_MARKER_CLASS));
        self::assertNotCount(0, ContaoTestApplication::findByClass($defaultXPath, $defaultClass));
    }

    /**
     * Gibt den Insert-Tag, den GARAN-Button der Produktdetailseite, den Zuordnungsblock und
     * den LGN-Button der letzten Bestellseite aus: vier Einheiten, zwei Buttons, ein
     * Zuordnungsblock.
     */
    private function renderAllOutputs(ls_shop_product $product): string
    {
        $provider = $this->createProvider();

        return $provider->renderCurrentLanguageLgnNotice()
            . $provider->renderProductGaranLabelButton($product)
            . $provider->checkoutGaranBlock()
            . $provider->checkoutLgnNoticeButton();
    }

    /**
     * Legt den Ausschnitt des Standard-Templates der letzten Bestellseite mit der Label-Ausgabe
     * als eigenes Template ab. Das ganze Template setzt einen vollständigen Checkout voraus.
     */
    private function writeOrderReviewExcerptTemplate(): void
    {
        $templateSource = (string) file_get_contents(
            __DIR__ . '/../../src/Resources/contao/templates/template_orderReview_default.html5'
        );
        $excerptStart = strpos($templateSource, 'if ($this->checkoutGaranBlock || $this->checkoutLgnNoticeButton) {');
        $excerptEnd = strpos($templateSource, 'if (\Merconis\Core\ls_shop_generalHelper::check_finishingOrderIsAllowed())');

        self::assertIsInt($excerptStart);
        self::assertIsInt($excerptEnd);

        $excerptPath = ContaoTestApplication::getProjectDir() . '/templates/' . self::ORDER_REVIEW_EXCERPT_TEMPLATE . '.html5';
        $this->temporaryTemplatePaths[] = $excerptPath;
        file_put_contents($excerptPath, '<?php ' . substr($templateSource, $excerptStart, $excerptEnd - $excerptStart) . ' ?>');
    }

    /**
     * @return list<string>
     */
    private function readPositionTexts(DOMXPath $xpath, DOMElement $item): array
    {
        return array_map(
            static fn (DOMElement $position): string => $position->textContent,
            ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__position', $item)
        );
    }

    private function findSingleByClass(DOMXPath $xpath, string $className, ?DOMElement $context = null): DOMElement
    {
        $elements = ContaoTestApplication::findByClass($xpath, $className, $context);

        self::assertCount(1, $elements, sprintf('Expected exactly one element with class "%s".', $className));

        return $elements[0];
    }

    private function findSingleElement(DOMXPath $xpath, string $expression, ?DOMElement $context = null): DOMElement
    {
        $nodes = $xpath->query($expression, $context);

        self::assertNotFalse($nodes);
        self::assertSame(1, $nodes->length, sprintf('Expected exactly one match for "%s".', $expression));

        $element = $nodes->item(0);
        self::assertInstanceOf(DOMElement::class, $element);

        return $element;
    }

    /**
     * @return list<string>
     */
    private function getClassNames(DOMElement $element): array
    {
        return preg_split('/\s+/', trim($element->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @return list<DOMElement>
     */
    private function getElementChildren(DOMElement $element): array
    {
        $children = [];

        foreach ($element->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $children[] = $childNode;
            }
        }

        return $children;
    }

    private function createProvider(?LegalGuaranteeTemplateRendererInterface $templateRenderer = null): LegalGuaranteeMarkupProvider
    {
        $assetLocator = $this->createAssetLocator();

        return new LegalGuaranteeMarkupProvider(
            $assetLocator,
            new ProductGuaranteeDisplayResolver(),
            new GaranLabelGrouping(),
            new LegalGuaranteeMarkupBuilder(),
            $templateRenderer ?? new LegalGuaranteeTemplateRenderer(),
            new NullLogger(),
            new class implements MemberGroupLgnModeProviderInterface {
                public function getCurrentMemberGroupLgnMode(): string
                {
                    return 'on';
                }
            },
            [new GaranLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
            [new GaranNestedLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
        );
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(dirname(__DIR__, 2), new OfficialGuaranteeLanguageResolver());
    }

    /**
     * @return array<string, string>
     */
    private function createGaranMainData(string $durationMonths): array
    {
        return [
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => $durationMonths,
        ];
    }

    /**
     * @param array<string, mixed> $mainData
     */
    private function createProduct(
        array $mainData,
        int $productId = self::PRODUCT_ID,
        string $title = 'Kissen',
        ?ls_shop_variant $selectedVariant = null,
        string $code = ''
    ): ls_shop_product {
        $productReflection = new ReflectionClass(ls_shop_product::class);
        /** @var ls_shop_product $product */
        $product = $productReflection->newInstanceWithoutConstructor();
        $product->ls_ID = $productId;
        $product->mainData = $mainData + [
            'lsShopProductCode' => $code,
            'title' => $title,
        ];
        $product->currentLanguageData = ['title' => $title];

        if (null !== $selectedVariant) {
            $product->ls_currentVariantID = $selectedVariant->ls_ID;
            $product->ls_variants = [$selectedVariant->ls_ID => $selectedVariant];
        }

        return $product;
    }

    private function createVariant(int $variantId, string $title, string $code = ''): ls_shop_variant
    {
        $variantReflection = new ReflectionClass(ls_shop_variant::class);
        /** @var ls_shop_variant $variant */
        $variant = $variantReflection->newInstanceWithoutConstructor();
        $variant->ls_ID = $variantId;
        $variant->mainData = [
            'lsShopVariantCode' => $code,
            'title' => $title,
        ];
        $variant->currentLanguageData = ['title' => $title];

        return $variant;
    }

    /**
     * @param list<ls_shop_product> $products
     */
    private function setCartItems(array $products): void
    {
        $cartReflection = new ReflectionClass(ls_shop_cartX::class);
        /** @var ls_shop_cartX $cart */
        $cart = $cartReflection->newInstanceWithoutConstructor();

        $itemsExtendedProperty = new ReflectionProperty(ls_shop_cartX::class, 'itemsExtended');
        $itemsExtendedProperty->setValue(
            $cart,
            array_map(
                static fn (ls_shop_product $product): array => ['objProduct' => $product],
                $products
            )
        );

        $instanceProperty = new ReflectionProperty(ls_shop_cartX::class, 'objInstance');
        $instanceProperty->setValue(null, $cart);
    }
}
