<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupGllMode;
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
                ProductGuaranteeConfigurationValidator::MESSAGE_GLL_INVALID_MODE => 'Text gll_invalid_mode <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_INVALID_MODE => 'Text garan_invalid_mode <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_BRAND => 'Text missing_brand',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_MODEL => 'Text missing_model',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_MISSING_DURATION => 'Text missing_duration',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_INVALID_DURATION => 'Text invalid_duration <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED => 'Text gll_required',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_REQUIRED_ALWAYS => 'Text gll_required_always',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_DISABLED_GLL_VARIANT_OVERRIDE => 'Text variant_override <%s>',
                ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_GLL_REQUIRED => 'Text override_gll_required',
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

    private function createApplier(string $standardMemberGroupGllMode = 'on'): ProductGuaranteeConfigurationApplier
    {
        return new ProductGuaranteeConfigurationApplier(
            new ProductGuaranteeConfigurationValidator(
                standardMemberGroupGllMode: new StandardMemberGroupGllMode(
                    static fn (int $groupId): array => ['id' => $groupId, 'lsShopGllMode' => $standardMemberGroupGllMode]
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
            'gllMode' => 'inherit',
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
    public static function productRowGllModeProvider(): iterable
    {
        yield 'Spalte fehlt' => [[], 'inherit'];
        yield 'leere Zelle' => [['gllMode' => ''], 'inherit'];
        yield 'Leerzeichen' => [['gllMode' => '  '], 'inherit'];
        yield 'inherit' => [['gllMode' => 'inherit'], 'inherit'];
        yield 'always' => [['gllMode' => 'always'], 'always'];
        yield 'never' => [['gllMode' => 'never'], 'never'];
        yield 'always mit Leerzeichen' => [['gllMode' => ' always '], 'always'];
    }

    /**
     * @param array<string, mixed> $gllModeCell
     */
    #[DataProvider('productRowGllModeProvider')]
    public function testApplyToProductRowWritesGllMode(array $gllModeCell, string $expectedGllMode): void
    {
        $result = $this->createApplier()->applyToProductRow(array_merge(
            [
                'producer' => 'ACME',
                'enableGaran' => '',
                'guaranteeDurationMonths' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => '',
            ],
            $gllModeCell
        ));

        self::assertSame([], $result['messages']);
        self::assertSame($expectedGllMode, $result['row']['gllMode']);
    }

    public function testApplyToProductRowIgnoresEnableGllColumn(): void
    {
        $applier = $this->createApplier();

        $resultWithoutGllMode = $applier->applyToProductRow([
            'producer' => 'ACME',
            'enableGll' => '',
            'enableGaran' => '',
        ]);
        $resultWithNever = $applier->applyToProductRow([
            'producer' => 'ACME',
            'enableGll' => '1',
            'gllMode' => 'never',
            'enableGaran' => '',
        ]);

        self::assertSame('inherit', $resultWithoutGllMode['row']['gllMode']);
        self::assertSame('never', $resultWithNever['row']['gllMode']);
        self::assertSame('', $resultWithoutGllMode['row']['enableGll']);
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
                'gllMode' => 'inherit',
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
                'gllMode' => 'inherit',
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
    public static function gllRequiredProvider(): iterable
    {
        yield 'Gruppe on, Produkt never' => ['on', 'never', 'Feld <gllMode>: Text gll_required'];
        yield 'Gruppe blocked, Produkt never' => ['blocked', 'never', 'Feld <gllMode>: Text gll_required'];
        yield 'Gruppe off, Produkt inherit' => ['off', 'inherit', 'Feld <gllMode>: Text gll_required_always'];
        yield 'Gruppe off, Produkt never' => ['off', 'never', 'Feld <gllMode>: Text gll_required_always'];
    }

    #[DataProvider('gllRequiredProvider')]
    public function testWriteMessagesExposeGllDependencyOnGllMode(
        string $standardMemberGroupGllMode,
        string $productGllMode,
        string $expectedMessage
    ): void {
        $applier = $this->createApplier($standardMemberGroupGllMode);

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'gllMode' => $productGllMode,
            'enableGaran' => '1',
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => 'ACME',
            'guaranteeModelIdentifier' => 'Model42',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('gllMode', $result['messages'][0]['field']);
        self::assertSame($expectedMessage, $applier->formatWriteErrorMessage($result['messages']));
        self::assertSame($productGllMode, $result['row']['gllMode']);
        self::assertSame('1', $result['row']['enableGaran']);
    }

    public function testWriteMessagesExposeInvalidGllMode(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToProductRow([
            'producer' => 'ACME Corporation',
            'gllMode' => 'sometimes',
            'enableGaran' => '',
        ]);

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('gllMode', $result['messages'][0]['field']);
        self::assertSame(
            'Feld <gllMode>: Text gll_invalid_mode <sometimes>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testWriteMessagesExposeOverrideGllDependencyOnGaranMode(): void
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
                'gllMode' => 'inherit',
                'enableGaran' => '1',
                'guaranteeDurationMonths' => '60',
                'guaranteeBrand' => 'Parent Brand',
                'guaranteeModelIdentifier' => 'ParentM',
            ],
        );

        self::assertTrue($applier->hasBlockingWriteMessages($result['messages']));
        self::assertSame('garanMode', $result['messages'][0]['field']);
        self::assertSame(
            ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_GLL_REQUIRED,
            $result['messages'][0]['code']
        );
        self::assertSame(
            'Feld <garanMode>: Text override_gll_required',
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
                'gllMode' => 'inherit',
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

    public function testWriteMessagesExposeAffectedVariantsOnGllMode(): void
    {
        $applier = $this->createApplier();

        $result = $applier->applyToProductRow(
            [
                'producer' => 'ACME Corporation',
                'gllMode' => 'never',
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
        self::assertSame('gllMode', $result['messages'][0]['field']);
        self::assertSame(
            'Feld <gllMode>: Text variant_override <VAR-001>',
            $applier->formatWriteErrorMessage($result['messages'])
        );
    }

    public function testFormatWriteErrorMessageJoinsSeveralMessages(): void
    {
        $applier = $this->createApplier('off');

        $result = $applier->applyToProductRow(
            [
                'producer' => 'ACME Corporation',
                'gllMode' => 'inherit',
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
            'Feld <gllMode>: Text variant_override <VAR-001>; Feld <gllMode>: Text gll_required_always',
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
            'gllMode' => 'inherit',
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
                'gllMode' => 'inherit',
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
                'gllMode' => 'inherit',
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
