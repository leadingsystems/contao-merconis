(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
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