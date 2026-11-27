<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

require_once __DIR__ . '/../Support/RecordingLegalGuaranteeTemplateRenderer.php';

use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranNestedLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranNestedLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeProviderInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\Tests\Support\RecordingLegalGuaranteeTemplateRenderer;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

final class LegalGuaranteeMarkupProviderTest extends TestCase
{
    private RecordingLegalGuaranteeTemplateRenderer $templateRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'lgnTitle' => 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
            'lgnEuLinkText' => 'EU-Information zur gesetzlichen Gewährleistung',
            'garanBlockHeadline' => 'Herstellergarantie für folgende Artikel:',
            'garanEuLinkText' => 'EU-Information zur Herstellergarantie',
            'garanTitlePattern' => 'Herstellergarantie von %s für %s mit %s Jahren',
            'lgnButtonText' => 'Ihre gesetzlichen Gewährleistungsrechte',
            'garanButtonLabel' => 'Herstellergarantie %s Jahre',
            'popoverCloseLabel' => 'Schließen',
        ];
        $GLOBALS['TL_CSS'] = [];
        $this->templateRenderer = new RecordingLegalGuaranteeTemplateRenderer();
    }

    protected function tearDown(): void
    {
        $instanceProperty = new ReflectionProperty(ls_shop_cartX::class, 'objInstance');
        $instanceProperty->setValue(null, null);

        parent::tearDown();
    }

    public function testRenderCurrentLanguageLgnNoticeRendersLgnUnitWithResolvedLanguageAndTitle(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];

        $markup = $this->createProvider()->renderCurrentLanguageLgnNotice();
        $unitData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertSame(LegalGuaranteeMarkupProvider::UNIT_TEMPLATE, $this->templateRenderer->renderedTemplates[0]['templateName']);
        self::assertSame('lgn', $unitData['type']);
        self::assertNull($unitData['image']);
        self::assertStringContainsString('lang="de"', $unitData['svgMarkup']);
        self::assertStringContainsString('Gesetzliche Gewährleistung, mindestens zwei Jahre', $unitData['svgMarkup']);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-lgn-title"', $unitData['svgMarkup']);
        self::assertStringContainsString('https://europa.eu/youreurope/garantien', $unitData['euUrl']);
        self::assertSame('EU-Information zur gesetzlichen Gewährleistung', $unitData['euLinkText']);
        self::assertContains(
            'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee-fonts.css',
            $GLOBALS['TL_CSS']
        );
    }

    public function testRenderCurrentLanguageLgnNoticeFallsBackToEnglishForUnknownLocale(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'zz_ZZ'];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee']['lgnTitle'] = 'Legal guarantee, at least two years';

        $markup = $this->createProvider()->renderCurrentLanguageLgnNotice();
        $unitData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertStringContainsString('lang="en"', $unitData['svgMarkup']);
        self::assertStringContainsString('Legal guarantee, at least two years', $unitData['svgMarkup']);
        self::assertStringContainsString('https://europa.eu/youreurope/guarantees', $unitData['euUrl']);
    }

    public function testBuildGaranTitleFormatsStoredDurationForDisplay(): void
    {
        $method = new ReflectionMethod(LegalGuaranteeMarkupProvider::class, 'buildGaranTitle');
        $method->setAccessible(true);

        $formattedHalfYear = $method->invoke($this->createProvider(), 'ACME', 'MX-42', '30');
        $formattedWholeYears = $method->invoke($this->createProvider(), 'ACME', 'MX-42', '360');

        self::assertSame('Herstellergarantie von ACME für MX-42 mit 2,5 Jahren', $formattedHalfYear);
        self::assertSame('Herstellergarantie von ACME für MX-42 mit 30 Jahren', $formattedWholeYears);
    }

    public function testBuildGaranTitleAlwaysUsesCommaAsDecimalSeparator(): void
    {
        $method = new ReflectionMethod(LegalGuaranteeMarkupProvider::class, 'buildGaranTitle');
        $method->setAccessible(true);

        $formattedHalfYear = $method->invoke($this->createProvider(), 'ACME', 'MX-42', '30');

        self::assertSame('Herstellergarantie von ACME für MX-42 mit 2,5 Jahren', $formattedHalfYear);
    }

    public function testCheckoutLgnOutputsReturnEmptyStringWithoutEligibleCartItems(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'lgnMode' => 'never',
                'enableGaran' => '',
            ]),
        ]);
        $provider = $this->createProvider();

        self::assertSame('', $provider->checkoutLgnNotice());
        self::assertSame('', $provider->checkoutLgnNoticeButton());
        self::assertSame([], $this->templateRenderer->renderedTemplates);
        self::assertSame([], $GLOBALS['TL_CSS']);
    }

    public function testCheckoutLgnNoticeRendersLgnUnitWithCheckoutFragmentScope(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct(['lgnMode' => 'inherit', 'enableGaran' => '']),
            $this->createProduct(['lgnMode' => 'inherit', 'enableGaran' => '']),
        ]);

        $markup = $this->createProvider()->checkoutLgnNotice();
        $unitData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertCount(1, $this->templateRenderer->renderedTemplates);
        self::assertSame('lgn', $unitData['type']);
        self::assertStringContainsString('lang="de"', $unitData['svgMarkup']);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-lgn-checkout-title"', $unitData['svgMarkup']);
        self::assertSame('EU-Information zur gesetzlichen Gewährleistung', $unitData['euLinkText']);
    }

    public function testCheckoutLgnNoticeButtonRendersLgnButtonWithCheckoutPopoverId(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([$this->createProduct(['lgnMode' => 'inherit', 'enableGaran' => ''])]);

        $markup = $this->createProvider()->checkoutLgnNoticeButton();
        $buttonData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertSame('lgn', $buttonData['type']);
        self::assertSame('merconis-legal-guarantee-popover-lgn-checkout', $buttonData['popoverId']);
        self::assertSame('Ihre gesetzlichen Gewährleistungsrechte', $buttonData['triggerText']);
    }

    public function testCheckoutGaranBlockGroupsPositionsAndPassesTheirPartsToTheAssignmentTemplate(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct(
                $this->createCompleteGaranMainData('inherit'),
                10,
                'Kissen',
                $this->createVariant(101, 'Rot', 'ART-101')
            ),
            $this->createProduct(
                $this->createCompleteGaranMainData('inherit'),
                10,
                'Kissen',
                $this->createVariant(102, 'Blau', 'ART-102')
            ),
            $this->createProduct($this->createCompleteGaranMainData('inherit'), 20, 'Decke', null, 'ART-200'),
        ]);

        $markup = $this->createProvider()->checkoutGaranBlock();
        $assignmentData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertSame('Herstellergarantie für folgende Artikel:', $assignmentData['headline']);
        self::assertCount(2, $assignmentData['items']);
        self::assertSame([
            ['productName' => 'Kissen', 'variantName' => 'Rot', 'articleNumber' => 'ART-101'],
            ['productName' => 'Kissen', 'variantName' => 'Blau', 'articleNumber' => 'ART-102'],
        ], $assignmentData['items'][0]['positions']);
        self::assertSame([
            ['productName' => 'Decke', 'variantName' => '', 'articleNumber' => 'ART-200'],
        ], $assignmentData['items'][1]['positions']);

        $firstUnitData = $this->templateRenderer->getTemplateDataForOutput($assignmentData['items'][0]['unitMarkup']);
        $secondUnitData = $this->templateRenderer->getTemplateDataForOutput($assignmentData['items'][1]['unitMarkup']);

        self::assertSame('garan', $firstUnitData['type']);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-checkout-title"', $firstUnitData['svgMarkup']);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-checkout-2-title"', $secondUnitData['svgMarkup']);
        self::assertSame('https://europa.eu/youreurope/commercial-guarantee-durability/', $firstUnitData['euUrl']);
        self::assertSame('EU-Information zur Herstellergarantie', $firstUnitData['euLinkText']);
    }

    public function testRenderProductGaranLabelUsesRendererMatchingCurrentVersion(): void
    {
        $product = $this->createProduct([
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'MX-42',
            'guaranteeDurationMonths' => '30',
        ]);

        $markup = $this->createProvider(
            dirname(__DIR__, 2),
            [
                $this->createGaranLabelRendererStub('v1.1', 'wrong-version'),
                new class implements GaranLabelRendererInterface {
                    public function getVersion(): string
                    {
                        return OfficialGuaranteeAssetLocator::GARAN_VERSION;
                    }

                    public function render(
                        string $brand,
                        string $modelIdentifier,
                        string $durationText
                    ): string {
                        return sprintf(
                            '<svg xmlns="http://www.w3.org/2000/svg"><text>%s|%s|%s</text></svg>',
                            $brand,
                            $modelIdentifier,
                            $durationText
                        );
                    }
                },
            ]
        )->renderProductGaranLabel($product);
        $unitData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertSame('garan', $unitData['type']);
        self::assertStringContainsString('ACME|MX-42|30', $unitData['svgMarkup']);
        self::assertStringNotContainsString('wrong-version', $unitData['svgMarkup']);
    }

    public function testCheckoutGaranBlockPassesEmptyArticleNumberWhenCodeIsMissing(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct($this->createCompleteGaranMainData('inherit'), 10, 'Kissen'),
        ]);

        $markup = $this->createProvider()->checkoutGaranBlock();
        $assignmentData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertSame(
            [['productName' => 'Kissen', 'variantName' => '', 'articleNumber' => '']],
            $assignmentData['items'][0]['positions']
        );
    }

    public function testCheckoutGaranBlockLogsAndSkipsFailedGroupWhileKeepingRemainingGroups(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeBrand' => 'FAIL',
                'guaranteeModelIdentifier' => 'X100',
                'guaranteeDurationMonths' => '36',
            ], 10, 'Defekt', null, 'ART-500'),
            $this->createProduct([
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeBrand' => 'OK',
                'guaranteeModelIdentifier' => 'X100',
                'guaranteeDurationMonths' => '36',
            ], 20, 'Decke', null, 'ART-200'),
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(self::logicalAnd(
                self::stringContains('ART-500'),
                self::stringContains('broken checkout label')
            ));

        $markup = $this->createProvider(null, [$this->createFailingGaranLabelRendererForBrand('FAIL')], $logger)
            ->checkoutGaranBlock();
        $assignmentData = $this->templateRenderer->getTemplateDataForOutput($markup);

        self::assertCount(1, $assignmentData['items']);
        self::assertSame(
            [['productName' => 'Decke', 'variantName' => '', 'articleNumber' => 'ART-200']],
            $assignmentData['items'][0]['positions']
        );
    }

    public function testCheckoutGaranBlockReturnsEmptyStringWhenAllGroupsFail(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeBrand' => 'FAIL',
                'guaranteeModelIdentifier' => 'X100',
                'guaranteeDurationMonths' => '36',
            ], 10, 'Defekt', null, 'ART-500'),
        ]);

        $markup = $this->createProvider(null, [$this->createFailingGaranLabelRendererForBrand('FAIL')])
            ->checkoutGaranBlock();

        self::assertSame('', $markup);
        self::assertSame([], $this->templateRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::ASSIGNMENT_TEMPLATE));
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: bool}>
     */
    public static function memberGroupAndProductLgnModeProvider(): iterable
    {
        yield 'group on, product inherit' => ['on', 'inherit', true];
        yield 'group on, product never' => ['on', 'never', false];
        yield 'group off, product inherit' => ['off', 'inherit', false];
        yield 'group off, product always' => ['off', 'always', true];
        yield 'group blocked, product always' => ['blocked', 'always', false];
        yield 'group empty, product always' => ['', 'always', false];
    }

    #[DataProvider('memberGroupAndProductLgnModeProvider')]
    public function testProductDetailOutputsFollowMemberGroupLgnModeFromProvider(
        string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedOutput
    ): void {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct($this->createCompleteGaranMainData($productLgnMode));
        $provider = $this->createProvider(memberGroupLgnMode: $memberGroupLgnMode);

        self::assertSame($expectedOutput, '' !== $provider->renderProductLgnNotice($product));
        self::assertSame($expectedOutput, '' !== $provider->renderProductLgnNoticeButton($product));
        self::assertSame($expectedOutput, '' !== $provider->renderProductGaranLabel($product));
        self::assertSame($expectedOutput, '' !== $provider->renderProductGaranLabelButton($product));
    }

    #[DataProvider('memberGroupAndProductLgnModeProvider')]
    public function testLastOrderPageOutputsFollowMemberGroupLgnModeFromProvider(
        string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedOutput
    ): void {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct($this->createCompleteGaranMainData($productLgnMode), 10, 'Kissen', null, 'ART-100'),
        ]);
        $provider = $this->createProvider(memberGroupLgnMode: $memberGroupLgnMode);

        self::assertSame($expectedOutput, '' !== $provider->checkoutLgnNotice());
        self::assertSame($expectedOutput, '' !== $provider->checkoutLgnNoticeButton());
        self::assertSame($expectedOutput, '' !== $provider->checkoutGaranBlock());
    }

    public function testProductDetailOutputAsksProviderForEveryRendering(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $memberGroupLgnModeProvider = new class implements MemberGroupLgnModeProviderInterface {
            /** @var list<string> */
            public array $returnValues = ['off', 'on'];

            public function getCurrentMemberGroupLgnMode(): string
            {
                return array_shift($this->returnValues) ?? '';
            }
        };
        $product = $this->createProduct($this->createCompleteGaranMainData('inherit'));
        $provider = $this->createProviderWithMemberGroupLgnModeProvider($memberGroupLgnModeProvider);

        self::assertSame('', $provider->renderProductLgnNotice($product));
        self::assertNotSame('', $provider->renderProductLgnNotice($product));
    }

    public function testRenderProductLgnNoticeButtonRendersTextTriggerAndLazyImageUnit(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct($this->createCompleteGaranMainData('inherit'), 6560);

        $markup = $this->createProvider()->renderProductLgnNoticeButton($product);
        $buttonData = $this->templateRenderer->getTemplateDataForOutput($markup);
        $unitData = $this->templateRenderer->getTemplateDataForOutput($buttonData['unitMarkup']);

        self::assertSame(LegalGuaranteeMarkupProvider::BUTTON_TEMPLATE, $this->templateRenderer->renderedTemplates[1]['templateName']);
        self::assertSame('lgn', $buttonData['type']);
        self::assertSame('merconis-legal-guarantee-popover-lgn-6560', $buttonData['popoverId']);
        self::assertSame('Ihre gesetzlichen Gewährleistungsrechte', $buttonData['triggerText']);
        self::assertSame('', $buttonData['triggerLabel']);
        self::assertSame('', $buttonData['nestedSvgMarkup']);
        self::assertSame('Schließen', $buttonData['closeLabel']);
        self::assertSame('lgn', $unitData['type']);
        self::assertSame('', $unitData['svgMarkup']);
        self::assertSame([
            'src' => 'bundles/leadingsystemsmerconis/legal-guarantee/lgn/v1.0/legal-guarantee-notice-de.svg',
            'alt' => 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
            'width' => 595,
            'height' => 842,
        ], $unitData['image']);
        self::assertStringContainsString('https://europa.eu/youreurope/garantien', $unitData['euUrl']);
        self::assertSame([], $GLOBALS['TL_CSS']);
    }

    public function testRenderProductGaranLabelButtonRendersNestedLabelTriggerAndFullLabelUnit(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct([
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => '30',
        ], 6560);

        $markup = $this->createProvider()->renderProductGaranLabelButton($product);
        $buttonData = $this->templateRenderer->getTemplateDataForOutput($markup);
        $unitData = $this->templateRenderer->getTemplateDataForOutput($buttonData['unitMarkup']);

        self::assertSame('garan', $buttonData['type']);
        self::assertSame('merconis-legal-guarantee-popover-garan-6560', $buttonData['popoverId']);
        self::assertSame('Herstellergarantie 2,5 Jahre', $buttonData['triggerLabel']);
        self::assertSame('', $buttonData['triggerText']);
        self::assertSame('Schließen', $buttonData['closeLabel']);
        self::assertStringContainsString('viewBox="0 0 368.5 56.69"', $buttonData['nestedSvgMarkup']);
        self::assertStringContainsString('aria-hidden="true"', $buttonData['nestedSvgMarkup']);
        self::assertStringNotContainsString('<title', $buttonData['nestedSvgMarkup']);
        self::assertStringContainsString('>2,5</tspan>', $buttonData['nestedSvgMarkup']);
        self::assertSame('garan', $unitData['type']);
        self::assertStringContainsString('role="img"', $unitData['svgMarkup']);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-6560-title"', $unitData['svgMarkup']);
        self::assertStringContainsString('Herstellergarantie von ACME für X100 mit 2,5 Jahren', $unitData['svgMarkup']);
        self::assertContains(
            'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee-fonts.css',
            $GLOBALS['TL_CSS']
        );
    }

    public function testRepeatedProductOutputOfTheSameTypeAddsCounterToFragmentIds(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct($this->createCompleteGaranMainData('inherit'), 7);
        $provider = $this->createProvider();

        $directOutputData = $this->templateRenderer->getTemplateDataForOutput(
            $provider->renderProductGaranLabel($product)
        );
        $buttonData = $this->templateRenderer->getTemplateDataForOutput(
            $provider->renderProductGaranLabelButton($product)
        );
        $popoverUnitData = $this->templateRenderer->getTemplateDataForOutput($buttonData['unitMarkup']);

        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-7-title"', $directOutputData['svgMarkup']);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-7-2-title"', $popoverUnitData['svgMarkup']);
        self::assertSame('merconis-legal-guarantee-popover-garan-7', $buttonData['popoverId']);
    }

    public function testRenderProductGaranLabelReturnsEmptyStringAndLogsArticleNumberOfSelectedVariantWhenRendererFails(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct(
            $this->createCompleteGaranMainData('inherit'),
            10,
            'Kissen',
            $this->createVariant(101, 'Rot', 'ART-101'),
            'ART-100'
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(self::logicalAnd(
                self::stringContains('GARAN label rendering failed on the product detail page'),
                self::stringContains('ART-101'),
                self::stringContains('broken product label')
            ));

        $markup = $this->createProvider(null, [$this->createFailingGaranLabelRenderer('broken product label')], $logger)
            ->renderProductGaranLabel($product);

        self::assertSame('', $markup);
        self::assertSame([], $this->templateRenderer->renderedTemplates);
    }

    public function testRenderProductGaranLabelButtonReturnsEmptyStringAndLogsArticleNumberWhenNestedRendererFails(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct($this->createCompleteGaranMainData('inherit'), 10, 'Kissen', null, 'ART-100');
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(self::logicalAnd(
                self::stringContains('GARAN label button rendering failed on the product detail page'),
                self::stringContains('ART-100'),
                self::stringContains('broken nested label')
            ));

        $markup = $this->createProvider(
            null,
            null,
            $logger,
            garanNestedLabelRenderers: [
                new class implements GaranNestedLabelRendererInterface {
                    public function getVersion(): string
                    {
                        return OfficialGuaranteeAssetLocator::GARAN_VERSION;
                    }

                    public function render(string $durationMonths): string
                    {
                        throw new RuntimeException('broken nested label');
                    }
                },
            ]
        )->renderProductGaranLabelButton($product);

        self::assertSame('', $markup);
        self::assertSame([], $this->templateRenderer->renderedTemplates);
    }

    public function testRenderProductGaranLabelButtonReturnsEmptyStringAndLogsArticleNumberWhenFullLabelRendererFails(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct($this->createCompleteGaranMainData('inherit'), 10, 'Kissen', null, 'ART-100');
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(self::logicalAnd(
                self::stringContains('GARAN label button rendering failed on the product detail page'),
                self::stringContains('ART-100'),
                self::stringContains('broken full label')
            ));

        $markup = $this->createProvider(null, [$this->createFailingGaranLabelRenderer('broken full label')], $logger)
            ->renderProductGaranLabelButton($product);

        self::assertSame('', $markup);
        self::assertSame([], $this->templateRenderer->renderedTemplates);
    }

    public function testCheckoutTemplateVariablesDoNotComputeOutputsThatAreNotRead(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct($this->createCompleteGaranMainData('inherit'), 10, 'Kissen', null, 'ART-100'),
        ]);

        $templateVariables = $this->createProvider()->createCheckoutTemplateVariables();

        self::assertSame(
            ['checkoutLgnNotice', 'checkoutLgnNoticeButton', 'checkoutGaranBlock'],
            array_keys($templateVariables)
        );
        self::assertSame([], $this->templateRenderer->renderedTemplates);

        $templateVariables['checkoutGaranBlock']();

        self::assertSame([], $this->templateRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::BUTTON_TEMPLATE));
        self::assertCount(1, $this->templateRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::ASSIGNMENT_TEMPLATE));
    }

    public function testCheckoutTemplateVariablesComputeEachOutputOnlyOnce(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct($this->createCompleteGaranMainData('inherit'), 10, 'Kissen', null, 'ART-100'),
        ]);
        $templateVariables = $this->createProvider()->createCheckoutTemplateVariables();

        $firstReadings = [];
        $secondReadings = [];

        foreach ($templateVariables as $variableName => $closureOutput) {
            $firstReadings[$variableName] = $closureOutput();
        }

        $renderCountAfterFirstReadings = count($this->templateRenderer->renderedTemplates);

        foreach ($templateVariables as $variableName => $closureOutput) {
            $secondReadings[$variableName] = $closureOutput();
        }

        self::assertSame($firstReadings, $secondReadings);
        self::assertSame($renderCountAfterFirstReadings, count($this->templateRenderer->renderedTemplates));
        self::assertCount(1, $this->templateRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::ASSIGNMENT_TEMPLATE));
        self::assertCount(1, $this->templateRenderer->getTemplateDataFor(LegalGuaranteeMarkupProvider::BUTTON_TEMPLATE));
    }

    public function testCurrentGaranNestedLabelRendererMatchesCurrentVersion(): void
    {
        $currentVersionRenderer = $this->createNestedLabelRendererStub(OfficialGuaranteeAssetLocator::GARAN_VERSION);
        $provider = $this->createProvider(
            garanNestedLabelRenderers: [
                $this->createNestedLabelRendererStub('v1.1'),
                $currentVersionRenderer,
            ]
        );
        $method = new ReflectionMethod(LegalGuaranteeMarkupProvider::class, 'getCurrentGaranNestedLabelRenderer');
        $method->setAccessible(true);

        self::assertSame($currentVersionRenderer, $method->invoke($provider));
    }

    public function testCurrentGaranNestedLabelRendererFailsWithoutRegistrationForCurrentVersion(): void
    {
        $provider = $this->createProvider(
            garanNestedLabelRenderers: [$this->createNestedLabelRendererStub('v1.1')]
        );
        $method = new ReflectionMethod(LegalGuaranteeMarkupProvider::class, 'getCurrentGaranNestedLabelRenderer');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'No GARAN nested label renderer registered for version "%s".',
            OfficialGuaranteeAssetLocator::GARAN_VERSION
        ));

        $method->invoke($provider);
    }

    private function createNestedLabelRendererStub(string $version): GaranNestedLabelRendererInterface
    {
        return new class ($version) implements GaranNestedLabelRendererInterface {
            public function __construct(private readonly string $version)
            {
            }

            public function getVersion(): string
            {
                return $this->version;
            }

            public function render(string $durationMonths): string
            {
                return '<svg xmlns="http://www.w3.org/2000/svg"><text>' . $this->version . '</text></svg>';
            }
        };
    }

    private function createGaranLabelRendererStub(string $version, string $text): GaranLabelRendererInterface
    {
        return new class ($version, $text) implements GaranLabelRendererInterface {
            public function __construct(
                private readonly string $version,
                private readonly string $text,
            ) {
            }

            public function getVersion(): string
            {
                return $this->version;
            }

            public function render(string $brand, string $modelIdentifier, string $durationText): string
            {
                return '<svg xmlns="http://www.w3.org/2000/svg"><text>' . $this->text . '</text></svg>';
            }
        };
    }

    private function createFailingGaranLabelRenderer(string $message): GaranLabelRendererInterface
    {
        return new class ($message) implements GaranLabelRendererInterface {
            public function __construct(private readonly string $message)
            {
            }

            public function getVersion(): string
            {
                return OfficialGuaranteeAssetLocator::GARAN_VERSION;
            }

            public function render(string $brand, string $modelIdentifier, string $durationText): string
            {
                throw new RuntimeException($this->message);
            }
        };
    }

    private function createFailingGaranLabelRendererForBrand(string $failingBrand): GaranLabelRendererInterface
    {
        return new class ($failingBrand) implements GaranLabelRendererInterface {
            public function __construct(private readonly string $failingBrand)
            {
            }

            public function getVersion(): string
            {
                return OfficialGuaranteeAssetLocator::GARAN_VERSION;
            }

            public function render(string $brand, string $modelIdentifier, string $durationText): string
            {
                if ($brand === $this->failingBrand) {
                    throw new RuntimeException('broken checkout label');
                }

                return '<svg xmlns="http://www.w3.org/2000/svg"><text>ok</text></svg>';
            }
        };
    }

    /**
     * @param iterable<GaranLabelRendererInterface>|null $garanLabelRenderers
     * @param iterable<GaranNestedLabelRendererInterface>|null $garanNestedLabelRenderers
     */
    private function createProvider(
        ?string $projectDir = null,
        ?iterable $garanLabelRenderers = null,
        ?LoggerInterface $contaoErrorLogger = null,
        string $memberGroupLgnMode = 'on',
        ?iterable $garanNestedLabelRenderers = null
    ): LegalGuaranteeMarkupProvider
    {
        return $this->createProviderWithMemberGroupLgnModeProvider(
            new class ($memberGroupLgnMode) implements MemberGroupLgnModeProviderInterface {
                public function __construct(private readonly string $memberGroupLgnMode)
                {
                }

                public function getCurrentMemberGroupLgnMode(): string
                {
                    return $this->memberGroupLgnMode;
                }
            },
            $projectDir,
            $garanLabelRenderers,
            $contaoErrorLogger,
            $garanNestedLabelRenderers
        );
    }

    /**
     * @param iterable<GaranLabelRendererInterface>|null $garanLabelRenderers
     * @param iterable<GaranNestedLabelRendererInterface>|null $garanNestedLabelRenderers
     */
    private function createProviderWithMemberGroupLgnModeProvider(
        MemberGroupLgnModeProviderInterface $memberGroupLgnModeProvider,
        ?string $projectDir = null,
        ?iterable $garanLabelRenderers = null,
        ?LoggerInterface $contaoErrorLogger = null,
        ?iterable $garanNestedLabelRenderers = null
    ): LegalGuaranteeMarkupProvider
    {
        $assetLocator = new OfficialGuaranteeAssetLocator(
            $projectDir ?? dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );

        return new LegalGuaranteeMarkupProvider(
            $assetLocator,
            new ProductGuaranteeDisplayResolver(),
            new GaranLabelGrouping(),
            new LegalGuaranteeMarkupBuilder(),
            $this->templateRenderer,
            $contaoErrorLogger ?? new NullLogger(),
            $memberGroupLgnModeProvider,
            $garanLabelRenderers ?? [new GaranLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
            $garanNestedLabelRenderers ?? [
                new GaranNestedLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION),
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function createCompleteGaranMainData(string $productLgnMode): array
    {
        return [
            'lgnMode' => $productLgnMode,
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => '36',
        ];
    }

    /**
     * @param array<string, mixed> $mainData
     */
    private function createProduct(
        array $mainData,
        int $productId = 1,
        string $title = 'Product',
        ?ls_shop_variant $selectedVariant = null,
        string $code = ''
    ): ls_shop_product
    {
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
        $itemsExtendedProperty->setAccessible(true);
        $itemsExtendedProperty->setValue(
            $cart,
            array_map(
                static fn (ls_shop_product $product): array => ['objProduct' => $product],
                $products
            )
        );

        $instanceProperty = new ReflectionProperty(ls_shop_cartX::class, 'objInstance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $cart);
    }
}
