<?php

namespace LeadingSystems\MerconisBundle\Services;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;


class Scope {
    private RequestStack $requestStack;
    private ScopeMatcher $scopeMatcher;

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher) {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function isBackend() {
        return $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest());
    }

    public function isFrontend() {
        return $this->scopeMatcher->isFrontendRequest($this->requestStack->getCurrentRequest());
    }

    public function getTLMode()
    {
        if(!$this->requestStack->getCurrentRequest())
        {
            return '';
        }

        if($this->isBackend())
        {
            return 'BE';
        }

        if($this->isFrontend())
        {
            return 'FE';
        }

        return '';
    }
}