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
         * Do me! Duplicate call of lsShopGetProcessedImages()?
         */
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
                // Try cache for main image first
                $objCached = null;
                try {
                    $container = System::getContainer();
                    if ($container->has('merconis.cache_handler.gallery')) {
                        $handler = $container->get('merconis.cache_handler.gallery');
                        $params = array(
                            'sort' => 'none',
                            'ov' => array_values($this->arrOverlays),
                            'sig' => array(),
                            'mis' => $this->buildFileSignature($this->mainImageSRC),
                            'incMain' => true
                        );
                        $handle = $handler->createForRecipe('gallery_images', $params, 86400);
                        list($hit, $payload) = $handle->getValueOrStart();
                        if ($hit && is_array($payload)) {
                            $rehydrated = $this->rehydrateImagesFromCache(array($payload));
                            $objCached = isset($rehydrated[0]) ? $rehydrated[0] : null;
                        }
                    }
                } catch (\Throwable $e) {}

                if ($objCached) {
                    $this->mainImage = $objCached;
                } else {
                    $this->mainImage = $this->processSingleImage($this->mainImageSRC);
                    // Store to cache if possible
                    try {
                        if (isset($handle)) {
                            $payload = $this->serializeImagesForCache(array($this->mainImage));
                            $row = isset($payload[0]) ? $payload[0] : null;
                            if (is_array($row)) {
                                $handle->storeValue($row);
                            }
                        }
                    } catch (\Throwable $e) {}
                }
            }else if(!empty($this->getMoreImages())){
                /*
                 * Do me! Check: Is this an expensive double call of $this->getMoreImages()?
                 */
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

        // Gallery cache: try to load processed images list from cache
        $__cacheEnabled = false;
        $__cacheHit = false;
        $__cacheHandle = null;
        $__sortIsRandom = ($this->ls_moreImagesSortBy === 'random');
        $__ttl = 86400; // 24h default
        try {
            $__container = System::getContainer();
            if ($__container->has('merconis.cache_handler.gallery')) {
                $__cacheEnabled = true;
                $__handler = $__container->get('merconis.cache_handler.gallery');
                $__params = array(
                    'sort' => (string) $this->ls_moreImagesSortBy,
                    'ov' => array_values($this->arrOverlays),
                    'sig' => $this->buildMultiSrcSignature($this->multiSRC),
                    'mis' => $this->buildFileSignature($this->mainImageSRC),
                    'incMain' => false
                );
                $__cacheHandle = $__handler->createForRecipe('gallery_images', $__params, $__ttl);
                list($__hit, $__payload) = $__cacheHandle->getValueOrStart();
                if ($__hit && is_array($__payload)) {
                    $this->ls_images = $this->rehydrateImagesFromCache($__payload);
                    $__cacheHit = true;
                }
            }
        } catch (\Throwable $e) {
            // ignore cache errors
        }

        // Compute images on cache miss
        if (!$__cacheHit) {
            // Get all images
            foreach ($this->multiSRC as $file) {
                $newImageToAdd = $this->processSingleImage($file);
                if($newImageToAdd){
                    $this->ls_images[] = $newImageToAdd;
                }
            }

            // For random sort, store processed-but-unsorted list so each request can randomize freshly
            if ($__cacheEnabled && $__cacheHandle && $__sortIsRandom) {
                try {
                    $__payloadToStore = $this->serializeImagesForCache($this->ls_images);
                    $__cacheHandle->storeValue($__payloadToStore);
                } catch (\Throwable $e) {}
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
				$__keys = array();
				foreach ($this->ls_images as $i => $img) {
					$__keys[$i] = md5((isset($img->name) ? (string) $img->name : '') . $this->sortingRandomizer);
				}
				// Sort images by the ephemeral keys without mutating objects
				array_multisort($__keys, SORT_ASC, $this->ls_images);
				break;

            case 'none':
                break;
        }

        //sort videos to end of image list
        $videos = [];
        for ($i = 0; count($this->ls_images) > $i; $i++) {
            //test if video (originalSRC is false for all images)
            if ($this->ls_images[$i]->originalSRC != false){
                $videos[] = $this->ls_images[$i];
                unset($this->ls_images[$i]);
            }
        }
        $this->ls_images = array_merge($this->ls_images, $videos);

        // After final ordering, store non-random results in cache
        if (!$__cacheHit && $__cacheEnabled && $__cacheHandle && !$__sortIsRandom) {
            try {
                $__payloadToStore = $this->serializeImagesForCache($this->ls_images);
                $__cacheHandle->storeValue($__payloadToStore);
            } catch (\Throwable $e) {}
        }

    }

    protected function processSingleImage($file) {
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
         * Walk through the image suffix array and check whether there's
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

	/**
	 * Build a signature array for a list of file paths using path and filemtime.
	 */
	protected function buildMultiSrcSignature($multiSRC) {
		if (!is_array($multiSRC)) {
			return array();
		}
		$arr = array();
		foreach ($multiSRC as $p) {
			$sig = $this->buildFileSignature($p);
			if ($sig !== null) {
				$arr[] = $sig;
			}
		}
		sort($arr, SORT_STRING);
		return $arr;
	}

	/**
	 * Build a signature string for a single file path using path and filemtime, or null if not applicable.
	 */
	protected function buildFileSignature($path) {
		if (!$path) {
			return null;
		}
		$str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
		$abs = $str_projectDir . '/' . ltrim((string) $path, '/');
		if (!is_file($abs)) {
			return (string) $path . ':na';
		}
		$mtime = @filemtime($abs);
		$mtime = $mtime === false ? '0' : (string) $mtime;
		return (string) $path . ':' . $mtime;
	}

	/**
	 * Serialize list of image objects to an array suitable for cache storage.
	 */
	protected function serializeImagesForCache($images) {
		$out = array();
		if (!is_array($images)) {
			return $out;
		}
		foreach ($images as $img) {
			if (!is_object($img)) {
				continue;
			}
			$out[] = array(
				'name' => isset($img->name) ? (string) $img->name : '',
				'originalSRC' => isset($img->originalSRC) ? $img->originalSRC : false,
				'arrOverlays' => isset($img->arrOverlays) && is_array($img->arrOverlays) ? array_values($img->arrOverlays) : array(),
				'singleSRC' => isset($img->singleSRC) ? (string) $img->singleSRC : '',
				'alt' => isset($img->alt) ? (string) $img->alt : '',
				'title' => isset($img->title) ? (string) $img->title : '',
				'imageUrl' => isset($img->imageUrl) ? (string) $img->imageUrl : '',
				'caption' => isset($img->caption) ? (string) $img->caption : '',
				'mtime' => isset($img->mtime) ? (int) $img->mtime : 0
			);
		}
		return $out;
	}

	/**
	 * Rehydrate cached array payload back into list of image objects.
	 */
	protected function rehydrateImagesFromCache($payload) {
		$out = array();
		if (!is_array($payload)) {
			return $out;
		}
		foreach ($payload as $row) {
			if (!is_array($row)) {
				continue;
			}
			$objImage = new \stdClass();
			$objImage->name = isset($row['name']) ? (string) $row['name'] : '';
			$objImage->originalSRC = isset($row['originalSRC']) ? $row['originalSRC'] : false;
			$objImage->arrOverlays = isset($row['arrOverlays']) && is_array($row['arrOverlays']) ? array_values($row['arrOverlays']) : array();
			$objImage->singleSRC = isset($row['singleSRC']) ? (string) $row['singleSRC'] : '';
			$objImage->alt = isset($row['alt']) ? (string) $row['alt'] : '';
			$objImage->title = isset($row['title']) ? (string) $row['title'] : '';
			$objImage->imageUrl = isset($row['imageUrl']) ? (string) $row['imageUrl'] : '';
			$objImage->caption = isset($row['caption']) ? (string) $row['caption'] : '';
			$objImage->mtime = isset($row['mtime']) ? (int) $row['mtime'] : 0;
			$out[] = $objImage;
		}
		return $out;
	}
}