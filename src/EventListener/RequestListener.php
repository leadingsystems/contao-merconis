<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use LeadingSystems\MerconisBundle\Helpers\GeneralHelper;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListener
{
    private ContaoFramework $framework;
    private ScopeMatcher $scopeMatcher;
    private GeneralHelper $generalHelper;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher, GeneralHelper $generalHelper)
    {

        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->generalHelper = $generalHelper;
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->scopeMatcher->isContaoRequest($request)) {
            $this->framework->initialize();

            if ($this->generalHelper->check_refererCheckCanBeBypassed()) {
                $request->attributes->set('_token_check', false);
            }
        }
    }

}