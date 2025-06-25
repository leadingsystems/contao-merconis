<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['title']										= array('Designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['alias']										= array('Alias', 'Unique designation for referencing. This field can be left blank, the matching value will then be filled in automatically.');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerProzentPeriod1']						= array('Tax rate/tax zone (left side: tax rate in percent, right side: countries of the tax zone)', 'Please enter the countries of the tax zone as a list of two-digit country codes (ISO-3166-1 coding list) and separate the country codes with commas. If you leave the field on the right side blank (countries of the tax zone), then the defined percentage value will be applied for all customers of which the country does not explicitely match a country that has been allocated a tax zone.');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['startPeriod1']								= array('Valid from', 'Enter the date here from which the percentage value for this tax rate will be valid (from 0 o\'clock of the respective day).');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['stopPeriod1']								= array('Valid until', 'Enter the date here until which the percentage value for this tax rate will be valid (until 23:59 o\'clock of the respective day).');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerProzentPeriod2']						= array('Tax rate/tax zone (left side: tax rate in percent, right side: countries of the tax zone)', 'Please enter the countries of the tax zone as a list of two-digit country codes (ISO-3166-1 coding list) and separate the country codes with commas.');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['startPeriod2']								= array('Valid from', 'Enter the date here from which the percentage value for this tax rate will be valid (from 0 o\'clock of the respective day).');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['stopPeriod2']								= array('Valid until', 'Enter the date here until which the percentage value for this tax rate will be valid (until 23:59 o\'clock of the respective day).');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['title_legend']   = 'Designation';
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerPeriod1_legend']   = 'Period of validity 1';
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerPeriod2_legend']   = 'Period of validity 2';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['new']        = array('New tax rate', 'Define a new tax rate');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['edit']        = array('Edit tax rate', 'Edit tax rate ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['delete']        = array('Delete tax rate', 'Delete tax rate ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['copy']        = array('Copy tax rate', 'Copy tax rate ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['show']        = array('Show details', 'Show details of tax rate ID %s');
	
	/*
	 * Misc
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['wildcardNotAllowed'] = 'You can not use dynamic values because you already assigned this tax rate to at least one product.';


    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStopDatePeriod1'] = 'For period 1, a start date was entered, but no end date.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStartDatePeriod1'] = 'For period 1, a end date was entered, but no start date.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_stopDateBeforeStartDatePeriod1'] = 'The end date for period 1 must not be before the start date.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStopDatePeriod2'] = 'For period 2, a start date was entered, but no end date.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStartDatePeriod2'] = 'For period 2, a end date was entered, but no start date.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_stopDateBeforeStartDatePeriod2'] = 'The end date for period 2 must not be before the start date.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_periodsOverlap'] = 'The date ranges for period 1 and period 2 must not overlap.';