<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php';

use Contao\Database;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranLabelRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\CurrentMemberGroupGllModeProvider;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotBuilder;
use Merconis\Core\ls_shop_cartX;
use Merconis\Core\ls_shop_product;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;

/**
 * Prüft den Weg des Gruppenwerts über `ls_shop_generalHelper::getGroupSettings4User()`
 * einschließlich Hook `getGroupSettingsForUser` bis in Produktdetailseite, letzte
 * Bestellseite und Bestell-Snapshot. Die Standard-Mitgliedergruppe liegt in einer
 * SQLite-Datenbank; angemeldet ist niemand.
 */
final class LegalGuaranteeMemberGroupGllModeHookTest extends TestCase
{
    private const STANDARD_MEMBER_GROUP_ID = 7;

    /**
     * @var array<string, mixed>
     */
    private array $originalGlobals = [];
    private mixed $originalContainer;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['TL_CONFIG', 'MERCONIS_HOOKS', 'merconis_globals', 'TL_LANG', 'TL_CSS', 'objPage'] as $globalName) {
            $this->originalGlobals[$globalName] = $GLOBALS[$globalName] ?? null;
        }

        $this->originalContainer = System::getContainer();

        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = self::STANDARD_MEMBER_GROUP_ID;
        $GLOBALS['MERCONIS_HOOKS'] = [];
        $GLOBALS['merconis_globals'] = ['LaFP' => ['y2' => true]];
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'gllTitle' => 'Gesetzliche Gewährleistung, mindestens zwei Jahre',
            'gllEuLinkText' => 'EU-Information zur gesetzlichen Gewährleistung',
            'garanBlockHeadline' => 'Herstellergarantie für folgende Artikel:',
            'garanEuLinkText' => 'EU-Information zur Herstellergarantie',
            'garanTitlePattern' => 'Herstellergarantie von %s für %s mit %s Jahren',
        ];
        $GLOBALS['TL_CSS'] = [];
        $GLOBALS['objPage'] = (object) ['language' => 'de_DE'];
        LegalGuaranteeMemberGroupGllModeTestHook::$gllModeToSet = '';
    }

    protected function tearDown(): void
    {
        foreach ($this->originalGlobals as $globalName => $originalValue) {
            if ($originalValue === null) {
                unset($GLOBALS[$globalName]);
            } else {
                $GLOBALS[$globalName] = $originalValue;
            }
        }

        $this->resetDatabaseSingleton();
        $this->resetCartSingleton();

        $containerProperty = new ReflectionProperty(System::class, 'objContainer');
        $containerProperty->setValue(null, $this->originalContainer);

        parent::tearDown();
    }

    public function testStoredStandardMemberGroupValueReachesOutputWithoutHook(): void
    {
        $this->installContainer($this->createMemberGroupConnection('off'));
        $inheritProduct = $this->createProduct('inherit', 10, 'Kissen', 'ART-100');
        $alwaysProduct = $this->createProduct('always', 20, 'Decke', 'ART-200');
        $markupProvider = $this->createMarkupProvider();

        self::assertSame('', $markupProvider->renderProductGllNotice($inheritProduct));
        self::assertSame('', $markupProvider->renderProductGaranLabel($inheritProduct));
        self::assertNotSame('', $markupProvider->renderProductGllNotice($alwaysProduct));
        self::assertNotSame('', $markupProvider->renderProductGaranLabel($alwaysProduct));
    }

    public function testHookAdjustmentEnablesProductDetailOutput(): void
    {
        $this->installContainer($this->createMemberGroupConnection('off'));
        $this->registerHook('on');
        $product = $this->createProduct('inherit', 10, 'Kissen', 'ART-100');
        $markupProvider = $this->createMarkupProvider();

        self::assertStringContainsString('EU-Information zur gesetzlichen Gewährleistung', $markupProvider->renderProductGllNotice($product));
        self::assertStringContainsString('Herstellergarantie von ACME für X100 mit 3 Jahren', $markupProvider->renderProductGaranLabel($product));
    }

    public function testHookAdjustmentEnablesLastOrderPageOutput(): void
    {
        $this->installContainer($this->createMemberGroupConnection('off'));
        $this->registerHook('on');
        $this->setCartItems([$this->createProduct('inherit', 10, 'Kissen', 'ART-100')]);
        $markupProvider = $this->createMarkupProvider();

        self::assertNotSame('', $markupProvider->checkoutGllNotice());
        self::assertStringContainsString('Kissen - ART-100', $markupProvider->checkoutGaranBlock());
    }

    public function testHookAdjustmentReachesOrderSnapshot(): void
    {
        $this->installContainer($this->createMemberGroupConnection('off'));
        $this->registerHook('on');

        $snapshot = $this->createSnapshotBuilder()->buildItemSnapshot(
            $this->createProduct('inherit', 10, 'Kissen', 'ART-100')->mainData
        );

        self::assertSame('1', $snapshot[OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY]);
        self::assertSame('ACME', $snapshot['garanBrand']);
    }

    public function testHookAdjustmentSuppressesOutputOfStoredOnGroup(): void
    {
        $this->installContainer($this->createMemberGroupConnection('on'));
        $this->registerHook('blocked');
        $product = $this->createProduct('always', 10, 'Kissen', 'ART-100');
        $this->setCartItems([$product]);
        $markupProvider = $this->createMarkupProvider();

        self::assertSame('', $markupProvider->renderProductGllNotice($product));
        self::assertSame('', $markupProvider->renderProductGaranLabel($product));
        self::assertSame('', $markupProvider->checkoutGllNotice());
        self::assertSame('', $markupProvider->checkoutGaranBlock());
        self::assertSame(
            '',
            $this->createSnapshotBuilder()->buildItemSnapshot($product->mainData)[OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY]
        );
    }

    public function testNullValueOfStandardMemberGroupSuppressesOutput(): void
    {
        $this->installContainer($this->createMemberGroupConnection(null));
        $product = $this->createProduct('always', 10, 'Kissen', 'ART-100');

        self::assertSame('', (new CurrentMemberGroupGllModeProvider())->getCurrentMemberGroupGllMode());
        self::assertSame('', $this->createMarkupProvider()->renderProductGllNotice($product));
    }

    private function registerHook(string $gllModeToSet): void
    {
        LegalGuaranteeMemberGroupGllModeTestHook::$gllModeToSet = $gllModeToSet;
        $GLOBALS['MERCONIS_HOOKS']['getGroupSettingsForUser'][] = [
            LegalGuaranteeMemberGroupGllModeTestHook::class,
            'adjustGroupSettings',
        ];
    }

    private function installContainer(Connection $connection): void
    {
        $container = new Container();
        $container->setParameter('kernel.debug', false);
        $container->set('database_connection', $connection);
        $container->set('contao.security.token_checker', new class {
            public function hasFrontendUser(): bool
            {
                return false;
            }
        });

        System::setContainer($container);
        $this->resetDatabaseSingleton();
    }

    private function createMemberGroupConnection(?string $standardMemberGroupGllMode): Connection
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $connection->executeStatement('
            CREATE TABLE tl_member_group (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL DEFAULT \'\',
                lsShopGllMode TEXT NULL
            )
        ');
        $connection->insert('tl_member_group', [
            'id' => self::STANDARD_MEMBER_GROUP_ID,
            'name' => 'Standard',
            'lsShopGllMode' => $standardMemberGroupGllMode,
        ]);

        return $connection;
    }

    private function createMarkupProvider(): LegalGuaranteeMarkupProvider
    {
        $assetLocator = $this->createAssetLocator();

        return new LegalGuaranteeMarkupProvider(
            $assetLocator,
            new ProductGuaranteeDisplayResolver(),
            new GaranLabelGrouping(),
            new LegalGuaranteeMarkupBuilder(),
            new NullLogger(),
            new CurrentMemberGroupGllModeProvider(),
            [new GaranLabelRenderer($assetLocator, OfficialGuaranteeAssetLocator::GARAN_VERSION)],
        );
    }

    private function createSnapshotBuilder(): OrderLabelSnapshotBuilder
    {
        return new OrderLabelSnapshotBuilder(
            new ProductGuaranteeDisplayResolver(),
            $this->createAssetLocator(),
            new CurrentMemberGroupGllModeProvider()
        );
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(dirname(__DIR__, 2), new OfficialGuaranteeLanguageResolver());
    }

    private function createProduct(string $gllMode, int $productId, string $title, string $code): ls_shop_product
    {
        $productReflection = new ReflectionClass(ls_shop_product::class);
        /** @var ls_shop_product $product */
        $product = $productReflection->newInstanceWithoutConstructor();
        $product->ls_ID = $productId;
        $product->mainData = [
            'gllMode' => $gllMode,
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => '36',
            'lsShopProductCode' => $code,
            'title' => $title,
        ];
        $product->currentLanguageData = ['title' => $title];

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

    private function resetCartSingleton(): void
    {
        $instanceProperty = new ReflectionProperty(ls_shop_cartX::class, 'objInstance');
        $instanceProperty->setValue(null, null);
    }

    private function resetDatabaseSingleton(): void
    {
        $instanceProperty = new ReflectionProperty(Database::class, 'objInstance');
        $instanceProperty->setValue(null, null);
    }
}

final class LegalGuaranteeMemberGroupGllModeTestHook
{
    public static string $gllModeToSet = '';

    /**
     * @param array<string, mixed> $groupSettings
     *
     * @return array<string, mixed>
     */
    public function adjustGroupSettings(array $groupSettings): array
    {
        $groupSettings['lsShopGllMode'] = self::$gllModeToSet;

        return $groupSettings;
    }
}
