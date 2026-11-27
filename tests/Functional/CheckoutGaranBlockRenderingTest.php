<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../Support/ContaoTestApplication.php';

use DOMElement;
use DOMXPath;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranNestedLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeProviderInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\Tests\Support\ContaoTestApplication;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use Merconis\Core\ls_shop_variant;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionProperty;

/**
 * Zuordnungsblock der letzten Bestellseite mit echtem Renderer und den Templates aus der
 * Template-Auflösung von Contao (ADR 0020; ADR 0028, Entscheidung 6).
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CheckoutGaranBlockRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ContaoTestApplication::boot('de');
    }

    protected function tearDown(): void
    {
        $instanceProperty = new ReflectionProperty(ls_shop_cartX::class, 'objInstance');
        $instanceProperty->setValue(null, null);

        parent::tearDown();
    }

    public function testCheckoutGaranBlockGroupsVariantsOfSameProductAndSeparatesOtherProducts(): void
    {
        $this->setCartItems([
            $this->createProduct($this->createGaranMainData('inherit'), 10, 'Kissen', $this->createVariant(101, 'Rot', 'ART-101')),
            $this->createProduct($this->createGaranMainData('inherit'), 10, 'Kissen', $this->createVariant(102, 'Blau', 'ART-102')),
            $this->createProduct($this->createGaranMainData('inherit'), 20, 'Decke', null, 'ART-200'),
        ]);

        $xpath = ContaoTestApplication::createXPath($this->createProvider()->checkoutGaranBlock());
        $items = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__item');

        self::assertSame(
            'Herstellergarantie für folgende Artikel:',
            ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__headline')[0]->textContent
        );
        self::assertCount(2, $items);
        self::assertSame(['Kissen (Rot) - ART-101', 'Kissen (Blau) - ART-102'], $this->readPositionTexts($xpath, $items[0]));
        self::assertSame(['Decke - ART-200'], $this->readPositionTexts($xpath, $items[1]));
        self::assertSame(2, $xpath->query('//svg')?->length);
        self::assertSame(2, $xpath->query('//a[@href="https://europa.eu/youreurope/commercial-guarantee-durability/"]')?->length);
        self::assertSame(2, $xpath->query('//svg/title[text()="Herstellergarantie von ACME für X100 mit 3 Jahren"]')?->length);

        $ids = ContaoTestApplication::collectIds($xpath);

        self::assertNotEmpty($ids);
        self::assertSame(array_values(array_unique($ids)), $ids);
    }

    public function testCheckoutGaranBlockOmitsSeparatorAndArticleNumberWhenArticleNumberIsMissing(): void
    {
        $this->setCartItems([
            $this->createProduct($this->createGaranMainData('inherit'), 10, 'Kissen'),
        ]);

        $xpath = ContaoTestApplication::createXPath($this->createProvider()->checkoutGaranBlock());
        $items = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__item');

        self::assertSame(['Kissen'], $this->readPositionTexts($xpath, $items[0]));
        self::assertCount(0, ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__position-separator'));
    }

    public function testCheckoutGaranBlockKeepsOnlyPositionsWithLgnOutputForMemberGroup(): void
    {
        $this->setCartItems([
            $this->createProduct($this->createGaranMainData('inherit'), 10, 'Kissen', null, 'ART-100'),
            $this->createProduct($this->createGaranMainData('always'), 20, 'Decke', $this->createVariant(201, 'Grau', 'ART-201')),
        ]);

        $xpath = ContaoTestApplication::createXPath($this->createProvider('off')->checkoutGaranBlock());
        $items = ContaoTestApplication::findByClass($xpath, 'merconis-legal-guarantee-assignment__item');

        self::assertCount(1, $items);
        self::assertSame(1, $xpath->query('//svg')?->length);
        self::assertSame(['Decke (Grau) - ART-201'], $this->readPositionTexts($xpath, $items[0]));
    }

    public function testCheckoutGaranBlockIsEmptyWithoutLgnOutputForMemberGroup(): void
    {
        $this->setCartItems([
            $this->createProduct($this->createGaranMainData('always'), 10, 'Kissen', null, 'ART-100'),
        ]);

        self::assertSame('', $this->createProvider('blocked')->checkoutGaranBlock());
        self::assertSame('', $this->createProvider('')->checkoutGaranBlock());
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

    private function createProvider(string $memberGroupLgnMode = 'on'): LegalGuaranteeMarkupProvider
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
            new LegalGuaranteeTemplateRenderer(),
            new NullLogger(),
            new class ($memberGroupLgnMode) implements MemberGroupLgnModeProviderInterface {
                public function __construct(private readonly string $memberGroupLgnMode)
                {
                }

                public function getCurrentMemberGroupLgnMode(): string
                {
                    return $this->memberGroupLgnMode;
                }
            },
            [new GaranLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
            [new GaranNestedLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
        );
    }

    /**
     * @return array<string, string>
     */
    private function createGaranMainData(string $productLgnMode): array
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
