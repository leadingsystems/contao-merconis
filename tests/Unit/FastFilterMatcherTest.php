<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterMatcher;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterQueryService;
use PHPUnit\Framework\TestCase;

final class FastFilterMatcherTest extends TestCase
{
    public function testMatchMarksCompleteAndPartialProductMatches(): void
    {
        $queryService = new class extends FastFilterQueryService {
            /**
             * @param list<int> $productIds
             *
             * @return array<int, array<int, bool>>
             */
            public function getUnitsByProduct(array $productIds): array
            {
                return [
                    10 => [0 => true],
                    20 => [2001 => true, 2002 => true],
                    30 => [3001 => true, 3002 => true],
                ];
            }

            /**
             * @param list<int>            $productIds
             * @param array<string, mixed> $criteria
             *
             * @return array<int, array<int, bool>>
             */
            public function getMatchingUnits(array $productIds, array $criteria): array
            {
                return [
                    10 => [0 => true],
                    20 => [2001 => true],
                    30 => [3001 => true, 3002 => true],
                ];
            }
        };

        $matcher = new FastFilterMatcher($queryService);

        self::assertSame(
            [
                'matchedProductIds' => [10, 20, 30],
                'matchedProducts' => [
                    10 => 'complete',
                    20 => 'partial',
                    30 => 'complete',
                ],
                'matchedVariants' => [
                    2001 => true,
                    2002 => false,
                    3001 => true,
                    3002 => true,
                ],
            ],
            $matcher->match([10, 20, 30], ['attributes' => [50 => [5597]], 'producers' => []])
        );
    }
}
