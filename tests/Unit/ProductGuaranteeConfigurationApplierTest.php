<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationApplier;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductGuaranteeConfigurationApplierTest extends TestCase
{
    private mixed $originalTlConfig;
    private mixed $originalTlLang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;
        $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = 3;
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuaranteeImport'] = [
            'apiMessage' => 'Feld <%s>: %s',
            'messages' => [
                ProductGuaranteeConfigurationValidator::MESSAGE_LGN_INVALID_MODE => 'Text lgn_invalid_mode <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE => 'Text garan_invalid_mode <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 'Text missing_brand',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL => 'Text missing_model',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION => 'Text missing_duration',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => 'Text invalid_duration <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_REQUIRED => 'Text lgn_required',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_REQUIRED_ALWAYS => 'Text lgn_required_always',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_LGN_VARIANT_OVERRIDE => 'Text variant_override <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED => 'Text override_lgn_required',
            ],
        ];
    }

    protected function tearDown(): void
    {
        foreach (['TL_CONFIG' => $this->originalTlConfig, 'TL_LANG' => $this->originalTlLang] as $globalName => $originalValue) {
            if ($originalValue === null) {
                unset($GLOBALS[$globalName]);
            } else {
                $GLOBALS[$globalName] = $originalValue;
            }
        }

        parent::tearDown();
    }

    private function createApplier(string $standardMemberGroupLgnMode = 'on'): ProductGuaranteeConfigurationApplier
    {
        return new ProductGuaranteeConfigurationApplier(
            new ProductGuaranteeConfigurationValidator(
                standardMemberGroupLgnMode: new StandardMemberGroupLgnMode(
                    static fn (int $groupId): array => ['id' => $groupId, 'lsShopLgnMode' => $standardMemberGroupLgnMode]
                )
            )
        );
    }

    public function testApplyToProductRowPrefillsBrandAndKeepsMonths(): void
    {
        $applier = $this->createApplier();
        $producer = "ACME & Co. 'Premium' " . str_repeat('X', 60);

        $result = $applier->applyToProductRow([
            'producer' => $producer,
            'lgnMode' => 'inherit',
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '30',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Model42',
        ]);

        self::assertSame("ACME & Co. 'Premium' " . str_repeat('X', 60), $result['row']['guaranteeBrand']);
        self::assertSame('30', $result['row']['guaranteeDurationMonths']);
        self::assertSame([], $result['messages']);
    }

    /**
     * Leere Zelle und fehlende Spalte ergeben `inherit`, auch wenn das Produkt gespeichert
     * auf `always` oder `never` steht; `always` und `never` werden übernommen
     * (Spec 27, Abschnitt 10).
     *
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function productRowLgnModeProvider(): iterable
    {
        yield 'Spalte fehlt' => [[], 'inherit'];
        yield 'leere Zelle' => [['lgnMode' => ''], 'inherit'];
        yield 'Leerzeichen' => [['lgnMode' => '  '], 'inherit'];
        yield 'inherit' => [['lgnMode' => 'inherit'], 'inherit'];
        yield 'always' => [['lgnMode' => 'always'], 'always'];
        yield 'never' => [['lgnMode' => 'never'], 'never'];
        yield 'always mit Leerzeichen' => [['lgnMode' => ' always '], 'always'];
    }

    /**
     * @param array<string, mixed> $lgnModeCell
     */
    #[DataProvider('productRowLgnModeProvider')]
    public function testApplyToProductRowWritesLgnMode(array $lgnModeCell, string $expectedLgnMode): void
    {
        $result = $this->createApplier()->applyToProductRow(array_merge(
            [
                'producer' => 'ACME',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            $lgnModeCell
        ));

        self::assertSame([], $result['messages']);
        self::assertSame($expectedLgnMode, $result['row']['lgnMode']);
    }

    public function testApplyToProductRowIgnoresEnableGllColumn(): void
    {
        $applier = $this->createApplier();

        $resultWithoutLgnMode = $applier->applyToProductRow([
            'producer' => 'ACME',
            'enableGll' => '',
            'enableGaran' => '',
        ]);
        $resultWithNever = $applier->applyToProductRow([
            'producer' => 'ACME',
            'enableGll' => '1',
            'lgnMode' => 'never',
            'enableGaran' => '',
        ]);

        self::assertSame('inherit', $resultWithoutLgnMode['row']['lgnMode']);
        self::assertSame('never', $resultWithNever['row']['lgnMode']);
        self::assertSame('', $resultWithoutLgnMode['row']['enableGll']);
        self::assertSame('1', $resultWithNever['row']['enableGll']);
    }

    public function testApplyToVariantRowKeepsOverrideModeWhenBrandIsMissing(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '54',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertSame('override', $result['row']['garanMode']);
        self::assertSame('guaranteeBrand', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND,
            $result['messages'][0]['code']
        );
    }

    public function testWriteMessagesBlockPersistenceAndExposeReadableError(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '31',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model42',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame(
            'Feld <guaranteeDurationMonths>: Text invalid_duration <31>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testApplyToProductRowValidatesRawImportMonthsAgainstStoredYearLikeRowValue(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToProductRow(
            [
                'producer' => 'ACME Corporation',
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '216',
                'guaranteeBrand' => 'ACME',
                'guaranteeModelIdentifier' => 'Model42',
            ],
            true,
            'producer',
            [],
            '24'
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertNull($result['row']['guaranteeDurationMonths']);
        self::assertSame(
            'Feld <guaranteeDurationMonths>: Text invalid_duration <24>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function lgnRequiredProvider(): iterable
    {
        yield 'Gruppe on, Produkt never' => ['on', 'never', 'Feld <lgnMode>: Text lgn_required'];
        yield 'Gruppe blocked, Produkt never' => ['blocked', 'never', 'Feld <lgnMode>: Text lgn_required'];
        yield 'Gruppe off, Produkt inherit' => ['off', 'inherit', 'Feld <lgnMode>: Text lgn_required_always'];
        yield 'Gruppe off, Produkt never' => ['off', 'never', 'Feld <lgnMode>: Text lgn_required_always'];
    }

    #[DataProvider('lgnRequiredProvider')]
    public function testWriteMessagesExposeLgnDependencyOnLgnMode(
        string $standardMemberGroupLgnMode,
        string $productLgnMode,
        string $expectedMessage
    ): void {
        $applier = $this->createApplier($standardMemberGroupLgnMode);

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'lgnMode' => $productLgnMode,
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model42',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('lgnMode', $result['messages'][0]['field']);
        self::assertSame($expectedMessage, $applier->formatWriteErrorMessage($result['messages']));
        self::assertSame($productLgnMode, $result['row']['lgnMode']);
        self::assertSame('1', $result['row']['enableGaran']);
    }

    public function testWriteMessagesExposeInvalidLgnMode(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'lgnMode' => 'sometimes',
            'enableGaran' => '',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('lgnMode', $result['messages'][0]['field']);
        self::assertSame(
            'Feld <lgnMode>: Text lgn_invalid_mode <sometimes>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeOverrideLgnDependencyOnGaranMode(): void
    {
        $applier = $this->createApplier('off');

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VariantM',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('garanMode', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED,
            $result['messages'][0]['code']
        );
        self::assertSame(
            'Feld <garanMode>: Text override_lgn_required',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testApplyToVariantRowValidatesRawImportMonthsAgainstStoredYearLikeRowValue(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToVariantRow(
            [
                'garanMode' => 'override',
                'guaranteeDurationMonths' => '216',
                'guaranteeBrand' => 'Variant Brand',
                'guaranteeModelIdentifier' => 'VarMod',
            ],
            [
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '186',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
            '24'
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertNull($result['row']['guaranteeDurationMonths']);
        self::assertSame(
            'Feld <guaranteeDurationMonths>: Text invalid_duration <24>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeAffectedVariantsOnLgnMode(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToProductRow(
            [
                'producer' => 'ACME Corporation',
                'lgnMode' => 'never',
                'enableGaran' => '',
                'guaranteeDurationMonths' => null,
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            true,
            'producer',
            [
                [
                    'lsShopVariantCode' => 'VAR-001',
                    'garanMode' => 'override',
                ],
            ]
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('lgnMode', $result['messages'][0]['field']);
        self::assertSame(
            'Feld <lgnMode>: Text variant_override <VAR-001>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testFormatWriteErrorMessageJoinsSeveralMessages(): void
    {
        $applier = $this->createApplier('off');

        $result = $applier->applyToProductRow(
            [
                'producer' => 'ACME Corporation',
                'lgnMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '36',
                'guaranteeBrand' => 'ACME',
                'guaranteeModelIdentifier' => 'Model42',
            ],
            true,
            'producer',
            [['lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override']]
        );

        self::assertSame(
            'Feld <lgnMode>: Text variant_override <VAR-001>; Feld <lgnMode>: Text lgn_required_always',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testApplyToProductRowKeepsLongValuesWhenGaranIsDisabled(): void
    {
        $applier = $this->createApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'lgnMode' => 'inherit',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => $brand,
            'guaranteeModelIdentifier' => $model,
        ]);

        self::assertFalse($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame($brand, $result['row']['guaranteeBrand']);
        self::assertSame($model, $result['row']['guaranteeModelIdentifier']);
    }

    public function testApplyToVariantRowKeepsInheritedLongValuesWhenOverrideIsDisabled(): void
    {
        $applier = $this->createApplier();
        $brand = 'ÄÖÜ&<>ß' . str_repeat('A', 80);
        $model = 'ä&"<>ß' . str_repeat('B', 60);

        $result = $applier->applyToVariantRow(
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

        self::assertFalse($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('inherit', $result['row']['garanMode']);
        self::assertSame('', $result['row']['guaranteeBrand']);
        self::assertSame('', $result['row']['guaranteeModelIdentifier']);
    }

    public function testWriteMessagesExposeInvalidGaranMode(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToVariantRow(
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

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('garanMode', $result['messages'][0]['field']);
        self::assertSame(
            'Feld <garanMode>: Text garan_invalid_mode <bogus>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }
}
