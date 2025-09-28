<?php

namespace Merconis\Core;

use Contao\System;
use LeadingSystems\MerconisBundle\SearchServer\SearchServer;

class ls_shop_apiController_searchServer {
	protected static $objInstance;

	/** @var \LeadingSystems\Api\ls_apiController $obj_apiReceiver */
	protected $obj_apiReceiver = null;

	protected function __construct() {}

	private function __clone() {}

	public static function getInstance() {
		if (!is_object(self::$objInstance))
		{
			self::$objInstance = new self();
		}
		
		return self::$objInstance;
	}
	
	public function processRequest($str_resourceName, $obj_apiReceiver) {
		if (!$str_resourceName || !$obj_apiReceiver) {
			return;
		}
		
		$this->obj_apiReceiver = $obj_apiReceiver;
		
		/*
		 * If this class has a method that matches the resource name, we call it.
		 * If not, we don't do anything because another class with a corresponding
		 * method might have a hook registered.
		 */
		if (method_exists($this, $str_resourceName)) {
			$this->{$str_resourceName}();
		}
	}
	
	protected function apiResource_searchServer_test01() {
        try {
            /** @var $searchServer SearchServer */
            $searchServer = System::getContainer()->get('LeadingSystems\MerconisBundle\SearchServer\SearchServer');

            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data($searchServer->runTests());
        } catch (\Throwable $e) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($e->getMessage());
        }
	}

	protected function apiResource_searchServer_createProductsIndex() {
        try {
            /** @var $searchServer SearchServer */
            $searchServer = System::getContainer()->get('LeadingSystems\MerconisBundle\SearchServer\SearchServer');

            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data($searchServer->createProductsIndex());
        } catch (\Throwable $e) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($e->getMessage());
        }
	}

	protected function apiResource_searchServer_addAllProductsToIndex() {
        try {
            /** @var $searchServer SearchServer */
            $searchServer = System::getContainer()->get('LeadingSystems\MerconisBundle\SearchServer\SearchServer');

            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data($searchServer->addAllProductsToIndex());
        } catch (\Throwable $e) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($e->getMessage());
        }
	}
}
