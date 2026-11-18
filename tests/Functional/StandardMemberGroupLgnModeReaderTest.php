<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

require_once __DIR__ . '/../../src/Resources/contao/helpers/ls_shop_generalHelper.php';

use Contao\Database;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;
use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;

/**
 * Prüft das Lesen des gespeicherten Werts der Standard-Benutzergruppe über
 * `ls_shop_generalHelper::getGroupSettings()` gegen eine SQLite-Datenbank. Der Hook
 * `getGroupSettingsForUser` ist registriert und darf den Wert nicht ändern.
 */
final class StandardMemberGroupLgnModeReaderTest extends TestCase
{
    private const STANDARD_MEMBER_GROUP_ID = 7;
    private const OTHER_MEMBER_GROUP_ID = 8;

    /**
     * @var array<string, mixed>
     */
    private array $originalGlobals = [];
    private mixed $originalContainer;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['TL_CONFIG', 'MERCONIS_HOOKS', 'merconis_globals'] as $globalName) {
            $this->originalGlobals[$globalName] = $GLOBALS[$globalName] ?? null;
        }

        $this->originalContainer = System::getContainer();

        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = self::STANDARD_MEMBER_GROUP_ID;
        $GLOBALS['MERCONIS_HOOKS'] = [
            'getGroupSettingsForUser' => [[StandardMemberGroupLgnModeReaderTestHook::class, 'adjustGroupSettings']],
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

        $this->resetDatabaseSingleton();

        $containerProperty = new ReflectionProperty(System::class, 'objContainer');
        $containerProperty->setValue(null, $this->originalContainer);

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function storedGroupValueProvider(): iterable
    {
        yield 'on' => ['on', 'on'];
        yield 'off' => ['off', 'off'];
        yield 'blocked' => ['blocked', 'blocked'];
        yield 'leer' => ['', ''];
        yield 'NULL' => [null, ''];
    }

    #[DataProvider('storedGroupValueProvider')]
    public function testStoredValueIsReadWithoutHook(?string $storedLgnMode, string $expectedLgnMode): void
    {
        $this->installContainer($this->createMemberGroupConnection([
            self::STANDARD_MEMBER_GROUP_ID => $storedLgnMode,
            self::OTHER_MEMBER_GROUP_ID => 'on',
        ]));

        self::assertSame($expectedLgnMode, (new StandardMemberGroupLgnMode())->getStoredLgnMode());
    }

    public function testHookAdjustmentDoesNotChangeStoredValue(): void
    {
        $this->installContainer($this->createMemberGroupConnection([self::STANDARD_MEMBER_GROUP_ID => 'off']));

        self::assertSame('on', ls_shop_generalHelper::getGroupSettings4User(true)['lsShopLgnMode']);
        self::assertSame('off', (new StandardMemberGroupLgnMode())->getStoredLgnMode());
    }

    public function testMissingGroupYieldsEmptyValue(): void
    {
        $this->installContainer($this->createMemberGroupConnection([self::OTHER_MEMBER_GROUP_ID => 'on']));

        self::assertSame('', (new StandardMemberGroupLgnMode())->getStoredLgnMode());
    }

    public function testMissingStandardMemberGroupYieldsEmptyValue(): void
    {
        $this->installContainer($this->createMemberGroupConnection([self::STANDARD_MEMBER_GROUP_ID => 'on']));
        unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);

        self::assertSame('', (new StandardMemberGroupLgnMode())->getStoredLgnMode());
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

    /**
     * @param array<int, string|null> $lgnModeByGroupId
     */
    private function createMemberGroupConnection(array $lgnModeByGroupId): Connection
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $connection->executeStatement('
            CREATE TABLE tl_member_group (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL DEFAULT \'\',
                lsShopLgnMode TEXT NULL
            )
        ');

        foreach ($lgnModeByGroupId as $groupId => $lgnMode) {
            $connection->insert('tl_member_group', [
                'id' => $groupId,
                'name' => 'Gruppe ' . $groupId,
                'lsShopLgnMode' => $lgnMode,
            ]);
        }

        return $connection;
    }

    private function resetDatabaseSingleton(): void
    {
        $instanceProperty = new ReflectionProperty(Database::class, 'objInstance');
        $instanceProperty->setValue(null, null);
    }
}

final class StandardMemberGroupLgnModeReaderTestHook
{
    /**
     * @param array<string, mixed> $groupSettings
     *
     * @return array<string, mixed>
     */
    public function adjustGroupSettings(array $groupSettings): array
    {
        $groupSettings['lsShopLgnMode'] = 'on';

        return $groupSettings;
    }
}
