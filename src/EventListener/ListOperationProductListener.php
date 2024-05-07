<?php
namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;

class ListOperationProductListener
{
    const STATUS_DRAFT = 'draft';
    const STATUS_KOMMENDE = 'kommende';
    const STATUS_AKTIVE_NO_ORDER = 'aktive-no-order';
    const STATUS_AKTIVE = 'aktive';
    const STATUS_ABGELAUFENE_NO_ORDER = 'abgelaufene-no-order';
    const STATUS_ABGELAUFENE = 'abgelaufene';


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

        if(
            $status == self::STATUS_ABGELAUFENE ||
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
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

        if(
            $status == self::STATUS_ABGELAUFENE ||
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
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
            $status == self::STATUS_ABGELAUFENE ||
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
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


        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $product['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
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
}