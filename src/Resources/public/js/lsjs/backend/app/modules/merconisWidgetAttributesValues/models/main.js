var obj_classdef_model = {
	name: 'main',

	data: {
		json_data: '[]',
		arr_data: []
	},

	obj_dataFunctionBindings: {
		/*
		 * The data object holds the value in JSON format and in an array.
		 * The following data function bindings make sure that each gets updated when the other is changed.
		 */
		'json_data': 'saveJsonData',
		'arr_data': 'saveArrData'
	},

	start: function() {
		/*
		 * In order to load the original json data from the input field, we need the view to be
		 * initialized and therefore we need to call the module's onModelLoad method first.
		 */
		this.__module.onModelLoaded();
	},

	loadOriginalJsonData: function() {
		this.writeData('json_data', this.__view.el_originalInputField.getProperty('value'));
	},

	saveJsonData: function(var_newData, str_registeredDataPath, var_originalData, str_actualDataPath) {
		if (typeOf(var_newData) !== 'string' || !var_newData) {
			var_newData = '[]';
		}
		this.writeData('arr_data', JSON.parse(var_newData), true, false);
	},

	saveArrData: function(var_newData, str_registeredDataPath, var_originalData, str_actualDataPath) {
		this.writeData('json_data', JSON.stringify(var_newData), true, false);
	},

	updateData: function(els_inputs) {
		var el_lastParent = null;
		var int_arrKeyCounterLevel1 = -1;
		var int_arrKeyCounterLevel2 = 0;
		var arr_dataToStore = [];

		Array.each(
			els_inputs,
			function(el_input) {
				var el_parent = el_input.getParent();

				if (el_parent !== el_lastParent) {
					int_arrKeyCounterLevel1++;
					int_arrKeyCounterLevel2 = 0;
				} else {
					int_arrKeyCounterLevel2++;
				}

				if (typeOf(arr_dataToStore[int_arrKeyCounterLevel1]) !== 'array') {
					arr_dataToStore[int_arrKeyCounterLevel1] = [];
				}

				arr_dataToStore[int_arrKeyCounterLevel1][int_arrKeyCounterLevel2] = el_input.getProperty('value');

				el_lastParent = el_parent;
			}
		);

		this.writeData('arr_data', arr_dataToStore);
		this.__view.drawAttributeValueFields();
	},

	getDefaultRowValue: function() {

		const arr_allAttributesAndValues = this.__models.config.data.obj_allAttributesAndValues;

		//Iterate through the array until a valid attribute with a value is found
		for (let i = 0; i < arr_allAttributesAndValues.length; i++) {

			let attribute = arr_allAttributesAndValues[i]

			if(attribute.values.length > 0){
				for (let j = 0; j < attribute.values.length; j++) {

					let value = attribute.values[j]
					if ( value && value.id !== null && value.id !== undefined) {
						return [
							attribute.id,
							value.id
						];
					}
				}
			}
		}

		const firstAttribute = this.__models.config.data.obj_allAttributesAndValues[0];

		return [
			firstAttribute.id,
			null
		];
	},

	addDataRow: function() {
		var arr_tmp_toModify = this.data.arr_data;
		arr_tmp_toModify.splice(arr_tmp_toModify.length, 0, this.getDefaultRowValue());
		this.writeData('arr_data', arr_tmp_toModify);
		return true;
	},

	copyDataRow: function(int_rowKey) {
		if (int_rowKey === undefined || int_rowKey === null) {
			console.log('data row could not be copied because no data row key was given');
			return false;
		}

		if (this.data.arr_data[int_rowKey] === undefined || this.data.arr_data[int_rowKey] === null) {
			console.log('data row could not be added because given data row key does not exist');
			return false;
		}

		if (typeOf(int_rowKey) !== 'number') {
			console.log('row key must be a number');
			return false;
		}

		var arr_tmp_toModify = this.data.arr_data;
		arr_tmp_toModify.splice(int_rowKey, 0, arr_tmp_toModify[int_rowKey]);
		this.writeData('arr_data', arr_tmp_toModify);
		return true;
	},

	deleteDataRow: function(int_rowKey) {
		if (int_rowKey === undefined || int_rowKey === null) {
			console.log('data row could not be deleted because no data row key was given');
			return false;
		}

		if (this.data.arr_data[int_rowKey] === undefined || this.data.arr_data[int_rowKey] === null) {
			console.log('data row could not be deleted because given data row key does not exist');
			return false;
		}

		if (typeOf(int_rowKey) !== 'number') {
			console.log('row key must be a number');
			return false;
		}

		var arr_tmp_toModify = this.data.arr_data;
		arr_tmp_toModify.splice(int_rowKey, 1);
		this.writeData('arr_data', arr_tmp_toModify);
		return true;
	},

	moveDataRowUp: function(int_rowKey) {
		if (int_rowKey === undefined || int_rowKey === null) {
			console.log('data row could not be moved up because no data row key was given');
			return false;
		}

		if (typeOf(int_rowKey) !== 'number') {
			console.log('row key must be a number');
			return false;
		}

		var arr_tmp_toModify = this.data.arr_data;

		var int_newKey = int_rowKey - 1;
		if (int_newKey < 0) {
			int_newKey = arr_tmp_toModify.length - 1;
		}

		arr_tmp_toModify.splice(int_newKey, 0, arr_tmp_toModify.splice(int_rowKey, 1)[0]);
		this.writeData('arr_data', arr_tmp_toModify);
		return true;
	},

	moveDataRowDown: function(int_rowKey) {
		if (int_rowKey === undefined || int_rowKey === null) {
			console.log('data row could not be moved down because no data row key was given');
			return false;
		}

		if (typeOf(int_rowKey) !== 'number') {
			console.log('row key must be a number');
			return false;
		}

		var arr_tmp_toModify = this.data.arr_data;

		var int_newKey = int_rowKey + 1;
		if (int_newKey >= arr_tmp_toModify.length) {
			int_newKey = 0;
		}

		arr_tmp_toModify.splice(int_newKey, 0, arr_tmp_toModify.splice(int_rowKey, 1)[0]);
		this.writeData('arr_data', arr_tmp_toModify);
		return true;
	}
};