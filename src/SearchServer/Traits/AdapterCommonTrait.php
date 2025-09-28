<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Traits;

trait AdapterCommonTrait
{
    public function getAdapterName(): string
    {
        /*
         * The adapter name is the part of the FQCN that comes immediately after 'Adapters'
         */
        $fqcn = get_class($this);
        $parts = explode('\\', $fqcn);
        $adaptersIndex = array_search('Adapters', $parts);

        if ($adaptersIndex !== false && isset($parts[$adaptersIndex + 1])) {
            return $parts[$adaptersIndex + 1];
        }

        throw new \Exception('Adapter name can not be determined from the FQCN, probably because of an unexpected/wrong namesapce hierarchy');
    }

    public function initialize(): void {}
}