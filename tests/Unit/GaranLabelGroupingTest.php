<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use PHPUnit\Framework\TestCase;

final class GaranLabelGroupingTest extends TestCase
{
    public function testGroupEntriesMergesVariantsOfSameProductWithIdenticalValues(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Kissen', 'Rot', 'ART-101'),
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Kissen', 'Blau', 'ART-102'),
        ]);

        self::assertSame([
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'positions' => [
                    ['productName' => 'Kissen', 'variantName' => 'Rot', 'articleNumber' => 'ART-101'],
                    ['productName' => 'Kissen', 'variantName' => 'Blau', 'articleNumber' => 'ART-102'],
                ],
            ],
        ], $groups);
    }

    public function testGroupEntriesSeparatesDifferentEffectiveValues(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Kissen', 'Rot'),
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '48', 'Kissen', 'Blau'),
        ]);

        self::assertCount(2, $groups);
        self::assertSame('Rot', $groups[0]['positions'][0]['variantName']);
        self::assertSame('36', $groups[0]['durationMonths']);
        self::assertSame('Blau', $groups[1]['positions'][0]['variantName']);
        self::assertSame('48', $groups[1]['durationMonths']);
    }

    public function testGroupEntriesSeparatesDifferentProductsWithSameValues(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Kissen'),
            $this->createEntry('20', 'v1.0', 'ACME', 'X100', '36', 'Decke'),
        ]);

        self::assertCount(2, $groups);
        self::assertSame('10', $groups[0]['productId']);
        self::assertSame('20', $groups[1]['productId']);
    }

    public function testGroupEntriesPreservesGroupAndPositionOrder(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            $this->createEntry('20', 'v1.0', 'Beta', 'B100', '60', 'Decke'),
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Kissen', 'Rot'),
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Kissen', 'Blau'),
        ]);

        self::assertSame('20', $groups[0]['productId']);
        self::assertSame('Decke', $groups[0]['positions'][0]['productName']);
        self::assertSame('10', $groups[1]['productId']);
        self::assertSame(['Rot', 'Blau'], array_column($groups[1]['positions'], 'variantName'));
    }

    public function testGroupEntriesSeparatesDifferentVersions(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Kissen'),
            $this->createEntry('10', 'v1.1', 'ACME', 'X100', '36', 'Kissen'),
        ]);

        self::assertCount(2, $groups);
        self::assertSame('v1.0', $groups[0]['garanVersion']);
        self::assertSame('v1.1', $groups[1]['garanVersion']);
    }

    public function testGroupEntriesDoesNotCollideWhenBrandOrModelContainsPipe(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            $this->createEntry('10', 'v1.0', 'A|B', 'C', '36', 'Produkt Eins'),
            $this->createEntry('10', 'v1.0', 'A', 'B|C', '36', 'Produkt Zwei'),
        ]);

        self::assertCount(2, $groups);
        self::assertSame('A|B', $groups[0]['brand']);
        self::assertSame('C', $groups[0]['modelIdentifier']);
        self::assertSame('Produkt Eins', $groups[0]['positions'][0]['productName']);
        self::assertSame('A', $groups[1]['brand']);
        self::assertSame('B|C', $groups[1]['modelIdentifier']);
        self::assertSame('Produkt Zwei', $groups[1]['positions'][0]['productName']);
    }

    public function testGroupEntriesKeepsPositionPartsSeparateAndTrimmed(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', ' Stehlampe ', ' Alternative 3 ', ' WMBE140040011-03 '),
            $this->createEntry('10', 'v1.0', 'ACME', 'X100', '36', 'Stehlampe', 'Alternative 3', 'WMBE140040011-03'),
        ]);

        self::assertSame([
            ['productName' => 'Stehlampe', 'variantName' => 'Alternative 3', 'articleNumber' => 'WMBE140040011-03'],
            ['productName' => 'Stehlampe', 'variantName' => 'Alternative 3', 'articleNumber' => 'WMBE140040011-03'],
        ], $groups[0]['positions']);
    }

    /**
     * @return array{
     *   productId: string,
     *   garanVersion: string,
     *   brand: string,
     *   modelIdentifier: string,
     *   durationMonths: string,
     *   position: array{productName: string, variantName: string, articleNumber: string}
     * }
     */
    private function createEntry(
        string $productId,
        string $garanVersion,
        string $brand,
        string $modelIdentifier,
        string $durationMonths,
        string $productName,
        string $variantName = '',
        string $articleNumber = ''
    ): array {
        return [
            'productId' => $productId,
            'garanVersion' => $garanVersion,
            'brand' => $brand,
            'modelIdentifier' => $modelIdentifier,
            'durationMonths' => $durationMonths,
            'position' => [
                'productName' => $productName,
                'variantName' => $variantName,
                'articleNumber' => $articleNumber,
            ],
        ];
    }
}
