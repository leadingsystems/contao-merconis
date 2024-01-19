<?php

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\System;

class ModuleFilterForm extends Module
{
	public function generate()
	{
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS filter form ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}

	/**
	 * The frontend module "ModuleFilterForm" should generate and display the filter form.
	 * The problem is that this module is reliably rendered after the product list that provides the
	 * information about available attributes and values. If this module is inserted in the layouts
	 * left section and the product list (for example in the product overview or the search results
	 * crossSeller) is inserted in the main section, than the filter form module is rendered first.
	 * This means that filter form module that relies on the attribute value information can not access
	 * this information because the product list hasn't even been parsed yet.
	 *
	 * We use a workaround that makes sure that the filter form is always parsed after the corresponding
	 * product list. The actual filter form frontend module (this frontend module here) only writes a
	 * placeholder to the page output. This placeholder also contains the id of the fronted module so
	 * that the function that actually generates the filter form and is executed as a outputFrontendTemplate
	 * hook can get the settings of the frontend module record.
	 *
	 * To give a résumé, the approach described above provides a function that is definitely executed after
	 * the product lists and that has all the information that the filter form frontend module record has
	 * and - that's the important part - all the information about available attributes and values of the
	 * product list. And by simply placing a placeholder with the actual filter form fronted module (this frontend module here)
	 * the function executed by the hook can display it's generated form right where the actual fronted
	 * module would have displayed it.
	 */
	public function compile()
	{
		if (!isset($GLOBALS['merconis_globals']['ls_shop_activateFilter']) || !$GLOBALS['merconis_globals']['ls_shop_activateFilter']) {
			return;
		}

		$this->Template = new FrontendTemplate('filterFormPlaceholder');
		$this->Template->filterFormFrontendModuleID = $this->id;
	}
}