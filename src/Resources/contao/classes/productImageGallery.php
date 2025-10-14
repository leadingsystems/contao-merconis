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

class productImageGallery extends Frontend {

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

    /**
     * @var FrontendTemplate|null
     */
    protected $Template = null;


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

		/*
		 * If this is a variant without its own main image and without any more-images,
		 * immediately fall back to the parent product sources and process once.
		 * Otherwise, process the initially provided sources.
		 */
		if ($obj_productOrVariant->_objectType === 'variant' && !$this->mainImageSRC && empty($this->multiSRC)){
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
		} else {
			$this->lsShopGetProcessedImages();
		}
        
    }

    //returns the MainImage
    public function getMainImage(): \stdClass|false {
        if(!$this->mainImage && !$this->hasMoreImages() && isset($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])){
            $this->mainImage = $this->processSingleImage(FilesModel::findByUuid(ls_helpers_controller::uuidFromId($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage']))->path);
        }
        return $this->mainImage ?: false;
    }

    //returns All Images MainImage+MoreImages
    public function getImages(): array {
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
    public function getMoreImages(): array {

        $arrImg = $this->ls_images;

        if ($this->ls_imageLimit) {
            $arrImg = array_slice($arrImg, 0, $this->ls_imageLimit);
        }
        return $arrImg;
    }

    public function hasMainImage(): bool {
        if($this->getMainImage()){
            return true;
        }
        return false;
    }

    public function hasMoreImages(): bool {
        if($this->getMoreImages()){
            return true;
        }
        return false;
    }

    public function hasImages(): bool {
        if($this->getImages()){
            return true;
        }
        return false;
    }

    public function getMainImageUnprocessed(): string|false|null {
        return $this->mainImageSRC;
    }

    public function getMoreImagesUnprocessed(): array {
        return $this->multiSRC;
    }


    protected function lsShopGetProcessedImages(): void {
        $builder = new GalleryImageListBuilder($this->sortingRandomizer);

        // Prepare cache context
        /** @var PageModel $objPage */
        global $objPage;
        $language = is_object($objPage) && isset($objPage->language) ? (string) $objPage->language : '';
        $sortMode = (string) $this->ls_moreImagesSortBy;
        $overlaysHash = GalleryImageCache::buildOverlaysHash($this->arrOverlays);
        $mainSig = $this->getMainImageSignature();
        $multiSig = $this->getMultiSrcSignature();
        $multiSigHash = GalleryImageCache::buildMultiSigHash($multiSig);

        // Try cache via handler if available
        $cached = false;
        try {
            $container = System::getContainer();
            $handler = $container->has('leadingsystems.contao_cache.handler') ? $container->get('leadingsystems.contao_cache.handler') : null;
        } catch (\Throwable $e) {
            $handler = null;
        }

        $payload = null;
        if ($handler) {
            $tags = GalleryImageCache::buildTags($language, $sortMode, $overlaysHash, $mainSig, $multiSigHash);
            $handle = $handler->create(86400, $tags);
            list($hit, $payload) = $handle->getValueOrStart();
            if ($hit && is_array($payload)) {
                $cached = true;
            }
        }

        if ($cached) {
            $this->mainImage = isset($payload['main']) && is_array($payload['main']) ? GalleryImageCache::hydrateImage($payload['main']) : false;
            $baseList = isset($payload['moreBase']) && is_array($payload['moreBase']) ? GalleryImageCache::hydrateImages($payload['moreBase']) : array();

            // Apply random strategy on read
            if ($sortMode === 'random') {
                // Shuffle a copy
                $shuffled = $baseList;
                shuffle($shuffled);
                $this->ls_images = $shuffled;
            } else {
                $this->ls_images = isset($payload['more']) && is_array($payload['more']) ? GalleryImageCache::hydrateImages($payload['more']) : $baseList;
            }
        } else {
            $result = $builder->build($this->mainImageSRC, $this->multiSRC, $this->arrOverlays, $sortMode);
            $this->ls_images = $result['moreImages'];
            $this->mainImage = $result['mainImage'] ?: false;

            if ($handler) {
                $tags = GalleryImageCache::buildTags($language, $sortMode, $overlaysHash, $mainSig, $multiSigHash);
                $handle = $handler->create(86400, $tags);
                $payload = array(
                    'main' => $this->mainImage instanceof \stdClass ? GalleryImageCache::serializeImage($this->mainImage) : null,
                    'more' => $sortMode === 'random' ? null : GalleryImageCache::serializeImages($this->ls_images),
                    'moreBase' => GalleryImageCache::serializeImages($result['moreImages'])
                );
                $handle->storeValue($payload);
            }
        }
    }

    public function getSortMode(): string {
        return $this->ls_moreImagesSortBy;
    }

    public function getMultiSrcSignature(): string {
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $signatures = array();
        if (!is_array($this->multiSRC)) {
            return '';
        }
        foreach ($this->multiSRC as $path) {
            $full = $str_projectDir . '/' . $path;
            $mtime = is_file($full) ? filemtime($full) : 'na';
            $signatures[] = $path . ':' . $mtime;
        }
        return implode('|', $signatures);
    }

    public function getMainImageSignature(): string {
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $path = $this->mainImageSRC ? $this->mainImageSRC : '';
        if (!$path) {
            return '';
        }
        $full = $str_projectDir . '/' . $path;
        $mtime = is_file($full) ? filemtime($full) : 'na';
        return $path . ':' . $mtime;
    }

    protected function processSingleImage($file): \stdClass|false {
        /** @var PageModel $objPage */
        global $objPage;
        $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');

        //check if _cover is in name
        if (preg_match('/_cover/siU', $file)) {
            $parts = explode("_cover.", $file);
            //check of there is a image for this cover or not, if not then this will be used as a normal product image
            if (!preg_match('/\.mp4/siU', $file) && file_exists($str_projectDir.'/'.$parts[0].".mp4")) {
                return false;

            }
        }

        if (isset($this->ls_images[$file]) || !file_exists($str_projectDir.'/'.$file)) {
            return false;
        }


        if (!is_file($str_projectDir . '/' . $file)) {
            return false;
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

        $objFileModel = FilesModel::findMultipleByPaths(array($this->originalSRC ? $this->originalSRC : $file));
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
            $objImage->alt = $arrMeta['alt'] ?? '';
            $objImage->title = $arrMeta['title'] ?? '';
            $objImage->imageUrl = $arrMeta['link'] ?? '';
            $objImage->caption = $arrMeta['caption'] ?? '';
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
    protected function lsShopGetVideoCover(&$filename): File {
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