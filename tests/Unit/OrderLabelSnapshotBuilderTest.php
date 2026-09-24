<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotBuilder;
use PHPUnit\Framework\TestCase;

final class OrderLabelSnapshotBuilderTest extends TestCase
{
    public function testBuildOrderSnapshotStoresResolvedOfficialLanguageWithEnglishFallback(): void
    {
        $builder = $this->createBuilder();

        $snapshot = $builder->buildOrderSnapshot([
            'customerLanguage' => 'zz_ZZ',
            'items' => [
                [
                    OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY => '1',
                ],
            ],
        ]);

        self::assertSame(OfficialGuaranteeAssetLocator::GLL_VERSION, $snapshot['gllVersion']);
        self::assertSame('en', $snapshot['gllLanguage']);
    }

    public function testBuildItemSnapshotUsesVariantResolvedEffectiveValues(): void
    {
        $builder = $this->createBuilder();

        $snapshot = $builder->buildItemSnapshot(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Produktmarke',
                'guaranteeModelIdentifier' => 'Produktmodell',
                'guaranteeDurationYears' => '2,0',
            ],
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Variantenmarke',
                'guaranteeModelIdentifier' => '',
                'guaranteeDurationYears' => '3,5',
            ]
        );

        self::assertSame('1', $snapshot[OrderLabelSnapshotBuilder::REQUIRES_GLL_ORDER_SNAPSHOT_KEY]);
        self::assertSame(OfficialGuaranteeAssetLocator::GARAN_VERSION, $snapshot['garanVersion']);
        self::assertSame('Variantenmarke', $snapshot['garanBrand']);
        self::assertSame('Produktmodell', $snapshot['garanModelIdentifier']);
        self::assertSame('3.5', $snapshot['garanDurationYears']);
    }

    public function testBuildItemSnapshotLeavesEmptyGaranFieldsWhenConfigurationIsIncomplete(): void
    {
        $builder = $this->createBuilder();

        $snapshot = $builder->buildItemSnapshot([
            'enableGll' => '',
            'enableGaran' => '1',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Modell',
            'guaranteeDurationYears' => '2',
        ]);

        self::assertSame('', $snapshot['garanVersion']);
        self::assertSame('', $snapshot['garanBrand']);
        self::assertSame('', $snapshot['garanModelIdentifier']);
        self::assertSame('', $snapshot['garanDurationYears']);
    }

    private function createBuilder(): OrderLabelSnapshotBuilder
    {
        return new OrderLabelSnapshotBuilder(
            new ProductGuaranteeDisplayResolver(),
            new OfficialGuaranteeAssetLocator(
                dirname(__DIR__, 2),
                new OfficialGuaranteeLanguageResolver(),
            )
        );
    }
}
