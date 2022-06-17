(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	el_linkChangeConfiguration: null,
	el_configuratorForm: null,

	int_productId: null,
	int_productVariantId: null,

	start: function() {
		this.int_productId = this.__el_container.getProperty('data-merconis-productId');
		this.int_productVariantId = this.__el_container.getProperty('data-merconis-productVariantId');

		if (!this.int_productVariantId) {
			console.error('Product variant id for configurator could not be determined. Check for [data-merconis-productVariantId] in configurator element: ', this.__el_container);
			return;
		}



		this.initializeEvents();
	},

	initializeEvents: function() {
		this.initializeChangeConfigurationLink();
		this.initializeConfiguratorForm();

//		console.log('this.el_configuratorForm');
//		console.log(this.el_configuratorForm);
//		console.log('this.el_linkChangeConfiguration');
//		console.log(this.el_linkChangeConfiguration);
	},

	initializeConfiguratorForm: function() {
		var self = this;

		this.el_configuratorForm = this.__el_container.getElement('form');

		if (typeOf(this.el_configuratorForm) !== 'element') {
			return;
		}

		this.el_configuratorForm.addEvent('submit', function(event) {
			if (event !== undefined && event !== null) {
				event.stop();
			}

			lsjs.loadingIndicator.__controller.show();

			new Request.cajax({
				url: this.getProperty('action'),
				method: 'post',
				noCache: true,
				cajaxMode: 'updateCompletely',
				el_formToUseForFormData: this,
				obj_additionalFormData: {
					'cajaxRequestData[requestedElementClass]': 'ajax-reload-by-configurator_' + self.int_productVariantId,
					'cajaxRequestData[custom][onlyRequestedProductId]': self.int_productId
				},

				// data: this.toQueryString() + '&cajaxRequestData[requestedElementClass]=ajax-reload-by-configurator_' + self.int_productVariantId,

				onComplete: function() {
					lsjs.loadingIndicator.__controller.hide();
				}
			}).send();
		});
	},

	initializeChangeConfigurationLink: function() {
		var self = this;

		this.el_linkChangeConfiguration = this.__el_container.getElement('.changeConfiguration a');

		if (typeOf(this.el_linkChangeConfiguration) !== 'element') {
			return;
		}

		this.el_linkChangeConfiguration.addEvent('click', function(event) {
			event.stop();

			lsjs.loadingIndicator.__controller.show();

			new Request.cajax({
				url: this.getProperty('href'),
				method: 'post',
				noCache: true,
				cajaxMode: 'updateCompletely',
				data:	'cajaxRequestData[requestedElementClass]=ajax-reload-by-configurator_' + self.int_productVariantId
				+ '&cajaxRequestData[custom][onlyRequestedProductId]=' + self.int_productId
				+ '&REQUEST_TOKEN=' + lsjs.__appHelpers.merconisApp.obj_config.REQUEST_TOKEN,
				onComplete: function() {
					lsjs.loadingIndicator.__controller.hide();
				}
			}).send();
		});
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();