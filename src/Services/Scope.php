<?php

namespace LeadingSystems\MerconisBundle\Services;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


class Scope {
    private RequestStack $requestStack;
    private ScopeMatcher $scopeMatcher;

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher) {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function isBackend() {
        /*
         * @toDo check change
         * old: return $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest());
         */
        return $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest() ?? Request::create(''));
    }

    public function isFrontend() {
        /*
         * @toDo check change
         * old: return $this->scopeMatcher->isFrontendRequest($this->requestStack->getCurrentRequest());
         */
        return $this->scopeMatcher->isFrontendRequest($this->requestStack->getCurrentRequest() ?? Request::create(''));
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