<?php


namespace LeadingSystems\MerconisBundle\EventListener;

use Merconis\Core\ls_shop_checkoutData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;


class LoginListener implements AuthenticationSuccessHandlerInterface
{

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $request = $event->getRequest();
        $this->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $user = $token->getUser();

        if ($user instanceof \FrontendUser) {
            ls_shop_checkoutData::getInstance()->ls_shop_postLogin($user);
        }

    }
}