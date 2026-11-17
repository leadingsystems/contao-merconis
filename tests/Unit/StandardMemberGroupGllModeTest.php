<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupGllMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StandardMemberGroupGllModeTest extends TestCase
{
    private const STANDARD_MEMBER_GROUP_ID = 7;

    private mixed $originalTlConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = self::STANDARD_MEMBER_GROUP_ID;
    }

    protected function tearDown(): void
    {
        if ($this->originalTlConfig === null) {
            unset($GLOBALS['TL_CONFIG']);
        } else {
            $GLOBALS['TL_CONFIG'] = $this->originalTlConfig;
        }

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{mixed, string}>
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
    public function testStoredValueOfStandardMemberGroupIsReturned(mixed $storedGllMode, string $expectedGllMode): void
    {
        $requestedGroupIds = [];
        $standardMemberGroupGllMode = new StandardMemberGroupGllMode(
            static function (int $groupId) use ($storedGllMode, &$requestedGroupIds): array {
                $requestedGroupIds[] = $groupId;

                return ['id' => $groupId, 'lsShopGllMode' => $storedGllMode];
            }
        );

        self::assertSame($expectedGllMode, $standardMemberGroupGllMode->getStoredGllMode());
        self::assertSame([self::STANDARD_MEMBER_GROUP_ID], $requestedGroupIds);
    }

    public function testMissingGroupYieldsEmptyValue(): void
    {
        $standardMemberGroupGllMode = new StandardMemberGroupGllMode(static fn (int $groupId): array => []);

        self::assertSame('', $standardMemberGroupGllMode->getStoredGllMode());
    }

    public function testMissingGroupResultYieldsEmptyValue(): void
    {
        $standardMemberGroupGllMode = new StandardMemberGroupGllMode(static fn (int $groupId): bool => false);

        self::assertSame('', $standardMemberGroupGllMode->getStoredGllMode());
    }

    public function testMissingStandardMemberGroupYieldsEmptyValueWithoutReadingAnyGroup(): void
    {
        $requestedGroupIds = [];
        $standardMemberGroupGllMode = new StandardMemberGroupGllMode(
            static function (int $groupId) use (&$requestedGroupIds): array {
                $requestedGroupIds[] = $groupId;

                return ['lsShopGllMode' => 'on'];
            }
        );

        unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
        self::assertSame('', $standardMemberGroupGllMode->getStoredGllMode());

        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = '';
        self::assertSame('', $standardMemberGroupGllMode->getStoredGllMode());

        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = '0';
        self::assertSame('', $standardMemberGroupGllMode->getStoredGllMode());

        self::assertSame([], $requestedGroupIds);
    }

    /**
     * Tabelle aus Spec 27, Abschnitt 4, für alle Kombinationen.
     *
     * @return iterable<string, array{string, mixed, bool}>
     */
    public static function gllOffProvider(): iterable
    {
        $expectedGllOffByGroup = [
            'on' => ['inherit' => false, 'always' => false, 'never' => true],
            'off' => ['inherit' => true, 'always' => false, 'never' => true],
            'blocked' => ['inherit' => false, 'always' => false, 'never' => true],
            '' => ['inherit' => false, 'always' => false, 'never' => true],
        ];

        foreach ($expectedGllOffByGroup as $standardMemberGroupGllMode => $expectedGllOffByProductGllMode) {
            foreach ($expectedGllOffByProductGllMode as $productGllMode => $expectedGllOff) {
                yield sprintf('Gruppe "%s", Produkt "%s"', $standardMemberGroupGllMode, $productGllMode) => [
                    $standardMemberGroupGllMode,
                    $productGllMode,
                    $expectedGllOff,
                ];
            }
        }
    }

    #[DataProvider('gllOffProvider')]
    public function testGllOffFollowsSpecTable(string $standardMemberGroupGllMode, mixed $productGllMode, bool $expectedGllOff): void
    {
        self::assertSame(
            $expectedGllOff,
            (new StandardMemberGroupGllMode())->isGllOff($standardMemberGroupGllMode, $productGllMode)
        );
    }

    public function testMissingProductValueCountsAsInherit(): void
    {
        $standardMemberGroupGllMode = new StandardMemberGroupGllMode();

        self::assertTrue($standardMemberGroupGllMode->isGllOff('off', null));
        self::assertTrue($standardMemberGroupGllMode->isGllOff('off', ''));
        self::assertFalse($standardMemberGroupGllMode->isGllOff('on', null));
        self::assertSame('inherit', $standardMemberGroupGllMode->normalizeProductGllMode(null));
        self::assertSame('inherit', $standardMemberGroupGllMode->normalizeProductGllMode(' '));
        self::assertSame('never', $standardMemberGroupGllMode->normalizeProductGllMode(' never '));
    }

    public function testGllOffForProductUsesStoredValueOfStandardMemberGroup(): void
    {
        $standardMemberGroupGllMode = new StandardMemberGroupGllMode(
            static fn (int $groupId): array => ['lsShopGllMode' => 'off']
        );

        self::assertTrue($standardMemberGroupGllMode->isGllOffForProduct('inherit'));
        self::assertFalse($standardMemberGroupGllMode->isGllOffForProduct('always'));
    }

    public function testGllModeForEnabledGaranIsAlwaysOnlyForGroupOff(): void
    {
        $standardMemberGroupGllMode = new StandardMemberGroupGllMode();

        self::assertSame('always', $standardMemberGroupGllMode->getGllModeForEnabledGaran('off'));
        self::assertSame('inherit', $standardMemberGroupGllMode->getGllModeForEnabledGaran('on'));
        self::assertSame('inherit', $standardMemberGroupGllMode->getGllModeForEnabledGaran('blocked'));
        self::assertSame('inherit', $standardMemberGroupGllMode->getGllModeForEnabledGaran(''));
    }
}
