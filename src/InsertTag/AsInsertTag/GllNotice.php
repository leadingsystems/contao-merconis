<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;

#[AsInsertTag('gll_notice')]
final class GllNotice extends InsertTag
{
    public function __construct(
        private readonly LegalGuaranteeMarkupProvider $markupProvider,
    ) {
    }

    public function customInserttags($strTag, $params)
    {
        return $this->markupProvider->renderCurrentLanguageGllNotice();
    }
}
