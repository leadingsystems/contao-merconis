<?php

namespace Merconis\Core;

/*
 * This widget generates an output for a field value using a template given as
 * $this->arrConfiguration['ls_shop_generatedTemplate_template'] or as the value of
 * a user field referenced by $this->arrConfiguration['ls_shop_generatedTemplate_userTemplateField'].
 * 
 * The field value of this widget field is passed through to the template as $this->value.
 * 
 * There's nothing more to do for the widget. If the field value needs to be manipulated
 * in any way, it's the job of the field's load_callback to do so and it's the job
 * of the given template to create the specific output.
 */

use Contao\BackendTemplate;
use Contao\Widget;

class ls_shop_generatedTemplate extends Widget
{

	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = false;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';


	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate() {
		if (!$this->varValue) {
			return '';
		}
		
		$useLanguage = false;
		if (isset($this->arrConfiguration['ls_shop_useLanguage'])) {
			$useLanguage = $this->arrConfiguration['ls_shop_useLanguage'];
		}
		
		if ($useLanguage) {
			$this->loadLanguageFile('default', $useLanguage, true);
		}
		
		$strTemplateToUse = '';
		if (isset($this->arrConfiguration['ls_shop_generatedTemplate_userTemplateField']) && $this->arrConfiguration['ls_shop_generatedTemplate_userTemplateField']) {
			$this->import('BackendUser', 'User');
			$fieldName = $this->arrConfiguration['ls_shop_generatedTemplate_userTemplateField'];
			$strTemplateToUse = $this->User->{$fieldName};
		}
		
		if (!$strTemplateToUse && isset($this->arrConfiguration['ls_shop_generatedTemplate_localconfigTemplateField']) && $this->arrConfiguration['ls_shop_generatedTemplate_localconfigTemplateField']) {
			$strTemplateToUse = isset($GLOBALS['TL_CONFIG'][$this->arrConfiguration['ls_shop_generatedTemplate_localconfigTemplateField']]) ? $GLOBALS['TL_CONFIG'][$this->arrConfiguration['ls_shop_generatedTemplate_localconfigTemplateField']] : '';
		}
		
		if (!$strTemplateToUse && isset($this->arrConfiguration['ls_shop_generatedTemplate_template']) && $this->arrConfiguration['ls_shop_generatedTemplate_template']) {
			$strTemplateToUse = $this->arrConfiguration['ls_shop_generatedTemplate_template'];
		}
		
		$objTemplate = new BackendTemplate($strTemplateToUse);
		$objTemplate->value = $this->varValue;
		$this->varValue = $objTemplate->parse();
		
		if ($useLanguage) {
			$this->loadLanguageFile('default', $GLOBALS['TL_LANGUAGE'], true);
		}
		
		return sprintf('<div class="ls_shop_generatedTemplate">%s</div>%s',
						$this->varValue,
						$this->wizard);
	}
}

?>