<?php

namespace Merconis\Core;

class customizerStorage {
    private $var_customizationData = null;
    private $var_miscData = null;
    private $str_customizerHash = '';
    private $bln_customizerHashFixed = false;

    public function __construct($str_storageKey)
    {
        if (preg_match('/_(.*)$/', $str_storageKey, $arr_matches)) {
            $this->bln_customizerHashFixed = true;
            $this->str_customizerHash = $arr_matches[1];
        }
    }

    public function writeCustomizationData($var_data) {
        $this->var_customizationData = $var_data;
        $this->updateCustomizerHash();
    }

    public function getCustomizationData() {
        //dump($this->var_customizationData);
        return $this->var_customizationData;
    }

    public function writeMiscData($var_data) {
        $this->var_miscData = $var_data;
    }

    public function getMiscData() {
        return $this->var_miscData;
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

        $this->str_customizerHash = sha1(serialize($this->var_customizationData));
    }
}