<?php


namespace LeadingSystems\MerconisBundle\EventSubscriber;


use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

class LsjsInsertionSubscriber implements EventSubscriberInterface
{
    private ScopeMatcher $scopeMatcher;
    private ContaoFramework $framework;
    private string $webDir;
    private string $projectDir;
    private TokenChecker $tokenChecker;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher, TokenChecker $tokenChecker, string $webDir, string $projectDir)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
        $this->webDir = $webDir;
        $this->projectDir = $projectDir;
        $this->tokenChecker = $tokenChecker;
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event)
    {
        if ($this->scopeMatcher->isBackendMainRequest($event) && $this->tokenChecker->hasBackendUser()) {
            require_once($this->projectDir . '/assets/lsjs/core/appBinder/binderController.php');

            $arr_config = [
                'pathForRenderedFiles' => $this->projectDir . '/assets/js',
                "pathToApp" => $this->webDir . '/bundles/leadingsystemsmerconis/js/lsjs/backend/app',
                "includeCore" => 'no',
                "includeCoreModules" => 'no',
                "debug" => (Config::get('ls_shop_lsjsDebugMode') ? '1' : ''),
                "no-minifier" => (Config::get('ls_shop_lsjsNoMinifierMode') ? '1' : ''),
            ];

            $binderController = new \lsjs_binderController($arr_config);

            $GLOBALS['TL_JAVASCRIPT'][] = str_replace($this->projectDir, '', $binderController->getPathToRenderedFile());


            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_shop_BE.js';
            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_x_controller.js';
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            ControllerArgumentsEvent::class => 'onKernelControllerArguments',
        ];
    }
}