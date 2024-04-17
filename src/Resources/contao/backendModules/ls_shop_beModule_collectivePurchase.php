<?php

namespace Merconis\Core;

use Contao\StringUtil;

class ls_shop_beModule_collectivePurchase extends \BackendModule
{
	protected $strTemplate = 'beModule_collectivePurchase';

	protected function compile() {
		\System::loadLanguageFile('be_collectivePurchase');
		\System::loadLanguageFile('tl_ls_shop_product');
		$this->Template->request = StringUtil::ampersand(\Environment::get('request'), true);

		$objWidgets = array();
		$widgets = array();

		/*
		 * Erzeugen der Widgets für die Suchfelder
		 * sowie Verarbeitung evtl. übergebener Werte
		 */

		$objWidgets['variantId'] = new \TextField();
		$objWidgets['variantId']->label = $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopVariantId'][0];
		$objWidgets['variantId']->id = 'variantId';
		$objWidgets['variantId']->name = 'variantId';
		$objWidgets['variantId']->value = \Input::post('variantId') ? \Input::post('variantId') : (isset($_SESSION['lsShop']['beModule_productSearch']['values']['lsShopVariantId']) ? $_SESSION['lsShop']['beModule_productSearch']['values']['lsShopVariantId'] : '');

		if (\Input::post('FORM_SUBMIT') == 'beModule_collectivePurchase') {
			$_SESSION['lsShop']['beModule_collectivePurchase']['values']['variantId'] = \Input::post('variantId') ? \Input::post('variantId') : '';

            $variantId = $this->createVariant($_SESSION['lsShop']['beModule_collectivePurchase']['values']['variantId']);
            $productId = $this->createProduct($variantId);

            \Database::getInstance()
                ->prepare("
                UPDATE tl_ls_shop_variant 
                SET pid = ?
                WHERE id = ?
            ")
                ->execute(
                    $productId,
                    $variantId
                );

            $_SESSION['BE_CollectivePurchase'] = true;

			\Controller::redirect(ls_shop_generalHelper::getUrl(false, array('page')));
		}

		$widgets['variantId']['widget'] = $objWidgets['variantId']->parse();
		
		$this->Template->widgets = $widgets;
		/*
		 * Ende Erzeugen der Widgets
		 */

	}

    //create variant return parentId
    private function createProduct($pid){

        \Database::getInstance()
            ->prepare("
                CREATE TEMPORARY TABLE tmp_tl_ls_shop_product
                SELECT *
                FROM tl_ls_shop_product
                WHERE id = ?
            ")
            ->execute(
                $pid
            );

        $selectStatement= \Database::getInstance()
            ->prepare("
                    SELECT * FROM tmp_tl_ls_shop_product LIMIT 1
                ")
            ->execute();
        $objProduct = $selectStatement->fetchAllAssoc()[0];


        \Database::getInstance()
            ->prepare("
                ALTER TABLE tmp_tl_ls_shop_product MODIFY id INT(10) NULL;
            ")
            ->execute();

        \Database::getInstance()
            ->prepare("
                UPDATE tmp_tl_ls_shop_product 
                SET id = NULL,
                    productTypeCollectiveOrder = true,
                    variationGroupCode = NULL,
                    lsShopProductCode = ?,
                    alias = ?,
                    alias_de = ?,
                    pages = ?;
            ")
            ->execute(
                "sk".$objProduct['id']."#".$objProduct['lsShopVariantCode'],
                "sk".$objProduct['id']."#".$objProduct['alias'],
                "sk".$objProduct['id']."#".$objProduct['alias_de'],
                $GLOBALS['TL_CONFIG']['ls_shop_collectivePurchasePages']
            );

        $objQuery = \Database::getInstance()
            ->prepare("
                    INSERT INTO tl_ls_shop_product
                    SELECT * FROM tmp_tl_ls_shop_product LIMIT 1
                ")
            ->execute();

        $insertID = $objQuery->insertId;

        return $insertID;
    }

    //TODO: sk[DatenbankID]#[oldProductCode]
    //create variant return parentId
    private function createVariant($lsShopVariantCode) {

        \Database::getInstance()
            ->prepare("
                CREATE TEMPORARY TABLE tmp_tl_ls_shop_variant
                SELECT *
                FROM tl_ls_shop_variant
                WHERE lsShopVariantCode = ?
            ")
            ->execute(
                $lsShopVariantCode
            );

        $selectStatement= \Database::getInstance()
            ->prepare("
                    SELECT * FROM tmp_tl_ls_shop_variant LIMIT 1
                ")
            ->execute();
        $objVariant = $selectStatement->fetchAllAssoc()[0];

        \Database::getInstance()
            ->prepare("
                ALTER TABLE tmp_tl_ls_shop_variant MODIFY id INT(10) NULL;
            ")
            ->execute();

        \Database::getInstance()
            ->prepare("
                UPDATE tmp_tl_ls_shop_variant 
                SET id = NULL,
                    lsShopVariantCode = ?,
                    alias = ?,
                    alias_de = ?,
                    lsShopVariantPriceOld = ?,
                    useOldPrice = true;
            ")
            ->execute(
                "sk".$objVariant['id']."#".$objVariant['lsShopVariantCode'],
                "sk".$objVariant['id']."#".$objVariant['alias'],
                "sk".$objVariant['id']."#".$objVariant['alias_de'],
                $objVariant['lsShopVariantPrice']
            );

        \Database::getInstance()
            ->prepare("
                    INSERT INTO tl_ls_shop_variant
                    SELECT * FROM tmp_tl_ls_shop_variant LIMIT 1
                ")
            ->execute();






        return $objVariant['pid'];
        //dump($objVariant);
        //dump($objVariant['pid']);

    }
}