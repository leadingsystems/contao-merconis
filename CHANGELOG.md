Leading Systems Contao Merconis bundle changelog
===========================================

### 4.0.24 (2024-01-17)
 * Fix: Payment service provider PAYONE

### 4.0.23 (2023-09-06)
 * Fix: mediabox

### 4.0.22 (2023-09-01)
 * Fix: Static path for lsjs to dynamic path

### 4.0.21 (2023-07-19)
 * Fix: PayPal Checkout calculation (tax)

### 4.0.20 (2023-07-19)
 * Fix: Login bug

### 4.0.19 (2023-04-13)
 * Fix: Output sitemap.xml for foreign languages
 * Fix: PayPal Checkout calculation

### 4.0.18 (2023-02-17)
 * Fix: layoutcallback for product detail view layout
 * remove Contao 4.9 compatibility

### 4.0.17 (2023-02-17)
 * Fix: dca

### 4.0.16 (2023-01-31)
 * Establish compatibility with Contao 4.13

### 4.0.15 (2022-11-14)
 * Fix a search issue

### 4.0.14 (2022-06-09)
 * Fix a search issue

### 4.0.13 (2022-04-22)
 * Fix a search issue

### 4.0.12 (2022-04-06)
 * Fix a minor bug

### 4.0.11 (2022-02-09)

### 4.0.10 (2021-11-17)

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
