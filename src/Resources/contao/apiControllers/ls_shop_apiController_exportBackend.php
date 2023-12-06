<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;
use Contao\System;

class ls_shop_apiController_exportBackend
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
	 * Writes an export to a file
	 */
	protected function apiResource_writeExportFile()
	{
		$int_exportId = \Input::get('exportId') ? \Input::get('exportId') : null;

		if (!$int_exportId) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no export id given');
			return;
		}

		$obj_dbres_export = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_export`
		  	WHERE		`fileExportActive` = ?
		  		AND 	`id` = ?
		")
			->limit(1)
			->execute(
				'1',
				$int_exportId
			);

		if ($obj_dbres_export->numRows < 1) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('could not find an export model with given parameters');
			return;
		}

		$obj_dbres_export->first();

		if (!$obj_dbres_export->folder || !$obj_dbres_export->fileName) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('folder and/or fileName not defined in export model');
			return;
		}

		$obj_export = new ls_shop_export($obj_dbres_export->id, 'id', 'file');

		$var_return = $obj_export->parseExport();

		$str_fileName = $obj_dbres_export->fileName;
		if ($str_fileName) {
			/*
			 * Find and replace the wildcards
			 */
			preg_match_all('/\{\{date:(.*)\}\}/siU', $str_fileName, $matches);
			foreach ($matches[0] as $key => $match) {
				$str_fileName = preg_replace('/' . preg_quote($match) . '/siU', date($matches[1][$key]), $str_fileName);
			}

			$str_fileName = str_replace('{{currentSegment}}', $obj_export->bln_useSegmentation ? str_pad($obj_export->obj_segmentizer->currentSegment, strlen($obj_export->obj_segmentizer->numSegmentsTotal), 0, STR_PAD_LEFT) : 0, $str_fileName);
			$str_fileName = str_replace('{{numSegmentsTotal}}', $obj_export->bln_useSegmentation ? $obj_export->obj_segmentizer->numSegmentsTotal : 0, $str_fileName);
			$str_fileName = str_replace('{{currentTurn}}', $obj_export->bln_useSegmentation ? $obj_export->obj_segmentizer->currentTurn : 0, $str_fileName);
		}

		$str_pathToFileExportFolder = ls_getFilePathFromVariableSources($obj_dbres_export->folder);

		$str_filePath = $str_pathToFileExportFolder.'/'.$str_fileName;
		$str_fullFilePath = System::getContainer()->getParameter('kernel.project_dir').'/'.$str_filePath;

		$handle_fileCsv = fopen($str_fullFilePath, $obj_dbres_export->appendToFile ? 'ab' : 'wb');

		if ($handle_fileCsv === false) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('could not create file "'.$str_filePath.'"');
			return;
		}

		$int_bytesWritten = fwrite($handle_fileCsv, !is_array($var_return) ? $var_return : json_encode($var_return));
		if ($int_bytesWritten !== false) {
			$this->obj_apiReceiver->success();
			$this->obj_apiReceiver->set_data(array(
				'bytesWritten' => $int_bytesWritten,
				'fileName' => $str_filePath,
				'str_fullFileName' => $str_fullFilePath
			));
		} else {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('could not write to export file "'.$str_filePath.'"');
		}
		fclose($handle_fileCsv);
	}

	/**
	 * Deletes an export file
	 */
	protected function apiResource_deleteExportFile()
	{
		$int_exportId = \Input::get('exportId') ? \Input::get('exportId') : null;
		$str_fileName = \Input::get('fileName') ? \Input::get('fileName') : null;

		if (!$int_exportId || !$str_fileName) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('no export id or file name given');
			return;
		}

		$obj_dbres_export = \Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_ls_shop_export`
		  	WHERE		`id` = ?
		")
			->limit(1)
			->execute(
				$int_exportId
			);

		if ($obj_dbres_export->numRows < 1) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('could not find an export model with given parameters');
			return;
		}

		$obj_dbres_export->first();

		if (!$obj_dbres_export->folder) {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('folder not defined in export model');
			return;
		}

		$str_pathToFileExportFolder = ls_getFilePathFromVariableSources($obj_dbres_export->folder);

		$str_filePath = $str_pathToFileExportFolder.'/'.$str_fileName;
		$str_fullFilePath = System::getContainer()->getParameter('kernel.project_dir').'/'.$str_filePath;

		if (unlink($str_fullFilePath) !== false) {
			$this->obj_apiReceiver->success();
			$this->obj_apiReceiver->set_data(array(
				'fileName' => $str_filePath,
				'str_fullFileName' => $str_fullFilePath
			));
		} else {
			$this->obj_apiReceiver->fail();
			$this->obj_apiReceiver->set_data('could not delete export file "'.$str_filePath.'"');
		}
	}
}