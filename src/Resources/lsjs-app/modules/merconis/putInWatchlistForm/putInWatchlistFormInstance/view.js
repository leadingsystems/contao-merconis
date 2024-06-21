(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	el_putInWatchlistForm: null,
	el_productContainer: null,

	int_productId: null,
	int_productVariantId: null,

	bln_currentlyDisplayedInWatchlistCrossSeller: false,

	start: function() {
		this.int_productId = this.__el_container.getProperty('data-merconis-productId');
		this.int_productVariantId = this.__el_container.getProperty('data-merconis-productVariantId');


		if (!this.int_productVariantId) {
			console.error('Product variant id for put-in-watchlist-form could not be determined. Check for [data-merconis-productVariantId] in put-in-watchlist-form element: ', this.__el_container);
			return;
		}

		this.bln_currentlyDisplayedInWatchlistCrossSeller = typeOf(this.__el_container.getParent('.crossSeller.favorites')) === 'element';
		this.el_productContainer = this.__el_container.getParent('.shopProduct');

		this.initializeEvents();
	},

	initializeEvents: function() {
		this.initializePutInWatchlistForm();
	},

	initializePutInWatchlistForm: function() {
		var self = this;

		this.el_putInWatchlistForm = typeOf(this.__el_container.getElement('form')) === 'element' ? this.__el_container.getElement('form') : this.__el_container;

		if (typeOf(this.el_putInWatchlistForm) !== 'element') {
			return;
		}

		this.el_putInWatchlistForm.addEvent('submit', function(event) {
			event.stop();

			lsjs.loadingIndicator.__controller.show();

			new Request.cajax({
				url: this.getProperty('action'),
				method: 'post',
				noCache: true,
				bln_doNotModifyUrl: true,
				cajaxMode: self.bln_currentlyDisplayedInWatchlistCrossSeller ? 'discard' : 'updateCompletely',
				el_formToUseForFormData: this,
				obj_additionalFormData: {
					'cajaxRequestData[requestedElementClass]': 'ajax-reload-by-putInWatchlist,ajax-reload-by-putInWatchlist_' + self.int_productVariantId,
					'cajaxRequestData[custom][onlyRequestedProductId]': self.int_productId
				},

				onComplete: function() {
					lsjs.loadingIndicator.__controller.hide();

					if (self.bln_currentlyDisplayedInWatchlistCrossSeller) {
						if (typeOf(self.el_productContainer) === 'element') {
							self.el_productContainer.dispose();
						}
					}
				}
			}).send();
		});
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();