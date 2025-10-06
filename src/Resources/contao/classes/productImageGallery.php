<?php

namespace Merconis\Core;

use Contao\File;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\System;
use LeadingSystems\Helpers\ls_helpers_controller;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;
use LeadingSystems\MerconisBundle\Cache\MerconisCacheHandler;

class productImageGallery extends Frontend {

    const CACHE_VERSION = 'gallery_v1';

    //src for unprocessed Images
    protected $mainImageSRC = false;
    protected $multiSRC = array();

    //processed Images
    protected $mainImage = false;
    protected $ls_images = array(); // the array holding the processed images

    protected $ls_imageLimit = 0;

    protected $arrImgSuffixes = array('jpg', 'jpeg', 'JPG', 'JPEG', 'gif', 'GIF', 'png', 'PNG');

    protected $originalSRC = false;

    //new, onSale
    protected $arrOverlays = array();

    protected $sortingRandomizer = 0;

    protected $ls_moreImagesSortBy = '';


    public function __construct($obj_productOrVariant, $ls_moreImagesSortBy = false, $ls_imageLimit = 0) {
        parent::__construct();

        if (!is_object($obj_productOrVariant)) {
            return;
        }

        $str_mainImageKey = $obj_productOrVariant->_objectType === 'variant' ? 'lsShopProductVariantMainImage' : 'lsShopProductMainImage';
        $str_moreImagesKey = $obj_productOrVariant->_objectType === 'variant' ? 'lsShopProductVariantMoreImages' : 'lsShopProductMoreImages';

        $mainImageSRC = isset($obj_productOrVariant->mainData[$str_mainImageKey]) && $obj_productOrVariant->mainData[$str_mainImageKey] ? ls_getFilePathFromVariableSources($obj_productOrVariant->mainData[$str_mainImageKey]) : null;



        $multiSRC = ls_shop_generalHelper::getAllProductImages($obj_productOrVariant, $obj_productOrVariant->_code, null, $obj_productOrVariant->mainData[$str_moreImagesKey]);



        $this->ls_imageLimit = $ls_imageLimit;

        if(!is_array($arrOverlays ?? null)){
            $arrOverlays = array();
        }

        if ($obj_productOrVariant->_isNew) {
            $arrOverlays[] = 'isNew';
        }
        if ($obj_productOrVariant->_isOnSale) {
            $arrOverlays[] = 'isOnSale';
        }

        if (!is_array($multiSRC)) {
            $multiSRC = array();
        }

        $this->multiSRC = $multiSRC;
        $this->mainImageSRC = $mainImageSRC;

        $this->ls_moreImagesSortBy = $ls_moreImagesSortBy  ? $ls_moreImagesSortBy : $GLOBALS['TL_CONFIG']['ls_shop_imageSortingStandardDirection'];

        $this->sortingRandomizer = rand(0,99999);

        $this->Template = new FrontendTemplate($this->strTemplate);

        $this->arrOverlays = $arrOverlays;

        $this->Template->images = array();

        $this->lsShopGetProcessedImages();

        if(!$this->ls_images && !$this->mainImageSRC && $obj_productOrVariant->_objectType === 'variant'){
            $str_mainImageKey = 'lsShopProductMainImage';

            $mainImageSRC = isset($obj_productOrVariant->_objParentProduct->mainData[$str_mainImageKey]) && $obj_productOrVariant->_objParentProduct->mainData[$str_mainImageKey] ? ls_getFilePathFromVariableSources($obj_productOrVariant->_objParentProduct->mainData[$str_mainImageKey]) : null;

            $this->mainImageSRC = $mainImageSRC;

            $str_moreImagesKey = 'lsShopProductMoreImages';
            $multiSRC = ls_shop_generalHelper::getAllProductImages($obj_productOrVariant->_objParentProduct, $obj_productOrVariant->_objParentProduct->_code, null, $obj_productOrVariant->_objParentProduct->mainData[$str_moreImagesKey]);

            if (!is_array($multiSRC)) {
                $multiSRC = array();
            }

            $this->multiSRC = $multiSRC;

            $this->lsShopGetProcessedImages();
        }
        
    }

    //returns the MainImage
    public function getMainImage(){
        if(!$this->mainImage){
            if($this->mainImageSRC){
                $this->mainImage = $this->processSingleImage($this->mainImageSRC);
            }else if(!empty($this->getMoreImages())){
                $this->mainImage = $this->getMoreImages()[0];
            }else if(isset($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])){
                $this->mainImage = $this->processSingleImage(FilesModel::findByUuid(ls_helpers_controller::uuidFromId($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage']))->path);
            }
        }
        return $this->mainImage;
    }

    //returns All Images MainImage+MoreImages
    public function getImages(){
        $arrImg = $this->ls_images;
        if ($this->hasMoreImages() && !empty($this->mainImageSRC)) {
            array_unshift($arrImg, $this->getMainImage());
        }
        else if (!$this->hasMoreImages()) {
            array_unshift($arrImg, $this->getMainImage());
        }

        if ($this->ls_imageLimit) {
            $arrImg = array_slice($arrImg, 0, $this->ls_imageLimit);
        }
        return $arrImg;
    }

    //returns MoreImages (without MainImage)
    public function getMoreImages(){

        $arrImg = $this->ls_images;

        if ($this->ls_imageLimit) {
            $arrImg = array_slice($arrImg, 0, $this->ls_imageLimit);
        }
        return $arrImg;
    }

    public function hasMainImage(){
        if($this->getMainImage()){
            return true;
        }
        return false;
    }

    public function hasMoreImages(){
        if($this->getMoreImages()){
            return true;
        }
        return false;
    }

    public function hasImages(){
        if($this->getImages()){
            return true;
        }
        return false;
    }

    public function getMainImageUnprocessed(){
        return $this->mainImageSRC;
    }

    public function getMoreImagesUnprocessed(){
        return $this->multiSRC;
    }


    protected function lsShopGetProcessedImages() {
        // Prepare cache tags/handle (MerconisCacheHandler value mode)
        $cacheHandle = null;
        try {
            /** @var MerconisCacheHandler $cacheHandler */
            $cacheHandler = System::getContainer()->get(MerconisCacheHandler::class);
            /** @var PageModel $objPage */
            global $objPage;
            $language = is_object($objPage) && isset($objPage->language) ? $objPage->language : 'xx';
            $overlays = $this->arrOverlays;
            if (!is_array($overlays)) {
                $overlays = array();
            }
            sort($overlays);
            $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
            $signature = array();
            foreach ((array) $this->multiSRC as $path) {
                $abs = $str_projectDir . '/' . $path;
                $mtime = is_file($abs) ? @filemtime($abs) : 0;
                $signature[] = array('p' => $path, 'm' => (int) $mtime);
            }
            $mainSig = null;
            if ($this->mainImageSRC) {
                $miAbs = $str_projectDir . '/' . $this->mainImageSRC;
                $mainSig = array('p' => $this->mainImageSRC, 'm' => is_file($miAbs) ? (int)@filemtime($miAbs) : 0);
            }
            $entityParams = array(
                'sort' => (string) $this->ls_moreImagesSortBy,
                'ov' => $overlays,
                'sig' => $signature,
                'mis' => $mainSig,
                'incMain' => true
            );
            $ttlSeconds = 21600;

            $cacheHandle = $cacheHandler->createForRecipe('gallery_images', $entityParams, $ttlSeconds);
            list($hit, $cached) = $cacheHandle->getValueOrStart();
            if ($hit && is_array($cached) && isset($cached['images'])) {
                foreach ($cached['images'] as $imgArr) {
                    $imgObj = new \stdClass();
                    $imgObj->name = $imgArr['name'];
                    $imgObj->originalSRC = $imgArr['originalSRC'];
                    $imgObj->arrOverlays = $imgArr['arrOverlays'];
                    $imgObj->singleSRC = $imgArr['singleSRC'];
                    $imgObj->alt = $imgArr['alt'];
                    $imgObj->title = $imgArr['title'];
                    $imgObj->imageUrl = $imgArr['imageUrl'];
                    $imgObj->caption = $imgArr['caption'];
                    $imgObj->mtime = $imgArr['mtime'];
                    $imgObj->randomSortingValue = $imgArr['randomSortingValue'];
                    $this->ls_images[] = $imgObj;
                }
                if (isset($cached['mainImage']) && is_array($cached['mainImage'])) {
                    $mi = $cached['mainImage'];
                    $miObj = new \stdClass();
                    $miObj->name = $mi['name'];
                    $miObj->originalSRC = $mi['originalSRC'];
                    $miObj->arrOverlays = $mi['arrOverlays'];
                    $miObj->singleSRC = $mi['singleSRC'];
                    $miObj->alt = $mi['alt'];
                    $miObj->title = $mi['title'];
                    $miObj->imageUrl = $mi['imageUrl'];
                    $miObj->caption = $mi['caption'];
                    $miObj->mtime = $mi['mtime'];
                    $miObj->randomSortingValue = $mi['randomSortingValue'];
                    $this->mainImage = $miObj;
                }
                return;
            }
        } catch (\Throwable $t) {
            // Ignore cache service errors and continue without cache
            $cacheHandle = null;
        }

        // Get all images (non-cached or cache miss)
        foreach ($this->multiSRC as $file) {
            $processedImage = $this->processSingleImage($file);
            if ($processedImage) {
                $this->ls_images[] = $processedImage;
            }
        }

        // Sort array
        switch ($this->ls_moreImagesSortBy) {
            default:
            case 'name_asc':
                uasort($this->ls_images, function($a, $b) {
                    return strnatcasecmp($a->name, $b->name);
                });
                break;

            case 'name_desc':
                uasort($this->ls_images, function($a, $b) {
                    return -strnatcasecmp($a->name, $b->name);
                });
                break;

            case 'date_asc':
                uasort($this->ls_images, function($a, $b) {
                    if ($a->mtime == $b->mtime) {
                        return 0;
                    }
                    return ($a->mtime < $b->mtime) ? -1 : 1;
                });
                break;

            case 'date_desc':
                uasort($this->ls_images, function($a, $b) {
                    if ($a->mtime == $b->mtime) {
                        return 0;
                    }
                    return ($a->mtime < $b->mtime) ? 1 : -1;
                });
                break;

            case 'random':
                uasort($this->ls_images, function($a, $b) {
                    return strcmp($a->randomSortingValue, $b->randomSortingValue);
                });
                break;

            case 'none':
                break;
        }

        // sort videos to end of image list (stable partition)
        $images = array();
        $videos = array();
        foreach ($this->ls_images as $image) {
            // test if video (originalSRC is false for all images)
            if ($image->originalSRC !== false) {
                $videos[] = $image;
            } else {
                $images[] = $image;
            }
        }
        $this->ls_images = array_merge($images, $videos);

        // Determine main image now if not set so we can store it in cache as well
        if (!$this->mainImage) {
            if ($this->mainImageSRC) {
                $this->mainImage = $this->processSingleImage($this->mainImageSRC);
            } else if (!empty($this->ls_images)) {
                $this->mainImage = $this->ls_images[0];
            } else if (isset($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])) {
                $this->mainImage = $this->processSingleImage(FilesModel::findByUuid(ls_helpers_controller::uuidFromId($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage']))->path);
            }
        }

        // Store in persistent cache
        try {
            $toStoreImages = array();
            foreach ($this->ls_images as $imgObj) {
                $toStoreImages[] = array(
                    'name' => $imgObj->name,
                    'originalSRC' => $imgObj->originalSRC,
                    'arrOverlays' => $imgObj->arrOverlays,
                    'singleSRC' => $imgObj->singleSRC,
                    'alt' => $imgObj->alt,
                    'title' => $imgObj->title,
                    'imageUrl' => $imgObj->imageUrl,
                    'caption' => $imgObj->caption,
                    'mtime' => $imgObj->mtime,
                    'randomSortingValue' => $imgObj->randomSortingValue
                );
            }
            $mainImageArr = null;
            if ($this->mainImage) {
                $mi = $this->mainImage;
                $mainImageArr = array(
                    'name' => $mi->name,
                    'originalSRC' => $mi->originalSRC,
                    'arrOverlays' => $mi->arrOverlays,
                    'singleSRC' => $mi->singleSRC,
                    'alt' => $mi->alt,
                    'title' => $mi->title,
                    'imageUrl' => $mi->imageUrl,
                    'caption' => $mi->caption,
                    'mtime' => $mi->mtime,
                    'randomSortingValue' => $mi->randomSortingValue
                );
            }
            $payload = array('images' => $toStoreImages, 'mainImage' => $mainImageArr);
            if ($cacheHandle) {
                $cacheHandle->storeValue($payload);
            }
        } catch (\Throwable $t) {
            // Ignore cache store errors
        }
    }

    protected function buildGalleryCacheKey() {
        /** @var PageModel $objPage */
        global $objPage;
        $language = is_object($objPage) && isset($objPage->language) ? $objPage->language : 'xx';
        $sortBy = (string) $this->ls_moreImagesSortBy;
        $overlays = $this->arrOverlays;
        if (!is_array($overlays)) {
            $overlays = array();
        }
        sort($overlays);

        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $signature = array();
        foreach ((array) $this->multiSRC as $path) {
            $abs = $str_projectDir . '/' . $path;
            $mtime = is_file($abs) ? @filemtime($abs) : 0;
            $signature[] = array('p' => $path, 'm' => (int) $mtime);
        }
        // main image signature (optional in key for safety)
        $mainSig = null;
        if ($this->mainImageSRC) {
            $miAbs = $str_projectDir . '/' . $this->mainImageSRC;
            $mainSig = array('p' => $this->mainImageSRC, 'm' => is_file($miAbs) ? (int)@filemtime($miAbs) : 0);
        }

        $keySeed = json_encode(array(
            'lang' => $language,
            'sort' => $sortBy,
            'ov' => $overlays,
            'sig' => $signature,
            'mis' => $mainSig,
            'incMain' => true
        ));

        return 'merconis.gallery.' . sha1($keySeed);
    }

    protected function processSingleImage($file) {
        if (isset($GLOBALS['merconis_globals']['cache'][__METHOD__][$file])) {
            return $GLOBALS['merconis_globals']['cache'][__METHOD__][$file];
        }
        /** @var PageModel $objPage */
        global $objPage;
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');

        //check if _cover is in name
        if (preg_match('/_cover/siU', $file)) {
            $parts = explode("_cover.", $file);
            //check of there is a image for this cover or not, if not then this will be used as a normal product image
            if (!preg_match('/\.mp4/siU', $file) && file_exists($str_projectDir.'/'.$parts[0].".mp4")) {
                $GLOBALS['merconis_globals']['cache'][__METHOD__][$file] = false;
                return $GLOBALS['merconis_globals']['cache'][__METHOD__][$file];

            }
        }

        if (isset($this->ls_images[$file]) || !file_exists($str_projectDir.'/'.$file)) {
            $GLOBALS['merconis_globals']['cache'][__METHOD__][$file] = false;
            return $GLOBALS['merconis_globals']['cache'][__METHOD__][$file];
        }


        if (!is_file($str_projectDir . '/' . $file)) {
            $GLOBALS['merconis_globals']['cache'][__METHOD__][$file] = false;
            return $GLOBALS['merconis_globals']['cache'][__METHOD__][$file];
        }

        $arrOverlays = $this->arrOverlays;

        $objFile = new File($file, true);

        /*
         * If the image is not a gd image we assume that it's a video. This means that images of the following types
         * can be used and everything else is handled as if it was a video: 'gif', 'jpg', 'jpeg', 'png'. This approach
         * can be used and everything else is handled as if it was a video: 'gif', 'jpg', 'jpeg', 'png'. This approach
         * is not exactly clean but it should be okay for now.
         *
         */
        if (!$objFile->isGdImage) {
            /*
             * This function returns the file object for the determined video cover image
             */
            $objFile = $this->lsShopGetVideoCover($file);

            /*
             * If the overlay image "isVideo" is not defined in the overlay array given as a parameter on class
             * instantiation (which is most likely never the case because noone would want to label every image as
             * a video) this overlay image type is being set here for this specific image because it actually is a video.
             */
            if (!in_array('isVideo', $arrOverlays)) {
                $arrOverlays[] = 'isVideo';
            }
        }
        /*
         * If the image is a gd image it is not handled as a video and therefore there's no original src
         */
        else {
            $this->originalSRC = false;
        }

        // Metadata cache per file+language using MerconisCacheHandler to avoid repeated DB lookups
        $arrMeta = array();
        try {
            /** @var MerconisCacheHandler $cacheHandler */
            $cacheHandler = System::getContainer()->get(MerconisCacheHandler::class);
            $entityParams = array(
                'file' => ($this->originalSRC ? $this->originalSRC : $file)
            );
            $cacheHandle = $cacheHandler->createForRecipe('gallery_filemeta', $entityParams, 21600);
            list($metaHit, $metaVal) = $cacheHandle->getValueOrStart();
            if ($metaHit) {
                $arrMeta = (array) $metaVal;
            } else {
                $objFileModel = FilesModel::findMultipleByPaths(array($this->originalSRC ? $this->originalSRC : $file));
                if (is_object($objFileModel)) {
                    $objFileModel->first();
                    $arrMeta = $this->getMetaData($objFileModel->meta, $objPage->language);
                }
                $cacheHandle->storeValue($arrMeta);
            }
        } catch (\Throwable $t) {
            $objFileModel = FilesModel::findMultipleByPaths(array($this->originalSRC ? $this->originalSRC : $file));
            if (is_object($objFileModel)) {
                $objFileModel->first();
                $arrMeta = $this->getMetaData($objFileModel->meta, $objPage->language);
            }
        }

        /*
         * If we have a gd image (which should be the case for video covers too), we add
         * the image to the images array
         */



        if ($objFile->isGdImage) {
            $objImage = new \stdClass();
            $objImage->name = $objFile->basename;
            $objImage->originalSRC = $this->originalSRC;
            $objImage->arrOverlays = $arrOverlays;
            $objImage->singleSRC = $file;
            $objImage->alt = $arrMeta['alt'] ?? '';
            $objImage->title = $arrMeta['title'] ?? '';
            $objImage->imageUrl = $arrMeta['link'] ?? '';
            $objImage->caption = $arrMeta['caption'] ?? '';
            $objImage->mtime = $objFile->mtime;
            $objImage->randomSortingValue = md5($objFile->basename.$this->sortingRandomizer);
            $GLOBALS['merconis_globals']['cache'][__METHOD__][$file] = $objImage;
            return $GLOBALS['merconis_globals']['cache'][__METHOD__][$file];

        }

        $GLOBALS['merconis_globals']['cache'][__METHOD__][$file] = false;
        return $GLOBALS['merconis_globals']['cache'][__METHOD__][$file];
    }

    /*
     * This function is called if an image is actually a video and therefore the cover image is needed
     * for further processing.
     */
    protected function lsShopGetVideoCover(&$filename) {
        $this->originalSRC = $filename;
        $coverFile = false;

        /*
         * determine the cover image filename without a suffix by replacing the last dot and suffix of
         * the video file's filename with the string '_cover'.
         */
        $coverFilename = preg_replace('(\..*$)', '_cover', $filename);

        /*
         * Walk throught the image suffix array and check whether there's
         * a file named with the coverFilename and the respective image suffix.
         */
        foreach ($this->arrImgSuffixes as $suffix) {
            $coverFilename2 = $coverFilename.'.'.$suffix;
            if (is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $coverFilename2)) {
                /*
                 * If we have a match, that's our cover filename, so we break the loop and use this value
                 */
                $coverFile = $coverFilename2;
                break;
            }
        }

        /*
         * If we did not find a cover image, we use the system image
         */
        if (!$coverFile) {
            $coverFile = ls_shop_generalHelper::getSystemImage('videoDummyCover');
        }

        /*
         * If we still don't have a cover filename because even the system image is not available, we
         * use the given filename, no matter what.
         */
        $coverFile = $coverFile ? $coverFile : $filename;

        $filename = $coverFile;
        return new File($coverFile, true);
    }
}