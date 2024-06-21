(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	arr_instances: [],

	start: function() {
		/*
		 * Look for variant-selectors to enrich with the lsjs-module and then
		 * instantiate variantSelectorInstance for each variant-selector found.
		 */
		Array.each($$('[data-merconis-component="variantSelector"]'), function(el_variantSelectorContainer) {
			this.arr_instances.push(
				lsjs.createModule({
					__name: 'variantSelectorInstance',
					__useLoadingIndicator: true,
					el_variantSelectorContainer: el_variantSelectorContainer
				})
			);
		}.bind(this));
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