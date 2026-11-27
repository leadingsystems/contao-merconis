<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationValidatorTest extends TestCase
{
    private const STANDARD_MEMBER_GROUP_ID = 3;

    private mixed $originalTlConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
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

    public function testVariantInheritUsesParentEffectiveValuesButStillValidatesOwnDuration(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '31',
                'guaranteeBrand' => 'Ignored',
                'guaranteeModelIdentifier' => 'Ignored',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '54',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $result->getMessages()[0]['code']
        );
        self::assertNull($result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('1', $result->getEffectiveData()['enableGaran']);
        self::assertSame('54', $result->getEffectiveData()['guaranteeDurationMonths']);
        self::assertSame('Parent Brand', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('ParentM', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideDoesNotFallBackToProductValuesForEmptyFields(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION,
            ],
            array_column($result->getMessages(), 'code')
        );
        self::assertNull($result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('', $result->getEffectiveData()['guaranteeDurationMonths']);
        self::assertSame('', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantInheritDoesNotDuplicateParentLgnValidation(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'lgnMode' => 'never',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame([], $result->getMessages());
    }

    public function testVariantOverrideKeepsGaranEnabledWhenEffectiveBrandIsMissing(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame('override', $result->getNormalizedData()['garanMode']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result->getMessages()[0]['code']
        );
    }

    /**
     * R3 für jeden Wert der Standard-Benutzergruppe; `null` steht für "keine
     * Standard-Benutzergruppe gesetzt", ein fehlender Produktwert für `inherit`.
     *
     * @return iterable<string, array{string|null, string|null, bool}>
     */
    public static function variantOverrideLgnProvider(): iterable
    {
        $productLgnModes = ['inherit', 'always', 'never', null];
        $expectedRejectionByGroup = [
            'on' => [false, false, true, false],
            'off' => [true, false, true, true],
            'blocked' => [false, false, true, false],
            '' => [false, false, true, false],
        ];

        foreach ($expectedRejectionByGroup as $standardMemberGroupLgnMode => $expectedRejections) {
            foreach ($productLgnModes as $productLgnModeIndex => $productLgnMode) {
                yield sprintf('Gruppe "%s", Produkt "%s"', $standardMemberGroupLgnMode, $productLgnMode ?? 'fehlt') => [
                    (string) $standardMemberGroupLgnMode,
                    $productLgnMode,
                    $expectedRejections[$productLgnModeIndex],
                ];
            }
        }

        yield 'keine Standard-Benutzergruppe, Produkt "never"' => [null, 'never', true];
        yield 'keine Standard-Benutzergruppe, Produkt "inherit"' => [null, 'inherit', false];
    }

    #[DataProvider('variantOverrideLgnProvider')]
    public function testVariantOverrideIsRejectedWhenLgnIsOffForParentProduct(
        ?string $standardMemberGroupLgnMode,
        ?string $productLgnMode,
        bool $expectedRejection
    ): void {
        $productData = [
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '60',
            'guaranteeBrand' => 'Parent Brand',
            'guaranteeModelIdentifier' => 'ParentM',
        ];

        if ($productLgnMode !== null) {
            $productData['lgnMode'] = $productLgnMode;
        }

        $result = $this->createValidator($standardMemberGroupLgnMode)->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            $productData,
        );

        self::assertSame(
            $expectedRejection ? [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED] : [],
            array_column($result->getMessages(), 'code')
        );
    }

    public function testVariantOverrideIgnoresParentEnableGll(): void
    {
        $result = $this->createValidator('on')->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'enableGll' => '',
                'lgnMode' => 'always',
            ],
        );

        self::assertSame([], $result->getMessages());
    }

    public function testVariantInheritAndDisabledAreNotRejectedWhenLgnIsOffForParentProduct(): void
    {
        $validator = $this->createValidator('off');

        foreach (['inherit', 'disabled'] as $garanMode) {
            $result = $validator->validateVariant(
                [
                    'garanMode' => $garanMode,
                    'guaranteeDurationMonths' => '',
                    'guaranteeBrand' => '',
                    'guaranteeModelIdentifier' => '',
                ],
                [
                    'lgnMode' => 'never',
                    'enableGaran' => '1',
                    'guaranteeDurationMonths' => '60',
                    'guaranteeBrand' => 'Parent Brand',
                    'guaranteeModelIdentifier' => 'ParentM',
                ],
            );

            self::assertSame([], $result->getMessages(), $garanMode);
        }
    }

    public function testOverrideVariantIdentifiersUseCodeThenTitleThenId(): void
    {
        self::assertSame(
            ['VAR-001', 'Variante B', '#13'],
            $this->createValidator()->getOverrideVariantIdentifiers([
                ['id' => 11, 'title' => 'Variante A', 'lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override'],
                ['id' => 12, 'title' => 'Variante B', 'lsShopVariantCode' => '', 'garanMode' => 'override'],
                ['id' => 13, 'title' => '', 'lsShopVariantCode' => '', 'garanMode' => 'override'],
                ['id' => 14, 'title' => 'Variante D', 'lsShopVariantCode' => 'VAR-004', 'garanMode' => 'inherit'],
            ])
        );
    }

    public function testVariantValidationRejectsInvalidGaranMode(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'bogus',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
        );

        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE,
            $result->getMessages()[0]['code']
        );
        self::assertSame('inherit', $result->getNormalizedData()['garanMode']);
    }

    public function testInitialGuaranteeBrandKeepsFullProducerName(): void
    {
        $validator = $this->createValidator();
        $producer = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789567890ABCDEFGHIJ';

        self::assertSame($producer, $validator->getInitialGuaranteeBrand($producer));
    }

    public function testVariantFallbackKeepsParentTextValuesWithoutEntityDecoding(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => ' Elternmarke &amp; Co. ',
                'guaranteeModelIdentifier' => ' M&amp;S ',
            ],
        );

        self::assertSame([], $result->getMessages());
        self::assertSame('Elternmarke &amp; Co.', $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame('M&amp;S', $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantOverrideAcceptsLongOwnValuesEvenWhenGaranIsDisabled(): void
    {
        $validator = $this->createValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame($brand, $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame($model, $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    public function testVariantInheritKeepsInheritedLongValuesWhenParentGaranIsDisabled(): void
    {
        $validator = $this->createValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateVariant(
            [
                'garanMode' => 'inherit',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => $brand,
                'guaranteeModelIdentifier' => $model,
            ],
        );

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getEffectiveData()['guaranteeBrand']);
        self::assertSame($model, $result->getEffectiveData()['guaranteeModelIdentifier']);
    }

    /**
     * `null` heißt "keine Standard-Benutzergruppe gesetzt".
     */
    private function createValidator(?string $standardMemberGroupLgnMode = 'on'): ProductGuaranteeConfigurationValidator
    {
        if ($standardMemberGroupLgnMode === null) {
            unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
        } else {
            $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = self::STANDARD_MEMBER_GROUP_ID;
        }

        return new ProductGuaranteeConfigurationValidator(
            standardMemberGroupLgnMode: new StandardMemberGroupLgnMode(
                static fn (int $groupId): array => ['id' => $groupId, 'lsShopLgnMode' => $standardMemberGroupLgnMode]
            )
        );
    }
}
