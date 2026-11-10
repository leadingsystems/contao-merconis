<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeValidationHelper;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeHiddenSubpaletteRequestTest extends TestCase
{
    private ProductGuaranteeValidationHelper $validationHelper;
    private ProductGuaranteeConfigurationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationHelper = new ProductGuaranteeValidationHelper();
        $this->validator = new ProductGuaranteeConfigurationValidator();
    }

    public function testProductKeepsStoredGuaranteeValuesWhenEnableGaranIsTurnedOffBeforeSave(): void
    {
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => 'Stored brand',
            'guaranteeModelIdentifier' => 'Stored mdl',
        ];
        $postedValues = [
            'enableGaran' => '',
            'guaranteeDurationMonths' => '15',
            'guaranteeBrand' => 'Changed brand',
            'guaranteeModelIdentifier' => '',
        ];
        $visibleFieldNames = $this->validationHelper->getVisibleProductFieldNames($postedValues);

        $requestResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                $postedValues,
                $visibleFieldNames
            )
        );
        $persistenceResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                $postedValues,
                $visibleFieldNames
            )
        );

        self::assertSame(
            [],
            $this->validationHelper->getGlobalMessages($requestResult->getMessages(), $visibleFieldNames)
        );
        self::assertSame('30', $persistenceResult->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('Stored brand', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('Stored mdl', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testVariantKeepsStoredGuaranteeValuesWhenModeLeavesOverrideBeforeSave(): void
    {
        $currentVariantData = [
            'garanMode' => 'override',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => 'Stored brand',
            'guaranteeModelIdentifier' => 'Stored mdl',
        ];
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => 'Product brand',
            'guaranteeModelIdentifier' => 'Product mdl',
        ];
        $postedValues = [
            'garanMode' => 'inherit',
            'guaranteeDurationMonths' => '15',
            'guaranteeBrand' => 'Changed brand',
            'guaranteeModelIdentifier' => '',
        ];
        $visibleFieldNames = $this->validationHelper->getVisibleVariantFieldNames($postedValues);

        $requestResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                $postedValues,
                $visibleFieldNames
            ),
            $currentProductData
        );
        $persistenceResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                $postedValues,
                $visibleFieldNames
            ),
            $currentProductData
        );

        self::assertSame(
            [],
            $this->validationHelper->getGlobalMessages($requestResult->getMessages(), $visibleFieldNames)
        );
        self::assertSame('30', $persistenceResult->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('Stored brand', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('Stored mdl', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testProductRequiresGuaranteeFieldsOnlyWhenEnableGaranIsEnabled(): void
    {
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $enabledResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                [
                    'enableGaran' => '1',
                    'guaranteeDurationMonths' => '',
                    'guaranteeBrand' => '',
                    'guaranteeModelIdentifier' => '',
                ],
                [
                    'enableGaran',
                    'guaranteeDurationMonths',
                    'guaranteeBrand',
                    'guaranteeModelIdentifier',
                ]
            )
        );
        $disabledResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                [
                    'enableGaran' => '',
                    'guaranteeDurationMonths' => '',
                    'guaranteeBrand' => '',
                    'guaranteeModelIdentifier' => '',
                ],
                ['enableGaran']
            )
        );

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND],
            array_column(
                $this->validationHelper->getMessagesForField($enabledResult->getMessages(), 'guaranteeBrand'),
                'code'
            )
        );
        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION],
            array_column(
                $this->validationHelper->getMessagesForField($enabledResult->getMessages(), 'guaranteeDurationMonths'),
                'code'
            )
        );
        self::assertSame([], $disabledResult->getMessages());
    }

    public function testVariantRequiresGuaranteeFieldsOnlyWhenModeIsOverride(): void
    {
        $currentVariantData = [
            'garanMode' => 'inherit',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $overrideResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                [
                    'garanMode' => 'override',
                    'guaranteeDurationMonths' => '',
                    'guaranteeBrand' => '',
                    'guaranteeModelIdentifier' => '',
                ],
                [
                    'garanMode',
                    'guaranteeDurationMonths',
                    'guaranteeBrand',
                    'guaranteeModelIdentifier',
                ]
            ),
            $currentProductData
        );
        $inheritResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                [
                    'garanMode' => 'inherit',
                    'guaranteeDurationMonths' => '',
                    'guaranteeBrand' => '',
                    'guaranteeModelIdentifier' => '',
                ],
                ['garanMode']
            ),
            $currentProductData
        );

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND],
            array_column(
                $this->validationHelper->getMessagesForField($overrideResult->getMessages(), 'guaranteeBrand'),
                'code'
            )
        );
        self::assertSame([], $inheritResult->getMessages());
    }
}
