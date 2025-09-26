var class_ls_shop_BE_import = new Class({
	objConfiguration: null,
	
	elLockOverlay: null,
	
	elActionButtonsContainer: null,
	elDeleteButton: null,
	elValidateButton: null,
	elImportButton: null,
	elContinueButton: null,
	
	elImportProcessMessage: null,
	
	blnHideLockOverlayAfterInit: true,
	
	initialize: function() {
		/*
		 * If the importHandler area doesn't exist, we hide the lockOverlay to allow access to the upload form
		 * and do nothing more.
		 */
		if ($('importHandler') == undefined || $('importHandler') == null) {
			window.setTimeout(this.hideLockOverlay, 1000);
			return;
		}
		
		this.getConfiguration();
	},
	
	getConfiguration: function() {
		new Request.JSON({
			url: window.location.href,
			noCache: true,
			data: 'REQUEST_TOKEN=' + Contao.request_token + '&action=callImporterFunction&what=getConfiguration',
			onComplete: function(objResponse) {
				if (objResponse == undefined || !objResponse.success) {
					return;
				}
				this.objConfiguration = objResponse.value;
				this.initializeSecondPart();
   			}.bind(this)
		}).send();
	},
	
	initializeSecondPart: function() {
		this.elActionButtonsContainer = $('actionButtonsContainer');
		
		this.displayCurrentFileInfos();
		
		this.handleStatusChange(true);
		
		if (this.blnHideLockOverlayAfterInit) {
			this.hideLockOverlay();
		}
	},
	
	displayCurrentFileInfos: function() {
		Object.each(this.objConfiguration.fileInfo, function(value, key) {
			if (key != 'numRecords' && key != 'changes') {
				var elTarget = $$('#importHandler .' + key + ' .value');
				if (elTarget == undefined || elTarget == null) {
					return;
				}
			}
			
			switch (key) {
				case 'numRecords':
					if (value != null) {
						$$('#importHandler .numRecords').setStyle('display', 'block');
						$$('#importHandler .numRecords .products .value')[0].setProperty('html', value.products);
						$$('#importHandler .numRecords .productLanguages .value')[0].setProperty('html', value.productLanguages);
						$$('#importHandler .numRecords .variants .value')[0].setProperty('html', value.variants);
						$$('#importHandler .numRecords .variantLanguages .value')[0].setProperty('html', value.variantLanguages);
					}
					break;
				
				case 'status':
					elTarget.getParent('.status').setProperty('class', 'status ' + value);
					elTarget.setProperty('html', this.objConfiguration.lang.importFileStatus[value]);
					break;
				
				case 'changesStock':
					if (value) {
						elTarget.getParent('.changesStock').setStyle('display', 'block');
						elTarget.setProperty('html', this.objConfiguration.lang.misc.changesStock);
					}
					break;
				
				case 'deletesRecords':
					if (value) {
						elTarget.getParent('.deletesRecords').setStyle('display', 'block');
						elTarget.setProperty('html', this.objConfiguration.lang.misc.deletesRecords);
					}
					break;
					
				default:
					elTarget.setProperty('html', value);
					break;
			}
		}.bind(this));		
	},
	
	handleStatusChange: function(blnInitCall) {
		blnInitCall = blnInitCall != undefined ? blnInitCall : false;
		switch(this.objConfiguration.fileInfo.status) {
			case 'notValidatedYet':
				this.createDeleteButton();
				this.createValidateButton();
				break;
				
			case 'ok':
				if(
						blnInitCall
					&&
						(
								(	
										this.objConfiguration.fileInfo.numProcessedRecords.products != undefined
									&&	this.objConfiguration.fileInfo.numProcessedRecords.products > 0
								)
							||
								(	
										this.objConfiguration.fileInfo.numProcessedRecords.variants != undefined
									&&	this.objConfiguration.fileInfo.numProcessedRecords.variants > 0
								)
							||
								(	
										this.objConfiguration.fileInfo.numProcessedRecords.productLanguages != undefined
									&&	this.objConfiguration.fileInfo.numProcessedRecords.productLanguages > 0
								)
							||
								(	
										this.objConfiguration.fileInfo.numProcessedRecords.variantLanguages != undefined
									&&	this.objConfiguration.fileInfo.numProcessedRecords.variantLanguages > 0
								)
						)
				) {
					/*
					 * If this is the initial call and there are signs of an unfinished import process,
					 * we have to make sure that the import can be continued
					 */
					this.blnHideLockOverlayAfterInit = false;
					this.showImportLockOverlay(true);
					this.updateImportProcessMessage();
					new Element('div.importInterruptedMessage').setProperty('html', this.objConfiguration.lang.misc.importInterrupted).inject(this.elLockOverlay.getElement('.message'), 'top');
					this.elLockOverlay.getElement('.importProcessMessage').addClass('interrupted');
					this.createContinueButton();
				} else {
					this.createDeleteButton();
					this.removeValidateButton();
					this.createImportButton();
				}
				break;
				
			case 'notOk':
				this.createDeleteButton();
				this.removeValidateButton();
				break;
				
			case 'fileChanged':
				this.createDeleteButton();
				this.removeValidateButton();
				break;
				
			case 'importFailed':
				this.createDeleteButton();
				this.removeValidateButton();
				this.removeImportButton();
				$$('#importHandler .numRecords').setStyle('display', 'none');
				$$('#importHandler .changesStock').setStyle('display', 'none');
				$$('#importHandler .deletesRecords').setStyle('display', 'none');
				break;
				
			case 'importFinished':
				this.createDeleteButton();
				this.removeValidateButton();
				this.removeImportButton();
				$$('#importHandler .numRecords').setStyle('display', 'none');
				$$('#importHandler .changesStock').setStyle('display', 'none');
				$$('#importHandler .deletesRecords').setStyle('display', 'none');
				break;
		}		
	},
	
	createDeleteButton: function () {
		if (this.elDeleteButton != null) {
			return;
		}
		this.elDeleteButton = new Element('div.actionButton.deleteFile').setProperty('html', this.objConfiguration.lang.buttons.deleteFile).addEvent('click', this.deleteFile.bind(this));
		this.elActionButtonsContainer.adopt(this.elDeleteButton);		
	},
	
	removeDeleteButton: function() {
		if (this.elDeleteButton == null) {
			return;
		}
		this.elDeleteButton.destroy();
		this.elDeleteButton = null;
	},
	
	createValidateButton: function () {
		if (this.elValidateButton != null) {
			return;
		}
		this.elValidateButton = new Element('div.actionButton.validateFile').setProperty('html', this.objConfiguration.lang.buttons.validateFile).addEvent('click', this.validateFile.bind(this));
		this.elActionButtonsContainer.adopt(this.elValidateButton);		
	},
	
	removeValidateButton: function() {
		if (this.elValidateButton == null) {
			return;
		}
		this.elValidateButton.destroy();
		this.elValidateButton = null;
	},
	
	createImportButton: function () {
		if (this.elImportButton != null) {
			return;
		}
		this.elImportButton = new Element('div.actionButton.importFile').setProperty('html', this.objConfiguration.lang.buttons.importFile).addEvent('click', this.importFile.bind(this));
		this.elActionButtonsContainer.adopt(this.elImportButton);		
	},
	
	removeImportButton: function() {
		if (this.elImportButton == null) {
			return;
		}
		this.elImportButton.destroy();
		this.elImportButton = null;
	},
	
	createContinueButton: function () {
		if (this.elContinueButton != null) {
			return;
		}
		this.elContinueButton = new Element('div.actionButton.continueImport')
									.setProperty('html', this.objConfiguration.lang.buttons.continueImport)
									.addEvent('click', function() {
										this.hideLockOverlay(true);
										this.removeContinueButton();
										this.importFile();
									}.bind(this));
		this.elLockOverlay.adopt(this.elContinueButton);
	},
	
	removeContinueButton: function() {
		if (this.elContinueButton == null) {
			return;
		}
		this.elContinueButton.destroy();
		this.elContinueButton = null;
	},
	
	deleteFile: function() {
		this.hideTlConfirmMessage();
		this.showLockOverlay(this.objConfiguration.lang.actionsInProgress.deleting, true);
		new Request.JSON({
			url: window.location.href,
			noCache: true,
			data: 'REQUEST_TOKEN=' + Contao.request_token + '&action=callImporterFunction&what=deleteFile',
			onComplete: function(objResponse) {
				if (objResponse == undefined || !objResponse.success) {
					return;
				}
				this.reloadPage();
   			}.bind(this)
		}).send();
	},
	
	validateFile: function() {
		this.hideTlConfirmMessage();
		this.showLockOverlay(this.objConfiguration.lang.actionsInProgress.validating, true);
		new Request.JSON({
			url: window.location.href,
			noCache: true,
			data: 'REQUEST_TOKEN=' + Contao.request_token + '&action=callImporterFunction&what=validateFile',
			onComplete: function(objResponse) {
				if (objResponse == undefined || !objResponse.success) {
					return;
				}
				this.objConfiguration.fileInfo = objResponse.value;
				this.displayCurrentFileInfos();
				this.handleStatusChange();
				this.hideLockOverlay();
   			}.bind(this)
		}).send();
	},
	
	showImportLockOverlay: function(blnForce) {
		blnForce = blnForce != undefined ? blnForce : false;
		if (blnForce || !this.lockOverlayIsVisible()) {
			this.elImportProcessMessage = new Element('div.importProcessMessage').adopt(
				new Element('div.numRecordsProcessed').adopt(
					new Element('div.products').adopt(
						new Element('span.label').setProperty('html', this.objConfiguration.lang.misc.processedProducts + ': '),
						new Element('span.value')
					),
					new Element('div.variants').adopt(
						new Element('span.label').setProperty('html', this.objConfiguration.lang.misc.processedVariants + ': '),
						new Element('span.value')
					),
					new Element('div.productLanguages').adopt(
						new Element('span.label').setProperty('html', this.objConfiguration.lang.misc.processedProductLanguages + ': '),
						new Element('span.value')
					),
					new Element('div.variantLanguages').adopt(
						new Element('span.label').setProperty('html', this.objConfiguration.lang.misc.processedVariantLanguages + ': '),
						new Element('span.value')
					),
					new Element('div.recommendedProductTranslation').adopt(
						new Element('span.label').setProperty('html', this.objConfiguration.lang.misc.recommendedProductTranslation),
						new Element('span.value')
					)
				)
			);
			this.showLockOverlay(this.elImportProcessMessage, true);
		}
	},
	
	updateImportProcessMessage: function() {
		var elNumRecordsProcessedProducts = this.elImportProcessMessage.getElement('.numRecordsProcessed .products');
		elNumRecordsProcessedProducts.getElement('.value').setProperty('html', (this.objConfiguration.fileInfo.numProcessedRecords.products != undefined ? this.objConfiguration.fileInfo.numProcessedRecords.products : 0) + '/' + this.objConfiguration.fileInfo.numRecords.products);
		if (
				(
						this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'product'
					||	this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variant'
					||	this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'productLanguage'
					||	this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variantLanguage'
				)
			&&	this.objConfiguration.fileInfo.numProcessedRecords.products == this.objConfiguration.fileInfo.numRecords.products) {
			elNumRecordsProcessedProducts.addClass('finished');
		}
		
		if (
				(
						this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'product'
					||	!this.objConfiguration.fileInfo.currentlyProcessingDataRowType
				)
				&& this.objConfiguration.fileInfo.numProcessedRecords.products != this.objConfiguration.fileInfo.numRecords.products
			) {
			elNumRecordsProcessedProducts.addClass('processing');
		} else {
			elNumRecordsProcessedProducts.removeClass('processing');
		}
		
		
		
		var elNumRecordsProcessedVariants = this.elImportProcessMessage.getElement('.numRecordsProcessed .variants');
		elNumRecordsProcessedVariants.getElement('.value').setProperty('html', (this.objConfiguration.fileInfo.numProcessedRecords.variants != undefined ? this.objConfiguration.fileInfo.numProcessedRecords.variants : 0) + '/' + this.objConfiguration.fileInfo.numRecords.variants);
		if (
				(
						this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variant'
					||	this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'productLanguage'
					||	this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variantLanguage'
				)
			&& this.objConfiguration.fileInfo.numProcessedRecords.variants == this.objConfiguration.fileInfo.numRecords.variants) {
			elNumRecordsProcessedVariants.addClass('finished');
		}
		
		if (
					(
							this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variant'
						&&	this.objConfiguration.fileInfo.numProcessedRecords.variants != this.objConfiguration.fileInfo.numRecords.variants
					)
				||
					(
							this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'product'
						&&	this.objConfiguration.fileInfo.numProcessedRecords.products == this.objConfiguration.fileInfo.numRecords.products
					)				
			) {
			elNumRecordsProcessedVariants.addClass('processing');
		} else {
			elNumRecordsProcessedVariants.removeClass('processing');
		}
		
		
		
		var elNumRecordsProcessedProductLanguages = this.elImportProcessMessage.getElement('.numRecordsProcessed .productLanguages');
		elNumRecordsProcessedProductLanguages.getElement('.value').setProperty('html', (this.objConfiguration.fileInfo.numProcessedRecords.productLanguages != undefined ? this.objConfiguration.fileInfo.numProcessedRecords.productLanguages : 0) + '/' + this.objConfiguration.fileInfo.numRecords.productLanguages);
		if (
				(
						this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'productLanguage'
					||	this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variantLanguage'
				)
			&& this.objConfiguration.fileInfo.numProcessedRecords.productLanguages == this.objConfiguration.fileInfo.numRecords.productLanguages) {
			elNumRecordsProcessedProductLanguages.addClass('finished');
		}
		
		if (
					(
							this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'productLanguage'
						&&	this.objConfiguration.fileInfo.numProcessedRecords.productLanguages != this.objConfiguration.fileInfo.numRecords.productLanguages
					)
				||
					(
							this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variant'
						&&	this.objConfiguration.fileInfo.numProcessedRecords.variants == this.objConfiguration.fileInfo.numRecords.variants
					)
			) {
			elNumRecordsProcessedProductLanguages.addClass('processing');
		} else {
			elNumRecordsProcessedProductLanguages.removeClass('processing');
		}
		
		
		
		var elNumRecordsProcessedVariantLanguages = this.elImportProcessMessage.getElement('.numRecordsProcessed .variantLanguages');
		elNumRecordsProcessedVariantLanguages.getElement('.value').setProperty('html', (this.objConfiguration.fileInfo.numProcessedRecords.variantLanguages != undefined ? this.objConfiguration.fileInfo.numProcessedRecords.variantLanguages : 0) + '/' + this.objConfiguration.fileInfo.numRecords.variantLanguages);
		if (
				(
					this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variantLanguage'
				)
			&& this.objConfiguration.fileInfo.numProcessedRecords.variantLanguages == this.objConfiguration.fileInfo.numRecords.variantLanguages) {
			elNumRecordsProcessedVariantLanguages.addClass('finished');
		}
		
		if (
					(
							this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variantLanguage'
						&&	this.objConfiguration.fileInfo.numProcessedRecords.variantLanguages != this.objConfiguration.fileInfo.numRecords.variantLanguages
					)
				||
					(
							this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'productLanguage'
						&&	this.objConfiguration.fileInfo.numProcessedRecords.productLanguages == this.objConfiguration.fileInfo.numRecords.productLanguages
					)
			) {
			elNumRecordsProcessedVariantLanguages.addClass('processing');
		} else {
			elNumRecordsProcessedVariantLanguages.removeClass('processing');
		}
		
		
		
		var elRecommendedProductTranslation = this.elImportProcessMessage.getElement('.numRecordsProcessed .recommendedProductTranslation');
		elRecommendedProductTranslation.getElement('.value').setProperty('html', this.objConfiguration.fileInfo.recommendedProductsTranslated != undefined && this.objConfiguration.fileInfo.recommendedProductsTranslated ? ': ' + this.objConfiguration.lang.misc.finished : '');
		
		if (
					this.objConfiguration.fileInfo.currentlyProcessingDataRowType == 'variantLanguage'
				&&	this.objConfiguration.fileInfo.numProcessedRecords.variantLanguages == this.objConfiguration.fileInfo.numRecords.variantLanguages
				&&	!this.objConfiguration.fileInfo.recommendedProductsTranslated
			) {
			elRecommendedProductTranslation.addClass('processing');
		} else {
			elRecommendedProductTranslation.removeClass('processing');
		}
		
		if (this.objConfiguration.fileInfo.recommendedProductsTranslated) {
			elRecommendedProductTranslation.addClass('finished');
		}
	},
	
	importFile: function() {
console.log('Aufruf importFile');
		this.hideTlConfirmMessage();
		this.showImportLockOverlay();
		this.updateImportProcessMessage();
		this.removeImportButton();
		this.removeDeleteButton();
		new Request.JSON({
			url: window.location.href,
			noCache: true,
			data: 'REQUEST_TOKEN=' + Contao.request_token + '&action=callImporterFunction&what=importFile',
			onComplete: function(objResponse) {
				if (objResponse == undefined || !objResponse.success) {
					return;
				}
				this.objConfiguration.fileInfo = objResponse.value;
				this.displayCurrentFileInfos();
				this.handleStatusChange();
				
				this.updateImportProcessMessage();
				
				if (this.objConfiguration.fileInfo.status != 'importFailed' && this.objConfiguration.fileInfo.status != 'importFinished') {
					this.importFile();
				}
				
				if (this.objConfiguration.fileInfo.status == 'importFinished') {
//console.log('Status importFinished');

	const event = new CustomEvent("ImportFinished", {
        detail: {
            timestamp: Date.now(),
            info: "Der Import wurde abgeschlossen"
        }
    });
	window.dispatchEvent(event);
console.log('Status importFinished - nach Event');



					this.hideLockOverlay();
				}
   			}.bind(this)
		}).send();
	},
	
	reloadPage: function() {
		window.location.href = window.location.href;
	},
	
	lockOverlayIsVisible: function() {
		return $('lockOverlay').getStyle('visibility') == 'visible' ? true : false;
	},
	
	hideLockOverlay: function(blnHideInstantly) {
		blnHideInstantly = blnHideInstantly != undefined ? blnHideInstantly : false;
		/*
		 * Get lock overlay and hide it
		 */
		this.elLockOverlay = $('lockOverlay');
		
		if (this.elLockOverlay != undefined && this.elLockOverlay != null) {
			this.elLockOverlay.fade(blnHideInstantly ? 'hide' : 'out');
		}
	},
	
	showLockOverlay: function(message, blnShowInstantly) {
		message = message != undefined ? message : '';
		blnShowInstantly = blnShowInstantly != undefined ? blnShowInstantly : false;
		/*
		 * Get lock overlay and hide it
		 */
		this.elLockOverlay = $('lockOverlay');
		
		if (this.elLockOverlay != undefined && this.elLockOverlay != null) {
			if (typeof(message) == 'object') {
				this.elLockOverlay.getElement('.message').removeClass('inProcess');
				this.elLockOverlay.getElement('.message').empty();
				this.elLockOverlay.getElement('.message').setProperty('html', '');
				this.elLockOverlay.getElement('.message').adopt(message);
			} else {
				this.elLockOverlay.getElement('.message').addClass('inProcess');
				this.elLockOverlay.getElement('.message').empty();
				this.elLockOverlay.getElement('.message').setProperty('html', message);
			}
			this.elLockOverlay.fade(blnShowInstantly ? 'show' : 'in');
		}
	},
	
	hideTlConfirmMessage: function() {
		Array.each($$('#shopImport .tl_confirm'), function(el) {
			el.destroy();
			delete el;
		});
	}
});

window.addEvent('domready', function() {
	ls_shop_BE_import = new class_ls_shop_BE_import();
});