<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FastFilterEstimateProviderTest extends TestCase
{
    public function testEstimateProviderKeepsValidatedPerformancePatterns(): void
    {
        $sourceCode = (string) file_get_contents(__DIR__ . '/../../src/FastFilter/FastFilterEstimateProvider.php');

        self::assertStringContainsString('CREATE TEMPORARY TABLE', $sourceCode);
        self::assertStringContainsString('ENGINE=MEMORY', $sourceCode);
        self::assertStringContainsString('FORCE INDEX (`attribute_id_attribute_value_id_product_id_variant_id`)', $sourceCode);
        self::assertStringContainsString('HAVING      COUNT(DISTINCT `attribute_id`) = ?', $sourceCode);
        self::assertStringContainsString('createCriteriaSignature', $sourceCode);
        self::assertStringContainsString('tl_ls_shop_fast_filter_index', $sourceCode);
    }
}
