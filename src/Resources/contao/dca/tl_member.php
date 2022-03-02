<?php

namespace Merconis\Core;

	$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] = preg_replace('/(\{groups_legend\})/siU', '{lsShop_legend:hide},VATID,firstname_alternative,lastname_alternative,company_alternative,street_alternative,postal_alternative,city_alternative,country_alternative,phone_alternative,mobile_alternative,fax_alternative,email_alternative;$1', $GLOBALS['TL_DCA']['tl_member']['palettes']['default']);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['VATID'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['VATID'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>255, 'rgxp'=>'merconisCheckVATID', 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) NOT NULL default ''"
	);

	$GLOBALS['TL_DCA']['tl_member']['fields']['firstname_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['firstname_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'sorting'                 => true,
		'flag'                    => 1,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['lastname_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['lastname_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'sorting'                 => true,
		'flag'                    => 1,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['company_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['company_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'sorting'                 => true,
		'flag'                    => 1,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['street_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['street_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['postal_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['postal_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>32, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
        'sql'                     => "varchar(32) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['city_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['city_alternative'],
		'exclude'                 => true,
		'filter'                  => true,
		'search'                  => true,
		'sorting'                 => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['country_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['country_alternative'],
		'exclude'                 => true,
		'filter'                  => true,
		'sorting'                 => true,
		'inputType'               => 'select',
		'options'                 => $this->getCountries(),
		'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
        'sql'                     => "varchar(2) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['phone_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['phone_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>64, 'rgxp'=>'phone', 'decodeEntities'=>true, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
        'sql'                     => "varchar(64) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['mobile_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['mobile_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>64, 'rgxp'=>'phone', 'decodeEntities'=>true, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
        'sql'                     => "varchar(64) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['fax_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['fax_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>64, 'rgxp'=>'phone', 'decodeEntities'=>true, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
        'sql'                     => "varchar(64) NOT NULL default ''"
	);
	
	$GLOBALS['TL_DCA']['tl_member']['fields']['email_alternative'] = array(
		'label'                   => &$GLOBALS['TL_LANG']['tl_member']['email_alternative'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('maxlength'=>255, 'rgxp'=>'email', 'unique'=>true, 'decodeEntities'=>true, 'feEditable'=>true, 'feViewable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
        'sql'                     => "varchar(255) NOT NULL default ''"
	);

	$GLOBALS['TL_DCA']['tl_member']['fields']['state_alternative'] = array (
        'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['state_alternative'],
        'exclude'                 => true,
        'sql'                     => "varchar(64) NOT NULL default ''"
    );

	$GLOBALS['TL_DCA']['tl_member']['fields']['merconis_favoriteProducts'] = array (
        'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['merconis_favoriteProducts'],
        'exclude'                 => true,
        'sql'                     => "blob NULL"
    );
?>

