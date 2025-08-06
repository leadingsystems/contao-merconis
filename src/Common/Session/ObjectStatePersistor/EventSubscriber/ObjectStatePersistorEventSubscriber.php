<?php

namespace LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor\EventSubscriber;

use LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor\ObjectStatePersistorController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ObjectStatePersistorEventSubscriber implements EventSubscriberInterface
{
    private ObjectStatePersistorController $controller;

    public function __construct(ObjectStatePersistorController $controller)
    {
        $this->controller = $controller;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->controller->persistAllStates();
        $this->controller->clear();
    }
}