<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit\Core;

use Contao\Database;
use Contao\FormTextField;
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
    private bool $hadOriginalMandatoryLabel;
    private mixed $originalMandatoryLabel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabaseInstances = $this->readStaticProperty(Database::class, 'arrInstances');
        $this->originalSystemContainer = $this->readStaticProperty(System::class, 'objContainer');
        $this->originalFrontendFormFields = $GLOBALS['TL_FFL'] ?? [];
        $this->originalFormFieldNameCache = $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'] ?? [];
        $this->originalPost = $_POST ?? [];
        $this->hadOriginalMandatoryLabel = array_key_exists('mandatory', $GLOBALS['TL_LANG']['MSC'] ?? []);
        $this->originalMandatoryLabel = $GLOBALS['TL_LANG']['MSC']['mandatory'] ?? null;

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
        $GLOBALS['TL_LANG']['MSC']['mandatory'] = 'Required field';
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $this->writeStaticProperty(Database::class, 'arrInstances', $this->originalDatabaseInstances);
        $this->writeStaticProperty(System::class, 'objContainer', $this->originalSystemContainer);
        $GLOBALS['TL_FFL'] = $this->originalFrontendFormFields;
        $GLOBALS['merconis_globals']['cache']['getFormFieldNameForFormFieldId'] = $this->originalFormFieldNameCache;
        if ($this->hadOriginalMandatoryLabel) {
            $GLOBALS['TL_LANG']['MSC']['mandatory'] = $this->originalMandatoryLabel;
        } else {
            unset($GLOBALS['TL_LANG']['MSC']['mandatory']);
        }
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
     * @dataProvider provideConditionalFormFieldStates
     */
    public function testHandleConditionalFormFieldsUsesEffectiveMandatoryState(
        bool $baseMandatory,
        array $primaryCondition,
        array $secondaryCondition,
        array $postedValues,
        ?string $submittedFormId,
        array $fieldNameMap,
        bool $expectedMandatoryState
    ): void {
        $this->primeFormFieldNameCache($fieldNameMap);
        $this->writeStaticProperty(Database::class, 'arrInstances', [
            'd41d8cd98f00b204e9800998ecf8427e' => new FakeDatabase(
                self::createConditionalFormFieldSettings($primaryCondition, $secondaryCondition)
            ),
        ]);

        Input::setPost('FORM_SUBMIT', $submittedFormId);

        foreach ($postedValues as $fieldName => $postedValue) {
            Input::setPost($fieldName, $postedValue);
        }

        $widget = (new \ReflectionClass(FormTextField::class))->newInstanceWithoutConstructor();
        $widget->id = 6;
        $widget->name = 'VATID';
        $widget->mandatory = $baseMandatory;

        $processedWidget = ls_shop_generalHelper::handleConditionalFormFields(
            $widget,
            'auto_customer_data',
            []
        );

        self::assertSame($expectedMandatoryState, (bool) $processedWidget->mandatory);
    }

    public function testHandleConditionalFormFieldsProvidesConditionAttributesAndLocalizedLabel(): void
    {
        $this->primeFormFieldNameCache([
            11 => 'country',
            12 => 'differentShippingAddress',
        ]);
        $this->writeStaticProperty(Database::class, 'arrInstances', [
            'd41d8cd98f00b204e9800998ecf8427e' => new FakeDatabase(
                self::createConditionalFormFieldSettings(
                    self::createCondition(11, 'de', true),
                    self::createCondition(12, '1')
                )
            ),
        ]);

        $widget = (new \ReflectionClass(FormTextField::class))->newInstanceWithoutConstructor();
        $widget->id = 6;
        $widget->name = 'VATID';
        $widget->mandatory = true;

        $processedWidget = ls_shop_generalHelper::handleConditionalFormFields(
            $widget,
            'auto_customer_data',
            []
        );

        self::assertSame('country', $processedWidget->{'data-required-field'});
        self::assertSame('de', $processedWidget->{'data-required-value'});
        self::assertSame('1', $processedWidget->{'data-required-boolean'});
        self::assertSame('differentShippingAddress', $processedWidget->{'data-required-field2'});
        self::assertSame('1', $processedWidget->{'data-required-value2'});
        self::assertSame(null, $processedWidget->{'data-required-boolean2'});
        self::assertSame('Required field', $processedWidget->{'data-required-label'});
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

    public static function provideConditionalFormFieldStates(): array
    {
        return [
            'base false stays optional' => [
                false,
                self::createCondition(11, 'company'),
                [],
                ['customerType' => 'company'],
                'auto_customer_data',
                [11 => 'customerType'],
                false,
            ],
            'base true without conditions stays mandatory' => [
                true,
                [],
                [],
                [],
                'auto_customer_data',
                [],
                true,
            ],
            'primary matching condition keeps field mandatory' => [
                true,
                self::createCondition(11, 'company'),
                [],
                ['customerType' => 'company'],
                'auto_customer_data',
                [11 => 'customerType'],
                true,
            ],
            'primary non matching condition makes field optional' => [
                true,
                self::createCondition(11, 'company'),
                [],
                ['customerType' => 'private'],
                'auto_customer_data',
                [11 => 'customerType'],
                false,
            ],
            'primary inverted matching condition makes field optional' => [
                true,
                self::createCondition(11, 'company', true),
                [],
                ['customerType' => 'company'],
                'auto_customer_data',
                [11 => 'customerType'],
                false,
            ],
            'primary inverted non matching condition keeps field mandatory' => [
                true,
                self::createCondition(11, 'company', true),
                [],
                ['customerType' => 'private'],
                'auto_customer_data',
                [11 => 'customerType'],
                true,
            ],
            'secondary matching condition keeps field mandatory' => [
                true,
                [],
                self::createCondition(12, 'de'),
                ['country' => 'de'],
                'auto_customer_data',
                [12 => 'country'],
                true,
            ],
            'secondary non matching condition makes field optional' => [
                true,
                [],
                self::createCondition(12, 'de'),
                ['country' => 'at'],
                'auto_customer_data',
                [12 => 'country'],
                false,
            ],
            'secondary inverted matching condition makes field optional' => [
                true,
                [],
                self::createCondition(12, 'de', true),
                ['country' => 'de'],
                'auto_customer_data',
                [12 => 'country'],
                false,
            ],
            'secondary inverted non matching condition keeps field mandatory' => [
                true,
                [],
                self::createCondition(12, 'de', true),
                ['country' => 'at'],
                'auto_customer_data',
                [12 => 'country'],
                true,
            ],
            'both configured conditions apply' => [
                true,
                self::createCondition(11, 'company'),
                self::createCondition(12, 'de'),
                [
                    'customerType' => 'company',
                    'country' => 'de',
                ],
                'auto_customer_data',
                [
                    11 => 'customerType',
                    12 => 'country',
                ],
                true,
            ],
            'only primary condition applies' => [
                true,
                self::createCondition(11, 'company'),
                self::createCondition(12, 'de'),
                [
                    'customerType' => 'company',
                    'country' => 'at',
                ],
                'auto_customer_data',
                [
                    11 => 'customerType',
                    12 => 'country',
                ],
                false,
            ],
            'only secondary condition applies' => [
                true,
                self::createCondition(11, 'company'),
                self::createCondition(12, 'de'),
                [
                    'customerType' => 'private',
                    'country' => 'de',
                ],
                'auto_customer_data',
                [
                    11 => 'customerType',
                    12 => 'country',
                ],
                false,
            ],
            'neither configured condition applies' => [
                true,
                self::createCondition(11, 'company'),
                self::createCondition(12, 'de'),
                [
                    'customerType' => 'private',
                    'country' => 'at',
                ],
                'auto_customer_data',
                [
                    11 => 'customerType',
                    12 => 'country',
                ],
                false,
            ],
            'unresolved trigger field makes condition non matching' => [
                true,
                self::createCondition(11, 'company'),
                [],
                [],
                'auto_customer_data',
                [11 => ''],
                false,
            ],
            'missing unchecked checkbox post value remains evaluable' => [
                true,
                self::createCondition(11, '1', true),
                [],
                [],
                'auto_customer_data',
                [11 => 'newsletter'],
                true,
            ],
            'initial rendering preserves base mandatory state' => [
                true,
                self::createCondition(11, 'company'),
                [],
                ['customerType' => 'private'],
                null,
                [11 => 'customerType'],
                true,
            ],
            'unrelated form submission preserves base mandatory state' => [
                true,
                self::createCondition(11, 'company'),
                [],
                ['customerType' => 'private'],
                'auto_other_form',
                [11 => 'customerType'],
                true,
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

    private static function createConditionalFormFieldSettings(
        array $primaryCondition,
        array $secondaryCondition
    ): array {
        return [
            'lsShop_mandatoryOnConditionField' => $primaryCondition['field'] ?? 0,
            'lsShop_mandatoryOnConditionValue' => $primaryCondition['value'] ?? '',
            'lsShop_mandatoryOnConditionBoolean' => !empty($primaryCondition['invert']) ? '1' : '',
            'lsShop_mandatoryOnConditionField2' => $secondaryCondition['field'] ?? 0,
            'lsShop_mandatoryOnConditionValue2' => $secondaryCondition['value'] ?? '',
            'lsShop_mandatoryOnConditionBoolean2' => !empty($secondaryCondition['invert']) ? '1' : '',
            'lsShop_ShowOnConditionField' => 0,
            'lsShop_ShowOnConditionValue' => '',
            'lsShop_ShowOnConditionBoolean' => '',
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

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
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
    public function __construct(
        private array $conditionalFormFieldSettings = []
    ) {
    }

    public function prepare(string $query): FakeDatabaseStatement
    {
        return new FakeDatabaseStatement($query, $this->conditionalFormFieldSettings);
    }
}

final class FakeDatabaseStatement
{
    public function __construct(
        private string $query,
        private array $conditionalFormFieldSettings
    ) {
    }

    public function limit(int $limit): self
    {
        return $this;
    }

    public function execute(...$params): FakeDatabaseResult
    {
        if (str_contains($this->query, '`tl_form_field`')) {
            return new FakeDatabaseResult($this->conditionalFormFieldSettings);
        }

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
    public int $lsShop_mandatoryOnConditionField = 0;
    public string $lsShop_mandatoryOnConditionValue = '';
    public string $lsShop_mandatoryOnConditionBoolean = '';
    public int $lsShop_mandatoryOnConditionField2 = 0;
    public string $lsShop_mandatoryOnConditionValue2 = '';
    public string $lsShop_mandatoryOnConditionBoolean2 = '';
    public int $lsShop_ShowOnConditionField = 0;
    public string $lsShop_ShowOnConditionValue = '';
    public string $lsShop_ShowOnConditionBoolean = '';

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
