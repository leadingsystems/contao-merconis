(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	start: function() {
		if (this.__models.options.data.bln_useUnfold) {
			this.init_miniCartUnfold();
		}
	},
	
	reloadCartMini: function() {
		if (lsjs.__appHelpers.merconisApp.obj_config.int_minicartID === undefined || lsjs.__appHelpers.merconisApp.obj_config.int_minicartID === null || lsjs.__appHelpers.merconisApp.obj_config.int_minicartID == 0) {
			return;
		}
		new Request.HTML({
			url: lsjs.__appHelpers.merconisApp.obj_config.str_ajaxUrl,
			noCache: true,
			data: 'REQUEST_TOKEN=' + lsjs.__appHelpers.merconisApp.obj_config.REQUEST_TOKEN + '&isAjax=1&feModID=' + lsjs.__appHelpers.merconisApp.obj_config.int_minicartID,
			update: $$('.template_cart_mini')[0],
			onComplete: function() {
				if (this.__models.options.data.bln_useUnfold) {
					this.init_miniCartUnfold(true);
				}
			}.bind(this)
		}).send();
	},
	
	init_miniCartUnfold: function(bln_toggleOnInitialization) {
		var obj_unfoldOptions = {
			bln_automaticallyCreateResizeBox: false,
			var_initialHeight: 'auto',
			int_heightOffset: 50,
			str_initialDisplayType: 'block',
			str_animationMode: 'margin-top',
			bln_considerWindowScrollInMarginAnimationMode: true,
			bln_moveWithWindowScrollInMarginAnimationMode: true,
			obj_moveWithWindowScrollInMarginAnimationModeOffsets: {
				top: 0,
				bottom: 50
			},
			bln_closeOnOutsideClick: true,

			str_togglerEventType: ['click', 'closeButton'],
			str_initialToggleStatus: 'closed',
			bln_toggleOnInitialization: bln_toggleOnInitialization,
			var_togglerSelector: '.template_cart_mini',
			var_contentBoxSelector: '.template_cart_mini .cartPreview',
			var_wrapperSelector: '.template_cart_mini',
			var_closeButtonSelector: '.template_cart_mini .cartPreview .closeButton',
			obj_morphOptions: {
				'duration': 600
			}
		};
		
		Object.each(obj_unfoldOptions, function(value, key) {
			obj_unfoldOptions[key] = this.__models.options.data.obj_unfoldOptions[key] !== undefined && this.__models.options.data.obj_unfoldOptions[key] !== null ? this.__models.options.data.obj_unfoldOptions[key] : value;
		}.bind(this));
		
		bln_toggleOnInitialization = bln_toggleOnInitialization !== undefined && bln_toggleOnInitialization !== null ? bln_toggleOnInitialization : false;
		this.obj_miniCartUnfold = lsjs.__moduleHelpers.unfold.start(obj_unfoldOptions);
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();