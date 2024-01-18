<?php
/*
 * @toDo Fix: Contao\CoreBundle\Framework\ContaoFrameworkInterface deprecated since Contao 4.7, to be removed in Contao 5.0; use the Contao\CoreBundle\Framework\ContaoFramework class instead
 */

namespace LeadingSystems\MerconisBundle\Controller;

use Contao\Ajax;
use Contao\Backend;
use Contao\BackendTemplate;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * Configures the bundle.
 *
 * @author Leading Systems GmbH
 */
class ProductSearchController extends Backend
{
	/**
	 * Contao framework.
	 *
	 * @var ContaoFrameworkInterface
	 */
	private $framework;

	/**
	 * ProductSearchController constructor.
	 *
	 * @param ContaoFrameworkInterface $framework Contao framework.
	 */
	public function __construct(ContaoFrameworkInterface $framework)
	{
		$this->framework = $framework;
		$this->framework->initialize();

		parent::__construct();

		$this->loadLanguageFile('default');
		$this->loadLanguageFile('modules');
	}

	/**
	 * Handle the search action.
	 *
	 * @return Response
	 */
	public function searchAction()
	{

		$this->Template = new BackendTemplate('be_productSearch');
		$this->Template->main = '';

		// Ajax request
		if ($_POST && Environment::get('isAjaxRequest'))
		{
			$ajax = new Ajax(Input::post('action'));
			$ajax->executePreActions();
		}

		$this->Template->main .= $this->getBackendModule('ls_shop_productSearch');

		// Default headline
		if ($this->Template->headline == '')
		{
			$this->Template->headline = $GLOBALS['TL_CONFIG']['websiteTitle'];
		}

		$this->Template->theme = Backend::getTheme();
		$this->Template->base = Environment::get('base');
		$this->Template->language = $GLOBALS['TL_LANGUAGE'] ?? '';
		$this->Template->title = $GLOBALS['TL_CONFIG']['websiteTitle'] ?? '';
		$this->Template->charset = $GLOBALS['TL_CONFIG']['characterSet'] ?? '';
		$this->Template->pageOffset = Input::cookie('BE_PAGE_OFFSET');
		$this->Template->error = (Input::get('act') == 'error') ? $GLOBALS['TL_LANG']['ERR']['general'] : '';
		$this->Template->skipNavigation = $GLOBALS['TL_LANG']['MSC']['skipNavigation'];
		$this->Template->request = StringUtil::ampersand(Environment::get('request'));
		$this->Template->top = $GLOBALS['TL_LANG']['MSC']['backToTop'];

		return $this->Template->getResponse();
	}
}
