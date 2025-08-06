<?php

namespace LeadingSystems\MerconisBundle\Common\Session\ObjectStatePersistor;

class ObjectStatePersistorController
{
    private array $instances = [];

    public function registerInstance($instance): void
    {
        $this->instances[spl_object_hash($instance)] = $instance;
    }

    public function unregisterInstance($instance): void
    {
        unset($this->instances[spl_object_hash($instance)]);
    }

    public function persistAllStates(): void
    {
        foreach ($this->instances as $instance) {
            /** @var $instance ObjectStatePersistorTrait */
            if (method_exists($instance, 'storeStateToSession')) {
                $instance->storeStateToSession();
            }
        }
    }

    public function clear(): void
    {
        $this->instances = [];
    }
}