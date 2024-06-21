var obj_classdef_model = {
	name: 'attributesAndValues',

	data: {
		int_productVariantId: 0,
		int_numMatchingVariants: 0,

		obj_allAttributeValues: {},
		obj_selectedAttributeValues: {},
		obj_possibleAttributeValues: {},
		obj_numAttributeValues: {}
	},

	start: function() {
		/*
		 * Every model needs to call the "this.__module.onModelLoaded()" method
		 * when its data is completely loaded and available or, since in some
		 * cases data is loaded later, when the model is ready for the view
		 * to be rendered.
		 */
		this.__module.onModelLoaded();
	},

	setProductVariantId: function(int_productVariantId) {
		if (int_productVariantId === undefined) {
			console.error('int_productVariantId not given');
		}

		this.writeData('int_productVariantId', int_productVariantId);
	},

	setProductId: function(int_productId) {
		if (int_productId === undefined) {
			console.error('int_productId not given');
		}

		this.writeData('int_productId', int_productId);
	},

	loadAttributesAndValues: function(func_onSuccess) {
		lsjs.loadingIndicator.__controller.show();

		lsjs.apiInterface.request({
			str_resource: 'variantSelector_getInitialData',
			obj_params: {
				productVariantId: this.readData('int_productVariantId')
			},
			func_onSuccess: function(obj_data) {
				/*
				 * While an associative array sent by php via json will be converted
				 * into an object, an empty array still remains an empty array.
				 * Since we expect objects, we have to make sure to create an
				 * empty object if we have an array instead of an object.
				 */
				this.data.obj_allAttributeValues = typeOf(obj_data._allVariantAttributes) === 'object' ? obj_data._allVariantAttributes : {};
				this.data.obj_selectedAttributeValues = typeOf(obj_data._selectedAttributeValues) === 'object' ? obj_data._selectedAttributeValues : {};
				this.data.obj_possibleAttributeValues = typeOf(obj_data._possibleAttributeValues) === 'object' ? obj_data._possibleAttributeValues : {};

				Object.each(
					this.data.obj_allAttributeValues,
					function(obj_attributeData, int_attributeId) {
						this.data.obj_numAttributeValues[int_attributeId] = Object.getLength(obj_attributeData.values)
					}.bind(this)
				);

				// console.log('this.data', this.data);

				if (typeOf(func_onSuccess) === 'function') {
					func_onSuccess();
				}

				lsjs.loadingIndicator.__controller.hide();
			}.bind(this)
		});
	},

	selectAttributeValue: function(int_attributeId, int_valueId) {
		if (!int_attributeId || !int_valueId) {
			console.error('missing parameters - ', 'int_attributeId: ', int_attributeId, 'int_valueId: ', int_valueId);
			return;
		}

		/*
		 * If the currently selected attribute value is not possible in combination
		 * with an already existing selection, we reset the selection so that it
		 * is now possible to select the requested attribute value.
		 */
		if (this.data.obj_possibleAttributeValues[int_attributeId].values[int_valueId] === undefined) {
			this.data.obj_selectedAttributeValues = {};
		}

		this.data.obj_selectedAttributeValues[int_attributeId] = int_valueId;

		this.triggerDataBinding('obj_selectedAttributeValues');

		/*
		 * Read the attribute values that are possible with the current selection
		 */
		// lsjs.loadingIndicator.__controller.show();
		lsjs.apiInterface.request({
			str_resource: 'callProductMethod',
			obj_params: {
				productId: this.readData('int_productVariantId'),
				method: '_getPossibleAttributeValuesForCurrentSelection',
				parameters: JSON.encode([
					this.readData('obj_selectedAttributeValues'),
					/*
					 * Don't let the server count the number of matching variants
					 * for each possible attribute value.
					 * This is better for performance reasons but could be changed
					 * if we wanted to display the number of expected variant matches.
					 */
					false
				])
			},
			func_onSuccess: function(obj_data) {
				this.writeData('obj_possibleAttributeValues', obj_data);
				this.triggerDataBinding('');

				// console.log(this.readData('obj_possibleAttributeValues'));

				// lsjs.loadingIndicator.__controller.hide();
			}.bind(this)
		});
	},

	getNumMatchingVariants: function(func_onSuccess) {
		/*
		 * Determine the number of variants that match the current selection
		 */
		// lsjs.loadingIndicator.__controller.show();
		lsjs.apiInterface.request({
			str_resource: 'callProductMethod',
			obj_params: {
				productId: this.readData('int_productVariantId'),
				method: '_getNumMatchingVariantsByAttributeValues',
				parameters: JSON.encode([
					this.readData('obj_selectedAttributeValues')
				])
			},
			func_onSuccess: function(int_numMatchingVariants) {
				this.data.int_numMatchingVariants = int_numMatchingVariants;
				// console.log('this.data.int_numMatchingVariants: ', this.data.int_numMatchingVariants);

				if (typeOf(func_onSuccess) === 'function') {
					func_onSuccess();
				}

				// lsjs.loadingIndicator.__controller.hide();
			}.bind(this)
		});
	},

	bindingTranslation_selectedValueIdToSelectionClass: function(var_valueToSet, el_bound) {
		var	int_valueId,
			int_attributeId,
			bln_isSelected,
			bln_isPossible,
			bln_isOnlyPossibleOption,
			str_class = '';

		int_valueId = el_bound.getProperty('data-lsjs-value-valueId');
		int_attributeId = el_bound.getProperty('data-lsjs-value-attributeId');

		bln_isSelected = this.data.obj_selectedAttributeValues[int_attributeId] !== undefined && this.data.obj_selectedAttributeValues[int_attributeId] === int_valueId;
		bln_isPossible = this.data.obj_possibleAttributeValues[int_attributeId].values[int_valueId] !== undefined;
		bln_isOnlyPossibleOption = bln_isPossible && Object.getLength(this.data.obj_possibleAttributeValues[int_attributeId].values) === 1;

		if (bln_isSelected) {
			str_class += 'selected';
		}

		if (bln_isPossible) {
			str_class += (str_class !==  '' ? ' ' : '') + 'possible';
		}

		if (bln_isOnlyPossibleOption) {
			str_class += (str_class !==  '' ? ' ' : '') + 'only-option';
		}

		return str_class;
	},

	bindingTranslation_determineSelectedValueInfo: function(var_valueToSet, el_bound) {
		var	int_attributeId,
			int_selectedValueId,
			str_selectedValueName;

		int_attributeId = el_bound.getProperty('data-lsjs-value-attributeId');
		int_selectedValueId = this.data.obj_selectedAttributeValues[int_attributeId];

		el_bound.removeClass('selection-required');

		if (int_selectedValueId !== undefined && int_selectedValueId !== null) {
			str_selectedValueName = this.data.obj_allAttributeValues[int_attributeId].values[int_selectedValueId].valueTitle;
		} else {
			if (Object.getLength(this.data.obj_possibleAttributeValues[int_attributeId].values) === 1) {
				Object.each(
					this.data.obj_possibleAttributeValues[int_attributeId].values,
					function (obj_possibleAttributeValue) {
						str_selectedValueName = obj_possibleAttributeValue.valueTitle;
					}
				);
			} else {
				str_selectedValueName = this.__models.lang.data.MSC.ls_shop.variantSelector.nothingSelectedYet;
				el_bound.addClass('selection-required');
			}
		}

		return str_selectedValueName;
	},

	bindingTranslation_getMoreOptionsInfo: function(var_valueToSet, el_bound) {
		var	int_attributeId,
			int_numMoreOptionsToShow,
			str_moreOptionsTextToUse,
			str_moreOptionsInfo;

		int_attributeId = el_bound.getProperty('data-lsjs-value-attributeId');

		int_numMoreOptionsToShow = Object.getLength(this.data.obj_allAttributeValues[int_attributeId].values);

		if (
			this.data.obj_selectedAttributeValues[int_attributeId] !== undefined
			|| Object.getLength(this.data.obj_possibleAttributeValues[int_attributeId].values) === 1
		) {
			int_numMoreOptionsToShow -= 1;
			str_moreOptionsTextToUse = this.__models.lang.data.MSC.ls_shop.variantSelector.moreOptionsAdditional;
		} else {
			str_moreOptionsTextToUse = this.__models.lang.data.MSC.ls_shop.variantSelector.moreOptionsAll;
		}

		str_moreOptionsInfo = str_moreOptionsTextToUse.replace(/%s/, int_numMoreOptionsToShow);

		return str_moreOptionsInfo;
	}
};