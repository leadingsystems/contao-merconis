<?php

namespace LeadingSystems\MerconisBundle\EventListener;
use Contao\DataContainer;

class SelectButtonsRemoveListener
{
    public function __invoke(array $buttons, DataContainer $dc): array
    {
        unset($buttons['delete']);
        return $buttons;
    }
}