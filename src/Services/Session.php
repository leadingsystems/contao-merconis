<?php

namespace LeadingSystems\MerconisBundle\Services;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;


class Session {
    private $requestStack;
    private $scopeMatcher;

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher) {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;

        $this->initializeEmptyCart();
    }

    public function getSession() {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        return $session;
    }


    private function initializeEmptyCart(){


        $session = $this->getSession();
        $session_lsShopCart =  $session->get('lsShopCart');

        if (!is_array($session_lsShopCart['items'] ?? null)) {
            $session_lsShopCart['items'] = array();
            $session->set('lsShopCart', $session_lsShopCart);
        }
    }

}