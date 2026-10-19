<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\GaranLabelRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class LegalGuaranteeMarkupProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'gllTitle' => 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
            'gllEuLinkText' => 'EU-Information zur gesetzlichen Gewährleistung',
            'garanBlockHeadline' => 'Herstellergarantie für folgende Artikel:',
            'garanEuLinkText' => 'EU-Information zur Herstellergarantie',
            'garanTitlePattern' => 'Herstellergarantie von %s für %s mit %s Jahren',
        ];
        $GLOBALS['TL_CSS'] = [];
        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = ',';
    }

    public function testRenderCurrentLanguageGllNoticeUsesResolvedLanguageAndAccessibilityTitle(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];

        $markup = $this->createProvider()->renderCurrentLanguageGllNotice();

        self::assertStringContainsString('lang="de"', $markup);
        self::assertStringContainsString('Gesetzliche Gewährleistung, mindestens zwei Jahre', $markup);
        self::assertStringContainsString(
            'https://europa.eu/youreurope/garantien',
            $markup
        );
        self::assertContains(
            'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee.css',
            $GLOBALS['TL_CSS']
        );
    }

    public function testRenderCurrentLanguageGllNoticeFallsBackToEnglishForUnknownLocale(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'zz_ZZ'];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'gllTitle' => 'Legal guarantee, at least two years',
            'gllEuLinkText' => 'EU information about the legal guarantee',
            'garanBlockHeadline' => 'Manufacturer\'s commercial guarantee for the following items:',
            'garanEuLinkText' => 'EU information about the manufacturer\'s commercial guarantee',
            'garanTitlePattern' => 'Manufacturer\'s commercial guarantee by %s for %s with %s years',
        ];

        $markup = $this->createProvider()->renderCurrentLanguageGllNotice();

        self::assertStringContainsString('lang="en"', $markup);
        self::assertStringContainsString('Legal guarantee, at least two years', $markup);
        self::assertStringContainsString('https://europa.eu/youreurope/guarantees', $markup);
    }

    public function testBuildGaranTitleFormatsStoredDurationForDisplay(): void
    {
        $method = new ReflectionMethod(LegalGuaranteeMarkupProvider::class, 'buildGaranTitle');
        $method->setAccessible(true);

        $formattedHalfYear = $method->invoke($this->createProvider(), 'ACME', 'MX-42', '2.5', ',');
        $formattedWholeYears = $method->invoke($this->createProvider(), 'ACME', 'MX-42', '30.0', ',');

        self::assertSame('Herstellergarantie von ACME für MX-42 mit 2,5 Jahren', $formattedHalfYear);
        self::assertSame('Herstellergarantie von ACME für MX-42 mit 30 Jahren', $formattedWholeYears);
    }

    public function testBuildGaranTitleUsesConfiguredDotSeparator(): void
    {
        $method = new ReflectionMethod(LegalGuaranteeMarkupProvider::class, 'buildGaranTitle');
        $method->setAccessible(true);

        $formattedHalfYear = $method->invoke($this->createProvider(), 'ACME', 'MX-42', '2.5', '.');

        self::assertSame('Herstellergarantie von ACME für MX-42 mit 2.5 Jahren', $formattedHalfYear);
    }

    public function testRenderCheckoutGllLinksReturnsEmptyStringWithoutEligibleCartItems(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'enableGll' => '',
                'enableGaran' => '',
            ]),
        ]);

        $markup = $this->createProvider()->renderCheckoutGllLinks();

        self::assertSame('', $markup);
        self::assertSame([], $GLOBALS['TL_CSS']);
    }

    public function testRenderCheckoutGllLinksRendersLabelMarkupForFirstEligibleCartItem(): void
    {
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $this->setCartItems([
            $this->createProduct([
                'enableGll' => '1',
                'enableGaran' => '',
            ]),
            $this->createProduct([
                'enableGll' => '1',
                'enableGaran' => '',
            ]),
        ]);

        $markup = $this->createProvider()->renderCheckoutGllLinks();

        self::assertStringContainsString('merconis-legal-guarantee-label', $markup);
        self::assertStringContainsString('lang="de"', $markup);
        self::assertStringContainsString('EU-Information zur gesetzlichen Gewährleistung', $markup);
        self::assertStringNotContainsString('gewaehrleistungslabel', $markup);
        self::assertSame(1, substr_count($markup, '<a '));
        self::assertContains(
            'bundles/leadingsystemsmerconis/legal-guarantee/legal-guarantee.css',
            $GLOBALS['TL_CSS']
        );
    }

    public function testRenderProductGaranLabelUsesRendererMatchingCurrentVersion(): void
    {
        $product = $this->createProduct([
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'MX-42',
            'guaranteeDurationYears' => '2.5',
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
                        string $durationText,
                        string $decimalSeparator
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
                        string $durationText,
                        string $decimalSeparator
                    ): string {
                        return sprintf(
                            '<svg xmlns="http://www.w3.org/2000/svg"><text>%s|%s|%s|%s</text></svg>',
                            $brand,
                            $modelIdentifier,
                            $durationText,
                            $decimalSeparator
                        );
                    }
                },
            ]
        )->renderProductGaranLabel($product);

        self::assertStringContainsString('ACME|MX-42|2.5|,', $markup);
        self::assertStringNotContainsString('wrong-version', $markup);
    }

    /**
     * @param iterable<GaranLabelRendererInterface>|null $garanLabelRenderers
     */
    private function createProvider(?string $projectDir = null, ?iterable $garanLabelRenderers = null): LegalGuaranteeMarkupProvider
    {
        $assetLocator = new OfficialGuaranteeAssetLocator(
            $projectDir ?? dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );

        return new LegalGuaranteeMarkupProvider(
            $assetLocator,
            new ProductGuaranteeDisplayResolver(),
            new LegalGuaranteeMarkupBuilder(),
            $garanLabelRenderers ?? [new GaranLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
        );
    }

    /**
     * @param array<string, mixed> $mainData
     */
    private function createProduct(array $mainData): ls_shop_product
    {
        $productReflection = new ReflectionClass(ls_shop_product::class);
        /** @var ls_shop_product $product */
        $product = $productReflection->newInstanceWithoutConstructor();
        $product->mainData = $mainData;

        return $product;
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
