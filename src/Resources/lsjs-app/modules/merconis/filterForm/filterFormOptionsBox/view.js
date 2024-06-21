(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
	obj_unfold: null,

	start: function() {
		this.registerElements(this.__el_container, 'main');

		if (this.__autoElements.main.optionsBox_filterOption === undefined) {
			this.__autoElements.main.optionsBox_filterOption = new Elements();
		}
		this.prepare();
		this.hideAllPossibleOptions();
	},

	prepare: function() {
		var self = this;

		this.__el_container.addClass('usingJS');

		Array.each(this.__el_container.getElements('[data-lsjs-element="optionsBox_filterOption"] input'), function(el_filterOptionInput) {
			el_filterOptionInput.addEvent('change', function() {
				self.hideShowMoreLessIfUnneeded();
			});
		});

		this.initializeShowMoreLess();
		this.initializeCheckAllIcon();
		this.initializeUncheckRadioIcon();

		if (this.__module.__parentModule.__models.options.data.bln_useFolding) {
			this.obj_unfold = lsjs.__moduleHelpers.unfold.start({
				str_initialToggleStatus: 'open',
				var_togglerSelector: this.__autoElements.main.optionsBox_label,
				var_contentBoxSelector: this.__autoElements.main.optionsBox_content,
				var_wrapperSelector: this.__el_container,
				obj_morphOptions: {
					'duration': 600
				}
			});
		}
	},

	initializeShowMoreLess: function() {
		var self = this;
		if (typeOf(this.__autoElements.main.showMoreLess) !== 'element') {
			return;
		}

		this.__autoElements.main.showMoreLess.addEvent('click', function() {
			if (this.hasClass('currentlyHiding')) {
				self.showAllOptions();
			} else {
				self.hideAllPossibleOptions();
			}
		});

		this.hideShowMoreLessIfUnneeded();
	},

	initializeCheckAllIcon: function() {
		if (typeOf(this.__autoElements.main.checkAll) !== 'element') {
			return;
		}

		this.__autoElements.main.checkAll.addEvent(
			'click',
			function() {
				var int_numChecked = 0;
				var str_selectorForRelevantInputs = '.filterOption:not(.hidden) input';
				Array.each(
					this.__autoElements.main.optionsBox_filterOptionsWrapper.getElements(str_selectorForRelevantInputs),
					function(el_input) {
						if (el_input.getProperty('checked')) {
							int_numChecked++;
						}
					}
				);
				this.__autoElements.main.optionsBox_filterOptionsWrapper.getElements(str_selectorForRelevantInputs).setProperty('checked', !(int_numChecked > this.__autoElements.main.optionsBox_filterOptionsWrapper.getElements(str_selectorForRelevantInputs).length / 2));
				this.hideShowMoreLessIfUnneeded();
			}.bind(this)
		);
	},

	initializeUncheckRadioIcon: function() {
		if (typeOf(this.__autoElements.main.uncheckRadio) !== 'element') {
			return;
		}

		this.__autoElements.main.uncheckRadio.addEvent(
			'click',
			function() {
				this.__autoElements.main.optionsBox_filterOptionsWrapper.getElements('input').setProperty('checked', false);
				this.hideShowMoreLessIfUnneeded();
			}.bind(this)
		);
	},

	hideShowMoreLessIfUnneeded: function() {
		if (typeOf(this.__autoElements.main.showMoreLess) !== 'element') {
			return;
		}

		var obj_numOptions = this.getNumOptions();

		if (obj_numOptions.hideableOptions <= 0) {
			this.__autoElements.main.showMoreLess.addClass('hidden');
		} else {
			this.__autoElements.main.showMoreLess.removeClass('hidden');
		}

		if (obj_numOptions.hiddenOptions <= 0) {
			this.__autoElements.main.showMoreLess.removeClass('currentlyHiding');
			this.__autoElements.main.showMoreLess.addClass('currentlyShowing');
		} else {
			this.__autoElements.main.showMoreLess.addClass('currentlyHiding');
			this.__autoElements.main.showMoreLess.removeClass('currentlyShowing');
		}
	},

	getNumOptions: function() {
		var obj_return = {
			'allOptions': 0,
			'hiddenOptions': 0,
			'displayedOptions': 0,
			'importantOptions': 0,
			'unimportantOptions': 0,
			'checkedOptions': 0,
			'uncheckedOptions': 0,
			'hideableOptions': 0,
			'unhideableOptions': 0
		}
		Array.each(this.__autoElements.main.optionsBox_filterOption, function(el_filterOption) {
			obj_return.allOptions++;

			var bln_isImportant = false;
			var bln_isChecked = false;

			if (el_filterOption.hasClass('important')) {
				obj_return.importantOptions++;
				bln_isImportant = true;
			} else {
				obj_return.unimportantOptions++;
			}

			if (el_filterOption.getElement('input').getProperty('checked')) {
				obj_return.checkedOptions++;
				bln_isChecked = true;
			} else {
				obj_return.uncheckedOptions++;
			}

			if (bln_isImportant || bln_isChecked) {
				obj_return.unhideableOptions++;
			} else {
				obj_return.hideableOptions++;
			}

			if (el_filterOption.hasClass('hidden')) {
				obj_return.hiddenOptions++;
			} else {
				obj_return.displayedOptions++;
			}
		});

		/*
		 * If there are no options marked as important, we can't know
		 * which options should be considered hideable, so we set the
		 * number of hideableOptions to 0.
		 */
		if (obj_return.importantOptions <= 0) {
			obj_return.hideableOptions = 0;
			obj_return.unhideableOptions = obj_return.unimportantOptions;
		}
		return obj_return;
	},

	hideAllPossibleOptions: function() {
		if (typeOf(this.__autoElements.main.showMoreLess) !== 'element') {
			return;
		}

		/*
		 * Don't hide anything if there are no important options
		 */
		var arr_importantOptions = this.__autoElements.main.optionsBox_filterOption.filter('.important');
		if (arr_importantOptions.length <= 0) {
			return;
		}

		Array.each(this.__autoElements.main.optionsBox_filterOption, function(el_filterOption) {
			if (el_filterOption.hasClass('important') || el_filterOption.getElement('input').getProperty('checked')) {
				return;
			}

			el_filterOption.addClass('hidden');
		});
		this.__autoElements.main.showMoreLess.addClass('currentlyHiding');
		this.__autoElements.main.showMoreLess.removeClass('currentlyShowing');
	},

	showAllOptions: function() {
		if (typeOf(this.__autoElements.main.showMoreLess) !== 'element') {
			return;
		}

		Array.each(this.__autoElements.main.optionsBox_filterOption, function(el_filterOption) {
			el_filterOption.removeClass('hidden');
		});
		this.__autoElements.main.showMoreLess.removeClass('currentlyHiding');
		this.__autoElements.main.showMoreLess.addClass('currentlyShowing');
	},

	clear: function() {
		Array.each(
			this.__el_container.getElements('input'),
			function(el_input) {
				if (['checkbox', 'radio'].includes(el_input.getProperty('type'))) {
					el_input.setProperty('checked', false);
				} else if (['text'].includes(el_input.getProperty('type'))) {
					el_input.setProperty('value', '');
				}
			}
		);
	}
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();