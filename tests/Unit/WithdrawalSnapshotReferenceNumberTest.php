<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenBProcessor;
use PHPUnit\Framework\TestCase;

final class WithdrawalSnapshotReferenceNumberTest extends TestCase
{
    private WithdrawalScreenBProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new WithdrawalScreenBProcessor();
    }

    public function testConfiguratorReferenceNumberCopiedWhenHasValueSet(): void
    {
        $orderItem = [
            'configurator_hasValue' => '1',
            'configurator_referenceNumber' => 'A3F7B2C1',
        ];

        self::assertSame('A3F7B2C1', $this->processor->resolveConfiguratorReferenceNumber($orderItem));
    }

    public function testConfiguratorReferenceNumberEmptyWhenHasValueNotSet(): void
    {
        $orderItem = [
            'configurator_hasValue' => '',
            'configurator_referenceNumber' => 'A3F7B2C1',
        ];

        self::assertSame('', $this->processor->resolveConfiguratorReferenceNumber($orderItem));
    }

    public function testConfiguratorReferenceNumberEmptyWhenHasValueMissing(): void
    {
        $orderItem = [
            'configurator_referenceNumber' => 'A3F7B2C1',
        ];

        self::assertSame('', $this->processor->resolveConfiguratorReferenceNumber($orderItem));
    }

    public function testConfiguratorReferenceNumberEmptyWhenHasValueZero(): void
    {
        $orderItem = [
            'configurator_hasValue' => '0',
            'configurator_referenceNumber' => 'A3F7B2C1',
        ];

        self::assertSame('', $this->processor->resolveConfiguratorReferenceNumber($orderItem));
    }

    public function testCustomizerReferenceNumberCopiedWhenPresent(): void
    {
        $orderItem = [
            'customizer_hasCustomization' => '1',
            'customizer_referenceNumber' => 'B4E8C3D2',
        ];

        self::assertSame('B4E8C3D2', $this->processor->resolveCustomizerReferenceNumber($orderItem));
    }

    public function testCustomizerReferenceNumberEmptyWhenHasCustomizationNotSet(): void
    {
        $orderItem = [
            'customizer_hasCustomization' => '',
            'customizer_referenceNumber' => 'B4E8C3D2',
        ];

        self::assertSame('', $this->processor->resolveCustomizerReferenceNumber($orderItem));
    }

    public function testCustomizerReferenceNumberEmptyWhenHasCustomizationMissing(): void
    {
        $orderItem = [];

        self::assertSame('', $this->processor->resolveCustomizerReferenceNumber($orderItem));
    }

    public function testCustomizerFallbackHashComputedForLegacyOrderWithoutReferenceNumber(): void
    {
        $summary = 'Farbe: Rot, Größe: XL';
        $flexData = '{"color":"red","size":"xl"}';
        $expectedHash = strtoupper(substr(md5($summary . $flexData), 0, 8));

        $orderItem = [
            'customizer_hasCustomization' => '1',
            'customizer_referenceNumber' => '',
            'customizer_summary' => $summary,
            'customizer_flexData' => $flexData,
        ];

        self::assertSame($expectedHash, $this->processor->resolveCustomizerReferenceNumber($orderItem));
        self::assertSame(8, strlen($this->processor->resolveCustomizerReferenceNumber($orderItem)));
        self::assertMatchesRegularExpression('/^[0-9A-F]{8}$/', $this->processor->resolveCustomizerReferenceNumber($orderItem));
    }

    public function testCustomizerFallbackHashComputedWhenReferenceNumberFieldMissing(): void
    {
        $summary = 'Stoff: Leinen';
        $flexData = '{"fabric":"linen"}';
        $expectedHash = strtoupper(substr(md5($summary . $flexData), 0, 8));

        $orderItem = [
            'customizer_hasCustomization' => '1',
            'customizer_summary' => $summary,
            'customizer_flexData' => $flexData,
        ];

        self::assertSame($expectedHash, $this->processor->resolveCustomizerReferenceNumber($orderItem));
    }

    public function testOrderItemWithoutConfigurationReceivesEmptySnapshotFields(): void
    {
        $orderItem = [
            'configurator_hasValue' => '',
            'customizer_hasCustomization' => '',
        ];

        self::assertSame('', $this->processor->resolveConfiguratorReferenceNumber($orderItem));
        self::assertSame('', $this->processor->resolveCustomizerReferenceNumber($orderItem));
    }

    public function testOrderItemWithBothConfigurationsReturnsRespectiveValues(): void
    {
        $orderItem = [
            'configurator_hasValue' => '1',
            'configurator_referenceNumber' => 'CFG12345',
            'customizer_hasCustomization' => '1',
            'customizer_referenceNumber' => 'CUS67890',
        ];

        self::assertSame('CFG12345', $this->processor->resolveConfiguratorReferenceNumber($orderItem));
        self::assertSame('CUS67890', $this->processor->resolveCustomizerReferenceNumber($orderItem));
    }
}
