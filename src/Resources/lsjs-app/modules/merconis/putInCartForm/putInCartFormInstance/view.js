(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	el_putInCartForm: null,

	int_productId: null,
	int_productVariantId: null,

	start: function() {
		this.int_productId = this.__el_container.getProperty('data-merconis-productId');
		this.int_productVariantId = this.__el_container.getProperty('data-merconis-productVariantId');

		if (!this.int_productVariantId) {
			console.error('Product variant id for put-in-cart-form could not be determined. Check for [data-merconis-productVariantId] in put-in-cart-form element: ', this.__el_container);
			return;
		}



		this.initializeEvents();
	},

	initializeEvents: function() {
		this.initializePutInCartForm();
	},

	initializePutInCartForm: function() {
		var self = this;

		this.el_putInCartForm = typeOf(this.__el_container.getElement('form')) === 'element' ? this.__el_container.getElement('form') : this.__el_container;

		if (typeOf(this.el_putInCartForm) !== 'element') {
			return;
		}

		this.el_putInCartForm.addEvent('submit', function(event) {
			event.stop();

			lsjs.loadingIndicator.__controller.show();

			new Request.cajax({
				url: this.getProperty('action'),
				method: 'post',
				noCache: true,
				bln_doNotModifyUrl: true,
				cajaxMode: 'updateCompletely',
				el_formToUseForFormData: this,
				obj_additionalFormData: {
					'cajaxRequestData[requestedElementClass]': 'ajax-reload-by-putInCart,ajax-reload-by-putInCart_' + self.int_productVariantId,
					'cajaxRequestData[custom][onlyRequestedProductId]': self.int_productId
				},

				onComplete: function() {
					lsjs.loadingIndicator.__controller.hide();
				},

				onSuccess: function(els, str_html, str_script) {
					Browser.exec(str_script);
				}
			}).send();
		});
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();
