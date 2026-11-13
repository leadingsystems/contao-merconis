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
            'guaranteeBrand' => '  ACME &amp; Co.  ',
            'guaranteeModelIdentifier' => '  Model &#34;42&#34;  ',
            'guaranteeDurationMonths' => '54',
        ]);

        self::assertTrue($result['showGll']);
        self::assertTrue($result['showGaran']);
        self::assertSame('ACME &amp; Co.', $result['garan']['brand']);
        self::assertSame('Model &#34;42&#34;', $result['garan']['modelIdentifier']);
        self::assertSame('54', $result['garan']['durationMonths']);
    }

    public function testResolveUsesVariantOverrideWithoutFieldFallback(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
                'guaranteeDurationMonths' => '54',
            ],
            [
                'garanMode' => 'override',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationMonths' => '60',
            ],
        );

        self::assertTrue($result['showGaran']);
        self::assertSame('Variant Brand', $result['garan']['brand']);
        self::assertSame('Variant Model', $result['garan']['modelIdentifier']);
        self::assertSame('60', $result['garan']['durationMonths']);
    }

    public function testResolveUsesInheritedProductValuesForInheritMode(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
                'guaranteeDurationMonths' => '54',
            ],
            [
                'garanMode' => 'inherit',
                'guaranteeBrand' => 'Ignored Variant Brand',
                'guaranteeModelIdentifier' => 'Ignored Variant Model',
                'guaranteeDurationMonths' => '60',
            ],
        );

        self::assertTrue($result['showGaran']);
        self::assertSame('Parent Brand', $result['garan']['brand']);
        self::assertSame('Parent Model', $result['garan']['modelIdentifier']);
        self::assertSame('54', $result['garan']['durationMonths']);
    }

    public function testResolveSuppressesGaranForDisabledMode(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
                'guaranteeDurationMonths' => '54',
            ],
            [
                'garanMode' => 'disabled',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationMonths' => '60',
            ],
        );

        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveSuppressesIncompleteGaranOutput(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve([
            'enableGll' => '',
            'enableGaran' => '1',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model 42',
            'guaranteeDurationMonths' => '54',
        ]);

        self::assertFalse($result['showGll']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveSuppressesCompleteProductGaranOutputWhenGllIsDisabled(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve([
            'enableGll' => '',
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model 42',
            'guaranteeDurationMonths' => '54',
        ]);

        self::assertFalse($result['showGll']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveSuppressesVariantOverrideGaranOutputWhenParentGllIsDisabled(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            [
                'enableGll' => '',
                'enableGaran' => '1',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'Parent Model',
                'guaranteeDurationMonths' => '54',
            ],
            [
                'garanMode' => 'override',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationMonths' => '60',
            ],
        );

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
                'guaranteeDurationMonths' => '3',
            ]),
            new ArrayIterator([
                'garanMode' => 'override',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationMonths' => '60',
            ])
        );

        self::assertTrue($result['showGll']);
        self::assertTrue($result['showGaran']);
        self::assertSame('Variant Brand', $result['garan']['brand']);
        self::assertSame('Variant Model', $result['garan']['modelIdentifier']);
        self::assertSame('60', $result['garan']['durationMonths']);
    }
}
