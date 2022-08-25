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

    abstract function initialize();

    abstract function manipulateProductData();

    /*
     * Should be called via API (callCustomizerMethodForProduct()) and handle receiving and storing user input
     */
    abstract function receiveUserInput($var_formData);

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
}