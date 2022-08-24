<?php

namespace Merconis\Core;

abstract class customizerLogicBase {
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
     * Called via API (callCustomizerMethodForProduct())
     */
    public function receiveFormData($var_formData) {
        $this->obj_storage->writeData($var_formData);
    }

    /*
     * Called via API (callCustomizerMethodForProduct())
     */
    public function getStoredData() {
        return $this->obj_storage->getData();
    }

    /*
     * Called from product template
     */
    public function getCustomizerInterface() {
        ob_start();
        ?>
        <div data-merconis-component="customizerBasic" data-merconis-productVariantId="<?php echo $this->obj_productOrVariant->_productVariantID; ?>" data-merconis-targetUrl="<?php echo \Environment::get('request'); ?>"></div>
        <?php
        return ob_get_clean();
    }

    abstract function initialize();
}

class customizerStorage {
    /*
     * This class should take care of updating the customizer hash (unless it is fixed) when data is written.
     * Therefore we have set $var_data to private and need to create a convenient setter method!
     */
    private $var_data = null;
    private $str_customizerHash = '';
    private $bln_customizerHashFixed = false;

    public function __construct($str_storageKey)
    {
        if (preg_match('/_(.*)$/', $str_storageKey, $arr_matches)) {
            $this->bln_customizerHashFixed = true;
            $this->str_customizerHash = $arr_matches[1];
        }
    }

    public function writeData($var_data) {
        $this->var_data = $var_data;
        $this->updateCustomizerHash();
    }

    public function getData() {
        return $this->var_data;
    }

    public function getHash() {
        return $this->str_customizerHash;
    }

    private function updateCustomizerHash() {
        if ($this->bln_customizerHashFixed) {
            /*
             * If the hash is fixed, we know that we deal with a customizer instance that is already isolated from the
             * original product's customizer.
             *
             * An isolated instance exists if the customized product has been placed in the cart.
             *
             * Please note: If the customization represented by an isolated customizer instance is being changed,
             * the customizer hash MUST NOT be changed to match the current customization. Instead it is important to
             * keep the customizer hash used for isolation because we need to keep the isolated instance!
             *
             * This is why we simply return here without actually updating the customizer hash.
             *
             */
            return;
        }

        $this->str_customizerHash = sha1(serialize($this->var_data));
    }
}