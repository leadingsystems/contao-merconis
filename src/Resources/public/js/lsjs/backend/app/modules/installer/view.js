(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	start: function() {
		this.initializeGui();
	},

	initializeGui: function() {
		var self = this;

		this.registerElements(this.__el_container, 'main', true);

		console.log(this.__autoElements);
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();