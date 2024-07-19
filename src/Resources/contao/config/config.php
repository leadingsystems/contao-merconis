<?php

namespace Merconis\Core;

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

define('TL_MERCONIS_INSTALLER', 'MERCONIS INSTALLER');
define('TL_MERCONIS_THEME_SETUP', 'MERCONIS THEME_SETUP');
define('TL_MERCONIS_IMPORTER', 'MERCONIS IMPORTER');
define('TL_MERCONIS_GENERAL', 'MERCONIS GENERAL');
define('TL_MERCONIS_ERROR', 'MERCONIS ERROR');
define('TL_MERCONIS_MESSAGES', 'MERCONIS MESSAGES');
define('TL_MERCONIS_STOCK_MANAGEMENT', 'MERCONIS STOCK MANAGEMENT');

$GLOBALS['TL_HOOKS']['merconisCustomTaxRateCalculation'][] = array('Merconis\Core\ls_shop_generalHelper', 'merconisCustomTaxRateCalculation');

/*
 * Hooks for language selector
 */
$GLOBALS['LS_LANGUAGESELECTOR_HOOKS']['modifyLanguageLinks'][] = array('Merconis\Core\ls_shop_languageHelper', 'modifyLanguageSelectorLinks');

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
$GLOBALS['LS_API_HOOKS']['afterProcessingRequest'][] = array('Merconis\Core\ls_shop_generalHelper', 'storeConfiguratorDataToSession');
$GLOBALS['LS_API_HOOKS']['afterProcessingRequest'][] = array('Merconis\Core\ls_shop_generalHelper', 'storeCustomizerDataToSession');

/** Frontend Hooks */
if (!System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_variantSelector', 'processRequest');
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_exportFrontend', 'processRequest');
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_productManagement', 'processRequest');
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_cart', 'processRequest');
}
/** Backend Hooks */
if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_exportBackend', 'processRequest');
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiControllerBackend', 'processRequest');
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_dashboard', 'processRequest');
    $GLOBALS['LS_API_HOOKS']['apiReceiver_processRequest'][] = array('Merconis\Core\ls_shop_apiController_themeExporter', 'processRequest');
}

if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
{
	$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_shop_BE.js';
	$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/leadingsystemsmerconis/js/ls_x_controller.js';
}

if (!System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
{
    $GLOBALS['TL_HOOKS']['loadFormField'][] = array('Merconis\Core\ls_shop_generalHelper', 'handleConditionalFormFields');
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
