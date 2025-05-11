<?php

namespace Merconis\Core;

class customizerStorage {
    public $var_customizationData = null;
    public $var_miscData = null;
    private $str_customizerHash = '';
    private $bln_customizerHashFixed = false;

    public function __construct($str_storageKey)
    {
        if (preg_match('/_(.*)$/', $str_storageKey, $arr_matches)) {
            $this->bln_customizerHashFixed = true;
            $this->str_customizerHash = $arr_matches[1];
        }
    }

    public function __serialize() {
        $this->updateCustomizerHash();

        $arr_objectRepresentation = [
            'var_customizationData' => $this->var_customizationData,
            'var_miscData' => $this->var_miscData,
            'str_customizerHash' => $this->str_customizerHash,
            'bln_customizerHashFixed' => $this->bln_customizerHashFixed,
        ];

        return $arr_objectRepresentation;
    }

    public function __unserialize($arr_objectRepresentation) {
        $this->var_customizationData = $arr_objectRepresentation['var_customizationData'];
        $this->var_miscData = $arr_objectRepresentation['var_miscData'];
        $this->str_customizerHash = $arr_objectRepresentation['str_customizerHash'];
        $this->bln_customizerHashFixed = $arr_objectRepresentation['bln_customizerHashFixed'];
    }

    /**
     * @deprecated Deprecated, to be removed in Merconis 6
     * $this->var_customizationData is now publicly accessible and should be written to directly
     */
    public function writeCustomizationData($var_data) {
        $this->var_customizationData = $var_data;
    }

    /**
     * @deprecated Deprecated, to be removed in Merconis 6
     * $this->var_customizationData is now publicly accessible and should be read from directly
     */
    public function getCustomizationData() {
        return $this->var_customizationData;
    }

    /**
     * @deprecated Deprecated, to be removed in Merconis 6
     * $this->var_miscData is now publicly accessible and should be written to directly
     */
    public function writeMiscData($var_data) {
        $this->var_miscData = $var_data;
    }

    /**
     * @deprecated Deprecated, to be removed in Merconis 6
     * $this->var_miscData is now publicly accessible and should be read from directly
     */
    public function getMiscData() {
        return $this->var_miscData;
    }

    public function getHash() {
        return $this->str_customizerHash;
    }

    public function updateCustomizerHash() {
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