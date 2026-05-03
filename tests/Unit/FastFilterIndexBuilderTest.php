<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterIndexBuilder;
use PHPUnit\Framework\TestCase;

final class FastFilterIndexBuilderTest extends TestCase
{
    public function testRebuildExecutesValidatedMaterializationStatements(): void
    {
        $executedStatements = [];
        $statementResults = [0, 11, 12, 13, 14, 15];
        $databaseConnection = $this->createMock(Connection::class);
        $databaseConnection
            ->method('executeStatement')
            ->willReturnCallback(
                static function (string $statement) use (&$executedStatements, &$statementResults): int {
                    $executedStatements[] = $statement;

                    return array_shift($statementResults);
                }
            );

        $indexBuilder = new FastFilterIndexBuilder($databaseConnection);

        self::assertSame(
            [
                'productUnits' => 11,
                'productAttributeRows' => 12,
                'variantUnits' => 13,
                'variantProductAttributeRows' => 14,
                'variantAttributeRows' => 15,
            ],
            $indexBuilder->rebuild()
        );

        self::assertCount(6, $executedStatements);
        self::assertStringContainsString('TRUNCATE TABLE `tl_ls_shop_fast_filter_index`', $executedStatements[0]);
        self::assertSame(
            5,
            count(
                array_filter(
                    $executedStatements,
                    static fn (string $statement): bool => str_contains(
                        $statement,
                        'INSERT IGNORE INTO `tl_ls_shop_fast_filter_index`'
                    )
                )
            )
        );
        self::assertStringContainsString("`aa`.`parentIsVariant` = '0'", $executedStatements[2]);
        self::assertStringContainsString("`aa`.`parentIsVariant` = '1'", $executedStatements[5]);
    }
}
