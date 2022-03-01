<?php

namespace Merconis\Core;

use Contao\System;
use LeadingSystems\Helpers\ls_helpers_controller;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class productImageGallery extends \Frontend {

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

        $this->Template = new \FrontendTemplate($this->strTemplate);

        if(!is_array($arrOverlays)){
            $arrOverlays = array();
        }

        $this->arrOverlays = $arrOverlays;

        $this->Template->images = array();


        $this->lsShopGetProcessedImages();


    }

    //returns the MainImage
    public function getMainImage(){
        if(!$this->mainImage){
            if($this->mainImageSRC){
                $this->mainImage = $this->processSingleImage($this->mainImageSRC);
            }else if(!empty($this->getMoreImages())){
                $this->mainImage = $this->getMoreImages()[0];
            }else{
                $this->mainImage = $this->processSingleImage(\FilesModel::findByUuid(ls_helpers_controller::uuidFromId($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage']))->path);
            }
        }
        return $this->mainImage;
    }

    //returns All Images MainImage+MoreImages
    public function getImages(){
        $arrImg = $this->ls_images;
        if (!$this->hasMoreImages() || !empty($this->mainImageSRC)) {
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



    protected function process($file){
        if (@file_exists(TL_ROOT.'/'.$file)) {

            // Process single files
            if (is_file(TL_ROOT.'/'.$file)) {

                $this->ls_images[] = $this->processSingleImage($file);
            }

            // Process folders (not recursive, only the one given folder!)
            else {
                $subfiles = scan(TL_ROOT.'/'.$file);

                foreach ($subfiles as $subfile) {
                    $subfileName = $file . '/' . $subfile;

                    $this->ls_images[] = $this->processSingleImage($subfileName);
                }
            }
        }
    }

    protected function lsShopGetProcessedImages() {

        // Get all images
        foreach ($this->multiSRC as $file) {
            $this->ls_images[] = $this->processSingleImage($file);
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
        }

    }

    protected function processSingleImage($file) {
        /** @var \PageModel $objPage */
        global $objPage;


        if (preg_match('/_cover/siU', $file)) {
            return false;
        }

        if (isset($this->ls_images[$file]) || !file_exists(TL_ROOT.'/'.$file)) {
            return false;
        }


        if (!is_file(TL_ROOT . '/' . $file)) {
            return false;
        }

        $arrOverlays = $this->arrOverlays;

        $objFile = new \File($file, true);

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

        $objFileModel = \FilesModel::findMultipleByPaths(array($this->originalSRC ? $this->originalSRC : $file));
        $arrMeta = array();
        if (is_object($objFileModel)) {
            $objFileModel->first();
            $arrMeta = $this->getMetaData($objFileModel->meta, $objPage->language);
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
            $objImage->alt = $arrMeta['title'];
            $objImage->imageUrl = $arrMeta['link'];
            $objImage->caption = $arrMeta['caption'];
            $objImage->mtime = $objFile->mtime;
            $objImage->randomSortingValue = md5($objFile->basename.$this->sortingRandomizer);

            return $objImage;

        }

        return false;
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
            if (is_file(TL_ROOT . '/' . $coverFilename2)) {
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
        return new \File($coverFile, true);
    }
}