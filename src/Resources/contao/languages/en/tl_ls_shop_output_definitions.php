<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['title']												= array('Designation');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductTemplate']								= array('Template for product display');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']							= array('Sorting');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSortingKeyOrAlias']					= array('Alias or key for sorting by attribute or flexContent');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSorting']						= array('Enable user-defined sorting');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSortingFields']				= array('Fields to offer for user-defined sorting');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewPagination']						= array('Products per page', 'Please enter "0" if pagination is not required.');

	/*
	 * References
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductTemplate']['reference']		= array(
		'template_productOverview_useDetailsTemplate' => array('Use product-specific detailed display template', 'This selection enables you to display each product in the overview with the template that has also been selected for the detailed view of the product. Use this setting if you wish to, for example, enable ordering of a product directly from the product overview.')
	);

	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSortingFields']['labels'] = array(
		'field1' => 'Sorting field',
		'field2' => 'Alias or key<br />for sorting by<br />attribute or flexContent',
		'field3' => 'Designation<br />(iflng insert tags possible)'
	);

	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewSorting']['reference']		= array(
		'title_sortDir_ASC' => 'by name in ascending order',
		'title_sortDir_DESC' => 'by name in descending order',
		
		'lsShopProductPrice_sortDir_ASC' => 'by price in ascending order',
		'lsShopProductPrice_sortDir_DESC' => 'by price in descending order',

		'lsShopProductCode_sortDir_ASC' => 'by product code in ascending order',
		'lsShopProductCode_sortDir_DESC' => 'by product code in descending order',
		
		'sorting_sortDir_ASC' => 'by sorting number in ascending order',
		'sorting_sortDir_DESC' => 'by sorting number in descending order',
		
		'lsShopProductProducer_sortDir_ASC' => 'by producer in ascending order',
		'lsShopProductProducer_sortDir_DESC' => 'by producer in descending order',
		
		'lsShopProductWeight_sortDir_ASC' => 'by weight in ascending order',
		'lsShopProductWeight_sortDir_DESC' => 'by weight in descending order',
		
		'priority_sortDir_ASC' => 'priority in ascending order (only available for product search results)',
		'priority_sortDir_DESC' => 'priority in descending order (only available for product search results)',
		
		'flex_contentsLanguageIndependentKEYORALIAS_sortDir_ASC' => 'FlexContent (monolingual) in ascending order',
		'flex_contentsLanguageIndependentKEYORALIAS_sortDir_DESC' => 'FlexContent (monolingual) in descending order',

		'flex_contentsKEYORALIAS_sortDir_ASC' => 'FlexContent (multilingual) in ascending order',
		'flex_contentsKEYORALIAS_sortDir_DESC' => 'FlexContent (multilingual) in descending order',

		'lsShopProductAttributesValuesKEYORALIAS_sortDir_ASC' => 'Attribute in ascending order',
		'lsShopProductAttributesValuesKEYORALIAS_sortDir_DESC' => 'Attribute in descending order'
	);

	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['lsShopProductOverviewUserSorting']['reference']		= array(
		'yes' => array('yes'),
		'no' => array('no')
	);

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['title_legend']   = 'Designation';
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['outputDefinitions_legend']   = 'Display settings for product overview';
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['outputDefinitionsCrossSeller_legend']   = 'Display settings for CrossSeller';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['new']        = array('New data record', 'Produce a new data record');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['edit']        = array('Edit data record', 'Edit data record ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['delete']        = array('Delete data record', 'Delete data record ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['copy']        = array('Copy data record', 'Copy data record ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_output_definitions']['show']        = array('Show details', 'Show details of data record ID %s');
