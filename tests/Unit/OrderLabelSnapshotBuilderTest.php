<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupGllModeProviderInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderLabelSnapshotBuilderTest extends TestCase
{
    /**
     * Tabelle aus Spec 27, Abschnitt 3.5; leer und `NULL` wirken wie `blocked`.
     *
     * @return iterable<string, array{0: string|null, 1: string, 2: bool}>
     */
    public static function memberGroupAndProductGllModeProvider(): iterable
    {
        $expectedShowGllByMemberGroupGllMode = [
            'on' => ['inherit' => true, 'always' => true, 'never' => false],
            'off' => ['inherit' => false, 'always' => true, 'never' => false],
            'blocked' => ['inherit' => false, 'always' => false, 'never' => false],
            'empty' => ['inherit' => false, 'always' => false, 'never' => false],
            'null' => ['inherit' => false, 'always' => false, 'never' => false],
        ];
        $memberGroupGllModesByCaseName = [
            'on' => 'on',
            'off' => 'off',
            'blocked' => 'blocked',
            'empty' => '',
            'null' => null,
        ];

        foreach ($expectedShowGllByMemberGroupGllMode as $memberGroupCaseName => $expectedShowGllByProductGllMode) {
            foreach ($expectedShowGllByProductGllMode as $productGllMode => $expectedShowGll) {
                yield sprintf('group %s, product %s', $memberGroupCaseName, $productGllMode) => [
                    $memberGroupGllModesByCaseName[$memberGroupCaseName],
                    $productGllMode,
                    $expectedShowGll,
                ];
            }
        }
    }

    #[DataProvider('memberGroupAndProductGllModeProvider')]
    public function testBuildItemSnapshotFollowsBuyerMemberGroupForProductPosition(
        ?string $memberGroupGllMode,
        string $productGllMode,
        bool $expectedShowGll
    ): void {
        $snapshot = $this->createBuilder($memberGroupGllMode)->buildItemSnapshot([
            'gllMode' => $productGllMode,
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => '36',
        ]);

        self::assertSame(
            $expectedShowGll ? '1' : '',
            $snapshot[OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY]
        );
        self::assertSame($expectedShowGll ? OfficialGuaranteeAssetLocator::GARAN_VERSION : '', $snapshot['garanVersion']);
        self::assertSame($expectedShowGll ? 'ACME' : '', $snapshot['garanBrand']);
        self::assertSame($expectedShowGll ? 'X100' : '', $snapshot['garanModelIdentifier']);
        self::assertSame($expectedShowGll ? '36' : null, $snapshot['garanDurationMonths']);
    }

    #[DataProvider('memberGroupAndProductGllModeProvider')]
    public function testBuildItemSnapshotUsesParentGllModeForVariantPosition(
        ?string $memberGroupGllMode,
        string $productGllMode,
        bool $expectedShowGll
    ): void {
        $snapshot = $this->createBuilder($memberGroupGllMode)->buildItemSnapshot(
            [
                'gllMode' => $productGllMode,
                'enableGaran' => '',
            ],
            [
                'garanMode' => 'override',
                'guaranteeBrand' => 'Variantenmarke',
                'guaranteeModelIdentifier' => 'Variantenmodell',
                'guaranteeDurationMonths' => '42',
            ]
        );

        self::assertSame(
            $expectedShowGll ? '1' : '',
            $snapshot[OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY]
        );
        self::assertSame($expectedShowGll ? OfficialGuaranteeAssetLocator::GARAN_VERSION : '', $snapshot['garanVersion']);
        self::assertSame($expectedShowGll ? 'Variantenmarke' : '', $snapshot['garanBrand']);
        self::assertSame($expectedShowGll ? 'Variantenmodell' : '', $snapshot['garanModelIdentifier']);
        self::assertSame($expectedShowGll ? '42' : null, $snapshot['garanDurationMonths']);
    }

    public function testBuildItemSnapshotDoesNotStoreMemberGroupValue(): void
    {
        $snapshot = $this->createBuilder('on')->buildItemSnapshot([
            'gllMode' => 'inherit',
            'enableGaran' => '',
        ]);

        self::assertSame(
            [
                OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY,
                'garanVersion',
                'garanBrand',
                'garanModelIdentifier',
                'garanDurationMonths',
            ],
            array_keys($snapshot)
        );
        self::assertNotContains('on', $snapshot);
    }

    public function testBuildItemSnapshotReadsMemberGroupValueForEveryPosition(): void
    {
        $memberGroupGllModeProvider = new class implements MemberGroupGllModeProviderInterface {
            public int $callCount = 0;

            public function getCurrentMemberGroupGllMode(): string
            {
                ++$this->callCount;

                return 'on';
            }
        };
        $builder = $this->createBuilderWithProvider($memberGroupGllModeProvider);

        $builder->buildItemSnapshot(['gllMode' => 'inherit']);
        $builder->buildItemSnapshot(['gllMode' => 'always']);

        self::assertSame(2, $memberGroupGllModeProvider->callCount);
    }

    public function testBuildOrderSnapshotStoresResolvedOfficialLanguageWithEnglishFallback(): void
    {
        $builder = $this->createBuilder('on');

        $snapshot = $builder->buildOrderSnapshot([
            'customerLanguage' => 'zz_ZZ',
            'items' => [
                [
                    OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY => '1',
                ],
            ],
        ]);

        self::assertSame(['gllVersion', 'gllLanguage'], array_keys($snapshot));
        self::assertSame(OfficialGuaranteeAssetLocator::GLL_VERSION, $snapshot['gllVersion']);
        self::assertSame('en', $snapshot['gllLanguage']);
    }

    public function testBuildItemSnapshotUsesVariantResolvedEffectiveValues(): void
    {
        $builder = $this->createBuilder('on');

        $snapshot = $builder->buildItemSnapshot(
            [
                'gllMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Produkt & Marke',
                'guaranteeModelIdentifier' => 'Produktmodell "A"',
                'guaranteeDurationMonths' => '36',
            ],
            [
                'garanMode' => 'override',
                'guaranteeBrand' => 'Variantenmarke <S>',
                'guaranteeModelIdentifier' => 'Variantenmodell',
                'guaranteeDurationMonths' => '42',
            ]
        );

        self::assertSame('1', $snapshot[OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY]);
        self::assertSame(OfficialGuaranteeAssetLocator::GARAN_VERSION, $snapshot['garanVersion']);
        self::assertSame('Variantenmarke <S>', $snapshot['garanBrand']);
        self::assertSame('Variantenmodell', $snapshot['garanModelIdentifier']);
        self::assertSame('42', $snapshot['garanDurationMonths']);
    }

    public function testBuildItemSnapshotLeavesEmptyGaranFieldsWhenConfigurationIsIncomplete(): void
    {
        $builder = $this->createBuilder('on');

        $snapshot = $builder->buildItemSnapshot([
            'gllMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Modell',
            'guaranteeDurationMonths' => '24',
        ]);

        self::assertSame('1', $snapshot[OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY]);
        self::assertSame('', $snapshot['garanVersion']);
        self::assertSame('', $snapshot['garanBrand']);
        self::assertSame('', $snapshot['garanModelIdentifier']);
        self::assertSame(null, $snapshot['garanDurationMonths']);
    }

    private function createBuilder(?string $memberGroupGllMode): OrderLabelSnapshotBuilder
    {
        return $this->createBuilderWithProvider(new class ($memberGroupGllMode) implements MemberGroupGllModeProviderInterface {
            public function __construct(private readonly ?string $memberGroupGllMode)
            {
            }

            public function getCurrentMemberGroupGllMode(): string
            {
                return trim((string) $this->memberGroupGllMode);
            }
        });
    }

    private function createBuilderWithProvider(
        MemberGroupGllModeProviderInterface $memberGroupGllModeProvider
    ): OrderLabelSnapshotBuilder {
        return new OrderLabelSnapshotBuilder(
            new ProductGuaranteeDisplayResolver(),
            new OfficialGuaranteeAssetLocator(
                dirname(__DIR__, 2),
                new OfficialGuaranteeLanguageResolver(),
            ),
            $memberGroupGllModeProvider
        );
    }
}
