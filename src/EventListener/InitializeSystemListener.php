<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_cartHelper;

class InitializeSystemListener
{
    public function execute(): void
    {
        ls_shop_cartHelper::initializeEmptyCart();
    }
}