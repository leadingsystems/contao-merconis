(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
		/*
		 * Look for put-in-watchlist-forms to enrich with the lsjs-module and then
		 * instantiate putInWatchlistFormInstance for each form found.
		 */
		Array.each($$('[data-merconis-component="put-in-watchlist-form"]'), function(el_putInWatchlistFormContainer) {
			/* ->
			 * Make sure not to handle an element more than once
			 */
			if (!el_putInWatchlistFormContainer.retrieve('alreadyHandledBy_' + str_moduleName)) {
				el_putInWatchlistFormContainer.store('alreadyHandledBy_' + str_moduleName, true);
			} else {
				return;
			}
			/*
			 * <-
			 */

			lsjs.createModule({
				__name: 'putInWatchlistFormInstance',
				__useLoadingIndicator: true,
				__el_container: el_putInWatchlistFormContainer
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