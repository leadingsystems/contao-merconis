<?php
namespace Merconis\Core;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;
use function LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray;
use function LeadingSystems\Helpers\createMultidimensionalArray;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_product
{
	public $ls_ID = 0;
	public $ls_productVariantID = 0;
	public $ls_currentVariantID = 0;
	public $ls_currentProductVariantID = 0;

	public $ls_variants = array();

	private $arr_originalData = null;
	public $arr_customizableData = null;
	private $ls_data = null;
	public $mainData = null;
	public $currentLanguageData = null;

	private $ls_objConfigurator = null;

    /**
     * @var customizer
     */
	public $obj_customizer = null;

	public $ls_configuratorHash = '';

	protected $ls_mainLanguageMode = false;

	protected $blnAlreadyGeneratedScalePricesOutput = array(
		'unconfigured' => false,
		'configurator' => false,
		'configured' => false,
		'all' => false
	);
	protected $scalePricesOutput = array(
		'unconfigured' => null,
		'configurator' => null,
		'configured' => null,
		'all' => null
	);

	protected $ls_linkToProduct = '';

    // Holds image galleries created with getImageGallery()
    protected $arr_imageGalleries = [];

    public function __construct($intID = false, $configuratorHash = '') {
		$this->ls_ID = $intID;

		$this->ls_productVariantID = $this->ls_ID.'-0';

		if ($configuratorHash) {
			$this->ls_configuratorHash = $configuratorHash;
		}

		$this->ls_getData();

		$this->ls_getVariants();

		$this->createCustomizerObject();
	}

	protected function createCustomizerObject() {
        if (!$this->_hasVariants) {
            $this->obj_customizer = ls_shop_generalHelper::getCustomizerObject($this);
        }
    }

	protected function createObjConfigurator() {
		if ($this->ls_objConfigurator !== null) {
			return;
		}
		$this->ls_objConfigurator = ls_shop_generalHelper::getObjConfigurator($this->_configuratorID, $this->ls_ID.'-0', array('weight' => $this->_weightBeforeConfigurator, 'price' => $this->_priceBeforeConfiguratorAfterTax, 'unscaledPrice' => $this->_unscaledPriceBeforeConfiguratorAfterTax, 'steuersatz' => $this->_steuersatz, 'anchor' => $this->_anchor), $this->ls_configuratorHash, $this);
	}

	public function ls_setMainLanguageMode($bln) {
		if ($this->_variantIsSelected) {
			$this->_selectedVariant->ls_setMainLanguageMode($bln);
		}

		$this->ls_mainLanguageMode = $bln;

		$this->setDataReferences();
	}

	public function getLowestVariantPrice() {
		$lowestPriceBeforeTax = $this->_priceBeforeTax;
		$lowestPriceOldBeforeTax = $this->_priceOldBeforeTax;

		$count = 0;
		foreach ($this->_variants as $variant) {
			if ($variant->_priceBeforeTax < $lowestPriceBeforeTax) {
				$lowestPriceBeforeTax = $variant->_priceBeforeTax;
			}
			if ($variant->_priceOldBeforeTax < $lowestPriceOldBeforeTax) {
				$lowestPriceOldBeforeTax = $variant->_priceOldBeforeTax;
			}
		}
	}

	/**-->
	 * Diese Funktion erstellt eine automatische Dokumentation über die in Templates zu verwendenden Eigenschaften und Methoden
	 * des Produkt-Objektes und analysiert dafür die Methoden "__get()" und "__call()"
	 <--*/
	protected function ls_outputOptions() {
		$fileContent = file_get_contents(System::getContainer()->getParameter('kernel.project_dir').'/vendor/leadingsystems/contao-merconis/src/Resources/contao/classes/ls_shop_product.php');

		/*-->
		 * Properties
		 <--*/
		preg_match('/\x23\x23 START AUTO DOCUMENTATION PROPERTIES PRODUCT \x23\x23(.*)\x23\x23 STOP AUTO DOCUMENTATION PROPERTIES PRODUCT \x23\x23/siU', $fileContent, $matches);
		$tmp = $matches[1];
		preg_match_all("/case '(.*)'(.*\/\*.*## DESCRIPTION:(.*)\*\/.*)?:/siU", $tmp, $matches);
		$allOutputProperties = $matches[1];
		$allOutputPropertiesDescriptions = $matches[3];
		$outputProperties = array();
		foreach ($allOutputProperties as $k => $v) {
			$outputProperties[$v] = trim($allOutputPropertiesDescriptions[$k]);
		}
		ksort($outputProperties);



		/*-->
		 * Methods
		 <--*/
		preg_match('/\x23\x23 START AUTO DOCUMENTATION METHODS PRODUCT \x23\x23(.*)\x23\x23 STOP AUTO DOCUMENTATION METHODS PRODUCT \x23\x23/siU', $fileContent, $matches);
		$tmp = $matches[1];
		preg_match_all("/case '(.*)'(.*\/\*.*## DESCRIPTION:(.*)\*\/.*)?:/siU", $tmp, $matches);
		$allOutputMethods = $matches[1];
		$allOutputMethodsDescriptions = $matches[3];
		$outputMethods = array();
		foreach ($allOutputMethods as $k => $v) {
			$outputMethods[$v] = trim($allOutputMethodsDescriptions[$k]);
		}
		ksort($outputMethods);

		ob_start();
		?>
		<div class="ls_shop_templateInfo">
			<script type="text/javascript">
				document.addEvent('domready', function() {
					$$(".togglePreview")[0].addEvent('click', function() {
						var displayValue;
						if (this.innerHTML == 'show preview') {
							displayValue = 'block';
							this.innerHTML = 'hide preview';
						} else {
							displayValue = 'none';
							this.innerHTML = 'show preview';
						}
						$$(".infoblock .preview").each(function(value, key) {
							value.setStyle('display', displayValue);
						});
					});

					$$(".hideSinglePreview").each(function(value, key) {
						value.addEvent('click', function() {
							this.getParent().setStyle('display', 'none');
						});
					})
				});
			</script>
			<h1>OUTPUT OPTIONS FOR PRODUCT OBJECT</h1>
			<?php if (false): ?>
			<div class="togglePreview">show preview</div>
			<?php endif; ?>
			<h2>Methods</h2>
			<?php
				foreach ($outputMethods as $title => $description) {
					?>
					<div class="infoblock">
						<div class="name"><?php echo $title; ?>()</div>
						<div class="description"><pre><?php echo $description; ?></pre></div>
						<?php if (false): ?>
						<div class="preview">
							<div class="hideSinglePreview">hide</div>
							<?php
								$returnValue = $this->{$title}();
								if (is_array($returnValue)) {
									?>
									<pre><?php print_r($returnValue); ?></pre>
									<?php
								} else {
									var_dump($returnValue);
								}
							?>
						</div>
						<?php endif; ?>
					</div>
					<?php
				}
			?>
			<h2>Properties</h2>
			<?php
				foreach ($outputProperties as $title => $description) {
					?>
					<div class="infoblock">
						<div class="name"><?php echo $title; ?></div>
						<div class="description"><pre><?php echo $description; ?></pre></div>
						<?php if (false): ?>
						<?php if ($title != '_outputOptions'): ?>
						<div class="preview">
							<div class="hideSinglePreview">hide</div>
							<?php
								if (is_array($this->{$title})) {
									?>
									<pre><?php print_r($this->{$title}); ?></pre>
									<?php
								} else {
									var_dump($this->{$title});
								}
							?>
						</div>
						<?php endif; ?>
						<?php endif; ?>
					</div>
					<?php
				}
			?>
		</div>
		<?php
		$outputBuffer = ob_get_contents();
		ob_end_clean();
		return $outputBuffer;
	}


    // create ImageGallery if it does not exist yet and returns it
    public function getImageGallery($ls_moreImagesSortBy = false, $ls_imageLimit = 0) {
        $str_argHash = md5(implode('', func_get_args()));
        if(!($this->arr_imageGalleries[$str_argHash] ?? null)){
            $this->arr_imageGalleries[$str_argHash] = new productImageGallery($this, $ls_moreImagesSortBy, $ls_imageLimit);
        }
        return $this->arr_imageGalleries[$str_argHash];
    }

	/*
	 * Getter-Funktion. Durch die Kommentare für AUTO DOCUMENTATION und DESCRIPTION können die
	 * hier verfügbaren Eigenschaften in der automatischen Dokumentation dargestellt werden
	 */
	public function __get($what = '') {
		/** @var PageModel $objPage */
		global $objPage;
		switch ($what) {
			/* ## START AUTO DOCUMENTATION PROPERTIES PRODUCT ## */
			case '_outputOptions':
				return $this->ls_outputOptions();
				break;

			case '_objectType':
				return 'product';

            case '_configuratorHash':
                return $this->ls_configuratorHash;

			case '_customizerLogicFile':
				return ls_getFilePathFromVariableSources($this->mainData['customizerLogicFile']);
				break;

            case '_hasCustomizerLogicFile':
                return $this->_customizerLogicFile && is_file(System::getContainer()->getParameter('kernel.project_dir')."/".$this->_customizerLogicFile);

            case '_customizer':
                return $this->obj_customizer;

            case '_hasCustomizer':
                return is_object($this->_customizer);

			case '_isPublished':
				return $this->mainData['published'] ? true : false;
				break;

			case '_id':
				return $this->ls_ID;
				break;

			case '_anchor':
				return 'p_'.$this->_productVariantID;
				break;

			case '_productVariantID':
				return $this->ls_productVariantID;
				break;

			case '_configuratorID':
				return $this->mainData['configurator'];
				break;

			case '_hasConfigurator':
				if (!$this->_variantIsSelected && $this->_configuratorID) {
					return true;
				} else if ($this->_variantIsSelected && $this->_selectedVariant->_configuratorID) {
					return true;
				}
				return false;
				break;

			case '_objConfigurator':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return $this->ls_objConfigurator;
				break;

			case '_configurator':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->parse() : $this->_selectedVariant->ls_objConfigurator->parse();
				break;

			case '_configuratorWithCartRepresentation':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->parse('cartRepresentation') : $this->_selectedVariant->ls_objConfigurator->parse('cartRepresentation');
				break;

			case '_orderAllowed':
                if ($this->_variantIsSelected) {
                    return $this->_selectedVariant->_orderAllowed;
                }

			    $bln_orderAllowed = true;

                if ($this->_hasCustomizer) {
                    if (!$this->_customizer->checkIfOrderIsAllowed()) {
                        $bln_orderAllowed = false;
                    }
                } else {
                    $this->createObjConfigurator();
                    if (!$this->ls_objConfigurator->blnIsValid) {
                        $bln_orderAllowed = false;
                    }
                }

                if (!$this->_isAvailableBasedOnDate && !$this->_isPreorderable) {
                    $bln_orderAllowed = false;
                }

                return $bln_orderAllowed;
				break;

			case '_cartKey':
			    $str_cartKey = $this->ls_productVariantID;

			    if ($this->_hasCustomizer) {
                    $str_cartKey = $this->ls_productVariantID . '_' . ($this->_customizer->getCustomizerHash() ?: 'no-customization');
                } else if ($this->_hasConfigurator) {
			        $str_cartKey = $this->_configuratorCartKey;
                }

			    return $str_cartKey;
				break;

            case '_configuratorCartKey':
                /*
                 * If the product has not been configured yet, we use the default configuratorHash
                 */
                if (!isset($_SESSION['lsShop']['productVariantIDsAlreadyConfigured']) || !is_array($_SESSION['lsShop']['productVariantIDsAlreadyConfigured']) || !in_array($this->ls_productVariantID, $_SESSION['lsShop']['productVariantIDsAlreadyConfigured'])) {
                    $str_cartKey = $this->ls_productVariantID.'_'.ls_shop_generalHelper::getDefaultConfiguratorHash(!$this->ls_currentVariantID ? $this->_configuratorID : $this->ls_variants[$this->ls_currentVariantID]->_configuratorID);
                } else {
                    !$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
                    if (!$this->_variantIsSelected) {
                        $str_cartKey = $this->ls_productVariantID.($this->ls_objConfigurator->configuratorHash ? '_'.$this->ls_objConfigurator->configuratorHash : '');
                    } else {
                        $str_cartKey = $this->_selectedVariant->ls_productVariantID.($this->_selectedVariant->ls_objConfigurator->configuratorHash ? '_'.$this->_selectedVariant->ls_objConfigurator->configuratorHash : '');
                    }
                }

                return $str_cartKey;
                break;

			case '_configuratorRepresentation':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->representation : $this->_selectedVariant->ls_objConfigurator->representation;
				break;

			case '_configuratorCartRepresentation':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->cartRepresentation : $this->_selectedVariant->ls_objConfigurator->cartRepresentation;
				break;

			case '_configuratorMerchantRepresentation':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->merchantRepresentation : $this->_selectedVariant->ls_objConfigurator->merchantRepresentation;
				break;

			case '_configuratorReferenceNumber':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->referenceNumber : $this->_selectedVariant->ls_objConfigurator->referenceNumber;
				break;

			case '_configuratorHasValue':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->hasValue : $this->_selectedVariant->ls_objConfigurator->hasValue;
				break;

			case '_configuratorInDataEntryMode':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				return !$this->_variantIsSelected ? $this->ls_objConfigurator->blnDataEntryMode : $this->_selectedVariant->ls_objConfigurator->blnDataEntryMode;
				break;

			case '_title':
				return $this->currentLanguageData['title'] ? $this->currentLanguageData['title'] : $this->mainData['title'];
				break;

			case '_hasTitle':
				return $this->_title ? true : false;
				break;

			case '_code':
				return $this->mainData['lsShopProductCode'];
				break;

			case '_hasCode':
				return $this->_code ? true : false;
				break;

			case '_keywords':
				return $this->currentLanguageData['keywords'] ? $this->currentLanguageData['keywords'] : $this->mainData['keywords'];
				break;

			case '_hasKeywords':
				return $this->_keywords ? true : false;
				break;

			case '_pageTitle':
			    $str_pageTitle = trim($this->currentLanguageData['pageTitle']);
				return $str_pageTitle ? $str_pageTitle : '';
				break;

			case '_hasPageTitle':
				return $this->_pageTitle ? true : false;
				break;

			case '_pageDescription':
			    $str_pageDescription = trim($this->currentLanguageData['pageDescription']);
				return $str_pageDescription ? $str_pageDescription : '';
				break;

			case '_hasPageDescription':
				return $this->_pageDescription ? true : false;
				break;

			case '_isNew':
				return $this->mainData['lsShopProductIsNew'];
				break;

			case '_isOnSale':
				return $this->mainData['lsShopProductIsOnSale'];
				break;

			case '_producer':
				return $this->mainData['lsShopProductProducer'];
				break;

			case '_hasProducer':
				return $this->_producer ? true : false;
				break;

			case '_deliveryInfo':
				return ls_shop_generalHelper::getDeliveryInfo($this->getDeliveryInfoSetID(), $this->ls_mainLanguageMode);
				break;

			case '_recommendedProducts':
				return $this->mainData['lsShopProductRecommendedProducts'];
				break;

			case '_useStock':
				return $this->_deliveryInfo['useStock'];
				break;

			case '_stock':
				return number_format($this->mainData['lsShopProductStock'], $this->_quantityDecimals, '.', '');
				break;

			case '_stockIsInsufficient'
				/* ## DESCRIPTION:
Indicates whether or not stock is insufficient. Returns true if stock should be used (_useStock == true), stock is equal or less than 0 (_stock <= 0) and orders with insufficient stock aren't allowed (_allowOrdersWithInsufficientStock == false), otherwise returns false.
				 */
				 :
				$blnStockIsSufficient = false;
				// only if stock should be used, it can be insufficient
				if ($this->_useStock) {
					// if stock is equal or less than 0 and orders with insufficient stock are not allowed
					if ($this->_stock <= 0 && !$this->_allowOrdersWithInsufficientStock) {
						$blnStockIsSufficient = true;
					}
				}

				return $blnStockIsSufficient;
				break;

			case '_allowOrdersWithInsufficientStock':
				return $this->_deliveryInfo['allowOrdersWithInsufficientStock'];
				break;

            case '_deliveryTimeMessage':
                return $this->getDeliveryTimeMessage();
                break;

            case '_deliveryTimeDays':
                return $this->getDeliveryTimeDays();
				break;

			case '_associatedProducts':
				return $this->mainData['associatedProducts'];
				break;

			case '_mainImageUnprocessed'
				/* ## DESCRIPTION:
returns the main image that has been selected explicitly or null if none has been selected
				 */
				 :
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery()->getMainImage()->singleSRC instead', E_USER_DEPRECATED);
				return isset($this->mainData['lsShopProductMainImage']) && $this->mainData['lsShopProductMainImage'] ? ls_getFilePathFromVariableSources($this->mainData['lsShopProductMainImage']) : null;
				break;

            case '_mainImage'
				/*
				 * ## DESCRIPTION:
				 * Returns the image that will be used as the main image if images are processed in an alphabetical ascending order.
				 * If a main image has been selected explicitly, it will always be returned here. Otherwise the image sorted on top will be returned.
				 */
				 :
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery()->getMainImage() instead', E_USER_DEPRECATED);
                $objTmpGallery = new ls_shop_moreImagesGallery($this->_mainImageUnprocessed, $this->_moreImagesUnprocessed, false);
                $allImagesSortedAndWithVideoCovers = $objTmpGallery->imagesSortedAndWithVideoCovers();
                return isset($allImagesSortedAndWithVideoCovers[0]) && $allImagesSortedAndWithVideoCovers[0] && isset($allImagesSortedAndWithVideoCovers[0]['singleSRC']) && $allImagesSortedAndWithVideoCovers[0]['singleSRC'] ? $allImagesSortedAndWithVideoCovers[0]['singleSRC'] : null;
				break;

			case '_hasMainImage':
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery()->hasMainImage() instead', E_USER_DEPRECATED);
				return $this->_mainImage ? true : false;
				break;

			case '_moreImagesUnprocessed':
				/*-->
				 * Using null as the parameter for the main image results in getAllProductImages() returning
				 * all images except for the main image which is exactly what we want here.
				 <--*/
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery()->getMoreImagesUnprocessed() instead', E_USER_DEPRECATED);
				return ls_shop_generalHelper::getAllProductImages($this, $this->_code, null, $this->mainData['lsShopProductMoreImages']);
				break;

			case '_moreImages':
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery()->getMoreImages() instead', E_USER_DEPRECATED);
				if (!isset($GLOBALS['merconis_globals']['_moreImages'][$this->_productVariantID])) {
					$arrMoreImages = array();
					$objTmpGallery = new ls_shop_moreImagesGallery($this->_mainImageUnprocessed, $this->_moreImagesUnprocessed, false);
					$allImagesSortedAndWithVideoCovers = $objTmpGallery->imagesSortedAndWithVideoCovers();
					if (!is_array($allImagesSortedAndWithVideoCovers) || count($allImagesSortedAndWithVideoCovers) <= 1) {
						return $arrMoreImages;
					}

					$allImagesSortedAndWithVideoCovers = array_slice($allImagesSortedAndWithVideoCovers, 1);

					foreach ($allImagesSortedAndWithVideoCovers as $arrImageData) {
						if (!isset($arrImageData['singleSRC']) || !$arrImageData['singleSRC']) {
							continue;
						}
						$arrMoreImages[] = $arrImageData['singleSRC'];
					}

					$GLOBALS['merconis_globals']['_moreImages'][$this->_productVariantID] = $arrMoreImages;
				}
				return $GLOBALS['merconis_globals']['_moreImages'][$this->_productVariantID];
				break;

			case '_hasMoreImages':
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery()->hasMoreImages() instead', E_USER_DEPRECATED);
				return is_array($this->_moreImages) && count($this->_moreImages) ? true : false;
				break;

			case '_hasImages':
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery()->hasImages() instead', E_USER_DEPRECATED);
				return $this->_hasMainImage || $this->_hasMoreImages;
				break;

			case '_description':
				return $this->currentLanguageData['description'] ? $this->currentLanguageData['description'] : $this->mainData['description'];
				break;

			case '_hasDescription':
				return $this->_description ? true : false;
				break;

			case '_shortDescription':
				return $this->currentLanguageData['shortDescription'] ? $this->currentLanguageData['shortDescription'] : $this->mainData['shortDescription'];
				break;

			case '_hasShortDescription':
				return $this->_shortDescription ? true : false;
				break;

			case '_flexContents':
				$flexContents = $this->currentLanguageData['flex_contents'] ? $this->currentLanguageData['flex_contents'] : $this->mainData['flex_contents'];
				if (!$flexContents) {
					return false;
				}

				return createMultidimensionalArray(createOneDimensionalArrayFromTwoDimensionalArray(json_decode($flexContents)), 2, 1);
				break;

			case '_flexContentsLanguageIndependent':
				$flexContents = $this->currentLanguageData['flex_contentsLanguageIndependent'] ? $this->currentLanguageData['flex_contentsLanguageIndependent'] : $this->mainData['flex_contentsLanguageIndependent'];
				if (!$flexContents) {
					return false;
				}

				return createMultidimensionalArray(createOneDimensionalArrayFromTwoDimensionalArray(json_decode($flexContents)), 2, 1);
				break;

			case '_hasFlexContents':
				if (!$this->_flexContents) {
					return false;
				}

				if (!is_array($this->_flexContents)) {
					return false;
				}

				foreach ($this->_flexContents as $flexContent) {
					$flexContent = trim($flexContent);
					if (!empty($flexContent)) {
						return true;
					}
				}

				return false;
				break;

			case '_hasFlexContentsLanguageIndependent':
				if (!$this->_flexContentsLanguageIndependent) {
					return false;
				}

				if (!is_array($this->_flexContentsLanguageIndependent)) {
					return false;
				}

				foreach ($this->_flexContentsLanguageIndependent as $flexContent) {
					$flexContent = trim($flexContent);
					if (!empty($flexContent)) {
						return true;
					}
				}

				return false;
				break;

			case '_weightBeforeConfigurator':
				return $this->mainData['lsShopProductWeight'];
				break;

			case '_weight':
				/*-->
				 * ACHTUNG WICHTIG: An dieser Stelle DARF NICHT das Konfigurator-Objekt der Variante verwendet werden, sofern eine Variante ausgewählt wird, da
				 * ansonsten eine doppelte Anpassung durch den Konfigurator erfolgt.
				 <--*/
				$this->createObjConfigurator();
				return ls_add($this->_weightBeforeConfigurator, $this->ls_objConfigurator->getWeightModification($this->_weightBeforeConfigurator));
				break;

			case '_weightFormatted':
				return ls_shop_generalHelper::outputWeight($this->_weight);
				break;

			case '_hasWeight':
				return intval($this->mainData['lsShopProductWeight']) ? true : false;
				break;

			case '_quantityUnit':
				if (!$this->_variantIsSelected || !$this->_selectedVariant->_hasQuantityUnitSelf) {
					return $this->currentLanguageData['lsShopProductQuantityUnit'] ? $this->currentLanguageData['lsShopProductQuantityUnit'] : $this->mainData['lsShopProductQuantityUnit'];
				} else {
					return $this->_selectedVariant->_quantityUnitSelf;
				}
				break;

			case '_hasQuantityUnit':
				if (!$this->_variantIsSelected || !$this->_selectedVariant->_hasQuantityUnitSelf) {
					return $this->_quantityUnit ? true : false;
				} else {
					return $this->_selectedVariant->_hasQuantityUnitSelf;
				}
				break;

			case '_quantityComparisonUnit':
				if (!$this->_variantIsSelected || !$this->_selectedVariant->_hasQuantityComparisonUnitSelf) {
					return $this->currentLanguageData['lsShopProductMengenvergleichUnit'] ? $this->currentLanguageData['lsShopProductMengenvergleichUnit'] : $this->mainData['lsShopProductMengenvergleichUnit'];
				} else {
					return $this->_selectedVariant->_quantityComparisonUnitSelf;
				}
				break;

			case '_hasQuantityComparisonUnit':
				if (!$this->_variantIsSelected || !$this->_selectedVariant->_hasQuantityComparisonUnitSelf) {
					return $this->_quantityComparisonUnit ? true : false;
				} else {
					$this->_selectedVariant->_hasQuantityComparisonUnitSelf;
				}
				break;

            case '_useGroupRestrictions':
                return $this->mainData['useGroupRestrictions'];
                break;

            case '_allowedGroups':
                return StringUtil::deserialize($this->mainData['allowedGroups'], true);
                break;

			case '_pages'
				/* ## DESCRIPTION:
Returns an Array containing the pages which the product is assigned to
				 */
				:
				$arr_pages = StringUtil::deserialize($this->mainData['pages']);

				$arr_pagesForDomain = array();
				foreach ($arr_pages as $int_pageID) {
					$pageInfo = PageModel::findWithDetails($int_pageID);
					if (!is_object($objPage) || $pageInfo->domain == $objPage->domain) {
						$arr_pagesForDomain[] = $int_pageID;
					}
				}
				$arr_pages = $arr_pagesForDomain;

				return $arr_pages;
				break;

			case '_variants'
				/* ## DESCRIPTION:
Array. If the product has variants, this array contains all the variant objects.
				 */
				:
				return $this->ls_variants;
				break;

			case '_hasVariants':
				return is_array($this->_variants) && count($this->_variants);
				break;

			case '_variantIsSelected'
				/* ## DESCRIPTION:
returns true/false, indicates whether a variant of this product has currently been selected
				 */
				:
				return $this->ls_currentVariantID && is_object($this->ls_variants[$this->ls_currentVariantID] ?? null) ? true : false;
				break;

			case '_selectedVariantID'
				/* ## DESCRIPTION:
returns the id of the variant that has currently been selected
				 */
				:
				if ($this->_variantIsSelected) {
					return $this->ls_currentVariantID;
				} else {
					return 0;
				}
				break;

			case '_selectedVariant':
				return $this->_variantIsSelected ? $this->ls_variants[$this->_selectedVariantID] : false;
				break;

			case '_alias':
				return $this->currentLanguageData['alias'] ? $this->currentLanguageData['alias'] : $this->mainData['alias'];
				break;

			case '_linkToProduct':
				if (!isset($objPage) || !is_object($objPage)) {
					return '';
				}
				return $this->getlinkToProduct();
				break;

			case '_link':
				return $this->_linkToProduct;
				break;

			case '_quantityComparisonText':
				return $this->getMengenvergleichsangabe($this->_priceMinimumAfterTax, $this->_quantityComparisonUnit, $this->_quantityComparisonDivisor);
				break;

			case '_taxInfo':
				return ls_shop_generalHelper::getMwstInfo($this->_hasVariants ? $this->_priceMinimumAfterTax : $this->_priceAfterTax, $this->_steuersatz);
				break;

			case '_unscaledTaxInfo':
				return ls_shop_generalHelper::getMwstInfo($this->_hasVariants ? $this->_unscaledPriceMinimumAfterTax : $this->_unscaledPriceAfterTax, $this->_steuersatz);
				break;

			case '_shippingInfo':
				return ls_shop_generalHelper::getVersandkostenInfo();
				break;

			case '_quantityInput':
				return ls_shop_generalHelper::getQuantityInput($this);
				break;

			case '_hasQuantityInput':
				/*-->
				 * Ein Mengen-Eingabefelde gibt es unter folgenden Voraussetzungen:
				 * 1. Es handelt sich nicht um einen variantenbezogenen Aufruf und das Produkt hat auch keine Varianten
				 * oder
				 * 2. Es handelt sich um einen variantenbezogenen Aufruf und das Produkt hat Varianten
				 <--*/
				return (!$this->ls_currentVariantID && !$this->_hasVariants) || ($this->ls_currentVariantID && $this->_hasVariants);
				break;

			case '_quantityDecimals':
				return $this->mainData['lsShopProductQuantityDecimals'];
				break;

			case '_isFavorite':
				$obj_user = System::importStatic('Contao\FrontendUser');
				$strFavorites = isset($obj_user->merconis_favoriteProducts) ? $obj_user->merconis_favoriteProducts : '';
				$arrFavorites = $strFavorites ? StringUtil::deserialize($strFavorites) : array();
				$arrFavorites = is_array($arrFavorites) ? $arrFavorites : array();

				return in_array($this->_id, $arrFavorites);
				break;

			case '_favoritesForm':
				return ls_shop_generalHelper::getFavoritesForm($this);
				break;

			case '_isOnRestockInfoList':
				$obj_user = System::importStatic('Contao\FrontendUser');

                $obj_dbres_restockInfoListRecord = Database::getInstance()
                    ->prepare("
                        SELECT      *
                        FROM        tl_ls_shop_restock_info_list
                        WHERE       productVariantId = ?
                            AND     memberId = ?
                    ")
                    ->execute(
                        $this->_productVariantID,
                        $obj_user->id
                    );

                return (bool) $obj_dbres_restockInfoListRecord->numRows;
				break;

			case '_restockInfoListForm':
				return ls_shop_generalHelper::getRestockInfoListForm($this);
				break;



			/*-->
			 * Preise
			 <--*/
			case '_useScalePrice':
				return isset($this->mainData['useScalePrice']) && $this->mainData['useScalePrice'];
				break;

			case '_scalePriceType':
				return $this->_useScalePrice ? $this->mainData['scalePriceType'] : null;
				break;

			case '_scalePriceQuantityDetectionMethod':
				return $this->mainData['scalePriceQuantityDetectionMethod'];
				break;

			case '_scalePriceQuantityDetectionAlwaysSeparateConfigurations':
				return $this->mainData['scalePriceQuantityDetectionAlwaysSeparateConfigurations'];
				break;

			case '_scalePriceQuantity':
				return isset($GLOBALS['merconis_globals']['temporarilyFixedScalePriceQuantity']) ? $GLOBALS['merconis_globals']['temporarilyFixedScalePriceQuantity'] : ls_shop_generalHelper::getScalePriceQuantityForProductOrVariant('product', $this);
				break;

			case '_scalePriceKeyword':
				return $this->mainData['scalePriceKeyword'];
				break;

			case '_scalePrice':
				if (!$this->_useScalePrice) {
					return null;
				}
				$scalePrice = isset($this->mainData['scalePrice']) ? json_decode($this->mainData['scalePrice']) : null;
				return is_array($scalePrice) ? createMultidimensionalArray(createOneDimensionalArrayFromTwoDimensionalArray($scalePrice), 2, 0, array('minQuantity', 'price')) : null;
				break;

			case '_scalePricesOutput'
				/* ## DESCRIPTION:
You can use this property if you want easy access to the full scaled base price, configurator price and combined price. Be aware, that performance might be an issue
if you display many price scales for many products or variants at the same time. If you have performance problems, please consider using only the actually required data
with the separately existing properties &quot;_scalePricesOutputUnconfigured&quot;, &quot;_scalePricesOutputConfigurator&quot; and &quot;_scalePricesOutputConfigured&quot;.
				 */
				 :
				return $this->getScalePricesOutput('all');
				break;

			case '_scalePricesOutputConfigured':
				return $this->getScalePricesOutput('configured');
				break;

			case '_scalePricesOutputConfigurator':
				return $this->getScalePricesOutput('configurator');
				break;

			case '_scalePricesOutputUnconfigured':
				return $this->getScalePricesOutput('unconfigured');
				break;

			case '_priceModificationByConfigurator':
				$this->createObjConfigurator();
				return $this->ls_objConfigurator->getPriceModification();
				break;

			case '_unscaledPriceModificationByConfigurator':
				$this->createObjConfigurator();
				return $this->ls_objConfigurator->getUnscaledPriceModification();
				break;

			case '_priceModificationByConfiguratorFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceModificationByConfigurator);
				break;

			case '_unscaledPriceModificationByConfiguratorFormatted':
				return ls_shop_generalHelper::outputPrice($this->_unscaledPriceModificationByConfigurator);
				break;

			case '_priceBeforeTax':
				$priceBeforeTax = $this->mainData['lsShopProductPrice'];
				$priceBeforeTax = ls_shop_generalHelper::calculateScaledPrice($priceBeforeTax, $this);
				return $priceBeforeTax;
				break;

			case '_unscaledPriceBeforeTax':
				$priceBeforeTax = $this->mainData['lsShopProductPrice'];
				return $priceBeforeTax;
				break;

			case '_priceBeforeConfiguratorAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_priceBeforeTax, $this->_steuersatz);
				break;

			case '_unscaledPriceBeforeConfiguratorAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_unscaledPriceBeforeTax, $this->_steuersatz);
				break;

			case '_priceBeforeConfiguratorAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceBeforeConfiguratorAfterTax);
				break;

			case '_unscaledPriceBeforeConfiguratorAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_unscaledPriceBeforeConfiguratorAfterTax);
				break;

			case '_priceAfterTax':
				return ls_add($this->_priceBeforeConfiguratorAfterTax, $this->_priceModificationByConfigurator);

				break;

			case '_unscaledPriceAfterTax':
				return ls_add($this->_unscaledPriceBeforeConfiguratorAfterTax, $this->_unscaledPriceModificationByConfigurator);

				break;

			case '_priceAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceAfterTax);
				break;

			case '_unscaledPriceAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_unscaledPriceAfterTax);
				break;

			case '_priceCheapestVariantBeforeTax':
                $float_lowestPriceBeforeTaxFromHook = null;

                if (isset($GLOBALS['MERCONIS_HOOKS']['getProductData_priceCheapestVariantBeforeTax']) && is_array($GLOBALS['MERCONIS_HOOKS']['getProductData_priceCheapestVariantBeforeTax'])) {
                    foreach ($GLOBALS['MERCONIS_HOOKS']['getProductData_priceCheapestVariantBeforeTax'] as $mccb) {
                        $objMccb = System::importStatic($mccb[0]);
                        $float_lowestPriceBeforeTaxFromHook = $objMccb->{$mccb[1]}($this);
                    }
                }

                if ($float_lowestPriceBeforeTaxFromHook !== null) {
                    return $float_lowestPriceBeforeTaxFromHook;
                }

				$lowestPriceBeforeTax = $this->_priceBeforeTax;
				$count = 0;
				foreach ($this->_variants as $variant) {
					/*-->
					 * Beim ersten Durchlauf, also der ersten geprüften Variante, wird der Variantenpreis auch dann als niedrigster
					 * Variantenpreis eingetragen, wenn er höher ist als der bislang hinterlegte Produktpreis. Dies ist notwendig,
					 * um sicherzustellen, dass nicht ein im Falle hinterlegter Varianten überhaupt nicht relevanter Produktpreis
					 * angegeben wird, nur weil der niedriger ist als der tatsächlich relevante Variantenpreis. Sonst könnte es passieren,
					 * dass als niedrigster Preis ein Preis angegeben wird, der de facto überhaupt nicht existiert bzw. zu dem das
					 * Produkt überhaupt nicht bestellt werden kann.
					 <--*/
					if ($variant->_priceBeforeTax < $lowestPriceBeforeTax || $count == 0) {
						$lowestPriceBeforeTax = $variant->_priceBeforeTax;
					}
					$count++;
				}
				return $lowestPriceBeforeTax;
				break;

			case '_unscaledPriceCheapestVariantBeforeTax':
			    $float_lowestPriceBeforeTaxFromHook = null;

			    if (isset($GLOBALS['MERCONIS_HOOKS']['getProductData_unscaledPriceCheapestVariantBeforeTax']) && is_array($GLOBALS['MERCONIS_HOOKS']['getProductData_unscaledPriceCheapestVariantBeforeTax'])) {
                    foreach ($GLOBALS['MERCONIS_HOOKS']['getProductData_unscaledPriceCheapestVariantBeforeTax'] as $mccb) {
                        $objMccb = System::importStatic($mccb[0]);
                        $float_lowestPriceBeforeTaxFromHook = $objMccb->{$mccb[1]}($this);
                    }
                }

			    if ($float_lowestPriceBeforeTaxFromHook !== null) {
			        return $float_lowestPriceBeforeTaxFromHook;
                }

				$lowestPriceBeforeTax = $this->_unscaledPriceBeforeTax;
				$count = 0;
				foreach ($this->_variants as $variant) {
					/*-->
					 * Beim ersten Durchlauf, also der ersten geprüften Variante, wird der Variantenpreis auch dann als niedrigster
					 * Variantenpreis eingetragen, wenn er höher ist als der bislang hinterlegte Produktpreis. Dies ist notwendig,
					 * um sicherzustellen, dass nicht ein im Falle hinterlegter Varianten überhaupt nicht relevanter Produktpreis
					 * angegeben wird, nur weil der niedriger ist als der tatsächlich relevante Variantenpreis. Sonst könnte es passieren,
					 * dass als niedrigster Preis ein Preis angegeben wird, der de facto überhaupt nicht existiert bzw. zu dem das
					 * Produkt überhaupt nicht bestellt werden kann.
					 <--*/
					if ($variant->_unscaledPriceBeforeTax < $lowestPriceBeforeTax || $count == 0) {
						$lowestPriceBeforeTax = $variant->_unscaledPriceBeforeTax;
					}
					$count++;
				}
				return $lowestPriceBeforeTax;
				break;

			case '_priceCheapestVariantAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_priceCheapestVariantBeforeTax, $this->_steuersatz);
				break;

			case '_unscaledPriceCheapestVariantAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_unscaledPriceCheapestVariantBeforeTax, $this->_steuersatz);
				break;

			case '_priceCheapestVariantAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceCheapestVariantAfterTax);
				break;

			case '_unscaledPriceCheapestVariantAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_unscaledPriceCheapestVariantAfterTax);
				break;

			case '_priceMinimumBeforeTax'
				/* ## DESCRIPTION:
returns the product price or the cheapest variant price.
				 */
				:
				return !$this->_pricesAreDifferent ? $this->_priceBeforeTax : $this->_priceCheapestVariantBeforeTax;
				break;

			case '_unscaledPriceMinimumBeforeTax'
				/* ## DESCRIPTION:
returns the product price or the cheapest variant price.
				If there is only one variant, the min-variant price is always used. If there is no variant or more than
				one variant, the 'unscaledCheckForDifferentPrices' runs (which does not detect any price difference for
				a variant and therefore returns the main product price)
				 */
				:
                if (count($this->_variants) == 1) {
                    return $this->_unscaledPriceCheapestVariantBeforeTax;
                } else {
                    return !$this->_unscaledPricesAreDifferent ? $this->_unscaledPriceBeforeTax : $this->_unscaledPriceCheapestVariantBeforeTax;
                }
				break;

            case '_scaledOrVariantsPriceMinimum'
                /* ## DESCRIPTION:
                 * The logic that previously ran in 2 templates has been moved here
                 * The cheapest variant or scale price is determined (for the entire product) - especially for the output
                 * in the product overview
                 * All variants are looped through - if available - and then all scaled prices - if available.
                 */
                :

                $float_cheapestPrice = null;
                $str_cheapestPriceOutput = null;
                $str_minQuantityInfo = null;
                $bln_cheapestPriceComesFromScalePrices = false;

                if ($this->_hasVariants) {
                    foreach ($this->_variants as $obj_variant){
                        if (is_array($obj_variant->_scalePricesOutputUnconfigured)) {
                            $this->getMinScalePrice($obj_variant->_scalePricesOutputUnconfigured,$float_cheapestPrice, $str_cheapestPriceOutput, $str_minQuantityInfo, $bln_cheapestPriceComesFromScalePrices);
                        } else {
                            if (!$float_cheapestPrice || $obj_variant->_priceAfterTax < $float_cheapestPrice) {
                                $float_cheapestPrice = $obj_variant->_priceAfterTax;
                                $str_cheapestPriceOutput = ls_shop_generalHelper::outputPrice($float_cheapestPrice);
                            }
                        }
                    }
                } else {
                    if (is_array($this->_scalePricesOutputUnconfigured)) {
                        $this->getMinScalePrice($this->_scalePricesOutputUnconfigured,$float_cheapestPrice, $str_cheapestPriceOutput, $str_minQuantityInfo, $bln_cheapestPriceComesFromScalePrices);
                    }
                }

                return array($bln_cheapestPriceComesFromScalePrices, $str_cheapestPriceOutput, $str_minQuantityInfo);
                break;

			case '_priceMinimumAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_priceMinimumBeforeTax, $this->_steuersatz);
				break;

			case '_unscaledPriceMinimumAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_unscaledPriceMinimumBeforeTax, $this->_steuersatz);
				break;

			case '_priceMinimumAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceMinimumAfterTax);
				break;

			case '_unscaledPriceMinimumAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_unscaledPriceMinimumAfterTax);
				break;

			case '_useOldPrice':
				return $this->mainData['useOldPrice'];
				break;

			case '_priceOldBeforeTax':
				return $this->_useOldPrice ? $this->mainData['lsShopProductPriceOld'] : 0;
				break;

			case '_priceOldAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_priceOldBeforeTax, $this->_steuersatz);
				break;

			case '_priceOldAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceOldAfterTax);
				break;

			case '_priceOldCheapestVariantBeforeTax':
				$lowestPriceOldBeforeTax = $this->_priceOldBeforeTax;
				$count = 0;
				foreach ($this->_variants as $variant) {
					/*-->
					 * Beim ersten Durchlauf, also der ersten geprüften Variante, wird der Variantenpreis auch dann als niedrigster
					 * Variantenpreis eingetragen, wenn er höher ist als der bislang hinterlegte Produktpreis. Dies ist notwendig,
					 * um sicherzustellen, dass nicht ein im Falle hinterlegter Varianten überhaupt nicht relevanter Produktpreis
					 * angegeben wird, nur weil der niedriger ist als der tatsächlich relevante Variantenpreis. Sonst könnte es passieren,
					 * dass als niedrigster Preis ein Preis angegeben wird, der de facto überhaupt nicht existiert bzw. zu dem das
					 * Produkt überhaupt nicht bestellt werden kann.
					 <--*/
					if ($variant->_priceOldBeforeTax < $lowestPriceOldBeforeTax || $count == 0) {
						$lowestPriceOldBeforeTax = $variant->_priceOldBeforeTax;
					}
					$count++;
				}
				return $lowestPriceOldBeforeTax;
				break;

			case '_priceOldCheapestVariantAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_priceOldCheapestVariantBeforeTax, $this->_steuersatz);
				break;

			case '_priceOldCheapestVariantAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceOldCheapestVariantAfterTax);
				break;

			case '_priceOldMinimumBeforeTax'
				/* ## DESCRIPTION:
returns the product price or the cheapest variant price.
				 */
				:
				return !$this->_oldPricesAreDifferent ? $this->_priceOldBeforeTax : $this->_priceOldCheapestVariantBeforeTax;
				break;

			case '_priceOldMinimumAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_priceOldMinimumBeforeTax, $this->_steuersatz);
				break;

			case '_priceOldMinimumAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceOldMinimumAfterTax);
				break;

			case '_pricesAreDifferent':
				return $this->checkForDifferentPrices();
				break;

			case '_unscaledPricesAreDifferent':
				return $this->unscaledCheckForDifferentPrices();
				break;

			case '_oldPricesAreDifferent':
				return $this->_priceOldBeforeTax != $this->_priceOldCheapestVariantBeforeTax ? true : false;
				break;

			case '_hasOldPrice':
				return $this->_useOldPrice ? true : false;
				break;

			case '_priceControl':
				ob_start();
				?>
				<div class="ls_shop_priceControl">
					<style type="text/css" scoped>
						.ls_shop_priceControl {
							position: relative;
							left: 0px;
							right: 0px;
							top: 0px;
							bottom: 0px;
							background-color: #FFFFAA;
							overflow: scroll;
							padding: 20px;
						}

						.ls_shop_priceControl .fullsizetoggler {
							position: absolute;
							top: 10px;
							right: 10px;
							cursor: pointer;
						}

						.ls_shop_priceControl table {
							border-collapse: collapse;
						}

						.ls_shop_priceControl table td,
						.ls_shop_priceControl table th {
							border: 1px solid #000000;
							padding: 5px;
							white-space: nowrap;
						}
					</style>
					<h2>Price control</h2>
					<div class="fullsizetoggler" onclick="this.getParent('.ls_shop_priceControl').setStyle('position', this.getParent('.ls_shop_priceControl').getStyle('position') === 'relative' ? 'fixed' : 'relative');">(toggle fullsize)</div>
					<h3>Product &quot;<?php echo $this->_title; ?>&quot;</h3>
					<div class="info">different prices: <?php echo $this->_pricesAreDifferent ? 'yes' : 'no'; ?></div>
					<div class="info">has old price: <?php echo $this->_hasOldPrice ? 'yes' : 'no'; ?></div>
					<div class="info">different old prices: <?php echo $this->_oldPricesAreDifferent ? 'yes' : 'no'; ?></div>
					<div class="info">quantityComparisonUnit: <?php echo $this->_quantityComparisonUnit; ?></div>
					<div class="info">quantityComparisonDivisor: <?php echo $this->_quantityComparisonDivisor; ?></div>
					<div>&nbsp;</div>
					<table>
						<thead>
							<tr>
								<th></th>
								<th>product price</th>
								<th></th>
								<th>price cheapest variant</th>
								<th></th>
								<th>price minimum</th>
								<th></th>
								<th>old product price</th>
								<th></th>
								<th>old price cheapest variant</th>
								<th></th>
								<th>old price minimum</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="label">beforeTax</td>

								<td title="_priceBeforeTax"><?php echo $this->_priceBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_priceBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceBeforeTax'); ?></td>

								<td title="_priceCheapestVariantBeforeTax"><?php echo $this->_priceCheapestVariantBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_priceCheapestVariantBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceCheapestVariantBeforeTax'); ?></td>

								<td title="_priceMinimumBeforeTax"><?php echo $this->_priceMinimumBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_priceMinimumBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceMinimumBeforeTax'); ?></td>

								<td title="_priceOldBeforeTax"><?php echo $this->_priceOldBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_priceOldBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceOldBeforeTax'); ?></td>

								<td title="_priceOldCheapestVariantBeforeTax"><?php echo $this->_priceOldCheapestVariantBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_priceOldCheapestVariantBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceOldCheapestVariantBeforeTax'); ?></td>

								<td title="_priceOldMinimumBeforeTax"><?php echo $this->_priceOldMinimumBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_priceOldMinimumBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceOldMinimumBeforeTax'); ?></td>
							</tr>
							<tr>
								<td class="label">beforeTax (unscaled)</td>

								<td title="_unscaledPriceBeforeTax"><?php echo $this->_unscaledPriceBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceBeforeTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceBeforeTax'); ?></td>

								<td title="_unscaledPriceCheapestVariantBeforeTax"><?php echo $this->_unscaledPriceCheapestVariantBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceCheapestVariantBeforeTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceCheapestVariantBeforeTax'); ?></td>

								<td title="_unscaledPriceMinimumBeforeTax"><?php echo $this->_unscaledPriceMinimumBeforeTax; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceMinimumBeforeTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceMinimumBeforeTax'); ?></td>

								<td colspan="6"></td>
							</tr>
							<tr>
								<td class="label">beforeConfiguratorAfterTax</td>

								<td title="_priceBeforeConfiguratorAfterTax"><?php echo $this->_priceBeforeConfiguratorAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_priceBeforeConfiguratorAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceBeforeConfiguratorAfterTax'); ?></td>

								<td colspan="10"></td>
							</tr>
							<tr>
								<td class="label">beforeConfiguratorAfterTax (unscaled)</td>

								<td title="_unscaledPriceBeforeConfiguratorAfterTax"><?php echo $this->_unscaledPriceBeforeConfiguratorAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceBeforeConfiguratorAfterTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceBeforeConfiguratorAfterTax'); ?></td>

								<td colspan="10"></td>
							</tr>
							<tr>
								<td class="label">beforeConfiguratorAfterTaxFormatted</td>
								<td title="_priceBeforeConfiguratorAfterTaxFormatted"><?php echo $this->_priceBeforeConfiguratorAfterTaxFormatted; ?></td>
								<td colspan="11"></td>
							</tr>
							<tr>
								<td class="label">beforeConfiguratorAfterTaxFormatted (unscaled)</td>
								<td title="_unscaledPriceBeforeConfiguratorAfterTaxFormatted"><?php echo $this->_unscaledPriceBeforeConfiguratorAfterTaxFormatted; ?></td>
								<td colspan="11"></td>
							</tr>
							<tr>
								<td class="label">modificationByConfigurator</td>

								<td title="_priceModificationByConfigurator"><?php echo $this->_priceModificationByConfigurator; ?></td>
								<td title="_getQuantityComparisonText('_priceModificationByConfigurator')"><?php echo $this->_getQuantityComparisonText('_priceModificationByConfigurator'); ?></td>

								<td colspan="10"></td>
							</tr>
							<tr>
								<td class="label">modificationByConfigurator (unscaled)</td>

								<td title="_unscaledPriceModificationByConfigurator"><?php echo $this->_unscaledPriceModificationByConfigurator; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceModificationByConfigurator')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceModificationByConfigurator'); ?></td>

								<td colspan="10"></td>
							</tr>
							<tr>
								<td class="label">modificationByConfiguratorFormatted</td>
								<td title="_priceModificationByConfiguratorFormatted"><?php echo $this->_priceModificationByConfiguratorFormatted; ?></td>
								<td colspan="11"></td>
							</tr>
							<tr>
								<td class="label">modificationByConfiguratorFormatted (unscaled)</td>
								<td title="_unscaledPriceModificationByConfiguratorFormatted"><?php echo $this->_unscaledPriceModificationByConfiguratorFormatted; ?></td>
								<td colspan="11"></td>
							</tr>
							<tr>
								<td class="label">afterTax</td>

								<td title="_priceAfterTax"><?php echo $this->_priceAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_priceAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceAfterTax'); ?></td>

								<td title="_priceCheapestVariantAfterTax"><?php echo $this->_priceCheapestVariantAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_priceCheapestVariantAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceCheapestVariantAfterTax'); ?></td>

								<td title="_priceMinimumAfterTax"><?php echo $this->_priceMinimumAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_priceMinimumAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceMinimumAfterTax'); ?></td>

								<td title="_priceOldAfterTax"><?php echo $this->_priceOldAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_priceOldAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceOldAfterTax'); ?></td>

								<td title="_priceOldCheapestVariantAfterTax"><?php echo $this->_priceOldCheapestVariantAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_priceOldCheapestVariantAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceOldCheapestVariantAfterTax'); ?></td>

								<td title="_priceOldMinimumAfterTax"><?php echo $this->_priceOldMinimumAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_priceOldMinimumAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceOldMinimumAfterTax'); ?></td>
							</tr>
							<tr>
								<td class="label">afterTax (unscaled)</td>

								<td title="_unscaledPriceAfterTax"><?php echo $this->_unscaledPriceAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceAfterTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceAfterTax'); ?></td>

								<td title="_unscaledPriceCheapestVariantAfterTax"><?php echo $this->_unscaledPriceCheapestVariantAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceCheapestVariantAfterTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceCheapestVariantAfterTax'); ?></td>

								<td title="_unscaledPriceMinimumAfterTax"><?php echo $this->_unscaledPriceMinimumAfterTax; ?></td>
								<td title="_getQuantityComparisonText('_unscaledPriceMinimumAfterTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceMinimumAfterTax'); ?></td>

								<td colspan="6"></td>
							</tr>
							<tr>
								<td class="label">afterTaxFormatted</td>
								<td title="_priceAfterTaxFormatted"><?php echo $this->_priceAfterTaxFormatted; ?></td>
								<td></td>
								<td title="_priceCheapestVariantAfterTaxFormatted"><?php echo $this->_priceCheapestVariantAfterTaxFormatted; ?></td>
								<td></td>
								<td title="_priceMinimumAfterTaxFormatted"><?php echo $this->_priceMinimumAfterTaxFormatted; ?></td>
								<td></td>
								<td title="_priceOldAfterTaxFormatted"><?php echo $this->_priceOldAfterTaxFormatted; ?></td>
								<td></td>
								<td title="_priceOldCheapestVariantAfterTaxFormatted"><?php echo $this->_priceOldCheapestVariantAfterTaxFormatted; ?></td>
								<td></td>
								<td title="_priceOldMinimumAfterTaxFormatted"><?php echo $this->_priceOldMinimumAfterTaxFormatted; ?></td>
								<td></td>
							</tr>
							<tr>
								<td class="label">afterTaxFormatted (unscaled)</td>
								<td title="_unscaledPriceAfterTaxFormatted"><?php echo $this->_unscaledPriceAfterTaxFormatted; ?></td>
								<td></td>
								<td title="_unscaledPriceCheapestVariantAfterTaxFormatted"><?php echo $this->_unscaledPriceCheapestVariantAfterTaxFormatted; ?></td>
								<td></td>
								<td title="_unscaledPriceMinimumAfterTaxFormatted"><?php echo $this->_unscaledPriceMinimumAfterTaxFormatted; ?></td>
								<td colspan="7"></td>
							</tr>
						</tbody>
					</table>
					<div>&nbsp;</div>
					<div class="info">
						scalePricesOutput:
						<?php
							if (!$this->_scalePricesOutput) {
								?>
								no scale prices
								<?php
							} else {
								?>
								<table>
									<thead>
										<tr>
											<th>minQuantity</th>
											<th>priceUnconfigured</th>
											<th>priceUnconfiguredUnformatted</th>
											<th>priceUnconfiguredQuantityComparison</th>
											<th>priceConfigured</th>
											<th>priceConfiguredUnformatted</th>
											<th>priceConfiguredQuantityComparison</th>
											<th>priceConfigurator</th>
											<th>priceConfiguratorUnformatted</th>
											<th>priceConfiguratorQuantityComparison</th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ($this->_scalePricesOutput as $int_scalePriceKey => $arr_scalePriceOutputStep) {
											?>
											<tr>
												<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['minQuantity']"><?php echo $arr_scalePriceOutputStep['minQuantity']; ?></td>

												<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceUnconfigured']"><?php echo $arr_scalePriceOutputStep['priceUnconfigured']; ?></td>
												<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceUnconfiguredUnformatted']"><?php echo $arr_scalePriceOutputStep['priceUnconfiguredUnformatted']; ?></td>
												<td title="_getQuantityComparisonText($obj_product->_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceUnconfiguredUnformatted'])"><?php echo $this->_getQuantityComparisonText($arr_scalePriceOutputStep['priceUnconfiguredUnformatted']); ?></td>

												<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfigured']"><?php echo $arr_scalePriceOutputStep['priceConfigured']; ?></td>
												<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguredUnformatted']"><?php echo $arr_scalePriceOutputStep['priceConfiguredUnformatted']; ?></td>
												<td title="_getQuantityComparisonText($obj_product->_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguredUnformatted'])"><?php echo $this->_getQuantityComparisonText($arr_scalePriceOutputStep['priceConfiguredUnformatted']); ?></td>

												<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfigurator']"><?php echo $arr_scalePriceOutputStep['priceConfigurator']; ?></td>
												<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguratorUnformatted']"><?php echo $arr_scalePriceOutputStep['priceConfiguratorUnformatted']; ?></td>
												<td title="_getQuantityComparisonText($obj_product->_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguratorUnformatted'])"><?php echo $this->_getQuantityComparisonText($arr_scalePriceOutputStep['priceConfiguratorUnformatted']); ?></td>
											</tr>
											<?php
										}
										?>
									</tbody>
								</table>
								<?php
							}
						?>
					</div>
					<h3>Variants</h3>
					<?php
						if (!$this->_hasVariants) {
							?>
							<p>Product has no variants</p>
							<?php
						} else {
							foreach ($this->ls_variants as $variant) {
								?>
								<div class="priceControlVariant">
									<h4><?php echo $variant->_title; ?></h4>
									<div><?php echo $variant->_priceControl; ?></div>
								</div>
								<?php
							}
						}
					?>
					<div style="clear: both; overflow: hidden; height: 1px;">&nbsp;</div>
				</div>
				<?php
				$outputBuffer = ob_get_contents();
				ob_end_clean();
				return $outputBuffer;
				break;



			/*-->
			 * Ende Preise
			 <--*/

			case '_attributes':
				return ls_shop_generalHelper::processProductAttributesValues(json_decode($this->mainData['lsShopProductAttributesValues']));
				break;

			case '_attributeValueIds':
				return ls_shop_generalHelper::getProductAttributeValueIds(json_decode($this->mainData['lsShopProductAttributesValues']));
				break;

			case '_attributesAsString':
				return ls_shop_generalHelper::createAttributesString($this->_attributes);
				break;

			case '_allVariantAttributes':
				return $this->getAllVariantAttributes();
				break;

			case '_allExistingCombinationsOfVariantAttributes':
				$arr_existingCombinations = array();
				foreach ($this->_variants as $obj_variant) {
					$arr_combinationsForThisVariant = array();

					$arr_valuesForAttributes = array();
					foreach ($obj_variant->_attributes as $arr_attributeValues) {
						foreach ($arr_attributeValues as $arr_attributeValue) {
							$arr_valuesForAttributes[$arr_attributeValue['attributeID']][] = $arr_attributeValue['valueID'];
						}
					}

					$arr_combinations = \LeadingSystems\Helpers\create_arrayCombinations($arr_valuesForAttributes);
					$arr_existingCombinations = array_merge($arr_existingCombinations, $arr_combinations);
				}

				return $arr_existingCombinations;
				break;

			case '_filterMatch'
				/* ## DESCRIPTION:
returns "complete" if the whole product matches a currently active filter,
"partial" if one or more variants of the product match the filter
or "none" if the product does not match the active filter. If there is no
filter context, NULL will be returned.
				 *
				 */
				:
				return isset($_SESSION['lsShop']['filter']['matchedProducts'][$this->_id]) ? $_SESSION['lsShop']['filter']['matchedProducts'][$this->_id] : null;
				break;


			/* ## STOP AUTO DOCUMENTATION PROPERTIES PRODUCT ## */
			case 'productExists':
				return $this->ls_data === null ? false : true;
				break;

			case '_steuersatz':
				$taxClass = $this->mainData['lsShopProductSteuersatz'];
                if (isset($GLOBALS['MERCONIS_HOOKS']['getProductTaxClass']) && is_array($GLOBALS['MERCONIS_HOOKS']['getProductTaxClass'])) {
                    foreach ($GLOBALS['MERCONIS_HOOKS']['getProductTaxClass'] as $mccb) {
                        $objMccb = System::importStatic($mccb[0]);
                        $taxClass = $objMccb->{$mccb[1]}($this, $taxClass);
                    }
                }
                return $taxClass;
				break;

			case '_quantityComparisonDivisor':
				if (!$this->_variantIsSelected || !$this->_selectedVariant->_hasQuantityComparisonDivisorSelf) {
					return $this->mainData['lsShopProductMengenvergleichDivisor'];
				} else {
					return $this->_selectedVariant->_quantityComparisonDivisorSelf;
				}
				break;

			case '_hasQuantityComparisonDivisor':
				if (!$this->_variantIsSelected || !$this->_selectedVariant->_hasQuantityComparisonDivisorSelf) {
					return $this->_quantityComparisonDivisor > 0 ? true : false;
				} else {
					return $this->_selectedVariant->_hasQuantityComparisonDivisorSelf;
				}
				break;

			case '_allImages':
				return ls_shop_generalHelper::getAllProductImages($this, $this->_code, $this->mainData['lsShopProductMainImage'], $this->mainData['lsShopProductMoreImages']);
				break;

            case '_availableFrom':
                return $this->mainData['availableFrom'] ?: null;
                break;

            case '_isAvailableBasedOnDate':
                $bln_isAvailableBasedOnDate = is_null($this->_availableFrom) || time() >= $this->_availableFrom;
                return $bln_isAvailableBasedOnDate;
                break;

            case '_isPreorderable':
                $bln_isPreorderable = !$this->_isAvailableBasedOnDate && $this->mainData['preorderingAllowed'];
                return $bln_isPreorderable;
                break;

            case '_searchDebug':
                return isset($GLOBALS['merconis_globals']['searchDebug'][$this->ls_ID]) ? $GLOBALS['merconis_globals']['searchDebug'][$this->ls_ID] : null;
                break;
		}

		return null;
	}

    /*  Determines the min price of the scale prices
     *  Because the function can be called for all variants of a product, some arguments are passed byref and thus retain their values
     *
     *  @param      $arr_scalePriceDetailsAll               array, list of scale-prices
     *  @param      $float_cheapestPrice                    float, byref min-price to compare
     *  @param      $str_cheapestPriceOutput                string, byref min-price as string
     *  @param      $str_minQuantityInfo                    string, byref, min-quantity from which the price applies
     *  @param      $bln_cheapestPriceComesFromScalePrices  boolean, byref, flag for output control in template
     *  @return     null
     */
    private function getMinScalePrice($arr_scalePriceDetailsAll, &$float_cheapestPrice, &$str_cheapestPriceOutput, &$str_minQuantityInfo, &$bln_cheapestPriceComesFromScalePrices)
    {
        $bln_cheapestPriceComesFromScalePrices = true;

        foreach ($arr_scalePriceDetailsAll as $arr_scalePriceDetails) {
            if (!$float_cheapestPrice || $arr_scalePriceDetails['priceUnconfiguredUnformatted'] < $float_cheapestPrice) {
                $float_cheapestPrice = $arr_scalePriceDetails['priceUnconfiguredUnformatted'];
                $str_cheapestPriceOutput = $arr_scalePriceDetails['priceUnconfigured'];
                $str_minQuantityInfo = $arr_scalePriceDetails['minQuantity'];
            }
        }
    }


	/*
	 * Getter- (bzw. Caller-)Funktion. Durch die Kommentare für AUTO DOCUMENTATION und DESCRIPTION können die
	 * hier verfügbaren Methoden in der automatischen Dokumentation dargestellt werden
	 */
	public function __call($what, $args) {
		switch ($what) {
			/* ## START AUTO DOCUMENTATION METHODS PRODUCT ## */
			case '_createGallery'
				/* ## DESCRIPTION:
use like this:
echo $this->objProduct->_createGallery(
'name_asc', <span class="comment">// image sorting, possible values: 'name_asc', 'name_desc', 'date_asc', 'date_desc', 'random'</span>
array(160, 160, 'box'), <span class="comment">// size of the main image, has to be an array, key 0: width, key 1: height, key 2: scaling method (possible values: 'crop', 'proportional', 'box')</span>
array(75, 75, 'box'), <span class="comment">// size of the other images, has to be an array, key 0: width, key 1: height, key 2: scaling method (possible values: 'crop', 'proportional', 'box')</span>
array('bottom' => 0, 'left' => 0, 'right' => 0, 'top' => 0, 'unit' => 'px'), <span class="comment">// margins for the images, has to be an array</span>
1, <span class="comment">// flag indicating whether to open the fullsize image as a lightbox, possible values 0 or 1</span>
'', <span class="comment">// name of a gallery template file (optional, defaults to 'template_productGallery_01)</span>
'', <span class="comment">// additional class names that will be used in the template and are therfore available for styling purposes</span>
array(), <span class="comment">// array containing names of additional overlay elements</span>
0 <span class="comment">// number of images returend in the gallery or 0 to disable the limit and output all available images</span>
);
				 */
				:
                trigger_error('Case ' . $what . ' is deprecated use $obj_product->getImageGallery() instead', E_USER_DEPRECATED);
				$args = ls_shop_generalHelper::setArrayLength($args, 8);
				$args[3] = is_array($args[3]) ? serialize($args[3]): $args[3];
				$args[7] = is_array($args[7]) ? serialize($args[7]): $args[7];
				return $this->ls_createGallery($this->_mainImageUnprocessed, $this->_moreImagesUnprocessed, $this->ls_currentProductVariantID, $args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7],$args[8]);
				break;

			case '_selectVariant'
				/* ## DESCRIPTION:
With this method it is possible to select a variant for a product. It takes the requested variant's ID or alias as an argument. If the requested variant ID or alias does not belong to the product, it won't be selected, so there's not necessarily a reason to check this before calling this method.
				 */
			:
				$args = ls_shop_generalHelper::setArrayLength($args, 1);
				$var_selectVariantID = $args[0];

				if (!$var_selectVariantID) {
					return false;
				}

				/*
				 * If the given variant does not solely consist of digits,
				 * it must be an alias that has to be translated
				 */
				if ($var_selectVariantID && !ctype_digit($var_selectVariantID)) {
					$var_selectVariantID = ls_shop_generalHelper::getVariantIdForAlias($var_selectVariantID);
				}

				/*
				 * the variant requested for selection does not belong to this product
				 */
				if (!key_exists($var_selectVariantID, $this->ls_variants)) {
					return false;
				}

				$this->ls_setVariantID($var_selectVariantID);
				return true;
				break;

			case '_deliveryTimeMessageInCart'
				/* ## DESCRIPTION:
This method can be used to get the delivery time message for this product regarding the quantity that is currently in the cart. It takes the required quantity as an argument and checks whether or not stock is sufficient.
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 1);
				$float_requiredQuantity = (float) $args[0];
				return $this->getDeliveryTimeMessage($float_requiredQuantity);
				break;

			case '_getNumMatchingVariantsByAttributeValues'
				/* ## DESCRIPTION:
This method takes an array holding attribute ids as keys and attribute value ids as values and returns the number of matching variants
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 1, array());
				$arr_requestedAttributeValues = $args[0];

				$int_numMatchingVariants = 0;

				$arr_matchingVariants = $this->_getVariantsByAttributeValues($arr_requestedAttributeValues);

				$int_numMatchingVariants = count($arr_matchingVariants);

				return $int_numMatchingVariants;
				break;

			case '_getAllVariantAttributesForMatchingVariants'
				/* ## DESCRIPTION:
This method takes an array holding attribute ids as keys and attribute value ids as values and returns all variant attributes of the matching variants
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 1, array());
				$arr_requestedAttributeValues = $args[0];
				$arr_matchingVariants = $this->_getVariantsByAttributeValues($arr_requestedAttributeValues);
				return $this->getAllVariantAttributes($arr_matchingVariants);
				break;

			case '_getPossibleAttributeValuesForCurrentSelection'
				/* ## DESCRIPTION:
This method takes an array holding attribute ids as keys and attribute value ids as values and returns all variant attribute values that would result in a match if they were chosen instead of the currently selected value for the attribute
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 2);

				/*
				 * The current selection is given as a parameter of this
				 * function call
				 */
				$arr_currentlySelectedAttributeValues = $args[0];
				if (!is_array($arr_currentlySelectedAttributeValues)) {
					$arr_currentlySelectedAttributeValues = array();
				}

				$bln_getNumMatches = $args[1];

				$arr_possibleAttributeValues = array();
				$arr_allAttributeValues = $this->getAllVariantAttributes();

				/*
				 * Walk through all attribute values and replace each selected
				 * attribute value with each value that - in theory - should be
				 * possible for the same attribute. Then we count the number of
				 * matching variants to find out which attribute value would
				 * acutally result in a match and therefore is a possible selection.
				 */
				/*
				 * FIXME: Important: Check performance!
				 */
				foreach ($arr_allAttributeValues as $int_attributeKey => $arr_attributeToCheck) {
					/*
					 * We create an entry for each attribute in the array that
					 * in the end should hold all possible attribute values and
					 * because we don't know yet which values are actually possible
					 * (i.e. would result in a match if they were chosen), we
					 * clear the 'values' array.
					 */
					$arr_possibleAttributeValues[$int_attributeKey] = $arr_attributeToCheck;
					$arr_possibleAttributeValues[$int_attributeKey]['values'] = array();

					foreach ($arr_attributeToCheck['values'] as $int_valueKey => $arr_valueToCheck) {
						/*
						 * We walk through each value of the attribute and create
						 * an attribute value selection based on the actual current
						 * selection but with the value to check as an alternative
						 * selection for actually selected value for the attribute.
						 *
						 * We do this so that we can count the number of matches
						 * that this alternative selection would have.
						 */
						$arr_modifiedSelectionToCheck = $arr_currentlySelectedAttributeValues;
						$arr_modifiedSelectionToCheck[$arr_attributeToCheck['attributeID']] = $arr_valueToCheck['valueID'];

						if ($bln_getNumMatches) {
							$int_numMatchesForModifiedSelection = $this->_getNumMatchingVariantsByAttributeValues($arr_modifiedSelectionToCheck);

							/*
							 * Only if the alternative selection with this value
							 * would have matches, we add this value to the result
							 * array.
							 */
							if ($int_numMatchesForModifiedSelection) {
								$arr_valueToCheck['numMatches'] = $int_numMatchesForModifiedSelection;
								$arr_possibleAttributeValues[$int_attributeKey]['values'][$int_valueKey] = $arr_valueToCheck;
							}
						} else {
							$bln_atLeastOneMatch = $this->_getVariantsByAttributeValues($arr_modifiedSelectionToCheck, true) !== null ? true : false;

							/*
							 * Only if the alternative selection with this value
							 * would have matches, we add this value to the result
							 * array.
							 */
							if ($bln_atLeastOneMatch) {
								$arr_possibleAttributeValues[$int_attributeKey]['values'][$int_valueKey] = $arr_valueToCheck;
							}
						}
					}
				}

				return $arr_possibleAttributeValues;
				break;

			case '_getVariantByAttributeValues'
				/* ## DESCRIPTION:
This method takes an array holding attribute ids as keys and attribute value ids as values and returns the variant object of the matching variant (the first match if more than one variant matches) or null if no variant matches. If the provided array holds an empty value for an attribute, no variant will match.
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 2);
				$arr_requestedAttributeValues = is_array($args[0]) ? $args[0] : [];
                $bln_returnFirstMatch = (bool) $args[1];

				return $this->_getVariantsByAttributeValues($arr_requestedAttributeValues, $bln_returnFirstMatch);
				break;

			case '_getVariantsByAttributeValues'
				/* ## DESCRIPTION:
This method takes an array holding attribute ids as keys and attribute value ids as values and returns the variant objects of the matching variants
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 2);
				$arr_requestedAttributeValues = $args[0];
				if (!is_array($arr_requestedAttributeValues)) {
					$arr_requestedAttributeValues = array();
				}

				$bln_returnFirstMatch = $args[1];

				$arr_matchingVariants = array();

				foreach ($this->_variants as $objVariant) {
					$blnMatches = true;
					foreach ($arr_requestedAttributeValues as $attributeID => $valueID) {
						if (!isset($objVariant->_attributes[$attributeID])) {
							$blnMatches = false;
							break;
						}

						$blnMatchForValue = false;
						foreach ($objVariant->_attributes[$attributeID] as $arrAttributeValue) {
							if ($arrAttributeValue['valueID'] == $valueID) {
								$blnMatchForValue = true;
								break;
							}
						}
						if (!$blnMatchForValue) {
							$blnMatches = false;
							break;
						}
					}
					if ($blnMatches) {
						if ($bln_returnFirstMatch) {
							return $objVariant;
						}
						$arr_matchingVariants[] = $objVariant;
					}
				}

				if ($bln_returnFirstMatch) {
					/*
					 * not match found because otherwise it would already have
					 * been returned
					 */
					return null;
				}

				return $arr_matchingVariants;
				break;

			case '_flexContentExists'
				/* ## DESCRIPTION:
This method checks whether or not a flexible product information with the given keyword exists
				 */
				:
				return isset($this->_flexContents[$args[0]]) && !empty($this->_flexContents[$args[0]]);
				break;

			case '_flexContentExistsLanguageIndependent'
				/* ## DESCRIPTION:
This method checks whether or not a language independent flexible product information with the given keyword exists
				 */
				:
				return isset($this->_flexContentsLanguageIndependent[$args[0]]) && !empty($this->_flexContentsLanguageIndependent[$args[0]]);
				break;

			case '_getQuantityComparisonText'
				/* ## DESCRIPTION:
This method creates and returns the quantity comparison text for a given price value
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 1, 0);
				return $this->getMengenvergleichsangabe(is_string($args[0]) ? $this->{$args[0]} : $args[0], $this->_quantityComparisonUnit, $this->_quantityComparisonDivisor);
				break;

			case '_preselectVariantIfPossible'
				/* ## DESCRIPTION:
This method checks whether the product has variants and none of them is currently selected. In this case the first variant will be selected. If a filter is currently active, the first variant that matches the filter will be selected. If no variant matches the filter the first variant will be selected.
				 */
				:
				if ($this->_hasVariants && !$this->_variantIsSelected) {
					$arrVariants = $this->_variants;
					reset($arrVariants);
					$firstKey = key($arrVariants);

					if ($arrVariants[$firstKey]->_filterMatch === false) {
						/*
						 * The first variant has been checked by the filter and does not
						 * match so we have to walk through all the other variants and look
						 * for the first variant that does.
						 */
						foreach ($arrVariants as $objVariant) {
							if ($objVariant->_filterMatch) {
								$this->ls_setVariantID($objVariant->_id);
								break;
							}
						}

						/*
						 * If there's still no variant selected, which means that none of the variants
						 * matches the filter, we select the first variant because it's still the
						 * best shot.
						 */
						if (!$this->_variantIsSelected) {
							$this->ls_setVariantID($arrVariants[$firstKey]->_id);
						}
					} else {
						/*
						 * The first variant has not been checked by the filter because the
						 * product itself matched completely or it doesn't match at all
						 * or the variant has been checked and matched the filter.
						 * In this case we simply select this variant.
						 */
						$this->ls_setVariantID($arrVariants[$firstKey]->_id);
					}
				}
				break;

            case '_useCustomTemplate'
                /* ## DESCRIPTION:
This method takes the name of a template file as an argument and returns the rendered output as an html string. Use this functionality to outsource parts of your product output in separate templates to keep things clean, well structured and easily reusable.
                 */
            :
                $args = ls_shop_generalHelper::setArrayLength($args, 2);
                $str_template = $args[0];
                $obj_template = new FrontendTemplate($str_template);
                $obj_template->objProduct = $this;
                $obj_template->arr_args = is_array($args[1]) ? $args[1] : [$args[1]];
                return $obj_template->parse();
                break;

			case '_hookedFunction'
				/* ## DESCRIPTION:
This method can be used to call a function hooked with the "callingHookedProductOrVariantFunction" hook. It takes one custom argument and passes it to the hooked function along with the product/variant object and the information whether the hook is called from a product or variant object. This custom argument can be used to trigger a specific behaviour in the hooked function.
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 1);
				$args[0];

				$var_return = null;

				if (isset($GLOBALS['MERCONIS_HOOKS']['callingHookedProductOrVariantFunction']) && is_array($GLOBALS['MERCONIS_HOOKS']['callingHookedProductOrVariantFunction'])) {
					foreach ($GLOBALS['MERCONIS_HOOKS']['callingHookedProductOrVariantFunction'] as $mccb) {
						$objMccb = System::importStatic($mccb[0]);
						$var_return = $objMccb->{$mccb[1]}($this, 'product', $args[0]);
					}
				}

				return $var_return;
				break;
			/* ## STOP AUTO DOCUMENTATION METHODS PRODUCT ## */

			case 'saveConfiguratorForCurrentCartKey':
				!$this->_variantIsSelected ? $this->createObjConfigurator() : $this->_selectedVariant->createObjConfigurator();
				if (!$this->_variantIsSelected) {
					$this->ls_objConfigurator->saveConfiguratorForCurrentCartKey();
				} else if ($this->_variantIsSelected) {
					$this->_selectedVariant->ls_objConfigurator->saveConfiguratorForCurrentCartKey();
				}
				break;

			case 'saveCustomizerForCurrentCartKey':
				if (!$this->_variantIsSelected) {
					$this->_customizer->saveCustomizerForCurrentCartKey();
				} else if ($this->_variantIsSelected) {
					$this->_selectedVariant->_customizer->saveCustomizerForCurrentCartKey();
				}
				break;

			default:
				return false;
				break;

		}
	}

	public function ls_getData() {
		$this->arr_originalData = ls_shop_languageHelper::getMultiLanguage($this->ls_ID, 'tl_ls_shop_product', 'all', 'all', true, false);

		if (is_array($this->arr_originalData)) {
			foreach ($this->arr_originalData as $languageKey => $arrLanguageData) {
				$this->arr_originalData[$languageKey]['lsShopProductDetailsTemplate'] = isset($this->arr_originalData[$languageKey]['lsShopProductDetailsTemplate']) && $this->arr_originalData[$languageKey]['lsShopProductDetailsTemplate'] ? $this->arr_originalData[$languageKey]['lsShopProductDetailsTemplate'] : $GLOBALS['TL_CONFIG']['ls_shop_productDetailsTemplate'];
			}
		}

		/*
		 * Prepare group prices
		 */
		$arr_groupPrices = null;
		foreach ($this->arr_originalData as $languageKey => $arrLanguageData) {
			/*
			 * Since group prices are not language specific and therefore
			 * are the same in every language data array, we only have to structure
			 * the prices once and then store the structured group prices in
			 * all language data arrays.
			 */
			if ($arr_groupPrices === null) {
				$arr_groupPrices = ls_shop_generalHelper::getStructuredGroupPrices($arrLanguageData, 'product');
			}

			$this->arr_originalData[$languageKey]['arr_groupPrices'] = $arr_groupPrices;

			/*
			 * If group price settings exist for the current member group, we
			 * override the product's basic price settings with the group settings
			 */
			$arr_groupSettingsForUser = ls_shop_generalHelper::getGroupSettings4User();
			if (isset($this->arr_originalData[$languageKey]['arr_groupPrices'][$arr_groupSettingsForUser['id']])) {
				foreach ($this->arr_originalData[$languageKey]['arr_groupPrices'][$arr_groupSettingsForUser['id']] as $str_groupPriceKey => $str_groupPriceValue) {
					$this->arr_originalData[$languageKey][$str_groupPriceKey] = $str_groupPriceValue;
				}
			}

		}

		if (isset($GLOBALS['MERCONIS_HOOKS']['manipulateProductOrVariantData']) && is_array($GLOBALS['MERCONIS_HOOKS']['manipulateProductOrVariantData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['manipulateProductOrVariantData'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$this->arr_originalData = $objMccb->{$mccb[1]}($this->arr_originalData, 'product');
			}
		}

		$this->arr_customizableData = $this->arr_originalData;

		$this->ls_data = &$this->arr_customizableData;

		$this->setDataReferences();
	}

	private function setDataReferences() {
        global $objPage;

        $this->mainData = &$this->ls_data[ls_shop_languageHelper::getFallbackLanguage()];

        if ($this->ls_mainLanguageMode || !isset($objPage) || !is_object($objPage) || !isset($this->ls_data[$objPage->language])) {
            $this->currentLanguageData = &$this->mainData;
        } else {
            $this->currentLanguageData = &$this->ls_data[$objPage->language];
        }
    }

	public function useOriginalData() {
        $this->ls_data = &$this->arr_originalData;
        $this->setDataReferences();
    }

	public function useCustomizableData() {
        $this->ls_data = &$this->arr_customizableData;
        $this->setDataReferences();
    }

	public function ls_getVariants() {
		$objVariants = Database::getInstance()->prepare("
			SELECT		`id`
			FROM		`tl_ls_shop_variant`
			WHERE		`pid` = ?
				".(System::getContainer()->get('merconis.routing.scope')->isBackend() && (strpos(Environment::get('request'), 'tl_ls_shop_variant') !== false || strpos(Environment::get('request'), 'ls_shop_stockManagement') !== false) ? "" : "AND		`published` = '1'")."
			ORDER BY	`sorting` ASC
		");

		$objVariants = $objVariants->execute($this->ls_ID);

		while ($objVariants->next()) {
			$this->ls_variants[$objVariants->id] = new ls_shop_variant($objVariants->id, $this->ls_ID, $this);
		}
	}

	/*
	 * Diese Funktion wird verwendet, um dem Produkt-Objekt Informationen über die aktuell benötigte Variante zu hinterlegen.
	 */
	public function ls_setVariantID($variantID) {
		$this->ls_currentVariantID = $variantID;
		$this->ls_productVariantID = $this->ls_ID.'-'.$this->ls_currentVariantID;
		$this->ls_currentProductVariantID = $this->ls_productVariantID;
	}

	protected function getAllVariantAttributes($arr_variants = null) {
		$arr_variants = is_array($arr_variants) ? $arr_variants : $this->_variants;

		$arrAllAttributesAndValues = array();
		foreach ($arr_variants as $objVariant) {
			foreach ($objVariant->_attributes as $arrAttributeValues) {
				foreach ($arrAttributeValues as $arrAttributeValue) {
					if (!isset($arrAllAttributesAndValues[$arrAttributeValue['attributeID']])) {
						$arrAllAttributesAndValues[$arrAttributeValue['attributeID']] = array(
							'attributeID' => $arrAttributeValue['attributeID'],
							'attributeTitle' => $arrAttributeValue['attributeTitle'],
							'attributeAlias' => $arrAttributeValue['attributeAlias'],
							'values' => array()
						);
					}
					if (!isset($arrAllAttributesAndValues[$arrAttributeValue['attributeID']]['values'][$arrAttributeValue['valueID']])) {
						$arrAllAttributesAndValues[$arrAttributeValue['attributeID']]['values'][$arrAttributeValue['valueID']] = array(
							'valueID' => $arrAttributeValue['valueID'],
							'valueTitle' => $arrAttributeValue['valueTitle'],
							'valueAlias' => $arrAttributeValue['valueAlias'],
							'valueSorting' => $arrAttributeValue['valueSorting']
						);
					}
				}
			}
		}

        foreach ($arrAllAttributesAndValues as $arrAttributeValue) {
            uasort($arrAllAttributesAndValues[$arrAttributeValue['attributeID']]['values'],
                function (array $a, array $b) {
                    return $a['valueSorting'] <=> $b['valueSorting'];
                }

            );
        }

		return $arrAllAttributesAndValues;
	}

	/*
	 * Diese Funktion erstellt die Mengenvergleichsangabe
	 */
	public function getMengenvergleichsangabe($productPrice = false, $vergleichUnit = false, $vergleichDivisor = false) {
		$vergleichsangabe = '';
		if (!$productPrice || !$vergleichUnit || !$vergleichDivisor || $vergleichDivisor == 0) {
			return $vergleichsangabe;
		}

		$mengenvergleichspreis = ls_shop_generalHelper::outputPrice($productPrice / $vergleichDivisor);
		/*-->
		 * Prüfen, ob als Unit ein sprintf-String angegeben wurde
		 <--*/
		if (preg_match('/%s/', $vergleichUnit)) {
			/*--> wenn ja, sprintf durchführen <--*/
			$vergleichsangabe = sprintf($vergleichUnit, $mengenvergleichspreis);
		} else {
			/*--> ansonsten Standardausgabe <--*/
			$vergleichsangabe = $vergleichUnit.' = '.$mengenvergleichspreis;
		}
		return $vergleichsangabe;
	}

	/**-->
	 * Diese Funktion gibt die Info zurück, ob das Produkt aufgrund verschiedener Varianten
	 * unterschiedliche Preise hat.
	 <--*/
	public function checkForDifferentPrices() {
		if (!$this->_hasVariants) {
			return false;
		}

		$price = false;
		foreach ($this->_variants as $variant) {
			if ($price === false) {
				$price = $variant->_priceBeforeTax;
				continue;
			}
			if ($price != $variant->_priceBeforeTax || $price != $this->_priceBeforeTax) {
				return true;
			}
		}
		return false;
	}

	/**-->
	 * Diese Funktion gibt die Info zurück, ob das Produkt aufgrund verschiedener Varianten
	 * unterschiedliche ungestaffelte Preise hat.
	 <--*/
	public function unscaledCheckForDifferentPrices() {
		if (!$this->_hasVariants) {
			return false;
		}

		$price = false;
		foreach ($this->_variants as $variant) {
			if ($price === false) {
				$price = $variant->_unscaledPriceBeforeTax;
				continue;
			}
			if ($price != $variant->_unscaledPriceBeforeTax || $price != $this->_unscaledPriceBeforeTax) {
				return true;
			}
		}
		return false;
	}

	/*
	 * Funktion zum Erstellen der Produkt-Galerie
	 */
	public function ls_createGallery($mainImage, $moreImages, $currentProductVariantID, $moreImagesSortBy, $sizeMainImage, $sizeMoreImages, $moreImagesMargin, $imagesFullsize, $strTemplate, $additionalClass, $arrOverlays, $ls_imageLimit) {
		if (!is_array($arrOverlays)) {
			$arrOverlays = array();
		}
		if ($this->_isNew) {
			if (!in_array('isNew', $arrOverlays)) {
				$arrOverlays[] = 'isNew';
			}
		}
		if ($this->_isOnSale) {
			if (!in_array('isOnSale', $arrOverlays)) {
				$arrOverlays[] = 'isOnSale';
			}
		}
		$objGallery = new ls_shop_moreImagesGallery($mainImage, $moreImages, $currentProductVariantID, $moreImagesSortBy, $sizeMainImage, $sizeMoreImages, $moreImagesMargin, $imagesFullsize, $strTemplate, $additionalClass, $arrOverlays, $ls_imageLimit);
		return $objGallery->parse();
	}

	/*
	 * Diese Funktion verändert den in der DB eingetragenen Warenbestand des Produktes bzw. der Variante
	 */
	public function changeStock($quantity, $blnDoNotCalculate = false, $blnWriteLog = false) {
		$quantity = number_format((float) $quantity, $this->_quantityDecimals, '.', '');

		if ($this->_variantIsSelected) {
			return $this->_selectedVariant->changeStock($quantity, $blnDoNotCalculate, $blnWriteLog);
		} else {
			if (!$this->_useStock) {
				return false;
			}

			if (!$blnDoNotCalculate) {
				$this->getFreshestStock();
				$newStock = ls_add($this->_stock, $quantity);
			} else {
				$newStock = $quantity;
			}

			if ($blnWriteLog) {
                System::getContainer()->get('monolog.logger.contao')->info(
                        'MERCONIS: Changed stock for product with Item no. '.$this->_code.' (ID '.$this->ls_ID.') from '.ls_shop_generalHelper::outputQuantity($this->_stock, $this->_quantityDecimals).' '.$this->_quantityUnit.' to '.ls_shop_generalHelper::outputQuantity($newStock, $this->_quantityDecimals).' '.$this->_quantityUnit,
                        ['contao' => new ContaoContext('MERCONIS STOCK MANAGEMENT', TL_MERCONIS_STOCK_MANAGEMENT)]
                );
			}

			Database::getInstance()->prepare("
				UPDATE		`tl_ls_shop_product`
				SET			`lsShopProductStock` = ?
				WHERE		`id` = ?
			")
			->limit(1)
			->execute($newStock, $this->ls_ID);

			ls_shop_generalHelper::sendStockNotification($newStock, $this);

			return true;
		}
	}

	protected function getFreshestStock() {
		$objFreshestStock = Database::getInstance()->prepare("
			SELECT		`lsShopProductStock`
			FROM		`tl_ls_shop_product`
			WHERE		`id` = ?
		")
		->execute($this->ls_ID);

		if (!$objFreshestStock->numRows) {
			return;
		}

		$objFreshestStock->first();

		if (is_array($this->ls_data)) {
			foreach ($this->ls_data as $languageKey => $arrLanguageData) {
				if (array_key_exists('lsShopProductStock', $arrLanguageData)) {
					$this->ls_data[$languageKey]['lsShopProductStock'] = $objFreshestStock->lsShopProductStock;
				}
			}
		}
	}

	/**-->
	 * Diese Funktion zählt den Bestellungs-Zähler des Produktes um eins hoch
	 <--*/
	public function countSale() {
		Database::getInstance()->prepare("
			UPDATE		`tl_ls_shop_product`
			SET			`lsShopProductNumSales` = `lsShopProductNumSales` + 1
			WHERE		`id` = ?
		")
		->limit(1)
		->execute($this->ls_ID);
	}

	/*
	 * Diese Funktion ermittelt den korrekten Link zu einem Produkt. Zu lösen ist hierbei die Problematik, dass
	 * ein Produkt nicht exakt einer Seite zugeordnet ist, wodurch der Link eindeutig wäre, sondern mehreren Seiten.
	 * Der korrekte Link ist deshalb situationsabhängig unterschiedlich:
	 *
	 * 1. Ist die aktuell aufgerufene Seite (bzw. die Hauptsprachseite der aktuell aufgerufenen Fremdsprachseite!) eine Seite,
	 * der das Produkt zugeordnet ist, so wird diese Seite im Link verwendet
	 *
	 * 2. Ist die aktuell aufgerufene Seite (bzw. die Hauptsprachseite der aktuell aufgerufenen Fremdsprachseite!) KEINE Seite,
	 * der das Produkt zugeordnet ist, so wird die Hauptseite, die dem Produkt zugeordnet ist, für den Link verwendet.
	 * Eine tatsächliche Hauptseite gibt es nur, wenn bei der Auswahl der Seiten, denen ein Produkt zugeordnet ist, eine
	 * benutzerdefinierte Sortierung bzw. Kennzeichnung der Hauptseite möglich ist. Solange
	 * das nicht der Fall ist, wird einfach die erstbeste hinterlegte Seite verwendet.
	 */
	public function getlinkToProduct($var_useVariantAliasOrID = '') {
        /** @var PageModel $objPage */
        global $objPage;
        $currentMainLanguagePageID = ls_shop_languageHelper::getMainlanguagePageIDForPageID($objPage->id);

        /*-->
         * Prüfen, ob die aktuelle Hauptsprachseite dem Produkt hinterlegt ist
         <--*/
        if (is_array($this->_pages) && in_array($currentMainLanguagePageID, $this->_pages)) {
            /*-->
             * Wenn ja, zielt der Link auf die aktuelle Seite (natürlich nicht zwingend auf die Hauptsprachseite sondern tatsächlich die aktuell aufgerufene, ggf. also Fremdsprachseite)
             <--*/
            $objProductPage = $objPage;
        } else {
            /*-->
             * Wenn nein, so zielt der Link auf die erstbeste, dem Produkt zugeordnete Seite die veröffentlicht ist. Da die auf diese Art ermittelte
             * Seiten-ID allerdings eine Hauptsprachseiten-ID ist, muss zu dieser Seite die Seite ermittelt werden, die der
             * Sprache der aktuell aufgerufenen Seite entspricht.
             <--*/
            $MainLanguagePageIDForLink = null;
            foreach ($this->_pages as $int_pageID) {
                $pageInfo = PageModel::findWithDetails($int_pageID);
                if ($pageInfo !== null && $pageInfo->published == "1") {
                    $MainLanguagePageIDForLink = $int_pageID;
                    break;
                }
            }

            $languagePages = ls_shop_languageHelper::getLanguagePages($MainLanguagePageIDForLink);
            $currentLanguagePageIDForLink = $languagePages[$objPage->language]['id'];

            $objProductPage = PageModel::findWithDetails($currentLanguagePageIDForLink);
        }

        //redirect to product default Page if no categories/sites are set for this product
        if(empty($MainLanguagePageIDForLink)){
            $idDefaultProductPage = ls_shop_languageHelper::getLanguagePage('ls_shop_defaultProductPage', false, 'id');
            $objProductPage = PageModel::findWithDetails($idDefaultProductPage);
        }

        if (!is_object($objProductPage)) {
            $objProductPage = $objPage;
        }

        $addReturnPageToUrl = '';
        if ($objPage->id == ls_shop_languageHelper::getLanguagePage('ls_shop_searchResultPages', false, 'id')) {
            $addReturnPageToUrl = '/calledBy/searchResult';
        }

        $pageModel = PageModel::findWithDetails($objProductPage->row()['id']);
        $objContentUrlGenerator = System::getContainer()->get('contao.routing.content_url_generator');
        $this->ls_linkToProduct = $objContentUrlGenerator->generate($pageModel, array('parameters' => '/product/'.$this->_alias.($var_useVariantAliasOrID ? '/selectVariant/'.$var_useVariantAliasOrID : '').$addReturnPageToUrl));

		return $this->ls_linkToProduct;
	}


	protected function getScalePricesOutput($mode = 'unconfigured') {
		if (!$this->blnAlreadyGeneratedScalePricesOutput[$mode]) {
			$arrScalePriceOutput = null;
			if (is_array($this->_scalePrice)) {
				foreach ($this->_scalePrice as $arrStep) {
					$GLOBALS['merconis_globals']['temporarilyFixedScalePriceQuantity'] = $arrStep['minQuantity'];
					$arrTmp = array(
						'minQuantity' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['scalePriceQuantityFrom'].' '.ls_shop_generalHelper::outputQuantity($arrStep['minQuantity'], $this->_quantityDecimals).' '.$this->_quantityUnit
					);

					switch ($mode) {
						case 'unconfigured':
							$arrTmp['priceUnconfigured'] = $this->_priceBeforeConfiguratorAfterTaxFormatted;
							$arrTmp['priceUnconfiguredUnformatted'] = $this->_priceBeforeConfiguratorAfterTax;
							break;

						case 'configured':
							$arrTmp['priceConfigured'] = $this->_priceAfterTaxFormatted;
							$arrTmp['priceConfiguredUnformatted'] = $this->_priceAfterTax;
							break;

						case 'configurator':
							$arrTmp['priceConfigurator'] = $this->_priceModificationByConfiguratorFormatted;
							$arrTmp['priceConfiguratorUnformatted'] = $this->_priceModificationByConfigurator;
							break;

						case 'all':
							$arrTmp['priceUnconfigured'] = $this->_priceBeforeConfiguratorAfterTaxFormatted;
							$arrTmp['priceUnconfiguredUnformatted'] = $this->_priceBeforeConfiguratorAfterTax;

							$arrTmp['priceConfigured'] = $this->_priceAfterTaxFormatted;
							$arrTmp['priceConfiguredUnformatted'] = $this->_priceAfterTax;

							$arrTmp['priceConfigurator'] = $this->_priceModificationByConfiguratorFormatted;
							$arrTmp['priceConfiguratorUnformatted'] = $this->_priceModificationByConfigurator;
							break;
					}

					$arrScalePriceOutput[] = $arrTmp;

					unset($GLOBALS['merconis_globals']['temporarilyFixedScalePriceQuantity']);
				}
			}
			$this->scalePricesOutput[$mode] = $arrScalePriceOutput;
			$this->blnAlreadyGeneratedScalePricesOutput[$mode] = true;
		}

		return $this->scalePricesOutput[$mode];
	}

	private function getDeliveryTimeMessage($float_requestedQuantity = 1) {
	    return ls_shop_generalHelper::getDeliveryTimeMessage($this, $float_requestedQuantity);
    }

	public function getDeliveryTimeDays($float_requestedQuantity = 1) {
        $int_deliveryTimeDays = $this->_stock >= $float_requestedQuantity || !$this->_useStock ? $this->_deliveryInfo['deliveryTimeDaysWithSufficientStock'] : $this->_deliveryInfo['deliveryTimeDaysWithInsufficientStock'];

        if (!$this->_isAvailableBasedOnDate && $this->_isPreorderable) {
            $int_deliveryTimeDays += ceil(($this->_availableFrom - strtotime("midnight", time())) / 86400);
        }

        return (int) $int_deliveryTimeDays;
    }

    public function getDeliveryInfoSetID() {
        $int_deliveryInfoSetID = 0;

        if ($this->_isPreorderable) {
            $int_deliveryInfoSetID = $this->mainData['deliveryInfoSetToUseInPreorderPhase'];
        }

        $int_deliveryInfoSetID = $int_deliveryInfoSetID ?: $this->mainData['lsShopProductDeliveryInfoSet'];
        $int_deliveryInfoSetID = $int_deliveryInfoSetID ?: $GLOBALS['TL_CONFIG']['ls_shop_delivery_infoSet'];

        return $int_deliveryInfoSetID;
    }
}
