<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use ArrayIterator;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\ProductGuaranteeDisplayResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeDisplayResolverTest extends TestCase
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
    public function testResolveFollowsMemberGroupAndProductGllModeForProduct(
        ?string $memberGroupGllMode,
        string $productGllMode,
        bool $expectedShowGll
    ): void {
        $result = (new ProductGuaranteeDisplayResolver())->resolve(
            $memberGroupGllMode,
            $this->createCompleteGaranProductData($productGllMode)
        );

        self::assertSame($expectedShowGll, $result['showGll']);
        self::assertSame($expectedShowGll, $result['showGaran']);
        self::assertSame($expectedShowGll ? 'ACME' : null, $result['garan']['brand'] ?? null);
    }

    #[DataProvider('memberGroupAndProductGllModeProvider')]
    public function testResolveUsesParentProductGllModeForVariant(
        ?string $memberGroupGllMode,
        string $productGllMode,
        bool $expectedShowGll
    ): void {
        $result = (new ProductGuaranteeDisplayResolver())->resolve(
            $memberGroupGllMode,
            $this->createCompleteGaranProductData($productGllMode),
            [
                'gllMode' => 'never' === $productGllMode ? 'always' : 'never',
                'garanMode' => 'override',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationMonths' => '60',
            ]
        );

        self::assertSame($expectedShowGll, $result['showGll']);
        self::assertSame($expectedShowGll, $result['showGaran']);
        self::assertSame($expectedShowGll ? 'Variant Brand' : null, $result['garan']['brand'] ?? null);
    }

    /**
     * @return iterable<string, array{0: string|null, 1: mixed}>
     */
    public static function unknownGllModeProvider(): iterable
    {
        yield 'unknown member group value' => ['yes', 'always'];
        yield 'unknown product value' => ['on', 'sometimes'];
        yield 'empty product value' => ['on', ''];
        yield 'missing product value' => ['on', null];
        yield 'legacy enableGll value' => ['on', '1'];
    }

    #[DataProvider('unknownGllModeProvider')]
    public function testResolveSuppressesOutputForUnknownGllModeValues(
        ?string $memberGroupGllMode,
        mixed $productGllMode
    ): void {
        $productData = $this->createCompleteGaranProductData('inherit');
        unset($productData['gllMode']);

        if (null !== $productGllMode) {
            $productData['gllMode'] = $productGllMode;
        }

        $result = (new ProductGuaranteeDisplayResolver())->resolve($memberGroupGllMode, $productData);

        self::assertFalse($result['showGll']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveIgnoresLegacyEnableGllColumn(): void
    {
        $productData = $this->createCompleteGaranProductData('never');
        $productData['enableGll'] = '1';

        $result = (new ProductGuaranteeDisplayResolver())->resolve('on', $productData);

        self::assertFalse($result['showGll']);
        self::assertFalse($result['showGaran']);
    }

    public function testResolveShowsGllWithoutGaranWhenGaranIsDisabled(): void
    {
        $productData = $this->createCompleteGaranProductData('inherit');
        $productData['enableGaran'] = '';

        $result = (new ProductGuaranteeDisplayResolver())->resolve('on', $productData);

        self::assertTrue($result['showGll']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveUsesProductFlagsWhenNoVariantIsSelected(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve('on', [
            'gllMode' => 'inherit',
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
            'on',
            [
                'gllMode' => 'inherit',
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
            'on',
            [
                'gllMode' => 'inherit',
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
            'on',
            [
                'gllMode' => 'inherit',
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

        self::assertTrue($result['showGll']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveSuppressesIncompleteGaranOutput(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve('on', [
            'gllMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model 42',
            'guaranteeDurationMonths' => '54',
        ]);

        self::assertTrue($result['showGll']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveAcceptsTraversableProductAndVariantData(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            'off',
            new ArrayIterator([
                'gllMode' => 'always',
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

    /**
     * @return array<string, string>
     */
    private function createCompleteGaranProductData(string $productGllMode): array
    {
        return [
            'gllMode' => $productGllMode,
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => '36',
        ];
    }
}
