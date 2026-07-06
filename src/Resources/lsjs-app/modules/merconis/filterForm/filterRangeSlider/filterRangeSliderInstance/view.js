(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
    obj_config: {},
    arr_optionData: [],
    arr_numericOptionData: [],
    arr_nonNumericOptionData: [],
    arr_numericGroups: [],
    el_content: null,
    el_optionsWrapper: null,
    el_nonNumericOptionsWrapper: null,
    el_sliderRoot: null,
    el_sliderTrackActive: null,
    el_sliderInputMin: null,
    el_sliderInputMax: null,
    el_sliderValueMin: null,
    el_sliderValueMax: null,
    el_leftHintFull: null,
    el_leftHintCompact: null,
    el_rightHintFull: null,
    el_rightHintCompact: null,
    el_generatedInputsContainer: null,
    int_selectedMinIndex: 0,
    int_selectedMaxIndex: 0,
    int_trackRefreshTimer: null,
    obj_resizeObserver: null,
    obj_initialRange: null,
    str_unit: '',

    start: function() {
        this.el_content = this.__el_container.getElement('[data-lsjs-element="optionsBox_content"]') || this.__el_container.getElement('.content');
        this.el_optionsWrapper = this.__el_container.getElement('[data-lsjs-element="optionsBox_filterOptionsWrapper"]');

        if (typeOf(this.el_content) !== 'element' || typeOf(this.el_optionsWrapper) !== 'element') {
            return;
        }

        this.obj_config = this.readConfig();
        this.collectOptionData();
        this.str_unit = this.determineConsistentUnit();

        if (!this.arr_optionData.length) {
            return;
        }

        this.__el_container.store('filterRangeSliderInstance', this);
        this.hideShowMoreLessToggle();

        if (this.arr_numericOptionData.length < this.obj_config.int_minOptionCount) {
            this.applyFallbackState();
            return;
        }

        this.__el_container.addClass('uses-range-slider');
        this.createSliderMarkup();
        this.moveOptionsIntoBuckets();
        this.initializeSliderState();
        this.bindSliderEvents();
        this.bindOptionEvents();
        this.bindFormResetEvent();
        this.applyRangeVisibility();
        this.registerVisibilityRefreshHooks();
        this.scheduleTrackRefresh();
    },

    readConfig: function() {
        return {
            str_displayMode: this.__el_container.getProperty('data-filter-display-mode') || 'showMoreLess',
            str_decimalSeparator: this.__el_container.getProperty('data-range-slider-decimal-separator') || 'dot',
            str_thousandSeparator: this.__el_container.getProperty('data-range-slider-thousand-separator') || 'none',
            int_minOptionCount: Math.max(parseInt(this.__el_container.getProperty('data-range-slider-min-option-count') || '10', 10) || 10, 3),
            int_initialOptionCount: Math.max(parseInt(this.__el_container.getProperty('data-range-slider-initial-option-count') || '10', 10) || 10, 1),
            str_initialPosition: this.normalizeInitialPosition(this.__el_container.getProperty('data-range-slider-initial-position') || 'bottom'),
            str_autoOptionVisibility: this.normalizeAutoOptionVisibility(this.__el_container.getProperty('data-range-slider-auto-option-visibility') || 'show'),
            str_moreLabel: this.__el_container.getProperty('data-range-slider-more-label') || '%s more',
            str_moreLabelCompact: this.__el_container.getProperty('data-range-slider-more-label-compact') || '+ %s',
            str_minHandleLabel: this.__el_container.getProperty('data-range-slider-min-handle-label') || 'Minimum value',
            str_maxHandleLabel: this.__el_container.getProperty('data-range-slider-max-handle-label') || 'Maximum value'
        };
    },

    normalizeInitialPosition: function(str_initialPosition) {
        return ['top', 'middle', 'bottom'].indexOf(str_initialPosition) !== -1
            ? str_initialPosition
            : 'bottom';
    },

    normalizeAutoOptionVisibility: function(str_autoOptionVisibility) {
        return str_autoOptionVisibility === 'hide' ? 'hide' : 'show';
    },

    collectOptionData: function() {
        var map_groupsByValue = {};

        this.arr_optionData = [];
        this.arr_numericOptionData = [];
        this.arr_nonNumericOptionData = [];
        this.arr_numericGroups = [];

        Array.each(
            this.el_optionsWrapper.getElements('[data-lsjs-element="optionsBox_filterOption"]'),
            function(el_option, int_index) {
                var el_input = el_option.getElement('input');
                var el_labelText = el_option.getElement('.label-text');
                var str_labelText = typeOf(el_labelText) === 'element'
                    ? el_labelText.get('text').trim()
                    : (el_option.get('text') || '').trim();
                var obj_numericValue = this.extractNumericValue(str_labelText);
                var obj_optionData = {
                    el_option: el_option,
                    el_input: el_input,
                    str_labelText: str_labelText,
                    int_originalIndex: int_index,
                    obj_numericValue: obj_numericValue,
                    int_groupIndex: null
                };

                this.arr_optionData.push(obj_optionData);

                if (obj_numericValue === null) {
                    this.arr_nonNumericOptionData.push(obj_optionData);
                    return;
                }

                this.arr_numericOptionData.push(obj_optionData);
            }.bind(this)
        );

        this.arr_numericOptionData.sort(function(obj_first, obj_second) {
            if (obj_first.obj_numericValue.float_value === obj_second.obj_numericValue.float_value) {
                return obj_first.int_originalIndex - obj_second.int_originalIndex;
            }

            return obj_first.obj_numericValue.float_value - obj_second.obj_numericValue.float_value;
        });

        Array.each(
            this.arr_numericOptionData,
            function(obj_optionData) {
                var str_groupKey = this.getGroupKey(obj_optionData.obj_numericValue.float_value);

                if (map_groupsByValue[str_groupKey] === undefined) {
                    map_groupsByValue[str_groupKey] = {
                        float_value: obj_optionData.obj_numericValue.float_value,
                        str_displayValue: obj_optionData.obj_numericValue.str_displayValue,
                        arr_options: []
                    };
                    this.arr_numericGroups.push(map_groupsByValue[str_groupKey]);
                }

                map_groupsByValue[str_groupKey].arr_options.push(obj_optionData);
            }.bind(this)
        );

        Array.each(
            this.arr_numericGroups,
            function(obj_group, int_groupIndex) {
                Array.each(
                    obj_group.arr_options,
                    function(obj_optionData) {
                        obj_optionData.int_groupIndex = int_groupIndex;
                    }
                );
            }
        );
    },

    determineConsistentUnit: function() {
        var str_detectedUnit = '';
        var bln_hasConsistentUnit = true;

        if (!this.arr_numericOptionData.length) {
            return '';
        }

        Array.each(
            this.arr_numericOptionData,
            function(obj_optionData) {
                var str_suffix;

                if (!bln_hasConsistentUnit || obj_optionData.obj_numericValue === null) {
                    return;
                }

                str_suffix = String(obj_optionData.obj_numericValue.str_suffix || '').trim();

                if (str_suffix === '') {
                    bln_hasConsistentUnit = false;
                    return;
                }

                if (str_detectedUnit === '') {
                    str_detectedUnit = str_suffix;
                    return;
                }

                if (str_detectedUnit !== str_suffix) {
                    bln_hasConsistentUnit = false;
                }
            }
        );

        return bln_hasConsistentUnit ? str_detectedUnit : '';
    },

    extractNumericValue: function(str_labelText) {
        var str_match = null;
        var str_normalizedNumericString = '';
        var str_decimalCharacter = this.obj_config.str_decimalSeparator === 'comma' ? ',' : '.';
        var str_thousandCharacter = this.getSeparatorCharacter(this.obj_config.str_thousandSeparator);
        var arr_matches;
        var float_value;
        var str_suffix;

        if (!str_labelText) {
            return null;
        }

        arr_matches = str_labelText
            .replace(/\u00a0/g, ' ')
            .match(/[+-]?\d[\d\s.,']*/);

        if (!arr_matches || !arr_matches.length) {
            return null;
        }

        str_match = arr_matches[0].replace(/[.,'\s]+$/, '').trim();
        str_normalizedNumericString = str_match;

        if (str_thousandCharacter !== '') {
            str_normalizedNumericString = str_normalizedNumericString.split(str_thousandCharacter).join('');
        }

        str_normalizedNumericString = str_normalizedNumericString.replace(/\s+/g, '');

        if (str_decimalCharacter === ',') {
            str_normalizedNumericString = str_normalizedNumericString.replace(',', '.');
        }

        if (!/^[+-]?\d+(?:\.\d+)?$/.test(str_normalizedNumericString)) {
            return null;
        }

        float_value = parseFloat(str_normalizedNumericString);
        str_suffix = str_labelText.substring(arr_matches.index + arr_matches[0].length).trim();

        if (isNaN(float_value)) {
            return null;
        }

        return {
            float_value: float_value,
            str_displayValue: str_match.replace(/\s+/g, this.obj_config.str_thousandSeparator === 'space' ? ' ' : ''),
            str_suffix: str_suffix
        };
    },

    getSeparatorCharacter: function(str_separatorName) {
        switch (str_separatorName) {
            case 'dot':
                return '.';
            case 'comma':
                return ',';
            case 'space':
                return ' ';
            case 'apostrophe':
                return "'";
            default:
                return '';
        }
    },

    getGroupKey: function(float_value) {
        return String(Math.round(float_value * 1000000) / 1000000);
    },

    groupHasAvailableOption: function(obj_group) {
        var bln_hasAvailable = false;

        Array.each(
            obj_group.arr_options,
            function(obj_optionData) {
                if (!obj_optionData.el_option.hasClass('zero-match-hidden')) {
                    bln_hasAvailable = true;
                }
            }
        );

        return bln_hasAvailable;
    },

    hideShowMoreLessToggle: function() {
        Array.each(
            this.__el_container.getElements('[data-lsjs-element="showMoreLess"], [data-fast-filter-show-more-less]'),
            function(el_toggle) {
                el_toggle.addClass('hidden');
                el_toggle.setProperty('aria-hidden', 'true');
            }
        );
    },

    applyFallbackState: function() {
        this.__el_container.addClass('range-slider-fallback');

        Array.each(
            this.arr_optionData,
            function(obj_optionData) {
                obj_optionData.el_option.removeClass('range-hidden');
                obj_optionData.el_option.removeClass('show-more-hidden');

                if (!obj_optionData.el_option.hasClass('zero-match-hidden')) {
                    obj_optionData.el_option.removeClass('hidden');
                }
            }
        );
    },

    createSliderMarkup: function() {
        var el_values;
        var el_hints;
        var el_track;
        var el_leftHint;
        var el_rightHint;

        this.el_sliderRoot = new Element('div', {
            'class': 'filter-range-slider',
            'data-filter-range-slider': '1'
        });

        el_values = new Element('div', {
            'class': 'filter-range-slider__values'
        }).inject(this.el_sliderRoot);

        this.el_sliderValueMin = new Element('span', {
            'class': 'filter-range-slider__value filter-range-slider__value--min'
        }).inject(el_values);

        this.el_sliderValueMax = new Element('span', {
            'class': 'filter-range-slider__value filter-range-slider__value--max'
        }).inject(el_values);

        el_track = new Element('div', {
            'class': 'filter-range-slider__track'
        }).inject(this.el_sliderRoot);

        this.el_sliderTrackActive = new Element('div', {
            'class': 'filter-range-slider__track-active'
        }).inject(el_track);

        this.el_sliderInputMin = new Element('input', {
            'class': 'filter-range-slider__input filter-range-slider__input--min',
            'type': 'range',
            'min': '0',
            'max': String(this.arr_numericGroups.length - 1),
            'step': '1',
            'value': '0',
            'aria-label': this.obj_config.str_minHandleLabel
        }).inject(el_track);

        this.el_sliderInputMax = new Element('input', {
            'class': 'filter-range-slider__input filter-range-slider__input--max',
            'type': 'range',
            'min': '0',
            'max': String(this.arr_numericGroups.length - 1),
            'step': '1',
            'value': String(this.arr_numericGroups.length - 1),
            'aria-label': this.obj_config.str_maxHandleLabel
        }).inject(el_track);

        el_hints = new Element('div', {
            'class': 'filter-range-slider__hints'
        }).inject(this.el_sliderRoot);

        el_leftHint = new Element('span', {
            'class': 'filter-range-slider__hint filter-range-slider__hint--left'
        }).inject(el_hints);

        this.el_leftHintFull = new Element('span', {
            'class': 'filter-range-slider__hint-text filter-range-slider__hint-text--full'
        }).inject(el_leftHint);

        this.el_leftHintCompact = new Element('span', {
            'class': 'filter-range-slider__hint-text filter-range-slider__hint-text--compact'
        }).inject(el_leftHint);

        el_rightHint = new Element('span', {
            'class': 'filter-range-slider__hint filter-range-slider__hint--right'
        }).inject(el_hints);

        this.el_rightHintFull = new Element('span', {
            'class': 'filter-range-slider__hint-text filter-range-slider__hint-text--full'
        }).inject(el_rightHint);

        this.el_rightHintCompact = new Element('span', {
            'class': 'filter-range-slider__hint-text filter-range-slider__hint-text--compact'
        }).inject(el_rightHint);

        this.el_sliderRoot.inject(this.el_optionsWrapper, 'before');
    },

    moveOptionsIntoBuckets: function() {
        this.el_optionsWrapper.addClass('filter-range-slider__options');
        this.el_optionsWrapper.addClass('filter-range-slider__options--numeric');

        this.el_generatedInputsContainer = new Element('div', {
            'class': 'filter-range-slider__generated-inputs',
            'data-filter-range-slider-generated-inputs': '1'
        }).inject(this.el_optionsWrapper, 'after');

        this.el_nonNumericOptionsWrapper = new Element('div', {
            'class': 'filter-range-slider__options filter-range-slider__options--non-numeric'
        }).inject(this.el_generatedInputsContainer, 'after');

        Array.each(
            this.arr_numericOptionData,
            function(obj_optionData) {
                this.el_optionsWrapper.adopt(obj_optionData.el_option);
            }.bind(this)
        );

        Array.each(
            this.arr_nonNumericOptionData,
            function(obj_optionData) {
                this.el_nonNumericOptionsWrapper.adopt(obj_optionData.el_option);
            }.bind(this)
        );
    },

    initializeSliderState: function() {
        var obj_initialRange = this.obj_config.str_displayMode === 'sliderRange'
            ? this.determineInitialRange()
            : {
                int_minIndex: 0,
                int_maxIndex: this.arr_numericGroups.length - 1
            };
        var obj_persistedRange;

        this.obj_initialRange = obj_initialRange;
        obj_persistedRange = this.determinePersistedRange(obj_initialRange);
        this.int_selectedMinIndex = obj_persistedRange.int_minIndex;
        this.int_selectedMaxIndex = obj_persistedRange.int_maxIndex;
        this.el_sliderInputMin.setProperty('value', String(this.int_selectedMinIndex));
        this.el_sliderInputMax.setProperty('value', String(this.int_selectedMaxIndex));
    },

    determineInitialRange: function() {
        var int_totalNumericOptions = this.arr_numericOptionData.length;
        var int_lastGroupIndex = this.arr_numericGroups.length - 1;
        var int_targetOptionCount = Math.max(
            Math.min(this.obj_config.int_initialOptionCount, int_totalNumericOptions),
            1
        );
        var int_sliceStartIndex;
        var int_sliceEndIndex;
        var obj_startOption;
        var obj_endOption;

        if (!int_totalNumericOptions || int_lastGroupIndex < 0) {
            return {
                int_minIndex: 0,
                int_maxIndex: 0
            };
        }

        if (int_targetOptionCount >= int_totalNumericOptions) {
            return {
                int_minIndex: 0,
                int_maxIndex: int_lastGroupIndex
            };
        }

        int_sliceStartIndex = this.getInitialSliceStartIndex(int_targetOptionCount, int_totalNumericOptions);
        int_sliceEndIndex = int_sliceStartIndex + int_targetOptionCount - 1;
        obj_startOption = this.arr_numericOptionData[int_sliceStartIndex];
        obj_endOption = this.arr_numericOptionData[int_sliceEndIndex];

        return {
            int_minIndex: obj_startOption && obj_startOption.int_groupIndex !== null ? obj_startOption.int_groupIndex : 0,
            int_maxIndex: obj_endOption && obj_endOption.int_groupIndex !== null ? obj_endOption.int_groupIndex : int_lastGroupIndex
        };
    },

    getInitialSliceStartIndex: function(int_targetOptionCount, int_totalNumericOptions) {
        switch (this.obj_config.str_initialPosition) {
            case 'top':
                return int_totalNumericOptions - int_targetOptionCount;
            case 'middle':
                return Math.floor((int_totalNumericOptions - int_targetOptionCount) / 2);
            default:
                return 0;
        }
    },

    determinePersistedRange: function(obj_fallbackRange) {
        var obj_state = this.getStoredSliderState();
        var arr_matchingGroupIndices = [];

        if (obj_state === null) {
            return obj_fallbackRange;
        }

        Array.each(
            this.arr_numericGroups,
            function(obj_group, int_groupIndex) {
                if (
                    obj_group.float_value >= obj_state.float_minValue
                    && obj_group.float_value <= obj_state.float_maxValue
                    && this.groupHasAvailableOption(obj_group)
                ) {
                    arr_matchingGroupIndices.push(int_groupIndex);
                }
            }.bind(this)
        );

        if (!arr_matchingGroupIndices.length) {
            return {
                int_minIndex: 0,
                int_maxIndex: this.arr_numericGroups.length - 1
            };
        }

        return {
            int_minIndex: arr_matchingGroupIndices[0],
            int_maxIndex: arr_matchingGroupIndices[arr_matchingGroupIndices.length - 1]
        };
    },

    getStoredSliderState: function() {
        var str_stateKey = this.getSliderStateKey();
        var obj_stateStore = this.getSliderStateStore();
        var obj_state = str_stateKey !== '' ? obj_stateStore[str_stateKey] : null;

        if (
            obj_state === undefined
            || obj_state === null
            || typeof obj_state !== 'object'
            || typeof obj_state.float_minValue !== 'number'
            || typeof obj_state.float_maxValue !== 'number'
            || isNaN(obj_state.float_minValue)
            || isNaN(obj_state.float_maxValue)
            || obj_state.float_minValue > obj_state.float_maxValue
        ) {
            return null;
        }

        return obj_state;
    },

    persistCurrentSliderState: function() {
        var str_stateKey = this.getSliderStateKey();
        var obj_minGroup = this.arr_numericGroups[this.int_selectedMinIndex];
        var obj_maxGroup = this.arr_numericGroups[this.int_selectedMaxIndex];

        if (
            str_stateKey === ''
            || obj_minGroup === undefined
            || obj_maxGroup === undefined
        ) {
            return;
        }

        this.getSliderStateStore()[str_stateKey] = {
            float_minValue: obj_minGroup.float_value,
            float_maxValue: obj_maxGroup.float_value
        };
    },

    getSliderStateStore: function() {
        if (lsjs.__moduleHelpers.filterRangeSlider === undefined) {
            lsjs.__moduleHelpers.filterRangeSlider = {};
        }

        if (lsjs.__moduleHelpers.filterRangeSlider.obj_sliderStates === undefined) {
            lsjs.__moduleHelpers.filterRangeSlider.obj_sliderStates = {};
        }

        return lsjs.__moduleHelpers.filterRangeSlider.obj_sliderStates;
    },

    getSliderStateKey: function() {
        var str_fieldId = this.__el_container.getProperty('data-filter-field-id')
            || this.__el_container.getProperty('data-lsjs-filter-section-id')
            || '';

        if (str_fieldId !== '') {
            return String(str_fieldId);
        }

        var el_firstInput = this.__el_container.getElement('input[name]');

        if (typeOf(el_firstInput) === 'element') {
            return el_firstInput.getProperty('name') || '';
        }

        return '';
    },

    bindSliderEvents: function() {
        this.el_sliderInputMin.addEvent(
            'input',
            function() {
                this.handleSliderInput('min');
            }.bind(this)
        );

        this.el_sliderInputMax.addEvent(
            'input',
            function() {
                this.handleSliderInput('max');
            }.bind(this)
        );

        this.el_sliderInputMin.addEvent(
            'change',
            function() {
                this.handleSliderInput('min');
                this.handleDirectFilterApply();
            }.bind(this)
        );

        this.el_sliderInputMax.addEvent(
            'change',
            function() {
                this.handleSliderInput('max');
                this.handleDirectFilterApply();
            }.bind(this)
        );
    },

    bindOptionEvents: function() {
        Array.each(
            this.arr_optionData,
            function(obj_optionData) {
                if (typeOf(obj_optionData.el_input) !== 'element') {
                    return;
                }

                if (obj_optionData.el_input.retrieve('alreadyHandledBy_' + str_moduleName)) {
                    return;
                }

                obj_optionData.el_input.store('alreadyHandledBy_' + str_moduleName, true);
                obj_optionData.el_input.addEvent(
                    'change',
                    function() {
                        window.setTimeout(
                            function() {
                                this.applyRangeVisibility();
                            }.bind(this),
                            0
                        );
                    }.bind(this)
                );
            }.bind(this)
        );
    },

    bindFormResetEvent: function() {
        var el_form = this.__el_container.getParent('form');

        if (typeOf(el_form) !== 'element') {
            return;
        }

        el_form.addEvent(
            'reset',
            function() {
                window.setTimeout(
                    function() {
                        this.resetToInitialRange();
                    }.bind(this),
                    0
                );
            }.bind(this)
        );
    },

    handleSliderInput: function(str_handle) {
        var int_minValue = parseInt(this.el_sliderInputMin.getProperty('value') || '0', 10);
        var int_maxValue = parseInt(this.el_sliderInputMax.getProperty('value') || '0', 10);

        if (isNaN(int_minValue)) {
            int_minValue = 0;
        }

        if (isNaN(int_maxValue)) {
            int_maxValue = this.arr_numericGroups.length - 1;
        }

        if (str_handle === 'min' && int_minValue > int_maxValue) {
            int_maxValue = int_minValue;
            this.el_sliderInputMax.setProperty('value', String(int_maxValue));
        }

        if (str_handle === 'max' && int_maxValue < int_minValue) {
            int_minValue = int_maxValue;
            this.el_sliderInputMin.setProperty('value', String(int_minValue));
        }

        this.int_selectedMinIndex = int_minValue;
        this.int_selectedMaxIndex = int_maxValue;
        this.persistCurrentSliderState();
        this.applyRangeVisibility();
    },

    handleDirectFilterApply: function() {
        if (!this.isDirectFilterMode()) {
            return;
        }

        window.dispatchEvent(
            new CustomEvent(
                'filterRangeSlider:applied',
                {
                    detail: {
                        el_field: this.__el_container
                    }
                }
            )
        );
    },

    applyRangeVisibility: function() {
        Array.each(
            this.arr_optionData,
            function(obj_optionData) {
                this.normalizeOptionVisibility(obj_optionData.el_option);

                if (obj_optionData.obj_numericValue === null) {
                    return;
                }

                if (
                    obj_optionData.int_groupIndex < this.int_selectedMinIndex
                    || obj_optionData.int_groupIndex > this.int_selectedMaxIndex
                ) {
                    if (
                        !this.isDirectFilterMode()
                        && (
                            typeOf(obj_optionData.el_input) === 'element'
                            && obj_optionData.el_input.getProperty('checked')
                        )
                    ) {
                        return;
                    }

                    if (
                        typeOf(obj_optionData.el_input) === 'element'
                        && obj_optionData.el_input.getProperty('checked')
                    ) {
                        obj_optionData.el_input.setProperty('checked', false);
                    }

                    obj_optionData.el_option.addClass('range-hidden');
                }
            }.bind(this)
        );

        if (this.isDirectFilterMode()) {
            this.syncDirectFilterState();
        }

        this.updateSliderUi();
    },

    normalizeOptionVisibility: function(el_option) {
        el_option.removeClass('range-hidden');
        el_option.removeClass('show-more-hidden');
        el_option.removeClass('is-slider-direct-selected');
        el_option.removeClass('is-slider-direct-locked');

        if (!el_option.hasClass('zero-match-hidden')) {
            el_option.removeClass('hidden');
        }
    },

    syncDirectFilterState: function() {
        var bln_showAutoOptions = this.obj_config.str_autoOptionVisibility === 'show';
        var bln_isFullRange = this.isFullRangeSelected();

        this.clearGeneratedInputs();

        Array.each(
            this.arr_numericOptionData,
            function(obj_optionData) {
                var bln_isInsideSelectedRange = obj_optionData.int_groupIndex >= this.int_selectedMinIndex
                    && obj_optionData.int_groupIndex <= this.int_selectedMaxIndex;

                this.applyDirectFilterOptionState(
                    obj_optionData,
                    bln_isInsideSelectedRange,
                    bln_showAutoOptions
                );

                if (!bln_isInsideSelectedRange || bln_isFullRange) {
                    return;
                }

                this.appendGeneratedInputForOption(obj_optionData);
            }.bind(this)
        );

        this.toggleDirectFilterActions();
    },

    applyDirectFilterOptionState: function(obj_optionData, bln_isInsideSelectedRange, bln_showAutoOptions) {
        if (typeOf(obj_optionData.el_input) === 'element') {
            obj_optionData.el_input.setProperty('checked', false);
            obj_optionData.el_input.setProperty('disabled', 'disabled');
            obj_optionData.el_input.setProperty('aria-disabled', 'true');
        }

        if (!bln_isInsideSelectedRange) {
            obj_optionData.el_option.addClass('range-hidden');
            return;
        }

        obj_optionData.el_option.addClass('is-slider-direct-selected');
        obj_optionData.el_option.addClass('is-slider-direct-locked');

        if (!bln_showAutoOptions) {
            obj_optionData.el_option.addClass('range-hidden');
        }
    },

    appendGeneratedInputForOption: function(obj_optionData) {
        var str_inputName;
        var str_inputValue;

        if (
            typeOf(this.el_generatedInputsContainer) !== 'element'
            || typeOf(obj_optionData.el_input) !== 'element'
        ) {
            return;
        }

        str_inputName = obj_optionData.el_input.getProperty('name') || '';
        str_inputValue = obj_optionData.el_input.getProperty('value') || '';

        if (str_inputName === '' || str_inputValue === '') {
            return;
        }

        new Element('input', {
            'type': 'hidden',
            'name': str_inputName,
            'value': str_inputValue,
            'data-filter-range-slider-generated-input': '1',
            'data-fast-filter-generated-input': '1'
        }).inject(this.el_generatedInputsContainer);
    },

    clearGeneratedInputs: function() {
        if (typeOf(this.el_generatedInputsContainer) !== 'element') {
            return;
        }

        this.el_generatedInputsContainer.getElements('[data-filter-range-slider-generated-input]').destroy();
    },

    toggleDirectFilterActions: function() {
        Array.each(
            this.__el_container.getElements(
                '[data-lsjs-element="checkAll"], [data-lsjs-element="uncheckRadio"], [data-fast-filter-check-all], [data-fast-filter-uncheck-radio]'
            ),
            function(el_action) {
                if (this.isDirectFilterMode()) {
                    el_action.addClass('hidden');
                    return;
                }

                el_action.removeClass('hidden');
            }.bind(this)
        );
    },

    isDirectFilterMode: function() {
        return this.obj_config.str_displayMode === 'sliderDirect';
    },

    isFullRangeSelected: function() {
        return this.int_selectedMinIndex === 0
            && this.int_selectedMaxIndex === this.arr_numericGroups.length - 1;
    },

    resetToInitialRange: function() {
        if (this.obj_initialRange === null) {
            return;
        }

        this.int_selectedMinIndex = this.obj_initialRange.int_minIndex;
        this.int_selectedMaxIndex = this.obj_initialRange.int_maxIndex;
        this.el_sliderInputMin.setProperty('value', String(this.int_selectedMinIndex));
        this.el_sliderInputMax.setProperty('value', String(this.int_selectedMaxIndex));
        this.persistCurrentSliderState();
        this.applyRangeVisibility();
    },

    updateSliderUi: function() {
        var obj_minGroup = this.arr_numericGroups[this.int_selectedMinIndex];
        var obj_maxGroup = this.arr_numericGroups[this.int_selectedMaxIndex];
        var int_lastIndex = this.arr_numericGroups.length - 1;
        var float_minPercent = int_lastIndex > 0 ? (this.int_selectedMinIndex / int_lastIndex) * 100 : 0;
        var float_maxPercent = int_lastIndex > 0 ? (this.int_selectedMaxIndex / int_lastIndex) * 100 : 100;
        var int_leftHiddenOptions = this.getHiddenOptionCount('left');
        var int_rightHiddenOptions = this.getHiddenOptionCount('right');
        var str_minDisplayValue;
        var str_maxDisplayValue;

        if (obj_minGroup !== undefined) {
            str_minDisplayValue = obj_minGroup.str_displayValue;

            if (this.str_unit !== '') {
                str_minDisplayValue += ' ' + this.str_unit;
            }

            this.el_sliderValueMin.set('text', str_minDisplayValue);
            this.el_sliderInputMin.setProperty('aria-valuetext', str_minDisplayValue);
        }

        if (obj_maxGroup !== undefined) {
            str_maxDisplayValue = obj_maxGroup.str_displayValue;

            if (this.str_unit !== '') {
                str_maxDisplayValue += ' ' + this.str_unit;
            }

            this.el_sliderValueMax.set('text', str_maxDisplayValue);
            this.el_sliderInputMax.setProperty('aria-valuetext', str_maxDisplayValue);
        }

        var float_thumbWidthRem = 1.8;
        var float_minFraction = float_minPercent / 100;
        var float_spanFraction = Math.max(float_maxPercent - float_minPercent, 0) / 100;

        this.el_sliderTrackActive.setStyle(
            'left',
            'calc(' + float_minPercent + '% + '
            + (float_thumbWidthRem / 2 - float_minFraction * float_thumbWidthRem)
            + 'rem)'
        );
        this.el_sliderTrackActive.setStyle(
            'width',
            'calc(' + (float_spanFraction * 100) + '% - '
            + (float_spanFraction * float_thumbWidthRem)
            + 'rem)'
        );

        this.updateHintText(this.el_leftHintFull, this.el_leftHintCompact, int_leftHiddenOptions);
        this.updateHintText(this.el_rightHintFull, this.el_rightHintCompact, int_rightHiddenOptions);
        this.scheduleTrackRefresh();
    },

    getHiddenOptionCount: function(str_side) {
        var int_hiddenOptions = 0;

        Array.each(
            this.arr_numericGroups,
            function(obj_group, int_groupIndex) {
                if (str_side === 'left' && int_groupIndex >= this.int_selectedMinIndex) {
                    return;
                }

                if (str_side === 'right' && int_groupIndex <= this.int_selectedMaxIndex) {
                    return;
                }

                Array.each(
                    obj_group.arr_options,
                    function(obj_optionData) {
                        if (
                            typeOf(obj_optionData.el_input) === 'element'
                            && obj_optionData.el_input.getProperty('checked')
                        ) {
                            return;
                        }

                        if (obj_optionData.el_option.hasClass('zero-match-hidden')) {
                            return;
                        }

                        int_hiddenOptions++;
                    }
                );
            }.bind(this)
        );

        return int_hiddenOptions;
    },

    updateHintText: function(el_fullLabel, el_compactLabel, int_hiddenOptions) {
        var str_fullLabel = this.obj_config.str_moreLabel.replace('%s', String(int_hiddenOptions));
        var str_compactLabel = this.obj_config.str_moreLabelCompact.replace('%s', String(int_hiddenOptions));
        var el_hint = el_fullLabel.getParent('.filter-range-slider__hint');

        if (int_hiddenOptions <= 0) {
            el_hint.addClass('is-hidden');
            el_fullLabel.set('text', '');
            el_compactLabel.set('text', '');
            return;
        }

        el_hint.removeClass('is-hidden');
        el_fullLabel.set('text', str_fullLabel);
        el_compactLabel.set('text', str_compactLabel);
    },

    registerVisibilityRefreshHooks: function() {
        window.addEvent(
            'ocFlexOpen',
            function() {
                this.scheduleTrackRefresh();
            }.bind(this)
        );

        if (typeof ResizeObserver === 'function') {
            this.obj_resizeObserver = new ResizeObserver(
                function() {
                    this.refreshTrackMetrics();
                }.bind(this)
            );
            this.obj_resizeObserver.observe(this.__el_container);
            this.obj_resizeObserver.observe(this.el_sliderRoot);
        }
    },

    scheduleTrackRefresh: function() {
        if (this.int_trackRefreshTimer !== null) {
            window.clearTimeout(this.int_trackRefreshTimer);
        }

        this.int_trackRefreshTimer = window.setTimeout(
            function() {
                this.refreshTrackMetrics();
            }.bind(this),
            0
        );
    },

    refreshTrackMetrics: function() {
        var obj_rect;

        if (typeOf(this.el_sliderRoot) !== 'element' || !this.isContainerVisible()) {
            return;
        }

        obj_rect = this.el_sliderRoot.getBoundingClientRect();

        this.el_sliderRoot.setStyle('--filter-range-slider-width', obj_rect.width + 'px');
        this.el_sliderRoot.addClass('is-layout-ready');
    },

    isContainerVisible: function() {
        var obj_rect = this.__el_container.getBoundingClientRect();

        return obj_rect.width > 0 && obj_rect.height > 0;
    }
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();
