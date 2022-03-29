<?php

namespace Merconis\Core;

/*
 * Just a dummy DCA for the dummy table tl_ls_shop_import which is only necessary because
 * we obviously need a DC for backend AJAX action even we have a custom BE module with it's own
 * module callback!
 */

$GLOBALS['TL_DCA']['tl_ls_shop_import'] = array(
	'config' => array(
		'dataContainer' => 'Table',
		'sql' => array
		(
			'engine' => 'MyISAM',
			'charset' => 'utf8 COLLATE utf8_general_ci',
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),
	
	'list' => array(
		'sorting' => array(
			'mode' => 1,
			'fields' => array('importInfo'),
			'flag' => 1,
			'panelLayout' => 'filter;sort,search,limit'
		),
		
		'label' => array(
			'fields' => array('importInfo'),
			'format' => '%s'
		),
		
		'global_operations' => array(),
		
		'operations' => array()
	),
	
	'palettes' => array(
		'default' => 'importInfo'
	),

	'fields' => array(
		'id' => array (
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array (
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array (
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'importInfo' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_import']['importInfo'],
			'exclude' => true,
			'inputType' => 'text',
            'sql'                     => "varchar(255) NOT NULL default ''"
		)
	)
);


