<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Frontend;

use Contao\FrontendTemplate;

final class LegalGuaranteeTemplateRenderer implements LegalGuaranteeTemplateRendererInterface
{
    public function render(string $templateName, array $templateData): string
    {
        $template = new FrontendTemplate($templateName);
        $template->setData($templateData);

        return $template->parse();
    }
}
