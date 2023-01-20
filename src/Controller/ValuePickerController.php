<?php

namespace LeadingSystems\MerconisBundle\Controller;
use Contao\Backend;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Environment;
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

		/** @var Adapter|Environment $environment */
		$template = new \BackendTemplate('be_valuePicker');

		$template->theme = Backend::getTheme();
		$template->base = \Environment::get('base');
		$template->language = $GLOBALS['TL_LANGUAGE'];
		$template->title = $GLOBALS['TL_CONFIG']['websiteTitle'] ?? '';
		$template->headline = \Input::get('pickerHeadline');
		$template->charset = $GLOBALS['TL_CONFIG']['characterSet'];
		$template->options = ls_shop_generalHelper::createValueList(\Input::get('requestedTable'),\Input::get('requestedValue'),\Input::get('requestedLanguage'));

		return $template->getResponse();
	}
}
