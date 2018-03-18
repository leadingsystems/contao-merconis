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
	ob_start();
	?>
	<script type="text/javascript">
		window.addEvent('domready', function () {
			if (lsjs.__appHelpers.merconisBackendApp !== undefined && lsjs.__appHelpers.merconisBackendApp !== null) {
				lsjs.__appHelpers.merconisBackendApp.obj_config.REQUEST_TOKEN = '<?php echo \RequestToken::get(); ?>';
				lsjs.__appHelpers.merconisBackendApp.obj_config.API_KEY = '<?php echo $GLOBALS['TL_CONFIG']['ls_api_key']; ?>';
				lsjs.__appHelpers.merconisBackendApp.start();
			}
		});
	</script>
	<?php
	$GLOBALS['TL_MOOTOOLS'][] = ob_get_clean();

	$GLOBALS['TL_JAVASCRIPT'][] = 'assets/lsjs/core/appBinder/binder.php?output=js&pathToApp='.urldecode('_dup4_/web/bundles/leadingsystemsmerconis/js/lsjs/backend/app').'&includeCore=no&includeCoreModules=no'.($GLOBALS['TL_CONFIG']['ls_shop_lsjsDebugMode'] ? '&debug=1' : '').($GLOBALS['TL_CONFIG']['ls_shop_lsjsNoCacheMode'] ? '&no-cache=1' : '').($GLOBALS['TL_CONFIG']['ls_shop_lsjsNoMinifierMode'] ? '&&no-minifier=1' : '');
	$GLOBALS['TL_CSS'][] = 'assets/lsjs/core/appBinder/binder.php?output=css&pathToApp='.urldecode('_dup4_/web/bundles/leadingsystemsmerconis/js/lsjs/backend/app').'&includeCore=no&includeCoreModules=no'.($GLOBALS['TL_CONFIG']['ls_shop_lsjsNoCacheMode'] ? '&no-cache=1' : '').($GLOBALS['TL_CONFIG']['ls_shop_lsjsNoMinifierMode'] ? '&&no-minifier=1' : '');
}

if (TL_MODE == 'FE') {
	$GLOBALS['TL_HOOKS']['getPageLayout'][] = array('Merconis\Core\ls_shop_generalHelper', 'ls_shop_switchTemplateInDetailsViewIfNecessary');
	/*
	 * Hook for writing the layout's filter settings into the global variables for later use
	 */
	$GLOBALS['TL_HOOKS']['getPageLayout'][] = array('Merconis\Core\ls_shop_generalHelper', 'merconis_getLayoutSettingsForGlobalUse');
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
	$GLOBALS['TL_HOOKS']['postLogin'][] = array('Merconis\Core\ls_shop_checkoutData', 'ls_shop_postLogin');
}


/*
 * Hooks for form validation
 */
if (TL_MODE == 'FE') {
	$GLOBALS['TL_HOOKS']['loadFormField'][] = array('Merconis\Core\ls_shop_generalHelper', 'handleMandatoryOnCondition');
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
 * Hook für Suchindex
 */
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('Merconis\Core\ls_shop_generalHelper', 'getSearchablePages');

/*
 * Hook für bedingte CTE-Ausgabe
 */
$GLOBALS['TL_HOOKS']['getContentElement'][] = array('Merconis\Core\ls_shop_generalHelper', 'conditionalCTEOutput');

/*
 * Hook zum Generieren und Einfügen des Filter-Formulars an seine Platzhalterstelle
 */
$GLOBALS['TL_HOOKS']['outputFrontendTemplate'][] = array('Merconis\Core\ls_shop_filterController', 'generateAndInsertFilterForms');

$GLOBALS['TL_HOOKS']['outputFrontendTemplate'][] = array('Merconis\Core\ls_shop_generalHelper', 'callback_outputFrontendTemplate');

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

if (TL_MODE === 'FE') {
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_variantSelector', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_exportFrontend', 'processRequest');
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_productManagement', 'processRequest');
}

if (TL_MODE === 'BE') {
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_exportBackend', 'processRequest');
}

if (TL_MODE === 'BE') {
	$GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiControllerBackend', 'processRequest');
}
/*
 * <-
 */

$GLOBALS['TL_CRON']['daily'][] = array('Merconis\Core\ls_shop_generalHelper','sendMessagesOnStatusChangeCronDaily');
$GLOBALS['TL_CRON']['hourly'][] = array('Merconis\Core\ls_shop_generalHelper','sendMessagesOnStatusChangeCronHourly');

if (TL_MODE == 'BE') {
	$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_shop_BE.js';
	$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_x_controller.js';
}

if (TL_MODE == 'BE') {
	$GLOBALS['TL_CSS'][] = 'bundles/leadingsystemsmerconis/css/beStyle.css';
}

array_insert($GLOBALS['BE_MOD'], 0, array(
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
	'ls_shop_ajaxGeneral' => 'Merconis\Core\ModuleAjaxGeneral',
	'ls_shop_productManagementApiInspector' => 'Merconis\Core\ModuleProductManagementApiInspector'
);

/**
 * Hinzufügen von Content-Elementen
 */
$GLOBALS['TL_CTE']['lsShop']['lsShopCrossSellerCTE'] = 'Merconis\Core\ls_shop_cross_sellerCTE';
