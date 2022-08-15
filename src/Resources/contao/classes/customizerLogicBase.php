<?php

namespace Merconis\Core;

abstract class customizerLogicBase {
    protected $obj_productOrVariant;
    protected $str_cacheKey;
    protected $var_storage;

    public function __construct($obj_productOrVariant, $str_cacheKey)
    {
        $this->obj_productOrVariant = $obj_productOrVariant;
        $this->str_cacheKey = $str_cacheKey;

        if (!isset($_SESSION['lsShop']['customizerStorage'][$this->str_cacheKey])) {
            $_SESSION['lsShop']['customizerStorage'][$this->str_cacheKey] = [];
        }

        $this->var_storage = $_SESSION['lsShop']['customizerStorage'][$this->str_cacheKey];

        $this->initialize();
    }

    public function storeToSession() {
        $_SESSION['lsShop']['customizerStorage'][$this->str_cacheKey] = $this->var_storage;
    }

    abstract function initialize();
}