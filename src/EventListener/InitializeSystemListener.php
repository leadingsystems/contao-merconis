<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_cartHelper;

class InitializeSystemListener
{
    public function __invoke(): void
    {
        ls_shop_cartHelper::initializeEmptyCart();
    }
}