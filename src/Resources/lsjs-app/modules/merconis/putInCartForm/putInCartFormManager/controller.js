(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
		/*
		 * Look for put-in-cart-forms to enrich with the lsjs-module and then
		 * instantiate putInCartFormInstance for each form found.
		 */
		Array.each($$('[data-merconis-component="put-in-cart-form"]'), function(el_putInCartFormContainer) {
			/* ->
			 * Make sure not to handle an element more than once
			 */
			if (!el_putInCartFormContainer.retrieve('alreadyHandledBy_' + str_moduleName)) {
				el_putInCartFormContainer.store('alreadyHandledBy_' + str_moduleName, true);
			} else {
				return;
			}
			/*
			 * <-
			 */

			lsjs.createModule({
				__name: 'putInCartFormInstance',
				__useLoadingIndicator: true,
				__el_container: el_putInCartFormContainer
			});
		});
	}
};

lsjs.addControllerClass(str_moduleName, obj_classdef);

lsjs.__moduleHelpers[str_moduleName] = {
	self: null,
	
	start: function() {
		this.self = lsjs.createModule({
			__name: str_moduleName
		});
	}
};

})();