<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\MemberGroupLgnModeProviderInterface;
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
    public static function memberGroupAndProductLgnModeProvider(): iterable
    {
        $expectedShowLgnByMemberGroupLgnMode = [
            'on' => ['inherit' => true, 'always' => true, 'never' => false],
            'off' => ['inherit' => false, 'always' => true, 'never' => false],
            'blocked' => ['inherit' => false, 'always' => false, 'never' => false],
            'empty' => ['inherit' => false, 'always' => false, 'never' => false],
            'null' => ['inherit' => false, 'always' => false, 'never' => false],
        ];
        $memberGroupLgnModesByCaseName = [
            'on' => 'on',
            'off' => 'off',
            'blocked' => 'blocked',
            'empty' => '',
            'null' => null,
        ];

        foreach ($expectedShowLgnByMemberGroupLgnMode as $memberGroupCaseName => $expectedShowLgnByProductLgnMode) {
            foreach ($expectedShowLgnByProductLgnMode as $productLgnMode => $expectedShowLgn) {
                yield sprintf('group %s, product %s', $memberGroupCaseName, $productLgnMode) => [
                    $memberGroupLgnModesByCaseName[$memberGroupCaseName],
                    $productLgnMode,
                    $expectedShowLgn,
                ];
            }
        }
    }

    #[DataProvider('memberGroupAndProductLgnModeProvider')]
    public function testBuildItemSnapshotFollowsBuyerMemberGroupForProductPosition(
        ?string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedShowLgn
    ): void {
        $snapshot = $this->createBuilder($memberGroupLgnMode)->buildItemSnapshot([
            'lgnMode' => $productLgnMode,
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => '36',
        ]);

        self::assertSame(
            $expectedShowLgn ? '1' : '',
            $snapshot[OrderLabelSnapshotBuilder::REQUIRES_LGN_ORDER_SNAPSHOT_KEY]
        );
        self::assertSame($expectedShowLgn ? OfficialGuaranteeAssetLocator::GARAN_VERSION : '', $snapshot['garanVersion']);
        self::assertSame($expectedShowLgn ? 'ACME' : '', $snapshot['garanBrand']);
        self::assertSame($expectedShowLgn ? 'X100' : '', $snapshot['garanModelIdentifier']);
        self::assertSame($expectedShowLgn ? '36' : null, $snapshot['garanDurationMonths']);
    }

    #[DataProvider('memberGroupAndProductLgnModeProvider')]
    public function testBuildItemSnapshotUsesParentLgnModeForVariantPosition(
        ?string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedShowLgn
    ): void {
        $snapshot = $this->createBuilder($memberGroupLgnMode)->buildItemSnapshot(
            [
                'lgnMode' => $productLgnMode,
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
            $expectedShowLgn ? '1' : '',
            $snapshot[OrderLabelSnapshotBuilder::REQUIRES_LGN_ORDER_SNAPSHOT_KEY]
        );
        self::assertSame($expectedShowLgn ? OfficialGuaranteeAssetLocator::GARAN_VERSION : '', $snapshot['garanVersion']);
        self::assertSame($expectedShowLgn ? 'Variantenmarke' : '', $snapshot['garanBrand']);
        self::assertSame($expectedShowLgn ? 'Variantenmodell' : '', $snapshot['garanModelIdentifier']);
        self::assertSame($expectedShowLgn ? '42' : null, $snapshot['garanDurationMonths']);
    }

    public function testBuildItemSnapshotDoesNotStoreMemberGroupValue(): void
    {
        $snapshot = $this->createBuilder('on')->buildItemSnapshot([
            'lgnMode' => 'inherit',
            'enableGaran' => '',
        ]);

        self::assertSame(
            [
                OrderLabelSnapshotBuilder::REQUIRES_LGN_ORDER_SNAPSHOT_KEY,
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
        $memberGroupLgnModeProvider = new class implements MemberGroupLgnModeProviderInterface {
            public int $callCount = 0;

            public function getCurrentMemberGroupLgnMode(): string
            {
                ++$this->callCount;

                return 'on';
            }
        };
        $builder = $this->createBuilderWithProvider($memberGroupLgnModeProvider);

        $builder->buildItemSnapshot(['lgnMode' => 'inherit']);
        $builder->buildItemSnapshot(['lgnMode' => 'always']);

        self::assertSame(2, $memberGroupLgnModeProvider->callCount);
    }

    public function testBuildOrderSnapshotStoresResolvedOfficialLanguageWithEnglishFallback(): void
    {
        $builder = $this->createBuilder('on');

        $snapshot = $builder->buildOrderSnapshot([
            'customerLanguage' => 'zz_ZZ',
            'items' => [
                [
                    OrderLabelSnapshotBuilder::REQUIRES_LGN_ORDER_SNAPSHOT_KEY => '1',
                ],
            ],
        ]);

        self::assertSame(['lgnVersion', 'lgnLanguage'], array_keys($snapshot));
        self::assertSame(OfficialGuaranteeAssetLocator::LGN_VERSION, $snapshot['lgnVersion']);
        self::assertSame('en', $snapshot['lgnLanguage']);
    }

    public function testBuildItemSnapshotUsesVariantResolvedEffectiveValues(): void
    {
        $builder = $this->createBuilder('on');

        $snapshot = $builder->buildItemSnapshot(
            [
                'lgnMode' => 'inherit',
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

        self::assertSame('1', $snapshot[OrderLabelSnapshotBuilder::REQUIRES_LGN_ORDER_SNAPSHOT_KEY]);
        self::assertSame(OfficialGuaranteeAssetLocator::GARAN_VERSION, $snapshot['garanVersion']);
        self::assertSame('Variantenmarke <S>', $snapshot['garanBrand']);
        self::assertSame('Variantenmodell', $snapshot['garanModelIdentifier']);
        self::assertSame('42', $snapshot['garanDurationMonths']);
    }

    public function testBuildItemSnapshotLeavesEmptyGaranFieldsWhenConfigurationIsIncomplete(): void
    {
        $builder = $this->createBuilder('on');

        $snapshot = $builder->buildItemSnapshot([
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Modell',
            'guaranteeDurationMonths' => '24',
        ]);

        self::assertSame('1', $snapshot[OrderLabelSnapshotBuilder::REQUIRES_LGN_ORDER_SNAPSHOT_KEY]);
        self::assertSame('', $snapshot['garanVersion']);
        self::assertSame('', $snapshot['garanBrand']);
        self::assertSame('', $snapshot['garanModelIdentifier']);
        self::assertSame(null, $snapshot['garanDurationMonths']);
    }

    private function createBuilder(?string $memberGroupLgnMode): OrderLabelSnapshotBuilder
    {
        return $this->createBuilderWithProvider(new class ($memberGroupLgnMode) implements MemberGroupLgnModeProviderInterface {
            public function __construct(private readonly ?string $memberGroupLgnMode)
            {
            }

            public function getCurrentMemberGroupLgnMode(): string
            {
                return trim((string) $this->memberGroupLgnMode);
            }
        });
    }

    private function createBuilderWithProvider(
        MemberGroupLgnModeProviderInterface $memberGroupLgnModeProvider
    ): OrderLabelSnapshotBuilder {
        return new OrderLabelSnapshotBuilder(
            new ProductGuaranteeDisplayResolver(),
            new OfficialGuaranteeAssetLocator(
                dirname(__DIR__, 2),
                new OfficialGuaranteeLanguageResolver(),
            ),
            $memberGroupLgnModeProvider
        );
    }
}
