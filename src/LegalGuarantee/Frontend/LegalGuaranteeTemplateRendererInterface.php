<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

interface LegalGuaranteeTemplateRendererInterface
{
    /**
     * Rendert einen Baustein der Label-Ausgabe über die Template-Auflösung von Contao. Ein
     * gleichnamiges Template im Theme oder in `templates/` ersetzt den Baustein.
     *
     * @param array<string, mixed> $templateData
     */
    public function render(string $templateName, array $templateData): string;
}
