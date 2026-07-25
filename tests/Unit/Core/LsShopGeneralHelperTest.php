<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit\Core;

use Contao\Database;
use Contao\Input;
use Contao\System;
use ErrorException;
use Merconis\Core\ls_shop_generalHelper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

final class LsShopGeneralHelperTest extends TestCase
{
    private array $originalDatabaseInstances;
    private mixed $originalSystemContainer;
    private array $originalFrontendFormFields;
    private array $originalFormFieldNameCache;
    private array $originalPost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseInstances = $this->readStaticProperty(Database::class, 'arrInstances');
        $this->originalSystemContainer = $this->readStaticProperty(System::class, 'objContainer');
        $this->originalFrontendFormFields = $GLOBALS['TL_FFL'] ?? [];
        $this->originalFormFieldNameCache = $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'] ?? [];
        $this->originalPost = $_POST ?? [];

        class_exists(Input::class);

        $this->writeStaticProperty(System::class, 'objContainer', new FakeContainer([
            'merconis.routing.scope' => new FakeRoutingScope(false),
        ], [
            'kernel.charset' => 'UTF-8',
        ]));
        $this->writeStaticProperty(Database::class, 'arrInstances', [
            'd41d8cd98f00b204e9800998ecf8427e' => new FakeDatabase(),
        ]);

        $GLOBALS['TL_FFL']['unit_test_text'] = FakeRequiredWidget::class;
        $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'] = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $this->writeStaticProperty(Database::class, 'arrInstances', $this->originalDatabaseInstances);
        $this->writeStaticProperty(System::class, 'objContainer', $this->originalSystemContainer);
        $GLOBALS['TL_FFL'] = $this->originalFrontendFormFields;
        $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'] = $this->originalFormFieldNameCache;
        $_POST = $this->originalPost;

        parent::tearDown();
    }

    /**
     * @dataProvider provideEffectiveMandatoryStates
     */
    public function testCalculateEffectiveMandatoryState(
        bool $baseMandatory,
        array $primaryCondition,
        array $secondaryCondition,
        array $validateData,
        array $fieldNameMap,
        bool $expectedMandatoryState
    ): void {
        $this->primeFormFieldNameCache($fieldNameMap);

        self::assertSame(
            $expectedMandatoryState,
            ls_shop_generalHelper::calculateEffectiveMandatoryState(
                $baseMandatory,
                $primaryCondition,
                $secondaryCondition,
                $validateData
            )
        );
    }

    public function testCalculateEffectiveMandatoryStateDoesNotTriggerWarningForMissingTriggerField(): void
    {
        $this->primeFormFieldNameCache([
            11 => 'triggerField',
        ]);

        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );

        try {
            self::assertFalse(
                ls_shop_generalHelper::calculateEffectiveMandatoryState(
                    true,
                    self::createCondition(11, 'company'),
                    [],
                    []
                )
            );
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @dataProvider provideWidgetValidationCases
     */
    public function testValidateCollectedFormDataUsesEffectiveMandatoryStateDuringWidgetValidation(
        string $vatIdValue,
        bool $expectedValidity
    ): void {
        $this->primeFormFieldNameCache([
            11 => 'customerType',
        ]);

        self::assertSame(
            $expectedValidity,
            ls_shop_generalHelper::validateCollectedFormData(
                [
                    'customerType' => [
                        'name' => 'customerType',
                        'arrData' => [
                            'name' => 'customerType',
                            'type' => 'unit_test_text',
                            'mandatory' => false,
                            'lsShop_mandatoryOnConditionField' => 0,
                            'lsShop_mandatoryOnConditionValue' => '',
                            'lsShop_mandatoryOnConditionBoolean' => '',
                            'lsShop_mandatoryOnConditionField2' => 0,
                            'lsShop_mandatoryOnConditionValue2' => '',
                            'lsShop_mandatoryOnConditionBoolean2' => '',
                        ],
                        'value' => 'company',
                    ],
                    'VATID' => [
                        'name' => 'VATID',
                        'arrData' => [
                            'name' => 'VATID',
                            'type' => 'unit_test_text',
                            'mandatory' => true,
                            'lsShop_mandatoryOnConditionField' => 11,
                            'lsShop_mandatoryOnConditionValue' => 'company',
                            'lsShop_mandatoryOnConditionBoolean' => '',
                            'lsShop_mandatoryOnConditionField2' => 0,
                            'lsShop_mandatoryOnConditionValue2' => '',
                            'lsShop_mandatoryOnConditionBoolean2' => '',
                        ],
                        'value' => $vatIdValue,
                    ],
                ],
                5
            )
        );
    }

    public static function provideEffectiveMandatoryStates(): array
    {
        return [
            'base false stays optional' => [
                false,
                [],
                [],
                [],
                [],
                false,
            ],
            'base true without conditions stays mandatory' => [
                true,
                [],
                [],
                [],
                [],
                true,
            ],
            'matching condition keeps field mandatory' => [
                true,
                self::createCondition(11, 'company'),
                [],
                [
                    'triggerField' => ['value' => 'company'],
                ],
                [
                    11 => 'triggerField',
                ],
                true,
            ],
            'non matching condition disables mandatory state' => [
                true,
                self::createCondition(11, 'company'),
                [],
                [
                    'triggerField' => ['value' => 'private'],
                ],
                [
                    11 => 'triggerField',
                ],
                false,
            ],
            'inverted non match keeps field mandatory' => [
                true,
                self::createCondition(11, 'company', true),
                [],
                [
                    'triggerField' => ['value' => 'private'],
                ],
                [
                    11 => 'triggerField',
                ],
                true,
            ],
            'inverted match disables mandatory state' => [
                true,
                self::createCondition(11, 'company', true),
                [],
                [
                    'triggerField' => ['value' => 'company'],
                ],
                [
                    11 => 'triggerField',
                ],
                false,
            ],
            'both configured conditions must match' => [
                true,
                self::createCondition(11, 'company'),
                self::createCondition(12, 'de', true),
                [
                    'triggerOne' => ['value' => 'company'],
                    'triggerTwo' => ['value' => 'at'],
                ],
                [
                    11 => 'triggerOne',
                    12 => 'triggerTwo',
                ],
                true,
            ],
            'second configured condition can disable mandatory state' => [
                true,
                self::createCondition(11, 'company'),
                self::createCondition(12, 'de'),
                [
                    'triggerOne' => ['value' => 'company'],
                    'triggerTwo' => ['value' => 'at'],
                ],
                [
                    11 => 'triggerOne',
                    12 => 'triggerTwo',
                ],
                false,
            ],
            'missing trigger field is treated as non matching' => [
                true,
                self::createCondition(11, 'company'),
                [],
                [],
                [
                    11 => 'triggerField',
                ],
                false,
            ],
        ];
    }

    public static function provideWidgetValidationCases(): array
    {
        return [
            'empty effectively mandatory value is rejected' => [
                '',
                false,
            ],
            'prefilled effectively mandatory value is accepted' => [
                'DE123456789',
                true,
            ],
        ];
    }

    private function primeFormFieldNameCache(array $fieldNameMap): void
    {
        foreach ($fieldNameMap as $fieldId => $fieldName) {
            $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'][$fieldId] = $fieldName;
        }
    }

    private function readStaticProperty(string $className, string $propertyName): mixed
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue();
    }

    private function writeStaticProperty(string $className, string $propertyName, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null, $value);
    }

    private static function createCondition(int $fieldId, string $value, bool $invert = false): array
    {
        return [
            'field' => $fieldId,
            'value' => $value,
            'invert' => $invert,
        ];
    }
}

final class FakeContainer implements ContainerInterface
{
    public function __construct(
        private array $services,
        private array $parameters
    ) {
    }

    public function get(string $id): mixed
    {
        return $this->services[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }
}

final class FakeRoutingScope
{
    public function __construct(
        private bool $backend
    ) {
    }

    public function isBackend(): bool
    {
        return $this->backend;
    }
}

final class FakeDatabase
{
    public function prepare(string $query): FakeDatabaseStatement
    {
        return new FakeDatabaseStatement($query);
    }
}

final class FakeDatabaseStatement
{
    public function __construct(
        private string $query
    ) {
    }

    public function execute(...$params): FakeDatabaseResult
    {
        return new FakeDatabaseResult([
            'allowTags' => false,
            'tableless' => false,
        ]);
    }
}

final class FakeDatabaseResult
{
    public int $numRows = 1;
    public bool $allowTags = false;
    public bool $tableless = false;

    public function __construct(
        private array $rowData
    ) {
        foreach ($rowData as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function first(): void
    {
    }

    public function row(): array
    {
        return $this->rowData;
    }
}

final class FakeRequiredWidget
{
    public bool $required = false;
    private bool $hasErrors = false;

    public function __construct(
        private array $attributes
    ) {
    }

    public function validate(): void
    {
        $value = Input::post($this->attributes['name'] ?? '');

        if ($this->required && ($value === null || $value === '')) {
            $this->hasErrors = true;
        }
    }

    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }
}
