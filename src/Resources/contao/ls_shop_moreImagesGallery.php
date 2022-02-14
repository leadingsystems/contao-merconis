<?php

namespace Merconis\Core;

use Contao\System;

class ls_shop_moreImagesGallery extends \Frontend {
	
	protected $multiSRC = array();
	
	protected $mainImage = false;

	//TODO einfügen
	protected $ls_imageLimit = 0;

	//id des products
	protected $id = false;
	
	protected $arrImgSuffixes = array('jpg', 'jpeg', 'JPG', 'JPEG', 'gif', 'GIF', 'png', 'PNG');
	
	protected $originalSRC = false;

	//neu, onSale
	protected $arrOverlays = array();
	
	protected $ls_images = array(); // the array holding the processed images

    //?
	protected $sortingRandomizer = 0;


	//ort für die processed images
    protected $images = array();
	
	public function __construct($mainImage = false, $multiSRC = array(), $id = false, $arrOverlays = array(), $product = false) {
		parent::__construct();

        if ($product->_isNew) {
            $arrOverlays[] = 'isNew';
        }
        if ($product->_isOnSale) {
            $arrOverlays[] = 'isOnSale';
        }

        //dump($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage']);
        //dump(\FilesModel::findByUuid($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])->path);
/*
        if($mainImage == false ){
            $mainImage = \FilesModel::findByUuid($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])->path;
        }
*/


		if (!is_array($multiSRC)) {
			$multiSRC = array();
		}

		$this->multiSRC = $multiSRC;
		$this->mainImage = $mainImage;
		
		$this->ls_moreImagesSortBy = $GLOBALS['TL_CONFIG']['ls_shop_imageSortingStandardDirection'];
		
		$this->sortingRandomizer = rand(0,99999);
		

		/*
		 * Wurde ein Hauptbild übergeben, so wird es auf Index 0 des gesamten Bildarrays gesetzt,
		 * da das erste Bild als Hauptbild anders dargestellt wird.
		 */

        /*
		if ($this->mainImage) {
			array_insert($this->multiSRC, 0, $mainImage);
		}*/

		$this->id = $id;
		$this->Template = new \FrontendTemplate($this->strTemplate);
		$this->arrOverlays = $arrOverlays;
		
		$this->Template->images = array();

        //dump("main Image");
		//dump($this->ls_sizeMainImage);

        $this->images = $this->lsShopGetProcessedImages();

        //dump($this->images);

        if(empty($this->images)){
            //dump("wop");
            //$this->mainImage = \FilesModel::findByUuid($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])->path;
            //dump($this->mainImage);
        }
        /*
        foreach ($this->ls_sizeMainImage as $k => $arrSizeMainImage) {
            //dump("in");
            $arrSizeMoreImages = $this->ls_sizeMoreImages[$k];



        }*/
        /*
        if ($this->mainImage) {
            array_insert($this->multiSRC, 0, $mainImage);
        }*/
	}

	public function getImages(){
	    return $this->images;
    }

	
	public function imagesSortedAndWithVideoCovers() {
		$this->getImagesSortedAndWithVideoCovers();
		return $this->ls_images;
	}

	protected function process($file){
        if (@file_exists(TL_ROOT.'/'.$file)) {

            // Process single files
            if (is_file(TL_ROOT.'/'.$file)) {
                $this->processSingleImage($file);
            }

            // Process folders (not recursive, only the one given folder!)
            else {
                $subfiles = scan(TL_ROOT.'/'.$file);

                foreach ($subfiles as $subfile) {
                    $subfileName = $file . '/' . $subfile;
                    $this->processSingleImage($subfileName);
                }
            }
        }
    }
	
	protected function getImagesSortedAndWithVideoCovers() {
		/*
		 * Reset the ls_images array because this function maybe called more than once
		 * if multiple image sizes are requested.
		 */
		$this->ls_images = array();


        if(empty($this->multiSRC)){
            //dump("ls_shop_systemImages_noProductImage");
            //$this->mainImage = \FilesModel::findByUuid($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])->path;
            //array_insert($this->multiSRC, 0, $this->mainImage);
        }

		// Get all images
		foreach ($this->multiSRC as $file) {
			$this->process($file);
		}

		if($this->mainImage){
		    dump($this->mainImage);
            $this->process($this->mainImage);
        }else{
            dump("2");
            $this->process(\FilesModel::findByUuid($GLOBALS['TL_CONFIG']['ls_shop_systemImages_noProductImage'])->path);
        }

		/*
		 * If a main image has been defined explicitly we remove it from the images array
		 * temporarily because we don't want it to be sorted somewhere but to stay on top
		 */

        dump($this->mainImage);


        /*
		if ($this->mainImage) {
			$mainImageTemp = $this->ls_images[$this->mainImage];
			unset($this->ls_images[$this->mainImage]);
		}*/

		// Sort array
		switch ($this->ls_moreImagesSortBy) {
			default:
			case 'name_asc':
				uksort($this->ls_images, 'basename_natcasecmp');
				break;

			case 'name_desc':
				uksort($this->ls_images, 'basename_natcasercmp');
				break;

			case 'date_asc':
				uasort($this->ls_images, function($a, $b) {
					if ($a['mtime'] == $b['mtime']) {
				        return 0;
				    }
				    return ($a['mtime'] < $b['mtime']) ? -1 : 1;
				});
				break;

			case 'date_desc':
				uasort($this->ls_images, function($a, $b) {
					if ($a['mtime'] == $b['mtime']) {
				        return 0;
				    }
				    return ($a['mtime'] < $b['mtime']) ? 1 : -1;
				});
				break;

			case 'random':
				uasort($this->ls_images, function($a, $b) {
					return strcmp($a['randomSortingValue'], $b['randomSortingValue']);
				});
				break;
		}
		$this->ls_images = array_values($this->ls_images);

		/*
		 * If we have an explicitly given main image and the temporarily saved main image from a few lines above
		 * we insert this image in the first position of the image array
		 */
		if ($this->mainImage) {
			array_insert($this->ls_images, 0, array($this->mainImage));
		}

		if ($this->ls_imageLimit) {
			$this->ls_images = array_slice($this->ls_images, 0, $this->ls_imageLimit);
		}
        dump("ende");
		dump($this->ls_images);
	}

	//TODO verarbeite Image
	public function lsShopGetProcessedImages() {
        dump($this->ls_images);

		$this->getImagesSortedAndWithVideoCovers();

        dump($this->ls_images);
		$arrGalleryImages = array();
		foreach ($this->ls_images as $imageKey => $imageValue) {



			$this->ls_images[$imageKey]['fullsize'] =  $this->ls_imagesFullsize;

			$objCell = new \stdClass();

			/*
			 * Add the image to the objCell standard object using the contao function
			 */
			//$this->addImageToTemplate($objCell, $this->ls_images[$imageKey], $intMaxWidth, $strLightboxId);

            $container = System::getContainer();
            $projectDir = $container->getParameter('kernel.project_dir');

            //Todo change Size
            $size = array(800,533);

            //dump($imageValue);
            $picture = $container->get('contao.image.picture_factory')->create($projectDir . '/' . $imageValue['singleSRC'], $size);
            $staticUrl = $container->get('contao.assets.files_context')->getStaticUrl();

            $objCell->img =  $picture->getImg($projectDir, $staticUrl);

            $objCell->href = $imageValue['singleSRC'];
            $objCell->singleSRC = $objCell->img['src'];
			/*
			 * Set the overlay image array for this image -> onSale, new
			 */
			$objCell->arrOverlays = is_array($imageValue['arrOverlays']) ? $imageValue['arrOverlays'] : array();
			
			/*
			 * Finally, add the image object to the gallery images array which will be returned
			 */
            //dump($arrGalleryImages);
			$arrGalleryImages[] = $objCell;
		}
		dump("dumpGallery");
		dump($arrGalleryImages);
		return $arrGalleryImages;
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
			$this->ls_images[isset($this->originalSRC) && $this->originalSRC ? $this->originalSRC : $file] = array (
				'name' => $objFile->basename,
				'originalSRC' => $this->originalSRC,
				'arrOverlays' => $arrOverlays,
				'singleSRC' => $file,
				'alt' => $arrMeta['title'],
				'imageUrl' => $arrMeta['link'],
				'caption' => $arrMeta['caption'],
				'mtime' => $objFile->mtime,
				'randomSortingValue' => md5($objFile->basename.$this->sortingRandomizer)
			);
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