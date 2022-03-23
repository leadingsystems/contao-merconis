<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_message_type'] = array(
	'config' => array(
		'dataContainer' => 'Table',
		'ctable' => array('tl_ls_shop_message_model'),
		'onsubmit_callback' => array(
			array('Merconis\Core\tl_ls_shop_message_type_controller', 'restartCounter')
		),
        'sql' => array
        (
            'engine' => 'MyISAM',
            'charset' => 'COLLATE utf8_general_ci',
            'keys' => array
            (
                'id' => 'primary'
            )
        )
	),
	
	'list' => array(
		'sorting' => array(
			'mode' => 1,
			'fields' => array('title'),
			'flag' => 1,
			'disableGrouping' => true,
			'panelLayout' => 'filter;sort,search,limit'
		),
		
		'label' => array(
			'fields' => array('title'),
			'format' => '%s'
		),
		
		'global_operations' => array(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		
		'operations' => array(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['edit'],
				'href'                => 'table=tl_ls_shop_message_model',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)
	),
	
	'palettes' => array(
		'__selector__'                => array('sendWhen','useCounter','useStatusCorrelation01','useStatusCorrelation02','useStatusCorrelation03','useStatusCorrelation04','useStatusCorrelation05','usePaymentStatusCorrelation'),
		'default' => '
			{title_legend},
			title,
			alias;
			
			{counter_legend},
			useCounter;
			
			{sendingOptions_legend},
			sendWhen;
		',
		'onStatusChangeImmediately' => '
			{title_legend},
			title,
			alias;
			
			{counter_legend},
			useCounter;
			
			{sendingOptions_legend},
			sendWhen,
			useStatusCorrelation01,
			useStatusCorrelation02,
			useStatusCorrelation03,
			useStatusCorrelation04,
			useStatusCorrelation05,
			usePaymentStatusCorrelation;
		',
		'onStatusChangeCronDaily' => '
			{title_legend},
			title,
			alias;
			
			{counter_legend},
			useCounter;
			
			{sendingOptions_legend},
			sendWhen,
			useStatusCorrelation01,
			useStatusCorrelation02,
			useStatusCorrelation03,
			useStatusCorrelation04,
			useStatusCorrelation05,
			usePaymentStatusCorrelation;
		',
		'onStatusChangeCronHourly' => '
			{title_legend},
			title,
			alias;
			
			{counter_legend},
			useCounter;
			
			{sendingOptions_legend},
			sendWhen,
			useStatusCorrelation01,
			useStatusCorrelation02,
			useStatusCorrelation03,
			useStatusCorrelation04,
			useStatusCorrelation05,
			usePaymentStatusCorrelation;
		'
	),

	'subpalettes' => array
	(
		'useCounter'                    => 'counter,counterString,counterStart,counterRestartCycle,counterRestartNow,lastDispatchDateUnixTimestamp',
		'useStatusCorrelation01'		=> 'statusCorrelation01',
		'useStatusCorrelation02'		=> 'statusCorrelation02',
		'useStatusCorrelation03'		=> 'statusCorrelation03',
		'useStatusCorrelation04'		=> 'statusCorrelation04',
		'useStatusCorrelation05'		=> 'statusCorrelation05',
		'usePaymentStatusCorrelation'	=> 'paymentStatusCorrelation_paymentProvider,paymentStatusCorrelation_statusValue'
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
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'maxlength'=>255),
			'flag' => 11,
			'search'		=> true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'alias' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['alias'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp'=>'alnum', 'doNotCopy'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'clr topLinedGroup'),
			'save_callback' => array (
				array('Merconis\Core\tl_ls_shop_message_type_controller', 'generateAlias')
			),
			'sorting' => true,
			'flag' => 11,
			'search' => true,
            'sql'                     => "varchar(128) COLLATE utf8_bin NOT NULL default ''"
		),
		
		'useCounter' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useCounter'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
			'filter'				  => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'counter' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counter'],
			'inputType' => 'simpleOutput',
			'eval' => array('tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'counterString' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterString'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'maxlength'=>255),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'counterStart' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterStart'],
			'inputType' => 'text',
			'eval' => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50', 'mandatory' => true),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'counterRestartCycle' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterRestartCycle'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('never', 'year', 'month', 'week', 'day'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterRestartCycle']['options'],
			'eval'                    => array('tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'counterRestartNow' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterRestartNow'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'lastDispatchDateUnixTimestamp' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['lastDispatchDateUnixTimestamp'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'load_callback' => array(
				array('Merconis\Core\tl_ls_shop_message_type_controller', 'getLastDispatchDateUnixTimestamp')
			),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'sendWhen' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['sendWhen'],
			'exclude'                 => true,
			'inputType'				  => 'select',
			'options'				  => array('manual','onStatusChangeImmediately','onStatusChangeCronDaily','onStatusChangeCronHourly','asOrderConfirmation','asOrderNotice','onRestock'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['sendWhen']['options'],
			'eval'					  => array('tl_class' => 'clr', 'submitOnChange' => true),
			'filter'				  => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'useStatusCorrelation01' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation01'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'useStatusCorrelation02' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation02'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'useStatusCorrelation03' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation03'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'useStatusCorrelation04' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation04'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'useStatusCorrelation05' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation05'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'usePaymentStatusCorrelation' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['usePaymentStatusCorrelation'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'statusCorrelation01' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation01'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'w50'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues01AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'statusCorrelation02' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation02'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'w50'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues02AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'statusCorrelation03' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation03'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'w50'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues03AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'statusCorrelation04' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation04'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'w50'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues04AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'statusCorrelation05' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation05'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'w50'),
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues05AsOptions'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'paymentStatusCorrelation_paymentProvider' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['paymentStatusCorrelation_paymentProvider'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'				  => array('payPalPlus', 'payone', 'saferpay', 'vrpay', 'sofortbanking'),
			'eval'					  => array('tl_class' => 'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['paymentStatusCorrelation_paymentProvider']['options'],
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

		'paymentStatusCorrelation_statusValue' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['paymentStatusCorrelation_statusValue'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		)
	)
);





class tl_ls_shop_message_type_controller extends \Backend {

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function generateAlias($varValue, \DataContainer $dc) {
		$autoAlias = false;

		$currentTitle = isset($dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()}) && $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} ? $dc->activeRecord->{'title_'.ls_shop_languageHelper::getFallbackLanguage()} : $dc->activeRecord->title;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = \StringUtil::generateAlias($currentTitle);
		}
		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_message_type WHERE id=? OR alias=?")
								   ->execute($dc->id, $varValue);

		// Check whether the alias exists
		if ($objAlias->numRows > 1) {
			if (!$autoAlias) {
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
			}
			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}
	
	public function restartCounter($dc) {
		if ($dc->activeRecord->counterRestartNow) {
			/*
			 * Beim Zurücksetzen des Zählers wird dieser auf 0 gesetzt und nicht direkt auf den Startwert,
			 * da beim Abschluss einer Bestellung automatisch der Startwert verwendet wird, sofern der Zähler
			 * auf 0 steht.
			 */
			$objUpdate = \Database::getInstance()->prepare("
				UPDATE		`tl_ls_shop_message_type`
				SET			`counter` = ?,
							`counterRestartNow` = ?
				WHERE		`id` = ?
			")
			->limit(1)
			->execute(0, '', $dc->activeRecord->id);
		}
	}
	
	public function getLastDispatchDateUnixTimestamp($varValue, \DataContainer $dc) {
		return \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $varValue);
	}
}
