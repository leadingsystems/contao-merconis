<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'ls_shop_productOverviewShowProductsFromSubordinatePages';

$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_productOverview'] = '{title_legend},name,type;{lsShopProductOverview_legend},ls_shop_productOverviewShowProductsFromSubordinatePages,';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_cart'] = '{title_legend},name,headline,type;{lsShopCart_legend},ls_shop_cart_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_orderReview'] = '{title_legend},name,headline,type;{lsShopOrderReview_legend},ls_shop_orderReview_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_checkoutFinish'] = '{title_legend},name,headline,type;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_afterCheckout'] = '{title_legend},name,headline,type;{lsShopAfterCheckout_legend},ls_shop_afterCheckout_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_paymentAfterCheckout'] = '{title_legend},name,headline,type;{lsShopPaymentAfterCheckout_legend},ls_shop_paymentAfterCheckout_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_cross_seller'] = '{title_legend},name,headline,type;{lsShopCrossSeller_legend},ls_shop_cross_seller;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_myOrders'] = '{title_legend},name,headline,type;{lsShopMyOrders_legend},ls_shop_myOrders_sortingOptions,ls_shop_myOrders_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_myOrderDetails'] = '{title_legend},name,headline,type;{lsShopMyOrderDetails_legend},ls_shop_myOrderDetails_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_filterForm'] = '{title_legend},name,type;{lsShopFilterForm_legend},ls_shop_filterForm_template,ls_shop_filterSummary_template;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_productSearch'] = '{title_legend},name,headline,type;{lsShopProductSearch_legend},ls_shop_productSearch_template,ls_shop_productSearch_minlengthInput;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['ls_shop_productManagementApiInspector'] = '{title_legend},name,headline,type;{ls_shop_productManagementApiInspector},ls_shop_productManagementApiInspector_apiPage;';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['ls_shop_productOverviewShowProductsFromSubordinatePages'] = 'ls_shop_productOverviewConsiderUnpublishedPages,ls_shop_productOverviewConsiderHiddenPages,ls_shop_productOverviewStartLevel,ls_shop_productOverviewStopLevel';



$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productOverviewShowProductsFromSubordinatePages'] = array (
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewShowProductsFromSubordinatePages'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productOverviewConsiderUnpublishedPages'] = array (
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewConsiderUnpublishedPages'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productOverviewConsiderHiddenPages'] = array (
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewConsiderHiddenPages'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productOverviewStartLevel'] = array (
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewStartLevel'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('maxlength'=>5, 'rgxp'=>'natural', 'tl_class'=>'w50'),
    'sql'                     => "smallint(5) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productOverviewStopLevel'] = array (
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productOverviewStopLevel'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('maxlength'=>5, 'rgxp'=>'natural', 'tl_class'=>'w50'),
    'sql'                     => "smallint(5) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productManagementApiInspector_apiPage'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productManagementApiInspector_apiPage'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'foreignKey'              => 'tl_page.title',
	'eval'                    => array('mandatory' => true, 'fieldType' => 'radio'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_cart_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_cart_template'],
	'default'                 => 'template_cart_big',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_cart_'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_orderReview_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_orderReview_template'],
	'default'                 => 'template_orderReview_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_orderReview_'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_afterCheckout_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_afterCheckout_template'],
	'default'                 => 'template_afterCheckout_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_afterCheckout_'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);
	
$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_paymentAfterCheckout_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_paymentAfterCheckout_template'],
	'default'                 => 'template_paymentAfterCheckout_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_paymentAfterCheckout_'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_myOrders_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_myOrders_template'],
	'default'                 => 'template_myOrders_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_myOrders_'),
	'eval'					  => array('tl_class' => 'clr'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);
	
$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_myOrders_sortingOptions'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_myOrders_sortingOptions'],
	'default'                 => '',
	'exclude'                 => true,
	'inputType'               => 'checkboxWizard',
	'options'                 => array('orderDateUnixTimestamp', 'orderNr', 'invoicedAmount', 'status01', 'status02', 'status03', 'status04', 'status05'),
	'reference'				  => $GLOBALS['TL_LANG']['MSC']['ls_shop']['orderSortingOptions'],
	'eval'					  => array('multiple' => true, 'tl_class' => 'clr'),
    'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_myOrderDetails_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_myOrderDetails_template'],
	'default'                 => 'template_myOrders_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_myOrderDetails_'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);
	
$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_filterForm_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_filterForm_template'],
	'default'                 => 'template_filterForm_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_filterForm_'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);
	
$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_filterSummary_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_filterSummary_template'],
	'default'                 => 'template_filterSummary_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_filterSummary_'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_cross_seller'] = array (
	'exclude'		=> true,
	'label'			=> &$GLOBALS['TL_LANG']['tl_module']['ls_shop_cross_seller'],
	'inputType'		=> 'select',
	'foreignKey'	=> 'tl_ls_shop_cross_seller.title',
	'eval'			=> array('tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productSearch_template'] = array (
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productSearch_template'],
	'default'                 => 'template_productSearch_default',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => $this->getTemplateGroup('template_productSearch_'),
	'eval'			=> array('tl_class' => 'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['ls_shop_productSearch_minlengthInput'] = array (
	'label' => &$GLOBALS['TL_LANG']['tl_module']['ls_shop_productSearch_minlengthInput'],
	'exclude' => true,
	'inputType' => 'text',
	'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50', 'mandatory' => true),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

