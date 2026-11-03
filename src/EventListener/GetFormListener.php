<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\FormModel;
use Contao\System;

class GetFormListener
{
    public function __invoke(FormModel $objRow, string $strBuffer, $objElement): string
    {
        if (!System::getContainer()->get('merconis.routing.scope')->isFrontend()) {
            return $strBuffer;
        }
        $strBuffer = html_entity_decode($strBuffer, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $strBuffer;
    }
}
