(function() {
	
// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
	els_allDeviantAddressFormFields: null,

	el_checkbox_useDeviantShippingAddress: null,

	start: function() {
		this.registerElements(this.__el_container, 'main');

		if (this.__autoElements.main.deviantShippingAddress) {
			this.initializeDeviantShippingAddressFunctionality();
		}

	},

	getAllDeviantAddressFormFields: function() {
		this.els_allDeviantAddressFormFields = new Elements();
		Array.prototype.push.apply(this.els_allDeviantAddressFormFields, this.__autoElements.main.deviantShippingAddress.getElements('input'));
		Array.prototype.push.apply(this.els_allDeviantAddressFormFields, this.__autoElements.main.deviantShippingAddress.getElements('select'));
		Array.prototype.push.apply(this.els_allDeviantAddressFormFields, this.__autoElements.main.deviantShippingAddress.getElements('textarea'));
	},

	initializeDeviantShippingAddressFunctionality: function() {
		var self = this;

		this.el_checkbox_useDeviantShippingAddress = this.__el_container.getElement('[type="checkbox"][name="useDeviantShippingAddress"]');

		if (this.el_checkbox_useDeviantShippingAddress === null) {
			return;
		}

		this.getAllDeviantAddressFormFields();

		if (this.els_allDeviantAddressFormFields.length <= 0) {
			return;
		}

		Array.each(
			this.els_allDeviantAddressFormFields,
			function(el_formField) {
				if (el_formField.getProperty('data-misc-required') !== null) {
					return;
				}

				el_formField.setProperty('data-misc-required', el_formField.getProperty('required') !== null ? '1' : '0');
			}
		);

		this.el_checkbox_useDeviantShippingAddress.addEvent(
			'change',
			function() {
				if (this.getProperty('checked') === true) {
					self.showDeviantShippingAddressForm();
				} else {
					self.hideDeviantShippingAddressForm();
				}
			}
		);

		this.el_checkbox_useDeviantShippingAddress.fireEvent('change');
	},

	showDeviantShippingAddressForm: function() {
		this.__autoElements.main.deviantShippingAddress.removeClass('hideDeviantShippingAddress');

		Array.each(
			this.els_allDeviantAddressFormFields,
			function(el_formField) {
				if (el_formField.getProperty('data-misc-required') == '1') {
					el_formField.setProperty('required', '');
				}
			}
		);
	},

	hideDeviantShippingAddressForm: function() {
		this.__autoElements.main.deviantShippingAddress.addClass('hideDeviantShippingAddress');

		Array.each(
			this.els_allDeviantAddressFormFields,
			function(el_formField) {
				el_formField.removeProperty('required');
			}
		);
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();