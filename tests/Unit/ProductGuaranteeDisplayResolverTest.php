<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use ArrayIterator;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeDisplayResolverTest extends TestCase
{
    public function testResolveUsesProductFlagsWhenNoVariantIsSelected(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve([
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model 42',
            'guaranteeDurationYears' => '4.5',
        ]);

        self::assertTrue($result['showGll']);
        self::assertTrue($result['showGaran']);
        self::assertSame('ACME', $result['garan']['brand']);
        self::assertSame('Model 42', $result['garan']['modelIdentifier']);
        self::assertSame('4.5', $result['garan']['durationYears']);
    }

    public function testResolveUsesVariantOverrideAndProductFallbackValues(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
                'guaranteeDurationYears' => '4.5',
            ],
            [
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationYears' => '',
            ],
        );

        self::assertTrue($result['showGaran']);
        self::assertSame('Parent Brand', $result['garan']['brand']);
        self::assertSame('Variant Model', $result['garan']['modelIdentifier']);
        self::assertSame('4.5', $result['garan']['durationYears']);
    }

    public function testResolveSuppressesIncompleteGaranOutput(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve([
            'enableGll' => '',
            'enableGaran' => '1',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model 42',
            'guaranteeDurationYears' => '4.5',
        ]);

        self::assertFalse($result['showGll']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveAcceptsTraversableProductAndVariantData(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            new ArrayIterator([
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
                'guaranteeDurationYears' => '3',
            ]),
            new ArrayIterator([
                'garanOverride' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationYears' => '5',
            ])
        );

        self::assertTrue($result['showGll']);
        self::assertTrue($result['showGaran']);
        self::assertSame('Variant Brand', $result['garan']['brand']);
        self::assertSame('Variant Model', $result['garan']['modelIdentifier']);
        self::assertSame('5', $result['garan']['durationYears']);
    }
}
