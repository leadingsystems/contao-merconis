var obj_classdef_model = {
	name: 'configuration',
	
	data: {},

	start: function() {
		this.loadData();
	},
	
	loadData: function() {
		new Request.JSON(
			{
				url: lsjs.__appHelpers.merconisApp.obj_config.str_ajaxUrl,
				noCache: true,
				method: 'post',
				data: {
					'REQUEST_TOKEN': lsjs.__appHelpers.merconisApp.obj_config.REQUEST_TOKEN,
					'isAjax': 1,
					'requestedClass': 'ModuleProductSearch',
					'action': 'getLiveHitsConfiguration'
				},
				onComplete: function(obj_response) {
					if (obj_response == undefined || !obj_response.success) {
						return;
					}
					
					this.data = obj_response.value;
					this.__module.onModelLoaded();
				}.bind(this)
			}
		).send();
	}
};