(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	start: function() {
	},

    callThemeExporter_export: function() {
		lsjs.loadingIndicator.__controller.show(this.__models.lang.readData('MSC.ls_shop.dashboard.pleaseWait'));
		lsjs.apiInterface.request({
			str_resource: 'merconisThemeExporter_export_legacy',
			obj_params: {
				'ls_api_key': lsjs.__appHelpers.merconisBackendApp.obj_config.API_KEY
			},
			func_onSuccess: function(obj_data) {
				lsjs.__moduleHelpers.messageBox.open({
					str_msg: this.__models.lang.readData('MSC.ls_shop.dashboard.requestSuccessful')
				});

				lsjs.loadingIndicator.__controller.hide();
 			}.bind(this),
			obj_additionalRequestOptions: {
				onFailure: function(obj_request) {
					lsjs.__moduleHelpers.messageBox.open({
						str_msg: this.__models.lang.readData('MSC.ls_shop.dashboard.requestFailed')
					});

					lsjs.loadingIndicator.__controller.hide(true);
				}.bind(this)
			}
		});
	},

    initializeGui: function() {
        this.__view.initializeGui();
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