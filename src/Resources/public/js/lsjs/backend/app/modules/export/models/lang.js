var obj_classdef_model = {
	name: 'lang',
	
	data: {
	},

	start: function() {
		this.loadData();
	},
	
	loadData: function() {
		lsjs.loadingIndicator.__controller.show();

		lsjs.apiInterface.request({
			str_resource: 'loadLanguageFiles',
			obj_params: {
				'ls_api_key': lsjs.__appHelpers.merconisBackendApp.obj_config.API_KEY,
				'var_name': 'tl_ls_shop_export',
				'var_keys': 'TL_LANG.tl_ls_shop_export.ajax'
			},
			func_onSuccess: function(obj_data) {
				this.data = obj_data;
				
				// console.log('this.data', this.data);
				
				/*
				 * Every model needs to call the "this.__module.onModelLoaded()" method
				 * when its data is completely loaded and available or, since in some
				 * cases data is loaded later, when the model is ready for the view
				 * to be rendered.
				 */
				this.__module.onModelLoaded();
				
				lsjs.loadingIndicator.__controller.hide();
			}.bind(this)
		});
	}
};