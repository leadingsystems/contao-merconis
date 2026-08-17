<?php
namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Merconis\Core\ls_shop_generalHelper;

class ListOperationProductListener extends Backend
{
    const STATUS_DRAFT = 'draft';
    const STATUS_KOMMENDE = 'kommende';
    const STATUS_AKTIVE_NO_ORDER = 'aktive-no-order';
    const STATUS_AKTIVE = 'aktive';
    const STATUS_ABGELAUFENE_NO_ORDER = 'abgelaufene-no-order';
    const STATUS_ABGELAUFENE = 'abgelaufene';

    public function __construct() {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    //disable edit all
    public function all(
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
    ){
        return '';
    }

    public function edit(
        array $product,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
        ?array $childRecordIds,
        bool $circularReference,
        ?string $previous,
        ?string $next,
        DataContainer $dc
    ): string
    {

        $variant = self::getVariant($product['id']);
        $status = self::getStatus($product, $variant);

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $product['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }


    public function editheader(
        array $product,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
        ?array $childRecordIds,
        bool $circularReference,
        ?string $previous,
        ?string $next,
        DataContainer $dc
    ): string
    {

        $variant = self::getVariant($product['id']);
        $status = self::getStatus($product, $variant);

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $product['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }


    public function delete(
        array $product,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
        ?array $childRecordIds,
        bool $circularReference,
        ?string $previous,
        ?string $next,
        DataContainer $dc
    ): string
    {

        $variant = self::getVariant($product['id']);
        $status = self::getStatus($product, $variant);

        if(
            $status == self::STATUS_AKTIVE ||
            $status == self::STATUS_ABGELAUFENE
        ){
            return '';
        }


        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $product['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }



    public function toggle(
        array $product,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
        ?array $childRecordIds,
        bool $circularReference,
        ?string $previous,
        ?string $next,
        DataContainer $dc
    ): string
    {

        $variant = self::getVariant($product['id']);
        $status = self::getStatus($product, $variant);

        return self::toggleIcon($product, $href, $label, $title, $icon, $attributes);
    }


    private function getVariant($productId){
        $selectStatementVariant= \Database::getInstance()
            ->prepare("
                            SELECT * FROM tl_ls_shop_variant
                            WHERE pid = ?
                            LIMIT 1

                        ")
            ->execute($productId);
        return $selectStatementVariant->fetchAllAssoc()[0];
    }

    public static function getStatus($product, $variant){

        $variantId = $variant['id'];
        $productId = $product['id'];

        $currenttime = time();

        //this fields are only empty if this variant is not saved because they are mandatory
        if(!$variant['lsShopRuntimeFrom'] && !$variant['lsShopRuntimeUntil']){
            return self::STATUS_DRAFT;
        }

        if($currenttime < $variant['lsShopRuntimeFrom']){
            return self::STATUS_KOMMENDE;
        }

        if($currenttime >= $variant['lsShopRuntimeFrom'] && $currenttime <= $variant['lsShopRuntimeUntil']){

            if(self::hasOrder($productId, $variantId)){
                return self::STATUS_AKTIVE;
            }else{
                return self::STATUS_AKTIVE_NO_ORDER;
            }
        }

        if($currenttime > $variant['lsShopRuntimeUntil']){

            if(self::hasOrder($productId, $variantId)){
                return self::STATUS_ABGELAUFENE;
            }else{
                return self::STATUS_ABGELAUFENE_NO_ORDER;
            }
        }
    }

    //if this product has at least one order -> return true
    private static function hasOrder($productId, $variantId){
        $selectStatementVariant= \Database::getInstance()
            ->prepare("
                            SELECT * FROM tl_ls_shop_orders_items
                            WHERE productVariantID = ?
                            LIMIT 1

                        ")
            ->execute($productId.'-'.$variantId);
        return boolval($selectStatementVariant->fetchAllAssoc()[0]);
    }

    public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
        if (strlen(\Input::get('tid'))) {
            $this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
            $this->redirect($this->getReferer());
        }



        if (!$this->User->hasAccess('tl_ls_shop_product::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.Backend::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ';
    }

    public function toggleVisibility($intId, $blnVisible) {
        if (!$this->User->hasAccess('tl_ls_shop_product::published', 'alexf')) {
            \System::log('Not enough permissions to publish/unpublish product ID "'.$intId.'"', 'tl_ls_shop_product toggleVisibility', TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        ls_shop_generalHelper::saveLastBackendDataChangeTimestamp();

        if (is_array($GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_ls_shop_product']['fields']['published']['save_callback'] as $callback) {
                $this->import($callback[0]);
                $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $this);
            }
        }

        // Update the database
        \Database::getInstance()->prepare("UPDATE tl_ls_shop_product SET tstamp=". time() .", published='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
            ->execute($intId);
    }
}