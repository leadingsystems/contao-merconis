<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterConfigurationRepository;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterEstimateProvider;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterMatcher;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterOptionProvider;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterQueryService;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterSearchCoordinator;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterSessionWriter;
use PHPUnit\Framework\TestCase;

final class FastFilterSearchCoordinatorTest extends TestCase
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $previousMerconisGlobals = null;

    protected function setUp(): void
    {
        $this->previousMerconisGlobals = $GLOBALS['merconis_globals'] ?? null;
        $GLOBALS['merconis_globals'] = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if ($this->previousMerconisGlobals === null) {
            unset($GLOBALS['merconis_globals']);
        } else {
            $GLOBALS['merconis_globals'] = $this->previousMerconisGlobals;
        }

        $_SESSION = [];
    }

    public function testApplyWritesEmptyFastFilterStateForEmptyProductScope(): void
    {
        $_SESSION['lsShop']['fastFilter'] = [
            'criteria' => [
                'attributes' => [
                    50 => [5597],
                ],
                'producers' => ['Axxatronic'],
            ],
            'availableOptions' => [
                'attributes' => [
                    50 => [
                        5597 => ['numericValue' => null],
                    ],
                ],
                'producers' => ['Axxatronic'],
            ],
            'matchEstimates' => [
                'attributes' => [
                    50 => [
                        5597 => ['products' => 12],
                    ],
                ],
                'producers' => [
                    'Axxatronic' => ['products' => 12],
                ],
            ],
            'matchedProducts' => [
                10 => 'complete',
            ],
            'matchedVariants' => [
                1001 => true,
            ],
            'legacyBridgeActive' => true,
            'lastResetTimestamp' => 1234567890,
        ];

        $_SESSION['lsShop']['filter'] = [
            'matchedProducts' => [
                10 => 'complete',
            ],
            'matchedVariants' => [
                1001 => true,
            ],
            'matchEstimates' => [
                'attributeValues' => [
                    5597 => ['products' => 12],
                ],
                'flexContentLIValues' => [],
                'flexContentLDValues' => [],
                'producers' => [
                    md5('Axxatronic') => ['products' => 12],
                ],
            ],
        ];

        $coordinator = new FastFilterSearchCoordinator(
            new FastFilterOptionProvider($this->createConfigurationRepository(), new FastFilterQueryService()),
            new FastFilterEstimateProvider($this->createConfigurationRepository()),
            new FastFilterMatcher(new FastFilterQueryService()),
            new FastFilterSessionWriter(),
        );

        self::assertSame(
            [
                'products' => [],
                'notAllProductsMatch' => false,
                'numProductsNotMatching' => 0,
            ],
            $coordinator->apply([])
        );

        self::assertSame(
            ['attributes' => [], 'producers' => []],
            $_SESSION['lsShop']['fastFilter']['availableOptions']
        );
        self::assertSame(
            ['attributes' => [], 'producers' => []],
            $_SESSION['lsShop']['fastFilter']['matchEstimates']
        );
        self::assertSame([], $_SESSION['lsShop']['fastFilter']['matchedProducts']);
        self::assertSame([], $_SESSION['lsShop']['fastFilter']['matchedVariants']);
        self::assertTrue($GLOBALS['merconis_globals'][FastFilterSearchCoordinator::REQUEST_MARKER]);
        self::assertSame([], $_SESSION['lsShop']['filter']['matchedProducts']);
        self::assertSame([], $_SESSION['lsShop']['filter']['matchedVariants']);
        self::assertSame(
            [
                'attributeValues' => [],
                'flexContentLIValues' => [],
                'flexContentLDValues' => [],
                'producers' => [],
            ],
            $_SESSION['lsShop']['filter']['matchEstimates']
        );
    }

    private function createConfigurationRepository(): FastFilterConfigurationRepository
    {
        return new class extends FastFilterConfigurationRepository {
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
                        'disableFilterIfOnlyOneValue' => '',
                    ],
                    2 => [
                        'id' => 2,
                        'dataSource' => 'producer',
                        'sourceAttribute' => 0,
                        'disableFilterIfOnlyOneValue' => '',
                    ],
                ];
            }
        };
    }
}
