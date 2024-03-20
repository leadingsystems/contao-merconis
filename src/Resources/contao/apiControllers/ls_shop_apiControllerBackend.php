<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\Input;

class ls_shop_apiControllerBackend
{
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

	/**
	 * Creates test products based on given source products
	 *
	 * Parameters:
	 * int_sourceProductIdsStart, int_sourceProductIdsStop, int_inputFactor (how many times should the products be inserted)
	 */
	protected function apiResource_createTestProducts()
	{
		$bln_functionDeactivated = true;
		$this->obj_apiReceiver->fail();
		$this->obj_apiReceiver->set_data('resource is currently disabled');

		if ($bln_functionDeactivated) {
			return;
		}

		$int_sourceProductIdsStart = Input::get('int_sourceProductIdsStart') ? Input::get('int_sourceProductIdsStart') : 1;
		$int_sourceProductIdsStop = Input::get('int_sourceProductIdsStop') ? Input::get('int_sourceProductIdsStop') : 1;
		$int_inputFactor = Input::get('int_inputFactor') ? Input::get('int_inputFactor') : 1;

		$this->obj_apiReceiver->success();

		$arr_products = array();

		$arr_resultOutput = array(
			'arr_insertedProductIds' => array(),
			'arr_insertedVariantIds' => array()
		);

		$obj_dbres_products = Database::getInstance()
			->prepare("
			SELECT		*
			FROM		`tl_ls_shop_product`
			WHERE		`id` >= ?
				AND 	`id` <= ?
			ORDER BY	`id` ASC
		")
			->execute($int_sourceProductIdsStart, $int_sourceProductIdsStop);

		if (!$obj_dbres_products->numRows) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no source products found');
			return;
		}

		while ($obj_dbres_products->next()) {
			$arr_products[$obj_dbres_products->id] = $obj_dbres_products->row();
			$arr_products[$obj_dbres_products->id]['arr_variants'] = array();

			$obj_dbres_variants = Database::getInstance()
				->prepare("
				SELECT		*
				FROM		`tl_ls_shop_variant`
				WHERE		`pid` = ?
				ORDER BY	`id` ASC
			")
				->execute($obj_dbres_products->id);

			while ($obj_dbres_variants->next()) {
				$arr_products[$obj_dbres_products->id]['arr_variants'][$obj_dbres_variants->id] = $obj_dbres_variants->row();
			}
		}

		for ($i = 0; $i <= $int_inputFactor; $i++) {
			foreach ($arr_products as $arr_product) {
				$arr_fieldNames = array_keys($arr_product);
				unset($arr_fieldNames[array_search('arr_variants', $arr_fieldNames)]);
				unset($arr_fieldNames[array_search('id', $arr_fieldNames)]);

				$arr_fieldValues = $arr_product;
				unset($arr_fieldValues['arr_variants']);
				unset($arr_fieldValues['id']);

				$arr_valuePlaceholders = array();
				foreach ($arr_fieldNames as $v) {
					$arr_valuePlaceholders[] = '?';
				}

				$obj_dbquery_insertProduct = Database::getInstance()
					->prepare("
					INSERT INTO		`tl_ls_shop_product`
									(" . implode(',', $arr_fieldNames) . ")
					VALUES			(" . implode(',', $arr_valuePlaceholders) . ")
				");

				$obj_dbquery_insertProduct->execute(...$arr_fieldValues);

				$int_lastProductId = $obj_dbquery_insertProduct->insertId;

				$arr_resultOutput['arr_insertedProductIds'][] = $int_lastProductId;

				foreach ($arr_product['arr_variants'] as $arr_variant) {
					$arr_fieldNames = array_keys($arr_variant);
					unset($arr_fieldNames[array_search('id', $arr_fieldNames)]);

					$arr_fieldValues = $arr_variant;
					unset($arr_fieldValues['id']);

					$arr_fieldValues['pid'] = $int_lastProductId;

					$arr_valuePlaceholders = array();
					foreach ($arr_fieldNames as $v) {
						$arr_valuePlaceholders[] = '?';
					}

					$obj_dbquery_insertVariant = Database::getInstance()
						->prepare("
						INSERT INTO		`tl_ls_shop_variant`
										(" . implode(',', $arr_fieldNames) . ")
						VALUES			(" . implode(',', $arr_valuePlaceholders) . ")
					");

					$obj_dbquery_insertVariant->execute(...$arr_fieldValues);

					$int_lastVariantId = $obj_dbquery_insertVariant->insertId;

					$arr_resultOutput['arr_insertedVariantIds'][] = $int_lastVariantId;
				}
			}
		}

		$str_titleUpdateStatement = "`title` = CONCAT(`title`, ' ', `id`)";
		foreach (ls_shop_languageHelper::getAllLanguages() as $str_language) {
			$str_titleUpdateStatement = $str_titleUpdateStatement.", `title_".$str_language."` = CONCAT(`title_".$str_language."`, ' ', `id`)";
		}

		if (count($arr_resultOutput['arr_insertedProductIds'])) {
			$obj_dbquery_updateProductTitles = Database::getInstance()
			->prepare("
				UPDATE		`tl_ls_shop_product`
				SET			".$str_titleUpdateStatement."
				WHERE		`id` IN (" . implode(',', $arr_resultOutput['arr_insertedProductIds']) . ")
			")
			->execute();
		}

		if (count($arr_resultOutput['arr_insertedVariantIds'])) {
			$obj_dbquery_updateVariantTitles = Database::getInstance()
			->prepare("
				UPDATE		`tl_ls_shop_variant`
				SET			".$str_titleUpdateStatement."
				WHERE		`id` IN (" . implode(',', $arr_resultOutput['arr_insertedVariantIds']) . ")
			")
			->execute();
		}

		$this->obj_apiReceiver->set_data($arr_resultOutput);
	}

	/**
	 * Returns all attributes and values
	 */
	protected function apiResource_backend_loadAttributesAndValues() {
		$arr_return = ls_shop_generalHelper::getAllAttributesAndValues();

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($arr_return);
	}
}
