<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_message_model'] = array(
	'config' => array(
		'dataContainer' => 'Table',
		'ptable' => 'tl_ls_shop_message_type'
	),
	
	'list' => array(
	
		'sorting' => array(
			'mode' => 4,
			'fields' => array('subject'),
			'panelLayout' => 'filter;sort,search,limit',
			'headerFields'            => array('title'),
			'disableGrouping'   => true,
			'child_record_callback'   => array('Merconis\Core\tl_ls_shop_message_model_controller', 'listChildRecords')
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
			'edit' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)
	),
	
	'palettes' => array(
		'__selector__' => array('sendToCustomerAddress1', 'sendToCustomerAddress2', 'sendToSpecificAddress', 'useHTML', 'useRawtext'),
		'default' => '
			{group_legend},
			member_group;
			{subject_legend},
			senderAddress,
			senderName,
			subject;
			{receiver_legend},
			sendToCustomerAddress1,
			sendToSpecificAddress,
			sendToMemberAddress;
			{content_legend},
			useHTML,
			useRawtext;
			{attachments_legend},
			attachments,
			dynamicAttachments;
			{expert_legend:hide},
			externalImages;
			{published_legend},
			published;
		'
	),
	
	'subpalettes' => array(
		'sendToCustomerAddress1' => 'customerDataType1,customerDataField1,sendToCustomerAddress2',
		'sendToCustomerAddress2' => 'customerDataType2,customerDataField2',
		'sendToSpecificAddress' => 'specificAddress',
		'useHTML' => 'template_html,content_html',
		'useRawtext' => 'template_rawtext,content_rawtext'
	),
	
	'fields' => array(
		'member_group' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['member_group'],
			'exclude' => true,
			'inputType' => 'checkboxWizard',
			'foreignKey' => 'tl_member_group.name',
			'eval' => array('tl_class'=>'clr','multiple'=>true)
		),
		
		'senderAddress' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['senderAddress'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'email', 'maxlength'=>255)
		),
		
		'senderName' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['senderName'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'maxlength'=>255),
			'search'		=> true
		),
		
		'subject' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['subject'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class'=>'w50', 'merconis_multilanguage' => true, 'maxlength'=>255),
			'search'		=> true
		),
		
		'sendToCustomerAddress1' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToCustomerAddress1'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'default'				  => '1',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
			'filter'				  => true
		),
		
		'customerDataType1' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'default'				  => 'personalData',
			'eval'					  => array('tl_class' => 'w50'),
			'options'				  => array('personalData', 'paymentData', 'shippingData'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType']['options'],
			'filter' => true
		),
		
		'customerDataField1' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataField'],
			'exclude' => true,
			'inputType' => 'text',
			'default' => 'email',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
			'search' => true
		),
		
		'sendToCustomerAddress2' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToCustomerAddress2'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
			'filter'				  => true
		),
		
		'customerDataType2' => array(
			'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'eval'					  => array('tl_class' => 'w50'),
			'options'				  => array('personalData', 'paymentData', 'shippingData'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType']['options'],
			'filter' => true
		),
		
		'customerDataField2' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataField'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
			'search' => true
		),
		
		'sendToSpecificAddress' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToSpecificAddress'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
			'filter'				  => true
		),
		
		'specificAddress' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['specificAddress'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'tl_class' => 'w50', 'rgxp' => 'email', 'maxlength'=>255),
			'search' => true
		),

		'sendToMemberAddress' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToMemberAddress'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr'),
			'filter'				  => true
		),

		'useHTML' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['useHTML'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'default'				  => '1',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true),
			'filter'				  => true
		),
		
		'template_html' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['template_html'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_merconisMessageHTML_'),
			'eval'					  => array('tl_class' => 'w50'),
			'filter' => true
		),
		
		'content_html' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_html'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true)
		),
		
		'useRawtext' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['useRawtext'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr topLinedGroup', 'submitOnChange' => true),
			'filter'				  => true
		),
		
		'template_rawtext' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['template_rawtext'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_merconisMessageRawtext_'),
			'eval'					  => array('tl_class' => 'w50'),
			'filter' => true
		),
		
		'content_rawtext' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_rawtext'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('tl_class'=>'clr', 'merconis_multilanguage' => true)
		),
		
		'attachments' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['attachments'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array('multiple' => true, 'tl_class'=>'clr', 'files' => true, 'filesOnly' => true, 'fieldType' => 'checkbox', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true)
		),
		
		'dynamicAttachments' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['dynamicAttachments'],
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => array('multiple' => true, 'tl_class'=>'clr', 'files' => true, 'filesOnly' => true, 'extensions'=>'php', 'fieldType' => 'checkbox', 'merconis_multilanguage' => true)
		),
		'externalImages' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['externalImages'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),
		
		'published' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['published'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'filter'				  => true
		)
	)
);

class tl_ls_shop_message_model_controller extends \Backend {

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}
	
	public function listChildRecords($arrRow) {
		if (!is_array($arrRow['member_group'])) {
			$arrRow['member_group'] = deserialize($arrRow['member_group'], true);
		}
		
		$memberGroupName = '';
		foreach ($arrRow['member_group'] as $memberGroup) {
			$objMemberGroup = \Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_member_group`
				WHERE		`id` = ?
			")
			->execute($memberGroup);
			if ($objMemberGroup->numRows) {
				$objMemberGroup->first();
				$memberGroupName .= ($memberGroupName ? ', ' : '').'&quot;'.$objMemberGroup->name.'&quot;';
			}
		}

		return sprintf($GLOBALS['TL_LANG']['tl_ls_shop_message_model']['childRecordListText'], $memberGroupName, substr($arrRow['subject'], 0, 550));
	}
}
