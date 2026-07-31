<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;

final class IdentifierFieldCapacityTest extends TestCase
{
    private string $productDcaSource;
    private string $variantDcaSource;

    protected function setUp(): void
    {
        $productDcaSource = file_get_contents(
            \dirname(__DIR__, 3) . '/src/Resources/contao/dca/tl_ls_shop_product.php'
        );
        $variantDcaSource = file_get_contents(
            \dirname(__DIR__, 3) . '/src/Resources/contao/dca/tl_ls_shop_variant.php'
        );

        if (!is_string($productDcaSource) || !is_string($variantDcaSource)) {
            throw new \RuntimeException('Merconis DCA source files could not be read.');
        }

        $this->productDcaSource = $productDcaSource;
        $this->variantDcaSource = $variantDcaSource;
    }

    public function testActiveProductIdentifiersProvideAtLeastRequiredCapacity(): void
    {
        self::assertGreaterThanOrEqual(
            64,
            $this->fieldVarcharLength($this->productDcaSource, 'lsShopProductCode')
        );
        self::assertGreaterThanOrEqual(64, $this->fieldVarcharLength($this->productDcaSource, 'mpn'));
        self::assertGreaterThanOrEqual(
            20,
            $this->fieldVarcharLength($this->productDcaSource, 'lsShopProductProducer')
        );
    }

    public function testActiveVariantIdentifiersProvideAtLeastRequiredCapacity(): void
    {
        self::assertGreaterThanOrEqual(
            64,
            $this->fieldVarcharLength($this->variantDcaSource, 'lsShopVariantCode')
        );
        self::assertGreaterThanOrEqual(64, $this->fieldVarcharLength($this->variantDcaSource, 'mpn'));
    }

    public function testExpandedComparisonUnitPreservesItsNonNullableContract(): void
    {
        self::assertMatchesRegularExpression(
            "/'lsShopVariantMengenvergleichUnit'\\s*=>\\s*array\\s*\\(.*?"
            . "'sql'\\s*=>\\s*\"text NOT NULL default ''\"/s",
            $this->variantDcaSource
        );
    }

    private function fieldVarcharLength(string $dcaSource, string $fieldName): int
    {
        $fieldPattern = sprintf(
            "/'%s'\\s*=>\\s*array\\s*\\(.*?'sql'\\s*=>\\s*\"varchar\\((\\d+)\\)/s",
            preg_quote($fieldName, '/')
        );

        if (preg_match($fieldPattern, $dcaSource, $matches) !== 1) {
            throw new \RuntimeException(sprintf('No varchar length found for DCA field "%s".', $fieldName));
        }

        return (int) $matches[1];
    }
}
