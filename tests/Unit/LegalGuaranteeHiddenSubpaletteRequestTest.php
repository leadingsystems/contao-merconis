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
            'guaranteeDurationYears' => '3',
            'guaranteeBrand' => 'Stored brand',
            'guaranteeModelIdentifier' => 'Stored model',
        ];
        $postedValues = [
            'enableGaran' => '',
            'guaranteeDurationYears' => '15',
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
        self::assertSame('3', $persistenceResult->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('Stored brand', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('Stored model', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testVariantKeepsStoredGuaranteeValuesWhenOverrideIsTurnedOffBeforeSave(): void
    {
        $currentVariantData = [
            'garanOverride' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '3',
            'guaranteeBrand' => 'Stored brand',
            'guaranteeModelIdentifier' => 'Stored model',
        ];
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '3',
            'guaranteeBrand' => 'Product brand',
            'guaranteeModelIdentifier' => 'Product model',
        ];
        $postedValues = [
            'garanOverride' => '',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '15',
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
        self::assertSame('3', $persistenceResult->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('Stored brand', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('Stored model', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testProductRequiresGuaranteeFieldsOnlyWhenEnableGaranIsEnabled(): void
    {
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $enabledResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                [
                    'enableGaran' => '1',
                    'guaranteeDurationYears' => '',
                    'guaranteeBrand' => '',
                    'guaranteeModelIdentifier' => '',
                ],
                [
                    'enableGaran',
                    'guaranteeDurationYears',
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
                    'guaranteeDurationYears' => '',
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
                $this->validationHelper->getMessagesForField($enabledResult->getMessages(), 'guaranteeDurationYears'),
                'code'
            )
        );
        self::assertSame([], $disabledResult->getMessages());
    }
}
