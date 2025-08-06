<?php

namespace LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor;

use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;

trait ObjectStatePersistorTrait
{
    /**
     * @var string[] Names of properties to persist.
     */
    private array $persistedProperties = [];

    /**
     * @var string|null The unique key for the instance
     */
    private ?string $persistorInstanceKey = null;

    /**
     * The fully computed session key.
     */
    private string $persistorSessionKey;

    /**
     * @var string Session base namespace
     */
    private string $persistorSessionNamespace = 'object_state_persistor';

    /**
     * The class using the trait, has to call this method in the constructor or some init method.
     * Pass a list of property names to persist, and optionally an instance key.
     */
    protected function initializePersistor(array $propertiesToPersist, ?string $instanceKey = null): void
    {
        $controller = System::getContainer()->get('LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor\ObjectStatePersistorController');
        $controller->registerInstance($this);

        $this->persistedProperties = $propertiesToPersist;
        $this->persistorInstanceKey = $instanceKey;
        $classPart = static::class; // Use late static binding

        $sessionKey = $classPart;
        if ($this->persistorInstanceKey) {
            $sessionKey .= '::' . $this->persistorInstanceKey;
        }
        $this->persistorSessionKey = $sessionKey;

        // On initialize, restore state for this instance
        $this->restoreStateFromSession();
    }

    protected function unregisterPersistor(): void
    {
        $controller = System::getContainer()->get('LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor\ObjectStatePersistorController');
        $controller->unregisterInstance($this);
    }

    /**
     * Restore persisted properties from the session (using $this->requestStack for session access).
     */
    private function restoreStateFromSession(): void
    {
        /** @var  $requestStack RequestStack */
        $requestStack = System::getContainer()->get('request_stack');
        $session = $requestStack->getSession();
        $allStates = $session->get($this->persistorSessionNamespace, []);
        $state = $allStates[$this->persistorSessionKey] ?? null;
        if (!is_array($state)) {
            return;
        }
        foreach ($this->persistedProperties as $property) {
            if (array_key_exists($property, $state)) {
                $this->$property = $state[$property];
            }
        }
    }

    /**
     * Store persisted properties to the session (using $this->requestStack for session access).
     */
    public function storeStateToSession(): void
    {
        /** @var  $requestStack RequestStack */
        $requestStack = System::getContainer()->get('request_stack');
        $session = $requestStack->getSession();
        $allStates = $session->get($this->persistorSessionNamespace, []);
        $state = [];
        foreach ($this->persistedProperties as $property) {
            $state[$property] = $this->$property;
        }
        $allStates[$this->persistorSessionKey] = $state;
        $session->set($this->persistorSessionNamespace, $allStates);
    }
}