<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php';
require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_productManagementApiHelper.php';
require_once __DIR__ . '/../../src/Resources/contao/classes/ls_shop_productManagementApiPreprocessor.php';

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\Database;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_productManagementApiHelper;
use Merconis\Core\ls_shop_productManagementApiPreprocessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;

/**
 * Product Management API (Spec 27, Abschnitte 4 und 10) gegen eine SQLite-Datenbank: Bezug
 * ist der gespeicherte Wert der Standard-Mitgliedergruppe, für Produktzeilen der
 * gespeicherte Stand der Varianten, für Variantenzeilen der gespeicherte Stand des
 * Elternprodukts. Die Sprachdatei `default` ist zu Beginn nicht geladen; der Code lädt sie
 * über `System::loadLanguageFile()` in der Sprache des Requests (`$GLOBALS['TL_LANGUAGE']`).
 */
final class LegalGuaranteeProductManagementApiTest extends TestCase
{
    private const STANDARD_MEMBER_GROUP_ID = 7;
    private const PRODUCT_ID = 21;
    private const LANGUAGE_RESOURCE_PATH = __DIR__ . '/../../src/Resources/contao';

    /*
     * Eine leere oder numerische Kategorie führt `preprocess_category()` in einen Aufruf der
     * nicht vorhandenen Methode `generatePageListFromCategoryIds()` (vorbestehend); der Alias
     * vermeidet diesen Zweig.
     */
    private const CATEGORY_ALIAS = 'shop';

    /**
     * @var array<string, mixed>
     */
    private array $originalGlobals = [];

    /**
     * @var array<string, mixed>
     */
    private array $originalSystemStatics = [];
    private mixed $originalContainer;
    private string $temporaryDirectory;
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['TL_CONFIG', 'TL_LANG', 'TL_LANGUAGE', 'TL_HOOKS', 'MERCONIS_HOOKS', 'merconis_globals'] as $globalName) {
            $this->originalGlobals[$globalName] = $GLOBALS[$globalName] ?? null;
        }

        foreach (['arrLanguageFiles', 'arrAvailableLanguageFiles', 'arrLanguages'] as $staticName) {
            $this->originalSystemStatics[$staticName] = (new ReflectionProperty(System::class, $staticName))->getValue();
            (new ReflectionProperty(System::class, $staticName))->setValue(null, $staticName === 'arrAvailableLanguageFiles' ? null : []);
        }

        $this->originalContainer = System::getContainer();
        $this->temporaryDirectory = sys_get_temp_dir() . '/merconis-pm-api-test-' . bin2hex(random_bytes(4));
        mkdir($this->temporaryDirectory);

        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = self::STANDARD_MEMBER_GROUP_ID;
        $GLOBALS['TL_LANG'] = [];
        $GLOBALS['TL_HOOKS'] = [];
        $GLOBALS['MERCONIS_HOOKS'] = [
            'getGroupSettingsForUser' => [[LegalGuaranteeProductManagementApiTestHook::class, 'adjustGroupSettings']],
        ];
        $GLOBALS['merconis_globals'] = ['LaFP' => ['y2' => true]];
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

        foreach ($this->originalSystemStatics as $staticName => $originalValue) {
            (new ReflectionProperty(System::class, $staticName))->setValue(null, $originalValue);
        }

        (new ReflectionProperty(Database::class, 'objInstance'))->setValue(null, null);
        (new ReflectionProperty(System::class, 'objContainer'))->setValue(null, $this->originalContainer);
        rmdir($this->temporaryDirectory);

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function requestLanguageProvider(): iterable
    {
        yield 'de' => [
            'de',
            'Feld "gllMode": Die Zeile wurde nicht übernommen, weil die Mitteilung über die gesetzliche Gewährleistung damit in der Standard-Benutzergruppe nicht angezeigt würde und folgende Varianten bei der Herstellergarantie "override" verwenden: VAR-OWN. Bitte zuerst diese Varianten auf einen anderen Wert als "override" stellen und die Zeile danach erneut übertragen oder für die gesetzliche Gewährleistung einen Wert angeben, mit dem die Mitteilung angezeigt wird.',
        ];
        yield 'en' => [
            'en',
            'field "gllMode": The row was not applied because the legal guarantee notice would then not be shown for the standard user group and the following variants use "override" for the manufacturer guarantee: VAR-OWN. Please first set these variants to a value other than "override" and then transfer the row again, or specify a legal guarantee notice setting that shows the notice.',
        ];
    }

    #[DataProvider('requestLanguageProvider')]
    public function testProductRowIsRejectedForStoredOverrideVariantsInRequestLanguage(
        string $requestLanguage,
        string $expectedMessage
    ): void {
        $GLOBALS['TL_LANGUAGE'] = $requestLanguage;
        $this->installDatabase('on', 'inherit', ['VAR-OWN' => 'override', 'VAR-INHERIT' => 'inherit']);

        try {
            ls_shop_productManagementApiHelper::applyLegalGuaranteeProductData($this->createProductRow('never', ''));
            self::fail('Die Produktzeile hätte abgelehnt werden müssen.');
        } catch (\Exception $exception) {
            self::assertSame($expectedMessage, $exception->getMessage());
        }

        self::assertSame('inherit', $this->fetchStoredProductGllMode());
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function acceptedGllModeProvider(): iterable
    {
        yield 'Feld fehlt' => [null, 'inherit'];
        yield 'inherit' => ['inherit', 'inherit'];
        yield 'always' => ['always', 'always'];
        yield 'never' => ['never', 'never'];
    }

    #[DataProvider('acceptedGllModeProvider')]
    public function testProductRowGllModeIsWritten(?string $submittedGllMode, string $expectedGllMode): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $this->installDatabase('on', 'always', ['VAR-INHERIT' => 'inherit']);
        $productRow = $this->createProductRow($submittedGllMode ?? '', '');

        if ($submittedGllMode === null) {
            unset($productRow['gllMode']);
        }

        self::assertSame($expectedGllMode, ls_shop_productManagementApiHelper::applyLegalGuaranteeProductData($productRow)['gllMode']);
    }

    /**
     * Der registrierte Hook `getGroupSettingsForUser` liefert `on`; maßgeblich bleibt der
     * gespeicherte Wert `off` der Standard-Mitgliedergruppe.
     */
    public function testHookDoesNotChangeReferenceOfTheRules(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'en';
        $this->installDatabase('off', 'always', []);

        self::assertSame('on', ls_shop_generalHelper::getGroupSettings4User(true)['lsShopGllMode']);

        $this->expectExceptionMessage(
            'field "gllMode": The row was not applied because the manufacturer guarantee is enabled and the legal guarantee notice would not be shown for the standard user group, which only shows it for products set to "always". Please specify "always" for the legal guarantee notice or disable the manufacturer guarantee.'
        );

        ls_shop_productManagementApiHelper::applyLegalGuaranteeProductData($this->createProductRow('inherit', '1'));
    }

    public function testVariantRowUsesStoredStateOfParentProduct(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $this->installDatabase('on', 'never', []);

        $this->expectExceptionMessage(
            'Feld "garanMode": Die Variante wurde nicht übernommen: "override" bei der Herstellergarantie setzt voraus, dass die Mitteilung über die gesetzliche Gewährleistung für das Produkt in der Standard-Benutzergruppe angezeigt wird. Bitte zuerst die Einstellung zur gesetzlichen Gewährleistung am Produkt ändern und die Zeile danach erneut übertragen.'
        );

        ls_shop_productManagementApiHelper::applyLegalGuaranteeVariantData(
            [
                'type' => 'variant',
                'productcode' => 'VAR-NEW',
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => 'ACME',
                'guaranteeModelIdentifier' => 'M-1',
            ],
            self::PRODUCT_ID
        );
    }

    public function testVariantRowIsAcceptedWhenParentProductShowsLegalGuarantee(): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $this->installDatabase('off', 'always', []);

        $variantRow = ls_shop_productManagementApiHelper::applyLegalGuaranteeVariantData(
            [
                'type' => 'variant',
                'productcode' => 'VAR-NEW',
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => 'ACME',
                'guaranteeModelIdentifier' => 'M-1',
            ],
            self::PRODUCT_ID
        );

        self::assertSame('override', $variantRow['garanMode']);
    }

    /**
     * Der Preprocessor prüft `gllMode` wie `garanMode`; Ablehnungen stehen auf dem
     * bestehenden Meldungsweg (`arr_messages[<Zeile>][<Feld>]`) in der Sprache des Requests.
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function preprocessorRejectionProvider(): iterable
    {
        yield 'gllMode, de' => [
            'de',
            'gllMode',
            'Die Zeile wurde nicht übernommen, weil "sometimes" kein zulässiger Wert für die Einstellung zur gesetzlichen Gewährleistung ist. Zulässig sind "inherit", "always" und "never".',
        ];
        yield 'gllMode, en' => [
            'en',
            'gllMode',
            'The row was not applied because "sometimes" is not a valid value for the legal guarantee notice setting. Allowed values are "inherit", "always" and "never".',
        ];
        yield 'garanMode, de' => [
            'de',
            'garanMode',
            'Die Zeile wurde nicht übernommen, weil "sometimes" kein zulässiger Wert für die Herstellergarantie der Variante ist. Zulässig sind "inherit", "disabled" und "override".',
        ];
    }

    #[DataProvider('preprocessorRejectionProvider')]
    public function testPreprocessorRejectsInvalidModeOnExistingMessagePath(
        string $requestLanguage,
        string $fieldName,
        string $expectedMessage
    ): void {
        $GLOBALS['TL_LANGUAGE'] = $requestLanguage;
        $this->installDatabase('on', 'inherit', []);
        $rowType = $fieldName === 'gllMode' ? 'product' : 'variant';

        $preprocessingResult = @ls_shop_productManagementApiPreprocessor::preprocess(
            [['type' => $rowType, 'productcode' => 'ART-1', 'category' => self::CATEGORY_ALIAS, $fieldName => 'sometimes']],
            'apiResource_writeProductData'
        );

        self::assertTrue($preprocessingResult['bln_hasError']);
        self::assertSame($expectedMessage, $preprocessingResult['arr_messages'][1][$fieldName]);
    }

    /**
     * @return iterable<string, array{string, mixed, string}>
     */
    public static function preprocessorGllModeProvider(): iterable
    {
        yield 'Produkt, Feld fehlt' => ['product', null, 'inherit'];
        yield 'Produkt, leer' => ['product', '', 'inherit'];
        yield 'Produkt, always' => ['product', ' always ', 'always'];
        yield 'Produkt, never' => ['product', 'never', 'never'];
        yield 'Variante' => ['variant', 'never', ''];
    }

    #[DataProvider('preprocessorGllModeProvider')]
    public function testPreprocessorNormalizesGllMode(string $rowType, mixed $submittedGllMode, string $expectedGllMode): void
    {
        $GLOBALS['TL_LANGUAGE'] = 'de';
        $this->installDatabase('on', 'inherit', []);
        $dataRow = ['type' => $rowType, 'productcode' => 'ART-1', 'category' => self::CATEGORY_ALIAS];

        if ($submittedGllMode !== null) {
            $dataRow['gllMode'] = $submittedGllMode;
        }

        $preprocessingResult = @ls_shop_productManagementApiPreprocessor::preprocess([$dataRow], 'apiResource_writeProductData');

        self::assertArrayNotHasKey('gllMode', $preprocessingResult['arr_messages'][1] ?? []);
        self::assertSame($expectedGllMode, $preprocessingResult['arr_preprocessedDataRows'][0]['gllMode']);
        self::assertArrayNotHasKey('enableGll', $preprocessingResult['arr_preprocessedDataRows'][0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createProductRow(string $gllMode, string $enableGaran): array
    {
        return [
            'type' => 'product',
            'productcode' => 'ART-1',
            'producer' => 'ACME',
            'gllMode' => $gllMode,
            'enableGaran' => $enableGaran,
            'guaranteeDurationMonths' => $enableGaran === '1' ? '36' : '',
            'guaranteeBrand' => $enableGaran === '1' ? 'ACME' : '',
            'guaranteeModelIdentifier' => $enableGaran === '1' ? 'M-1' : '',
        ];
    }

    /**
     * @param array<string, string> $storedGaranModeByVariantCode
     */
    private function installDatabase(
        string $standardMemberGroupGllMode,
        string $storedProductGllMode,
        array $storedGaranModeByVariantCode
    ): void {
        $this->connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $this->connection->executeStatement('CREATE TABLE tl_member_group (id INTEGER PRIMARY KEY, name TEXT NOT NULL DEFAULT \'\', lsShopGllMode TEXT NULL)');
        $this->connection->executeStatement('
            CREATE TABLE tl_ls_shop_product (
                id INTEGER PRIMARY KEY,
                lsShopProductCode TEXT NOT NULL,
                lsShopProductProducer TEXT NOT NULL DEFAULT \'\',
                gllMode TEXT NOT NULL DEFAULT \'inherit\',
                enableGll TEXT NOT NULL DEFAULT \'1\',
                enableGaran TEXT NOT NULL DEFAULT \'\',
                guaranteeDurationMonths INTEGER NULL,
                guaranteeBrand TEXT NOT NULL DEFAULT \'\',
                guaranteeModelIdentifier TEXT NOT NULL DEFAULT \'\'
            )
        ');
        $this->connection->executeStatement('
            CREATE TABLE tl_ls_shop_variant (
                id INTEGER PRIMARY KEY,
                pid INTEGER NOT NULL,
                title TEXT NOT NULL DEFAULT \'\',
                lsShopVariantCode TEXT NOT NULL,
                garanMode TEXT NOT NULL DEFAULT \'inherit\'
            )
        ');
        $this->connection->insert('tl_member_group', [
            'id' => self::STANDARD_MEMBER_GROUP_ID,
            'name' => 'Gäste',
            'lsShopGllMode' => $standardMemberGroupGllMode,
        ]);
        $this->connection->insert('tl_ls_shop_product', [
            'id' => self::PRODUCT_ID,
            'lsShopProductCode' => 'ART-1',
            'lsShopProductProducer' => 'ACME',
            'gllMode' => $storedProductGllMode,
        ]);

        $variantId = 100;

        foreach ($storedGaranModeByVariantCode as $variantCode => $garanMode) {
            $this->connection->insert('tl_ls_shop_variant', [
                'id' => ++$variantId,
                'pid' => self::PRODUCT_ID,
                'lsShopVariantCode' => $variantCode,
                'garanMode' => $garanMode,
            ]);
        }

        $container = new Container();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.project_dir', $this->temporaryDirectory);
        $container->setParameter('kernel.cache_dir', $this->temporaryDirectory);
        $container->set('database_connection', $this->connection);
        $container->set('contao.resource_finder', new ResourceFinder([self::LANGUAGE_RESOURCE_PATH]));
        $container->set('contao.security.token_checker', new class {
            public function hasFrontendUser(): bool
            {
                return false;
            }
        });

        System::setContainer($container);
        (new ReflectionProperty(Database::class, 'objInstance'))->setValue(null, null);
    }

    private function fetchStoredProductGllMode(): string
    {
        return (string) $this->connection->fetchOne('SELECT gllMode FROM tl_ls_shop_product WHERE id = ?', [self::PRODUCT_ID]);
    }
}

final class LegalGuaranteeProductManagementApiTestHook
{
    /**
     * @param array<string, mixed> $groupSettings
     *
     * @return array<string, mixed>
     */
    public function adjustGroupSettings(array $groupSettings): array
    {
        $groupSettings['lsShopGllMode'] = 'on';

        return $groupSettings;
    }
}
