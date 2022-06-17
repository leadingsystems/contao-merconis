var obj_classdef_model = {
	name: 'options',
	
	data: {
		var_inputField: '.liveHits input[name="merconis_searchWord"]'
	},
	
	start: function() {
	},
	
	set: function(obj_options) {
		Object.merge(this.data, obj_options);
		this.__module.onModelLoaded();
	}
};