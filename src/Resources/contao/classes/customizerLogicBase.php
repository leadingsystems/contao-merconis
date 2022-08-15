<?php

namespace Merconis\Core;

abstract class customizerLogicBase {
    protected $obj_productOrVariant;
    protected $str_storageKey;
    protected $obj_storage;

    public function __construct($obj_productOrVariant, $str_customizerHash)
    {
        $this->obj_productOrVariant = $obj_productOrVariant;
        $this->str_storageKey = $this->createStorageKey($str_customizerHash);

        if (isset($_SESSION['lsShop']['customizerStorage'][$this->str_storageKey])) {
            $this->obj_storage = unserialize($_SESSION['lsShop']['customizerStorage'][$this->str_storageKey]);
        } else {
            $this->obj_storage = new customizerStorage();
        }

        if ($str_customizerHash) {
            $this->obj_storage->str_customizerHash = $str_customizerHash;
        }

        $this->initialize();
    }

    private function createStorageKey($str_hashToUse = '') {
        return $this->obj_productOrVariant->_productVariantID . ($str_hashToUse ? '_' . $str_hashToUse : '');
    }

    public function storeToSession() {
        $_SESSION['lsShop']['customizerStorage'][$this->str_storageKey] = serialize($this->obj_storage);
    }

    protected function updateCustomizerHash() {
        if (strpos($this->str_storageKey, '_') !== false) {
            /*
             * If we find the delimiter character for the customizer hash in the storage key, we know that we deal
             * with a customizer instance that is already isolated from the original product's customizer.
             * Example:
             *     Storage key for the original product's customizer => 1-0 (productId-variantId)
             *     Storage key for the isolated customizer instance => 1-0_abcdef (productId-variantId_customizerHash)
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

        $this->obj_storage->str_customizerHash = sha1(serialize($this->obj_storage->var_data));
    }

    public function getCustomizerHash() {
        if (!$this->obj_storage->str_customizerHash) {
            $this->updateCustomizerHash();
        }

        return $this->obj_storage->str_customizerHash;
    }

    /*
     * Called via API (callCustomizerMethodForProduct())
     */
    public function receiveFormData($var_formData) {
        \LeadingSystems\Helpers\lsErrorLog('$var_formData', $var_formData, 'perm', 'var_dump');
        $this->obj_storage->var_data = $var_formData;

        $this->updateCustomizerHash();
    }

    /*
     * Called via API (callCustomizerMethodForProduct())
     */
    public function getStoredData() {
        return $this->obj_storage->var_data;
    }

    /*
     * Called from product template
     */
    public function getCustomizerInterface() {
        ob_start();
        ?>
        <div data-merconis-component="customizerBasic" data-merconis-productVariantId="<?php echo $this->obj_productOrVariant->_productVariantID; ?>"></div>
        <?php
        return ob_get_clean();
    }

    abstract function initialize();
}

class customizerStorage {
    public $var_data = [];
    public $str_customizerHash = '';
}