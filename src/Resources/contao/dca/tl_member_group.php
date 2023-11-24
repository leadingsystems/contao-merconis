<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_member_group']['config']['onsubmit_callback'][] = array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp');
$GLOBALS['TL_DCA']['tl_member_group']['config']['ondelete_callback'][] = array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp');
$GLOBALS['TL_DCA']['tl_member_group']['config']['onrestore_version_callback'][] = array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp');

$GLOBALS['TL_DCA']['tl_member_group']['palettes']['default'] = preg_replace('/,redirect;/siU', ',redirect;{lsShop_legend:hide},lsShopOutputPriceType, lsShopPriceAdjustment, lsShopMinimumOrderValue, lsShopMinimumOrderValueAddCouponToValueOfGoods, lsShopFormCustomerData, lsShopFormConfirmOrder, lsShopEmailText01, lsShopEmailText02, lsShopAttachments, lsShopStandardPaymentMethod, lsShopStandardShippingMethod;', $GLOBALS['TL_DCA']['tl_member_group']['palettes']['default']);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopStandardPaymentMethod'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopStandardPaymentMethod'],
    'inputType' => 'select',
    'foreignKey' => 'tl_ls_shop_payment_methods.title',
    'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopStandardShippingMethod'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopStandardShippingMethod'],
    'inputType' => 'select',
    'foreignKey' => 'tl_ls_shop_shipping_methods.title',
    'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopFormCustomerData'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopFormCustomerData'],
    'inputType' => 'select',
    'foreignKey' => 'tl_form.title',
    'eval'		 =>	array('tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopFormConfirmOrder'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopFormConfirmOrder'],
    'inputType' => 'select',
    'foreignKey' => 'tl_form.title',
    'eval'		 =>	array('tl_class' => 'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopOutputPriceType'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopOutputPriceType'],
    'inputType' => 'select',
    'options' => array('brutto', 'netto'),
    'reference' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopOutputPriceType']['options'],
    'eval' => array('helpwizard' => true, 'tl_class' => 'w50'),
    'sql'                     => "varchar(32) NOT NULL default 'brutto'"
);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopMinimumOrderValue'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopMinimumOrderValue'],
    'inputType' => 'text',
    'eval' => array('rgxp' => 'numberWithDecimals', 'mandatory' => true, 'tl_class' => 'w50'),
    'sql'                     => "decimal(10,2) NOT NULL default '0.00'"
);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopMinimumOrderValueAddCouponToValueOfGoods'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopMinimumOrderValueAddCouponToValueOfGoods'],
    'inputType'               => 'checkbox',
    'eval'					  =>	array('tl_class' => 'w50 m12'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member_group']['fields']['lsShopPriceAdjustment'] = array(
    'exclude' => true,
    'label' => &$GLOBALS['TL_LANG']['tl_member_group']['lsShopPriceAdjustment'],
    'inputType' => 'text',
    'eval' => array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
    'sql'                     => "decimal(10,2) NOT NULL default '0.00'"
);
