(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
		/*
		 * Look for configurators to enrich with the lsjs-module and then
		 * instantiate configuratorInstance for each configurator found.
		 */
		Array.each($$('[data-merconis-component="variantLinker"]'), function(el_variantLinkerContainer) {
			/* ->
			 * Make sure not to handle an element more than once
			 */
			if (!el_variantLinkerContainer.retrieve('alreadyHandledBy_' + str_moduleName)) {
				el_variantLinkerContainer.store('alreadyHandledBy_' + str_moduleName, true);
			} else {
				return;
			}
			/*
			 * <-
			 */

			lsjs.createModule({
				__name: 'variantLinkerInstance',
				__useLoadingIndicator: true,
				__el_container: el_variantLinkerContainer
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