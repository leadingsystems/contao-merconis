<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\Input;
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

    protected function tearDown(): void
    {
        foreach (
            [
                'enableGll',
                'enableGaran',
                'garanOverride',
                'guaranteeDurationYears',
                'guaranteeBrand',
                'guaranteeModelIdentifier',
            ] as $fieldName
        ) {
            Input::setPost($fieldName, null);
        }

        Input::resetCache();

        parent::tearDown();
    }

    public function testProductRejectsHiddenInvalidDurationGloballyWithoutOverwritingStoredValues(): void
    {
        Input::setPost('enableGaran', '');
        Input::setPost('guaranteeDurationYears', '15');
        Input::setPost('guaranteeBrand', 'Posted brand');
        Input::setPost('guaranteeModelIdentifier', 'Posted model');

        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '3',
            'guaranteeBrand' => 'Stored brand',
            'guaranteeModelIdentifier' => 'Stored model',
        ];

        $postedValues = $this->validationHelper->readPostedFieldValues(
            $this->validationHelper->getProductRequestFieldNames()
        );
        $visibleFieldNames = $this->validationHelper->getVisibleProductFieldNames($postedValues);

        $requestResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                $postedValues,
                array_keys($postedValues)
            )
        );
        $persistenceResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                $postedValues,
                $visibleFieldNames
            )
        );
        $globalMessages = $this->validationHelper->getGlobalMessages(
            $requestResult->getMessages(),
            $visibleFieldNames
        );

        self::assertSame('', $postedValues['enableGaran']);
        self::assertCount(1, $globalMessages);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $globalMessages[0]['code']
        );
        self::assertSame(['15'], $globalMessages[0]['parameters']);
        self::assertSame('3', $persistenceResult->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('Stored brand', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('Stored model', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testVariantRejectsHiddenInvalidDurationGloballyWithoutOverwritingStoredValues(): void
    {
        Input::setPost('garanOverride', '');
        Input::setPost('enableGaran', '1');
        Input::setPost('guaranteeDurationYears', '15');
        Input::setPost('guaranteeBrand', 'Posted brand');
        Input::setPost('guaranteeModelIdentifier', 'Posted model');

        $currentVariantData = [
            'garanOverride' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '3',
            'guaranteeBrand' => 'Stored brand',
            'guaranteeModelIdentifier' => 'Stored model',
        ];
        $currentProductData = [
            'enableGaran' => '1',
            'guaranteeDurationYears' => '3',
            'guaranteeBrand' => 'Product brand',
            'guaranteeModelIdentifier' => 'Product model',
        ];

        $postedValues = $this->validationHelper->readPostedFieldValues(
            $this->validationHelper->getVariantRequestFieldNames()
        );
        $visibleFieldNames = $this->validationHelper->getVisibleVariantFieldNames($postedValues);

        $requestResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                $postedValues,
                array_keys($postedValues)
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
        $globalMessages = $this->validationHelper->getGlobalMessages(
            $requestResult->getMessages(),
            $visibleFieldNames
        );

        self::assertSame('', $postedValues['garanOverride']);
        self::assertCount(1, $globalMessages);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $globalMessages[0]['code']
        );
        self::assertSame(['15'], $globalMessages[0]['parameters']);
        self::assertSame('3', $persistenceResult->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('Stored brand', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('Stored model', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
    }
}
