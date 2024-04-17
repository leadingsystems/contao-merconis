<?php

namespace Merconis\Core;

use Contao\StringUtil;

class ls_shop_beModule_collectivePurchase extends \BackendModule
{

	protected function compile() {
		\System::loadLanguageFile('be_productSearch');
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





            $pid = $this->createVariant($_SESSION['lsShop']['beModule_collectivePurchase']['values']['variantId']);

            $this->createProduct($pid);

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


        \Database::getInstance()
            ->prepare("
                ALTER TABLE tmp_tl_ls_shop_product MODIFY id INT(10) NULL;
            ")
            ->execute();

        \Database::getInstance()
            ->prepare("
                UPDATE tmp_tl_ls_shop_product SET id = NULL;
            ")
            ->execute();


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

        dump($objVariant);

        \Database::getInstance()
            ->prepare("
                UPDATE tmp_tl_ls_shop_variant 
                SET id = NULL,
                    lsShopVariantCode = ?;
            ")
            ->execute("sk".$objVariant['id']."#".$objVariant['lsShopVariantCode']);

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