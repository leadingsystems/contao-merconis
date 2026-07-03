<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterConfigurationRepository;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterFormService;
use LeadingSystems\MerconisBundle\FastFilter\FastFilterSessionWriter;
use PHPUnit\Framework\TestCase;

final class FastFilterFormServiceRangeSliderConfigTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [
            'lsShop' => [
                'fastFilter' => [
                    'criteria' => [
                        'attributes' => [
                            50 => [5597],
                        ],
                        'producers' => [],
                    ],
                    'availableOptions' => [
                        'attributes' => [
                            50 => [
                                5597 => ['numericValue' => '10'],
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
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testViewModelContainsRangeSliderConfiguration(): void
    {
        $service = new FastFilterFormService(
            new class extends FastFilterConfigurationRepository {
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
                            'filterDisplayMode' => 'sliderRange',
                            'rangeSliderDecimalSeparator' => 'comma',
                            'rangeSliderThousandSeparator' => 'space',
                            'rangeSliderMinOptionCount' => 15,
                            'rangeSliderInitialOptionCount' => 8,
                            'rangeSliderInitialPosition' => 'middle',
                            'rangeSliderAutoOptionVisibility' => 'hide',
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
                            'numericValue' => '10',
                            'sorting' => 1,
                        ],
                    ];
                }
            },
            new FastFilterSessionWriter(),
        );

        $viewModel = $service->buildViewModel();
        $field = $viewModel['fields'][0];

        self::assertSame('sliderRange', $field['filterDisplayMode']);
        self::assertSame('comma', $field['rangeSliderDecimalSeparator']);
        self::assertSame('space', $field['rangeSliderThousandSeparator']);
        self::assertSame(15, $field['rangeSliderMinOptionCount']);
        self::assertSame(8, $field['rangeSliderInitialOptionCount']);
        self::assertSame('middle', $field['rangeSliderInitialPosition']);
        self::assertSame('hide', $field['rangeSliderAutoOptionVisibility']);
    }
}
