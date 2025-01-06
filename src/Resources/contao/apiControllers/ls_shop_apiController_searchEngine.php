<?php

namespace Merconis\Core;

use Contao\System;
use LeadingSystems\MerconisBundle\SearchEngine\SearchEngine;

class ls_shop_apiController_searchEngine {
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
	
	protected function apiResource_searchEngine_test01() {
        try {
            /** @var $searchEngine SearchEngine */
            $searchEngine = System::getContainer()->get('LeadingSystems\MerconisBundle\SearchEngine\SearchEngine');

            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data($searchEngine->runTests());
        } catch (\Throwable $e) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($e->getMessage());
        }
	}

	protected function apiResource_searchEngine_createProductsIndex() {
        try {
            /** @var $searchEngine SearchEngine */
            $searchEngine = System::getContainer()->get('LeadingSystems\MerconisBundle\SearchEngine\SearchEngine');

            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data($searchEngine->createProductsIndex());
        } catch (\Throwable $e) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($e->getMessage());
        }
	}

	protected function apiResource_searchEngine_addAllProductsToIndex() {
        /*
         * Do me! Indexing all products probably takes much time and can probably not be done all at once.
         *  Find a better solution.
         *  Idea: The search engine client class could take care of splitting up adding the products into multiple
         *  requests and it could say in its result message whether there are still more products to add so that
         *  the api resource would have to be called again.
         */
        try {
            /** @var $searchEngine SearchEngine */
            $searchEngine = System::getContainer()->get('LeadingSystems\MerconisBundle\SearchEngine\SearchEngine');

            $this->obj_apiReceiver->success();
            $this->obj_apiReceiver->set_data($searchEngine->addAllProductsToIndex());
        } catch (\Throwable $e) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($e->getMessage());
        }
	}
}
