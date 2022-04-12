<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['title']	= array('Designation');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['member_group']	= array('Member group');

    $GLOBALS['TL_LANG']['tl_ls_shop_message_model']['flex_parameters']								= array('Flexible Content', 'You can add as many parameters as you like and they can be used in templates by referencing them with their keyword.');
    $GLOBALS['TL_LANG']['tl_ls_shop_message_model']['flex_parameters_label01']						= 'Parameter key';
    $GLOBALS['TL_LANG']['tl_ls_shop_message_model']['flex_parameters_label02']						= 'Parameter value';
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['useHTML']	= array('Use HTML content');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['template_html'] = array('Template for HTML mails');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_html']	= array('Content for HTML mails','Special MERCONIS wildcards can be used to insert information from the order into the text. A list of all available wildcards can be found in the MERCONIS manual.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['useRawtext']	= array('Use "Text only" content');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['template_rawtext'] = array('Template for text-only mails');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_rawtext']	= array('Content for text-only mails','Special MERCONIS wildcards can be used to insert information from the order into the text. A list of all available wildcards can be found in the MERCONIS manual.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['subject'] = array('Subject');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['senderName'] = array('Sender name');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['senderAddress'] = array('Sender address');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToCustomerAddress1'] = array('Customer address', 'Please activate this checkbox if this message shall be sent to an e-mail address of the customer that was stored with the order. Ideally, the input field for this should be a mandatory field because, otherwise, the message cannot be sent.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToCustomerAddress2'] = array('Alternative customer address', 'Please activate this checkbox if this message shall be sent to a different e-mail address of the customer that was stored with the order. If the customer has not entered an address in the respective input field, the message will be sent to the address that was defined in the field above by default.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType'] = array('Type of customer data input field', 'Select the form to which this input field belongs.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataField'] = array('Name of customer data input field', 'Enter the name of the input field that contains the e-mail address here.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToSpecificAddress'] = array('Special address', 'Please activate this checkbox if this message shall be sent to a special address that has been permanently stored here. If it is, for example, an order notification, it makes sense to indicate your own e-mail address. Should you also have activated the option to send a message to a customer address, then the address indicated here will be used as BCC.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['specificAddress'] = array('Special receiving address');
    $GLOBALS['TL_LANG']['tl_ls_shop_message_model']['sendToMemberAddress'] = array('Member address', 'Please activate this checkbox if the message should be sent to the member\'s e-mail address. This option can only be used in connection with notifications about renewed availibility.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['attachments'] = array('File attachments');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['dynamicAttachments'] = array('Dynamically generated PDF attachment', 'Select a PHP file which is used for the creation of a dynamically generated PDF attachment here. If you do not select a file, then no dynamically generated PDF will be attached.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['published']	= array('Active');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['externalImages'] = array('External images', 'Do not embed images in HTML newsletters.');
	
	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['title_legend']   = 'Designation';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['content_legend']   = 'Content';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['group_legend']   = 'Member group allocation';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['subject_legend']   = 'Sender and subject';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['receiver_legend']   = 'Recipient';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['attachments_legend']   = 'Attachments';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['published_legend']   = 'Activation';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['expert_legend']     = 'Expert settings';
	
	/*
	 * Reference
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['customerDataType']['options'] = array(
		'personalData' => 'Personal data',
		'paymentData' => 'Customer data concerning payment',
		'shippingData' => 'Customer data concerning dispatch'
	);

	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['new']        = array('New message model', 'Define a new message model');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['edit']        = array('Edit message model', 'Edit message model ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['delete']        = array('Delete message model', 'Delete message model ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['copy']        = array('Copy message model', 'Copy message model ID %s');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['show']        = array('Show details', 'Show details of message model ID %s');
	
	/*
	 * MISC
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_model']['childRecordListText'] = '<p>For group<strong>%s</strong></p><p>Subject: "%s"</p>';
