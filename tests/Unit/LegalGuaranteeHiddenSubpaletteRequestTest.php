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
                'action',
                'enableGll',
                'enableGaran',
                'field',
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

    public function testProductToggleKeepsStoredValueWhenEnableGaranIsBlockedByGll(): void
    {
        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'enableGaran');

        $currentProductData = [
            'enableGll' => '',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $result = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                [],
                [],
                'enableGaran',
                '1'
            )
        );

        self::assertSame(
            '',
            $this->validationHelper->getSubpaletteToggleSaveValue(
                $result->getMessages(),
                'enableGaran',
                '1',
                $currentProductData['enableGaran']
            )
        );
        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED],
            array_column($this->validationHelper->getMessagesForField($result->getMessages(), 'enableGaran'), 'code')
        );
    }

    public function testProductTogglePersistsNewValueWhenOnlySubpaletteFieldsAreMissing(): void
    {
        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'enableGaran');

        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $result = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                [],
                [],
                'enableGaran',
                '1'
            )
        );

        self::assertSame(
            '1',
            $this->validationHelper->getSubpaletteToggleSaveValue(
                $result->getMessages(),
                'enableGaran',
                '1',
                $currentProductData['enableGaran']
            )
        );
        self::assertSame([], $this->validationHelper->getMessagesForField($result->getMessages(), 'enableGaran'));
        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            ],
            array_column($result->getMessages(), 'code')
        );
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

    public function testVariantOverrideToggleKeepsStoredValueWhenParentGllIsDisabled(): void
    {
        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'garanOverride');

        $currentVariantData = [
            'garanOverride' => '',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $currentProductData = [
            'enableGll' => '',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $result = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                [],
                [],
                'garanOverride',
                '1'
            ),
            $currentProductData
        );

        self::assertSame(
            '',
            $this->validationHelper->getSubpaletteToggleSaveValue(
                $result->getMessages(),
                'garanOverride',
                '1',
                $currentVariantData['garanOverride']
            )
        );
        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED],
            array_column(
                $this->validationHelper->getSubpaletteToggleFieldMessages(
                    $result->getMessages(),
                    'garanOverride'
                ),
                'code'
            )
        );
    }

    public function testVariantOverrideTogglePersistsNewValueWhenParentGllIsEnabled(): void
    {
        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'garanOverride');

        $currentVariantData = [
            'garanOverride' => '',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $result = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                [],
                [],
                'garanOverride',
                '1'
            ),
            $currentProductData
        );

        self::assertSame(
            '1',
            $this->validationHelper->getSubpaletteToggleSaveValue(
                $result->getMessages(),
                'garanOverride',
                '1',
                $currentVariantData['garanOverride']
            )
        );
        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            ],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame(
            [],
            $this->validationHelper->getSubpaletteToggleFieldMessages($result->getMessages(), 'garanOverride')
        );
    }

    public function testVariantOverrideTogglePersistsNewValueWhenNoEffectiveGaranOutputExists(): void
    {
        Input::setPost('action', 'toggleSubpalette');
        Input::setPost('field', 'garanOverride');

        $currentVariantData = [
            'garanOverride' => '',
            'enableGaran' => '',
            'guaranteeDurationYears' => null,
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $currentProductData = [
            'enableGll' => '',
            'enableGaran' => '',
            'guaranteeDurationYears' => null,
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $result = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                [],
                [],
                'garanOverride',
                '1'
            ),
            $currentProductData
        );

        self::assertSame(
            '1',
            $this->validationHelper->getSubpaletteToggleSaveValue(
                $result->getMessages(),
                'garanOverride',
                '1',
                $currentVariantData['garanOverride']
            )
        );
        self::assertSame([], $result->getMessages());
    }

    public function testProductAutoSubmitPlaceholderTriggersMissingFieldMessagesOnFirstOpen(): void
    {
        $placeholder = html_entity_decode('&#8203;', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $postedValues = [
            'enableGaran' => '1',
            'guaranteeDurationYears' => $placeholder,
            'guaranteeBrand' => $placeholder,
            'guaranteeModelIdentifier' => $placeholder,
        ];

        $brandResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                $postedValues,
                $this->validationHelper->getProductRequestFieldNames(),
                'guaranteeBrand',
                $placeholder
            )
        );
        $durationResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                $postedValues,
                $this->validationHelper->getProductRequestFieldNames(),
                'guaranteeDurationYears',
                $placeholder
            )
        );

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND],
            array_column($this->validationHelper->getMessagesForField($brandResult->getMessages(), 'guaranteeBrand'), 'code')
        );
        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION],
            array_column($this->validationHelper->getMessagesForField($durationResult->getMessages(), 'guaranteeDurationYears'), 'code')
        );
    }

    public function testVariantAutoSubmitPlaceholderTriggersMissingFieldMessagesOnFirstOpen(): void
    {
        $placeholder = html_entity_decode('&#8203;', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $currentVariantData = [
            'garanOverride' => '',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $postedValues = [
            'garanOverride' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => $placeholder,
            'guaranteeBrand' => $placeholder,
            'guaranteeModelIdentifier' => $placeholder,
        ];

        $brandResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                $postedValues,
                $this->validationHelper->getVariantRequestFieldNames(),
                'guaranteeBrand',
                $placeholder
            ),
            $currentProductData
        );
        $durationResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                $postedValues,
                $this->validationHelper->getVariantRequestFieldNames(),
                'guaranteeDurationYears',
                $placeholder
            ),
            $currentProductData
        );

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND],
            array_column($this->validationHelper->getMessagesForField($brandResult->getMessages(), 'guaranteeBrand'), 'code')
        );
        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION],
            array_column($this->validationHelper->getMessagesForField($durationResult->getMessages(), 'guaranteeDurationYears'), 'code')
        );
    }

    public function testVariantAutoSubmitPlaceholderFieldsAreNotPersistedWhenOverrideOpensWithoutEffectiveGaran(): void
    {
        $placeholder = html_entity_decode('&#8203;', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $currentVariantData = [
            'garanOverride' => '',
            'enableGaran' => '',
            'guaranteeDurationYears' => null,
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $currentProductData = [
            'enableGll' => '',
            'enableGaran' => '',
            'guaranteeDurationYears' => null,
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        Input::setPost('garanOverride', '1');
        Input::setPost('guaranteeDurationYears', $placeholder);
        Input::setPost('guaranteeBrand', $placeholder);
        Input::setPost('guaranteeModelIdentifier', $placeholder);

        $postedValues = $this->validationHelper->readPostedFieldValues(
            $this->validationHelper->getVariantRequestFieldNames()
        );
        $visibleFieldNames = $this->validationHelper->getVisibleVariantFieldNames($postedValues);
        $persistenceResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                $postedValues,
                $visibleFieldNames
            ),
            $currentProductData
        );
        $fieldsToPersist = $this->validationHelper->buildChangedFieldValues(
            $currentVariantData,
            $persistenceResult->getNormalizedData(),
            $visibleFieldNames
        );

        self::assertNull($persistenceResult->getNormalizedData()['guaranteeDurationYears']);
        self::assertSame('', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
        self::assertSame(['garanOverride' => '1'], $fieldsToPersist);
    }

    public function testProductCompositePlaceholderFragmentsAreRemovedBeforePersistence(): void
    {
        $placeholder = html_entity_decode('&#8203;', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $postedValues = [
            'enableGaran' => '1',
            'guaranteeDurationYears' => $placeholder . '36',
            'guaranteeBrand' => $placeholder . 'Nestle',
            'guaranteeModelIdentifier' => 'Nestle' . $placeholder,
        ];
        $visibleFieldNames = $this->validationHelper->getVisibleProductFieldNames($postedValues);
        $persistenceResult = $this->validator->validateProduct(
            $this->validationHelper->buildValidationInputData(
                $currentProductData,
                $postedValues,
                $visibleFieldNames
            )
        );
        $fieldsToPersist = $this->validationHelper->buildChangedFieldValues(
            $currentProductData,
            $persistenceResult->getNormalizedData(),
            $visibleFieldNames
        );

        self::assertSame(
            [
                'guaranteeDurationYears' => '3',
                'guaranteeBrand' => 'Nestle',
                'guaranteeModelIdentifier' => 'Nestle',
            ],
            $fieldsToPersist
        );
    }

    public function testVariantCompositePlaceholderFragmentsAreRemovedBeforePersistence(): void
    {
        $placeholder = html_entity_decode('&#8203;', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $currentVariantData = [
            'garanOverride' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationYears' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
        $postedValues = [
            'garanOverride' => '1',
            'enableGaran' => '1',
            'guaranteeDurationYears' => $placeholder . '36',
            'guaranteeBrand' => $placeholder . 'Nestle',
            'guaranteeModelIdentifier' => 'Nestle' . $placeholder,
        ];
        $visibleFieldNames = $this->validationHelper->getVisibleVariantFieldNames($postedValues);
        $persistenceResult = $this->validator->validateVariant(
            $this->validationHelper->buildValidationInputData(
                $currentVariantData,
                $postedValues,
                $visibleFieldNames
            ),
            $currentProductData
        );
        $fieldsToPersist = $this->validationHelper->buildChangedFieldValues(
            $currentVariantData,
            $persistenceResult->getNormalizedData(),
            $visibleFieldNames
        );

        self::assertSame(
            [
                'guaranteeDurationYears' => '3',
                'guaranteeBrand' => 'Nestle',
                'guaranteeModelIdentifier' => 'Nestle',
            ],
            $fieldsToPersist
        );
    }
}
