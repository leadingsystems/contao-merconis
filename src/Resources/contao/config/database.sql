/*
relevant foreign key relations concerning tables which are not defined in this file:

@tl_article.pid@tl_page.id=single@
@tl_content.pid@tl_article.id=single@
@tl_content.form@tl_form.id=single@
@tl_content.module@tl_module.id=single@
@tl_form_field.pid@tl_form.id=single@
@tl_layout.modules@tl_module.id=special@
@tl_page.ls_cnc_languageSelector_correspondingMainLanguagePage@tl_page.id=single@
@tl_page.jumpTo@tl_page.id=single@
@tl_newsletter_channel.jumpTo@tl_page.id=single@
@tl_page.layout@tl_layout.id=single@
@tl_page.groups@tl_member_group.id=array@
@tl_member.groups@tl_member_group.id=array@
@tl_layout.pid@tl_theme.id=single@
@tl_module.pid@tl_theme.id=single@
@tl_module.rootPage@tl_page.id=single@
@tl_module.reg_groups@tl_member_group.id=array@
@tl_module.news_archives@tl_news_archive.id=array@
@tl_news.pid@tl_news_archive.id=single@


localconfig foreign key relations are also noted here although their parent table is in fact the localconfig file:

@localconfig.ls_shop_shippingInfoPages@tl_page.id=array@
@localconfig.ls_shop_cartPages@tl_page.id=array@
@localconfig.ls_shop_reviewPages@tl_page.id=array@
@localconfig.ls_shop_signUpPages@tl_page.id=array@
@localconfig.ls_shop_checkoutPaymentErrorPages@tl_page.id=array@
@localconfig.ls_shop_checkoutShippingErrorPages@tl_page.id=array@
@localconfig.ls_shop_checkoutFinishPages@tl_page.id=array@
@localconfig.ls_shop_afterCheckoutPages@tl_page.id=array@
@localconfig.ls_shop_paymentAfterCheckoutPages@tl_page.id=array@
@localconfig.ls_shop_ajaxPages@tl_page.id=array@
@localconfig.ls_shop_searchResultPages@tl_page.id=array@
@localconfig.ls_shop_myOrdersPages@tl_page.id=array@
@localconfig.ls_shop_myOrderDetailsPages@tl_page.id=array@
@localconfig.ls_shop_loginModuleID@tl_module.id=single@
@localconfig.ls_shop_miniCartModuleID@tl_module.id=single@
@localconfig.ls_shop_standardGroup@tl_member_group.id=single@
@localconfig.ls_shop_systemImages_videoDummyCover@tl_files.id=single@
@localconfig.ls_shop_standardProductImageFolder@tl_files.id=single@
@localconfig.ls_shop_standardProductImportFolder@tl_files.id=single@
*/

/*
@tl_ls_shop_product.pages@tl_page.id=array@
@tl_ls_shop_product.lsShopProductSteuersatz@tl_ls_shop_steuersaetze.id=single@
@tl_ls_shop_product.lsShopProductRecommendedProducts@tl_ls_shop_product.id=array@
@tl_ls_shop_product.associatedProducts@tl_ls_shop_product.id=array@
@tl_ls_shop_product.lsShopProductDeliveryInfoSet@tl_ls_shop_delivery_info.id=single@
@tl_ls_shop_product.configurator@tl_ls_shop_configurator.id=single@
*/
CREATE TABLE `tl_ls_shop_product` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`sorting` int(10) unsigned NOT NULL default '0',
	`tstamp` int(10) unsigned NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
	`keywords` text NULL,
	`pageTitle` varchar(255) NOT NULL default '',
	`pageDescription` varchar(255) NOT NULL default '',
	`shortDescription` text NULL,
	`description` text NULL,
	`published` char(1) NOT NULL default '',
	`pages` blob NULL,

	`useGroupRestrictions` char(1) NOT NULL default '',
	`allowedGroups` blob NULL,

	`lsShopProductPrice` decimal(12,4) NOT NULL default '0.0000',
	`useScalePrice` char(1) NOT NULL default '',
	`scalePriceType` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations` char(1) NOT NULL default '',
	`scalePriceKeyword` varchar(255) NOT NULL default '',
	`scalePrice` text NULL,
	`lsShopProductPriceOld` decimal(12,4) NOT NULL default '0.0000',
	`useOldPrice` char(1) NOT NULL default '',

	`useGroupPrices_1` char(1) NOT NULL default '',
	`priceForGroups_1` blob NULL,
	`lsShopProductPrice_1` decimal(12,4) NOT NULL default '0.0000',
	`useScalePrice_1` char(1) NOT NULL default '',
	`scalePriceType_1` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_1` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_1` char(1) NOT NULL default '',
	`scalePriceKeyword_1` varchar(255) NOT NULL default '',
	`scalePrice_1` text NULL,
	`lsShopProductPriceOld_1` decimal(12,4) NOT NULL default '0.0000',
	`useOldPrice_1` char(1) NOT NULL default '',

	`useGroupPrices_2` char(1) NOT NULL default '',
	`priceForGroups_2` blob NULL,
	`lsShopProductPrice_2` decimal(12,4) NOT NULL default '0.0000',
	`useScalePrice_2` char(1) NOT NULL default '',
	`scalePriceType_2` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_2` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_2` char(1) NOT NULL default '',
	`scalePriceKeyword_2` varchar(255) NOT NULL default '',
	`scalePrice_2` text NULL,
	`lsShopProductPriceOld_2` decimal(12,4) NOT NULL default '0.0000',
	`useOldPrice_2` char(1) NOT NULL default '',

	`useGroupPrices_3` char(1) NOT NULL default '',
	`priceForGroups_3` blob NULL,
	`lsShopProductPrice_3` decimal(12,4) NOT NULL default '0.0000',
	`useScalePrice_3` char(1) NOT NULL default '',
	`scalePriceType_3` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_3` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_3` char(1) NOT NULL default '',
	`scalePriceKeyword_3` varchar(255) NOT NULL default '',
	`scalePrice_3` text NULL,
	`lsShopProductPriceOld_3` decimal(12,4) NOT NULL default '0.0000',
	`useOldPrice_3` char(1) NOT NULL default '',

	`useGroupPrices_4` char(1) NOT NULL default '',
	`priceForGroups_4` blob NULL,
	`lsShopProductPrice_4` decimal(12,4) NOT NULL default '0.0000',
	`useScalePrice_4` char(1) NOT NULL default '',
	`scalePriceType_4` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_4` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_4` char(1) NOT NULL default '',
	`scalePriceKeyword_4` varchar(255) NOT NULL default '',
	`scalePrice_4` text NULL,
	`lsShopProductPriceOld_4` decimal(12,4) NOT NULL default '0.0000',
	`useOldPrice_4` char(1) NOT NULL default '',

	`useGroupPrices_5` char(1) NOT NULL default '',
	`priceForGroups_5` blob NULL,
	`lsShopProductPrice_5` decimal(12,4) NOT NULL default '0.0000',
	`useScalePrice_5` char(1) NOT NULL default '',
	`scalePriceType_5` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_5` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_5` char(1) NOT NULL default '',
	`scalePriceKeyword_5` varchar(255) NOT NULL default '',
	`scalePrice_5` text NULL,
	`lsShopProductPriceOld_5` decimal(12,4) NOT NULL default '0.0000',
	`useOldPrice_5` char(1) NOT NULL default '',

	`lsShopProductWeight` decimal(12,4) NOT NULL default '0.0000',
	`lsShopProductCode` varchar(255) NOT NULL default '',
	`lsShopProductSteuersatz` int(10) unsigned NOT NULL default '1',
	`lsShopProductQuantityUnit` varchar(255) NOT NULL default '',
	`lsShopProductQuantityDecimals` int(10) unsigned NOT NULL default '0',
	`lsShopProductMengenvergleichUnit` varchar(255) NOT NULL default '',
	`lsShopProductMengenvergleichDivisor` decimal(12,6) NOT NULL default '0.000000',
	`lsShopProductMainImage` binary(16) NULL,
	`lsShopProductMoreImages` blob NULL,
	`lsShopProductDetailsTemplate` varchar(64) NOT NULL default '',
	`lsShopProductIsNew` char(1) NOT NULL default '',
	`lsShopProductIsOnSale` char(1) NOT NULL default '',
	`lsShopProductRecommendedProducts` blob NULL,
	`associatedProducts` blob NULL,
	`lsShopProductDeliveryInfoSet` int(10) unsigned NOT NULL default '0',
	`lsShopProductStock` decimal(12,4) NOT NULL default '0.0000',
	`lsShopProductProducer` varchar(255) NOT NULL default '',
	`lsShopProductNumSales` int(10) unsigned NOT NULL default '0',
	`configurator` int(10) unsigned NOT NULL default '0',
	`flex_contents` mediumtext NULL,
	`flex_contentsLanguageIndependent` mediumtext NULL,
	`lsShopProductAttributesValues` text NULL
	PRIMARY KEY  (`id`),
	KEY `lsShopProductCode` (`lsShopProductCode`),
	KEY `alias` (`alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_ls_shop_variant.pid@tl_ls_shop_product.id=single@
@tl_ls_shop_variant.associatedProducts@tl_ls_shop_product.id=array@
@tl_ls_shop_variant.lsShopVariantDeliveryInfoSet@tl_ls_shop_delivery_info.id=single@
@tl_ls_shop_variant.configurator@tl_ls_shop_configurator.id=single@
*/
CREATE TABLE `tl_ls_shop_variant` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`pid` int(10) unsigned NOT NULL default '0',
	`sorting` int(10) unsigned NOT NULL default '0',
	`tstamp` int(10) unsigned NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
	`shortDescription` text NULL,
	`description` text NULL,
	`published` char(1) NOT NULL default '',
	`lsShopProductVariantAttributesValues` text NULL,

	`lsShopVariantPrice` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceType` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useScalePrice` char(1) NOT NULL default '',
	`scalePriceType` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations` char(1) NOT NULL default '',
	`scalePriceKeyword` varchar(255) NOT NULL default '',
	`scalePrice` text NULL,
	`lsShopVariantPriceOld` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceTypeOld` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useOldPrice` char(1) NOT NULL default '',

	`useGroupPrices_1` char(1) NOT NULL default '',
	`priceForGroups_1` blob NULL,
	`lsShopVariantPrice_1` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceType_1` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useScalePrice_1` char(1) NOT NULL default '',
	`scalePriceType_1` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_1` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_1` char(1) NOT NULL default '',
	`scalePriceKeyword_1` varchar(255) NOT NULL default '',
	`scalePrice_1` text NULL,
	`lsShopVariantPriceOld_1` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceTypeOld_1` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useOldPrice_1` char(1) NOT NULL default '',

	`useGroupPrices_2` char(1) NOT NULL default '',
	`priceForGroups_2` blob NULL,
	`lsShopVariantPrice_2` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceType_2` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useScalePrice_2` char(1) NOT NULL default '',
	`scalePriceType_2` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_2` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_2` char(1) NOT NULL default '',
	`scalePriceKeyword_2` varchar(255) NOT NULL default '',
	`scalePrice_2` text NULL,
	`lsShopVariantPriceOld_2` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceTypeOld_2` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useOldPrice_2` char(1) NOT NULL default '',

	`useGroupPrices_3` char(1) NOT NULL default '',
	`priceForGroups_3` blob NULL,
	`lsShopVariantPrice_3` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceType_3` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useScalePrice_3` char(1) NOT NULL default '',
	`scalePriceType_3` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_3` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_3` char(1) NOT NULL default '',
	`scalePriceKeyword_3` varchar(255) NOT NULL default '',
	`scalePrice_3` text NULL,
	`lsShopVariantPriceOld_3` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceTypeOld_3` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useOldPrice_3` char(1) NOT NULL default '',

	`useGroupPrices_4` char(1) NOT NULL default '',
	`priceForGroups_4` blob NULL,
	`lsShopVariantPrice_4` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceType_4` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useScalePrice_4` char(1) NOT NULL default '',
	`scalePriceType_4` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_4` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_4` char(1) NOT NULL default '',
	`scalePriceKeyword_4` varchar(255) NOT NULL default '',
	`scalePrice_4` text NULL,
	`lsShopVariantPriceOld_4` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceTypeOld_4` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useOldPrice_4` char(1) NOT NULL default '',

	`useGroupPrices_5` char(1) NOT NULL default '',
	`priceForGroups_5` blob NULL,
	`lsShopVariantPrice_5` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceType_5` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useScalePrice_5` char(1) NOT NULL default '',
	`scalePriceType_5` varchar(255) NOT NULL default 'scalePriceStandalone',
	`scalePriceQuantityDetectionMethod_5` varchar(255) NOT NULL default 'separatedVariantsAndConfigurations',
	`scalePriceQuantityDetectionAlwaysSeparateConfigurations_5` char(1) NOT NULL default '',
	`scalePriceKeyword_5` varchar(255) NOT NULL default '',
	`scalePrice_5` text NULL,
	`lsShopVariantPriceOld_5` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantPriceTypeOld_5` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`useOldPrice_5` char(1) NOT NULL default '',

	`lsShopVariantWeight` decimal(12,4) NOT NULL default '0.0000',
	`lsShopVariantWeightType` varchar(255) NOT NULL default 'adjustmentPercentaged',
	`lsShopVariantCode` varchar(255) NOT NULL default '',
	`lsShopProductVariantMainImage` binary(16) NULL,
	`lsShopProductVariantMoreImages` blob NULL,
	`lsShopVariantDeliveryInfoSet` int(10) unsigned NOT NULL default '0',
	`lsShopVariantStock` decimal(12,4) NOT NULL default '0.0000',
	`configurator` int(10) unsigned NOT NULL default '0',
	`flex_contents` mediumtext NULL,
	`flex_contentsLanguageIndependent` mediumtext NULL,
	`lsShopVariantQuantityUnit` varchar(255) NOT NULL default '',
	`lsShopVariantMengenvergleichUnit` varchar(255) NOT NULL default '',
	`lsShopVariantMengenvergleichDivisor` decimal(12,6) NOT NULL default '0.000000',
	`associatedProducts` blob NULL
	PRIMARY KEY  (`id`),
	KEY `lsShopVariantCode` (`lsShopVariantCode`),
	KEY `alias` (`alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `tl_ls_shop_steuersaetze` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `steuerProzentPeriod1` text NULL,
  `startPeriod1` varchar(10) NOT NULL default '',
  `stopPeriod1` varchar(10) NOT NULL default '',
  `steuerProzentPeriod2` text NULL,
  `startPeriod2` varchar(10) NOT NULL default '',
  `stopPeriod2` varchar(10) NOT NULL default ''
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_ls_shop_cross_seller.productDirectSelection@tl_ls_shop_product.id=array@
*/
CREATE TABLE `tl_ls_shop_cross_seller` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`tstamp` int(10) unsigned NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`template` varchar(64) NOT NULL default '',
	`productSelectionType` varchar(255) NOT NULL default '',
	`maxNumProducts` int(10) unsigned NOT NULL default '0',
	`noOutputIfMoreThanMaxResults` char(1) NOT NULL default '',
	`productDirectSelection` blob NULL,
	`activateSearchSelectionNewProduct` char(1) NOT NULL default '',
	`searchSelectionNewProduct` varchar(64) NOT NULL default '',
	`activateSearchSelectionSpecialPrice` char(1) NOT NULL default '',
	`searchSelectionSpecialPrice` varchar(64) NOT NULL default '',
	`activateSearchSelectionCategory` char(1) NOT NULL default '',
	`searchSelectionCategory` blob NULL,
	`activateSearchSelectionProducer` char(1) NOT NULL default '',
	`searchSelectionProducer` varchar(255) NOT NULL default '',
	`activateSearchSelectionProductName` char(1) NOT NULL default '',
	`searchSelectionProductName` varchar(255) NOT NULL default '',
	`activateSearchSelectionArticleNr` char(1) NOT NULL default '',
	`searchSelectionArticleNr` varchar(255) NOT NULL default '',
	`activateSearchSelectionTags` char(1) NOT NULL default '',
	`searchSelectionTags` varchar(255) NOT NULL default '',
	`fallbackCrossSeller` int(10) unsigned NOT NULL default '0',
	`fallbackOutput` text NULL,
	`text01` text NULL,
	`text02` text NULL,
	`canBeFiltered` char(1) NOT NULL default '',
	`doNotUseCrossSellerOutputDefinitions` char(1) NOT NULL default '',
	`published` char(1) NOT NULL default ''
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_ls_shop_coupon.allowedForGroups@tl_member_group.id=array@
@tl_ls_shop_coupon.productDirectSelection@tl_ls_shop_product.id=array@
*/
CREATE TABLE `tl_ls_shop_coupon` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`tstamp` int(10) unsigned NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`description` text NULL,
	`productCode` varchar(255) NOT NULL default '',
	`couponCode` varchar(255) NOT NULL default '',
	`couponValue` decimal(10,2) NOT NULL default '0.00',
	`couponValueType` varchar(255) NOT NULL default '',
	`minimumOrderValue` decimal(10,2) NOT NULL default '0.00',
	`allowedForGroups` blob NULL,
	`published` char(1) NOT NULL default '',
	`start` varchar(10) NOT NULL default '',
	`stop` varchar(10) NOT NULL default '',
	`limitNumAvailable` char(1) NOT NULL default '',
	`numAvailable` int(10) unsigned NOT NULL default '0',
	`changeNumAvailable` varchar(10) NOT NULL default '',
	
	`productSelectionType` varchar(255) NOT NULL default '',
	`maxNumProducts` int(10) unsigned NOT NULL default '0',
	`productDirectSelection` blob NULL,
	`activateSearchSelectionNewProduct` char(1) NOT NULL default '',
	`searchSelectionNewProduct` varchar(64) NOT NULL default '',
	`activateSearchSelectionSpecialPrice` char(1) NOT NULL default '',
	`searchSelectionSpecialPrice` varchar(64) NOT NULL default '',
	`activateSearchSelectionCategory` char(1) NOT NULL default '',
	`searchSelectionCategory` blob NULL,
	`activateSearchSelectionProducer` char(1) NOT NULL default '',
	`searchSelectionProducer` varchar(255) NOT NULL default '',
	`activateSearchSelectionProductName` char(1) NOT NULL default '',
	`searchSelectionProductName` varchar(255) NOT NULL default '',
	`activateSearchSelectionArticleNr` char(1) NOT NULL default '',
	`searchSelectionArticleNr` varchar(255) NOT NULL default '',
	`activateSearchSelectionTags` char(1) NOT NULL default '',
	`searchSelectionTags` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_ls_shop_export.productDirectSelection@tl_ls_shop_product.id=array@
*/
CREATE TABLE `tl_ls_shop_export` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`tstamp` int(10) unsigned NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`template` varchar(64) NOT NULL default '',
  `customLogicFile` binary(16) NULL,
	`flex_parameters` text NULL,
	`dataSource` varchar(255) NOT NULL default '',
	`tableName` varchar(255) NOT NULL default '',
	`simulateGroup` int(10) unsigned NOT NULL default '0',
	`createProductObjects` char(1) NOT NULL default '',
	`productDirectSelection` blob NULL,
	`activateSearchSelectionNewProduct` char(1) NOT NULL default '',
	`searchSelectionNewProduct` varchar(64) NOT NULL default '',
	`activateSearchSelectionSpecialPrice` char(1) NOT NULL default '',
	`searchSelectionSpecialPrice` varchar(64) NOT NULL default '',
	`activateSearchSelectionCategory` char(1) NOT NULL default '',
	`searchSelectionCategory` blob NULL,
	`activateSearchSelectionProducer` char(1) NOT NULL default '',
	`searchSelectionProducer` varchar(255) NOT NULL default '',
	`activateSearchSelectionProductName` char(1) NOT NULL default '',
	`searchSelectionProductName` varchar(255) NOT NULL default '',
	`activateSearchSelectionArticleNr` char(1) NOT NULL default '',
	`searchSelectionArticleNr` varchar(255) NOT NULL default '',
	`activateSearchSelectionTags` char(1) NOT NULL default '',
	`searchSelectionTags` varchar(255) NOT NULL default '',
	`feedActive` char(1) NOT NULL default '',
	`feedName` varchar(255) NOT NULL default '',
	`feedPassword` varchar(255) NOT NULL default '',
	`feedContentType` varchar(255) NOT NULL default 'text/plain',
	`feedFileName` varchar(255) NOT NULL default '',
	`fileExportActive` char(1) NOT NULL default '',
	`fileName` varchar(255) NOT NULL default '',
	`folder` int(10) unsigned NOT NULL default '0',
	`appendToFile` char(1) NOT NULL default '',
	`changedWithinMinutes` int(10) unsigned NOT NULL default '0',
	`useSegmentedOutput` char(1) NOT NULL default '',
	`numberOfRecordsPerSegment` int(10) unsigned NOT NULL default '0',
	`finishSegmentedOutputWithExtraSegment` char(1) NOT NULL default '',
	`activateFilterByStatus01` char(1) NOT NULL default '',
	`activateFilterByStatus02` char(1) NOT NULL default '',
	`activateFilterByStatus03` char(1) NOT NULL default '',
	`activateFilterByStatus04` char(1) NOT NULL default '',
	`activateFilterByStatus05` char(1) NOT NULL default '',
	`filterByStatus01` blob NULL,
	`filterByStatus02` blob NULL,
	`filterByStatus03` blob NULL,
	`filterByStatus04` blob NULL,
	`filterByStatus05` blob NULL,
	`activateAutomaticChangeStatus01` char(1) NOT NULL default '',
	`activateAutomaticChangeStatus02` char(1) NOT NULL default '',
	`activateAutomaticChangeStatus03` char(1) NOT NULL default '',
	`activateAutomaticChangeStatus04` char(1) NOT NULL default '',
	`activateAutomaticChangeStatus05` char(1) NOT NULL default '',
	`automaticChangeStatus01` varchar(255) NOT NULL default '',
	`automaticChangeStatus02` varchar(255) NOT NULL default '',
	`automaticChangeStatus03` varchar(255) NOT NULL default '',
	`automaticChangeStatus04` varchar(255) NOT NULL default '',
	`automaticChangeStatus05` varchar(255) NOT NULL default '',
	`sendOrderMailsOnStatusChange` char(1) NOT NULL default '',
	`activateFilterByPaymentMethod` char(1) NOT NULL default '',
	`activateFilterByShippingMethod` char(1) NOT NULL default '',
	`filterByPaymentMethod` blob NULL,
	`filterByShippingMethod` blob NULL,
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*
@tl_ls_shop_configurator.form@tl_form.id=single@
*/
CREATE TABLE `tl_ls_shop_configurator` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `form` int(10) unsigned NOT NULL default '0',
  `template` varchar(64) NOT NULL default '',
  `startWithDataEntryMode` char(1) NOT NULL default '1',
  `stayInDataEntryMode` char(1) NOT NULL default '',
  `skipStandardFormValidation` char(1) NOT NULL default '',
  `customLogicFile` binary(16) NULL
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_attributes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `title` varchar(255) NOT NULL default ''
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_attribute_values` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `sorting` int(10) unsigned NOT NULL default '0',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `classForFilterFormField` varchar(255) NOT NULL default '',
  `importantFieldValue` char(1) NOT NULL default ''
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_filter_fields` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `priority` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `numItemsInReducedMode` int(10) unsigned NOT NULL default '0',
  `classForFilterFormField` varchar(255) NOT NULL default '',
  `filterFormFieldType` varchar(255) NOT NULL default '',
  `dataSource` varchar(255) NOT NULL default '',
  `sourceAttribute` int(10) unsigned NOT NULL default '0',
  `published` char(1) NOT NULL default '',
  `filterMode` varchar(64) NOT NULL default '',
  `makeFilterModeUserAdjustable` char(1) NOT NULL default '',
  `templateToUse` varchar(64) NOT NULL default 'template_formFilterField_standard'
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_filter_field_values` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `sorting` int(10) unsigned NOT NULL default '0',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `filterValue` varchar(255) NOT NULL default '',
  `classForFilterFormField` varchar(255) NOT NULL default '',
  `importantFieldValue` char(1) NOT NULL default ''
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_attribute_allocation` (
  `pid` int(10) unsigned NOT NULL default '0',
  `parentIsVariant` char(1) NOT NULL default '0',
  `attributeID` int(10) unsigned NOT NULL default '0',
  `attributeValueID` int(10) unsigned NOT NULL default '0',
  `sorting` int(10) unsigned NOT NULL default '0'
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_delivery_info` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `useStock` char(1) NOT NULL default '',
  `allowOrdersWithInsufficientStock` char(1) NOT NULL default '',
  `minimumStock` int(10) unsigned NOT NULL default '0',
  `alertWhenLowerThanMinimumStock` char(1) NOT NULL default '',
  `deliveryTimeMessageWithSufficientStock` varchar(255) NOT NULL default '',
  `deliveryTimeDaysWithSufficientStock` int(10) unsigned NOT NULL default '0',
  `deliveryTimeMessageWithInsufficientStock` varchar(255) NOT NULL default '',
  `deliveryTimeDaysWithInsufficientStock` int(10) unsigned NOT NULL default '0'
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_ls_shop_payment_methods.formAdditionalData@tl_form.id=single@
@tl_ls_shop_payment_methods.excludedGroups@tl_member_group.id=array@
@tl_ls_shop_payment_methods.steuersatz@tl_ls_shop_steuersaetze.id=single@
@tl_ls_shop_payment_methods.paypalSecondForm@tl_form.id=single@
@tl_ls_shop_payment_methods.paypalGiropayRedirectForm@tl_form.id=single@
@tl_ls_shop_payment_methods.paypalGiropaySuccessPages@tl_page.id=array@
@tl_ls_shop_payment_methods.paypalGiropayCancelPages@tl_page.id=array@
@tl_ls_shop_payment_methods.paypalBanktransferPendingPages@tl_page.id=array@
*/
CREATE TABLE `tl_ls_shop_payment_methods` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `sorting` int(10) unsigned NOT NULL default '0',
  `description` text NULL,
  `infoAfterCheckout` text NULL,
  `additionalInfo` text NULL,
  `formAdditionalData` int(10) unsigned NOT NULL default '0',
  `type` varchar(255) NOT NULL default 'standard',
  `feeType` varchar(255) NOT NULL default 'none',
  `feeValue` decimal(12,4) NOT NULL default '0.0000',
  `feeAddCouponToValueOfGoods` char(1) NOT NULL default '',
  `feeAddShippingToValueOfGoods` char(1) NOT NULL default '',
  `feeFormula` text NULL,
  `feeFormulaResultConvertToDisplayPrice` char(1) NOT NULL default '',
  `feeWeightValues` text NULL,
  `feePriceValues` text NULL,
  `excludedGroups` blob NULL,
  `dynamicSteuersatzType` varchar(255) NOT NULL default 'none',
  `steuersatz` int(10) unsigned NOT NULL default '0',
  `published` char(1) NOT NULL default '',
  `weightLimitMin` decimal(12,4) NOT NULL default '0.0000',
  `weightLimitMax` decimal(12,4) NOT NULL default '0.0000',
  `priceLimitMin` decimal(12,4) NOT NULL default '0.0000',
  `priceLimitMax` decimal(12,4) NOT NULL default '0.0000',
  `priceLimitAddCouponToValueOfGoods` char(1) NOT NULL default '',
  `priceLimitAddShippingToValueOfGoods` char(1) NOT NULL default '',
  `countriesAsBlacklist` char(1) NOT NULL default '',
  `countries` text NULL,
  `cssID` varchar(255) NOT NULL default '',
  `cssClass` varchar(255) NOT NULL default '',
  `paypalAPIUsername` varchar(255) NOT NULL default '',
  `paypalAPIPassword` varchar(255) NOT NULL default '',
  `paypalAPISignature` varchar(255) NOT NULL default '',
  `paypalShipToFieldNameFirstname` varchar(255) NOT NULL default '',
  `paypalShipToFieldNameLastname` varchar(255) NOT NULL default '',
  `paypalShipToFieldNameStreet` varchar(255) NOT NULL default '',
  `paypalShipToFieldNameCity` varchar(255) NOT NULL default '',
  `paypalShipToFieldNamePostal` varchar(255) NOT NULL default '',
  `paypalShipToFieldNameState` varchar(255) NOT NULL default '',
  `paypalShipToFieldNameCountryCode` varchar(255) NOT NULL default '',
  `paypalLiveMode` char(1) NOT NULL default '',
  `paypalShowItems` char(1) NOT NULL default '',
  `paypalSecondForm` int(10) unsigned NOT NULL default '0',
  `paypalGiropayRedirectForm` int(10) unsigned NOT NULL default '0',
  `paypalGiropaySuccessPages` blob NULL,
  `paypalGiropayCancelPages` blob NULL,
  `paypalBanktransferPendingPages` blob NULL,
  `sofortueberweisungConfigkey` varchar(255) NOT NULL default '',
  `sofortueberweisungUseCustomerProtection` char(1) NOT NULL default '',
  `santanderWebQuickVendorNumber` varchar(255) NOT NULL default '',
  `santanderWebQuickVendorPassword` varchar(255) NOT NULL default '',
  `santanderWebQuickLiveMode` char(1) NOT NULL default '',
  `santanderWebQuickMinAge` int(10) unsigned NOT NULL default '18',
  `santanderWebQuickFieldNameSalutation` varchar(255) NOT NULL default '',
  `santanderWebQuickFieldNameFirstName` varchar(255) NOT NULL default '',
  `santanderWebQuickFieldNameLastName` varchar(255) NOT NULL default '',
  `santanderWebQuickFieldNameEmailAddress` varchar(255) NOT NULL default '',
  `santanderWebQuickFieldNameStreet` varchar(255) NOT NULL default '',
  `santanderWebQuickFieldNameCity` varchar(255) NOT NULL default '',
  `santanderWebQuickFieldNameZipCode` varchar(255) NOT NULL default '',
  `santanderWebQuickFieldNameCountry` varchar(255) NOT NULL default '',
  `payPalPlus_clientID` varchar(255) NOT NULL default '',
  `payPalPlus_clientSecret` varchar(255) NOT NULL default '',
  `payPalPlus_liveMode` char(1) NOT NULL default '',
  `payPalPlus_logMode` varchar(255) NOT NULL default 'NONE',
  `payPalPlus_shipToFieldNameFirstname` varchar(255) NOT NULL default '',
  `payPalPlus_shipToFieldNameLastname` varchar(255) NOT NULL default '',
  `payPalPlus_shipToFieldNameStreet` varchar(255) NOT NULL default '',
  `payPalPlus_shipToFieldNameCity` varchar(255) NOT NULL default '',
  `payPalPlus_shipToFieldNamePostal` varchar(255) NOT NULL default '',
  `payPalPlus_shipToFieldNameState` varchar(255) NOT NULL default '',
  `payPalPlus_shipToFieldNameCountryCode` varchar(255) NOT NULL default '',
  `payPalPlus_shipToFieldNamePhone` varchar(255) NOT NULL default '',
  `payone_subaccountId` varchar(255) NOT NULL default '',
  `payone_portalId` varchar(255) NOT NULL default '',
  `payone_key` varchar(255) NOT NULL default '',
  `payone_liveMode` char(1) NOT NULL default '',
  `payone_clearingtype` varchar(16) NOT NULL default '',
  `payone_fieldNameFirstname` varchar(255) NOT NULL default '',
  `payone_fieldNameLastname` varchar(255) NOT NULL default '',
  `payone_fieldNameCompany` varchar(255) NOT NULL default '',
  `payone_fieldNameStreet` varchar(255) NOT NULL default '',
  `payone_fieldNameAddressaddition` varchar(255) NOT NULL default '',
  `payone_fieldNameZip` varchar(255) NOT NULL default '',
  `payone_fieldNameCity` varchar(255) NOT NULL default '',
  `payone_fieldNameCountry` varchar(255) NOT NULL default '',
  `payone_fieldNameEmail` varchar(255) NOT NULL default '',
  `payone_fieldNameTelephonenumber` varchar(255) NOT NULL default '',
  `payone_fieldNameBirthday` varchar(255) NOT NULL default '',
  `payone_fieldNameGender` varchar(255) NOT NULL default '',
  `payone_fieldNamePersonalid` varchar(255) NOT NULL default '',
  `saferpay_username` varchar(255) NOT NULL default '',
  `saferpay_password` varchar(255) NOT NULL default '',
  `saferpay_customerId` varchar(255) NOT NULL default '',
  `saferpay_terminalId` varchar(255) NOT NULL default '',
  `saferpay_merchantEmail` varchar(255) NOT NULL default '',
  `saferpay_liveMode` char(1) NOT NULL default '',
  `saferpay_captureInstantly` char(1) NOT NULL default '',
  `saferpay_paymentMethods` blob NULL,
  `saferpay_wallets` blob NULL,
  `vrpay_userId` varchar(255) NOT NULL default '',
  `vrpay_password` varchar(255) NOT NULL default '',
  `vrpay_token` varchar(255) NOT NULL default '',	
  `vrpay_entityId` varchar(255) NOT NULL default '',
  `vrpay_liveMode` char(1) NOT NULL default '',
  `vrpay_testMode` varchar(8) NOT NULL default '',
  `vrpay_paymentInstrument` varchar(32) NOT NULL default '',
  `vrpay_creditCardBrands` blob NULL,
  `vrpay_fieldName_street1` varchar(255) NOT NULL default '',
  `vrpay_fieldName_city` varchar(255) NOT NULL default '',
  `vrpay_fieldName_postcode` varchar(255) NOT NULL default '',
  `vrpay_fieldName_country` varchar(255) NOT NULL default '',
  `vrpay_fieldName_givenName` varchar(255) NOT NULL default '',
  `vrpay_fieldName_surname` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_orders` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `orderIdentificationHash` varchar(255) NOT NULL default '',
  `orderNr` varchar(255) NOT NULL default '',
  `orderDateUnixTimestamp` int(10) unsigned NOT NULL default '0',
  `orderDate` varchar(32) NOT NULL default '',
  `customerNr` varchar(32) NOT NULL default '',
  `customerLanguage` varchar(2) NOT NULL default '',
  `firstname` varchar(255) NOT NULL default '',
  `lastname` varchar(255) NOT NULL default '',
  `personalDataReview` blob NULL,
  `personalDataReview_customerLanguage` blob NULL,
  `paymentDataReview` blob NULL,
  `paymentDataReview_customerLanguage` blob NULL,
  `shippingDataReview` blob NULL,
  `shippingDataReview_customerLanguage` blob NULL,
  `memberGroupInfo_id` int(10) unsigned NOT NULL default '0',
  `memberGroupInfo_name` varchar(255) NOT NULL default '',
  `currency` varchar(3) NOT NULL default '',
  `weightUnit` varchar(10) NOT NULL default '',
  `userOutputPriceType` varchar(10) NOT NULL default '',
  `inputPriceType` varchar(10) NOT NULL default '',
  `numDecimalsPrice` int(10) unsigned NOT NULL default '0',
  `numDecimalsWeight` int(10) unsigned NOT NULL default '0',
  `decimalsSeparator` varchar(1) NOT NULL default '',
  `thousandsSeparator` varchar(1) NOT NULL default '',
  `totalValueOfGoods` decimal(12,4) NOT NULL default '0.0000',
  `totalValueOfGoodsTaxedWith` blob NULL,
  `noVATBecauseOfEnteredIDs` char(1) NOT NULL default '',
  `VATIDValidationResult` text NULL,
  `totalWeightOfGoods` decimal(12,4) NOT NULL default '0.0000',
  `couponsUsed` blob NULL,
  `couponsTotalValue` decimal(12,4) NOT NULL default '0.0000',
  `paymentMethod_title` varchar(255) NOT NULL default '',
  `paymentMethod_title_customerLanguage` varchar(255) NOT NULL default '',
  `paymentMethod_infoAfterCheckout` text NULL,
  `paymentMethod_infoAfterCheckout_customerLanguage` text NULL,
  `paymentMethod_additionalInfo` text NULL,
  `paymentMethod_additionalInfo_customerLanguage` text NULL,
  `paymentMethod_id` int(10) unsigned NOT NULL default '0',
  `paymentMethod_alias` varchar(255) NOT NULL default '',
  `paymentMethod_feeInfo_customerLanguage` blob NULL,
  `paymentMethod_moduleReturnData` blob NULL,
  `paymentMethod_amount` decimal(12,4) NOT NULL default '0.0000',
  `paymentMethod_amountTaxedWith` blob NULL,
  `shippingMethod_title` varchar(255) NOT NULL default '',
  `shippingMethod_title_customerLanguage` varchar(255) NOT NULL default '',
  `shippingMethod_infoAfterCheckout` text NULL,
  `shippingMethod_infoAfterCheckout_customerLanguage` text NULL,
  `shippingMethod_additionalInfo` text NULL,
  `shippingMethod_additionalInfo_customerLanguage` text NULL,
  `shippingMethod_id` int(10) unsigned NOT NULL default '0',
  `shippingMethod_alias` varchar(255) NOT NULL default '',
  `shippingMethod_feeInfo_customerLanguage` blob NULL,
  `shippingMethod_moduleReturnData` blob NULL,
  `shippingMethod_amount` decimal(12,4) NOT NULL default '0.0000',
  `shippingMethod_amountTaxedWith` blob NULL,
  `total` decimal(12,4) NOT NULL default '0.0000',
  `totalTaxedWith` blob NULL,
  `taxTotal` decimal(12,4) NOT NULL default '0.0000',
  `tax` blob NULL,
  `taxInclusive` char(1) NOT NULL default '',
  `invoicedAmount` decimal(12,4) NOT NULL default '0.0000',
  `invoicedAmountNet` decimal(12,4) NOT NULL default '0.0000',
  `status` varchar(255) NOT NULL default '',
  `status01` varchar(255) NOT NULL default '',
  `status02` varchar(255) NOT NULL default '',
  `status03` varchar(255) NOT NULL default '',
  `status04` varchar(255) NOT NULL default '',
  `status05` varchar(255) NOT NULL default '',

  `shippingTrackingNr` varchar(255) NOT NULL default '',
  `shippingTrackingUrl` varchar(255) NOT NULL default '',

  `notesShort` varchar(32) NOT NULL default '',
  `notesLong` text NULL,
  `freetext` text NULL,

  `miscData` blob NULL,

  `payPalPlus_saleId` varchar(255) NOT NULL default '',
  `payPalPlus_currentStatus` varchar(255) NOT NULL default '',

  `payone_currentStatus` varchar(255) NOT NULL default '',

  `saferpay_currentStatus` varchar(255) NOT NULL default '',

  `vrpay_currentStatus` varchar(255) NOT NULL default '',

  `sofortbanking_currentStatus` varchar(255) NOT NULL default ''
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_orders_customer_data` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `dataType` varchar(255) NOT NULL default '',
  `fieldName` varchar(255) NOT NULL default '',
  `fieldValue` text NULL
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_orders_items` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `itemPosition` int(10) unsigned NOT NULL default '0',
  `productVariantID` varchar(255) NOT NULL default '',
  `productCartKey` varchar(255) NOT NULL default '',
  `price` decimal(12,4) NOT NULL default '0.0000',
  `weight` decimal(12,4) NOT NULL default '0.0000',
  `quantity` decimal(12,4) NOT NULL default '0.0000',
  `priceCumulative` decimal(12,4) NOT NULL default '0.0000',
  `weightCumulative` decimal(12,4) NOT NULL default '0.0000',
  `taxClass` int(10) unsigned NOT NULL default '0',
  `taxPercentage` decimal(12,4) NOT NULL default '0.0000',
  `isVariant` char(1) NOT NULL default '',
  `artNr` varchar(255) NOT NULL default '',
  `productTitle` varchar(255) NOT NULL default '',
  `variantTitle` varchar(255) NOT NULL default '',
  `quantityUnit` varchar(255) NOT NULL default '',
  `quantityDecimals` int(10) unsigned NOT NULL default '0',
  `configurator_merchantRepresentation` blob NULL,
  `configurator_cartRepresentation` blob NULL,
  `configurator_hasValue` char(1) NOT NULL default '',
  `configurator_referenceNumber` varchar(255) NOT NULL default '',
  `extendedInfo` blob NULL
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_output_definitions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `lsShopProductTemplate` varchar(255) NOT NULL default '',
  `lsShopProductOverviewSorting` varchar(255) NOT NULL default '',
  `lsShopProductOverviewSortingKeyOrAlias` varchar(255) NOT NULL default '',
  `lsShopProductOverviewUserSorting` varchar(255) NOT NULL default '',
  `lsShopProductOverviewUserSortingFields` text NULL,
  `lsShopProductOverviewPagination` int(3) unsigned NOT NULL default '0',
  `lsShopProductTemplate_crossSeller` varchar(255) NOT NULL default '',
  `lsShopProductOverviewSorting_crossSeller` varchar(255) NOT NULL default '',
  `lsShopProductOverviewSortingKeyOrAlias_crossSeller` varchar(255) NOT NULL default '',
  `lsShopProductOverviewUserSorting_crossSeller` varchar(255) NOT NULL default '',
  `lsShopProductOverviewUserSortingFields_crossSeller` text NULL,
  `lsShopProductOverviewPagination_crossSeller` int(3) unsigned NOT NULL default '0'
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_ls_shop_shipping_methods.formAdditionalData@tl_form.id=single@
*/
CREATE TABLE `tl_ls_shop_shipping_methods` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
  `sorting` int(10) unsigned NOT NULL default '0',
  `description` text NULL,
  `infoAfterCheckout` text NULL,
  `additionalInfo` text NULL,
  `formAdditionalData` int(10) unsigned NOT NULL default '0',
  `type` varchar(255) NOT NULL default 'standard',
  `feeType` varchar(255) NOT NULL default 'none',
  `feeValue` decimal(12,4) NOT NULL default '0.0000',
  `feeAddCouponToValueOfGoods` char(1) NOT NULL default '',
  `feeFormula` text NULL,
  `feeFormulaResultConvertToDisplayPrice` char(1) NOT NULL default '',
  `feeWeightValues` text NULL,
  `feePriceValues` text NULL,
  `excludedGroups` blob NULL,
  `dynamicSteuersatzType` varchar(255) NOT NULL default 'none',
  `steuersatz` int(10) unsigned NOT NULL default '0',
  `published` char(1) NOT NULL default '',
  `weightLimitMin` decimal(12,4) NOT NULL default '0.0000',
  `weightLimitMax` decimal(12,4) NOT NULL default '0.0000',
  `priceLimitMin` decimal(12,4) NOT NULL default '0.0000',
  `priceLimitMax` decimal(12,4) NOT NULL default '0.0000',
  `priceLimitAddCouponToValueOfGoods` char(1) NOT NULL default '',
  `countriesAsBlacklist` char(1) NOT NULL default '',
  `countries` text NULL,
  `cssID` varchar(255) NOT NULL default '',
  `cssClass` varchar(255) NOT NULL default ''
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE `tl_ls_shop_import` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`pid` int(10) unsigned NOT NULL default '0',
	`tstamp` int(10) unsigned NOT NULL default '0'
	`importInfo` varchar(255) NOT NULL default ''
	PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;









CREATE TABLE `tl_ls_shop_message_type` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`pid` int(10) unsigned NOT NULL default '0',
	`alias` varchar(128) COLLATE utf8_bin NOT NULL default '',
	`tstamp` int(10) unsigned NOT NULL default '0',
	`lastDispatchDateUnixTimestamp` int(10) unsigned NOT NULL default '0',
	`useCounter` char(1) NOT NULL default '',
	`counterString` varchar(255) NOT NULL default '',
	`counterStart` int(10) unsigned NOT NULL default '0',
	`counterRestartCycle` varchar(255) NOT NULL default '',
	`counterRestartNow` char(1) NOT NULL default '',
	`counter` int(10) unsigned NOT NULL default '0',
	`sendWhen` varchar(255) NOT NULL default '',
	`useStatusCorrelation01` char(1) NOT NULL default '',
	`useStatusCorrelation02` char(1) NOT NULL default '',
	`useStatusCorrelation03` char(1) NOT NULL default '',
	`useStatusCorrelation04` char(1) NOT NULL default '',
	`useStatusCorrelation05` char(1) NOT NULL default '',
	`usePaymentStatusCorrelation` char(1) NOT NULL default '',
	`statusCorrelation01` varchar(255) NOT NULL default '',
	`statusCorrelation02` varchar(255) NOT NULL default '',
	`statusCorrelation03` varchar(255) NOT NULL default '',
	`statusCorrelation04` varchar(255) NOT NULL default '',
	`statusCorrelation05` varchar(255) NOT NULL default '',
	`paymentStatusCorrelation_paymentProvider` varchar(255) NOT NULL default '',
	`paymentStatusCorrelation_statusValue` varchar(255) NOT NULL default '',
	`title` varchar(255) NOT NULL default ''
	PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_ls_shop_message_model.pid@tl_ls_shop_message_type.id=single@
@tl_ls_shop_message_model.member_group@tl_member_group.id=array@
*/
CREATE TABLE `tl_ls_shop_message_model` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`pid` int(10) unsigned NOT NULL default '0',
	`tstamp` int(10) unsigned NOT NULL default '0',
	`member_group` blob NULL,
	`subject` varchar(255) NOT NULL default '',
	`senderName` varchar(255) NOT NULL default '',
	`senderAddress` varchar(255) NOT NULL default '',
	`sendToCustomerAddress1` char(1) NOT NULL default '',
	`customerDataType1` varchar(255) NOT NULL default '',
	`customerDataField1` varchar(255) NOT NULL default '',
	`sendToCustomerAddress2` char(1) NOT NULL default '',
	`customerDataType2` varchar(255) NOT NULL default '',
	`customerDataField2` varchar(255) NOT NULL default '',
	`sendToSpecificAddress` char(1) NOT NULL default '',
	`specificAddress` varchar(255) NOT NULL default '',
	`sendToMemberAddress` char(1) NOT NULL default '',
	`useHTML` char(1) NOT NULL default '',
	`content_html` text NULL,
	`template_html` varchar(64) NOT NULL default '',
	`useRawtext` char(1) NOT NULL default '',
	`content_rawtext` text NULL,
	`template_rawtext` varchar(64) NOT NULL default '',
	`attachments` blob NULL,
	`dynamicAttachments` blob NULL,
	`published` char(1) NOT NULL default '',
	`externalImages` char(1) NOT NULL default ''
	PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_messages_sent` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`tstamp` int(10) unsigned NOT NULL default '0',
	`orderID` int(10) unsigned NOT NULL default '0',
	`orderNr` varchar(255) NULL,
	`messageTypeAlias` varbinary(128) NOT NULL default '',
	`messageTypeID` int(10) unsigned NOT NULL default '0',
	`messageModelID` int(10) unsigned NOT NULL default '0',
	`counterNr` varchar(255) NULL,
	
	`senderName` varchar(255) NOT NULL default '',
	`senderAddress` varchar(255) NOT NULL default '',
	`receiverMainAddress` varchar(255) NOT NULL default '',
	`receiverBccAddress` varchar(255) NULL,
	
	`subject` varchar(255) NOT NULL default '',
	`bodyHTML` text NULL,
	`bodyRawtext` text NULL,
	
	`dynamicPdfAttachmentPaths` blob NULL,
	`attachmentPaths` blob NULL
	PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_singular_storage` (
  `key` varchar(255) NOT NULL default '',
  `int_value` int(10) unsigned NOT NULL default '0',
  `float_value` decimal(12,4) NOT NULL default '0.0000',
  `str_value` text NULL,
  `bln_value` char(1) NOT NULL default '',
  `arr_value` blob NULL
  PRIMARY KEY  (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_ls_shop_restock_info_list` (
    `productVariantId` varchar(64) NOT NULL default '',
	`productId` int(10) unsigned NOT NULL default '0',
	`variantId` int(10) unsigned NOT NULL default '0',
	`memberId` int(10) unsigned NOT NULL default '0',
	`language` varchar(8) NOT NULL default ''
	KEY `productVariantId` (`productVariantId`),
	KEY `productId` (`productId`),
	KEY `variantId` (`variantId`),
	KEY `memberId` (`memberId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;











/*
@tl_module.ls_shop_cross_seller@tl_ls_shop_cross_seller.id=single@
@tl_module.jumpTo@tl_page.id=single@
@tl_module.pages@tl_page.id=array@
@tl_module.orderPages@tl_page.id=array@
@tl_module.reg_jumpTo@tl_page.id=single@
@tl_module.ls_shop_productManagementApiInspector_apiPage@tl_page.id=single@
*/
CREATE TABLE `tl_module` (
  `ls_shop_cart_template` varchar(64) NOT NULL default '',
  `ls_shop_orderReview_template` varchar(64) NOT NULL default '',
  `ls_shop_afterCheckout_template` varchar(64) NOT NULL default '',
  `ls_shop_paymentAfterCheckout_template` varchar(64) NOT NULL default '',
  `ls_shop_myOrders_template` varchar(64) NOT NULL default '',
  `ls_shop_myOrders_sortingOptions` blob NULL,
  `ls_shop_myOrderDetails_template` varchar(64) NOT NULL default '',
  `ls_shop_filterForm_template` varchar(64) NOT NULL default '',
  `ls_shop_filterSummary_template` varchar(64) NOT NULL default '',
  `ls_shop_productSearch_template` varchar(64) NOT NULL default '',
  `ls_shop_productSearch_minlengthInput` int(10) unsigned NOT NULL default '0',
  `ls_shop_cross_seller` int(10) unsigned NOT NULL default '0',
  `ls_shop_productManagementApiInspector_apiPage` int(10) unsigned NOT NULL default '0',
  `ls_shop_productOverviewShowProductsFromSubordinatePages` char(1) NOT NULL default '',
  `ls_shop_productOverviewConsiderUnpublishedPages` char(1) NOT NULL default '',
  `ls_shop_productOverviewConsiderHiddenPages` char(1) NOT NULL default '',
  `ls_shop_productOverviewStartLevel` smallint(5) unsigned NOT NULL default '0',
  `ls_shop_productOverviewStopLevel` smallint(5) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_member_group.lsShopFormCustomerData@tl_form.id=single@
@tl_member_group.lsShopFormConfirmOrder@tl_form.id=single@
@tl_member_group.lsShopStandardPaymentMethod@tl_ls_shop_payment_methods.id=single@
@tl_member_group.lsShopStandardShippingMethod@tl_ls_shop_shipping_methods.id=single@
*/
CREATE TABLE `tl_member_group` (
  `lsShopOutputPriceType` varchar(32) NOT NULL default 'brutto',
  `lsShopPriceAdjustment` decimal(10,2) NOT NULL default '0.00',
  `lsShopMinimumOrderValue` decimal(10,2) NOT NULL default '0.00',
  `lsShopMinimumOrderValueAddCouponToValueOfGoods` char(1) NOT NULL default '',
  `lsShopFormCustomerData` int(10) unsigned NOT NULL default '0',
  `lsShopFormConfirmOrder` int(10) unsigned NOT NULL default '0',
  `lsShopStandardPaymentMethod` int(10) unsigned NOT NULL default '0',
  `lsShopStandardShippingMethod` int(10) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_member` (
  `VATID` varchar(255) NOT NULL default '',
  `firstname_alternative` varchar(255) NOT NULL default '',
  `lastname_alternative` varchar(255) NOT NULL default '',
  `company_alternative` varchar(255) NOT NULL default '',
  `street_alternative` varchar(255) NOT NULL default '',
  `postal_alternative` varchar(32) NOT NULL default '',
  `city_alternative` varchar(255) NOT NULL default '',
  `state_alternative` varchar(64) NOT NULL default '',
  `country_alternative` varchar(2) NOT NULL default '',
  `phone_alternative` varchar(64) NOT NULL default '',
  `mobile_alternative` varchar(64) NOT NULL default '',
  `fax_alternative` varchar(64) NOT NULL default '',
  `email_alternative` varchar(255) NOT NULL default '',
  `merconis_favoriteProducts` blob NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_form_field` (
  `lsShop_mandatoryOnConditionField` int(10) unsigned NOT NULL default '0',
  `lsShop_mandatoryOnConditionValue` varchar(255) NOT NULL default '',
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_content.lsShopCrossSeller@tl_ls_shop_cross_seller.id=single@
*/
CREATE TABLE `tl_content` (
	`lsShopCrossSeller` int(10) unsigned NOT NULL default '0',
	`lsShopOutputCondition` varchar(32) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_article` (
	`lsShopOutputCondition` varchar(32) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_page.lsShopOutputDefinitionSet@tl_ls_shop_output_definitions.id=single@
@tl_page.lsShopLayoutForDetailsView@tl_layout.id=single@
@tl_page.pid@tl_page.id=single@
*/
CREATE TABLE `tl_page` (
	`lsShopOutputDefinitionSet` int(10) unsigned NOT NULL default '0',
	`lsShopIncludeLayoutForDetailsView` char(1) NOT NULL default '',
	`lsShopLayoutForDetailsView` int(10) unsigned NOT NULL default '0',
	`ls_shop_currencyBeforeValue` char(1) NOT NULL default '',
	`ls_shop_decimalsSeparator` char(1) NOT NULL default ',',
	`ls_shop_thousandsSeparator` char(1) NOT NULL default '',
  `ls_shop_useAsCategoryForErp` char(1) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
@tl_layout.lsShopOutputDefinitionSet@tl_ls_shop_output_definitions.id=single@
*/
CREATE TABLE `tl_layout` (
  `lsShopOutputDefinitionSet` int(10) unsigned NOT NULL default '0',
  `ls_shop_activateFilter` char(1) NOT NULL default '',
  `ls_shop_useFilterInStandardProductlist` char(1) NOT NULL default '',
  `ls_shop_useFilterMatchEstimates` char(1) NOT NULL default '',
  `ls_shop_useFilterInProductDetails` char(1) NOT NULL default '',
  `ls_shop_hideFilterFormInProductDetails` char(1) NOT NULL default '',
  `ls_shop_matchEstimatesMaxNumProducts` int(10) unsigned NOT NULL default '0',
  `ls_shop_matchEstimatesMaxFilterValues` int(10) unsigned NOT NULL default '0',
  `ls_shop_numFilterFieldsInSummary` smallint(5) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tl_user` (
  `lsShopBeOrderTemplateOverview` varchar(64) NOT NULL default '',
  `lsShopBeOrderTemplateDetails` varchar(64) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
