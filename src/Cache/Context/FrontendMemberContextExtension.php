<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Context;

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

/**
 * Context extension that exposes the current frontend member id as a dimension
 * via getter "getMemberId" (dimension name: "memberId").
 */
final class FrontendMemberContextExtension
{
    public function getMemberId(): ?int
    {
        try {
            $container = System::getContainer();

            // Ensure this is a frontend scope request if possible
            $scope = $container->get('contao.routing.scope_matcher');
            $request = $container->get('request_stack')->getCurrentRequest() ?? Request::create('');
            if (method_exists($scope, 'isBackendRequest') && $scope->isBackendRequest($request)) {
                return null;
            }

            // Try Symfony Security helper
            if ($container->has('security.helper')) {
                $security = $container->get('security.helper');
                if (method_exists($security, 'getUser')) {
                    $user = $security->getUser();
                    if (is_object($user)) {
                        if (method_exists($user, 'getId')) {
                            $id = (int) $user->getId();
                            return $id > 0 ? $id : null;
                        }
                        if (property_exists($user, 'id')) {
                            $id = (int) $user->id;
                            return $id > 0 ? $id : null;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore and fall through to null
        }
        return null;
    }
}


