<?php

namespace Merconis\Core;

define('TL_MERCONIS_INSTALLER', 'MERCONIS INSTALLER');
define('TL_MERCONIS_IMPORTER', 'MERCONIS IMPORTER');
define('TL_MERCONIS_GENERAL', 'MERCONIS GENERAL');
define('TL_MERCONIS_ERROR', 'MERCONIS ERROR');
define('TL_MERCONIS_MESSAGES', 'MERCONIS MESSAGES');
define('TL_MERCONIS_STOCK_MANAGEMENT', 'MERCONIS STOCK MANAGEMENT');

$GLOBALS['TL_HOOKS']['merconisCustomTaxRateCalculation'][] = array('Merconis\Core\ls_shop_generalHelper', 'merconisCustomTaxRateCalculation');

$GLOBALS['TL_HOOKS']['addCustomRegexp'][] = array('Merconis\Core\ls_shop_custom_regexp', 'customRegexp');
$GLOBALS['TL_HOOKS']['addCustomRegexp'][] = array('Merconis\Core\ls_shop_custom_regexp_fe', 'customRegexp');

/*
 * Include the lsjs app for the merconis backend
 */
if (TL_MODE === 'BE') {
    $GLOBALS['TL_HOOKS']['outputBackendTemplate'][] = array('Merconis\Core\ls_shop_generalHelper', 'merconis_getBackendLsjs');
}

/*
 * Hook for loading the themes' language files
 */
if (TL_MODE == 'FE') {
	$GLOBALS['TL_HOOKS']['loadLanguageFile'][] = array('Merconis\Core\ls_shop_generalHelper', 'ls_shop_loadThemeLanguageFiles');
}

/*
 * Hook zur Ermittlung und Bereitstellung der AJAX-URL
 */
if (TL_MODE == 'FE') {
	$GLOBALS['TL_HOOKS']['generatePage'][] = array('Merconis\Core\ls_shop_generalHelper', 'ls_shop_provideInfosForJS');
}

/*
 * Hooks für checkoutData
 */
if (TL_MODE == 'FE') {
	$GLOBALS['TL_HOOKS']['processFormData'][] = array('Merconis\Core\ls_shop_checkoutData', 'ls_shop_processFormData');
	$GLOBALS['TL_HOOKS']['loadFormField'][] = array('Merconis\Core\ls_shop_checkoutData', 'ls_shop_loadFormField');
}


/*
 * Hooks for form validation
 */
if (TL_MODE == 'FE') {
    $GLOBALS['TL_HOOKS']['loadFormField'][] = array('Merconis\Core\ls_shop_generalHelper', 'handleConditionalFormFields');
}

/*
 * Hooks für Ajax
 */
$GLOBALS['TL_HOOKS']['executePreActions'][] = array('Merconis\Core\ls_shop_ajaxController', 'executePreActions');
$GLOBALS['TL_HOOKS']['executePostActions'][] = array('Merconis\Core\ls_shop_ajaxController', 'executePostActions');

/*
 * Custom Inserttags
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Merconis\Core\ls_shop_customInserttags', 'customInserttags');

/*
 * Hook für bedingte CTE-Ausgabe
 */
$GLOBALS['TL_HOOKS']['getContentElement'][] = array('Merconis\Core\ls_shop_generalHelper', 'conditionalCTEOutput');
$GLOBALS['TL_HOOKS']['getArticle'][] = array('Merconis\Core\ls_shop_generalHelper', 'conditionalArticleOutput');

/*
 * Hook zum Generieren und Einfügen des Filter-Formulars an seine Platzhalterstelle
 */
$GLOBALS['TL_HOOKS']['outputFrontendTemplate'][] = array('Merconis\Core\ls_shop_filterController', 'generateAndInsertFilterForms');

$GLOBALS['TL_HOOKS']['modifyFrontendPage'][] = array('Merconis\Core\ls_shop_generalHelper', 'callback_modifyFrontendPage');

/*
 * Hook for the multiLanguage DCA manipulation
 */
if (\Input::get('do') != 'themes' || \Input::get('key') != 'importTheme') {
	$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Merconis\Core\ls_shop_languageHelper', 'createMultiLanguageDCAFields');
}
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('Merconis\Core\ls_shop_generalHelper', 'removeFieldsForEditAll');

/*
 * Hooks for language selector
 */
$GLOBALS['LS_LANGUAGESELECTOR_HOOKS']['modifyLanguageLinks'][] = array('Merconis\Core\ls_shop_languageHelper', 'modifyLanguageSelectorLinks');

/*
 * Hook to allow payment provider callbacks to work
 */
$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('Merconis\Core\ls_shop_generalHelper', 'bypassRefererCheckIfNecessary');
$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('Merconis\Core\ls_shop_cartHelper', 'initializeEmptyCart');

/*
 * ->
 * Hooks to register API resources
 */
$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController', 'processRequest');

/*
 * Use the modifyFrontendPage hook and in case of an api request the hook $GLOBALS['LS_API_HOOKS']['afterProcessingRequest']
 * to execute functionality that we would want to execute in destructor functions but can't because of symfony's
 * custom session handling
 */
$GLOBALS['TL_HOOKS']['modifyFrontendPage'][] = array('Merconis\Core\ls_shop_generalHelper', 'storeConfiguratorDataToSession');
$GLOBALS['LS_API_HOOKS']['afterProcessingRequest'][] = array('Merconis\Core\ls_shop_generalHelper', 'storeConfiguratorDataToSession');

$GLOBALS['TL_HOOKS']['modifyFrontendPage'][] = array('Merconis\Core\ls_shop_generalHelper', 'storeCustomizerDataToSession');
$GLOBALS['LS_API_HOOKS']['afterProcessingRequest'][] = array('Merconis\Core\ls_shop_generalHelper', 'storeCustomizerDataToSession');

if (TL_MODE === 'FE') {
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_variantSelector', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_exportFrontend', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_productManagement', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_cart', 'processRequest');
}

if (TL_MODE === 'BE') {
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_exportBackend', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiControllerBackend', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_dashboard', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_themeExporter', 'processRequest');
}
/*
 * <-
 */

if (TL_MODE == 'BE') {
	$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_shop_BE.js';
	$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_x_controller.js';
}

\Contao\ArrayUtil::arrayInsert($GLOBALS['BE_MOD'], 0, array(
	'merconis' => array(
		'ls_shop_dashboard' => array(
			'callback' => 'Merconis\Core\dashboard'
		),
		'ls_shop_settings' => array(
			'tables' => array('tl_lsShopSettings'),
		),
		'ls_shop_output_definitions' => array(
			'tables' => array('tl_ls_shop_output_definitions'),
		),
		'ls_shop_delivery_info' => array(
			'tables' => array('tl_ls_shop_delivery_info'),
		),
		'ls_shop_steuersaetze' => array(
			'tables' => array('tl_ls_shop_steuersaetze'),
		),
		'ls_shop_payment_methods' => array(
			'tables' => array('tl_ls_shop_payment_methods'),
		),
		'ls_shop_shipping_methods' => array(
			'tables' => array('tl_ls_shop_shipping_methods'),
		),
		'ls_shop_message_type' => array(
			'tables' => array('tl_ls_shop_message_type', 'tl_ls_shop_message_model'),
		),
		'ls_shop_cross_seller' => array(
			'tables' => array('tl_ls_shop_cross_seller'),
		),
		'ls_shop_coupon' => array(
			'tables' => array('tl_ls_shop_coupon'),
		),

		'ls_shop_attributes' => array(
			'tables' => array('tl_ls_shop_attributes', 'tl_ls_shop_attribute_values'),
		),

		'ls_shop_filter_fields' => array(
			'tables' => array('tl_ls_shop_filter_fields', 'tl_ls_shop_filter_field_values'),
		),

		'ls_shop_configurator' => array(
			'tables' => array('tl_ls_shop_configurator'),
		),

		'ls_shop_product' => array(
			'tables' => array('tl_ls_shop_product', 'tl_ls_shop_variant'),
		),

		'ls_shop_import' => array(
			'tables' => array('tl_ls_shop_import'),
			'callback' => 'Merconis\Core\ls_shop_import',
			'javascript' => 'bundles/leadingsystemsmerconis/js/ls_shop_BE_import.js?rand='.rand(0,99999),
		),

		'ls_shop_productSearch' => array(
			'callback' => 'Merconis\Core\ls_shop_beModule_productSearch',
		),

		'ls_shop_stockManagement' => array(
			'callback' => 'Merconis\Core\ls_shop_beModule_stockManagement',
		),

		'ls_shop_orders' => array(
			'tables' => array('tl_ls_shop_orders'),
		),

		'ls_shop_export' => array(
			'tables' => array('tl_ls_shop_export'),
		),

		'ls_shop_messages_sent' => array(
			'tables' => array('tl_ls_shop_messages_sent'),
		),

        'ls_shop_producer' => array(
            'tables' => array('tl_ls_shop_producer'),
        )
	)
));

$GLOBALS['BE_FFL']['htmlDiv'] = 'Merconis\Core\ls_shop_htmlDiv';
$GLOBALS['BE_FFL']['simpleOutput'] = 'Merconis\Core\ls_shop_simpleOutput';
$GLOBALS['BE_FFL']['ls_shop_productSelectionWizard'] = 'Merconis\Core\ls_shop_productSelectionWizard';
$GLOBALS['BE_FFL']['ls_shop_generatedTemplate'] = 'Merconis\Core\ls_shop_generatedTemplate';


$GLOBALS['FE_MOD']['ls_shop'] = array(
	'ls_shop_cart' => 'Merconis\Core\ModuleCart',
	'ls_shop_orderReview' => 'Merconis\Core\ModuleOrderReview',
	'ls_shop_checkoutFinish' => 'Merconis\Core\ModuleCheckoutFinish',
	'ls_shop_afterCheckout' => 'Merconis\Core\ModuleAfterCheckout',
	'ls_shop_paymentAfterCheckout' => 'Merconis\Core\ModulePaymentAfterCheckout',
	'ls_shop_productOverview' => 'Merconis\Core\ModuleProductOverview',
	'ls_shop_productSingleview' => 'Merconis\Core\ModuleProductSingleview',
	'ls_shop_cross_seller' => 'Merconis\Core\ModuleCrossSeller',
	'ls_shop_productSearch' => 'Merconis\Core\ModuleProductSearch',
	'ls_shop_myOrders' => 'Merconis\Core\ModuleMyOrders',
	'ls_shop_myOrderDetails' => 'Merconis\Core\ModuleMyOrderDetails',
	'ls_shop_filterForm' => 'Merconis\Core\ModuleFilterForm',
	'ls_shop_productManagementApiInspector' => 'Merconis\Core\ModuleProductManagementApiInspector'
);

/**
 * Hinzufügen von Content-Elementen
 */
$GLOBALS['TL_CTE']['lsShop']['lsShopCrossSellerCTE'] = 'Merconis\Core\ls_shop_cross_sellerCTE';

$GLOBALS['TL_HOOKS']['getUserNavigation'][] = array('Merconis\Core\ls_shop_generalHelper', 'manipulateBackendNavigation');

$GLOBALS['TL_HOOKS']['getSystemMessages'][] = array('Merconis\Core\ls_shop_generalHelper', 'getMerconisSystemMessages');