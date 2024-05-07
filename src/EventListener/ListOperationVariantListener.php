<?php
namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;

class ListOperationVariantListener
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
        array $variant,
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

        $product = self::getProduct($variant['pid']);
        $status = ListOperationProductListener::getStatus($product, $variant);

        if(
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
        ){
            return '';
        }

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $variant['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }


    public function copy(
        array $variant,
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

        $product = self::getProduct($variant['id']);
        $status = ListOperationProductListener::getStatus($product, $variant);

        if(
            $status == self::STATUS_DRAFT ||
            $status == self::STATUS_KOMMENDE ||
            $status == self::STATUS_AKTIVE_NO_ORDER ||
            $status == self::STATUS_AKTIVE ||
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
        ){
            return '';
        }


        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $variant['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }


    public function cut(
        array $variant,
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

        $product = self::getProduct($variant['id']);
        $status = ListOperationProductListener::getStatus($product, $variant);

        if(
            $status == self::STATUS_DRAFT ||
            $status == self::STATUS_KOMMENDE ||
            $status == self::STATUS_AKTIVE_NO_ORDER ||
            $status == self::STATUS_AKTIVE ||
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
        ){
            return '';
        }


        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $variant['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }



    public function delete(
        array $variant,
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

        $product = self::getProduct($variant['id']);
        $status = ListOperationProductListener::getStatus($product, $variant);

        if(
            $status == self::STATUS_DRAFT ||
            $status == self::STATUS_KOMMENDE ||
            $status == self::STATUS_AKTIVE_NO_ORDER ||
            $status == self::STATUS_AKTIVE ||
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
        ){
            return '';
        }


        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $variant['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }

    public function toggle(
        array $variant,
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

        $product = self::getProduct($variant['id']);
        $status = ListOperationProductListener::getStatus($product, $variant);

        if(
            $status == self::STATUS_DRAFT ||
            $status == self::STATUS_KOMMENDE ||
            $status == self::STATUS_AKTIVE_NO_ORDER ||
            $status == self::STATUS_AKTIVE ||
            $status == self::STATUS_ABGELAUFENE_NO_ORDER
        ){
            return '';
        }


        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($href . '&amp;id=' . $variant['id']),
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }

    private function getProduct($productId){
        $selectStatementVariant= \Database::getInstance()
            ->prepare("
                            SELECT * FROM tl_ls_shop_product
                            WHERE id = ?
                            LIMIT 1

                        ")
            ->execute($productId);
        return $selectStatementVariant->fetchAllAssoc()[0];
    }

}