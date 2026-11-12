<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionProperty;

final class CheckoutGaranBlockIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'gllTitle' => 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
            'gllEuLinkText' => 'EU-Information zur gesetzlichen Gewährleistung',
            'garanBlockHeadline' => 'Herstellergarantie für folgende Artikel:',
            'garanEuLinkText' => 'EU-Information zur Herstellergarantie',
            'garanTitlePattern' => 'Herstellergarantie von %s für %s mit %s Jahren',
        ];
        $GLOBALS['TL_CSS'] = [];
    }

    public function testCheckoutGaranBlockRendersGroupedHtmlWithRealRenderer(): void
    {
        $this->setCartItems([
            $this->createProduct(
                [
                    'enableGll' => '1',
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
                    'enableGll' => '1',
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
                    'enableGll' => '1',
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
        self::assertSame(2, substr_count($markup, 'class="merconis-legal-guarantee-link"'));
        self::assertStringContainsString(
            'https://europa.eu/youreurope/commercial-guarantee-durability/',
            $markup
        );
        self::assertStringContainsString('Kissen (Rot) - ART-101<br>Kissen (Blau) - ART-102', $markup);
        self::assertStringContainsString('Decke - ART-200', $markup);
        self::assertGreaterThan(
            strpos($markup, 'Kissen (Rot) - ART-101<br>Kissen (Blau) - ART-102'),
            strpos($markup, 'Decke - ART-200')
        );
        self::assertSame(2, substr_count($markup, 'Herstellergarantie von ACME für X100 mit 3 Jahren'));

        preg_match_all('/id="([^"]+)"/', $markup, $matches);
        self::assertNotEmpty($matches[1]);
        self::assertSameSize($matches[1], array_unique($matches[1]));
    }

    public function testCheckoutGaranBlockOmitsArticleSuffixWhenArticleNumberIsMissing(): void
    {
        $this->setCartItems([
            $this->createProduct(
                [
                    'enableGll' => '1',
                    'enableGaran' => '1',
                    'guaranteeBrand' => 'ACME',
                    'guaranteeModelIdentifier' => 'X100',
                    'guaranteeDurationMonths' => '36',
                ],
                10,
                'Kissen'
            ),
        ]);

        $markup = $this->createProvider()->checkoutGaranBlock();

        self::assertStringContainsString('Kissen', $markup);
        self::assertStringNotContainsString('Kissen - ', $markup);
    }

    private function createProvider(): LegalGuaranteeMarkupProvider
    {
        $assetLocator = new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );

        return new LegalGuaranteeMarkupProvider(
            $assetLocator,
            new ProductGuaranteeDisplayResolver(),
            new GaranLabelGrouping(),
            new LegalGuaranteeMarkupBuilder(),
            new NullLogger(),
            [new GaranLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
        );
    }

    private function createProduct(
        array $mainData,
        int $productId,
        string $title,
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
