<?php

namespace Merconis\Core;

	/* ##########################################################################
	 * # START HERE!                                                            #
	 * # -----------                                                            #
	 * #                                                                        #
	 * # This class provides custom functions to create a pdf attachment used   #
	 * # in MERCONIS messages. In this example a simple invoice using the order #
	 * # information is created and saved and then its file path is returned so #
	 * # that it can be attached to the email object.                           #
	 * #                                                                        #
	 * ##########################################################################
	 */

/*
 * The class name has to be exactly the same as the filename without it's suffix. You don't have
 * to worry about any special name restrictions though, everything that's allowed as a php class name
 * will be fine.
 */
class dynamicAttachment_invoice_01 extends \Controller {
	private $arrOrder = array();
	private $messageCounterNr = null;

	// The filename to be used is defined here. The placeholder will be replaced with the messageCounterNr later.
	private $filename = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/invoice_%s.pdf';

	private $pdf = null;
	private $numPages = 0;
	private $numCurrentPage = 0;

	/*
	 * Adjust these settings in order to modify pagebreaks. On the last page the total value, tax information etc.
	 * will be displayed so the number of items that can be displayed on the last page is smaller than on
	 * other pages
	 */
	private $numItemsPerPage = 22;
	private $numItemsOnLastPage = 11;
	private $itemListOffsetTop = 80;

	/*
	 * Adjust this setting to modify the indention
	 */
	private $leftIndention = 20;

	/*
	 * Adjust this setting to modify the postion of the info block
	 */
	private $infoBlockLeftPosition = 161;

	/*
	 * Define which tax information you'd like to display
	 */
	private	$blnShowTaxColumn = true;
	private $blnShowTaxedWithCompletely = false;

	/*
	 * The constructor function takes the order array and the messages counter nr as arguments
	 */
	public function __construct($arrOrder = array(), $messageCounterNr = null) {
		parent::__construct();

		$this->arrOrder = $arrOrder;
		$this->messageCounterNr = $messageCounterNr;

		$this->calculateNumberOfPages();

		$this->filename = sprintf($this->filename, $this->messageCounterNr);

		if (version_compare(VERSION, '3.0', '>=')) {
			$this->filename = preg_replace('/^tl_files/', 'files', $this->filename);
		}

		if (file_exists(TL_ROOT.'/'.$this->filename)) {
			// insert a unique string right before the last dot in the filename if the filename without it already exists
			$this->filename = preg_replace('/(\.[^.]*$)/', '_'.sha1(time().rand(0,99999)).'$1', $this->filename);
		}
	}

	public function calculateNumberOfPages() {
		$numPages = 0;
		$numItems = count($this->arrOrder['items']);
		$numPagesFilledCompletelyWithItems = floor($numItems / $this->numItemsPerPage);
		$numItemsLeft = $numItems - ($numPagesFilledCompletelyWithItems * $this->numItemsPerPage);

		if ($numItemsLeft < $this->numItemsOnLastPage) {
			$numPages = $numPagesFilledCompletelyWithItems + 1;
		} else {
			$numPages = $numPagesFilledCompletelyWithItems + 2;
		}

		$this->numPages = $numPages;
	}

	/*
	 * The parse function creates a pdf file, saves it and returns its file path
	 */
	public function parse() {
		$savedFilename = null;

		// create a new pdf instance
		$this->pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);

		/* #################################################
		 * Basic PDF settings
		 */
		$this->pdf->SetCreator(PDF_CREATOR);
		$this->pdf->SetAuthor(PDF_AUTHOR);
		$this->pdf->SetTitle('Invoice '.$this->messageCounterNr);
		$this->pdf->SetSubject('Invoice '.$this->messageCounterNr);

		$this->pdf->setFontSubsetting(false);

		// Remove default header/footer
		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);

		// Set margins
		$this->pdf->SetMargins(0, 0, 0);

		// Set auto page breaks
		$this->pdf->SetAutoPageBreak(false, 0);

		// Set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		/*
		 * #################################################
		 */


		/* #################################################
		 * Payload
		 */

		$this->addPage();
		$y = $this->itemListOffsetTop + 8;

		$countItems = 0;
		foreach ($this->arrOrder['items'] as $itemPosition => $cartItem) {
			if ($countItems >= $this->numItemsPerPage) {
				// add a new page
				$this->addPage();
				$y = $this->itemListOffsetTop + 8;

				$countItems = 0;
			}

			$countItems++;


			$y += 4;

			$strBorder = 'LR'.($countItems == 1 ? 'T' : '');

			$this->pdf->SetXY($this->leftIndention, $y, true);
			$this->pdf->Cell(201 - $this->leftIndention, 4, html_entity_decode($cartItem['extendedInfo']['_productTitle_customerLanguage'].($cartItem['extendedInfo']['_hasCode'] ?  ' ('.$cartItem['extendedInfo']['_code'].')' : ''), ENT_COMPAT, 'UTF-8'), $strBorder, 0, '', false, '', 0, false, 'T', 'B');


			$y += 4;

			$strBorder = 'B';

			$this->pdf->SetTextColor(150, 150, 150);
			$this->pdf->SetXY($this->leftIndention, $y, true);
			$this->pdf->Cell(113 - $this->leftIndention, 4, html_entity_decode(($cartItem['isVariant'] ? $cartItem['extendedInfo']['_title_customerLanguage'] : '').($cartItem['configurator_hasValue'] ? ' '.$GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText082'].' '.$cartItem['configurator_referenceNumber'] : ''), ENT_COMPAT, 'UTF-8'), $strBorder.'L', 0, '', false, '', 0, false, 'T', 'T');
			$this->pdf->SetTextColor(0, 0, 0);

			$this->pdf->SetXY(113, $y, true);
			$this->pdf->Cell(25, 4, html_entity_decode(ls_shop_generalHelper::outputPrice($cartItem['price']).($cartItem['extendedInfo']['_hasQuantityUnit'] ? '/'.$cartItem['extendedInfo']['_quantityUnit_customerLanguage'] : ''), ENT_COMPAT, 'UTF-8'), $strBorder, 0, 'R', false, '', 0, false, 'T', 'B');

			$this->pdf->SetXY(138, $y, true);
			$this->pdf->Cell(15, 4, ls_shop_generalHelper::outputQuantity($cartItem['quantity']), $strBorder, 0, 'R', false, '', 0, false, 'T', 'B');

			$this->pdf->SetXY(153, $y, true);
			$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($cartItem['priceCumulative']), $strBorder.($this->blnShowTaxColumn ? '' : 'R'), 0, 'R', false, '', 0, false, 'T', 'B');

			if ($this->blnShowTaxColumn) {
				$this->pdf->SetXY(181, $y, true);
				$this->pdf->Cell(20, 4, ls_shop_generalHelper::outputNumber($cartItem['taxPercentage'], 2, $this->arrOrder['decimalsSeparator'], $this->arrOrder['thousandsSeparator']).' %', $strBorder.'R', 0, 'R', false, '', 0, false, 'T', 'B');
			}
		}

		if ($countItems > $this->numItemsOnLastPage) {
			// add a new page
			$this->addPage(true);
			$y = $this->itemListOffsetTop;
		} else {
			$y += 8;
		}

		$strBorder = 'LTRB';

		$this->pdf->SetXY($this->leftIndention, $y, true);
		$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText020'], ENT_COMPAT, 'UTF-8'), $strBorder);

		$this->pdf->SetXY(153, $y, true);
		$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($this->arrOrder['totalValueOfGoods']), $strBorder, 0, 'R');

		if ($this->blnShowTaxColumn) {
			$this->pdf->SetXY(181, $y, true);
			$this->pdf->Cell(20, 4, '', $strBorder, 0, 'R');
		}

		if ($this->blnShowTaxedWithCompletely && !$this->arrOrder['noVATBecauseOfEnteredIDs']) {
			$this->pdf->SetTextColor(150, 150, 150);
			$this->pdf->SetFont('helvetica', 'I', 7);
			foreach ($this->arrOrder['totalValueOfGoodsTaxedWith'] as $taxClassID => $arrTaxInfo) {
				$y += 4;
				$this->pdf->SetXY($this->leftIndention, $y, true);
				$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode(sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText027'], ls_shop_generalHelper::outputNumber($arrTaxInfo['taxRate'], 2, $this->arrOrder['decimalsSeparator'], $this->arrOrder['thousandsSeparator']).' %'), ENT_COMPAT, 'UTF-8'), $strBorder);

				$this->pdf->SetXY(153, $y, true);
				$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($arrTaxInfo['amountTaxedHerewith']), $strBorder, 0, 'R');

				if ($this->blnShowTaxColumn) {
					$this->pdf->SetXY(181, $y, true);
					$this->pdf->Cell(20, 4, '', $strBorder, 0, 'R');
				}
			}
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->SetFont('helvetica', '', 9);
		}




		$y += 4;

		$strBorder = 'LRB';

		if (is_array($this->arrOrder['couponsUsed'])) {
			$countCoupons = 0;
			foreach ($this->arrOrder['couponsUsed'] as $couponID => $arrCouponInfo) {
				if ($arrCouponInfo['invalid']) {
					continue;
				}

				$this->pdf->SetXY($this->leftIndention, $y, true);
				$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode($arrCouponInfo['title_customerLanguage'].' ('.$arrCouponInfo['discountOutput'].')', ENT_COMPAT, 'UTF-8'), $strBorder);

				$this->pdf->SetXY(153, $y, true);
				$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($arrCouponInfo['amount']), $strBorder, 0, 'R');

				if ($this->blnShowTaxColumn) {
					$this->pdf->SetXY(181, $y, true);
					$this->pdf->Cell(20, 4, '', $strBorder, 0, 'R');
				}

				if (!$this->arrOrder['noVATBecauseOfEnteredIDs']) {
					$this->pdf->SetTextColor(150, 150, 150);
					$this->pdf->SetFont('helvetica', 'I', 7);
					foreach ($arrCouponInfo['amountTaxedWith'] as $taxClassID => $arrTaxInfo) {
						$y += 4;
						$this->pdf->SetXY($this->leftIndention, $y, true);
						$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode(sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText027'], ls_shop_generalHelper::outputNumber($arrTaxInfo['taxRate'], 2, $this->arrOrder['decimalsSeparator'], $this->arrOrder['thousandsSeparator']).' %'), ENT_COMPAT, 'UTF-8'), $strBorder);

						$this->pdf->SetXY(153, $y, true);
						$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($arrTaxInfo['amountTaxedHerewith']), $strBorder, 0, 'R');

						if ($this->blnShowTaxColumn) {
							$this->pdf->SetXY(181, $y, true);
							$this->pdf->Cell(20, 4, '', $strBorder, 0, 'R');
						}
					}
					$this->pdf->SetTextColor(0, 0, 0);
					$this->pdf->SetFont('helvetica', '', 9);
				}
				$y += 4;
			}
		}


		$this->pdf->SetXY($this->leftIndention, $y, true);
		$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText021'].' '.$this->arrOrder['paymentMethod_title_customerLanguage'].($this->arrOrder['paymentMethod_feeInfo_customerLanguage'] ? ' '.$this->arrOrder['paymentMethod_feeInfo_customerLanguage'] : ''), ENT_COMPAT, 'UTF-8'), $strBorder);

		$this->pdf->SetXY(153, $y, true);
		$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($this->arrOrder['paymentMethod_amount']), $strBorder, 0, 'R');

		if ($this->blnShowTaxColumn) {
			$this->pdf->SetXY(181, $y, true);
            $this->pdf->Cell(20, 4, ls_shop_generalHelper::outputNumber($this->arrOrder['paymentMethod_amountTaxedWith'][key($this->arrOrder['paymentMethod_amountTaxedWith'])]['taxRate'], 2, $this->arrOrder['decimalsSeparator'], $this->arrOrder['thousandsSeparator']).' %', $strBorder, 0, 'R');
		}


		$y += 4;

		$this->pdf->SetXY($this->leftIndention, $y, true);
		$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText022'].' '.$this->arrOrder['shippingMethod_title_customerLanguage'].($this->arrOrder['shippingMethod_feeInfo_customerLanguage'] ? ' '.$this->arrOrder['shippingMethod_feeInfo_customerLanguage'] : ''), ENT_COMPAT, 'UTF-8'), $strBorder);

		$this->pdf->SetXY(153, $y, true);
		$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($this->arrOrder['shippingMethod_amount']), $strBorder, 0, 'R');

		if ($this->blnShowTaxColumn) {
			$this->pdf->SetXY(181, $y, true);
            $this->pdf->Cell(20, 4, ls_shop_generalHelper::outputNumber($this->arrOrder['shippingMethod_amountTaxedWith'][key($this->arrOrder['shippingMethod_amountTaxedWith'])]['taxRate'], 2, $this->arrOrder['decimalsSeparator'], $this->arrOrder['thousandsSeparator']).' %', $strBorder, 0, 'R');
		}


		$y += 4;
		$additionalLineHeight = 0;
		if ($this->arrOrder['taxInclusive']) {
			$this->pdf->SetFillColor(220, 220, 220);
			$this->pdf->SetFont('helvetica', 'B', 12);
			$additionalLineHeight = 2;
			$strBorder = 'LTRB';
		}

		$this->pdf->SetXY($this->leftIndention, $y, true);
		$this->pdf->Cell(153 - $this->leftIndention, 4 + $additionalLineHeight, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop'][$this->arrOrder['taxInclusive'] ? 'miscText023' : 'miscText051'], ENT_COMPAT, 'UTF-8'), $strBorder, 0, '', $this->arrOrder['taxInclusive'] ? true : false);

		$this->pdf->SetXY(153, $y, true);
		$this->pdf->Cell(28, 4 + $additionalLineHeight, ls_shop_generalHelper::outputPrice($this->arrOrder['total']), $strBorder, 0, 'R', $this->arrOrder['taxInclusive'] ? true : false);

		if ($this->blnShowTaxColumn) {
			$this->pdf->SetXY(181, $y, true);
			$this->pdf->Cell(20, 4 + $additionalLineHeight, '', $strBorder, 0, 'R', $this->arrOrder['taxInclusive'] ? true : false);
		}

		if ($this->arrOrder['taxInclusive']) {
			$this->pdf->SetFillColor(255, 255, 255);
			$this->pdf->SetFont('helvetica', '', 9);
			$strBorder = 'LRB';

			$y += $additionalLineHeight;
		}

		if ($this->blnShowTaxedWithCompletely && !$this->arrOrder['noVATBecauseOfEnteredIDs']) {
			$this->pdf->SetTextColor(150, 150, 150);
			$this->pdf->SetFont('helvetica', 'I', 7);
			foreach ($this->arrOrder['totalTaxedWith'] as $taxClassID => $arrTaxInfo) {
				$y += 4;
				$this->pdf->SetXY($this->leftIndention, $y, true);
				$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode(sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText027'], ls_shop_generalHelper::outputNumber($arrTaxInfo['taxRate'], 2, $this->arrOrder['decimalsSeparator'], $this->arrOrder['thousandsSeparator']).' %'), ENT_COMPAT, 'UTF-8'), $strBorder);

				$this->pdf->SetXY(153, $y, true);
				$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($arrTaxInfo['amountTaxedHerewith']), $strBorder, 0, 'R');

				if ($this->blnShowTaxColumn) {
					$this->pdf->SetXY(181, $y, true);
					$this->pdf->Cell(20, 4, '', $strBorder, 0, 'R');
				}
			}
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->SetFont('helvetica', '', 9);
		}




		if (!$this->arrOrder['noVATBecauseOfEnteredIDs']) {
			foreach ($this->arrOrder['tax'] as $taxClassID => $arrTaxInfo) {
				$y += 4;

				$this->pdf->SetXY($this->leftIndention, $y, true);
				$this->pdf->Cell(153 - $this->leftIndention, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop'][$this->arrOrder['taxInclusive'] ? 'miscText052' : 'miscText053'].' ('.ls_shop_generalHelper::outputNumber($arrTaxInfo['taxRate'], 2, $this->arrOrder['decimalsSeparator'], $this->arrOrder['thousandsSeparator']).' %'.')', ENT_COMPAT, 'UTF-8'), $strBorder);

				$this->pdf->SetXY(153, $y, true);
				$this->pdf->Cell(28, 4, ls_shop_generalHelper::outputPrice($arrTaxInfo['taxAmount']), $strBorder, 0, 'R');

				if ($this->blnShowTaxColumn) {
					$this->pdf->SetXY(181, $y, true);
					$this->pdf->Cell(20, 4, '', $strBorder, 0, 'R');
				}
			}
		}

		if ($this->arrOrder['noVATBecauseOfEnteredIDs']) {
			$y += 4;

			$this->pdf->SetXY($this->leftIndention, $y, true);
			$this->pdf->Cell(201 - $this->leftIndention, 4, html_entity_decode(sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['misc']['noVAT2'], $GLOBALS['TL_CONFIG']['ls_shop_ownVATID'], $this->arrOrder['customerData']['personalData']['VATID']), ENT_COMPAT, 'UTF-8'), $strBorder);
		}



		if (!$this->arrOrder['taxInclusive']) {
			$y += 4;

			$this->pdf->SetFont('helvetica', 'B', 12);
			$this->pdf->SetFillColor(220, 220, 220);

			$this->pdf->SetXY($this->leftIndention, $y, true);
			$this->pdf->Cell(153 - $this->leftIndention, 6, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText023'], ENT_COMPAT, 'UTF-8'), $strBorder, 0, '', true);

			$this->pdf->SetXY(153, $y, true);
			$this->pdf->Cell(28, 6, ls_shop_generalHelper::outputPrice($this->arrOrder['invoicedAmount']), $strBorder, 0, 'R', true);

			if ($this->blnShowTaxColumn) {
				$this->pdf->SetXY(181, $y, true);
				$this->pdf->Cell(20, 6, '', $strBorder, 0, 'R', true);
			}

			$this->pdf->SetFont('helvetica', '', 9);
		}

		if ($this->arrOrder['paymentMethod_additionalInfo_customerLanguage']) {
			$y += 10;
			$this->pdf->writeHTMLCell(201 - $this->leftIndention, 50, $this->leftIndention, $y, html_entity_decode($this->arrOrder['paymentMethod_additionalInfo_customerLanguage'], ENT_COMPAT, 'UTF-8'));
		}


		/* #################################################
		 * Close and output the PDF document
		 */
		$this->pdf->lastPage();
		$this->pdf->Output(TL_ROOT.'/'.$this->filename, 'F');

		return $this->filename;
	}

	private function addPage($skipTableHeadline = false) {
		$this->numCurrentPage++;
		$this->pdf->AddPage();

		// Set font
		$this->pdf->SetFont('helvetica', '', 9);

		/* #################################################
		 * Background image
		 */
		$bgImage = TL_ROOT.'/'.(version_compare(VERSION, '3.0', '>=') ? 'files' : 'tl_files').'/merconisfiles/dynamicAttachmentFiles/images/invoice_background_01.gif';
		$this->pdf->Image($bgImage, -0.07, 0, 210.15, 297.01, '', '', '', false, 300, '', false, false, 0);
		/*
		 * #################################################
		 */

		/*
		 * Modify this code in order to display the customer information you need
		 */
		$addressOffsetY = 0;
		if (isset($this->arrOrder['customerData']['personalData']['company']) && $this->arrOrder['customerData']['personalData']['company']) {
			$addressOffsetY = 4;
			$this->pdf->SetXY($this->leftIndention, 40, true);
			$this->pdf->Cell(0, 0, html_entity_decode($this->arrOrder['customerData']['personalData']['company'], ENT_COMPAT, 'UTF-8'));
		}
		$this->pdf->SetXY($this->leftIndention, 40 + $addressOffsetY, true);
		$this->pdf->Cell(0, 0, html_entity_decode($this->arrOrder['customerData']['personalData']['firstname'].' '.$this->arrOrder['customerData']['personalData']['lastname'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetXY($this->leftIndention, 44 + $addressOffsetY, true);
		$this->pdf->Cell(0, 0, html_entity_decode($this->arrOrder['customerData']['personalData']['street'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetXY($this->leftIndention, 52 + $addressOffsetY, true);
		$this->pdf->Cell(0, 0, html_entity_decode($this->arrOrder['customerData']['personalData']['postal'].' '.$this->arrOrder['customerData']['personalData']['city'], ENT_COMPAT, 'UTF-8'));

		$this->pdf->SetFont('helvetica', 'B', 14);
		$this->pdf->SetXY($this->infoBlockLeftPosition, 38, true);
		$this->pdf->Cell(0, 0, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText076'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetFont('helvetica', '', 9);
		$this->pdf->SetXY($this->infoBlockLeftPosition, 44, true);
		$this->pdf->Cell(0, 0, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText077'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetXY($this->infoBlockLeftPosition + 20, 44, true);
		$this->pdf->Cell(0, 0, $this->messageCounterNr);
		$this->pdf->SetXY($this->infoBlockLeftPosition, 48, true);
		$this->pdf->Cell(0, 0, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText078'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetXY($this->infoBlockLeftPosition + 20, 48, true);
		$this->pdf->Cell(0, 0, $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], time()));

		$this->pdf->SetXY($this->infoBlockLeftPosition, 52, true);
		$this->pdf->Cell(0, 0, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText079'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetXY($this->infoBlockLeftPosition + 20, 52, true);
		$this->pdf->Cell(0, 0, $this->arrOrder['customerNr'] ? $this->arrOrder['customerNr'] : '-');

		$this->pdf->SetXY($this->infoBlockLeftPosition, 56, true);
		$this->pdf->Cell(0, 0, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText083'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetXY($this->infoBlockLeftPosition + 20, 56, true);
		$this->pdf->Cell(0, 0, $this->arrOrder['orderNr']);

		$this->pdf->SetXY($this->infoBlockLeftPosition, 64, true);
		$this->pdf->Cell(0, 0, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText080'], ENT_COMPAT, 'UTF-8'));
		$this->pdf->SetXY($this->infoBlockLeftPosition + 20, 64, true);
		$this->pdf->Cell(0, 0, html_entity_decode(sprintf($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText081'], $this->numCurrentPage, $this->numPages), ENT_COMPAT, 'UTF-8'));


		if (!$skipTableHeadline) {
			$y = $this->itemListOffsetTop;

			// write table headline
			$this->pdf->SetFont('helvetica', 'B', 8);
			$this->pdf->SetFillColor(40, 40, 40);
			$this->pdf->SetTextColor(255, 255, 255);

			$this->pdf->SetXY($this->leftIndention, $y, true);
			$this->pdf->Cell(113 - $this->leftIndention, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText013'], ENT_COMPAT, 'UTF-8'), 'LTRB', 0, '', true);

			$this->pdf->SetXY(113, $y, true);
			$this->pdf->Cell(25, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText014'], ENT_COMPAT, 'UTF-8'), 'LTRB', 0, '', true);

			$this->pdf->SetXY(138, $y, true);
			$this->pdf->Cell(15, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText016'], ENT_COMPAT, 'UTF-8'), 'LTRB', 0, '', true);

			$this->pdf->SetXY(153, $y, true);
			$this->pdf->Cell(28, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText018'], ENT_COMPAT, 'UTF-8'), 'LTRB', 0, '', true);

			if ($this->blnShowTaxColumn) {
				$this->pdf->SetXY(181, $y, true);
				$this->pdf->Cell(20, 4, html_entity_decode($GLOBALS['TL_LANG']['MSC']['ls_shop']['miscText026'], ENT_COMPAT, 'UTF-8'), 'LTRB', 0, '', true);
			}

			$this->pdf->SetFont('helvetica', '', 9);
			$this->pdf->SetFillColor(255, 255, 255);
			$this->pdf->SetTextColor(0, 0, 0);
		}
	}
}
