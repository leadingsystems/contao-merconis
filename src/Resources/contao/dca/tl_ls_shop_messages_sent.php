<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_ls_shop_messages_sent'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'closed' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary'
            )
        )
	),
	
	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_SORTABLE,
			'fields' => array('tstamp'),
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'disableGrouping' => true,
			'panelLayout' => 'filter;sort,search,limit'
		),
		
		'label' => array(
			'fields' => array('tstamp'),
			'format' => '%s',
			'label_callback' => array('Merconis\Core\ls_shop_messages_sent','createLabel')
		),
		
		'global_operations' => array(
		),
		
		'operations' => array(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			)
		
		)	
	),
	
	'palettes' => array(
		'default' => '
			{top_legend},
			tstamp,
			messageTypeAlias,
			orderNr,
			counterNr;
			{sender_legend},
			senderName,
			senderAddress;
			{sender_legend},
			receiverMainAddress,
			receiverBccAddress;
			{messageRepresentation_legend},
			subject,
			messageRepresentation;
			{attachments_legend},
			dynamicPdfAttachmentPaths;
			attachmentPaths
		'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'orderID' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'messageTypeID' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'messageModelID' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'bodyHTML' => array (
            'sql'                     => "text NULL"
        ),
        'bodyRawtext' => array (
            'sql'                     => "text NULL"
        ),
		'tstamp' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['tstamp'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'load_callback' => array(
				array('Merconis\Core\ls_shop_messages_sent', 'getDate')
			),
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'messageTypeAlias' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['messageTypeAlias'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'filter' => true,
            'sql'                     => "varbinary(128) NOT NULL default ''"
		),
		
		'orderNr' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['orderNr'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'search' => true,
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "varchar(255) NULL"
		),
		
		'counterNr' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['counterNr'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'search' => true,
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "varchar(255) NULL"
		),
		
		'senderName' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['senderName'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'search' => true,
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'senderAddress' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['senderAddress'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'search' => true,
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'receiverMainAddress' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['receiverMainAddress'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'search' => true,
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'receiverBccAddress' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['receiverBccAddress'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'search' => true,
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "varchar(255) NULL"
		),
		
		'subject' => array(
			'exclude' => true,
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['subject'],
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
			'search' => true,
			'sorting' => true,
			'flag' => 12,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'messageRepresentation' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['messageRepresentation'],
			'eval' => array(
				'tl_class' => 'clr',
				'ls_shop_generatedTemplate_template' => 'template_beMessageRepresentationDetails_default',
			),
			'load_callback' => array(
				array('Merconis\Core\ls_shop_messages_sent', 'getMessageRepresentationValue')
			),
			'inputType' => 'ls_shop_generatedTemplate'
		),
		
		'dynamicPdfAttachmentPaths' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['dynamicAttachmentPaths'],
			'eval' => array(
				'tl_class' => 'clr',
				'ls_shop_generatedTemplate_template' => 'template_beMessageAttachmentRepresentation_default',
			),
			'inputType' => 'ls_shop_generatedTemplate',
            'sql'                     => "blob NULL"
		),
		
		'attachmentPaths' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_messages_sent']['attachmentPaths'],
			'eval' => array(
				'tl_class' => 'clr',
				'ls_shop_generatedTemplate_template' => 'template_beMessageAttachmentRepresentation_default',
			),
			'inputType' => 'ls_shop_generatedTemplate',
            'sql'                     => "blob NULL"
		)
	)
);




class ls_shop_messages_sent extends \Backend {
	public function __construct() {
		parent::__construct();
	}

	public function createLabel($row, $label) {
		$objTemplate = new \BackendTemplate('template_beMessageRepresentationOverview_default');
		$objTemplate->value = ls_shop_generalHelper::getMessageSent($row['id']);
		$label = $objTemplate->parse();
		return $label;
	}

	public function getMessageRepresentationValue($varValue, \DataContainer $dc) {
		return ls_shop_generalHelper::getMessageSent($dc->id);
	}
	
	public function getDate($varValue, \DataContainer $dc) {
		return \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $varValue);
	}
}
