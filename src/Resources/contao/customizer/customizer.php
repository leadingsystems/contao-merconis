<?php

namespace Merconis\Core;

abstract class customizer {
    /**
     * @var ls_shop_product
     */
    protected $obj_productOrVariant;
    protected $str_storageKey;
    protected $obj_storage;

    public function __construct($obj_productOrVariant, $str_customizerHash)
    {
        $this->obj_productOrVariant = $obj_productOrVariant;
        $this->str_storageKey = $this->obj_productOrVariant->_productVariantID . ($str_customizerHash ? '_' . $str_customizerHash : '');

        if (isset($_SESSION['lsShop']['customizerStorage'][$this->str_storageKey])) {
            $this->obj_storage = unserialize($_SESSION['lsShop']['customizerStorage'][$this->str_storageKey]);
        } else {
            $this->obj_storage = new customizerStorage($this->str_storageKey);
        }

        $this->initialize();

        $this->manipulateProductData();
    }

    public function storeToSession() {
        $_SESSION['lsShop']['customizerStorage'][$this->str_storageKey] = serialize($this->obj_storage);
    }

    public function saveCustomizerForCurrentCartKey() {
        $_SESSION['lsShop']['customizerStorage'][$this->obj_productOrVariant->_cartKey] = $_SESSION['lsShop']['customizerStorage'][$this->str_storageKey];
    }

    public function getCustomizerHash() {
        return $this->obj_storage->getHash();
    }

    /*
     * Invoked when the customizer is being instantiated. If the customizer needs to perform any initialization actions
     * (like loading language files), this method is the right place for them.
     */
    abstract function initialize();

    /*
     * Invoked when the customizer is being instantiated, directly after the initialize() method has been finished.
     *
     * In this method, product (or variant) data can be manipulated based on the current customization data. For example, the product
     * price can be changed.
     *
     * The product data is accessible with
     *      $this->obj_productOrVariant->mainData
     * for everything that is not language specific
     * and with
     *      $this->obj_productOrVariant->currentLanguageData
     * for language specific data.
     *
     * The data manipulation takes place before the product data is processed any further and therefore has the same
     * effect as if the data would have been changed in the original product record.
     *
     * In most situations where a product is displayed, the manipulated product data should be displayed in order to
     * show the product's current customization status. However, there are certain situations where the original product
     * data must be displayed, e.g. in the product overview.
     *
     * By default, accessing product data with something like
     *      echo $this->obj_productOrVariant->_priceAfterTaxFormatted;
     * uses the manipulated product data.
     *
     * By calling
     *      $this->obj_productOrVariant->useOriginalData();
     * the product object can be switched to using the original data.
     *
     * With
     *      $this->obj_productOrVariant->useCustomizableData();
     * the product object can be switched back to using the possibly manipulated data.
     *
     * If the complete product output in a template should use the original data, it is not necessary to explicitly switch
     * back to using the manipulated data because every time a product object is instantiated, it starts using the
     * manipulated data.
     *
     * Please note: Some data fields' names are not the same in the product and variant record. For example, the product
     * price field is named "lsShopProductPrice" whereas the variant price field is named "lsShopVariantPrice". In order
     * to manipulate the correct value it is therefore necessary in certain situations to check whether the product/variant
     * object is actually a product or a variant object:
     *
     *      $this->obj_productOrVariant->mainData[$this->obj_productOrVariant->_objectType === 'variant' ? 'lsShopVariantPrice' : 'lsShopProductPrice'] = 123.45;
     */
    abstract function manipulateProductData();

    /*
     * Should be called via API (callCustomizerMethodForProduct()) and handle receiving and storing user input
     */
    abstract function receiveUserInput($var_userInput);

    /*
     * Should be called from the method "receiveUserInput" in order to validate and sanitize the user input.
     */
    abstract function validateUserInput($var_userInput);

    /*
     * Should be called via API (callCustomizerMethodForProduct()) and return the stored customization data.
     *
     * Customization data is data that represents the current customization. In a simple scenario the customization
     * data equals the user input.
     */
    abstract function getStoredCustomizationData();

    /*
     * Should be called via API (callCustomizerMethodForProduct()) and return the stored misc data.
     *
     * Misc data is data that does not represent the current customization but is necessary/helpful. If, for example,
     * the customizers user interface has a complex tab navigation, the misc data could hold information about the
     * currently opened tab.
     */
    abstract function getStoredMiscData();

    /*
     * Should be called from the product template and return the html for the user interface. The html code will
     * most likely be a container which will later be extended/enhanced by JavaScript.
     */
    abstract function getUserInterface();

    /*
     * Can be called to determine whether a product is customized or not. Must return true/false depending on whether
     * or not customizationData exists.
     */
    abstract function hasCustomization();

    /*
     * Should be called from the product template and return the summary (most likely as html) of the current customization
     */
    abstract function getSummary();

    /*
     * Should be called from the cart/checkout templates and return a summary (most likely as html) of the current
     * customization that is designed especially for this specific output situation. This summary will be stored in
     * the order record so that it can be used for the order confirmation, invoice etc.
     */
    abstract function getSummaryForCart();

    /*
     * This summary will be stored in the order record so that it can be used for the order notification received by
     * the merchant. Detailed information which is irrelevant for the customer but important for the merchant (e.g.
     * paths to possibly uploaded files) should be included in this summary.
     */
    abstract function getSummaryForMerchant();

    /*
     * The return value of this function will be stored in the order record so that it can be used for anything that
     * takes place after the order is finishe. Since the value is stored serialized, it can hold basically any value
     * and will most likely hold an array with lots of different information.
     */
    abstract function getFlexData();

    /*
     * This function is called to check whether a product is in a state where it can be ordered and must return true to
     * indicate that the product can be ordered or false if the product must not be ordered in its current state.
     */
    abstract function checkIfOrderIsAllowed();
}