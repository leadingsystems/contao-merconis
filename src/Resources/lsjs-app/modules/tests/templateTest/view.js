(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	start: function() {
		this.tplReplace({
			name: 'main'
		});
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();