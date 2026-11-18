<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeHiddenSubpaletteRequestTest extends TestCase
{
    private ProductGuaranteeConfigurationValidator $validator;
    private mixed $originalTlConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = 3;
        $this->validator = new ProductGuaranteeConfigurationValidator(
            standardMemberGroupLgnMode: new StandardMemberGroupLgnMode(
                static fn (int $groupId): array => ['id' => $groupId, 'lsShopLgnMode' => 'on']
            )
        );
    }

    protected function tearDown(): void
    {
        if ($this->originalTlConfig === null) {
            unset($GLOBALS['TL_CONFIG']);
        } else {
            $GLOBALS['TL_CONFIG'] = $this->originalTlConfig;
        }

        parent::tearDown();
    }

    public function testProductKeepsStoredGuaranteeValuesWhenEnableGaranIsTurnedOffBeforeSave(): void
    {
        $currentProductData = [
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => 'Stored brand',
            'guaranteeModelIdentifier' => 'Stored mdl',
        ];
        $submittedRequestData = [
            'lgnMode' => 'inherit',
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
            'lgnMode' => 'inherit',
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
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ]
        );
        $disabledResult = $this->validator->validateProduct(
            [
                'lgnMode' => 'inherit',
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
            'lgnMode' => 'inherit',
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
