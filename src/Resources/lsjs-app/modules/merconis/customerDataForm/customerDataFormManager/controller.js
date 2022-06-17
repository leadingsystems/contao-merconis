/*
 * -- ACTIVATION: --
 *
 * To activate this module, the following code has to be put in the app.js:
 *
	 lsjs.__moduleHelpers.customerDataFormManager.start({
		 el_domReference: el_domReference
	 });
 *
 * The el_domReference parameter is only required if this module initialization code is called in a cajax_domUpdate event.
 *
 *
 *
 * -- FUNCTIONALITY AND USAGE: --
 *
 * This module's purpose is to hide or show the customer data form fields for the deviant shipping address considering
 * whether or not a checkbox, which indicates whether or not a deviant shipping address should be used, is activated.
 *
 * Add the following attribute to a DOM element to apply this module:
 * data-lsjs-component="customerDataForm"
 *
 * The customer data form has to have a checkbox field with the field name "useDeviantShippingAddress". This checkbox
 * is the one that this module checks when determining whether the deviant shipping address should be used or not.
 *
 * This module also needs to identify all form fields that should be hidden if the above mentioned checkbox is not activated
 * and therefore it requires a DOM element with the following attribute as a container for all those form fields:
 *
 * data-lsjs-element="deviantShippingAddress"
 *
 * In the contao form generator we can add a form element of the type "html code" with the following code before all
 * the deviant shipping address form fields to achieve this
 *
 * <div data-lsjs-element="deviantShippingAddress">
 *
 * Of course, we also need to add another form element of the type "html code" after all those form fields, containing the closing tag.
 *
 */

(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
		var els_toEnhance;
		/*
		 * Look for elements to enrich with the lsjs-module and then
		 * instantiate instances for each element found.
		 */
		if (this.__models.options.data.el_domReference !== undefined && typeOf(this.__models.options.data.el_domReference) === 'element') {
			els_toEnhance = this.__models.options.data.el_domReference.getElements(this.__models.options.data.str_selector);
		} else {
			els_toEnhance = $$(this.__models.options.data.str_selectors);
		}

		Array.each(els_toEnhance, function(el_container) {
			/* ->
			 * Make sure not to handle an element more than once
			 */
			if (!el_container.retrieve('alreadyHandledBy_' + str_moduleName)) {
				el_container.store('alreadyHandledBy_' + str_moduleName, true);
			} else {
				return;
			}
			/*
			 * <-
			 */

			el_container.addClass(this.__models.options.data.str_classToSetWhenModuleApplied);

			lsjs.createModule({
				__name: 'customerDataFormInstance',
				__parentModule: this.__module,
				__useLoadingIndicator: false,
				__el_container: el_container
			});
		}.bind(this));
	}
};

lsjs.addControllerClass(str_moduleName, obj_classdef);

lsjs.__moduleHelpers[str_moduleName] = {
	self: null,
	
	start: function(obj_options) {
		this.self = lsjs.createModule({
			__name: str_moduleName
		});
		this.self.__models.options.set(obj_options);
	}
};

})();