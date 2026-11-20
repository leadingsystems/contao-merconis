<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class EInvoicingStoredSepaMandateTemplateTest extends TestCase
{
    public function testTemplateRendersLabelsValuesAndCssClasses(): void
    {
        $renderedTemplate = $this->renderTemplate([
            'accountHolder' => 'Lisa Beispiel',
            'maskedIban' => 'DE89 **** **** **** **** 3000',
            'mandateReference' => 'MANDATE-2026-0001',
        ]);

        self::assertStringContainsString('<p class="lsShopStoredSepaMandate__headline">Heading-Label</p>', $renderedTemplate);
        self::assertStringContainsString(
            '<span class="lsShopStoredSepaMandate__label">Account-Holder-Label:</span> <span class="lsShopStoredSepaMandate__value">Lisa Beispiel</span>',
            $renderedTemplate
        );
        self::assertStringContainsString(
            '<span class="lsShopStoredSepaMandate__label">Iban-Label:</span> <span class="lsShopStoredSepaMandate__value">DE89 **** **** **** **** 3000</span>',
            $renderedTemplate
        );
        self::assertStringContainsString(
            '<span class="lsShopStoredSepaMandate__label">Mandate-Reference-Label:</span> <span class="lsShopStoredSepaMandate__value">MANDATE-2026-0001</span>',
            $renderedTemplate
        );
        self::assertStringContainsString('<p class="lsShopStoredSepaMandate__hint">Change-Notice</p>', $renderedTemplate);
        self::assertStringContainsString('<div class="lsShopStoredSepaMandate">', $renderedTemplate);
        self::assertSame(3, substr_count($renderedTemplate, 'class="lsShopStoredSepaMandate__row"'));
        self::assertStringNotContainsString('Value-Unavailable', $renderedTemplate);
    }

    public function testTemplateRendersUnavailableTextForMissingValues(): void
    {
        $renderedTemplate = $this->renderTemplate([
            'accountHolder' => null,
            'maskedIban' => null,
            'mandateReference' => null,
        ]);

        self::assertSame(3, substr_count($renderedTemplate, '<span class="lsShopStoredSepaMandate__value">Value-Unavailable</span>'));
    }

    public function testTemplateEscapesValuesAndLabels(): void
    {
        $renderedTemplate = $this->renderTemplate(
            [
                'accountHolder' => 'Müller & <Söhne>',
                'maskedIban' => null,
                'mandateReference' => 'REF-"1"',
            ],
            [
                'headline' => '<script>alert(1)</script>',
            ]
        );

        self::assertStringContainsString('Müller &amp; &lt;Söhne&gt;', $renderedTemplate);
        self::assertStringContainsString('REF-&quot;1&quot;', $renderedTemplate);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $renderedTemplate);
        self::assertStringNotContainsString('<script>', $renderedTemplate);
    }

    /**
     * @param array<string, string|null> $memberValues
     * @param array<string, string>      $labelOverrides
     */
    private function renderTemplate(array $memberValues, array $labelOverrides = []): string
    {
        $templatePath = dirname(__DIR__, 2) . '/src/Resources/contao/templates/template_einvoicingStoredSepaMandate_default.html5';

        $templateData = array_merge(
            [
                'headline' => 'Heading-Label',
                'accountHolderLabel' => 'Account-Holder-Label',
                'ibanLabel' => 'Iban-Label',
                'mandateReferenceLabel' => 'Mandate-Reference-Label',
                'changeNotice' => 'Change-Notice',
                'valueUnavailableText' => 'Value-Unavailable',
            ],
            $labelOverrides,
            $memberValues
        );

        $templateRenderer = new class($templateData, $templatePath) {
            /**
             * @param array<string, string|null> $templateData
             */
            public function __construct(
                private readonly array $templateData,
                private readonly string $templatePath
            ) {
            }

            public function __get(string $templateVariableName): ?string
            {
                return $this->templateData[$templateVariableName];
            }

            public function __isset(string $templateVariableName): bool
            {
                return isset($this->templateData[$templateVariableName]);
            }

            public function render(): string
            {
                ob_start();
                include $this->templatePath;

                return (string) ob_get_clean();
            }
        };

        return $templateRenderer->render();
    }
}
