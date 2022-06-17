(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	start: function() {
		var self = this,
			el_ajaxTestContainer = $$('[data-lsjs-component="ajaxTest"]')[0];
		
		if (typeOf(el_ajaxTestContainer) !== 'element') {
			return;
		}
		
		this.registerElements(el_ajaxTestContainer);
		
		this.__autoElements.defaultElementGroup.ajaxTestElement.addEvent('click', function() {
			self.__controller.ajaxReloadElement(this);
		});
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();