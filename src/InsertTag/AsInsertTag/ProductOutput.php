<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\Input;
use Contao\System;
use Merconis\Core\ls_shop_generalHelper;
use Merconis\Core\ls_shop_productOutput;

#[AsInsertTag('shopproductoutput')]
#[AsInsertTag('shop_product_output')]
class ProductOutput extends InsertTag
{

	public function customInserttags($strTag, $params) {

        $arr_params = explode(',', $params[0]);
        $str_productVariantId = trim($arr_params[0]);
        $str_productVariantId = $this->convertTagToId($str_productVariantId);

        $str_templateToUse = isset($arr_params[1]) && $arr_params[1] ? trim($arr_params[1]) : '';

        $objProductOutput = new ls_shop_productOutput($str_productVariantId, 'overview', $str_templateToUse);
        $str_productOutput = $objProductOutput->parseOutput();

        $parser = System::getContainer()->get('contao.insert_tag.parser');
        return $parser->replace((string) $str_productOutput);
	}

    //converts tags(ids, codes, alias) to 'productId'-'variantId'
	private function convertTagToId($str_productTag, $what){

        //if current convert it to "productId-0"
        if ($str_productTag === 'current') {
            /*
             * Get product currently displayed in singleview if not productVariantId is given
             */
            $str_productAlias = Input::get('product');
            $int_productId = ls_shop_generalHelper::getProductIdForAlias($str_productAlias);
            if (!$int_productId) {
                return '';
            }
            $str_productVariantId = $int_productId.'-0';
        }
        if($what === "productId") {
            $str_productVariantId = $str_productTag;
        }
        //if productAlias convert it to "productId-0"
        if($what === "productAlias"){
            $int_productId = ls_shop_generalHelper::getProductIdForAlias($str_productTag);
            if (!$int_productId) {
                return '';
            }
            $str_productVariantId = $int_productId.'-0';
        }
        //if productCode convert it to "productId-0"
        if($what === "productCode" ){
            $int_productId = ls_shop_generalHelper::getProductIdForCode($str_productTag);
            if (!$int_productId) {
                return '';
            }
            $str_productVariantId = $int_productId.'-0';
        }
        //if variantAlias convert it to "productId-variantId"
        if($what === "variantAlias"){
            $int_variantId = ls_shop_generalHelper::getVariantIdForAlias($str_productTag);
            if (!$int_variantId) {
                return '';
            }
            $str_productVariantId = ls_shop_generalHelper::getProductIdForVariantId($int_variantId).'-'.$int_variantId;
        }
        //if variantCode convert it to "productId-variantId"
        if($what === "variantCode"){
            $int_variantId = ls_shop_generalHelper::getVariantIdForCode($str_productTag);
            if (!$int_variantId) {
                return '';
            }
            $str_productVariantId = ls_shop_generalHelper::getProductIdForVariantId($int_variantId).'-'.$int_variantId;
        }
        return $str_productVariantId;
    }
}
