(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
		var int_productVariantId,
			int_productId,
			str_options,
			obj_options = {};

		str_options = this.__module.obj_args.el_variantSelectorContainer.getProperty('data-merconis-variantSelectorOptions');

		if (str_options) {
			obj_options = JSON.decode(str_options);
			this.__models.options.set(obj_options);
		}

		/* ->
		 * Determine the product variant id that the variant selector refers to and then
		 * set the productVariantId in the model.
		 */
		int_productVariantId = this.__module.obj_args.el_variantSelectorContainer.getProperty('data-merconis-productVariantId');

		if (!int_productVariantId) {
			console.error('Product variant id could not be determined. Check for [data-merconis-productVariantId] in original variant selector element: ', this.__module.obj_args.el_variantSelectorContainer);
			return;
		}

		this.__models.attributesAndValues.setProductVariantId(int_productVariantId);
		/*
		 * <-
		 */


		/* ->
		 * Determine the product id that the variant selector refers to and then
		 * set the productId in the model.
		 */
		int_productId = this.__module.obj_args.el_variantSelectorContainer.getProperty('data-merconis-productId');

		if (!int_productId) {
			console.error('Product id could not be determined. Check for [data-merconis-productId] in original variant selector element: ', this.__module.obj_args.el_variantSelectorContainer);
			return;
		}

		this.__models.attributesAndValues.setProductId(int_productId);
		/*
		 * <-
		 */


		/*
		 * Empty the variant-selector container and insert the variantSelectorInstance,
		 * effectively replacing the non-js variant-selector with the lsjs version.
		 */
		this.__module.obj_args.el_variantSelectorContainer.empty();
		this.__module.obj_args.el_variantSelectorContainer.adopt(this.__module.__el_container);

		/*
		 * Load information about the product's attributes and values in the model,
		 * passing the showVariantSelector function as a callback for when loading
		 * is finished
		 */
		this.__models.attributesAndValues.loadAttributesAndValues(this.showVariantSelector.bind(this));
	},

	showVariantSelector: function() {
		if (Object.getLength(this.__models.attributesAndValues.data.obj_allAttributeValues) <= 0) {
			/*
			 * Don't show the variant selector if there are no attributes and values
			 */
			return;
		}
		this.__view.showVariantSelector();
	},

	selectAttributeValue: function(int_attributeId, int_valueId) {
		this.__models.attributesAndValues.selectAttributeValue(int_attributeId, int_valueId);

		/*
		 * Only load a variant if a attribute value has been selected and
		 * not if it has been deselected.
		 */
		if (
				this.__models.attributesAndValues.data.obj_selectedAttributeValues[int_attributeId] !== undefined
			&&	this.__models.attributesAndValues.data.obj_selectedAttributeValues[int_attributeId] !== null
		) {
			this.__models.attributesAndValues.getNumMatchingVariants(this.loadVariant.bind(this));
		}
	},

	loadVariant: function() {
		lsjs.loadingIndicator.__controller.show();
		new Request.cajax({
			url: document.location.href,
			method: 'post',
			noCache: true,
			bln_doNotModifyUrl: true,
			data:	'json_attributeValueSelection=' + JSON.encode(this.__models.attributesAndValues.data.obj_selectedAttributeValues)
					+ '&FORM_SUBMIT=' + 'variantSelectionForm_' + this.__models.attributesAndValues.data.int_productVariantId
					+ '&REQUEST_TOKEN=' + lsjs.__appHelpers.merconisApp.obj_config.REQUEST_TOKEN
					+ '&cajaxRequestData[requestedElementClass]=ajaxReloadByVariantSelector_' + this.__models.attributesAndValues.data.int_productId,
			onComplete: function() {
				lsjs.loadingIndicator.__controller.hide();
			}
		}).send();
	}
};

lsjs.addControllerClass(str_moduleName, obj_classdef);

})();