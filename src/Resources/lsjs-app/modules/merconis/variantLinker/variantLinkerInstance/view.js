(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	el_linkToVariant: null,

	int_productId: null,

	start: function() {
		var self = this;

		this.int_productId = this.__el_container.getProperty('data-merconis-productId');

		this.el_linkToVariant = this.__el_container.getElement('a');

		if (typeOf(this.el_linkToVariant) !== 'element') {
			console.error('Could not find a hyperlink element (a) in variantLinker element: ', this.__el_container);
			return;
		}

		this.el_linkToVariant.addEvent('click', function(event) {
			this.blur();
			event.stop();

			lsjs.loadingIndicator.__controller.show();

			new Request.cajax({
				url: this.getProperty('href'),
				method: 'post',
				noCache: true,
				cajaxMode: 'updateCompletely',
				data:	'cajaxRequestData[requestedElementClass]=ajax-reload-by-variantLinker_' + self.int_productId
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