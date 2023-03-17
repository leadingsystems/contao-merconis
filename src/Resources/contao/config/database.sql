/*
relevant foreign key relations concerning tables which are not defined in this file:

@tl_article.pid@tl_page.id=single@
@tl_content.pid@tl_article.id=single@
@tl_content.form@tl_form.id=single@
@tl_content.module@tl_module.id=single@
@tl_content.size@tl_image_size.id=array@
@tl_form_field.pid@tl_form.id=single@
@tl_form_field.lsShop_mandatoryOnConditionField@tl_form_field.id=single@
@tl_form_field.lsShop_mandatoryOnConditionField2@tl_form_field.id=single@
@tl_form_field.lsShop_ShowOnConditionField@tl_form_field.id=single@
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
@tl_news_archive.jumpTo@tl_page.id=single@
@tl_image_size.pid@tl_theme.id=single@
@tl_image_size_item.pid@tl_image_size.id=single@


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


@tl_ls_shop_product.pages@tl_page.id=array@
@tl_ls_shop_product.lsShopProductSteuersatz@tl_ls_shop_steuersaetze.id=single@
@tl_ls_shop_product.lsShopProductRecommendedProducts@tl_ls_shop_product.id=array@
@tl_ls_shop_product.associatedProducts@tl_ls_shop_product.id=array@
@tl_ls_shop_product.lsShopProductDeliveryInfoSet@tl_ls_shop_delivery_info.id=single@
@tl_ls_shop_product.configurator@tl_ls_shop_configurator.id=single@


@tl_ls_shop_variant.pid@tl_ls_shop_product.id=single@
@tl_ls_shop_variant.associatedProducts@tl_ls_shop_product.id=array@
@tl_ls_shop_variant.lsShopVariantDeliveryInfoSet@tl_ls_shop_delivery_info.id=single@
@tl_ls_shop_variant.configurator@tl_ls_shop_configurator.id=single@


@tl_ls_shop_cross_seller.productDirectSelection@tl_ls_shop_product.id=array@


@tl_ls_shop_coupon.allowedForGroups@tl_member_group.id=array@
@tl_ls_shop_coupon.productDirectSelection@tl_ls_shop_product.id=array@


@tl_ls_shop_export.productDirectSelection@tl_ls_shop_product.id=array@


@tl_ls_shop_configurator.form@tl_form.id=single@


@tl_ls_shop_payment_methods.formAdditionalData@tl_form.id=single@
@tl_ls_shop_payment_methods.excludedGroups@tl_member_group.id=array@
@tl_ls_shop_payment_methods.steuersatz@tl_ls_shop_steuersaetze.id=single@
@tl_ls_shop_payment_methods.paypalSecondForm@tl_form.id=single@
@tl_ls_shop_payment_methods.paypalGiropayRedirectForm@tl_form.id=single@
@tl_ls_shop_payment_methods.paypalGiropaySuccessPages@tl_page.id=array@
@tl_ls_shop_payment_methods.paypalGiropayCancelPages@tl_page.id=array@
@tl_ls_shop_payment_methods.paypalBanktransferPendingPages@tl_page.id=array@


@tl_ls_shop_shipping_methods.formAdditionalData@tl_form.id=single@


@tl_ls_shop_message_model.pid@tl_ls_shop_message_type.id=single@
@tl_ls_shop_message_model.member_group@tl_member_group.id=array@


@tl_module.ls_shop_cross_seller@tl_ls_shop_cross_seller.id=single@
@tl_module.jumpTo@tl_page.id=single@
@tl_module.pages@tl_page.id=array@
@tl_module.reg_jumpTo@tl_page.id=single@
@tl_module.ls_shop_productManagementApiInspector_apiPage@tl_page.id=single@


@tl_member_group.lsShopFormCustomerData@tl_form.id=single@
@tl_member_group.lsShopFormConfirmOrder@tl_form.id=single@
@tl_member_group.lsShopStandardPaymentMethod@tl_ls_shop_payment_methods.id=single@
@tl_member_group.lsShopStandardShippingMethod@tl_ls_shop_shipping_methods.id=single@


@tl_content.lsShopCrossSeller@tl_ls_shop_cross_seller.id=single@


@tl_page.lsShopOutputDefinitionSet@tl_ls_shop_output_definitions.id=single@
@tl_page.lsShopLayoutForDetailsView@tl_layout.id=single@
@tl_page.pid@tl_page.id=single@


@tl_layout.lsShopOutputDefinitionSet@tl_ls_shop_output_definitions.id=single@
*/

