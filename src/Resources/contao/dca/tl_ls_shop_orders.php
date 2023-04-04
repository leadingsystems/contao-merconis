<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_orders'] = array(
    'config' => array(
        'dataContainer' => 'Table',
        'closed' => true,
        'onsubmit_callback' => array (
            array('Merconis\Core\ls_shop_orders', 'sendMessagesOnStatusChange')
        ),
        'ondelete_callback' => array(
            array('Merconis\Core\ls_shop_orders', 'deleteOrderMessages')
        ),
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
            'mode' => 2,
            'fields' => array('orderDate'),
            'flag' => 1,
            'disableGrouping' => true,
            'panelLayout' => 'filter;sort,search,limit'
        ),

        'label' => array(
            'fields' => array('orderDate'),
            'format' => '%s',
            'label_callback' => array('Merconis\Core\ls_shop_orders','createLabel')
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
                'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'delete' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
            )

        )
    ),

    'palettes' => array(
        'default' => '
			{top_legend},
			orderNr,
			orderDateUnixTimestamp,
			firstname,
			lastname,
			customerLanguage,
			invoicedAmount,
			VATIDValidationResult;
			
			{orderRepresentation_legend},
			orderRepresentation;
			
			{status_legend},
			status01,
			status02,
			status03,
			status04,
			status05;
			
			{paymentInfo_legend},
			paymentMethod_moduleReturnData;
			
			{shippingTracking_legend},
			shippingTrackingNr,
			shippingTrackingUrl;
			
			{notes_legend},
			notesShort,
			notesLong;
			{misc_legend},
			freetext;
		'
    ),

    'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'orderNr' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['orderNr'],
            'inputType' => 'text',
            'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
            'sorting' => true,
            'flag' => 12,
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'orderDateUnixTimestamp' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['orderDate'],
            'inputType' => 'text',
            'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
            'load_callback' => array(
                array('Merconis\Core\ls_shop_orders', 'getOrderDate')
            ),
            'sorting' => true,
            'flag' => 12,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'firstname' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['firstname'],
            'inputType' => 'text',
            'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
            'sorting' => true,
            'flag' => 11,
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'lastname' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['lastname'],
            'inputType' => 'text',
            'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
            'sorting' => true,
            'flag' => 11,
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'customerLanguage' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['customerLanguage'],
            'inputType' => 'text',
            'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
            'load_callback' => array(
                array('Merconis\Core\ls_shop_orders', 'getLanguageName')
            ),
            'filter' => true,
            'sql'                     => "varchar(2) NOT NULL default ''"
        ),

        'invoicedAmount' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['invoicedAmount'],
            'inputType' => 'text',
            'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'w50'),
            'load_callback' => array(
                array('Merconis\Core\ls_shop_orders', 'getInvoicedAmount')
            ),
            'sorting' => true,
            'flag' => 11,
            'search' => true,
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'VATIDValidationResult' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['VATIDValidationResult'],
            'inputType' => 'text',
            'eval' => array('readonly' => true, 'disabled' => true, 'tl_class' => 'long'),
            'sql'                     => "text NULL"
        ),

        'orderRepresentation' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['orderRepresentation'],
            'eval' => array(
                /*
                 * The template to be used by the widget 'ls_shop_generatedTemplate'
                 */
                // 'ls_shop_generatedTemplate_template' => 'template_beOrderRepresentationDetails_01',

                /*
                 * The field name of the user field that holds the name of the template to use
                 */
                'ls_shop_generatedTemplate_userTemplateField' => 'lsShopBeOrderTemplateDetails',

                /*
                 * The field name of the localconfig field that holds the name of the template to use
                 */
                'ls_shop_generatedTemplate_localconfigTemplateField' => 'ls_shop_beOrderTemplateDetails',
            ),
            'load_callback' => array(
                /*
                 * The callback function that delivers the field's value
                 */
                array('Merconis\Core\ls_shop_orders', 'getOrderRepresentationValue')
            ),
            /*
             * The widget 'ls_shop_generatedTemplate' is used as the inputType for the field "orderRepresentation"
             * that doesn't actually exist in the database. Because there's no corresponding database field, it's
             * important that this field is never actually saved.
             *
             * The widget 'ls_shop_generatedTemplate' parses a template that's given as 'ls_shop_generatedTemplate_template'
             * in this field's eval attribute and passes the field value to the template as $this->value. Because
             * the field 'orderRepresentation' doesn't have a value (because it doesn't have a db field), a load_callback
             * is required which delivers the value to be used for this field.
             */
            'inputType' => 'ls_shop_generatedTemplate'
        ),

        'status01' => array(
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['status01'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'eval'					  => array('tl_class' => 'w50'),
            'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues01AsOptions'),
            'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'status02' => array(
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['status02'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'eval'					  => array('tl_class' => 'w50'),
            'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues02AsOptions'),
            'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'status03' => array(
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['status03'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'eval'					  => array('tl_class' => 'w50'),
            'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues03AsOptions'),
            'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'status04' => array(
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['status04'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'eval'					  => array('tl_class' => 'w50'),
            'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues04AsOptions'),
            'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'status05' => array(
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['status05'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'eval'					  => array('tl_class' => 'w50'),
            'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getStatusValues05AsOptions'),
            'reference'               => &$GLOBALS['TL_LANG']['MSC']['ls_shop']['statusValues'],
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paymentMethod_moduleReturnData' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['paymentMethod_moduleReturnData'],
            'inputType' => 'simpleOutput',
            'load_callback' => array(
                array('Merconis\Core\ls_shop_orders', 'get_paymentMethod_moduleReturnData')
            ),
            'eval' => array('tl_class' => 'paymentMethod_moduleReturnData'),
            'sql'                     => "blob NULL"
        ),

        'shippingTrackingNr' => array(
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['shippingTrackingNr'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'					  => array('tl_class' => 'w50', 'maxlength'=>255),
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'shippingTrackingUrl' => array(
            'label' =>  &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['shippingTrackingUrl'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'					  => array('tl_class' => 'w50', 'maxlength'=>255),
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'notesShort' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['notesShort'],
            'inputType' => 'text',
            'eval'					  => array('maxlength'=>32),
            'sorting' => true,
            'flag' => 11,
            'filter' => true,
            'search' => true,
            'sql'                     => "varchar(32) NOT NULL default ''"
        ),

        'notesLong' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['notesLong'],
            'inputType' => 'textarea',
            'eval' => array('rte'=>'tinyMCE'),
            'sql'                     => "text NULL"
        ),

        'freetext' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['freetext'],
            'inputType' => 'textarea',
            'sql'                     => "text NULL"
        ),

        'payPalPlus_saleId' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['payPalPlus_saleId'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalPlus_currentStatus' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['payPalPlus_currentStatus'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalCheckout_orderId' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['payPalCheckout_orderId'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payPalCheckout_currentStatus' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['payPalCheckout_currentStatus'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'payone_currentStatus' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['payone_currentStatus'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'saferpay_currentStatus' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['saferpay_currentStatus'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'vrpay_currentStatus' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['vrpay_currentStatus'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'sofortbanking_currentStatus' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_orders']['sofortbanking_currentStatus'],
            'inputType' => 'text',
            'eval' => array('maxlength'=>255),
            'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'orderIdentificationHash' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'orderDate' => array (
            'sql'                     => "varchar(32) NOT NULL default ''"
        ),

        'customerNr' => array (
            'sql'                     => "varchar(32) NOT NULL default ''"
        ),

        'personalDataReview' => array (
            'sql'                     => "blob NULL"
        ),

        'personalDataReview_customerLanguage' => array (
            'sql'                     => "blob NULL"
        ),

        'paymentDataReview' => array (
            'sql'                     => "blob NULL"
        ),

        'paymentDataReview_customerLanguage' => array (
            'sql'                     => "blob NULL"
        ),

        'shippingDataReview' => array (
            'sql'                     => "blob NULL"
        ),


        'shippingDataReview_customerLanguage' =>array (
            'sql'                     => "blob NULL"
        ),

        'memberGroupInfo_id' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'memberGroupInfo_name' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'currency' => array (
            'sql'                     => "varchar(3) NOT NULL default ''"
        ),

        'weightUnit' => array (
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),

        'userOutputPriceType' => array (
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),

        'inputPriceType' => array (
            'sql'                     => "varchar(10) NOT NULL default ''"
        ),

        'numDecimalsPrice' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'numDecimalsWeight' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'decimalsSeparator' => array (
            'sql'                     => "varchar(1) NOT NULL default ''"
        ),

        'thousandsSeparator' => array (
            'sql'                     => "varchar(1) NOT NULL default ''"
        ),

        'totalValueOfGoods' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'totalValueOfGoodsTaxedWith' => array (
            'sql'                     => "blob NULL"
        ),

        'noVATBecauseOfEnteredIDs' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'totalWeightOfGoods' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'couponsUsed' => array (
            'sql'                     => "blob NULL"
        ),

        'couponsTotalValue' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'paymentMethod_title' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paymentMethod_title_customerLanguage' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paymentMethod_infoAfterCheckout' => array (
            'sql'                     => "text NULL"
        ),

        'paymentMethod_infoAfterCheckout_customerLanguage' => array (
            'sql'                     => "text NULL"
        ),

        'paymentMethod_additionalInfo' => array (
            'sql'                     => "text NULL"
        ),

        'paymentMethod_additionalInfo_customerLanguage' => array (
            'sql'                     => "text NULL"
        ),

        'paymentMethod_id' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'paymentMethod_alias' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'paymentMethod_feeInfo_customerLanguage' => array (
            'sql'                     => "blob NULL"
        ),

        'paymentMethod_amount' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'paymentMethod_amountTaxedWith' => array (
            'sql'                     => "blob NULL"
        ),

        'shippingMethod_title' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'shippingMethod_title_customerLanguage' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'shippingMethod_infoAfterCheckout' => array (
            'sql'                     => "text NULL"
        ),

        'shippingMethod_infoAfterCheckout_customerLanguage' => array (
            'sql'                     => "text NULL"
        ),

        'shippingMethod_additionalInfo' => array (
            'sql'                     => "text NULL"
        ),

        'shippingMethod_additionalInfo_customerLanguage' => array (
            'sql'                     => "text NULL"
        ),

        'shippingMethod_id' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'shippingMethod_alias' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'shippingMethod_feeInfo_customerLanguage' => array (
            'sql'                     => "blob NULL"
        ),

        'shippingMethod_moduleReturnData' => array (
            'sql'                     => "blob NULL"
        ),

        'shippingMethod_amount' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'shippingMethod_amountTaxedWith' => array (
            'sql'                     => "blob NULL"
        ),

        'total' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'totalTaxedWith' => array (
            'sql'                     => "blob NULL"
        ),

        'taxTotal' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'tax' => array (
            'sql'                     => "blob NULL"
        ),

        'taxInclusive' => array (
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'invoicedAmountNet' => array (
            'sql'                     => "decimal(12,4) NOT NULL default '0.0000'"
        ),

        'miscData' => array (
            'sql'                     => "blob NULL"
        ),

        'status' => array (
            'sql'                     => "varchar(255) NOT NULL default ''"
        )
    )
);



class ls_shop_orders extends \Backend {
    public function __construct() {
        $this->import('BackendUser', 'User');
        parent::__construct();
    }

    public function createLabel($row, $label) {
        if (\Input::get('orderID') && \Input::get('messageTypeID')) {
            $objOrderMessages = new ls_shop_orderMessages(\Input::get('orderID'), \Input::get('messageTypeID'), 'id');
            $objOrderMessages->sendMessages();
            $this->redirect($this->getReferer());
        }

        $objTemplate = new \BackendTemplate($this->User->lsShopBeOrderTemplateOverview ? $this->User->lsShopBeOrderTemplateOverview : $GLOBALS['TL_CONFIG']['ls_shop_beOrderTemplateOverview']);
        $arrOrder = ls_shop_generalHelper::getOrder($row['id']);
        $objTemplate->arrOrder = $arrOrder;
        $objTemplate->arrMessageTypes = ls_shop_generalHelper::getMessageTypesForOrderOverview($arrOrder);

        // ### paymentMethod callback ########################
        $obj_paymentModule = new ls_shop_paymentModule();
        $obj_paymentModule->specializeManuallyWithPaymentID($arrOrder['paymentMethod_id']);
        $paymentModuleOutput = $obj_paymentModule->showPaymentStatusInOverview($arrOrder, $arrOrder['paymentMethod_moduleReturnData']);
        $objTemplate->paymentModuleOutput = !is_null($paymentModuleOutput) ? $paymentModuleOutput : '';
        // ###################################################

        $label = $objTemplate->parse();
        return $label;
    }

    /*
     * This function returns the output for the paymentMethod_moduleReturnData. A payment module callback is called
     * and if the payment module returns something other than null, it's return value will be displayed. If the payment
     * module doesn't care about the output it returns null and this function will return a standard output.
     */
    public function get_paymentMethod_moduleReturnData($varValue, \DataContainer $dc) {
        $arrOrder = ls_shop_generalHelper::getOrder($dc->id);

        // ### paymentMethod callback ########################
        try {
            $obj_paymentModule = new ls_shop_paymentModule();
            $obj_paymentModule->specializeManuallyWithPaymentID($arrOrder['paymentMethod_id']);
            $paymentModuleOutput = $obj_paymentModule->showPaymentDetailsInBackendOrderDetailView($arrOrder, $varValue);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        // ###################################################

        $outputValue = '';

        if (!is_null($paymentModuleOutput)) {
            $outputValue = $paymentModuleOutput;
        } else {
            $varValue = deserialize($varValue);
            ob_start();
            echo '<pre>';
            if (is_array($varValue)) {
                print_r($varValue);
            } else {
                echo $varValue;
            }
            echo '</pre>';
            $outputValue = ob_get_clean();
        }

        return $outputValue;
    }

    public function getOrderRepresentationValue($varValue, \DataContainer $dc) {
        return ls_shop_generalHelper::getOrder($dc->id);
    }

    public function getOrderDate($varValue, \DataContainer $dc) {
        return \Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $varValue);
    }

    public function getLanguageName($varValue, \DataContainer $dc) {
        $this->loadLanguageFile('languages');
        return $GLOBALS['TL_LANG']['LNG'][$varValue];
    }

    public function getInvoicedAmount($varValue, \DataContainer $dc) {
        return ls_shop_generalHelper::outputPrice($varValue);
    }

    public function sendMessagesOnStatusChange($dc) {
        $objOrderMessages = new ls_shop_orderMessages($dc->activeRecord->id, 'onStatusChangeImmediately', 'sendWhen', null, true);
        $objOrderMessages->sendMessages();
    }

    public function deleteOrderMessages($dc) {
        \Database::getInstance()->prepare("
			DELETE FROM	`tl_ls_shop_messages_sent`
			WHERE		`orderID` = ?
		")
            ->execute($dc->activeRecord->id);
    }
}
