<?php
/*
 * @toDo Fix: Contao\CoreBundle\Framework\ContaoFrameworkInterface deprecated since Contao 4.7, to be removed in Contao 5.0; use the Contao\CoreBundle\Framework\ContaoFramework class instead
 */

namespace LeadingSystems\MerconisBundle\Controller;
use Contao\Backend;
use Contao\BackendTemplate;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

/**
 * Controller for the value picker wizard.
 *
 * @author Leading Systems GmbH
 */
class ValuePickerController
{
	/**
	 * Contao framework.
	 *
	 * @var ContaoFrameworkInterface
	 */
	private $framework;

	/**
	 * ValuePickerController constructor.
	 *
	 * @param ContaoFrameworkInterface $framework Contao framework.
	 *
	 */
	public function __construct(ContaoFrameworkInterface $framework)
	{
		$this->framework = $framework;
	}

	/**
	 * Pick a value.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function pickAction()
	{
		$this->framework->initialize();

		Backend::setStaticUrls();

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
