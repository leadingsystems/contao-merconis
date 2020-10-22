<?php

namespace Merconis\Core;
use function LeadingSystems\Helpers\ls_mul;
use function LeadingSystems\Helpers\ls_div;
use function LeadingSystems\Helpers\ls_add;
use function LeadingSystems\Helpers\ls_sub;
use function LeadingSystems\Helpers\createOneDimensionalArrayFromTwoDimensionalArray;
use function LeadingSystems\Helpers\createMultidimensionalArray;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_variant
{
	public $ls_ID = 0;
	public $ls_productID = 0;
	public $ls_productVariantID = 0;

	public $ls_data = null;

	public $ls_objParentProduct = null;

	public $ls_objConfigurator = null;

	protected $ls_mainLanguageMode = false;
	protected $ls_currentLanguage = null;

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

	public function __construct($intID = 0, $productID = 0, $arrProductData = array(), &$objParentProduct = null) {
		$this->ls_ID = $intID;
		$this->ls_productID = $productID;
		$this->ls_productVariantID = $this->ls_productID.'-'.$this->ls_ID;

		$this->ls_objParentProduct = &$objParentProduct;

		$this->ls_getData();

//			For performance reasons configurator objects are not automatically instantiated in the __construct function
//			$this->createObjConfigurator();
	}

	public function createObjConfigurator() {
		if ($this->ls_objConfigurator !== null) {
			return;
		}
		$this->ls_objConfigurator = ls_shop_generalHelper::getObjConfigurator($this->_configuratorID, $this->ls_productVariantID, array('weight' => $this->_weightBeforeConfigurator, 'price' => $this->_priceBeforeConfiguratorAfterTax, 'unscaledPrice' => $this->_unscaledPriceBeforeConfiguratorAfterTax, 'steuersatz' => $this->_steuersatz, 'anchor' => $this->_anchor), $this->ls_objParentProduct->ls_configuratorHash, $this);
	}

	public function ls_setMainLanguageMode($bln) {
		$this->ls_mainLanguageMode = $bln;
	}

	public function ls_setCurrentLanguage($language = null) {
		$this->ls_currentLanguage = $language && in_array($language, ls_shop_languageHelper::getAllLanguages()) ? $language : null;
	}

	/**-->
	 * Diese Funktion erstellt eine automatische Dokumentation über die in Templates zu verwendenden Eigenschaften und Methoden
	 * des Varianten-Objektes und analysiert dafür die Methoden "__get()" und "__call()"
	 <--*/
	protected function ls_outputOptions() {
		$fileContent = file_get_contents(TL_ROOT.'/vendor/leadingsystems/contao-merconis/src/Resources/contao/ls_shop_variant.php');

		/*-->
		 * Properties
		 <--*/
		preg_match('/\x23\x23 START AUTO DOCUMENTATION PROPERTIES VARIANT \x23\x23(.*)\x23\x23 STOP AUTO DOCUMENTATION PROPERTIES VARIANT \x23\x23/siU', $fileContent, $matches);
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
		preg_match('/\x23\x23 START AUTO DOCUMENTATION METHODS VARIANT \x23\x23(.*)\x23\x23 STOP AUTO DOCUMENTATION METHODS VARIANT \x23\x23/siU', $fileContent, $matches);
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
			<h1>OUTPUT OPTIONS FOR VARIANT OBJECT</h1>
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

	/*
	 * Getter-Funktion. Durch die Kommentare für AUTO DOCUMENTATION und DESCRIPTION können die
	 * hier verfügbaren Eigenschaften in der automatischen Dokumentation dargestellt werden
	 */
	public function __get($what = '') {
		/** @var \PageModel $objPage */
		global $objPage;
		switch ($what) {
			/* ## START AUTO DOCUMENTATION PROPERTIES VARIANT ## */
			case '_outputOptions':
				return $this->ls_outputOptions();
				break;

			case '_objectType':
				return 'variant';

			case '_objParentProduct':
				return $this->ls_objParentProduct;
				break;

			case '_isPublished':
				return $this->mainData['published'] ? true : false;
				break;

			case '_isSelected':
				return $this->_objParentProduct->_selectedVariantID === $this->ls_ID;
				break;

			case '_id':
				return $this->ls_ID;
				break;

			case '_anchor':
				return 'p_'.$this->_productVariantID;
				/*-->
				return $this->_objParentProduct->_anchor;
				<--*/
				break;

			case '_productVariantID':
				return $this->ls_productVariantID;
				break;

			case '_productTitle':
				return $this->_objParentProduct->_title;
				break;

			case '_configuratorID':
				return $this->mainData['configurator'] ? $this->mainData['configurator'] : $this->_objParentProduct->_configuratorID;
				break;

			case '_objConfigurator':
				$this->createObjConfigurator();
				return $this->ls_objConfigurator;
				break;

			case '_configurator':
				$this->createObjConfigurator();
				return $this->ls_objConfigurator->parse();
				break;

			case '_configuratorWithCartRepresentation':
				$this->createObjConfigurator();
				return $this->ls_objConfigurator->parse('cartRepresentation');
				break;

			case '_orderAllowed':
				$this->createObjConfigurator();
				return $this->ls_objConfigurator->blnIsValid;
				break;

			case '_configuratorInDataEntryMode':
				$this->createObjConfigurator();
				return $this->ls_objConfigurator->blnDataEntryMode;
				break;

			case '_mainImageUnprocessed'
				/* ## DESCRIPTION:
returns the main image that has been selected explicitly or null if none has been selected
				 */
				 :
				return isset($this->mainData['lsShopProductVariantMainImage']) && $this->mainData['lsShopProductVariantMainImage'] ? ls_getFilePathFromVariableSources($this->mainData['lsShopProductVariantMainImage']) : null;
				break;

			case '_mainImage'
				/* ## DESCRIPTION:
Returns the image that will be used as the main image if images are processed in an alphabetical ascending order.
If a main image has been selected explicitly, it will always be returned here. Otherwise the image sorted on top will be returned.
You can use the method "getImage" to get the image in the size you need: \Image::get($image, $width, $height, $croppingMode='');
				 */
				 :
                $objTmpGallery = new ls_shop_moreImagesGallery($this->_mainImageUnprocessed, $this->_moreImagesUnprocessed, false);
                $allImagesSortedAndWithVideoCovers = $objTmpGallery->imagesSortedAndWithVideoCovers();
                return isset($allImagesSortedAndWithVideoCovers[0]) && $allImagesSortedAndWithVideoCovers[0] && isset($allImagesSortedAndWithVideoCovers[0]['singleSRC']) && $allImagesSortedAndWithVideoCovers[0]['singleSRC'] ? $allImagesSortedAndWithVideoCovers[0]['singleSRC'] : null;
				break;

			case '_hasMainImage':
				return $this->_mainImage ? true : false;
				break;

			case '_moreImagesUnprocessed':
				/*-->
				 * Using null as the parameter for the main image results in getAllProductImages() returning
				 * all images except for the main image which is exactly what we want here.
				 <--*/
				return ls_shop_generalHelper::getAllProductImages($this, $this->_code, null, $this->mainData['lsShopProductVariantMoreImages']);
				break;

			case '_moreImages'
				/* ## DESCRIPTION:
you can use the method "\Image::get" to get the image in the size you need: \Image::get($image, $width, $height, $croppingMode='');
				 */
				 :
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
				return is_array($this->_moreImages) && count($this->_moreImages) ? true : false;
				break;

			case '_hasImages':
				return $this->_hasMainImage || $this->_hasMoreImages;
				break;

			case '_code':
				return $this->mainData['lsShopVariantCode'];
				break;

			case '_hasCode':
				return $this->_code ? true : false;
				break;

			case '_weightType':
				return $this->mainData['lsShopVariantWeightType'];
				break;

			case '_weightBeforeConfigurator':
				return $this->calculateWeightRegardingWeightType();
				break;

			case '_weight':
				$this->createObjConfigurator();
				return ls_add($this->_weightBeforeConfigurator, $this->ls_objConfigurator->getWeightModification($this->_weightBeforeConfigurator));
				break;

			case '_weightFormatted':
				return ls_shop_generalHelper::outputWeight($this->_weight);
				break;

			case '_hasWeight':
				return intval($this->_weight) ? true : false;
				break;

			case '_alias':
				return $this->currentLanguageData['alias'] ? $this->currentLanguageData['alias'] : $this->mainData['alias'];
				break;

			case '_linkToVariant':
				return $this->_objParentProduct->getlinkToProduct($this->_alias ? $this->_alias : $this->ls_ID);
				break;

			case '_link':
				return $this->_linkToVariant;
				break;

			case '_title':
				$title = $this->currentLanguageData['title'] ? $this->currentLanguageData['title'] : $this->mainData['title'];
				if (!$title) {
					/*-->
					 * Unklar, ob es Sinn macht, die Produktbezeichnung hier für die Erzeugung der Variantenbezeichnung ebenfalls zu verwenden.
					 * Besser erst mal nur die Ausprägung als Variantenbezeichnung verwenden.
					$title = $this->_productTitle.' ('.$this->_attributesAsString.')';
					 <--*/
					$title = $this->_attributesAsString;
				}
				return $title;
				break;

			case '_hasTitle':
				return $this->_title ? true : false;
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

			case '_quantityUnitSelf':
				return $this->currentLanguageData['lsShopVariantQuantityUnit'] ? $this->currentLanguageData['lsShopVariantQuantityUnit'] : $this->mainData['lsShopVariantQuantityUnit'];
				break;

			case '_hasQuantityUnitSelf':
				return $this->_quantityUnitSelf ? true : false;
				break;

			case '_quantityUnit':
				if ($this->_hasQuantityUnitSelf) {
					return $this->_quantityUnitSelf;
				} else {
					return $this->_objParentProduct->_quantityUnit;
				}
				break;

			case '_hasQuantityUnit':
				if ($this->_hasQuantityUnitSelf) {
					return $this->_hasQuantityUnitSelf;
				} else {
					return $this->_objParentProduct->_hasQuantityUnit;
				}
				break;

			case '_quantityComparisonUnitSelf':
				return $this->currentLanguageData['lsShopVariantMengenvergleichUnit'] ? $this->currentLanguageData['lsShopVariantMengenvergleichUnit'] : $this->mainData['lsShopVariantMengenvergleichUnit'];
				break;

			case '_hasQuantityComparisonUnitSelf':
				return $this->_quantityComparisonUnitSelf ? true : false;
				break;

			case '_quantityComparisonUnit':
				if ($this->_hasQuantityComparisonUnitSelf) {
					return $this->_quantityComparisonUnitSelf;
				} else {
					return $this->_objParentProduct->_quantityComparisonUnit;
				}
				break;

			case '_hasQuantityComparisonUnit':
				if ($this->_hasQuantityComparisonUnitSelf) {
					return $this->_hasQuantityComparisonUnitSelf;
				} else {
					return $this->_objParentProduct->_hasQuantityComparisonUnit;
				}
				break;

			case '_quantityComparisonDivisorSelf':
				return $this->mainData['lsShopVariantMengenvergleichDivisor'];
				break;

			case '_hasQuantityComparisonDivisorSelf':
				return $this->_quantityComparisonDivisorSelf > 0 ? true : false;
				break;

			case '_quantityComparisonDivisor':
				if ($this->_hasQuantityComparisonDivisorSelf) {
					return $this->_quantityComparisonDivisorSelf;
				} else {
					return $this->_objParentProduct->_quantityComparisonDivisor;
				}
				break;

			case '_hasQuantityComparisonDivisor':
				if ($this->_hasQuantityComparisonDivisorSelf) {
					return $this->_hasQuantityComparisonDivisorSelf;
				} else {
					return $this->_objParentProduct->_hasQuantityComparisonDivisor;
				}
				break;

			case '_quantityComparisonText':
				return $this->_objParentProduct->getMengenvergleichsangabe($this->_priceAfterTax, $this->_quantityComparisonUnit, $this->_quantityComparisonDivisor);
				break;

			case '_taxInfo':
				return ls_shop_generalHelper::getMwstInfo($this->_priceAfterTax, $this->_steuersatz);
				break;

			case '_unscaledTaxInfo':
				return ls_shop_generalHelper::getMwstInfo($this->_unscaledPriceAfterTax, $this->_steuersatz);
				break;

			case '_shippingInfo':
				return ls_shop_generalHelper::getVersandkostenInfo();
				break;

			case '_quantityInput':
				return ls_shop_generalHelper::getQuantityInput($this);
				break;

			case '_hasQuantityInput':
				/*
				 * Da es sich um einen variantenbezogenen Aufruf handelt und das Produkt dementsprechend über Varianten
				 * verfügen muss, wird hier immer true zurückgegeben.
				 */
				return true;
				break;

			case '_deliveryInfo':
				return ls_shop_generalHelper::getDeliveryInfo($this->ls_ID, 'variant', $this->ls_mainLanguageMode);
				break;

			case '_deliveryTimeMessage':
				return $this->_stock > 0 || !$this->_useStock ? preg_replace('/\{\{deliveryDate\}\}/siU', date($GLOBALS['TL_CONFIG']['dateFormat'], time() + 86400 * $this->_deliveryInfo['deliveryTimeDaysWithSufficientStock']), $this->_deliveryInfo['deliveryTimeMessageWithSufficientStock']) : preg_replace('/\{\{deliveryDate\}\}/siU', date($GLOBALS['TL_CONFIG']['dateFormat'], time() + 86400 * $this->_deliveryInfo['deliveryTimeDaysWithInsufficientStock']), $this->_deliveryInfo['deliveryTimeMessageWithInsufficientStock']);
				break;

			case '_deliveryTimeDays':
				return $this->_stock > 0 || !$this->_useStock ? $this->_deliveryInfo['deliveryTimeDaysWithSufficientStock'] : $this->_deliveryInfo['deliveryTimeDaysWithInsufficientStock'];
				break;

			case '_associatedProducts':
				return $this->mainData['associatedProducts'];
				break;

			case '_useStock':
				return $this->_deliveryInfo['useStock'];
				break;

			case '_stock':
				return number_format($this->mainData['lsShopVariantStock'], $this->_quantityDecimals, '.', '');
				break;

			case '_stockIsInsufficient'
				/* ## DESCRIPTION:
Indicates whether or not stock is insufficient. Returns true if stock should be used (_useStock == true), stock is equal or less than 0 (_stock <= 0) and orders with insufficient stock aren't allowed (_allowOrdersWithInsufficientStock == false), otherwise returns false.
				 */
				 :
				$blnStockIsSufficient = false;
				/*--> only if stock should be used, it can be insufficient <--*/
				if ($this->_useStock) {
					// if stock is equal or less than 0 and orders with insufficient stock are not allowed
					if ($this->_stock <= 0 && !$this->_allowOrdersWithInsufficientStock) {
						$blnStockIsSufficient = true;
					}
				}

				return $blnStockIsSufficient;
				break;

			case '_quantityDecimals':
				return $this->_objParentProduct->_quantityDecimals;
				break;

			case '_allowOrdersWithInsufficientStock':
				return $this->_deliveryInfo['allowOrdersWithInsufficientStock'];
				break;

			/*-->
			 * Preise
			 <--*/
			case '_useScalePrice':
				return (isset($this->mainData['useScalePrice']) && $this->mainData['useScalePrice']) || ($this->_objParentProduct->_useScalePrice && $this->_priceType != 'standalone');
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
				return isset($GLOBALS['merconis_globals']['temporarilyFixedScalePriceQuantity']) ? $GLOBALS['merconis_globals']['temporarilyFixedScalePriceQuantity'] : ls_shop_generalHelper::getScalePriceQuantityForProductOrVariant('variant', $this);
				break;

			case '_scalePriceKeyword':
				return $this->mainData['scalePriceKeyword'] ? $this->mainData['scalePriceKeyword'] : $this->_objParentProduct->_scalePriceKeyword;
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

			case '_useOldPrice':
				return $this->mainData['useOldPrice'];
				break;

			case '_priceBeforeTax':
				$priceBeforeTax = $this->mainData['lsShopVariantPrice'];
				$priceBeforeTax = ls_shop_generalHelper::calculateScaledPrice($priceBeforeTax, $this);

				/*
				 * Memorize the current variant id of the parent product object,
				 * then set its current variant to this variant and reset it after
				 * the price detection. This is necessary because the parent object
				 * product can only tell us the correct scale price if it knows which
				 * variant to look for in the cart.
				 */
				$tmpPreviousParentCurrentVariantID = $this->_objParentProduct->ls_currentVariantID;
				$this->_objParentProduct->ls_setVariantID($this->_id);

				$priceBeforeTax = ls_shop_generalHelper::ls_calculateVariantPriceRegardingPriceType($this->_priceType, $this->_objParentProduct->_priceBeforeTax, $priceBeforeTax);

				$this->_objParentProduct->ls_setVariantID($tmpPreviousParentCurrentVariantID);
				return $priceBeforeTax;
				break;

			case '_unscaledPriceBeforeTax':
				$priceBeforeTax = $this->mainData['lsShopVariantPrice'];
				$priceBeforeTax = ls_shop_generalHelper::ls_calculateVariantPriceRegardingPriceType($this->_priceType, $this->_objParentProduct->_unscaledPriceBeforeTax, $priceBeforeTax);
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

			case '_priceOldBeforeTax':
				/*-->
				 * Keine Berechnung, wenn sowohl der alte Produktpreis als auch der alte Variantenpreis nicht verwendet werden sollen
				 <--*/
				if (!$this->_useOldPrice && !$this->_objParentProduct->_useOldPrice) {
					return null;
				}

				if ($this->_useOldPrice) {
					/*--> Alter Variantenpreis soll verwendet werden, daher kann damit gerechnet werden <--*/
					$variantPrice = $this->mainData['lsShopVariantPriceOld'];
					$priceType = $this->_oldPriceType;
				} else {
					/*--> Alter Variantenpreis soll nicht verwendet werden, es muss daher mit dem aktuellen Variantenpreis gerechnet werden, wobei dann auch der aktuelle Preistyp gilt. <--*/
					$variantPrice = $this->mainData['lsShopVariantPrice'];
					$priceType = $this->_priceType;
					if ($priceType == 'standalone') {
						/*-->
						 * Soll mit dem aktuellen Variantenpreis gerechnet werden und ist dessen Preistyp "standalone", so ist de facto
						 * in der Preisermittlung keine "alte Preiskomponente" enthalten (es gibt ja dann nur einen alten Produktpreis,
						 * der aber gar nicht in der Preisermittlung berücksichtigt wird). In diesem Fall wird hier also für den alten
						 * Variantenpreis gleich NULL zurückgegeben, weil also gar kein wirklicher alter Variantenpreis existiert.
						 <--*/
						return null;
					}
				}

				if ($this->_objParentProduct->_useOldPrice) {
					/*--> Alter Produktpreis soll verwendet werden, daher kann damit gerechnet werden <--*/
					$productPrice = $this->_objParentProduct->_priceOldBeforeTax;
				} else {
					/*--> Alter Produktpreis soll nicht verwendet werden, es muss daher mit dem aktuellen Produktpreis gerechnet werden. <--*/
					$productPrice = $this->_objParentProduct->_priceBeforeTax;
				}

				return ls_shop_generalHelper::ls_calculateVariantPriceRegardingPriceType($priceType, $productPrice, $variantPrice);
				break;

			case '_priceOldAfterTax':
				return ls_shop_generalHelper::getDisplayPrice($this->_priceOldBeforeTax, $this->_steuersatz);
				break;

			case '_priceOldAfterTaxFormatted':
				return ls_shop_generalHelper::outputPrice($this->_priceOldAfterTax);
				break;

			case '_pricesAreDifferent':
				return $this->_objParentProduct->_pricesAreDifferent;
				break;

			case '_oldPricesAreDifferent':
				return $this->_objParentProduct->_oldPricesAreDifferent;
				break;

			case '_hasOldPrice':
				return $this->_useOldPrice && !is_null($this->_priceOldBeforeTax);
				break;

			case '_priceControl':
				ob_start();
				?>
				<div class="info">quantityComparisonUnit: <?php echo $this->_quantityComparisonUnit; ?></div>
				<div class="info">quantityComparisonDivisor: <?php echo $this->_quantityComparisonDivisor; ?></div>
				<div>&nbsp;</div>
				<table>
					<thead>
						<tr>
							<th></th>
							<th>variant price</th>
							<th></th>
							<th>old variant price</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="label">priceType</td>
							<td title="_priceType"><?php echo $this->_priceType; ?></td>
							<td></td>
							<td title="_oldPriceType"><?php echo $this->_oldPriceType; ?></td>
							<td></td>
						</tr>
						<tr>
							<td class="label">price in db</td>
							<td title="lsShopVariantPrice"><?php echo $this->mainData['lsShopVariantPrice']; ?></td>
							<td></td>
							<td title="lsShopVariantPriceOld"><?php echo $this->mainData['lsShopVariantPriceOld']; ?></td>
							<td></td>
						</tr>
						<tr>
							<td class="label">beforeTax</td>
							<td title="_priceBeforeTax"><?php echo $this->_priceBeforeTax; ?></td>
							<td title="_getQuantityComparisonText('_priceBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceBeforeTax'); ?></td>
							<td title="_priceOldBeforeTax"><?php echo $this->_priceOldBeforeTax; ?></td>
							<td title="_getQuantityComparisonText('_priceOldBeforeTax')"><?php echo $this->_getQuantityComparisonText('_priceOldBeforeTax'); ?></td>
						</tr>
						<tr>
							<td class="label">beforeTax (unscaled)</td>
							<td title="_unscaledPriceBeforeTax"><?php echo $this->_unscaledPriceBeforeTax; ?></td>
							<td title="_getQuantityComparisonText('_unscaledPriceBeforeTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceBeforeTax'); ?></td>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td class="label">beforeConfiguratorAfterTax</td>
							<td title="_priceBeforeConfiguratorAfterTax"><?php echo $this->_priceBeforeConfiguratorAfterTax; ?></td>
							<td title="_getQuantityComparisonText('_priceBeforeConfiguratorAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceBeforeConfiguratorAfterTax'); ?></td>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td class="label">beforeConfiguratorAfterTax (unscaled)</td>
							<td title="_unscaledPriceBeforeConfiguratorAfterTax"><?php echo $this->_unscaledPriceBeforeConfiguratorAfterTax; ?></td>
							<td title="_getQuantityComparisonText('_unscaledPriceBeforeConfiguratorAfterTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceBeforeConfiguratorAfterTax'); ?></td>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td class="label">beforeConfiguratorAfterTaxFormatted</td>
							<td title="_priceBeforeConfiguratorAfterTaxFormatted"><?php echo $this->_priceBeforeConfiguratorAfterTaxFormatted; ?></td>
							<td colspan="3"></td>
						</tr>
						<tr>
							<td class="label">beforeConfiguratorAfterTaxFormatted (unscaled)</td>
							<td title="_unscaledPriceBeforeConfiguratorAfterTaxFormatted"><?php echo $this->_unscaledPriceBeforeConfiguratorAfterTaxFormatted; ?></td>
							<td colspan="3"></td>
						</tr>
						<tr>
							<td class="label">modificationByConfigurator</td>
							<td title="_priceModificationByConfigurator"><?php echo $this->_priceModificationByConfigurator; ?></td>
							<td title="_getQuantityComparisonText('_priceModificationByConfigurator')"><?php echo $this->_getQuantityComparisonText('_priceModificationByConfigurator'); ?></td>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td class="label">modificationByConfigurator (unscaled)</td>
							<td title="_unscaledPriceModificationByConfigurator"><?php echo $this->_unscaledPriceModificationByConfigurator; ?></td>
							<td title="_getQuantityComparisonText('_unscaledPriceModificationByConfigurator')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceModificationByConfigurator'); ?></td>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td class="label">modificationByConfiguratorFormatted</td>
							<td title="_priceModificationByConfiguratorFormatted"><?php echo $this->_priceModificationByConfiguratorFormatted; ?></td>
							<td colspan="3"></td>
						</tr>
						<tr>
							<td class="label">modificationByConfiguratorFormatted (unscaled)</td>
							<td title="_unscaledPriceModificationByConfiguratorFormatted"><?php echo $this->_unscaledPriceModificationByConfiguratorFormatted; ?></td>
							<td colspan="3"></td>
						</tr>
						<tr>
							<td class="label">afterTax</td>
							<td title="_priceAfterTax"><?php echo $this->_priceAfterTax; ?></td>
							<td title="_getQuantityComparisonText('_priceAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceAfterTax'); ?></td>
							<td title="_priceOldAfterTax"><?php echo $this->_priceOldAfterTax; ?></td>
							<td title="_getQuantityComparisonText('_priceOldAfterTax')"><?php echo $this->_getQuantityComparisonText('_priceOldAfterTax'); ?></td>
						</tr>
						<tr>
							<td class="label">afterTax (unscaled)</td>
							<td title="_unscaledPriceAfterTax"><?php echo $this->_unscaledPriceAfterTax; ?></td>
							<td title="_getQuantityComparisonText('_unscaledPriceAfterTax')"><?php echo $this->_getQuantityComparisonText('_unscaledPriceAfterTax'); ?></td>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td class="label">afterTaxFormatted</td>
							<td title="_priceAfterTaxFormatted"><?php echo $this->_priceAfterTaxFormatted; ?></td>
							<td title="_priceOldAfterTaxFormatted"><?php echo $this->_priceOldAfterTaxFormatted; ?></td>
							<td colspan="2"></td>
						</tr>
						<tr>
							<td class="label">afterTaxFormatted (unscaled)</td>
							<td title="_unscaledPriceAfterTaxFormatted"><?php echo $this->_unscaledPriceAfterTaxFormatted; ?></td>
							<td colspan="3"></td>
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
											<td title="_getQuantityComparisonText($obj_variant->_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceUnconfiguredUnformatted'])"><?php echo $this->_getQuantityComparisonText($arr_scalePriceOutputStep['priceUnconfiguredUnformatted']); ?></td>

											<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfigured']"><?php echo $arr_scalePriceOutputStep['priceConfigured']; ?></td>
											<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguredUnformatted']"><?php echo $arr_scalePriceOutputStep['priceConfiguredUnformatted']; ?></td>
											<td title="_getQuantityComparisonText($obj_variant->_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguredUnformatted'])"><?php echo $this->_getQuantityComparisonText($arr_scalePriceOutputStep['priceConfiguredUnformatted']); ?></td>

											<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfigurator']"><?php echo $arr_scalePriceOutputStep['priceConfigurator']; ?></td>
											<td title="_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguratorUnformatted']"><?php echo $arr_scalePriceOutputStep['priceConfiguratorUnformatted']; ?></td>
											<td title="_getQuantityComparisonText($obj_variant->_scalePricesOutput[<?php echo $int_scalePriceKey; ?>]['priceConfiguratorUnformatted'])"><?php echo $this->_getQuantityComparisonText($arr_scalePriceOutputStep['priceConfiguratorUnformatted']); ?></td>
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
				<?php
				$outputBuffer = ob_get_contents();
				ob_end_clean();
				return $outputBuffer;
				break;

			/*-->
			 * Ende Preise
			 <--*/

			case '_attributes':
				return ls_shop_generalHelper::processProductAttributesValues(json_decode($this->mainData['lsShopProductVariantAttributesValues']));
				break;

			case '_attributeValueIds':
				return ls_shop_generalHelper::getProductAttributeValueIds(json_decode($this->mainData['lsShopProductVariantAttributesValues']));
				break;

			case '_attributesAsString':
				return ls_shop_generalHelper::createAttributesString($this->_attributes);
				break;

			case '_filterMatch'
				/* ## DESCRIPTION:
returns true if the variant matches, false if it doesn't and NULL if there's no filter context.
				 *
				 */
				:
				if (isset($_SESSION['lsShop']['filter']['matchedVariants'][$this->_id])) {
					return $_SESSION['lsShop']['filter']['matchedVariants'][$this->_id];
				} else {
					switch ($this->_objParentProduct->_filterMatch) {
						case 'complete':
							/*-->
							 * If the whole product matches, the variants most likely haven't been checked themselves
							 * but of course all variants would match
							 <--*/
							return true;
							break;

						case 'none':
							/*-->
							 * FIXME:
							 * If the product as a whole doesn't match, none of it's variants would.
							 *
							 * However, if we have a situation where a product that doesn't match the
							 * filter, is actually displayed, it might be counterproductive to return false
							 * here and indicate that the variant doesn't match the filter because if we
							 * show a product that doesn't match the filter we surely also want to display
							 * all it's variants even if, of course, they don't match either.
							 *
							 * Still, at first glance, returning false seems to be the obvious choice and we make it for now
							 * but if the results don't seem to be logical, we might have to change the return value here.
							 <--*/
							return false;
							break;

						case 'partial':
							/*-->
							 * If the product has a partial match, all of it's variants must have been checked
							 * themselves which means that this condition should never occur. If, for whatever reason,
							 * it does, we return false because somehow the variant must have slipped through and
							 * it definitely has not been matched by the filter.
							 <--*/
							return false;
							break;

						case null:
							/*-->
							 * If there's no filter context for the product, there isn't one for it's variants
							 <--*/
							return null;
							break;
					}
				}
				break;



			/* ## STOP AUTO DOCUMENTATION PROPERTIES VARIANT ## */

			case 'mainData':
				return $this->ls_data[ls_shop_languageHelper::getFallbackLanguage()];
				break;

			case 'currentLanguageData':
				if ($this->ls_mainLanguageMode || !isset($objPage) || !is_object($objPage)) {
					return $this->mainData;
				}

				$languageToUse = $this->ls_currentLanguage ? $this->ls_currentLanguage : $objPage->language;

				return isset($this->ls_data[$languageToUse]) ? $this->ls_data[$languageToUse] : $this->mainData;
				break;

			case '_priceType':
				return $this->mainData['lsShopVariantPriceType'];
				break;

			case '_oldPriceType':
				return $this->mainData['lsShopVariantPriceTypeOld'];
				break;

			case '_steuersatz':
				return $this->_objParentProduct->_steuersatz;
				break;

			case '_allImages':
				return ls_shop_generalHelper::getAllProductImages($this, $this->_code, $this->mainData['lsShopProductVariantMainImage'], $this->mainData['lsShopProductVariantMoreImages']);
				break;
		}

		return null;
	}

	/*
	 * Getter- (bzw. Caller-)Funktion. Durch die Kommentare für AUTO DOCUMENTATION und DESCRIPTION können die
	 * hier verfügbaren Methoden in der automatischen Dokumentation dargestellt werden
	 */
	public function __call($what, $args) {
		switch ($what) {
			/* ## START AUTO DOCUMENTATION METHODS VARIANT ## */
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
				$args = ls_shop_generalHelper::setArrayLength($args, 8);

				$args[3] = is_array($args[3]) ? serialize($args[3]): $args[3];
				$args[7] = is_array($args[7]) ? serialize($args[7]): $args[7];
				return $this->_objParentProduct->ls_createGallery($this->_mainImageUnprocessed, $this->_moreImagesUnprocessed, $this->ls_productVariantID, $args[0],$args[1],$args[2],$args[3],$args[4],$args[5],$args[6],$args[7],$args[8]);
				break;

			case '_deliveryTimeMessageInCart'
				/* ## DESCRIPTION:
This method can be used to get the delivery time message for this product regarding the quantity that is currently in the cart. It takes the required quantity as an argument and checks whether or not stock is sufficient.
				 */
				:
				$args = ls_shop_generalHelper::setArrayLength($args, 1);
				$requiredQuantity = $args[0];
				return ls_sub($this->_stock, $requiredQuantity) >= 0 || !$this->_useStock ? preg_replace('/\{\{deliveryDate\}\}/siU', date($GLOBALS['TL_CONFIG']['dateFormat'], time() + 86400 * $this->_deliveryInfo['deliveryTimeDaysWithSufficientStock']), $this->_deliveryInfo['deliveryTimeMessageWithSufficientStock']) : preg_replace('/\{\{deliveryDate\}\}/siU', date($GLOBALS['TL_CONFIG']['dateFormat'], time() + 86400 * $this->_deliveryInfo['deliveryTimeDaysWithInsufficientStock']), $this->_deliveryInfo['deliveryTimeMessageWithInsufficientStock']);
				break;

			case '_flexContentExists'
				/* ## DESCRIPTION:
This method checks whether or not a flexible variant information with the given keyword exists
				 */
				:
				return isset($this->_flexContents[$args[0]]) && !empty($this->_flexContents[$args[0]]);
				break;

			case '_flexContentExistsLanguageIndependent'
				/* ## DESCRIPTION:
This method checks whether or not a flexible variant information with the given keyword exists
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
				return $this->_objParentProduct->getMengenvergleichsangabe(is_string($args[0]) ? $this->{$args[0]} : $args[0], $this->_quantityComparisonUnit, $this->_quantityComparisonDivisor);
				break;

            case '_useCustomTemplate'
                /* ## DESCRIPTION:
This method takes the name of a template file as an argument and returns the rendered output as an html string. Use this functionality to outsource parts of your product output in separate templates to keep things clean, well structured and easily reusable.
                 */
            :
                $args = ls_shop_generalHelper::setArrayLength($args, 2);
                $str_template = $args[0];
                $obj_template = new \FrontendTemplate($str_template);
                $obj_template->objVariant = $this;
                $obj_template->arr_args = is_array($args[1]) ? $args[1] : [$args[1]];
                return $obj_template->parse();

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
						$objMccb = \System::importStatic($mccb[0]);
						$var_return = $objMccb->{$mccb[1]}($this, 'variant', $args[0]);
					}
				}

				return $var_return;
				break;

			default:
				return false;
				break;
			/* ## STOP AUTO DOCUMENTATION METHODS VARIANT ## */
		}
	}

	public function ls_getData() {
		$this->ls_data = ls_shop_languageHelper::getMultiLanguage($this->ls_ID, 'tl_ls_shop_variant', 'all', 'all', true, false, true);

		/*
		 * Prepare group prices
		 */
		$arr_groupPrices = null;
		foreach ($this->ls_data as $languageKey => $arrLanguageData) {
			/*
			 * Since group prices are not language specific and therefore
			 * are the same in every language data array, we only have to structure
			 * the prices once and then store the structured group prices in
			 * all language data arrays.
			 */
			if ($arr_groupPrices === null) {
				$arr_groupPrices = ls_shop_generalHelper::getStructuredGroupPrices($arrLanguageData, 'variant');
			}

			$this->ls_data[$languageKey]['arr_groupPrices'] = $arr_groupPrices;

			/*
			 * If group price settings exist for the current member group, we
			 * override the product's basic price settings with the group settings
			 */
			$arr_groupSettingsForUser = ls_shop_generalHelper::getGroupSettings4User();
			if (isset($this->mainData['arr_groupPrices'][$arr_groupSettingsForUser['id']])) {
				foreach ($this->mainData['arr_groupPrices'][$arr_groupSettingsForUser['id']] as $str_groupPriceKey => $str_groupPriceValue) {
					$this->ls_data[$languageKey][$str_groupPriceKey] = $str_groupPriceValue;
				}
			}

		}

		if (isset($GLOBALS['MERCONIS_HOOKS']['manipulateProductOrVariantData']) && is_array($GLOBALS['MERCONIS_HOOKS']['manipulateProductOrVariantData'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['manipulateProductOrVariantData'] as $mccb) {
				$objMccb = \System::importStatic($mccb[0]);
				$this->ls_data = $objMccb->{$mccb[1]}($this->ls_data, 'variant');
			}
		}
	}

	public function calculateWeightRegardingWeightType() {
		$weight = $this->mainData['lsShopVariantWeight'];
		switch($this->_weightType) {
			case 'standalone':
				/*--> Variantengewicht bleibt wie es ist <--*/
				break;

			case 'adjustmentPercentaged':
				$weight = ls_add($this->_objParentProduct->_weight, ls_div(ls_mul($this->_objParentProduct->_weight, $weight), 100));
				break;

			case 'adjustmentFix':
				$weight = ls_add($this->_objParentProduct->_weight, $weight);
				break;

		}
		return $weight;
	}

	/*
	 * Diese Funktion verändert den in der DB eingetragenen Warenbestand der Variante
	 */
	public function changeStock($quantity, $blnDoNotCalculate = false, $blnWriteLog = false) {
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
			\System::log('MERCONIS: Changed stock for product variant with Item no. '.$this->_code.' (ID '.$this->ls_ID.') from '.ls_shop_generalHelper::outputQuantity($this->_stock, $this->_quantityDecimals).' '.$this->_quantityUnit.' to '.ls_shop_generalHelper::outputQuantity($newStock, $this->_quantityDecimals).' '.$this->_quantityUnit, 'MERCONIS STOCK MANAGEMENT', TL_MERCONIS_STOCK_MANAGEMENT);
		}

		$objQuery = \Database::getInstance()->prepare("
			UPDATE		`tl_ls_shop_variant`
			SET			`lsShopVariantStock` = ?
			WHERE		`id` = ?
		")
		->limit(1)
		->execute($newStock, $this->ls_ID);

		ls_shop_generalHelper::sendStockNotification($newStock, $this);

		return true;
	}

	protected function getFreshestStock() {
		$objFreshestStock = \Database::getInstance()->prepare("
			SELECT		`lsShopVariantStock`
			FROM		`tl_ls_shop_variant`
			WHERE		`id` = ?
		")
		->execute($this->ls_ID);

		if (!$objFreshestStock->numRows) {
			return;
		}

		$objFreshestStock->first();

		if (is_array($this->ls_data)) {
			foreach ($this->ls_data as $languageKey => $arrLanguageData) {
				if (array_key_exists('lsShopVariantStock', $arrLanguageData)) {
					$this->ls_data[$languageKey]['lsShopVariantStock'] = $objFreshestStock->lsShopVariantStock;
				}
			}
		}
	}

	protected function getScalePricesOutput($mode = 'unconfigured') {
		if (!$this->blnAlreadyGeneratedScalePricesOutput[$mode]) {
			$arrScalePriceOutput = null;

			$arrStepsCombined = null;

			if (is_array($this->_scalePrice) || (is_array($this->_objParentProduct->_scalePrice) && $this->_priceType != 'standalone')) {
				$arrStepsCombined = array();
				if (is_array($this->_scalePrice)) {
					foreach($this->_scalePrice as $arrStep) {
						$arrStepsCombined[] = $arrStep['minQuantity'];
					}
				}

				if (is_array($this->_objParentProduct->_scalePrice) && $this->_priceType != 'standalone') {
					foreach($this->_objParentProduct->_scalePrice as $arrStep) {
						$arrStepsCombined[] = $arrStep['minQuantity'];
					}
				}
				$arrStepsCombined = array_unique($arrStepsCombined, SORT_NUMERIC);
				sort($arrStepsCombined, SORT_NUMERIC);
			}

			if (is_array($arrStepsCombined)) {
				foreach ($arrStepsCombined as $minQuantity) {
					$GLOBALS['merconis_globals']['temporarilyFixedScalePriceQuantity'] = $minQuantity;

					$arrTmp = array(
						'minQuantity' => $GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['scalePriceQuantityFrom'].' '.ls_shop_generalHelper::outputQuantity($minQuantity, $this->_quantityDecimals).' '.$this->_quantityUnit
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
}