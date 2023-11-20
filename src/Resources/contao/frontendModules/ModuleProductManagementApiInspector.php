<?php

namespace Merconis\Core;

use Contao\System;

class ModuleProductManagementApiInspector extends \Module {
	public $strTemplate = 'template_productManagementApiInspector';

	public function generate() {
		if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
			$objTemplate = new \BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### MERCONIS product management API inspector ###';
			return $objTemplate->parse();
		}
		return parent::generate();
	}
	
	public function compile() {
		/** @var \PageModel $objPage */
		global $objPage;

		$str_selectedResource = \Input::get('selectedResource');

		$arr_allRawResourceNames = array_keys(ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition);
		$arr_allRawResourceNames = array_flip($arr_allRawResourceNames);

		$arr_allResourceLinks = array();

		foreach ($arr_allRawResourceNames as $str_rawResourceName => $void) {
			$str_resourceName = str_replace('apiResource_', '', $str_rawResourceName);
			$arr_allResourceLinks[$str_resourceName] = array(
				'str_href' => \Controller::generateFrontendUrl($objPage->row(), '/selectedResource/'.$str_resourceName),
				'bln_currentlySelected' => $str_selectedResource && $str_resourceName === $str_selectedResource,
				'str_httpRequestMethod' => isset(ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition[$str_rawResourceName]['str_httpRequestMethod']) && ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition[$str_rawResourceName]['str_httpRequestMethod'] ? ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition[$str_rawResourceName]['str_httpRequestMethod'] : 'post'
			);
		}

		$this->Template->arr_allResourceLinks = $arr_allResourceLinks;

		if (!$str_selectedResource) {
			return;
		}

		$arr_preprocessorDescriptions = ls_shop_productManagementApiPreprocessor::getPreprocessorDescriptions();

		$obj_apiPage = \Database::getInstance()
		->prepare("
			SELECT		*
			FROM		`tl_page`
			WHERE		`id` = ?
		")
		->execute($this->ls_shop_productManagementApiInspector_apiPage);

		if (!$obj_apiPage->numRows) {
			return '';
		}

		$obj_apiPage->first();

		$str_apiResourceUrl = \Controller::generateFrontendUrl($obj_apiPage->row(), '/resource/'.$str_selectedResource);

		$arr_apiResourceDescriptions = ls_shop_productManagementApiHelper::getApiResourceDescriptions();

		$this->Template->str_apiResourceUrl = $str_apiResourceUrl;
		$this->Template->str_selectedResource = $str_selectedResource;
		$this->Template->arr_preprocessorDescriptions = $arr_preprocessorDescriptions;
		$this->Template->str_resourceDescription = $arr_apiResourceDescriptions['apiResource_'.$str_selectedResource];
		$this->Template->arr_fieldDefinition = ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['arr_fields'];
		$this->Template->str_httpRequestMethod = isset(ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['str_httpRequestMethod']) && ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['str_httpRequestMethod'] ? ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['str_httpRequestMethod'] : 'post';
		$this->Template->str_responseType = isset(ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['str_responseType']) && ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['str_responseType'] ? ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['str_responseType'] : 'json';
		$this->Template->bln_expectsMultipleDataRows = isset(ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['bln_expectsMultipleDataRows']) && ls_shop_productManagementApiPreprocessor::$arr_resourceAndFieldDefinition['apiResource_'.$str_selectedResource]['bln_expectsMultipleDataRows'] ? 1 : 0;
	}
}
?>