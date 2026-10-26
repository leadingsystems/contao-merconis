<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Support\StoredSepaMandateFormatter;
use PHPUnit\Framework\TestCase;

final class StoredSepaMandateFormatterTest extends TestCase
{
    public function testDetectsStoredMandateByMandateReference(): void
    {
        self::assertTrue(StoredSepaMandateFormatter::hasStoredMandate([
            EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD => 'MANDATE-2026-0001',
        ]));

        self::assertFalse(StoredSepaMandateFormatter::hasStoredMandate([
            EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD => '',
        ]));
    }

    public function testResolveAccountHolderPrefersCompanyAndFallsBackToFullName(): void
    {
        self::assertSame(
            'Merconis GmbH',
            StoredSepaMandateFormatter::resolveAccountHolder([
                'company' => 'Merconis GmbH',
                'firstname' => 'Lisa',
                'lastname' => 'Beispiel',
            ])
        );

        self::assertSame(
            'Lisa Beispiel',
            StoredSepaMandateFormatter::resolveAccountHolder([
                'firstname' => 'Lisa',
                'lastname' => 'Beispiel',
            ])
        );
    }

    public function testMaskIbanKeepsOnlyPrefixAndSuffixVisible(): void
    {
        self::assertSame(
            'DE44 **** **** **** **** 4931',
            StoredSepaMandateFormatter::maskIban('DE44 5001 0517 5407 3249 31')
        );
    }
}
