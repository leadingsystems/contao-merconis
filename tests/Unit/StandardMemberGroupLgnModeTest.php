<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StandardMemberGroupLgnModeTest extends TestCase
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
    public function testStoredValueOfStandardMemberGroupIsReturned(mixed $storedLgnMode, string $expectedLgnMode): void
    {
        $requestedGroupIds = [];
        $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode(
            static function (int $groupId) use ($storedLgnMode, &$requestedGroupIds): array {
                $requestedGroupIds[] = $groupId;

                return ['id' => $groupId, 'lsShopLgnMode' => $storedLgnMode];
            }
        );

        self::assertSame($expectedLgnMode, $standardMemberGroupLgnMode->getStoredLgnMode());
        self::assertSame([self::STANDARD_MEMBER_GROUP_ID], $requestedGroupIds);
    }

    public function testMissingGroupYieldsEmptyValue(): void
    {
        $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode(static fn (int $groupId): array => []);

        self::assertSame('', $standardMemberGroupLgnMode->getStoredLgnMode());
    }

    public function testMissingGroupResultYieldsEmptyValue(): void
    {
        $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode(static fn (int $groupId): bool => false);

        self::assertSame('', $standardMemberGroupLgnMode->getStoredLgnMode());
    }

    public function testMissingStandardMemberGroupYieldsEmptyValueWithoutReadingAnyGroup(): void
    {
        $requestedGroupIds = [];
        $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode(
            static function (int $groupId) use (&$requestedGroupIds): array {
                $requestedGroupIds[] = $groupId;

                return ['lsShopLgnMode' => 'on'];
            }
        );

        unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
        self::assertSame('', $standardMemberGroupLgnMode->getStoredLgnMode());

        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = '';
        self::assertSame('', $standardMemberGroupLgnMode->getStoredLgnMode());

        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = '0';
        self::assertSame('', $standardMemberGroupLgnMode->getStoredLgnMode());

        self::assertSame([], $requestedGroupIds);
    }

    /**
     * Tabelle aus Spec 27, Abschnitt 4, für alle Kombinationen.
     *
     * @return iterable<string, array{string, mixed, bool}>
     */
    public static function lgnOffProvider(): iterable
    {
        $expectedLgnOffByGroup = [
            'on' => ['inherit' => false, 'always' => false, 'never' => true],
            'off' => ['inherit' => true, 'always' => false, 'never' => true],
            'blocked' => ['inherit' => false, 'always' => false, 'never' => true],
            '' => ['inherit' => false, 'always' => false, 'never' => true],
        ];

        foreach ($expectedLgnOffByGroup as $standardMemberGroupLgnMode => $expectedLgnOffByProductLgnMode) {
            foreach ($expectedLgnOffByProductLgnMode as $productLgnMode => $expectedLgnOff) {
                yield sprintf('Gruppe "%s", Produkt "%s"', $standardMemberGroupLgnMode, $productLgnMode) => [
                    $standardMemberGroupLgnMode,
                    $productLgnMode,
                    $expectedLgnOff,
                ];
            }
        }
    }

    #[DataProvider('lgnOffProvider')]
    public function testLgnOffFollowsSpecTable(string $standardMemberGroupLgnMode, mixed $productLgnMode, bool $expectedLgnOff): void
    {
        self::assertSame(
            $expectedLgnOff,
            (new StandardMemberGroupLgnMode())->isLgnOff($standardMemberGroupLgnMode, $productLgnMode)
        );
    }

    public function testMissingProductValueCountsAsInherit(): void
    {
        $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode();

        self::assertTrue($standardMemberGroupLgnMode->isLgnOff('off', null));
        self::assertTrue($standardMemberGroupLgnMode->isLgnOff('off', ''));
        self::assertFalse($standardMemberGroupLgnMode->isLgnOff('on', null));
        self::assertSame('inherit', $standardMemberGroupLgnMode->normalizeProductLgnMode(null));
        self::assertSame('inherit', $standardMemberGroupLgnMode->normalizeProductLgnMode(' '));
        self::assertSame('never', $standardMemberGroupLgnMode->normalizeProductLgnMode(' never '));
    }

    public function testLgnOffForProductUsesStoredValueOfStandardMemberGroup(): void
    {
        $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode(
            static fn (int $groupId): array => ['lsShopLgnMode' => 'off']
        );

        self::assertTrue($standardMemberGroupLgnMode->isLgnOffForProduct('inherit'));
        self::assertFalse($standardMemberGroupLgnMode->isLgnOffForProduct('always'));
    }

    public function testLgnModeForEnabledGaranIsAlwaysOnlyForGroupOff(): void
    {
        $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode();

        self::assertSame('always', $standardMemberGroupLgnMode->getLgnModeForEnabledGaran('off'));
        self::assertSame('inherit', $standardMemberGroupLgnMode->getLgnModeForEnabledGaran('on'));
        self::assertSame('inherit', $standardMemberGroupLgnMode->getLgnModeForEnabledGaran('blocked'));
        self::assertSame('inherit', $standardMemberGroupLgnMode->getLgnModeForEnabledGaran(''));
    }
}
