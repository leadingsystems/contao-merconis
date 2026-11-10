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
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen (Rot)',
            ],
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen (Blau)',
            ],
        ]);

        self::assertSame([
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productNames' => ['Kissen (Rot)', 'Kissen (Blau)'],
            ],
        ], $groups);
    }

    public function testGroupEntriesSeparatesDifferentEffectiveValues(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen (Rot)',
            ],
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '48',
                'productName' => 'Kissen (Blau)',
            ],
        ]);

        self::assertCount(2, $groups);
        self::assertSame(['Kissen (Rot)'], $groups[0]['productNames']);
        self::assertSame('36', $groups[0]['durationMonths']);
        self::assertSame(['Kissen (Blau)'], $groups[1]['productNames']);
        self::assertSame('48', $groups[1]['durationMonths']);
    }

    public function testGroupEntriesSeparatesDifferentProductsWithSameValues(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen',
            ],
            [
                'productId' => '20',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Decke',
            ],
        ]);

        self::assertCount(2, $groups);
        self::assertSame('10', $groups[0]['productId']);
        self::assertSame('20', $groups[1]['productId']);
    }

    public function testGroupEntriesPreservesGroupAndNameOrder(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            [
                'productId' => '20',
                'garanVersion' => 'v1.0',
                'brand' => 'Beta',
                'modelIdentifier' => 'B100',
                'durationMonths' => '60',
                'productName' => 'Decke',
            ],
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen (Rot)',
            ],
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen (Blau)',
            ],
        ]);

        self::assertSame('20', $groups[0]['productId']);
        self::assertSame(['Decke'], $groups[0]['productNames']);
        self::assertSame('10', $groups[1]['productId']);
        self::assertSame(['Kissen (Rot)', 'Kissen (Blau)'], $groups[1]['productNames']);
    }

    public function testGroupEntriesSeparatesDifferentVersions(): void
    {
        $grouping = new GaranLabelGrouping();

        $groups = $grouping->groupEntries([
            [
                'productId' => '10',
                'garanVersion' => 'v1.0',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen',
            ],
            [
                'productId' => '10',
                'garanVersion' => 'v1.1',
                'brand' => 'ACME',
                'modelIdentifier' => 'X100',
                'durationMonths' => '36',
                'productName' => 'Kissen',
            ],
        ]);

        self::assertCount(2, $groups);
        self::assertSame('v1.0', $groups[0]['garanVersion']);
        self::assertSame('v1.1', $groups[1]['garanVersion']);
    }
}
