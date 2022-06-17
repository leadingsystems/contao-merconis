(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
		/*
		 * Look for forms to enrich with this lsjs-module and then
		 * instantiate an instance for each form found.
		 */
		Array.each($$('[data-merconis-component="product-management-api-test"]'), function(el_formContainer) {
			/* ->
			 * Make sure not to handle an element more than once
			 */
			if (!el_formContainer.retrieve('alreadyHandledBy_' + str_moduleName)) {
				el_formContainer.store('alreadyHandledBy_' + str_moduleName, true);
			} else {
				return;
			}
			/*
			 * <-
			 */

			lsjs.createModule({
				__name: 'productManagementApiTestInstance',
				__useLoadingIndicator: true,
				__el_container: el_formContainer
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