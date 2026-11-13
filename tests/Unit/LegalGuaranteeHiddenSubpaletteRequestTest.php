<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeHiddenSubpaletteRequestTest extends TestCase
{
    private ProductGuaranteeConfigurationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

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
        $submittedRequestData = [
            'enableGll' => '1',
            'enableGaran' => '',
        ];

        $requestResult = $this->validator->validateProduct($submittedRequestData);
        $persistenceResult = $this->validator->validateProduct($currentProductData);

        self::assertSame([], $requestResult->getMessages());
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
        $submittedRequestData = [
            'garanMode' => 'inherit',
        ];

        $requestResult = $this->validator->validateVariant(
            $submittedRequestData,
            $currentProductData
        );
        $persistenceResult = $this->validator->validateVariant(
            $currentVariantData,
            $currentProductData
        );

        self::assertSame([], $requestResult->getMessages());
        self::assertSame('30', $persistenceResult->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('Stored brand', $persistenceResult->getNormalizedData()['guaranteeBrand']);
        self::assertSame('Stored mdl', $persistenceResult->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testProductRequiresGuaranteeFieldsOnlyWhenEnableGaranIsEnabled(): void
    {
        $enabledResult = $this->validator->validateProduct(
            [
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ]
        );
        $disabledResult = $this->validator->validateProduct(
            [
                'enableGll' => '1',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ]
        );

        self::assertContains(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            array_column($enabledResult->getMessages(), 'code')
        );
        self::assertContains(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            array_column($enabledResult->getMessages(), 'code')
        );
        self::assertSame([], $disabledResult->getMessages());
    }

    public function testVariantRequiresGuaranteeFieldsOnlyWhenModeIsOverride(): void
    {
        $currentProductData = [
            'enableGll' => '1',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];

        $overrideResult = $this->validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            $currentProductData
        );
        $inheritResult = $this->validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            $currentProductData
        );

        self::assertContains(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            array_column($overrideResult->getMessages(), 'code')
        );
        self::assertSame([], $inheritResult->getMessages());
    }
}
