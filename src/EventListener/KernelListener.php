<?php


namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_cartHelper;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;


class KernelListener
{
    public function onKernelControllerArguments(ControllerArgumentsEvent $event)
    {
        ls_shop_cartHelper::initializeEmptyCart();


    }
}