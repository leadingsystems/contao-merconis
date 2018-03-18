<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_lsShopSettings'] = array(
	'config' => array(
		'dataContainer' => 'File',
		'closed' => true,
		'onsubmit_callback' => array(
			array('Merconis\Core\tl_lsShopSettings_controller', 'restartOrderNrCounter'),
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		)
	),
	
	'palettes' => array(
		'__selector__' => array('ls_shop_useVATIDValidation'),
		'default' => '
		{basic_legend},ls_shop_serial,ls_shop_country,ls_shop_currency,ls_shop_currencyCode,ls_shop_numDecimals,ls_shop_priceRoundingFactor,ls_shop_priceType,ls_shop_numDecimalsWeight,ls_shop_weightUnit,ls_shop_quantityDefault,ls_shop_versandkostenType,ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods,ls_shop_ownEmailAddress,ls_shop_output_definitionset,ls_shop_delivery_infoSet,ls_shop_productDetailsTemplate,ls_shop_standardProductImageFolder,ls_shop_standardProductImageDelimiter,ls_shop_imageSortingStandardDirection,ls_shop_beOrderTemplateOverview,ls_shop_beOrderTemplateDetails;
		
		{euSettings_legend},ls_shop_ownVATID,ls_shop_euCountrycodes,ls_shop_useVATIDValidation;

		{userSettings_legend},ls_shop_standardGroup,ls_shop_allowCheckout;
		
		{orderNr_legend},ls_shop_orderNrCounter,ls_shop_orderNrString,ls_shop_orderNrStart,ls_shop_orderNrRestartCycle,ls_shop_orderNrRestartNow;
		
		{orderStatusTypes_legend},ls_shop_orderStatusValues01,ls_shop_orderStatusValues02,ls_shop_orderStatusValues03,ls_shop_orderStatusValues04,ls_shop_orderStatusValues05;
		
		{liveHits_legend},ls_shop_liveHitFields,ls_shop_liveHitImageSizeWidth,ls_shop_liveHitImageSizeHeight,ls_shop_liveHitsMaxNumHits,ls_shop_liveHitsMinLengthSearchTerm,ls_shop_liveHitsNoAutoPosition,ls_shop_liveHitsDOMSelector;
		
		{systemImages_legend},ls_shop_systemImages_videoDummyCover,ls_shop_systemImages_videoCoverOverlay,ls_shop_systemImages_videoCoverOverlaySmall,ls_shop_systemImages_isNewOverlay,ls_shop_systemImages_isNewOverlaySmall,ls_shop_systemImages_isOnSaleOverlay,ls_shop_systemImages_isOnSaleOverlaySmall;
		{pageSettings_legend},ls_shop_shippingInfoPages,ls_shop_cartPages,ls_shop_reviewPages,ls_shop_signUpPages,ls_shop_checkoutPaymentErrorPages,ls_shop_checkoutShippingErrorPages,ls_shop_checkoutFinishPages,ls_shop_paymentAfterCheckoutPages,ls_shop_afterCheckoutPages,ls_shop_ajaxPages,ls_shop_searchResultPages,ls_shop_myOrdersPages,ls_shop_myOrderDetailsPages;
		{systemSettings_legend},ls_shop_loginModuleID,ls_shop_miniCartModuleID;
		{performanceSettings_legend},ls_shop_maxNumParallelSearchCaches,ls_shop_searchCacheLifetimeSec,ls_shop_considerGroupPricesInFilterAndSorting;
		{importSettings_legend},ls_shop_standardProductImportFolder,ls_shop_importFlexFieldKeys,ls_shop_importFlexFieldKeysLanguageIndependent,ls_shop_importCsvDelimiter,ls_shop_importCsvEnclosure,ls_shop_importCsvEscape,ls_shop_importCsvLocale,ls_shop_numMaxImportRecordsPerRound;
		{compatSettings_legend},ls_shop_blnCompatMode2-1-4;
		{ipWhitelist_legend},ls_shop_ipWhitelist,ls_shop_urlWhitelist;
		{backendLsjs_legend},ls_shop_lsjsDebugMode,ls_shop_lsjsNoCacheMode,ls_shop_lsjsNoMinifierMode;
		{misc_legend},ls_shop_sortingCharacterTranslationTable,ls_shop_dcaNamesWithoutMultilanguageSupport'
	),

	'subpalettes' => array(
		'ls_shop_useVATIDValidation' => 'ls_shop_VATIDValidationSOAPOptions'
	),
	
	'fields' => array(
		'ls_shop_beOrderTemplateOverview' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateOverview'],
			'inputType' => 'select',
			'options_callback' => array('Merconis\Core\ls_shop_generalHelper', 'getTemplates_beOrderOverview'),
			'eval' => array('includeBlankOption' => false, 'tl_class' => 'w50')
		),
		
		'ls_shop_ownEmailAddress' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownEmailAddress'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'preserveTags' => true, 'tl_class'=>'w50', 'rgxp' => 'email')
		),
		
		'ls_shop_beOrderTemplateDetails' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateDetails'],
			'inputType' => 'select',
			'options_callback' => array('Merconis\Core\ls_shop_generalHelper', 'getTemplates_beOrderDetails'),
			'eval' => array('includeBlankOption' => false, 'tl_class' => 'w50')
		),
		
		'ls_shop_imageSortingStandardDirection' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('name_asc', 'name_desc', 'date_asc', 'date_desc', 'random'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection']['options'],
			'eval'                    => array('tl_class'=>'w50')
		),
		
		'ls_shop_orderStatusValues01' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues01'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50')
		),
	
		'ls_shop_orderStatusValues02' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues02'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50')
		),
	
		'ls_shop_orderStatusValues03' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues03'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50')
		),
	
		'ls_shop_orderStatusValues04' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues04'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50')
		),
	
		'ls_shop_orderStatusValues05' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues05'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('tl_class' => 'w50')
		),
	
		'ls_shop_liveHitFields' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitFields'],
			'exclude'                 => true,
			'inputType'               => 'checkboxWizard',
			'options'				  => array('_title', '_code', '_shortDescription', '_mainImage', '_priceAfterTaxFormatted', '_linkToProduct'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitFields']['options'],
			'eval'                    => array('multiple' => true, 'tl_class' => 'clr')
		),
		
		'ls_shop_liveHitsNoAutoPosition' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsNoAutoPosition'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'w50')
		),
		
		'ls_shop_liveHitsDOMSelector' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsDOMSelector'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50'),
			'load_callback' => array(
				array('Merconis\Core\tl_lsShopSettings_controller', 'ls_html_entity_decode')
			)
		),
		
		'ls_shop_liveHitImageSizeWidth' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeWidth'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_liveHitImageSizeHeight' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeHeight'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_liveHitsMaxNumHits' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMaxNumHits'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_liveHitsMinLengthSearchTerm' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMinLengthSearchTerm'],
			'inputType' => 'text',
			'eval' => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50')
		),
		
		'ls_shop_orderNrCounter' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrCounter'],
			'inputType' => 'simpleOutput',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_orderNrString' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrString'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50'),
			'load_callback' => array(
				array('Merconis\Core\tl_lsShopSettings_controller', 'ls_html_entity_decode')
			)
		),
		
		'ls_shop_loginModuleID' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_loginModuleID'],
			'inputType' => 'select',
			'foreignKey' => 'tl_module.name'
		),
		
		'ls_shop_miniCartModuleID' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_miniCartModuleID'],
			'inputType' => 'select',
			'foreignKey' => 'tl_module.name'
		),
		
		'ls_shop_output_definitionset' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_output_definitionset'],
			'default'				  => 0,
			'exclude'                 => true,
			'inputType'               => 'select',
			'foreignKey'			  => 'tl_ls_shop_output_definitions.title',
			'reference'				  => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_output_definitionset']['reference'],
			'eval'					  => array('tl_class' => 'w50', 'includeBlankOption' => true)
		),
		
		'ls_shop_orderNrStart' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrStart'],
			'inputType' => 'text',
			'eval' => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50')
		),
		
		'ls_shop_orderNrRestartCycle' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('never', 'year', 'month', 'week', 'day'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle']['options'],
			'eval'                    => array('tl_class'=>'w50')
		),
		
		'ls_shop_orderNrRestartNow' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartNow'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),
		
		'ls_shop_ownVATID' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownVATID'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_euCountrycodes' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_euCountrycodes'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_serial' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_serial'],
			'inputType' => 'text',
			'eval' => array('mandatory' => false, 'tl_class'=>'w50')
		),
		
		'ls_shop_country' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_country'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'minlength' => 2, 'maxlength' => 2, 'tl_class'=>'w50')
		),
		
		'ls_shop_currency' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currency'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'minlength' => 1, 'maxlength' => 10, 'tl_class'=>'w50')
		),
		
		'ls_shop_currencyCode' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currencyCode'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'minlength' => 3, 'maxlength' => 3, 'tl_class'=>'w50')
		),
		
		'ls_shop_weightUnit' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_weightUnit'],
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'minlength' => 1, 'maxlength' => 10, 'tl_class'=>'w50')
		),
		
		'ls_shop_quantityDefault' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_quantityDefault'],
			'inputType' => 'text',
			'eval' => array('minlength' => 1, 'maxlength' => 10, 'tl_class'=>'w50')
		),
		
		'ls_shop_delivery_infoSet' => array(
			'exclude'		=> true,
			'label'			=> &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_delivery_infoSet'],
			'inputType'		=> 'select',
			'foreignKey'	=> 'tl_ls_shop_delivery_info.title',
			'eval'			=> array('tl_class' => 'w50', 'includeBlankOption' => false)
		),
		
		'ls_shop_numDecimals' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimals'],
			'inputType' => 'text',
			'eval' => array('rgxp' => 'oneNumber', 'tl_class'=>'w50')
		),
		
		'ls_shop_priceRoundingFactor' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor'],
			'default' => 100,
			'inputType' => 'select',
			'options' => array(100 => '100', 20 => '20'),
			'reference' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor']['options'],
			'eval' => array('helpwizard' => true, 'tl_class'=>'w50')
		),
		
		'ls_shop_numDecimalsWeight' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimalsWeight'],
			'inputType' => 'text',
			'eval' => array('rgxp' => 'oneNumber', 'tl_class'=>'w50')
		),
		
		'ls_shop_priceType' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType'],
			'inputType' => 'select',
			'options' => array('brutto', 'netto'),
			'reference' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType']['options'],
			'eval' => array('helpwizard' => true, 'tl_class'=>'w50')
		),
		
		'ls_shop_versandkostenType' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType'],
			'inputType' => 'select',
			'options' => array('excl', 'incl'),
			'reference' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType']['options'],
			'eval' => array('helpwizard' => true, 'tl_class'=>'w50')
		),
		
		'ls_shop_standardGroup' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardGroup'],
			'exclude' => true,
			'inputType' => 'select',
			'foreignKey' => 'tl_member_group.name',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_allowCheckout' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('withLogin', 'withoutLogin', 'both'),
			'reference' => $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout']['options'],
			'eval' => array('helpwizard' => true, 'tl_class'=>'w50')
		),
		
		'ls_shop_shippingInfoPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_shippingInfoPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_cartPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_cartPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_reviewPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_reviewPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_signUpPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_signUpPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_checkoutPaymentErrorPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutPaymentErrorPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),

		'ls_shop_checkoutShippingErrorPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutShippingErrorPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_checkoutFinishPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutFinishPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_afterCheckoutPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_afterCheckoutPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_paymentAfterCheckoutPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_paymentAfterCheckoutPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),

		'ls_shop_ajaxPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ajaxPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_searchResultPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchResultPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_myOrdersPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrdersPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_myOrderDetailsPages' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrderDetailsPages'],
			'exclude' => true,
			'inputType' => 'pageTree',
			'eval' => array('fieldType'=>'checkbox', 'multiple' => true)
		),
		
		'ls_shop_systemImages_videoDummyCover' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoDummyCover'],
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_systemImages_videoCoverOverlay' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoCoverOverlay'],
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_systemImages_videoCoverOverlaySmall' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoCoverOverlaySmall'],
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_systemImages_isNewOverlay' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isNewOverlay'],
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_systemImages_isNewOverlaySmall' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isNewOverlaySmall'],
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_systemImages_isOnSaleOverlay' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isOnSaleOverlay'],
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_systemImages_isOnSaleOverlaySmall' => array(
			'exclude'		=>	true,
			'label'			=>	&$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isOnSaleOverlaySmall'],
			'inputType'		=>	'fileTree',
			'eval'			=> array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),
		
		'ls_shop_useVATIDValidation' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useVATIDValidation'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr', 'submitOnChange' => true)
		),
		
		'ls_shop_VATIDValidationSOAPOptions' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_VATIDValidationSOAPOptions'],
			'exclude'                 => true,
			'inputType'				  => 'text',
			'eval'			=> array(
				'decodeEntities' => true,
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": ""
							},
							{
								"type": "text",
								"label": ""
							}
						],
						"cssClass": ""
					}
				'
			)
		),
		
		'ls_shop_productDetailsTemplate' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_productDetailsTemplate'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_productDetails_'),
			'default'                 => 'template_productDetails_01',
			'eval'					  => array('tl_class' => 'w50')
		),
		
		'ls_shop_standardProductImageFolder' => array(
			'label'					  => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageFolder'],
			'exclude'                 => true,
			'inputType'				  => 'fileTree',
			'eval'					  => array('fieldType' => 'radio', 'files' => false, 'tl_class' => 'clr'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_standardProductImageDelimiter' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageDelimiter'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'maxlength' => 1, 'mandatory' => true)
		),
		
		'ls_shop_standardProductImportFolder' => array(
			'exclude' => true,
			'label'					  => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImportFolder'],
			'inputType'				  => 'fileTree',
			'eval'					  => array('fieldType' => 'radio', 'files' => false, 'tl_class' => 'clr'),
			'save_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'idFromUuid')
			),
			'load_callback' => array(
				array('LeadingSystems\Helpers\ls_helpers_controller', 'uuidFromId')
			)
		),
		
		'ls_shop_importFlexFieldKeys' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeys'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_importFlexFieldKeysLanguageIndependent' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeysLanguageIndependent'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_importCsvDelimiter' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvDelimiter'],
			'inputType' => 'text',
			'eval' => array('mandatory' => false, 'maxlength' => 1, 'tl_class'=>'w50', 'decodeEntities' => true)
		),
		
		'ls_shop_importCsvEnclosure' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEnclosure'],
			'inputType' => 'text',
			'eval' => array('mandatory' => false, 'maxlength' => 1, 'tl_class'=>'w50', 'decodeEntities' => true)
		),
		
		'ls_shop_importCsvEscape' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEscape'],
			'inputType' => 'text',
			'eval' => array('mandatory' => false, 'maxlength' => 1, 'tl_class'=>'w50', 'decodeEntities' => true),
			'save_callback' => array(
				array('Merconis\Core\tl_lsShopSettings_controller', 'ls_escapeBackslash')
			),
			'load_callback' => array(
				array('Merconis\Core\tl_lsShopSettings_controller', 'ls_unescapeBackslash')
			)
		),
		
		'ls_shop_importCsvLocale' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvLocale'],
			'inputType' => 'text',
			'eval' => array('mandatory' => false, 'decodeEntities' => true, 'tl_class'=>'w50')
		),
		
		'ls_shop_numMaxImportRecordsPerRound' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numMaxImportRecordsPerRound'],
			'inputType' => 'text',
			'eval' => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50')
		),
		
		'ls_shop_maxNumParallelSearchCaches' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_maxNumParallelSearchCaches'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_searchCacheLifetimeSec' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchCacheLifetimeSec'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_considerGroupPricesInFilterAndSorting' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_considerGroupPricesInFilterAndSorting'],
			'inputType' => 'checkbox',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_blnCompatMode2-1-4' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_blnCompatMode2-1-4'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'w50')
		),
		
		'ls_shop_ipWhitelist' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ipWhitelist'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		),
		
		'ls_shop_urlWhitelist' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_urlWhitelist'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50', 'decodeEntities' => true)
		),

		'ls_shop_lsjsDebugMode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsDebugMode'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'ls_shop_lsjsNoCacheMode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsNoCacheMode'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'ls_shop_lsjsNoMinifierMode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsNoMinifierMode'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		),

		'ls_shop_sortingCharacterTranslationTable' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_sortingCharacterTranslationTable'],
			'exclude'                 => true,
			'inputType'				  => 'text',
			'eval'			=> array(
				'decodeEntities' => true,
				'tl_class' => 'clr merconis-component-autostart--merconisWidgetMultiText',
				'data-merconis-widget-options' => '
					{
						"arr_fields": [
							{
								"type": "text",
								"label": ""
							},
							{
								"type": "text",
								"label": ""
							}
						],
						"cssClass": ""
					}
				'
			)
		),
		
		'ls_shop_dcaNamesWithoutMultilanguageSupport' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_dcaNamesWithoutMultilanguageSupport'],
			'inputType' => 'text',
			'eval' => array('tl_class'=>'w50')
		)
	)
);
	
class tl_lsShopSettings_controller extends \Backend {
	public function __construct() {
		parent::__construct();
	}
	
	public function restartOrderNrCounter($dc) {
		if (isset($GLOBALS['TL_CONFIG']['ls_shop_orderNrRestartNow']) && $GLOBALS['TL_CONFIG']['ls_shop_orderNrRestartNow']) {
			$this->Config->update("\$GLOBALS['TL_CONFIG']['ls_shop_orderNrRestartNow']", '');
			
			/*
			 * Beim Zurücksetzen des Zählers wird dieser auf 0 gesetzt und nicht direkt auf den Startwert,
			 * da beim Abschluss einer Bestellung automatisch der Startwert verwendet wird, sofern der Zähler
			 * auf 0 steht.
			 */
			$this->Config->update("\$GLOBALS['TL_CONFIG']['ls_shop_orderNrCounter']", 0);
		}
	}

	public function ls_html_entity_decode($value = '') {
		return html_entity_decode($value);
	}

	public function ls_escapeBackslash($value = '') {
		if (version_compare(VERSION . '.' . BUILD, '3.2.9', '>=')) {
			return $value;
		}
		return $value == '\\' ? '\\\\' : $value;
	}

	public function ls_unescapeBackslash($value = '') {
		if (version_compare(VERSION . '.' . BUILD, '3.2.9', '>=')) {
			return $value;
		}
		return $value == '\\\\' ? '\\' : $value;
	}
}
