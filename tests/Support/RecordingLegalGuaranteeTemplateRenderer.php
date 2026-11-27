<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Support;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeTemplateRendererInterface;
use RuntimeException;

/**
 * Testdouble ohne Contao-Laufzeit: hält jeden Aufruf mit seinen Daten fest und liefert eine
 * Marke `[<Template>#<laufende Nummer>]`, an der Tests die Verschachtelung erkennen. Mit
 * `$innerRenderer` gibt er den Aufruf zusätzlich weiter und liefert dessen Ergebnis.
 */
final class RecordingLegalGuaranteeTemplateRenderer implements LegalGuaranteeTemplateRendererInterface
{
    /**
     * @var list<array{templateName: string, templateData: array<string, mixed>, output: string}>
     */
    public array $renderedTemplates = [];

    public function __construct(
        private readonly ?LegalGuaranteeTemplateRendererInterface $innerRenderer = null,
    ) {
    }

    public function render(string $templateName, array $templateData): string
    {
        $output = null !== $this->innerRenderer
            ? $this->innerRenderer->render($templateName, $templateData)
            : sprintf('[%s#%d]', $templateName, count($this->renderedTemplates) + 1);

        $this->renderedTemplates[] = [
            'templateName' => $templateName,
            'templateData' => $templateData,
            'output' => $output,
        ];

        return $output;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTemplateDataFor(string $templateName): array
    {
        return array_values(array_map(
            static fn (array $renderedTemplate): array => $renderedTemplate['templateData'],
            array_filter(
                $this->renderedTemplates,
                static fn (array $renderedTemplate): bool => $renderedTemplate['templateName'] === $templateName
            )
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateDataForOutput(string $output): array
    {
        foreach ($this->renderedTemplates as $renderedTemplate) {
            if ($renderedTemplate['output'] === $output) {
                return $renderedTemplate['templateData'];
            }
        }

        throw new RuntimeException(sprintf('No template rendering produced output "%s".', $output));
    }
}
