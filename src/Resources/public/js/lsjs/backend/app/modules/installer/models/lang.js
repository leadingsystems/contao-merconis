var obj_classdef_model = {
	name: 'lang',
	
	data: {
	},

	start: function() {
		this.loadData();
	},
	
	loadData: function() {
		lsjs.loadingIndicator.__controller.show();

		/*
		 * FIXME: The api request doesn't work yet because we don't use the api key yet.
		 */
		lsjs.apiInterface.request({
			str_resource: 'loadLanguageFiles',
			obj_params: {
				'var_name': 'lsm_installer',
				'var_keys': 'TL_LANG.lsm_installer'
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