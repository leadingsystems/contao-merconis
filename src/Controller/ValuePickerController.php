<?php

namespace LeadingSystems\MerconisBundle\Controller;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Merconis\Core\ls_shop_generalHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the value picker wizard.
 *
 * @author Leading Systems GmbH
 */
class ValuePickerController extends AbstractBackendController
{
	/**
	 * Contao framework.
	 *
	 * @var ContaoFramework
	 */
	private $framework;

	/**
	 * ValuePickerController constructor.
	 *
	 * @param ContaoFramework $framework Contao framework.
	 *
	 */
	public function __construct(ContaoFramework $framework)
	{
		$this->framework = $framework;
	}

    /**
     * Pick a value.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(): Response
    {
        $this->framework->initialize();

        \define("VERSION", (method_exists(ContaoCoreBundle::class, 'getVersion') ? ContaoCoreBundle::getVersion() : VERSION));
        \define('TL_ASSETS_URL', System::getContainer()->get('contao.assets.assets_context')->getStaticUrl());

        System::loadLanguageFile('default');

        /** @var Adapter|Environment $environment */
        $template = new BackendTemplate('be_valuePicker');

        $template->theme = Backend::getTheme();
        $template->base = Environment::get('base');
        $template->language = $GLOBALS['TL_LANGUAGE'];
        $template->title = $GLOBALS['TL_CONFIG']['websiteTitle'];
        $template->headline = Input::get('pickerHeadline');
        $template->charset = $GLOBALS['TL_CONFIG']['characterSet'];
        $template->options = ls_shop_generalHelper::createValueList(Input::get('requestedTable'),Input::get('requestedValue'),Input::get('requestedLanguage'));

        return $template->getResponse();
    }
}
