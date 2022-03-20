<?php

	/*
	 * Fields
	 */

	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['alias']										= array('Alias', 'Unique designation to be used as a reference.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['title']										= array('Designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form']										= array('Form');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['template']									= array('Template');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['startWithDataEntryMode']					= array('Start with data entry mode', 'This setting defines whether the configurator displays the form at first when a product is called for the very first time, even if the product in question can also be ordered without prior data entry. If the configurator form has got mandatory fields, it will be displayed inevitably as long as data have not been entered yet.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['stayInDataEntryMode']						= array('Stay in data entry mode', 'With this option checked, the configurator stays in the data entry mode even after the form has been submitted.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['skipStandardFormValidation']				= array('No standard form validation', 'With this option checked the standard form validation will not be performed. Validation of the data gathered in the configurator will then only be validated by a "customValidator" function programmed in the file containing the personal processing logic. If no such function is defined, the configurator data is always considered to be valid. This setting makes sense, for example, if you manipulate the configurator form using the form hooks available in the personal processing logic file in a way that makes it impossible for the standard form validation to validate correctly.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['customLogicFile']							= array('File with personal processing logic', 'If required, please indicate the file containing the application with your personal processing logic here.');

    /*
     * Misc
     */
    $GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form_blankOptionLabel']                     = 'Use configurator without Contao form (advanced)';

    /*
     * Legends
     */
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['title_legend']   = 'Designation and alias';
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form_legend']   = 'Form';
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['template_legend']   = 'Template';
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['customLogic_legend'] = 'Personal processing logic';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['new']        = array('New configurator', 'Add a new product configurator');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['edit']        = array('Edit configurator', 'Edit product configurator ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['delete']        = array('Delete configurator', 'Delete product configurator ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['copy']        = array('Copy configurator', 'Copy product configurator ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['show']        = array('Show details', 'Show details of product configurator ID %s');
