<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeProviderInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
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
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'lgnTitle' => 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
            'lgnEuLinkText' => 'EU-Information zur gesetzlichen Gewährleistung',
            'garanBlockHeadline' => 'Herstellergarantie für folgende Artikel:',
            'garanEuLinkText' => 'EU-Information zur Herstellergarantie',
            'garanTitlePattern' => 'Herstellergarantie von %s für %s mit %s Jahren',
        ];
        $GLOBALS['TL_CSS'] = [];
    }

    public function testRenderCurrentLanguageLgnNoticeUsesResolvedLanguageAndAccessibilityTitle(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];

        $markup = $this->createProvider()->renderCurrentLanguageLgnNotice();

        self::assertStringContainsString('lang="de"', $markup);
        self::assertStringContainsString('Gesetzliche Gewährleistung, mindestens zwei Jahre', $markup);
        self::assertStringContainsString(
            'https://europa.eu/youreurope/garantien',
            $markup
        );
        self::assertContains(
            'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee-fonts.css',
            $GLOBALS['TL_CSS']
        );
    }

    public function testRenderCurrentLanguageLgnNoticeFallsBackToEnglishForUnknownLocale(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'zz_ZZ'];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'lgnTitle' => 'Legal guarantee, at least two years',
            'lgnEuLinkText' => 'EU information about the legal guarantee',
            'garanBlockHeadline' => 'Manufacturer\'s commercial guarantee for the following items:',
            'garanEuLinkText' => 'EU information about the manufacturer\'s commercial guarantee',
            'garanTitlePattern' => 'Manufacturer\'s commercial guarantee by %s for %s with %s years',
        ];

        $markup = $this->createProvider()->renderCurrentLanguageLgnNotice();

        self::assertStringContainsString('lang="en"', $markup);
        self::assertStringContainsString('Legal guarantee, at least two years', $markup);
        self::assertStringContainsString('https://europa.eu/youreurope/guarantees', $markup);
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

    public function testCheckoutLgnNoticeReturnsEmptyStringWithoutEligibleCartItems(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'lgnMode' => 'never',
                'enableGaran' => '',
            ]),
        ]);

        $markup = $this->createProvider()->checkoutLgnNotice();

        self::assertSame('', $markup);
        self::assertSame([], $GLOBALS['TL_CSS']);
    }

    public function testCheckoutLgnNoticeRendersLabelMarkupForFirstEligibleCartItem(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'lgnMode' => 'inherit',
                'enableGaran' => '',
            ]),
            $this->createProduct([
                'lgnMode' => 'inherit',
                'enableGaran' => '',
            ]),
        ]);

        $markup = $this->createProvider()->checkoutLgnNotice();

        self::assertStringContainsString('merconis-legal-guarantee-label', $markup);
        self::assertStringContainsString('lang="de"', $markup);
        self::assertStringContainsString('EU-Information zur gesetzlichen Gewährleistung', $markup);
        self::assertStringNotContainsString('gewaehrleistungslabel', $markup);
        self::assertSame(1, substr_count($markup, '<a '));
        self::assertContains(
            'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee-fonts.css',
            $GLOBALS['TL_CSS']
        );
    }

    public function testCheckoutGaranBlockGroupsVariantsOfSameProductWithSharedValues(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct(
                [
                    'lgnMode' => 'inherit',
                    'enableGaran' => '1',
                    'guaranteeBrand' => 'ACME',
                    'guaranteeModelIdentifier' => 'X100',
                    'guaranteeDurationMonths' => '36',
                ],
                10,
                'Kissen',
                $this->createVariant(101, 'Rot', 'ART-101')
            ),
            $this->createProduct(
                [
                    'lgnMode' => 'inherit',
                    'enableGaran' => '1',
                    'guaranteeBrand' => 'ACME',
                    'guaranteeModelIdentifier' => 'X100',
                    'guaranteeDurationMonths' => '36',
                ],
                10,
                'Kissen',
                $this->createVariant(102, 'Blau', 'ART-102')
            ),
            $this->createProduct(
                [
                    'lgnMode' => 'inherit',
                    'enableGaran' => '1',
                    'guaranteeBrand' => 'ACME',
                    'guaranteeModelIdentifier' => 'X100',
                    'guaranteeDurationMonths' => '36',
                ],
                20,
                'Decke',
                null,
                'ART-200'
            ),
        ]);

        $markup = $this->createProvider()->checkoutGaranBlock();

        self::assertStringContainsString('Herstellergarantie für folgende Artikel:', $markup);
        self::assertSame(2, substr_count($markup, 'class="merconis-legal-guarantee-checkout-item"'));
        self::assertSame(2, substr_count($markup, '<svg '));
        self::assertSame(2, substr_count($markup, 'merconis-legal-guarantee-link'));
        self::assertStringContainsString('Kissen (Rot) - ART-101<br>Kissen (Blau) - ART-102', $markup);
        self::assertStringContainsString('Decke - ART-200', $markup);
        self::assertNotFalse(strpos($markup, 'Kissen (Rot) - ART-101<br>Kissen (Blau) - ART-102'));
        self::assertGreaterThan(
            strpos($markup, 'Kissen (Rot) - ART-101<br>Kissen (Blau) - ART-102'),
            strpos($markup, 'Decke - ART-200')
        );
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
                new class implements GaranLabelRendererInterface {
                    public function getVersion(): string
                    {
                        return 'v1.1';
                    }

                    public function render(
                        string $brand,
                        string $modelIdentifier,
                        string $durationText
                    ): string {
                        return '<svg xmlns="http://www.w3.org/2000/svg"><text>wrong-version</text></svg>';
                    }
                },
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

        self::assertStringContainsString('ACME|MX-42|30', $markup);
        self::assertStringNotContainsString('wrong-version', $markup);
    }

    public function testCheckoutGaranBlockLeavesArticleNumberOutWhenCodeIsMissing(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeBrand' => 'ACME',
                'guaranteeModelIdentifier' => 'X100',
                'guaranteeDurationMonths' => '36',
            ], 10, 'Kissen'),
        ]);

        $markup = $this->createProvider()->checkoutGaranBlock();

        self::assertStringContainsString('Kissen', $markup);
        self::assertStringNotContainsString('Kissen - ', $markup);
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
            ->with(self::stringContains('ART-500'));

        $markup = $this->createProvider(null, [
            new class implements GaranLabelRendererInterface {
                public function getVersion(): string
                {
                    return OfficialGuaranteeAssetLocator::GARAN_VERSION;
                }

                public function render(string $brand, string $modelIdentifier, string $durationText): string
                {
                    if ($brand === 'FAIL') {
                        throw new RuntimeException('broken checkout label');
                    }

                    return '<svg xmlns="http://www.w3.org/2000/svg"><text>ok</text></svg>';
                }
            },
        ], $logger)->checkoutGaranBlock();

        self::assertStringContainsString('Decke - ART-200', $markup);
        self::assertStringNotContainsString('Defekt - ART-500', $markup);
        self::assertSame(1, substr_count($markup, 'class="merconis-legal-guarantee-checkout-item"'));
        self::assertSame(1, substr_count($markup, 'merconis-legal-guarantee-link'));
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

        $markup = $this->createProvider(null, [
            new class implements GaranLabelRendererInterface {
                public function getVersion(): string
                {
                    return OfficialGuaranteeAssetLocator::GARAN_VERSION;
                }

                public function render(string $brand, string $modelIdentifier, string $durationText): string
                {
                    throw new RuntimeException('broken checkout label');
                }
            },
        ])->checkoutGaranBlock();

        self::assertSame('', $markup);
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
    public function testProductDetailOutputFollowsMemberGroupLgnModeFromProvider(
        string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedOutput
    ): void {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $product = $this->createProduct($this->createCompleteGaranMainData($productLgnMode));
        $provider = $this->createProvider(memberGroupLgnMode: $memberGroupLgnMode);

        $lgnMarkup = $provider->renderProductLgnNotice($product);
        $garanMarkup = $provider->renderProductGaranLabel($product);

        self::assertSame($expectedOutput, '' !== $lgnMarkup);
        self::assertSame($expectedOutput, '' !== $garanMarkup);
        self::assertSame($expectedOutput ? 1 : 0, substr_count($lgnMarkup . $garanMarkup, 'europa.eu/youreurope/garantien'));
        self::assertSame($expectedOutput ? 1 : 0, substr_count($lgnMarkup . $garanMarkup, 'commercial-guarantee-durability'));
    }

    #[DataProvider('memberGroupAndProductLgnModeProvider')]
    public function testLastOrderPageOutputFollowsMemberGroupLgnModeFromProvider(
        string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedOutput
    ): void {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct($this->createCompleteGaranMainData($productLgnMode), 10, 'Kissen', null, 'ART-100'),
        ]);
        $provider = $this->createProvider(memberGroupLgnMode: $memberGroupLgnMode);

        $lgnMarkup = $provider->checkoutLgnNotice();
        $garanMarkup = $provider->checkoutGaranBlock();

        self::assertSame($expectedOutput, '' !== $lgnMarkup);
        self::assertSame($expectedOutput, '' !== $garanMarkup);
        self::assertSame($expectedOutput, str_contains($garanMarkup, 'Kissen - ART-100'));
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

    /**
     * @param iterable<GaranLabelRendererInterface>|null $garanLabelRenderers
     */
    private function createProvider(
        ?string $projectDir = null,
        ?iterable $garanLabelRenderers = null,
        ?LoggerInterface $contaoErrorLogger = null,
        string $memberGroupLgnMode = 'on'
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
            $contaoErrorLogger
        );
    }

    /**
     * @param iterable<GaranLabelRendererInterface>|null $garanLabelRenderers
     */
    private function createProviderWithMemberGroupLgnModeProvider(
        MemberGroupLgnModeProviderInterface $memberGroupLgnModeProvider,
        ?string $projectDir = null,
        ?iterable $garanLabelRenderers = null,
        ?LoggerInterface $contaoErrorLogger = null
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
            $contaoErrorLogger ?? new NullLogger(),
            $memberGroupLgnModeProvider,
            $garanLabelRenderers ?? [new GaranLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
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
