var obj_classdef_model = {
	name: 'options',
	
	data: {
	},
	
	start: function() {
		this.__module.onModelLoaded();
	},
	
	set: function(obj_options) {
		Object.merge(this.data, obj_options);
	}
};