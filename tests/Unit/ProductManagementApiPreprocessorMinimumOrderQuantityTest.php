<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_productManagementApiPreprocessor;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ProductManagementApiPreprocessorMinimumOrderQuantityTest extends TestCase
{
    public function testFieldDefinitionUsesDedicatedMinimumOrderQuantityPreprocessor(): void
    {
        $fieldDefinitions = ls_shop_productManagementApiPreprocessor::getResourceAndFieldDefinition();

        self::assertSame(
            'preprocess_minimumOrderQuantity',
            $fieldDefinitions['apiResource_writeProductData']['arr_fields']['minimumOrderQuantity']['preprocessor']
        );
    }

    public function testEmptyMinimumOrderQuantityDefaultsToZeroForProducts(): void
    {
        self::assertSame(
            '0',
            $this->invokePreprocessorMethod(
                'preprocess_minimumOrderQuantity',
                '',
                ['type' => 'product'],
                'minimumOrderQuantity'
            )
        );
    }

    public function testMinimumOrderQuantityKeepsDecimalValuesForVariants(): void
    {
        self::assertSame(
            '2.3456',
            $this->invokePreprocessorMethod(
                'preprocess_minimumOrderQuantity',
                '2.3456',
                ['type' => 'variant'],
                'minimumOrderQuantity'
            )
        );
    }

    public function testMinimumOrderQuantityIsIgnoredForLanguageRows(): void
    {
        self::assertSame(
            '',
            $this->invokePreprocessorMethod(
                'preprocess_minimumOrderQuantity',
                '1.5',
                ['type' => 'productLanguage'],
                'minimumOrderQuantity'
            )
        );
    }

    public function testMinimumOrderQuantityRejectsMoreThanFourDecimals(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not a valid minimum order quantity');

        $this->invokePreprocessorMethod(
            'preprocess_minimumOrderQuantity',
            '1.23456',
            ['type' => 'product'],
            'minimumOrderQuantity'
        );
    }

    private function invokePreprocessorMethod(
        string $methodName,
        mixed $input,
        array $row,
        string $fieldName
    ): mixed {
        $method = new ReflectionMethod(ls_shop_productManagementApiPreprocessor::class, $methodName);
        $method->setAccessible(true);

        return $method->invoke(null, $input, $row, $fieldName, 'apiResource_writeProductData', []);
    }
}
