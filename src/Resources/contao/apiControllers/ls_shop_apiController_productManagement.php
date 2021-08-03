<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_apiController_productManagement
{
	protected static $objInstance;

	/** @var \LeadingSystems\Api\ls_apiController $obj_apiReceiver */
	protected $obj_apiReceiver = null;

	protected function __construct() {}

	final private function __clone()
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
	 * Returns all contao page aliases that can be used as product categories
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getCategoryAliases()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getPageAliases(true));
	}




    /**
     * Returns all contao pages that can be used as product categories
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */

    protected function apiResource_getCategories()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getCategories(true));
    }


    /**
     * Inserts new or updates existing (if it already exists) pages that can be used as product categories
     * Expects the request details JSON formatted as POST parameter 'data'
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_writeCategories()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $arr_dataRows = json_decode($_POST['data'], true);

        if (!count($arr_dataRows)) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message('data parameter missing or empty');
            return;
        }

        $arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

        if ($arr_preprocessingResult['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

        $arr_result = $this->performCategoryImport($arr_dataRows);

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if ($arr_result['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_result['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_result);
    }

    /**
     * Deletes pages that can be used as product categories
     * Expects the request details JSON formatted as POST parameter 'data'
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_deleteCategories()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $arr_dataRows = json_decode($_POST['data'], true);

        if (!count($arr_dataRows)) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message('data parameter missing or empty');
            return;
        }

        $arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

        if ($arr_preprocessingResult['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

        foreach ($arr_dataRows as $arr_dataRow) {
            $int_pageId = $arr_dataRow['id'];
            $bln_deleted = ls_shop_productManagementApiHelper::deleteCategory($int_pageId);

            if ($bln_deleted) {
                $arr_result['arr_messages']['categoryDeleted'][] = $int_pageId;
            } else {
                $arr_result['arr_messages']['categoryNotDeleted'][] = $int_pageId;
            }
         }

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if ($arr_result['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_result['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_result);
    }

    /**
     * Returns all company names that are used as product-manufacturers
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_getManufacturer()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getManufacturer());
    }

    /**
     * Returns all types of Customers/Members
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_getMemberGroups()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getMemberGroups());
    }

    /**
     * Returns all Methods used for the delivery of the goods
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_getShippingMethods()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getShippingMethods());
    }

    /**
     * Returns all TaxRates
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_getTaxRates()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getTaxRates());
    }

    /**
     * Returns the used Currency saved in localconfig.php
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_getCurrency()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getCurrency());
    }

    /**
     * Inserts or updates existing Currency Settings
     * Expects the request details JSON formatted as POST parameter 'data'
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_writeCurrency()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $arr_dataRows = json_decode($_POST['data'], true);

        if (!count($arr_dataRows)) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message('data parameter missing or empty');
            return;
        }

        $arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

        if ($arr_preprocessingResult['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

        $arr_result = $this->performCurrencyImport($arr_dataRows);

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if ($arr_result['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_result['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_result);
    }

	/**
	 * Returns the input price type used by Merconis
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getInputPriceType()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($GLOBALS['TL_CONFIG']['ls_shop_priceType'] == 'brutto' ? 'gross' : 'net');
	}

	/**
	 * Returns the available price and weight modification types that are required to specify a variant price or weight
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getPriceAndWeightModificationTypes()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(array_keys(ls_shop_productManagementApiHelper::$modificationTypesTranslationMap));
	}

	/**
	 * Returns the available scale price types
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getScalePriceTypes()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::$arr_scalePriceTypes);
	}

	/**
	 * Returns the available scale price quantity detection methods
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getScalePriceQuantityDetectionMethods()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::$arr_scalePriceQuantityDetectionMethods);
	}

	/**
	 * Returns the aliases of all existing delivery info types
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getDeliveryInfoTypeAliases()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getDeliveryInfoTypeAliases());
	}

	/**
	 * Returns the aliases of all existing configurators
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getConfiguratorAliases()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getConfiguratorAliases());
	}

	/**
	 * Returns the names of all existing product details templates
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getProductDetailsTemplates()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(array_keys(\Controller::getTemplateGroup('template_productDetails_')));
	}

	/**
	 * Returns the aliases of all existing properties and values. The first level keys of the response object represent the property aliases and the second level keys the respective value aliases
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getPropertyAndValueAliases()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getAttributeAndValueAliasesInRelation());
	}

	/**
	 * Returns the aliases of existing tax classes
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getTaxClassAliases()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getTaxClassAliases());
	}

	/**
	 * Returns the standard product image path as defined in the Merconis settings in the Contao backend
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getStandardProductImagePath()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

        $str_pathToStandardProductImageFolder = ls_getFilePathFromVariableSources($GLOBALS['TL_CONFIG']['ls_shop_standardProductImageFolder']);

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($str_pathToStandardProductImageFolder);
	}

	/**
	 * Synchronize DBAFS
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_syncDbafs()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

        \Dbafs::syncFiles();

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(true);
	}

	/**
	 * Returns a list containing the names of all images that are stored in the standard product image folder
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_getProductImageNames()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$this->obj_apiReceiver->success();
		$obj_productFake = null;
		$this->obj_apiReceiver->set_data(ls_shop_generalHelper::getImagesFromStandardFolder($obj_productFake, '__ALL_IMAGES__', false));
	}

	/**
	 * Returns an image that is stored in the standard product image folder and that can be identified by its name
	 *
	 * Scope: FE
	 *
	 * Allowed user types: no restriction
	 */
	protected function apiResource_getProductImageByName()
	{
		$this->obj_apiReceiver->requireScope(['FE']);

		$arr_dataRows = json_decode($_GET['data'], true);

		if (!count($arr_dataRows)) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message('data parameter missing or empty');
			return;
		}

		$arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

		if ($arr_preprocessingResult['bln_hasError']) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
			$this->obj_apiReceiver->set_httpResponseCode(200);
			return;
		}

		$arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

		$str_filePath = ls_shop_generalHelper::getProductImageByPath(
			ls_getFilePathFromVariableSources($arr_dataRows['image']),
			$arr_dataRows['width'],
			$arr_dataRows['height'],
			$arr_dataRows['resizeMode'],
			$arr_dataRows['forceRefresh']
		);

		if (!$str_filePath) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message('no output file');
			return;
		}

		$obj_file = new \File($str_filePath, true);

		$this->obj_apiReceiver->set_httpResponseCode(200);

		header('Content-Type:'.$obj_file->mime);

		echo $obj_file->getContent();
		exit;
	}

    /**
     * Returns all products (no Variants)
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_getProducts()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data(ls_shop_productManagementApiHelper::getProducts());
    }

	/**
	 * Inserts product data or updates product data if it already exists. Expects the request details JSON formatted as POST parameter 'data'
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_writeProductData()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$arr_dataRows = json_decode($_POST['data'], true);

		if (!count($arr_dataRows)) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message('data parameter missing or empty');
			return;
		}

		$arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

		if ($arr_preprocessingResult['bln_hasError']) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
			$this->obj_apiReceiver->set_httpResponseCode(200);
			return;
		}

		$arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

		$arr_result = $this->performImport($arr_dataRows);
        $arr_resultIds = $arr_result['arr_idsInsertedOrUpdated'];

		ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

		if ($arr_result['bln_hasError']) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message($arr_result['arr_messages']);
			$this->obj_apiReceiver->set_httpResponseCode(200);
			return;
		}

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data($arr_resultIds );
	}

	/**
	 * Deletes product data. Expects the request details JSON formatted as POST parameter 'data'
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_deleteProductData()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$arr_dataRows = json_decode($_POST['data'], true);

		if (!count($arr_dataRows)) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message('data parameter missing or empty');
			return;
		}

		$arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

		if ($arr_preprocessingResult['bln_hasError']) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
			$this->obj_apiReceiver->set_httpResponseCode(200);
			return;
		}

		$arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

		$arr_result = $this->performDeletion($arr_dataRows);

		ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

		if ($arr_result['bln_hasError']) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message($arr_result['arr_messages']);
			$this->obj_apiReceiver->set_httpResponseCode(200);
			return;
		}

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(null);
	}

    /**
     * Inserts Product Properties or updates it if it already exists. Expects the request details JSON formatted as POST parameter 'data'
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_writeProperty()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $arr_dataRows = json_decode($_POST['data'], true);

        if (!count($arr_dataRows)) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message('data parameter missing or empty');
            return;
        }

        $arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

        if ($arr_preprocessingResult['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

        $arr_result = $this->performPropertyImport($arr_dataRows);
        $arr_resultIds = $arr_result['arr_idsInsertedOrUpdated'];

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if ($arr_result['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_result['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_resultIds );
    }

    /**
     * Deletes Propertys
     * Expects the request details JSON formatted as POST parameter 'data'
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_deleteProperty()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $arr_dataRows = json_decode($_POST['data'], true);

        if (!count($arr_dataRows)) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message('data parameter missing or empty');
            return;
        }

        $arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

        if ($arr_preprocessingResult['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

        foreach ($arr_dataRows as $arr_dataRow) {
            //TODO: das Löschen von Propertys vollständig umsetzen, wenn Zeit ist
/*
            $int_propertyId = $arr_dataRow['id'];
            $bln_deleted = ls_shop_productManagementApiHelper::deleteProperty($int_propertyId);

            if ($bln_deleted) {
                $arr_result['arr_messages']['propertyDeleted'][] = $int_propertyId;
            }
*/
        }
        $arr_result = array();

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if ($arr_result['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_result['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_result);
    }

    /**
     * Inserts Product Property Values or updates it if it already exists. Expects the request details JSON formatted as POST parameter 'data'
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_writePropertyValue()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $arr_dataRows = json_decode($_POST['data'], true);

        if (!count($arr_dataRows)) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message('data parameter missing or empty');
            return;
        }

        $arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

        if ($arr_preprocessingResult['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

        $arr_result = $this->performPropertyValueImport($arr_dataRows);
        $arr_resultIds = $arr_result['arr_idsInsertedOrUpdated'];

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if ($arr_result['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_result['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_resultIds);
    }

    /**
     * Deletes Property Values
     * Expects the request details JSON formatted as POST parameter 'data'
     *
     * Scope: FE
     *
     * Allowed user types: apiUser
     */
    protected function apiResource_deletePropertyValue()
    {
        $this->obj_apiReceiver->requireScope(['FE']);
        $this->obj_apiReceiver->requireUser(['apiUser']);

        $arr_dataRows = json_decode($_POST['data'], true);

        if (!count($arr_dataRows)) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message('data parameter missing or empty');
            return;
        }

        $arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

        if ($arr_preprocessingResult['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

        foreach ($arr_dataRows as $arr_dataRow) {
            //TODO: das Löschen von Propertys vollständig umsetzen, wenn Zeit ist
/*
            $int_propertyId = $arr_dataRow['id'];
            $bln_deleted = ls_shop_productManagementApiHelper::deletePropertyValue($int_propertyId);

            if ($bln_deleted) {
                $arr_result['arr_messages']['propertyDeleted'][] = $int_propertyId;
            }
*/
        }
$arr_result = array();

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if ($arr_result['bln_hasError']) {
            $this->obj_apiReceiver->error();
            $this->obj_apiReceiver->set_message($arr_result['arr_messages']);
            $this->obj_apiReceiver->set_httpResponseCode(200);
            return;
        }

        $this->obj_apiReceiver->success();
        $this->obj_apiReceiver->set_data($arr_result);
    }

	/**
	 * Changes product or variant stock. Expects the request details JSON formatted as POST parameter 'data'
	 *
	 * Scope: FE
	 *
	 * Allowed user types: apiUser
	 */
	protected function apiResource_changeStock()
	{
		$this->obj_apiReceiver->requireScope(['FE']);
		$this->obj_apiReceiver->requireUser(['apiUser']);

		$arr_dataRows = json_decode($_POST['data'], true);

		if (!count($arr_dataRows)) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message('data parameter missing or empty');
			return;
		}

		$arr_preprocessingResult = ls_shop_productManagementApiPreprocessor::preprocess($arr_dataRows, __FUNCTION__);

		if ($arr_preprocessingResult['bln_hasError']) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message($arr_preprocessingResult['arr_messages']);
			$this->obj_apiReceiver->set_httpResponseCode(200);
			return;
		}

		$arr_dataRows = $arr_preprocessingResult['arr_preprocessedDataRows'];

		$arr_result = $this->performStockChange($arr_dataRows);

		ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

		if ($arr_result['bln_hasError']) {
			$this->obj_apiReceiver->error();
			$this->obj_apiReceiver->set_message($arr_result['arr_messages']);
			$this->obj_apiReceiver->set_httpResponseCode(200);
			return;
		}

		$this->obj_apiReceiver->success();
		$this->obj_apiReceiver->set_data(null);
	}

	protected function performStockChange($arr_dataRows) {
		$arr_result = array(
			'bln_hasError' => false,
			'arr_messages' => array()
		);

		foreach (ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess as $str_dataRowType) {
			foreach ($arr_dataRows as $int_rowNumber => $arr_dataRow) {
				/*
				 * Since we perform the stock change for one data row type at a time, we skip rows that have the wrong type
				 */
				if ($arr_dataRow['type'] != $str_dataRowType) {
					continue;
				}

				try {
					switch ($str_dataRowType) {
						case 'product':
							ls_shop_productManagementApiHelper::changeStockForProductWithCode($arr_dataRow['productcode'], $arr_dataRow['changeStock']);
							break;

						case 'variant':
							ls_shop_productManagementApiHelper::changeStockForVariantWithCode($arr_dataRow['productcode'], $arr_dataRow['changeStock']);
							break;
					}
				} catch (\Exception $e) {
					$arr_result['bln_hasError'] = true;
					$arr_result['arr_messages'][$int_rowNumber + 1] = $e->getMessage();
				}
			}
		}

		return $arr_result;
	}

	protected function performDeletion($arr_dataRows) {
		$arr_result = array(
			'bln_hasError' => false,
			'arr_messages' => array()
		);

		foreach (ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess as $str_dataRowType) {
			foreach ($arr_dataRows as $int_rowNumber => $arr_dataRow) {
				/*
				 * Since we perform the deletion for one data row type at a time, we skip rows that have the wrong type
				 */
				if ($arr_dataRow['type'] != $str_dataRowType) {
					continue;
				}

				try {
					switch ($str_dataRowType) {
						case 'product':
							ls_shop_productManagementApiHelper::deleteProductWithCode($arr_dataRow['productcode']);
							break;

						case 'variant':
							ls_shop_productManagementApiHelper::deleteVariantWithCode($arr_dataRow['productcode']);
							break;

						case 'productLanguage':
							ls_shop_productManagementApiHelper::deleteProductLanguageWithCode($arr_dataRow['parentProductcode'], $arr_dataRow['language']);
							break;

						case 'variantLanguage':
							ls_shop_productManagementApiHelper::deleteVariantLanguageWithCode($arr_dataRow['parentProductcode'], $arr_dataRow['language']);
							break;
					}
				} catch (\Exception $e) {
					$arr_result['bln_hasError'] = true;
					$arr_result['arr_messages'][$int_rowNumber + 1] = $e->getMessage();
				}
			}
		}

		return $arr_result;
	}

	protected function performImport($arr_dataRows)
	{
		$arr_result = array(
			'bln_hasError' => false,
			'arr_messages' => array(),
            'arr_idsInsertedOrUpdated' => array()
		);
		$int_idInsertedOrUpdated = 0;

		foreach (ls_shop_productManagementApiHelper::$dataRowTypesInOrderToProcess as $str_dataRowType) {
			foreach ($arr_dataRows as $int_rowNumber => $arr_dataRow) {
				/*
				 * Since we import one data row type at a time, we skip rows that have the wrong type
				 */
				if ($arr_dataRow['type'] != $str_dataRowType) {
					continue;
				}
                $int_idInsertedOrUpdated = 0;

				try {
					switch ($str_dataRowType) {
						case 'product':
							ls_shop_productManagementApiHelper::insertOrUpdateProductRecord($arr_dataRow, $int_idInsertedOrUpdated);
							break;

						case 'variant':
							ls_shop_productManagementApiHelper::insertOrUpdateVariantRecord($arr_dataRow);
							break;

						case 'productLanguage':
							ls_shop_productManagementApiHelper::writeProductLanguageData($arr_dataRow);
							break;

						case 'variantLanguage':
							ls_shop_productManagementApiHelper::writeVariantLanguageData($arr_dataRow);
							break;
					}

					//Neue oder bestehende Ids zurückgeben (da der Connector sie für das PK-Mapping benötigt)
                    $arr_result['arr_idsInsertedOrUpdated'][$int_rowNumber + 1] = $int_idInsertedOrUpdated;

				} catch (\Exception $e) {
					$arr_result['bln_hasError'] = true;
					$arr_result['arr_messages'][$int_rowNumber + 1] = $e->getMessage();
				}
			}
		}

		ls_shop_productManagementApiHelper::translateRecommendedProductCodesInIDs();

		return $arr_result;
	}

	/*
	 * Führt den Import von Kategorien in die tl_page durch
	 *
	 * Rückgabe: Ergebnisarray mit evtl. Fehlermeldungen oder einer Liste der eingetragenen Datensatz IDs
	 * */
    protected function performCategoryImport($arr_dataRows)
    {

        $int_lastInsertId = '';

        $arr_result = array(
            'bln_hasError' => false,
            'arr_messages' => array()
        );

            foreach ($arr_dataRows as $int_rowNumber => $arr_dataRow) {
                /*
                 * Since we import one data row type at a time, we skip rows that have the wrong type
                 */


                //gehört der Type zu den gültigen ?
                if ( !in_array($arr_dataRow['type'], ls_shop_productManagementApiHelper::$dataCategoryRowTypesInOrderToProcess )) {
                    throw new \Exception('unknown/invalid page Type: '.$arr_dataRow['type']);
                }

                try {
                    switch ($arr_dataRow['type']) {
                        case 'regular':
                            // TODO: prüfen, ob die anderen Pagetypen überhaupt notwendig sind
//01.02.2021 nur Regular
                        #, 'root', 'error_404', 'error_403', 'error_401':

                            $int_lastInsertId = ls_shop_productManagementApiHelper::insertOrUpdateCategoryRecord($arr_dataRow);

                            $arr_result['arr_messages']['lastInsertId'][] = $int_lastInsertId;
                            break;
/*
                        case 'variant':
                            ls_shop_productManagementApiHelper::insertOrUpdateVariantRecord($arr_dataRow);
                            break;

                        case 'productLanguage':
                            ls_shop_productManagementApiHelper::writeProductLanguageData($arr_dataRow);
                            break;

                        case 'variantLanguage':
                            ls_shop_productManagementApiHelper::writeVariantLanguageData($arr_dataRow);
                            break;
*/
                    }
                } catch (\Exception $e) {
                    $arr_result['bln_hasError'] = true;
                    $arr_result['arr_messages'][$int_rowNumber + 1] = $e->getMessage();
                }
            }

        return $arr_result;
    }

    /*
	 * Führt die Aktualisierung von Grundeinstellungen->Währungen durch
	 *
	 * Rückgabe: Ergebnisarray mit evtl. Fehlermeldungen
	 * */
    protected function performCurrencyImport($arr_dataRows)
    {

        $arr_result = array(
            'bln_hasError' => false,
            'arr_messages' => array()
        );

        try {

            //TODO: die Währung kommt aus einer Konfigurationsdatei. Erst absprechen wie und ob das von hier aus eingetragen werden soll
            #$int_lastInsertId = ls_shop_productManagementApiHelper::insertOrUpdateCategoryRecord($arr_dataRow);
            $arr_result['arr_messages']['result'] = 'erfolg';

        } catch (\Exception $e) {
            $arr_result['bln_hasError'] = true;
            $arr_result['arr_messages']['result'] = $e->getMessage();
        }

        return $arr_result;
    }

    protected function performPropertyImport($arr_dataRows)
    {
\LeadingSystems\Helpers\lsErrorLog('performPropertyImport: $arr_dataRows', $arr_dataRows, 'perm');
        $arr_result = array(
            'bln_hasError' => false,
            'arr_messages' => array(),
            'arr_idsInsertedOrUpdated' => array()
        );
        $int_idInsertedOrUpdated = 0;

        foreach ($arr_dataRows as $int_rowNumber => $arr_dataRow) {

            $int_idInsertedOrUpdated = 0;

            try {
                //TODO: Hier den Eintrag des Merkmals umsetzen
\LeadingSystems\Helpers\lsErrorLog('performPropertyImport: $int_rowNumber', $int_rowNumber, 'perm');
\LeadingSystems\Helpers\lsErrorLog('performPropertyImport: $arr_dataRow', $arr_dataRow, 'perm');
/*
                $obj_dbres_page = \Database::getInstance()
                    ->prepare("
						SELECT		`id`
						FROM		`tl_page`
						WHERE		`alias` = ?
					")
                    ->execute($str_category);
*/

                //Spracheinträge, sofern übergeben
                if (isset($arr_dataRow['languageTitles']) AND $arr_dataRow['languageTitles'] != '' ) {
\LeadingSystems\Helpers\lsErrorLog('performPropertyImport: $arr_dataRow[\'languageTitles\'] ', $arr_dataRow['languageTitles'], 'perm');
                //TODO: Hier den Eintrag der Spracheinträge des Merkmals umsetzen
/*
                    $obj_dbres_page = \Database::getInstance()
                        ->prepare("
						SELECT		`id`
						FROM		`tl_page`
						WHERE		`alias` = ?
					")
                        ->execute($str_category);
*/
                }



                //Neue oder bestehende Ids zurückgeben (da der Connector sie für das PK-Mapping benötigt)
                $arr_result['arr_idsInsertedOrUpdated'][$int_rowNumber + 1] = $int_idInsertedOrUpdated;

            } catch (\Exception $e) {
                $arr_result['bln_hasError'] = true;
                $arr_result['arr_messages'][$int_rowNumber + 1] = $e->getMessage();
            }
        }



        return $arr_result;
    }


    protected function performPropertyValueImport($arr_dataRows)
    {
\LeadingSystems\Helpers\lsErrorLog('performPropertyValueImport: $arr_dataRows', $arr_dataRows, 'perm');
        $arr_result = array(
            'bln_hasError' => false,
            'arr_messages' => array(),
            'arr_idsInsertedOrUpdated' => array()
        );
        $int_idInsertedOrUpdated = 0;

        foreach ($arr_dataRows as $int_rowNumber => $arr_dataRow) {

            $int_idInsertedOrUpdated = 0;

            try {
                //TODO: Hier den Eintrag des Merkmalswerts umsetzen
\LeadingSystems\Helpers\lsErrorLog('performPropertyValueImport: $int_rowNumber', $int_rowNumber, 'perm');
\LeadingSystems\Helpers\lsErrorLog('performPropertyValueImport: $arr_dataRow', $arr_dataRow, 'perm');
/*
                $obj_dbres_page = \Database::getInstance()
                    ->prepare("
                        SELECT		`id`
                        FROM		`tl_page`
                        WHERE		`alias` = ?
                    ")
                    ->execute($str_category);
*/

                //Spracheinträge, sofern übergeben
                if (isset($arr_dataRow['languageTitles']) AND $arr_dataRow['languageTitles'] != '' ) {
\LeadingSystems\Helpers\lsErrorLog('performPropertyValueImport: $arr_dataRow[\'languageTitles\'] ', $arr_dataRow['languageTitles'], 'perm');
                    //TODO: Hier den Eintrag der Spracheinträge der Merkmals-Werte umsetzen
/*
                    $obj_dbres_page = \Database::getInstance()
                        ->prepare("
                        SELECT		`id`
                        FROM		`tl_page`
                        WHERE		`alias` = ?
                    ")
                        ->execute($str_category);
*/
                }

                //Neue oder bestehende Ids zurückgeben (da der Connector sie für das PK-Mapping benötigt)
                $arr_result['arr_idsInsertedOrUpdated'][$int_rowNumber + 1] = $int_idInsertedOrUpdated;

            } catch (\Exception $e) {
                $arr_result['bln_hasError'] = true;
                $arr_result['arr_messages'][$int_rowNumber + 1] = $e->getMessage();
            }
        }

        return $arr_result;
    }
}