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

	start: function(obj_args, obj_options) {
		obj_args = typeOf(obj_args) === 'object' ? obj_args : {};
		obj_options = typeOf(obj_options) === 'object' ? obj_options : {};

		/*
		 * Only allow one single instance of this module to be started!
		 */
		if (this.self !== null) {
			console.error('module ' + str_moduleName + ' has already been started');
			return;
		}
		/* */

		var obj_argsDefault = {
			__name: str_moduleName
		};

		this.self = lsjs.createModule(Object.merge(obj_argsDefault, obj_args));

		if (typeOf(obj_options) === 'object' && this.self.__models.options !== undefined && this.self.__models.options !== null) {
			this.self.__models.options.set(obj_options);
		}
	}
};

})();