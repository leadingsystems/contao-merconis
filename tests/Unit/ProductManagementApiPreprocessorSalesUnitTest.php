<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_productManagementApiPreprocessor;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ProductManagementApiPreprocessorSalesUnitTest extends TestCase
{
    public function testSalesUnitSizeKeepsZeroValueForProducts(): void
    {
        self::assertSame(
            0,
            $this->invokePreprocessorMethod(
                'preprocess_salesUnitSize',
                '0',
                ['type' => 'product'],
                'salesUnitSize'
            )
        );
    }

    public function testSalesUnitSizeCastsVariantValuesToPositiveIntegers(): void
    {
        self::assertSame(
            250,
            $this->invokePreprocessorMethod(
                'preprocess_salesUnitSize',
                '250',
                ['type' => 'variant'],
                'salesUnitSize'
            )
        );
    }

    public function testSalesUnitSizeIsIgnoredForLanguageRows(): void
    {
        self::assertSame(
            '',
            $this->invokePreprocessorMethod(
                'preprocess_salesUnitSize',
                '100',
                ['type' => 'productLanguage'],
                'salesUnitSize'
            )
        );
    }

    public function testSalesUnitUsesTrimmedStringPreprocessorAndDefinition(): void
    {
        $fieldDefinitions = ls_shop_productManagementApiPreprocessor::getResourceAndFieldDefinition();

        self::assertSame(
            'preprocess_string_maxlength_255',
            $fieldDefinitions['apiResource_writeProductData']['arr_fields']['salesUnit']['preprocessor']
        );
        self::assertSame(
            'Pack',
            $this->invokePreprocessorMethod(
                'preprocess_string_maxlength_255',
                ' Pack ',
                ['type' => 'productLanguage'],
                'salesUnit'
            )
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
