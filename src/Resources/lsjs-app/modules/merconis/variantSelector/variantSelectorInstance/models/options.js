var obj_classdef_model = {
	name: 'options',

	data: {
		interfaceType: 'standard' // standard, folded
	},

	start: function() {
		this.__module.onModelLoaded();
	},

	set: function(obj_options) {
		Object.merge(this.data, obj_options);
	}
};