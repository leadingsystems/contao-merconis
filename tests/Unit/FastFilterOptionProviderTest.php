<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterConfigurationRepository;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterOptionProvider;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterQueryService;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class FastFilterOptionProviderTest extends TestCase
{
    public function testBuildAvailableOptionsUsesAggregateCountsForSingleValueFields(): void
    {
        $configurationRepository = new class extends FastFilterConfigurationRepository {
            /**
             * @return array<int, array<string, mixed>>
             */
            public function getActiveFields(): array
            {
                return [
                    1 => [
                        'id' => 1,
                        'dataSource' => 'attribute',
                        'sourceAttribute' => 50,
                        'disableFilterIfOnlyOneValue' => '1',
                    ],
                    2 => [
                        'id' => 2,
                        'dataSource' => 'attribute',
                        'sourceAttribute' => 1008,
                        'disableFilterIfOnlyOneValue' => '1',
                    ],
                    3 => [
                        'id' => 3,
                        'dataSource' => 'attribute',
                        'sourceAttribute' => 22,
                        'disableFilterIfOnlyOneValue' => '',
                    ],
                ];
            }
        };

        $queryService = new class extends FastFilterQueryService {
            /**
             * @var list<int>
             */
            public array $requestedAttributeIds = [];

            /**
             * @param list<int> $productIds
             * @param list<int> $attributeIds
             *
             * @return array<int, int>
             */
            public function getDistinctAttributeValueCounts(array $productIds, array $attributeIds): array
            {
                Assert::assertSame([10, 20], $productIds);
                Assert::assertSame([50, 1008], $attributeIds);

                return [
                    50 => 1,
                    1008 => 2,
                ];
            }

            /**
             * @param list<int> $productIds
             * @param list<int> $attributeIds
             *
             * @return array<int, array<int, array{numericValue: string|null}>>
             */
            public function getAvailableAttributeOptions(array $productIds, array $attributeIds): array
            {
                $this->requestedAttributeIds = $attributeIds;

                return [];
            }

            /**
             * @param list<int> $productIds
             *
             * @return list<string>
             */
            public function getAvailableProducers(array $productIds): array
            {
                return [];
            }
        };

        $optionProvider = new FastFilterOptionProvider($configurationRepository, $queryService);

        $optionProvider->buildAvailableOptions([10, 20]);

        self::assertSame([1008, 22], $queryService->requestedAttributeIds);
    }
}
