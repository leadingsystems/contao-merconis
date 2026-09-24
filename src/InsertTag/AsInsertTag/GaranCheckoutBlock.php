<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupProvider;

#[AsInsertTag('garan_checkout_block')]
final class GaranCheckoutBlock extends InsertTag
{
    public function __construct(
        private readonly LegalGuaranteeMarkupProvider $markupProvider,
    ) {
    }

    public function customInserttags($strTag, $params)
    {
        return $this->markupProvider->renderCheckoutGaranBlock();
    }
}
