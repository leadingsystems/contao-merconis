<?php

namespace Merconis\Core;

use Contao\System;

class ls_shop_moreImagesGallery extends \Frontend {
	
	protected $multiSRC = array();

	protected $mainImageSRC = false;
	
	protected $mainImage = false;

	protected $ls_imageLimit = 0;


	//id des products
	protected $id = false;
	
	protected $arrImgSuffixes = array('jpg', 'jpeg', 'JPG', 'JPEG', 'gif', 'GIF', 'png', 'PNG');
	
	protected $originalSRC = false;

	//neu, onSale
	protected $arrOverlays = array();
	
	protected $ls_images = array(); // the array holding the processed images


	protected $sortingRandomizer = 0;


	
	public function __construct($mainImageSRC = false, $multiSRC = array(), $id = false, $arrOverlays = array(), $product = false, $ls_imageLimit = 0) {
		parent::__construct();

        $this->ls_imageLimit = $ls_imageLimit;

        if ($product->_isNew) {
            $arrOverlays[] = 'isNew';
        }
        if ($product->_isOnSale) {
            $arrOverlays[] = 'isOnSale';
        }


		if (!is_array($multiSRC)) {
			$multiSRC = array();
		}

		$this->multiSRC = $multiSRC;
		$this->mainImageSRC = $mainImageSRC;
		
		$this->ls_moreImagesSortBy = $GLOBALS['TL_CONFIG']['ls_shop_imageSortingStandardDirection'];
		
		$this->sortingRandomizer = rand(0,99999);


		$this->id = $id;
		$this->Template = new \FrontendTemplate($this->strTemplate);
		$this->arrOverlays = $arrOverlays;
		
		$this->Template->images = array();


        $this->ls_images = $this->lsShopGetProcessedImages();

/*
        dump("ls_shop_moreImagesGallery created");

        dump("getMainImage");
        dump($this->getMainImage());
        dump("getImages");
        dump($this->getImages());
        dump("getImages");
        dump($this->getMoreImages());
*/

	}

    //returns the MainImage
    public function getMainImage(){

        //set mainImage to the first position of this array

        return $this->mainImage;
    }

    //returns All Images MainImage+MoreImages
	public function getImages(){

        $arrImg = $this->ls_images;

        if ($this->mainImage) {
            array_insert($arrImg, 0, array($this->mainImage));
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
			$this->process($file);
			dump($this->ls_images);
		}


		if($this->mainImageSRC){
		    dump($this->mainImage);
            $this->mainImage = $this->processSingleImage($this->mainImageSRC);
        }else if(empty($this->multiSRC)){
            dump("ls_shop_systemImages_noProductImage");
            $this->mainImage = $this->processSingleImage(\FilesModel::findByUuid($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])->path);
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
		//$this->ls_images = array_values($this->ls_images);





        return $this->ls_images;

	}



	protected function processSingleImage($file) {
		/** @var \PageModel $objPage */
		global $objPage;

		/*
		if (preg_match('/_cover/siU', $file)) {
			return false;
		}

		if (isset($this->ls_images[$file]) || !file_exists(TL_ROOT.'/'.$file)) {
			return false;
		}


		if (!is_file(TL_ROOT . '/' . $file)) {
			return false;
		}*/

		$arrOverlays = $this->arrOverlays;
		
		$objFile = new \File($file, true);
		
		/*
		 * If the image is not a gd image we assume that it's a video. This means that images of the following types
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


            $container = System::getContainer();
            $projectDir = $container->getParameter('kernel.project_dir');

            //Todo change Size
            $size = array(800,533);

            //dump($imageValue);
            $picture = $container->get('contao.image.picture_factory')->create($projectDir . '/' . $objImage->singleSRC, $size);
            $staticUrl = $container->get('contao.assets.files_context')->getStaticUrl();

            $objImage->href =  $picture->getImg($projectDir, $staticUrl)['src'];

            //$arrGalleryImages[] = $imageValue;


            return $objImage;

		}
		
		return true;
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
?>