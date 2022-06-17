(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
	},
	
	ajaxReloadElement: function(el_toReload) {
		lsjs.loadingIndicator.__controller.show();
		new Request.cajax({
			url: document.location.href,
			method: 'get',
			noCache: true,
			data: 'cajaxRequestData[requestedElementID]=' + el_toReload.getProperty('id'),
			onComplete: function() {
				lsjs.loadingIndicator.__controller.hide();
			}
		}).send();
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