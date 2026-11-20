<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\System;
use Contao\Template;
use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingStandardPaymentModule;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EInvoicingStoredSepaMandateRenderingTest extends TestCase
{
    private const LANGUAGE_KEYS = [
        'einvoicing_sepa_mandate_heading',
        'einvoicing_sepa_mandate_account_holder_label',
        'einvoicing_sepa_mandate_iban_label',
        'einvoicing_sepa_mandate_reference_label',
        'einvoicing_sepa_mandate_change_notice',
        'einvoicing_sepa_mandate_value_unavailable',
    ];

    /**
     * @var array<string, string>|null
     */
    private ?array $originalLanguageStrings = null;

    /**
     * @var array<int|string, mixed>|null
     */
    private ?array $originalParseTemplateHooks = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootHostProjectFramework();

        System::loadLanguageFile('default');

        $this->originalLanguageStrings = [];

        foreach (self::LANGUAGE_KEYS as $languageKey) {
            $this->originalLanguageStrings[$languageKey] = $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis'][$languageKey];
            $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis'][$languageKey] = 'LANG-MARKER:' . $languageKey;
        }

        $this->originalParseTemplateHooks = $GLOBALS['TL_HOOKS']['parseTemplate'] ?? [];
    }

    protected function tearDown(): void
    {
        foreach ($this->originalLanguageStrings ?? [] as $languageKey => $originalLanguageString) {
            $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis'][$languageKey] = $originalLanguageString;
        }

        $GLOBALS['TL_HOOKS']['parseTemplate'] = $this->originalParseTemplateHooks ?? [];

        parent::tearDown();
    }

    public function testStoredMandateIsRenderedViaNamedContaoTemplateWithLanguageLabelsAndCssClasses(): void
    {
        $parsedTemplateNames = $this->recordParsedTemplateNames();

        $renderedCustomUserInterface = $this->createPaymentModuleWithMemberRecord([
            'firstname' => 'Lisa',
            'lastname' => 'Beispiel',
            EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD => 'MANDATE-2026-0001',
            EInvoicingSnapshotFields::MEMBER_DEBTOR_IBAN_FIELD => 'DE89370400440532013000',
        ])->getCustomUserInterface();

        self::assertSame(
            [EInvoicingStandardPaymentModule::STORED_SEPA_MANDATE_TEMPLATE],
            $parsedTemplateNames::$templateNames
        );
        self::assertSame(
            'template_einvoicingStoredSepaMandate_default',
            EInvoicingStandardPaymentModule::STORED_SEPA_MANDATE_TEMPLATE
        );

        foreach ([
            'lsShopStoredSepaMandate',
            'lsShopStoredSepaMandate__headline',
            'lsShopStoredSepaMandate__row',
            'lsShopStoredSepaMandate__label',
            'lsShopStoredSepaMandate__value',
            'lsShopStoredSepaMandate__hint',
        ] as $cssClass) {
            self::assertStringContainsString('class="' . $cssClass . '"', $renderedCustomUserInterface);
        }

        self::assertStringContainsString('LANG-MARKER:einvoicing_sepa_mandate_heading', $renderedCustomUserInterface);
        self::assertStringContainsString('LANG-MARKER:einvoicing_sepa_mandate_account_holder_label:', $renderedCustomUserInterface);
        self::assertStringContainsString('LANG-MARKER:einvoicing_sepa_mandate_iban_label:', $renderedCustomUserInterface);
        self::assertStringContainsString('LANG-MARKER:einvoicing_sepa_mandate_reference_label:', $renderedCustomUserInterface);
        self::assertStringContainsString('LANG-MARKER:einvoicing_sepa_mandate_change_notice', $renderedCustomUserInterface);
        self::assertStringNotContainsString('LANG-MARKER:einvoicing_sepa_mandate_value_unavailable', $renderedCustomUserInterface);

        self::assertStringContainsString('Lisa Beispiel', $renderedCustomUserInterface);
        self::assertStringContainsString('DE89 **** **** **** **** 3000', $renderedCustomUserInterface);
        self::assertStringNotContainsString('DE89370400440532013000', $renderedCustomUserInterface);
        self::assertStringContainsString('MANDATE-2026-0001', $renderedCustomUserInterface);
    }

    public function testMissingMemberValuesAreRenderedWithUnavailableTextFromLanguageVariable(): void
    {
        $renderedCustomUserInterface = $this->createPaymentModuleWithMemberRecord([
            EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD => 'MANDATE-<2026>',
        ])->getCustomUserInterface();

        self::assertSame(
            2,
            substr_count($renderedCustomUserInterface, 'LANG-MARKER:einvoicing_sepa_mandate_value_unavailable')
        );
        self::assertStringContainsString('MANDATE-&lt;2026&gt;', $renderedCustomUserInterface);
    }

    public function testCustomUserInterfaceWithoutStoredMandateUsesParentPath(): void
    {
        $parsedTemplateNames = $this->recordParsedTemplateNames();

        $paymentModule = $this->createPaymentModuleWithMemberRecord([
            EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD => '',
            EInvoicingSnapshotFields::MEMBER_DEBTOR_IBAN_FIELD => 'DE89370400440532013000',
        ]);

        self::assertSame('', $paymentModule->getCustomUserInterface());
        self::assertSame([], $parsedTemplateNames::$templateNames);
    }

    /**
     * @param array<string, mixed> $memberRecord
     */
    private function createPaymentModuleWithMemberRecord(array $memberRecord): EInvoicingStandardPaymentModule
    {
        $paymentModuleReflection = new \ReflectionClass(EInvoicingStandardPaymentModule::class);

        /** @var EInvoicingStandardPaymentModule $paymentModule */
        $paymentModule = $paymentModuleReflection->newInstanceWithoutConstructor();
        $paymentModule->arrCurrentSettings = [
            EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD => EInvoicingCodeLists::SEPA_DIRECT_DEBIT_CODE,
        ];

        $paymentModuleReflection->getProperty('frontendMemberRecord')->setValue($paymentModule, $memberRecord);

        return $paymentModule;
    }

    private function recordParsedTemplateNames(): object
    {
        // `System::importStatic()` cached Hook-Objekte pro Klassenname; die Namen
        // werden daher statisch gesammelt und pro Test zurückgesetzt.
        $templateNameRecorder = new class() {
            /**
             * @var list<string>
             */
            public static array $templateNames = [];

            public function onParseTemplate(Template $template): void
            {
                self::$templateNames[] = $template->getName();
            }
        };

        $templateNameRecorder::$templateNames = [];
        $GLOBALS['TL_HOOKS']['parseTemplate'] = [[$templateNameRecorder, 'onParseTemplate']];

        return $templateNameRecorder;
    }

    private function bootHostProjectFramework(): void
    {
        $hostProjectPath = dirname(__DIR__, 5);
        $cacheKernel = ContaoKernel::fromRequest($hostProjectPath, Request::create('/'));
        $kernelProperty = new \ReflectionProperty($cacheKernel, 'kernel');
        $kernel = $kernelProperty->getValue($cacheKernel);

        if (!is_object($kernel) || !method_exists($kernel, 'boot') || !method_exists($kernel, 'getContainer')) {
            throw new \RuntimeException('Contao-Kernel der Host-Anwendung konnte nicht initialisiert werden.');
        }

        $kernel->boot();
        $kernel->getContainer()->get('contao.framework')->initialize();
    }
}
