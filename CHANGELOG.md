Leading Systems Contao Merconis bundle changelog
===========================================

### 5.0.31 (2025-01-15)
 * increase number of importable attributes and values becouse of MPM v1.64

### 5.0.30 (2024-12-20)
 * improvement of the product detail view without active page assignment
 * improve PayPal Checkout logging

### 5.0.29 (2024-11-14)
 * fix sorting functionality for FlexContents
 * fix ls_shop_generalHelper::getAttributesAndValuesCurrentlyInUse()
 * fix partial quantity put in cart
 * fix PayPal Checkout unnecessary api request
 * add hook 'manipulateLiveHit'
 * fix rangefilter for flexcontents and attributes/properties

### 5.0.28 (2024-09-26)
 * improve PayPal Checkout error handling

### 5.0.27 (2024-09-11)
 * fix scale price quantity detection

### 5.0.26 (2024-09-06)
 * improve delivery insert tags
 * improve filter field settings for single attributes values
 * fix minimum goods value has been reached

### 5.0.25 (2024-07-31)
 * improve trigger for ls_shop_msg::decreaseLifetime()

### 5.0.24 (2024-07-19)
 * improve accessibility of customizerStorage

### 5.0.23 (2024-06-11)
 * add flex content filters
 * add range filter for attributes

### 5.0.22 (2024-05-27)
 * add hook 'getProductTaxClass'
 * add hook 'addToCartCustomLogic'

### 5.0.21 (2024-03-14)
 * fix PAYONE for PHP 8
 * fix price overview for variants
 * fix PayPal Checkout calculation (negativ value)
 * fix remove script tag if no lsjs

### 5.0.20 (2023-12-11)
 * add enableVersioning
 * replace TL_CRON with service contao.cronjob 
 * remove deprecated constants
 * remove deprecated functions
 * improve cache lsjs4c & lscss4c

### 5.0.19 (2023-09-01)
 * remove deprecated FE_USER_LOGGED_IN
 * add loginListener & sitemapListener, remove old hook
 * fix static path for lsjs to dynamic path

### 5.0.18 (2023-07-21)
 * Fix PayPal Checkout calculation (tax)

### 5.0.17 (2023-07-14)
 * Fix frontend login

### 5.0.16 (2023-05-30)
 * Add noshipping option
 * Fix blank option on ShowOnConditionField
 * Improve styling issues

### 5.0.15 (2023-05-26)
 * Add language variables: countries, province_us, province_ca and province_it

### 5.0.14 (2023-05-12)
 * Improve coupon: Can be restricted to individual product groups or products

### 5.0.13 (2023-05-05)
 * Add pre-order
 * Correctly use language variables for previously hardcoded texts

### 5.0.12 (2023-04-27)
 * Fix search behaviour when priority is not used
 * Improve insert tag "shopPicture"

### 5.0.11 (2023-04-13)
 * Fix PayPal Checkout calculation

### 5.0.10 (2023-04-04)
 * Fix message trigger
 * Fix group prices
 * Some fixes

### 5.0.9 (2023-03-31)
 * Fix sitemal.xml (foreign language)
 * Customizer improvement: More flexibility with variants

### 5.0.8 (2023-03-21)
 * Fix product image gallery when images are assigned directly
 * fix ls_getRecommendedProductsSelection() if ->lsShopProductRecommendedProducts == NULL
 * Improve product search
 * Webp support for product images
 * Some fixes

### 5.0.7 (2023-02-28)
 * Fix: use the main product image if no image is available for the product variant
 * Improve login-switch in checkout

### 5.0.6 (2023-02-17)
 * Fix: layoutcallback for product detail view layout
 * remove Contao 4.9 compatibility

### 5.0.5 (2023-02-17)

### 5.0.4 (2023-02-10)
 * Fix: language inserttag handling in checkout form

### 5.0.3 (2023-02-06)
 * MPM improvement: import of customizer

### 5.0.2 (2023-01-20)
 * Update the value picker template and make sure languages variables are available

### 5.0.1 (2023-01-13)
 * Add new hook "afterCheckoutBeforeRedirect"

### 5.0.0 (2023-01-10)
 * Official release

### 5.0.0 beta13 (2020-10-26)
 * Return null instead of an empty string in custom conditional insert tags

### 5.0.0 beta12 (2020-10-26)
* Don't show the messageTypes which are sent "onRestock" in the backend order overview

### 5.0.0 beta11 (2020-10-26)
* Make sure that custom conditional insert tags don't produce "unknown insert tag" log entries

### 5.0.0 beta10 (2020-10-23)

 * Fixing a bug that caused the live hits results to be cut off not beginning from the
 result with the lowest priority but randomly

### 5.0.0 beta9 (2020-10-23)

### 5.0.0 beta8 (2020-10-23)

 * Add functionality to notify registered members about products which are in stock again
 after being out of stock before.

### 5.0.0 beta7 (2020-10-15)

### 5.0.0 beta6 (2020-10-12)

### 5.0.0 beta5 (2020-09-07)

### 5.0.0 beta4 (2020-09-01)

### 5.0.0 beta3 (2020-08-30)

### 5.0.0 beta2 (2020-08-01)

### 5.0.0 beta1 (2020-06-18)

### 4.0.9 (2020-06-07)

 * Establish compatibility with Contao 4.9

### 4.0.8 (2020-05-27)

 * Add new hook "afterCustomerDataHasBeenPrefilledAfterLogin"
 * Add group restriction functionality to allow products to only be seen and ordered by members of specifically selected groups

### 4.0.7 (2020-04-17)

 * Optimize product import
 * Improve performance
 * Implement new hook "crossSellerHookSelection"
 * Allow custom arguments with ls_shop_product::_useCustomTemplate()/ls_shop_variant::_useCustomTemplate()
 * Fix a few minor bugs
 * Make hit weighting adjustable in merconis settings
 * Add seo specific page title and description to the product record
 * Add the payment means "TWINT" to the Saferpy payment module
 * Use the new authentication technique required by VRpay (Bearer: Token)


### 4.0.6 (2019-04-17)

 * Allow the meta page description to be overwritten with the product's description based on a new shop setting option


### 4.0.5 (2019-04-01)

 * Fix a bug where the message counterNr could not be replaced in order messages
 * Enabling the display of products from subordinate pages in the product overview
 * Correctly access the tax information for payment and shipping in the backend order details and invoice template


### 4.0.4 (2019-01-14)

 * Don't send the OsInfo parameter when communicating with the saferpay API


### 4.0.3 (2018-11-09)

 * Issue #29 closed


### 4.0.2 (2018-10-02)

 * Use cajaxCaller in santander forms
 * Revert: "Add functionality to try to get a dummy image if no product image could be found" (8569124)


### 4.0.1 (2018-09-07)

 * Allow a customLogic file to be used for export data preparation

 * Only render order confirmation form if checkout is allowed

 * Accept aliases of non-existing categories/pages to make sure that a product import via API
 works even if the given category aliases don't make sense. Also accept product data with no
 given alias at all.

 * Add new API resource "syncDbafs"

 * Add new API resource "getStandardProductImagePath" (#26)

 * Add functionality to try to get a dummy image if no product image could be found

 * Add database relation for ls_shop_productManagementApiInspector_apiPage to make sure that
 the page reference is updated properly by the Merconis installer
 
 * Make sure that the filter options "--checkall--" and "--reset--" don't actually get stored
 in the filter criteria array
 
 
### 4.0.0 (2018-04-29)

 * Official release with some small adjustments
 
 
### 4.0.0 rc 1 (2018-04-25)

 * Some minor adjustments
 
 
### 4.0.0 beta 1 (2018-03-16)

 * Now compatible with Contao 4 (contao-bundle)
