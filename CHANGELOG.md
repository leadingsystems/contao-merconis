Leading Systems Contao Merconis bundle changelog
===========================================

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
