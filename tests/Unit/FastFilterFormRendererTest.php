<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterConfigurationRepository;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterFormRenderer;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterFormService;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterSearchCoordinator;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterSessionWriter;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FastFilterFormRendererTest extends TestCase
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

    public function testViewModelIgnoresStaleSessionWhenCoordinatorDidNotRunInCurrentRequest(): void
    {
        $this->prepareStaleFastFilterSession();

        self::assertSame(
            [
                'fields' => [],
                'activeCount' => 0,
                'hasOptions' => false,
            ],
            $this->buildViewModelForCurrentRequest()
        );
    }

    public function testViewModelUsesSessionWhenCoordinatorRanInCurrentRequest(): void
    {
        $this->prepareStaleFastFilterSession();
        $GLOBALS['merconis_globals'][FastFilterSearchCoordinator::REQUEST_MARKER] = true;

        $viewModel = $this->buildViewModelForCurrentRequest();

        self::assertTrue($viewModel['hasOptions']);
        self::assertSame(1, $viewModel['activeCount']);
        self::assertSame(1, $viewModel['fields'][0]['id']);
        self::assertSame(5597, $viewModel['fields'][0]['options'][0]['value']);
    }

    public function testViewModelCountsOnlyCurrentlyAvailableCriteria(): void
    {
        $this->prepareFastFilterSessionWithUnavailableCriteria();
        $GLOBALS['merconis_globals'][FastFilterSearchCoordinator::REQUEST_MARKER] = true;

        $viewModel = $this->buildViewModelForCurrentRequest();

        self::assertSame(0, $viewModel['activeCount']);
        self::assertFalse($viewModel['fields'][0]['options'][0]['checked']);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewModelForCurrentRequest(): array
    {
        $renderer = new FastFilterFormRenderer(
            new FastFilterFormService(
                $this->createConfigurationRepository(),
                new FastFilterSessionWriter(),
            ),
        );

        $reflectionMethod = new ReflectionMethod(FastFilterFormRenderer::class, 'buildViewModelForCurrentRequest');
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke($renderer);
    }

    private function prepareStaleFastFilterSession(): void
    {
        $_SESSION['lsShop']['fastFilter'] = [
            'criteria' => [
                'attributes' => [
                    50 => [5597],
                ],
                'producers' => [],
            ],
            'availableOptions' => [
                'attributes' => [
                    50 => [
                        5597 => ['numericValue' => null],
                    ],
                ],
                'producers' => [],
            ],
            'matchEstimates' => [
                'attributes' => [
                    50 => [
                        5597 => ['products' => 12],
                    ],
                ],
                'producers' => [],
            ],
            'matchedProducts' => [
                10 => 'complete',
            ],
            'matchedVariants' => [
                1001 => true,
            ],
            'classicBridgeActive' => true,
            'lastResetTimestamp' => 1234567890,
        ];
    }

    private function prepareFastFilterSessionWithUnavailableCriteria(): void
    {
        $_SESSION['lsShop']['fastFilter'] = [
            'criteria' => [
                'attributes' => [
                    50 => [9999],
                ],
                'producers' => ['Unavailable'],
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
            'matchedProducts' => [],
            'matchedVariants' => [],
            'classicBridgeActive' => true,
            'lastResetTimestamp' => 1234567890,
        ];
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
                        'title' => 'Material',
                        'filterFormFieldType' => 'checkbox',
                        'classForFilterFormField' => '',
                        'numItemsInReducedMode' => 0,
                    ],
                ];
            }

            /**
             * @return array<int, array<string, mixed>>
             */
            public function getAttributeValues(int $attributeId): array
            {
                return [
                    5597 => [
                        'id' => 5597,
                        'label' => 'Messing',
                        'alias' => 'messing',
                        'class' => '',
                        'important' => false,
                        'numericValue' => null,
                        'sorting' => 1,
                    ],
                ];
            }
        };
    }
}
