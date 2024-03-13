<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\Input;

class ls_shop_apiController_exportFrontend
{
	protected static $objInstance;

	/** @var \LeadingSystems\Api\ls_apiController $obj_apiReceiver */
	protected $obj_apiReceiver = null;

	protected function __construct() {}

	private function __clone()
	{
	}

	public static function getInstance()
	{
		if (!is_object(self::$objInstance)) {
			self::$objInstance = new self();
		}

		return self::$objInstance;
	}

	public function processRequest($str_resourceName, $obj_apiReceiver)
	{
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
	 * Returns export output
	 */
	protected function apiResource_exportFeed()
	{
		$str_feedName = Input::get('feedName') ? Input::get('feedName') : null;
		$str_pwd = Input::get('pwd') ? Input::get('pwd') : '';

		if (!$str_feedName) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no feed name given');
			return;
		}

		$obj_dbres_export = Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_export`
		  	WHERE		`feedActive` = ?
		  		AND 	`feedName` = ?
		  		AND 	`feedPassword` = ?
		")
			->limit(1)
			->execute(
				'1',
				$str_feedName,
				$str_pwd
			);

		if ($obj_dbres_export->numRows < 1) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('could not find an export model with given parameters');
			return;
		}

		$obj_export = new ls_shop_export($obj_dbres_export->first()->id, 'id', 'feed');

		$var_return = $obj_export->parseExport();

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($var_return);
		$this->obj_apiReceiver->set_headerContentType($obj_dbres_export->feedContentType);

		$str_feedFileName = $obj_dbres_export->feedFileName;
		if ($str_feedFileName) {
			/*
			 * Find and replace the date wildcards
			 */
			preg_match_all('/\{\{date:(.*)\}\}/siU', $str_feedFileName, $matches);
			foreach ($matches[0] as $key => $match) {
				$str_feedFileName = preg_replace('/' . preg_quote($match) . '/siU', date($matches[1][$key]), $str_feedFileName);
			}

			$str_feedFileName = str_replace('{{currentSegment}}', $obj_export->bln_useSegmentation ? str_pad($obj_export->obj_segmentizer->currentSegment, strlen($obj_export->obj_segmentizer->numSegmentsTotal), 0, STR_PAD_LEFT) : 0, $str_feedFileName);
			$str_feedFileName = str_replace('{{numSegmentsTotal}}', $obj_export->bln_useSegmentation ? $obj_export->obj_segmentizer->numSegmentsTotal : 0, $str_feedFileName);
			$str_feedFileName = str_replace('{{currentTurn}}', $obj_export->bln_useSegmentation ? $obj_export->obj_segmentizer->currentTurn : 0, $str_feedFileName);

			header('Content-disposition: attachment;filename=' . $str_feedFileName);
		}
	}
}