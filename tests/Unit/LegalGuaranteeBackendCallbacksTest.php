<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Contao\System;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupGllMode;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\LegalGuaranteeBackendCallbacks;
use LeadingSystems\MerconisBundle\LegalGuarantee\ProductData\ProductGuaranteeConfigurationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Regeln R1 bis R4 und Hinweise nach Spec 27, Abschnitt 4. Der Bezug ist der gespeicherte
 * Wert der Standard-Mitgliedergruppe; `null` steht in den Data-Providern für "keine
 * Standard-Mitgliedergruppe gesetzt".
 */
final class LegalGuaranteeBackendCallbacksTest extends TestCase
{
    private const STANDARD_MEMBER_GROUP_ID = 3;
    private const R1_MESSAGE = 'Produkt: gesetzliche Gewährleistung auf "%s" gestellt.';
    private const R2_MESSAGE = 'Produkt: Herstellergarantie ausgeschaltet.';
    private const R4_MESSAGE = 'Produkt: abgelehnt wegen Varianten %s.';
    private const OPEN_NEVER_MESSAGE = 'Produkt: Ursache Nie anzeigen.';
    private const OPEN_GROUP_OFF_MESSAGE = 'Produkt: Ursache Standard-Mitgliedergruppe.';
    private const OPEN_VARIANTS_MESSAGE = 'Produkt: Varianten %s.';
    private const R3_MESSAGE = 'Variante: Eigene Angaben abgelehnt.';
    private const OPEN_VARIANT_MESSAGE = 'Variante: Hinweis auf das Produkt.';

    private mixed $originalTlConfig;
    private mixed $originalTlLang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTlConfig = $GLOBALS['TL_CONFIG'] ?? null;
        $this->originalTlLang = $GLOBALS['TL_LANG'] ?? null;
        $this->resetPendingProductInfoMessages();
        $this->primeMessageContainer();

        $GLOBALS['TL_LANG']['tl_ls_shop_product']['gllModeOptions'] = [
            'inherit' => ['Wie Mitgliedergruppe', 'Erläuterung zu Wie Mitgliedergruppe.'],
            'always' => ['Immer anzeigen', 'Erläuterung zu Immer anzeigen.'],
            'never' => ['Nie anzeigen', 'Erläuterung zu Nie anzeigen.'],
        ];
        $GLOBALS['TL_LANG']['tl_ls_shop_product']['guaranteeValidationMessages'] = [
            'garan_disabled_invalid_duration' => 'Produkt: Ungültige Dauer %s.',
        ];
        $GLOBALS['TL_LANG']['tl_ls_shop_product']['legalGuaranteeMessages'] = [
            'gllModeChangedForManufacturerGuarantee' => self::R1_MESSAGE,
            'manufacturerGuaranteeDisabledByGllMode' => self::R2_MESSAGE,
            'gllModeRejectedByOwnVariantDetails' => self::R4_MESSAGE,
            'openHintProductNeverShown' => self::OPEN_NEVER_MESSAGE,
            'openHintStandardGroupOnlyAlwaysShow' => self::OPEN_GROUP_OFF_MESSAGE,
            'openHintVariantsWithOwnDetails' => self::OPEN_VARIANTS_MESSAGE,
        ];
        $GLOBALS['TL_LANG']['tl_ls_shop_variant']['guaranteeValidationMessages'] = [
            'garan_invalid_mode' => 'Variante: Ungültiger Modus %s.',
            'garan_disabled_invalid_duration' => 'Variante: Ungültige Dauer %s.',
        ];
        $GLOBALS['TL_LANG']['tl_ls_shop_variant']['legalGuaranteeMessages'] = [
            'ownDetailsRejectedWithoutLegalGuarantee' => self::R3_MESSAGE,
            'openHintParentProductWithoutLegalGuarantee' => self::OPEN_VARIANT_MESSAGE,
        ];
    }

    protected function tearDown(): void
    {
        Input::setPost('enableGaran', null);
        Input::setPost('enableGaran_42', null);
        Input::setPost('FORM_SUBMIT', null);
        Input::resetCache();
        Environment::reset();
        $this->primeMessageContainer();
        Message::reset();
        $this->resetPendingProductInfoMessages();
        $this->restoreGlobal('TL_CONFIG', $this->originalTlConfig);
        $this->restoreGlobal('TL_LANG', $this->originalTlLang);

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function standardMemberGroupGllModeProvider(): iterable
    {
        yield 'Gruppe on' => ['on'];
        yield 'Gruppe off' => ['off'];
        yield 'Gruppe blocked' => ['blocked'];
        yield 'Gruppe leer' => [''];
        yield 'keine Standard-Mitgliedergruppe gesetzt' => [null];
    }

    /**
     * R1 bei einem Speichern ohne geänderte Felder: `gllMode` und `enableGaran` stehen nicht
     * im Request außer `gllMode` selbst mit dem gespeicherten Wert.
     *
     * @return iterable<string, array{string|null, string, string}>
     */
    public static function r1Provider(): iterable
    {
        yield 'Gruppe on, never' => ['on', 'never', 'inherit'];
        yield 'Gruppe off, inherit' => ['off', 'inherit', 'always'];
        yield 'Gruppe off, never' => ['off', 'never', 'always'];
        yield 'Gruppe blocked, never' => ['blocked', 'never', 'inherit'];
        yield 'Gruppe leer, never' => ['', 'never', 'inherit'];
        yield 'keine Standard-Mitgliedergruppe, never' => [null, 'never', 'inherit'];
    }

    #[DataProvider('r1Provider')]
    public function testR1SetsGllModeOnSaveWithoutChangedFields(
        ?string $standardMemberGroupGllMode,
        string $storedGllMode,
        string $expectedGllMode
    ): void {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($storedGllMode, '1')]
        );

        self::assertSame($expectedGllMode, $callbacks->handleProductGllModeSave($storedGllMode, $this->createDataContainer(7)));

        $callbacks->addProductSubmitInfoMessages($this->createDataContainer(7));

        [$expectedGllModeLabel, $expectedGllModeExplanation] = $GLOBALS['TL_LANG']['tl_ls_shop_product']['gllModeOptions'][$expectedGllMode];
        $generatedMessages = Message::generateUnwrapped('BE');

        self::assertStringContainsString(sprintf(self::R1_MESSAGE, $expectedGllModeLabel), $generatedMessages);
        self::assertStringNotContainsString($expectedGllModeExplanation, $generatedMessages);
        self::assertStringNotContainsString('Array', $generatedMessages);
    }

    /**
     * Die Meldung zu R1 nennt die Beschriftung aus dem Paar `[Beschriftung, Erläuterung]`
     * (Implementierungsentscheidung 0027), für beide Zielwerte von R1.
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function r1MessageLabelProvider(): iterable
    {
        yield 'Zielwert inherit' => ['on', 'never', 'Wie Mitgliedergruppe'];
        yield 'Zielwert always' => ['off', 'inherit', 'Immer anzeigen'];
    }

    #[DataProvider('r1MessageLabelProvider')]
    public function testR1MessageUsesLabelAndNotExplanation(
        string $standardMemberGroupGllMode,
        string $storedGllMode,
        string $expectedGllModeLabel
    ): void {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($storedGllMode, '1')]
        );

        $callbacks->handleProductGllModeSave($storedGllMode, $this->createDataContainer(7));
        $callbacks->addProductSubmitInfoMessages($this->createDataContainer(7));

        $generatedMessages = Message::generateUnwrapped('BE');

        self::assertStringContainsString(sprintf(self::R1_MESSAGE, $expectedGllModeLabel), $generatedMessages);
        self::assertStringNotContainsString('Erläuterung zu', $generatedMessages);
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function r1NotApplicableProvider(): iterable
    {
        yield 'Gruppe on, inherit' => ['on', 'inherit'];
        yield 'Gruppe on, always' => ['on', 'always'];
        yield 'Gruppe off, always' => ['off', 'always'];
        yield 'Gruppe blocked, inherit' => ['blocked', 'inherit'];
        yield 'Gruppe blocked, always' => ['blocked', 'always'];
        yield 'Gruppe leer, inherit' => ['', 'inherit'];
        yield 'keine Standard-Mitgliedergruppe, inherit' => [null, 'inherit'];
    }

    #[DataProvider('r1NotApplicableProvider')]
    public function testR1KeepsGllModeWhenGllIsNotOff(?string $standardMemberGroupGllMode, string $storedGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($storedGllMode, '1')]
        );

        self::assertSame($storedGllMode, $callbacks->handleProductGllModeSave($storedGllMode, $this->createDataContainer(7)));

        $callbacks->addProductSubmitInfoMessages($this->createDataContainer(7));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    #[DataProvider('standardMemberGroupGllModeProvider')]
    public function testR1KeepsGllModeWhenEnableGaranIsOff(?string $standardMemberGroupGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct('never', '')]
        );

        self::assertSame('never', $callbacks->handleProductGllModeSave('never', $this->createDataContainer(7)));
    }

    public function testR1UsesPostedEnableGaranBeforeStoredValue(): void
    {
        Input::setPost('enableGaran', '1');

        $callbacks = $this->createCallbacks('on', products: [7 => $this->createProduct('never', '')]);

        self::assertSame('inherit', $callbacks->handleProductGllModeSave('never', $this->createDataContainer(7)));
    }

    public function testR1DoesNotApplyWhenEnableGaranIsPostedOff(): void
    {
        Input::setPost('enableGaran', '');

        $callbacks = $this->createCallbacks('off', products: [7 => $this->createProduct('inherit', '1')]);

        self::assertSame('inherit', $callbacks->handleProductGllModeSave('inherit', $this->createDataContainer(7)));
    }

    public function testR1ReadsEditAllSuffixForEnableGaran(): void
    {
        Input::setPost('enableGaran_42', '1');

        $callbacks = $this->createCallbacks('off', products: [42 => $this->createProduct('inherit', '')]);

        self::assertSame('always', $callbacks->handleProductGllModeSave('inherit', $this->createDataContainer(42)));
    }

    /**
     * Ändert der Nutzer `gllMode` und gilt danach "GLL aus", setzt der `save_callback` nichts
     * (kein R1); R2 schaltet im `onbeforesubmit_callback` die Herstellergarantie aus.
     *
     * @return iterable<string, array{string|null, string, string}>
     */
    public static function r2Provider(): iterable
    {
        yield 'Gruppe on, inherit nach never' => ['on', 'inherit', 'never'];
        yield 'Gruppe on, always nach never' => ['on', 'always', 'never'];
        yield 'Gruppe off, always nach inherit' => ['off', 'always', 'inherit'];
        yield 'Gruppe off, always nach never' => ['off', 'always', 'never'];
        yield 'Gruppe off, vorher schon aus: inherit nach never' => ['off', 'inherit', 'never'];
        yield 'Gruppe off, vorher schon aus: never nach inherit' => ['off', 'never', 'inherit'];
        yield 'Gruppe blocked, inherit nach never' => ['blocked', 'inherit', 'never'];
        yield 'Gruppe leer, always nach never' => ['', 'always', 'never'];
        yield 'keine Standard-Mitgliedergruppe, inherit nach never' => [null, 'inherit', 'never'];
    }

    #[DataProvider('r2Provider')]
    public function testR2DisablesGaranInsteadOfR1WhenUserChangesGllMode(
        ?string $standardMemberGroupGllMode,
        string $storedGllMode,
        string $submittedGllMode
    ): void {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($storedGllMode, '1')]
        );

        self::assertSame(
            $submittedGllMode,
            $callbacks->handleProductGllModeSave($submittedGllMode, $this->createDataContainer(7))
        );

        $values = $callbacks->handleProductBeforeSubmit(
            ['gllMode' => $submittedGllMode, 'tstamp' => 1],
            $this->createDataContainer(7)
        );

        self::assertSame('', $values['enableGaran']);
        self::assertArrayNotHasKey('guaranteeDurationMonths', $values);
        self::assertArrayNotHasKey('guaranteeBrand', $values);
        self::assertArrayNotHasKey('guaranteeModelIdentifier', $values);

        $callbacks->addProductSubmitInfoMessages($this->createDataContainer(7));
        $messages = Message::generateUnwrapped('BE');

        self::assertStringContainsString(self::R2_MESSAGE, $messages);
        self::assertStringNotContainsString('gestellt', $messages);
    }

    #[DataProvider('standardMemberGroupGllModeProvider')]
    public function testR2DisablesGaranEnabledInSameEdit(?string $standardMemberGroupGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct('inherit', '')]
        );

        $values = $callbacks->handleProductBeforeSubmit(
            ['gllMode' => 'never', 'enableGaran' => '1', 'guaranteeBrand' => 'ACME'],
            $this->createDataContainer(7)
        );

        self::assertSame('', $values['enableGaran']);
        self::assertSame('ACME', $values['guaranteeBrand']);
    }

    #[DataProvider('standardMemberGroupGllModeProvider')]
    public function testR2KeepsGaranWhenChangedGllModeIsNotOff(?string $standardMemberGroupGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct('never', '1')]
        );

        $values = $callbacks->handleProductBeforeSubmit(['gllMode' => 'always'], $this->createDataContainer(7));

        self::assertSame(['gllMode' => 'always'], $values);
    }

    #[DataProvider('standardMemberGroupGllModeProvider')]
    public function testTogglingOnlyEnableGaranChangesNothing(?string $standardMemberGroupGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct('never', '')]
        );

        $values = $callbacks->handleProductBeforeSubmit(['enableGaran' => '1', 'tstamp' => 1], $this->createDataContainer(7));
        $callbacks->addProductSubmitInfoMessages($this->createDataContainer(7));

        self::assertSame(['enableGaran' => '1', 'tstamp' => 1], $values);
        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    /**
     * @return iterable<string, array{string|null, string, string}>
     */
    public static function r4Provider(): iterable
    {
        yield 'Gruppe on, inherit nach never' => ['on', 'inherit', 'never'];
        yield 'Gruppe off, always nach inherit' => ['off', 'always', 'inherit'];
        yield 'Gruppe off, inherit nach never' => ['off', 'inherit', 'never'];
        yield 'Gruppe blocked, always nach never' => ['blocked', 'always', 'never'];
        yield 'Gruppe leer, inherit nach never' => ['', 'inherit', 'never'];
        yield 'keine Standard-Mitgliedergruppe, inherit nach never' => [null, 'inherit', 'never'];
    }

    #[DataProvider('r4Provider')]
    public function testR4RejectsChangedGllModeAndNamesOverrideVariants(
        ?string $standardMemberGroupGllMode,
        string $storedGllMode,
        string $submittedGllMode
    ): void {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($storedGllMode, '1')],
            relatedVariants: [7 => $this->createRelatedVariants()]
        );

        try {
            $callbacks->handleProductGllModeSave($submittedGllMode, $this->createDataContainer(7));
            self::fail('R4 hat nicht abgelehnt.');
        } catch (\Exception $exception) {
            self::assertSame(sprintf(self::R4_MESSAGE, 'VAR-001, Variante B'), $exception->getMessage());
        }

        $callbacks->addProductSubmitInfoMessages($this->createDataContainer(7));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    #[DataProvider('standardMemberGroupGllModeProvider')]
    public function testR4DoesNotRejectUnchangedGllMode(?string $standardMemberGroupGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct('never', '')],
            relatedVariants: [7 => $this->createRelatedVariants()]
        );

        self::assertSame('never', $callbacks->handleProductGllModeSave('never', $this->createDataContainer(7)));
    }

    #[DataProvider('standardMemberGroupGllModeProvider')]
    public function testR4DoesNotRejectChangeWhenGllIsNotOffAfterwards(?string $standardMemberGroupGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct('never', '1')],
            relatedVariants: [7 => $this->createRelatedVariants()]
        );

        self::assertSame('always', $callbacks->handleProductGllModeSave('always', $this->createDataContainer(7)));
    }

    public function testR4DoesNotRejectWithoutOverrideVariants(): void
    {
        $callbacks = $this->createCallbacks(
            'on',
            products: [7 => $this->createProduct('inherit', '')],
            relatedVariants: [7 => [
                ['id' => 11, 'title' => 'Variante A', 'lsShopVariantCode' => 'VAR-001', 'garanMode' => 'inherit'],
                ['id' => 12, 'title' => 'Variante B', 'lsShopVariantCode' => 'VAR-002', 'garanMode' => 'disabled'],
            ]]
        );

        self::assertSame('never', $callbacks->handleProductGllModeSave('never', $this->createDataContainer(7)));
    }

    public function testR4RejectsBeforeR2(): void
    {
        $callbacks = $this->createCallbacks(
            'on',
            products: [7 => $this->createProduct('inherit', '1')],
            relatedVariants: [7 => $this->createRelatedVariants()]
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf(self::R4_MESSAGE, 'VAR-001, Variante B'));

        $callbacks->handleProductGllModeSave('never', $this->createDataContainer(7));
    }

    public function testSubmitInfoMessagesAppearOnlyAfterSuccessfulSave(): void
    {
        $callbacks = $this->createCallbacks('off', products: [7 => $this->createProduct('inherit', '1')]);

        $callbacks->handleProductGllModeSave('inherit', $this->createDataContainer(7));

        self::assertSame('', Message::generateUnwrapped('BE'));

        $callbacks->addProductSubmitInfoMessages($this->createDataContainer(7));

        self::assertStringContainsString(sprintf(self::R1_MESSAGE, 'Immer anzeigen'), Message::generateUnwrapped('BE'));
    }

    /**
     * @return iterable<string, array{string|null, string, string}>
     */
    public static function productOpenHintCauseProvider(): iterable
    {
        yield 'Gruppe on, never' => ['on', 'never', self::OPEN_NEVER_MESSAGE];
        yield 'Gruppe off, never' => ['off', 'never', self::OPEN_NEVER_MESSAGE];
        yield 'Gruppe off, inherit' => ['off', 'inherit', self::OPEN_GROUP_OFF_MESSAGE];
        yield 'Gruppe blocked, never' => ['blocked', 'never', self::OPEN_NEVER_MESSAGE];
        yield 'Gruppe leer, never' => ['', 'never', self::OPEN_NEVER_MESSAGE];
        yield 'keine Standard-Mitgliedergruppe, never' => [null, 'never', self::OPEN_NEVER_MESSAGE];
    }

    #[DataProvider('productOpenHintCauseProvider')]
    public function testProductOpenHintNamesCause(
        ?string $standardMemberGroupGllMode,
        string $storedGllMode,
        string $expectedMessage
    ): void {
        $this->primeMessageContainer(['act' => 'edit']);

        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($storedGllMode, '1')]
        );

        $callbacks->addProductEditInfoMessage($this->createDataContainer(7));
        $messages = Message::generateUnwrapped('BE');

        self::assertStringContainsString($expectedMessage, $messages);
        self::assertStringNotContainsString(
            $expectedMessage === self::OPEN_NEVER_MESSAGE ? self::OPEN_GROUP_OFF_MESSAGE : self::OPEN_NEVER_MESSAGE,
            $messages
        );
        self::assertStringNotContainsString('Varianten', $messages);
    }

    public function testProductOpenHintNamesVariantsWithoutEnabledGaran(): void
    {
        $this->primeMessageContainer(['act' => 'edit']);

        $callbacks = $this->createCallbacks(
            'off',
            products: [7 => $this->createProduct('inherit', '')],
            relatedVariants: [7 => $this->createRelatedVariants()]
        );

        $callbacks->addProductEditInfoMessage($this->createDataContainer(7));
        $messages = Message::generateUnwrapped('BE');

        self::assertStringContainsString(sprintf(self::OPEN_VARIANTS_MESSAGE, 'VAR-001, Variante B'), $messages);
        self::assertStringNotContainsString(self::OPEN_GROUP_OFF_MESSAGE, $messages);
    }

    public function testProductOpenHintShowsCauseAndVariantsTogether(): void
    {
        $this->primeMessageContainer(['act' => 'edit']);

        $callbacks = $this->createCallbacks(
            'on',
            products: [7 => $this->createProduct('never', '1')],
            relatedVariants: [7 => $this->createRelatedVariants()]
        );

        $callbacks->addProductEditInfoMessage($this->createDataContainer(7));
        $messages = Message::generateUnwrapped('BE');

        self::assertStringContainsString(self::OPEN_NEVER_MESSAGE, $messages);
        self::assertStringContainsString(sprintf(self::OPEN_VARIANTS_MESSAGE, 'VAR-001, Variante B'), $messages);
    }

    #[DataProvider('r1NotApplicableProvider')]
    public function testProductOpenHintIsSkippedWhenGllIsNotOff(?string $standardMemberGroupGllMode, string $storedGllMode): void
    {
        $this->primeMessageContainer(['act' => 'edit']);

        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($storedGllMode, '1')],
            relatedVariants: [7 => $this->createRelatedVariants()]
        );

        $callbacks->addProductEditInfoMessage($this->createDataContainer(7));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    public function testProductOpenHintIsSkippedOnFormSubmit(): void
    {
        $this->primeMessageContainer(['act' => 'edit']);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');

        $this->createCallbacks('on', products: [7 => $this->createProduct('never', '1')])
            ->addProductEditInfoMessage($this->createDataContainer(7));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    public function testProductOpenHintIsSkippedOnAjaxRequest(): void
    {
        $this->primeMessageContainer(['act' => 'edit']);
        Environment::set('isAjaxRequest', true);

        $this->createCallbacks('on', products: [7 => $this->createProduct('never', '1')])
            ->addProductEditInfoMessage($this->createDataContainer(7));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    public function testProductOpenHintIsSkippedOutsideEditAction(): void
    {
        $this->primeMessageContainer(['act' => 'editAll']);

        $this->createCallbacks('on', products: [7 => $this->createProduct('never', '1')])
            ->addProductEditInfoMessage($this->createDataContainer(7));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    /**
     * @return iterable<string, array{string|null, string}>
     */
    public static function r3Provider(): iterable
    {
        yield 'Gruppe on, never' => ['on', 'never'];
        yield 'Gruppe off, inherit' => ['off', 'inherit'];
        yield 'Gruppe off, never' => ['off', 'never'];
        yield 'Gruppe off, gllMode fehlt' => ['off', ''];
        yield 'Gruppe blocked, never' => ['blocked', 'never'];
        yield 'Gruppe leer, never' => ['', 'never'];
        yield 'keine Standard-Mitgliedergruppe, never' => [null, 'never'];
    }

    #[DataProvider('r3Provider')]
    public function testR3RejectsOwnDetailsWhenGllIsOffForParentProduct(
        ?string $standardMemberGroupGllMode,
        string $parentGllMode
    ): void {
        $parentProduct = $this->createProduct($parentGllMode, '1');

        if ('' === $parentGllMode) {
            unset($parentProduct['gllMode']);
        }

        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $parentProduct],
            variants: [15 => $this->createVariant('inherit')]
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(self::R3_MESSAGE);

        $callbacks->validateVariantGaranMode('override', $this->createDataContainer(15));
    }

    #[DataProvider('standardMemberGroupGllModeProvider')]
    public function testR3AcceptsInheritAndDisabled(?string $standardMemberGroupGllMode): void
    {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct('never', '1')],
            variants: [15 => $this->createVariant('override')]
        );

        self::assertSame('inherit', $callbacks->validateVariantGaranMode('inherit', $this->createDataContainer(15)));
        self::assertSame('disabled', $callbacks->validateVariantGaranMode('disabled', $this->createDataContainer(15)));
    }

    #[DataProvider('r1NotApplicableProvider')]
    public function testR3AcceptsOwnDetailsWhenGllIsNotOffForParentProduct(
        ?string $standardMemberGroupGllMode,
        string $parentGllMode
    ): void {
        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($parentGllMode, '1')],
            variants: [15 => $this->createVariant('inherit', '36', 'Marke', 'Modell')]
        );

        self::assertSame('override', $callbacks->validateVariantGaranMode('override', $this->createDataContainer(15)));
    }

    #[DataProvider('r3Provider')]
    public function testVariantOpenHintPointsToProduct(?string $standardMemberGroupGllMode, string $parentGllMode): void
    {
        $this->primeMessageContainer(['act' => 'edit']);

        $callbacks = $this->createCallbacks(
            $standardMemberGroupGllMode,
            products: [7 => $this->createProduct($parentGllMode, '')],
            variants: [15 => $this->createVariant('override')]
        );

        $callbacks->addVariantEditInfoMessage($this->createDataContainer(15));

        self::assertStringContainsString(self::OPEN_VARIANT_MESSAGE, Message::generateUnwrapped('BE'));
    }

    public function testVariantOpenHintIsSkippedWhenGllIsNotOffForParentProduct(): void
    {
        $this->primeMessageContainer(['act' => 'edit']);

        $this->createCallbacks(
            'off',
            products: [7 => $this->createProduct('always', '')],
            variants: [15 => $this->createVariant('override')]
        )->addVariantEditInfoMessage($this->createDataContainer(15));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    public function testVariantOpenHintIsSkippedForInheritAndDisabled(): void
    {
        $this->primeMessageContainer(['act' => 'edit']);

        $callbacks = $this->createCallbacks(
            'on',
            products: [7 => $this->createProduct('never', '1')],
            variants: [15 => $this->createVariant('inherit'), 16 => $this->createVariant('disabled')]
        );

        $callbacks->addVariantEditInfoMessage($this->createDataContainer(15));
        $callbacks->addVariantEditInfoMessage($this->createDataContainer(16));

        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    public function testVariantOpenHintIsSkippedOnFormSubmitAjaxAndOutsideEditAction(): void
    {
        $callbacks = $this->createCallbacks(
            'on',
            products: [7 => $this->createProduct('never', '')],
            variants: [15 => $this->createVariant('override')]
        );

        $this->primeMessageContainer(['act' => 'edit']);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_variant');
        $callbacks->addVariantEditInfoMessage($this->createDataContainer(15));
        self::assertSame('', Message::generateUnwrapped('BE'));

        Input::setPost('FORM_SUBMIT', null);
        Environment::set('isAjaxRequest', true);
        $callbacks->addVariantEditInfoMessage($this->createDataContainer(15));
        self::assertSame('', Message::generateUnwrapped('BE'));

        Environment::reset();
        $this->primeMessageContainer(['act' => 'show']);
        $callbacks->addVariantEditInfoMessage($this->createDataContainer(15));
        self::assertSame('', Message::generateUnwrapped('BE'));
    }

    public function testProductDurationValidationReturnsNormalizedStoredValue(): void
    {
        $callbacks = $this->createCallbacks('on');

        self::assertSame(
            '30',
            $callbacks->validateProductGuaranteeDurationMonths('30', $this->createDataContainer(7))
        );
        self::assertNull(
            $callbacks->validateProductGuaranteeDurationMonths('', $this->createDataContainer(7))
        );
    }

    public function testProductDurationValidationThrowsForInvalidMonthValue(): void
    {
        $callbacks = $this->createCallbacks('on');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Produkt: Ungültige Dauer 31.');

        $callbacks->validateProductGuaranteeDurationMonths('31', $this->createDataContainer(7));
    }

    public function testProductGuaranteeBrandLoadUsesTrimmedProducerOnlyWhenStoredValueIsEmpty(): void
    {
        $product = $this->createProduct('inherit', '');
        $product['lsShopProductProducer'] = '  ACME  ';
        $callbacks = $this->createCallbacks('on', products: [7 => $product]);

        self::assertSame('ACME', $callbacks->loadProductGuaranteeBrand('', $this->createDataContainer(7)));
        self::assertSame('Bereits gesetzt', $callbacks->loadProductGuaranteeBrand('Bereits gesetzt', $this->createDataContainer(7)));
    }

    public function testVariantGuaranteeBrandLoadUsesParentBrandBeforeProducer(): void
    {
        $productWithBrand = $this->createProduct('inherit', '1');
        $productWithBrand['guaranteeBrand'] = 'Elternmarke';
        $productWithBrand['lsShopProductProducer'] = '  ACME  ';
        $productWithoutBrand = $this->createProduct('inherit', '1');
        $productWithoutBrand['lsShopProductProducer'] = '  ACME  ';
        $variantOfProductWithoutBrand = $this->createVariant('override');
        $variantOfProductWithoutBrand['id'] = 16;
        $variantOfProductWithoutBrand['pid'] = 8;

        $callbacks = $this->createCallbacks(
            'on',
            products: [7 => $productWithBrand, 8 => $productWithoutBrand],
            variants: [15 => $this->createVariant('override'), 16 => $variantOfProductWithoutBrand]
        );

        self::assertSame('Elternmarke', $callbacks->loadVariantGuaranteeBrand('', $this->createDataContainer(15)));
        self::assertSame('ACME', $callbacks->loadVariantGuaranteeBrand('', $this->createDataContainer(16)));
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<int, array<string, mixed>> $variants
     * @param array<int, list<array<string, mixed>>> $relatedVariants
     */
    private function createCallbacks(
        ?string $standardMemberGroupGllMode,
        array $products = [],
        array $variants = [],
        array $relatedVariants = [],
    ): LegalGuaranteeBackendCallbacks {
        if ($standardMemberGroupGllMode === null) {
            unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
        } else {
            $GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] = self::STANDARD_MEMBER_GROUP_ID;
        }

        $standardMemberGroupGllModeHelper = new StandardMemberGroupGllMode(
            static fn (int $groupId): array => self::STANDARD_MEMBER_GROUP_ID === $groupId
                ? ['id' => $groupId, 'lsShopGllMode' => $standardMemberGroupGllMode]
                : []
        );

        return new LegalGuaranteeBackendCallbacks(
            $this->createConnectionStub($products, $variants, $relatedVariants),
            new ProductGuaranteeConfigurationValidator(standardMemberGroupGllMode: $standardMemberGroupGllModeHelper),
            standardMemberGroupGllMode: $standardMemberGroupGllModeHelper,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createProduct(string $gllMode, string $enableGaran): array
    {
        return [
            'gllMode' => $gllMode,
            'enableGaran' => $enableGaran,
            'guaranteeDurationMonths' => '36',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => 'Modell',
            'lsShopProductProducer' => 'Producer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createVariant(
        string $garanMode,
        string $guaranteeDurationMonths = '',
        string $guaranteeBrand = '',
        string $guaranteeModelIdentifier = ''
    ): array {
        return [
            'id' => 15,
            'pid' => 7,
            'garanMode' => $garanMode,
            'guaranteeDurationMonths' => $guaranteeDurationMonths,
            'guaranteeBrand' => $guaranteeBrand,
            'guaranteeModelIdentifier' => $guaranteeModelIdentifier,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function createRelatedVariants(): array
    {
        return [
            ['id' => 11, 'title' => 'Variante A', 'lsShopVariantCode' => 'VAR-001', 'garanMode' => 'override'],
            ['id' => 12, 'title' => 'Variante B', 'lsShopVariantCode' => '', 'garanMode' => 'override'],
            ['id' => 13, 'title' => 'Variante C', 'lsShopVariantCode' => 'VAR-003', 'garanMode' => 'inherit'],
        ];
    }

    private function createDataContainer(int $id): DataContainer
    {
        return new class ($id) extends DataContainer {
            public function __construct(int $id)
            {
                $this->__set('id', $id);
                $this->__set('activeRecord', (object) ['id' => $id]);
            }

            public function getPalette()
            {
                return '';
            }

            protected function save($varValue)
            {
                return $varValue;
            }
        };
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @param array<int, array<string, mixed>> $variants
     * @param array<int, list<array<string, mixed>>> $relatedVariants
     */
    private function createConnectionStub(
        array $products = [],
        array $variants = [],
        array $relatedVariants = [],
    ): Connection {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('fetchAssociative')
            ->willReturnCallback(
                static function (string $query, array $params) use ($products, $variants): array|false {
                    $recordId = (int) ($params[0] ?? 0);

                    if (str_contains($query, 'FROM tl_ls_shop_product')) {
                        return $products[$recordId] ?? false;
                    }

                    if (str_contains($query, 'FROM tl_ls_shop_variant')) {
                        return $variants[$recordId] ?? false;
                    }

                    return false;
                }
            );

        $connection
            ->method('fetchAllAssociative')
            ->willReturnCallback(
                static function (string $query, array $params) use ($relatedVariants): array {
                    if (!str_contains($query, 'FROM tl_ls_shop_variant')) {
                        return [];
                    }

                    $productId = (int) ($params[0] ?? 0);

                    return $relatedVariants[$productId] ?? [];
                }
            );

        return $connection;
    }

    /**
     * @param array<string, string> $query
     */
    private function primeMessageContainer(array $query = []): void
    {
        Input::resetCache();

        $request = new Request($query);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher->method('isBackendRequest')->willReturn(true);
        $scopeMatcher->method('isFrontendRequest')->willReturn(false);

        $container = new ContainerBuilder();
        $container->set('request_stack', $requestStack);
        $container->set('contao.routing.scope_matcher', $scopeMatcher);

        System::setContainer($container);
        Message::reset();
    }

    private function resetPendingProductInfoMessages(): void
    {
        $pendingMessagesProperty = new ReflectionProperty(LegalGuaranteeBackendCallbacks::class, 'pendingProductInfoMessages');
        $pendingMessagesProperty->setValue(null, []);
    }

    private function restoreGlobal(string $globalName, mixed $originalValue): void
    {
        if ($originalValue === null) {
            unset($GLOBALS[$globalName]);
        } else {
            $GLOBALS[$globalName] = $originalValue;
        }
    }
}
