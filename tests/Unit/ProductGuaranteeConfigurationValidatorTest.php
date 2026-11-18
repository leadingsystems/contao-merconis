<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupGllMode;
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

    public function testProductValidationStoresWholeMonthsAsYears(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateProduct([
            'gllMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => ' ACME &amp; Co. ',
            'guaranteeModelIdentifier' => ' M-42 ',
        ]);

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame('30', $result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame('ACME &amp; Co.', $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame('M-42', $result->getNormalizedData()['guaranteeModelIdentifier']);
        self::assertSame([], $result->getMessages());
    }

    public function testProductValidationKeepsGaranEnabledWhenBrandIsMissing(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateProduct([
            'gllMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result->getMessages()[0]['code']
        );
    }

    public function testProductValidationRejectsInvalidMonthValuesWithoutRounding(): void
    {
        $validator = $this->createValidator();

        $result = $validator->validateProduct([
            'enableGaran' => '',
            'guaranteeDurationMonths' => '31',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model',
        ]);

        self::assertSame('', $result->getNormalizedData()['enableGaran']);
        self::assertNull($result->getNormalizedData()['guaranteeDurationMonths']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION,
            $result->getMessages()[0]['code']
        );
        self::assertSame(['31'], $result->getMessages()[0]['parameters']);
    }

    /**
     * "GARAN setzt GLL voraus" für jede Kombination aus Wert der Standard-Benutzergruppe
     * (`null` = keine gesetzt) und `gllMode` der Zeile (`null` = Spalte fehlt). Erwartet ist
     * der Meldungscode oder `null` für keine Ablehnung.
     *
     * @return iterable<string, array{string|null, string|null, string|null}>
     */
    public static function productGaranRequiresGllProvider(): iterable
    {
        $required = ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED;
        $requiredAlways = ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED_ALWAYS;
        $productGllModes = ['inherit', 'always', 'never', '', null];
        $expectedCodesByGroup = [
            'on' => [null, null, $required, null, null],
            'off' => [$requiredAlways, null, $requiredAlways, $requiredAlways, $requiredAlways],
            'blocked' => [null, null, $required, null, null],
            'leer' => [null, null, $required, null, null],
            'keine' => [null, null, $required, null, null],
        ];
        $groupValues = ['on' => 'on', 'off' => 'off', 'blocked' => 'blocked', 'leer' => '', 'keine' => null];

        foreach ($expectedCodesByGroup as $groupLabel => $expectedCodes) {
            foreach ($productGllModes as $productGllModeIndex => $productGllMode) {
                yield sprintf('Gruppe %s, Produkt "%s"', $groupLabel, $productGllMode ?? 'fehlt') => [
                    $groupValues[$groupLabel],
                    $productGllMode,
                    $expectedCodes[$productGllModeIndex],
                ];
            }
        }
    }

    #[DataProvider('productGaranRequiresGllProvider')]
    public function testProductRowWithGaranIsRejectedWhenGllIsOff(
        ?string $standardMemberGroupGllMode,
        ?string $productGllMode,
        ?string $expectedCode
    ): void {
        $productRow = $this->createCompleteGaranProductRow($productGllMode);

        $result = $this->createValidator($standardMemberGroupGllMode)->validateProduct($productRow);

        self::assertSame(
            $expectedCode === null ? [] : [$expectedCode],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame('1', $result->getNormalizedData()['enableGaran']);
        self::assertSame(
            in_array($productGllMode, ['always', 'never'], true) ? $productGllMode : 'inherit',
            $result->getNormalizedData()['gllMode']
        );
    }

    public function testProductRowWithoutGaranIsNotRejectedWhenGllIsOff(): void
    {
        foreach (['on' => 'never', 'off' => 'inherit', 'blocked' => 'never', '' => 'never'] as $groupGllMode => $productGllMode) {
            $result = $this->createValidator((string) $groupGllMode)->validateProduct([
                'gllMode' => $productGllMode,
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ]);

            self::assertSame([], $result->getMessages(), (string) $groupGllMode);
        }
    }

    public function testProductRowIsRejectedWhenStoredVariantsUseOverrideAndGllIsOff(): void
    {
        $storedVariants = [
            ['lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override'],
            ['lsShopVariantCode' => 'VAR-002', 'garanMode' => 'override'],
            ['lsShopVariantCode' => 'VAR-IGNORED', 'garanMode' => 'disabled'],
        ];

        $result = $this->createValidator('on')->validateProduct(
            [
                'gllMode' => 'never',
                'enableGaran' => '',
                'guaranteeDurationMonths' => null,
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            $storedVariants
        );

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame(['VAR-001, VAR-002'], $result->getMessages()[0]['parameters']);
    }

    /**
     * Der Validator kennt nur den gespeicherten Stand der Varianten. Nimmt dieselbe Datei die
     * Varianten von `override` weg, liegen diese Zeilen beim Prüfen der Produktzeile noch nicht
     * vor; die Produktzeile wird trotzdem abgelehnt, auch bei unverändertem `gllMode`.
     */
    public function testProductRowIsRejectedForStoredOverrideVariantsEvenWithUnchangedGllMode(): void
    {
        $result = $this->createValidator('off')->validateProduct(
            $this->createCompleteGaranProductRow('inherit'),
            [['lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override']]
        );

        self::assertSame(
            [
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE,
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED_ALWAYS,
            ],
            array_column($result->getMessages(), 'code')
        );
    }

    public function testStoredOverrideVariantsDoNotRejectProductRowWhenGllIsOn(): void
    {
        $result = $this->createValidator('off')->validateProduct(
            $this->createCompleteGaranProductRow('always'),
            [['lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override']]
        );

        self::assertSame([], $result->getMessages());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProductGllModeProvider(): iterable
    {
        yield 'unbekannter Wert' => ['sometimes'];
        yield 'alter Checkbox-Wert' => ['1'];
        yield 'Gruppenwert' => ['off'];
        yield 'Variantenwert' => ['override'];
    }

    #[DataProvider('invalidProductGllModeProvider')]
    public function testProductRowWithInvalidGllModeIsRejected(string $invalidGllMode): void
    {
        $result = $this->createValidator('on')->validateProduct($this->createCompleteGaranProductRow(' ' . $invalidGllMode . ' '));

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GLL_INVALID_MODE],
            array_column($result->getMessages(), 'code')
        );
        self::assertSame([$invalidGllMode], $result->getMessages()[0]['parameters']);
    }

    public function testProductRowColumnEnableGllHasNoEffect(): void
    {
        $validator = $this->createValidator('on');

        $rejectedRow = $this->createCompleteGaranProductRow('never');
        $rejectedRow['enableGll'] = '1';
        $acceptedRow = $this->createCompleteGaranProductRow(null);
        $acceptedRow['enableGll'] = '';

        self::assertSame(
            [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED],
            array_column($validator->validateProduct($rejectedRow)->getMessages(), 'code')
        );
        self::assertSame([], $validator->validateProduct($acceptedRow)->getMessages());
        self::assertSame('inherit', $validator->validateProduct($acceptedRow)->getNormalizedData()['gllMode']);
    }

    /**
     * Abgelehnte Zeilen behalten ihre Werte; der Validator setzt weder `gllMode` noch
     * `enableGaran` um (Spec 27, Abschnitt 4, "Import und Product Management API").
     */
    public function testRejectedProductRowKeepsItsValues(): void
    {
        foreach (['on' => 'never', 'off' => 'never', 'blocked' => 'never'] as $groupGllMode => $productGllMode) {
            $result = $this->createValidator($groupGllMode)->validateProduct(
                $this->createCompleteGaranProductRow($productGllMode),
                [['lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override']]
            );

            self::assertNotSame([], $result->getMessages(), $groupGllMode);
            self::assertSame('never', $result->getNormalizedData()['gllMode'], $groupGllMode);
            self::assertSame('1', $result->getNormalizedData()['enableGaran'], $groupGllMode);
            self::assertSame('36', $result->getNormalizedData()['guaranteeDurationMonths'], $groupGllMode);
        }
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
                'gllMode' => 'inherit',
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
                'gllMode' => 'inherit',
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

    public function testVariantInheritDoesNotDuplicateParentGllValidation(): void
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
                'gllMode' => 'never',
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
                'gllMode' => 'inherit',
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
    public static function variantOverrideGllProvider(): iterable
    {
        $productGllModes = ['inherit', 'always', 'never', null];
        $expectedRejectionByGroup = [
            'on' => [false, false, true, false],
            'off' => [true, false, true, true],
            'blocked' => [false, false, true, false],
            '' => [false, false, true, false],
        ];

        foreach ($expectedRejectionByGroup as $standardMemberGroupGllMode => $expectedRejections) {
            foreach ($productGllModes as $productGllModeIndex => $productGllMode) {
                yield sprintf('Gruppe "%s", Produkt "%s"', $standardMemberGroupGllMode, $productGllMode ?? 'fehlt') => [
                    (string) $standardMemberGroupGllMode,
                    $productGllMode,
                    $expectedRejections[$productGllModeIndex],
                ];
            }
        }

        yield 'keine Standard-Benutzergruppe, Produkt "never"' => [null, 'never', true];
        yield 'keine Standard-Benutzergruppe, Produkt "inherit"' => [null, 'inherit', false];
    }

    #[DataProvider('variantOverrideGllProvider')]
    public function testVariantOverrideIsRejectedWhenGllIsOffForParentProduct(
        ?string $standardMemberGroupGllMode,
        ?string $productGllMode,
        bool $expectedRejection
    ): void {
        $productData = [
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '60',
            'guaranteeBrand' => 'Parent Brand',
            'guaranteeModelIdentifier' => 'ParentM',
        ];

        if ($productGllMode !== null) {
            $productData['gllMode'] = $productGllMode;
        }

        $result = $this->createValidator($standardMemberGroupGllMode)->validateVariant(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            $productData,
        );

        self::assertSame(
            $expectedRejection ? [ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_GLL_REQUIRED] : [],
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
                'gllMode' => 'always',
            ],
        );

        self::assertSame([], $result->getMessages());
    }

    public function testVariantInheritAndDisabledAreNotRejectedWhenGllIsOffForParentProduct(): void
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
                    'gllMode' => 'never',
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
                'gllMode' => 'inherit',
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

    public function testProductValidationAcceptsLongBrandAndModelValues(): void
    {
        $validator = $this->createValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateProduct([
            'gllMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
    }

    public function testProductValidationAcceptsLongBrandAndModelEvenWhenGaranIsDisabled(): void
    {
        $validator = $this->createValidator();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $validator->validateProduct([
            'gllMode' => 'inherit',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertSame([], $result->getMessages());
        self::assertSame($brand, $result->getNormalizedData()['guaranteeBrand']);
        self::assertSame($model, $result->getNormalizedData()['guaranteeModelIdentifier']);
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
                'gllMode' => 'inherit',
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
                'gllMode' => 'inherit',
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
                'gllMode' => 'inherit',
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
     * Vollständige Produktzeile mit eingeschalteter Herstellergarantie; `null` lässt `gllMode`
     * weg (fehlende Spalte).
     *
     * @return array<string, mixed>
     */
    private function createCompleteGaranProductRow(?string $productGllMode): array
    {
        $productRow = [
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model',
        ];

        if ($productGllMode !== null) {
            $productRow['gllMode'] = $productGllMode;
        }

        return $productRow;
    }

    /**
     * `null` heißt "keine Standard-Benutzergruppe gesetzt".
     */
    private function createValidator(?string $standardMemberGroupGllMode = 'on'): ProductGuaranteeConfigurationValidator
    {
        if ($standardMemberGroupGllMode === null) {
            unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
        } else {
            $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = self::STANDARD_MEMBER_GROUP_ID;
        }

        return new ProductGuaranteeConfigurationValidator(
            standardMemberGroupGllMode: new StandardMemberGroupGllMode(
                static fn (int $groupId): array => ['id' => $groupId, 'lsShopGllMode' => $standardMemberGroupGllMode]
            )
        );
    }
}
