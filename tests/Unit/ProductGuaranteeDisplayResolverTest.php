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
    public function testResolveFollowsMemberGroupAndProductLgnModeForProduct(
        ?string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedShowLgn
    ): void {
        $result = (new ProductGuaranteeDisplayResolver())->resolve(
            $memberGroupLgnMode,
            $this->createCompleteGaranProductData($productLgnMode)
        );

        self::assertSame($expectedShowLgn, $result['showLgn']);
        self::assertSame($expectedShowLgn, $result['showGaran']);
        self::assertSame($expectedShowLgn ? 'ACME' : null, $result['garan']['brand'] ?? null);
    }

    #[DataProvider('memberGroupAndProductLgnModeProvider')]
    public function testResolveUsesParentProductLgnModeForVariant(
        ?string $memberGroupLgnMode,
        string $productLgnMode,
        bool $expectedShowLgn
    ): void {
        $result = (new ProductGuaranteeDisplayResolver())->resolve(
            $memberGroupLgnMode,
            $this->createCompleteGaranProductData($productLgnMode),
            [
                'lgnMode' => 'never' === $productLgnMode ? 'always' : 'never',
                'garanMode' => 'override',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'Variant Model',
                'guaranteeDurationMonths' => '60',
            ]
        );

        self::assertSame($expectedShowLgn, $result['showLgn']);
        self::assertSame($expectedShowLgn, $result['showGaran']);
        self::assertSame($expectedShowLgn ? 'Variant Brand' : null, $result['garan']['brand'] ?? null);
    }

    /**
     * @return iterable<string, array{0: string|null, 1: mixed}>
     */
    public static function unknownLgnModeProvider(): iterable
    {
        yield 'unknown member group value' => ['yes', 'always'];
        yield 'unknown product value' => ['on', 'sometimes'];
        yield 'empty product value' => ['on', ''];
        yield 'missing product value' => ['on', null];
        yield 'legacy enableGll value' => ['on', '1'];
    }

    #[DataProvider('unknownLgnModeProvider')]
    public function testResolveSuppressesOutputForUnknownLgnModeValues(
        ?string $memberGroupLgnMode,
        mixed $productLgnMode
    ): void {
        $productData = $this->createCompleteGaranProductData('inherit');
        unset($productData['lgnMode']);

        if (null !== $productLgnMode) {
            $productData['lgnMode'] = $productLgnMode;
        }

        $result = (new ProductGuaranteeDisplayResolver())->resolve($memberGroupLgnMode, $productData);

        self::assertFalse($result['showLgn']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveIgnoresLegacyEnableGllColumn(): void
    {
        $productData = $this->createCompleteGaranProductData('never');
        $productData['enableGll'] = '1';

        $result = (new ProductGuaranteeDisplayResolver())->resolve('on', $productData);

        self::assertFalse($result['showLgn']);
        self::assertFalse($result['showGaran']);
    }

    public function testResolveShowsLgnWithoutGaranWhenGaranIsDisabled(): void
    {
        $productData = $this->createCompleteGaranProductData('inherit');
        $productData['enableGaran'] = '';

        $result = (new ProductGuaranteeDisplayResolver())->resolve('on', $productData);

        self::assertTrue($result['showLgn']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveUsesProductFlagsWhenNoVariantIsSelected(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve('on', [
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => '  ACME &amp; Co.  ',
            'guaranteeModelIdentifier' => '  Model &#34;42&#34;  ',
            'guaranteeDurationMonths' => '54',
        ]);

        self::assertTrue($result['showLgn']);
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
                'lgnMode' => 'inherit',
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
                'lgnMode' => 'inherit',
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
                'lgnMode' => 'inherit',
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

        self::assertTrue($result['showLgn']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveSuppressesIncompleteGaranOutput(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve('on', [
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model 42',
            'guaranteeDurationMonths' => '54',
        ]);

        self::assertTrue($result['showLgn']);
        self::assertFalse($result['showGaran']);
        self::assertNull($result['garan']);
    }

    public function testResolveAcceptsTraversableProductAndVariantData(): void
    {
        $resolver = new ProductGuaranteeDisplayResolver();

        $result = $resolver->resolve(
            'off',
            new ArrayIterator([
                'lgnMode' => 'always',
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

        self::assertTrue($result['showLgn']);
        self::assertTrue($result['showGaran']);
        self::assertSame('Variant Brand', $result['garan']['brand']);
        self::assertSame('Variant Model', $result['garan']['modelIdentifier']);
        self::assertSame('60', $result['garan']['durationMonths']);
    }

    /**
     * @return array<string, string>
     */
    private function createCompleteGaranProductData(string $productLgnMode): array
    {
        return [
            'lgnMode' => $productLgnMode,
            'enableGaran' => '1',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'X100',
            'guaranteeDurationMonths' => '36',
        ];
    }
}
