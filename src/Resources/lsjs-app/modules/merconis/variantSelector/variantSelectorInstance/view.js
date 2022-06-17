(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	start: function() {
	},
	
	showVariantSelector: function() {
		this.tplAdd(
			{
				name: 'main',
			},
			str_moduleName,
			true
		);
		
		this.__autoElements.main.btn_valueSelect.addEvent('click', function(event) {
			var	int_attributeId,
				int_valueId;
			
			int_attributeId = event.event.currentTarget.getProperty('data-lsjs-value-attributeId');
			int_valueId = event.event.currentTarget.getProperty('data-lsjs-value-valueId');
			this.__controller.selectAttributeValue(int_attributeId, int_valueId);
		}.bind(this));

		if (this.__models.options.data.interfaceType === 'folded') {
			Array.each(
				this.__autoElements.main.attributeBox,
				function (el_attributeBox) {
					lsjs.__moduleHelpers.unfold.start(
						{
							str_initialToggleStatus: 'open',
							bln_toggleOnInitialization: true,
							bln_skipAnimationWhenTogglingOnInitialization: true,
							var_togglerSelector: el_attributeBox,
							var_contentBoxSelector: el_attributeBox.getElement('.values'),
							var_wrapperSelector: el_attributeBox,
							bln_closeOnOutsideClick: true,
							obj_morphOptions: {
								'duration': 600
							}
						}
					);
				}
			);
		}

		this.__module.obj_args.el_variantSelectorContainer.addClass(str_moduleName + '_view-loaded')
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();