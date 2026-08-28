(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
    bln_currentlySubmitting: false,

    start: function() {
        var els_containers = this.getContainers();

        Array.each(
            els_containers,
            function(el_container) {
                this.initializeContainer(el_container);
            }.bind(this)
        );
    },

    getContainers: function() {
        var arr_containers = [];
        var els_domReferences = this.getDomReferences();

        Array.each(
            els_domReferences,
            function(el_domReference) {
                this.addContainerFromCandidate(arr_containers, el_domReference);
                this.addContainerFromCandidate(
                    arr_containers,
                    el_domReference.getParent('[data-fast-filter-root]')
                );

                Array.each(
                    el_domReference.getElements(this.__models.options.data.str_containerSelector),
                    function(el_candidate) {
                        this.addContainerFromCandidate(arr_containers, el_candidate);
                    }.bind(this)
                );
            }.bind(this)
        );

        return new Elements(arr_containers);
    },

    getDomReferences: function() {
        var var_domReference = this.__models.options.data.el_domReference;

        if (typeOf(var_domReference) === 'element') {
            return new Elements([var_domReference]);
        }

        if (typeOf(var_domReference) === 'elements') {
            return var_domReference;
        }

        if (typeOf(var_domReference) === 'array') {
            return new Elements(var_domReference);
        }

        return $$('body');
    },

    addContainerFromCandidate: function(arr_containers, el_candidate) {
        var el_container = this.getContainerFromCandidate(el_candidate);

        if (typeOf(el_container) !== 'element') {
            return;
        }

        if (!arr_containers.contains(el_container)) {
            arr_containers.push(el_container);
        }
    },

    getContainerFromCandidate: function(el_candidate) {
        if (typeOf(el_candidate) !== 'element') {
            return null;
        }

        if (el_candidate.getProperty('data-fast-filter-root') !== null) {
            return el_candidate;
        }

        var el_parentRoot = el_candidate.getParent('[data-fast-filter-root]');
        if (typeOf(el_parentRoot) === 'element') {
            return el_parentRoot;
        }

        if (
            typeof el_candidate.match === 'function'
            && el_candidate.match(this.__models.options.data.str_containerSelector)
        ) {
            return el_candidate;
        }

        return null;
    },

    initializeContainer: function(el_container) {
        if (typeOf(el_container) !== 'element') {
            return;
        }

        this.prepareFields(el_container);
        this.prepareForm(el_container);
        this.updateResetButtonState(el_container);
    },

    prepareForm: function(el_container) {
        var el_filterForm = el_container.getElement('[data-fast-filter-form]');

        if (typeOf(el_filterForm) !== 'element') {
            return;
        }

        if (el_filterForm.retrieve('alreadyHandledBy_' + str_moduleName)) {
            return;
        }

        el_filterForm.store('alreadyHandledBy_' + str_moduleName, true);

        if (
            lsjs.helpers !== undefined
            && lsjs.helpers.prepareFormForCajaxRequest !== undefined
        ) {
            lsjs.helpers.prepareFormForCajaxRequest(el_filterForm);
        }

        this.prepareResetButton(el_container, el_filterForm);

        el_filterForm.addEvent(
            'submit',
            function(event) {
                if (event !== undefined && event !== null) {
                    event.stop();
                }

                this.submitForm(el_filterForm);
            }.bind(this)
        );
    },

    prepareResetButton: function(el_container, el_filterForm) {
        var els_resetButtons = el_container.getElements('[name="resetFastFilter"]');

        if (!els_resetButtons.length) {
            return;
        }

        Array.each(
            els_resetButtons,
            function(el_resetButton) {
                if (el_resetButton.retrieve('alreadyHandledBy_' + str_moduleName)) {
                    return;
                }

                el_resetButton.store('alreadyHandledBy_' + str_moduleName, true);
                el_resetButton.addEvent(
                    'click',
                    function(event) {
                        if (el_resetButton.hasClass('is-disabled') || el_resetButton.getProperty('disabled')) {
                            if (event !== undefined && event !== null) {
                                event.stop();
                            }

                            return;
                        }

                        el_filterForm.store('fastFilterResetRequested', true);
                        this.clearFieldValues(el_container);

                        if (event !== undefined && event !== null) {
                            event.stop();
                        }

                        this.submitForm(el_filterForm);
                    }.bind(this)
                );
            }.bind(this)
        );
    },

    updateResetButtonState: function(el_container) {
        if (typeOf(el_container) !== 'element') {
            return;
        }

        var bln_hasActiveCriteria = this.hasActiveFilterCriteria(el_container);

        Array.each(
            el_container.getElements('[name="resetFastFilter"]'),
            function(el_resetButton) {
                this.setResetButtonState(el_resetButton, bln_hasActiveCriteria);
            }.bind(this)
        );
    },

    setResetButtonState: function(el_resetButton, bln_isEnabled) {
        if (typeOf(el_resetButton) !== 'element') {
            return;
        }

        if (bln_isEnabled) {
            el_resetButton.removeClass('is-disabled');
            el_resetButton.removeProperty('disabled');
            el_resetButton.setProperty('aria-disabled', 'false');
            return;
        }

        el_resetButton.addClass('is-disabled');
        el_resetButton.setProperty('disabled', 'disabled');
        el_resetButton.setProperty('aria-disabled', 'true');
    },

    hasActiveFilterCriteria: function(el_container) {
        var bln_hasActiveCriteria = false;

        if (typeOf(el_container) !== 'element') {
            return false;
        }

        Array.each(
            el_container.getElements('[data-fast-filter-field] input'),
            function(el_input) {
                if (['checkbox', 'radio'].includes(el_input.getProperty('type')) && el_input.getProperty('checked')) {
                    bln_hasActiveCriteria = true;
                }
            }
        );

        return bln_hasActiveCriteria;
    },

    prepareFields: function(el_container) {
        Array.each(
            el_container.getElements('[data-fast-filter-field]'),
            function(el_field) {
                this.prepareField(el_field);
            }.bind(this)
        );
    },

    prepareField: function(el_field) {
        if (el_field.retrieve('alreadyHandledBy_' + str_moduleName)) {
            this.refreshFieldState(el_field);
            this.applyFieldOptionVisibility(el_field);
            return;
        }

        el_field.store('alreadyHandledBy_' + str_moduleName, true);
        el_field.store('fastFilterFieldId', el_field.getProperty('data-filter-field-id'));
        el_field.store('fastFilterSource', el_field.getProperty('data-source'));
        el_field.store('fastFilterSourceAttribute', el_field.getProperty('data-source-attribute'));

        Array.each(
            el_field.getElements('[data-fast-filter-option]'),
            function(el_option) {
                this.prepareOption(el_option, el_field);
            }.bind(this)
        );

        this.prepareFieldFolding(el_field);
        this.prepareShowMoreLess(el_field);
        this.prepareCheckAllToggle(el_field);
        this.prepareUncheckRadioToggle(el_field);
        this.applyFieldOptionVisibility(el_field);
        this.refreshFieldState(el_field);
    },

    prepareOption: function(el_option, el_field) {
        var el_input = el_option.getElement('input');
        var str_numericValue = el_option.getProperty('data-numeric-value');

        el_option.store('fastFilterOptionValue', el_option.getProperty('data-option-value'));
        el_option.store('fastFilterNumericValue', str_numericValue);

        if (str_numericValue !== null && str_numericValue !== '') {
            el_option.addClass('has-numeric-value');
            el_field.addClass('has-numeric-options');
        }

        this.refreshOptionState(el_option);

        if (typeOf(el_input) !== 'element' || el_input.retrieve('alreadyHandledBy_' + str_moduleName)) {
            return;
        }

        el_input.store('alreadyHandledBy_' + str_moduleName, true);
        el_input.addEvent(
            'change',
            function() {
                this.refreshOptionState(el_option);
                this.refreshFieldState(el_field);
                this.applyFieldOptionVisibility(el_field);
                this.updateResetButtonState(el_field.getParent('[data-fast-filter-root]'));
                this.queueAutoSubmit(this.getFilterFormForElement(el_field));
            }.bind(this)
        );
    },

    prepareFieldFolding: function(el_field) {
        var el_label = el_field.getElement('[data-fast-filter-field-label]');
        var el_content = el_field.getElement('[data-fast-filter-field-content]');
        var obj_fieldState = this.getFieldUiState(el_field);
        var obj_unfold = null;

        if (
            typeOf(el_label) !== 'element'
            || typeOf(el_content) !== 'element'
            || lsjs.__moduleHelpers.unfold === undefined
        ) {
            return;
        }

        obj_unfold = lsjs.__moduleHelpers.unfold.start({
            str_initialToggleStatus: obj_fieldState.str_foldingStatus === 'closed' ? 'closed' : 'open',
            var_togglerSelector: el_label,
            var_contentBoxSelector: el_content,
            var_wrapperSelector: el_field,
            obj_morphOptions: {
                duration: 600,
                onComplete: function() {
                    if (
                        obj_unfold !== null
                        && obj_unfold.__view !== undefined
                        && obj_unfold.__view.str_toggleStatus !== undefined
                    ) {
                        obj_fieldState.str_foldingStatus = obj_unfold.__view.str_toggleStatus;
                    }
                }
            }
        });
    },

    prepareShowMoreLess: function(el_field) {
        var el_showMoreLess = el_field.getElement('[data-fast-filter-show-more-less]');

        if (typeOf(el_showMoreLess) !== 'element') {
            return;
        }

        if (!el_showMoreLess.retrieve('alreadyHandledBy_' + str_moduleName)) {
            el_showMoreLess.store('alreadyHandledBy_' + str_moduleName, true);
            el_showMoreLess.addEvent(
                'click',
                function(event) {
                    var obj_fieldState = this.getFieldUiState(el_field);

                    if (event !== undefined && event !== null) {
                        event.stop();
                    }

                    if (el_showMoreLess.hasClass('is-disabled')) {
                        return;
                    }

                    obj_fieldState.bln_showAllOptions = !obj_fieldState.bln_showAllOptions;
                    this.applyFieldOptionVisibility(el_field);
                }.bind(this)
            );
        }
    },

    prepareCheckAllToggle: function(el_field) {
        var el_checkAll = el_field.getElement('[data-fast-filter-check-all]');

        if (typeOf(el_checkAll) !== 'element') {
            return;
        }

        if (el_checkAll.retrieve('alreadyHandledBy_' + str_moduleName)) {
            return;
        }

        el_checkAll.store('alreadyHandledBy_' + str_moduleName, true);
        el_checkAll.addEvent(
            'click',
            function(event) {
                var els_relevantInputs = this.getVisibleOptionInputs(el_field);
                var int_checkedInputs = 0;
                var bln_newCheckedStatus = false;
                var bln_changed = false;

                if (event !== undefined && event !== null) {
                    event.stop();
                }

                Array.each(
                    els_relevantInputs,
                    function(el_input) {
                        if (el_input.getProperty('checked')) {
                            int_checkedInputs++;
                        }
                    }
                );

                bln_newCheckedStatus = !(int_checkedInputs > els_relevantInputs.length / 2);

                Array.each(
                    els_relevantInputs,
                    function(el_input) {
                        if (el_input.getProperty('checked') !== bln_newCheckedStatus) {
                            bln_changed = true;
                        }

                        el_input.setProperty('checked', bln_newCheckedStatus);
                        this.refreshOptionState(el_input.getParent('[data-fast-filter-option]'));
                    }.bind(this)
                );

                this.refreshFieldState(el_field);
                this.applyFieldOptionVisibility(el_field);
                this.updateResetButtonState(el_field.getParent('[data-fast-filter-root]'));

                if (bln_changed) {
                    this.queueAutoSubmit(this.getFilterFormForElement(el_field));
                }
            }.bind(this)
        );
    },

    prepareUncheckRadioToggle: function(el_field) {
        var el_uncheckRadio = el_field.getElement('[data-fast-filter-uncheck-radio]');

        if (typeOf(el_uncheckRadio) !== 'element') {
            return;
        }

        if (el_uncheckRadio.retrieve('alreadyHandledBy_' + str_moduleName)) {
            return;
        }

        el_uncheckRadio.store('alreadyHandledBy_' + str_moduleName, true);
        el_uncheckRadio.addEvent(
            'click',
            function(event) {
                var bln_changed = false;

                if (event !== undefined && event !== null) {
                    event.stop();
                }

                Array.each(
                    el_field.getElements('[data-fast-filter-option] input[type="radio"]'),
                    function(el_input) {
                        if (el_input.getProperty('checked')) {
                            bln_changed = true;
                        }

                        el_input.setProperty('checked', false);
                        this.refreshOptionState(el_input.getParent('[data-fast-filter-option]'));
                    }.bind(this)
                );

                this.refreshFieldState(el_field);
                this.applyFieldOptionVisibility(el_field);
                this.updateResetButtonState(el_field.getParent('[data-fast-filter-root]'));

                if (bln_changed) {
                    this.queueAutoSubmit(this.getFilterFormForElement(el_field));
                }
            }.bind(this)
        );
    },

    getFilterFormForElement: function(el_element) {
        if (typeOf(el_element) !== 'element') {
            return null;
        }

        if (el_element.getProperty('data-fast-filter-form') !== null) {
            return el_element;
        }

        return el_element.getParent('[data-fast-filter-form]');
    },

    isAutoSubmitEnabledForForm: function(el_filterForm) {
        if (typeOf(el_filterForm) !== 'element') {
            return false;
        }

        var el_container = el_filterForm.getParent('[data-fast-filter-root]');

        return typeOf(el_container) === 'element'
            && el_container.getProperty('data-fast-filter-auto-submit') === '1';
    },

    queueAutoSubmit: function(el_filterForm) {
        if (!this.isAutoSubmitEnabledForForm(el_filterForm)) {
            return;
        }

        var int_existingTimeout = el_filterForm.retrieve('fastFilterAutoSubmitTimeout');
        if (int_existingTimeout) {
            window.clearTimeout(int_existingTimeout);
        }

        el_filterForm.store(
            'fastFilterAutoSubmitTimeout',
            window.setTimeout(
                function() {
                    el_filterForm.store('fastFilterAutoSubmitTimeout', null);
                    this.submitForm(el_filterForm);
                }.bind(this),
                150
            )
        );
    },

    applyFieldOptionVisibility: function(el_field) {
        var obj_fieldState = this.getFieldUiState(el_field);
        var el_showMoreLess = el_field.getElement('[data-fast-filter-show-more-less]');
        var els_options = el_field.getElements('[data-fast-filter-option]');
        var int_limit = this.getFieldVisibleLimit(el_field, els_options);
        var int_defaultHideableOptions = 0;
        var int_currentlyHideableOptions = 0;
        var bln_hideZeroMatches = this.isHideZeroMatchesEnabled(el_field);
        var int_availableOptionIndex = 0;

        Array.each(
            els_options,
            function(el_option, index) {
                var int_optionIndexForVisibility = index;

                el_option.removeClass('hidden');
                el_option.removeClass('show-more-hidden');
                el_option.removeClass('zero-match-hidden');

                if (bln_hideZeroMatches && this.isZeroMatchOption(el_option)) {
                    el_option.addClass('hidden');
                    el_option.addClass('zero-match-hidden');
                    return;
                }

                if (bln_hideZeroMatches) {
                    int_optionIndexForVisibility = int_availableOptionIndex;
                    int_availableOptionIndex++;
                }

                if (this.isOptionHideableByDefault(el_option, int_optionIndexForVisibility, int_limit)) {
                    int_defaultHideableOptions++;
                }

                if (this.isOptionHideable(el_option, int_optionIndexForVisibility, int_limit)) {
                    int_currentlyHideableOptions++;
                }
            }.bind(this)
        );

        if (typeOf(el_showMoreLess) !== 'element') {
            this.updateEmptyFieldMessage(el_field);
            return;
        }

        if (int_defaultHideableOptions <= 0) {
            el_showMoreLess.addClass('hidden');
            el_showMoreLess.removeClass('show-more-active');
            el_showMoreLess.removeClass('currentlyHiding');
            el_showMoreLess.removeClass('currentlyShowing');
            el_showMoreLess.removeClass('is-disabled');
            obj_fieldState.bln_showAllOptions = false;
            this.updateShowMoreLessLabel(el_showMoreLess, false);
            this.updateEmptyFieldMessage(el_field);
            return;
        }

        el_showMoreLess.removeClass('hidden');
        el_showMoreLess.addClass('show-more-active');
        el_showMoreLess.removeClass('is-disabled');
        el_showMoreLess.setProperty('aria-disabled', 'false');

        if (obj_fieldState.bln_showAllOptions) {
            el_showMoreLess.removeClass('currentlyHiding');
            el_showMoreLess.addClass('currentlyShowing');
            this.updateShowMoreLessLabel(el_showMoreLess, true);

            if (int_currentlyHideableOptions <= 0) {
                el_showMoreLess.addClass('is-disabled');
                el_showMoreLess.setProperty('aria-disabled', 'true');
            }

            this.updateEmptyFieldMessage(el_field);
            return;
        }

        int_availableOptionIndex = 0;

        Array.each(
            els_options,
            function(el_option, index) {
                var int_optionIndexForVisibility = index;

                if (el_option.hasClass('zero-match-hidden')) {
                    return;
                }

                if (bln_hideZeroMatches) {
                    int_optionIndexForVisibility = int_availableOptionIndex;
                    int_availableOptionIndex++;
                }

                if (!this.isOptionHideable(el_option, int_optionIndexForVisibility, int_limit)) {
                    return;
                }

                el_option.addClass('hidden');
                el_option.addClass('show-more-hidden');
            }.bind(this)
        );

        el_showMoreLess.addClass('currentlyHiding');
        el_showMoreLess.removeClass('currentlyShowing');
        this.updateShowMoreLessLabel(el_showMoreLess, false);
        this.updateEmptyFieldMessage(el_field);
    },

    isHideZeroMatchesEnabled: function(el_field) {
        if (typeOf(el_field) !== 'element') {
            return false;
        }

        var el_container = el_field.getParent('[data-fast-filter-root]');

        return typeOf(el_container) === 'element'
            && el_container.getProperty('data-fast-filter-hide-zero-matches') === '1';
    },

    isZeroMatchOption: function(el_option) {
        var str_matchEstimate = typeOf(el_option) === 'element'
            ? el_option.getProperty('data-match-estimate')
            : null;
        var int_matchEstimate = parseInt(str_matchEstimate || '', 10);

        return !isNaN(int_matchEstimate) && int_matchEstimate === 0;
    },

    updateEmptyFieldMessage: function(el_field) {
        var el_emptyFieldMessage = el_field.getElement('[data-fast-filter-empty-field-message]');
        var bln_hasAvailableOption = false;

        if (typeOf(el_emptyFieldMessage) !== 'element') {
            return;
        }

        if (!this.isHideZeroMatchesEnabled(el_field)) {
            el_field.removeClass('has-no-visible-fast-filter-options');
            el_emptyFieldMessage.addClass('hidden');
            return;
        }

        Array.each(
            el_field.getElements('[data-fast-filter-option]'),
            function(el_option) {
                if (!el_option.hasClass('zero-match-hidden')) {
                    bln_hasAvailableOption = true;
                }
            }
        );

        if (bln_hasAvailableOption) {
            el_field.removeClass('has-no-visible-fast-filter-options');
            el_emptyFieldMessage.addClass('hidden');
            return;
        }

        el_field.addClass('has-no-visible-fast-filter-options');
        el_emptyFieldMessage.removeClass('hidden');
    },

    getFieldVisibleLimit: function(el_field, els_options) {
        var int_configuredLimit = parseInt(el_field.getProperty('data-reduced-mode-limit') || '0', 10);

        if (int_configuredLimit > 0) {
            return int_configuredLimit;
        }

        var int_initiallyVisible = 0;
        Array.each(els_options, function(el_option) {
            if (el_option.hasClass('initially-visible') || el_option.hasClass('important')) {
                int_initiallyVisible++;
            }
        });

        if (int_initiallyVisible > 0) {
            return int_initiallyVisible;
        }

        var int_defaultLimit = 12;
        return els_options.length > int_defaultLimit ? int_defaultLimit : els_options.length;
    },

    isOptionHideable: function(el_option, int_index, int_limit) {
        var el_input = el_option.getElement('input');

        if (typeOf(el_input) === 'element' && el_input.getProperty('checked')) {
            return false;
        }

        return this.isOptionHideableByDefault(el_option, int_index, int_limit);
    },

    isOptionHideableByDefault: function(el_option, int_index, int_limit) {
        if (el_option.hasClass('initially-visible') || el_option.hasClass('important')) {
            return false;
        }

        return int_index >= int_limit;
    },

    updateShowMoreLessLabel: function(el_showMoreLess, bln_showLess) {
        var str_label = bln_showLess
            ? el_showMoreLess.getProperty('data-label-less')
            : el_showMoreLess.getProperty('data-label-more');

        el_showMoreLess.set('text', str_label);
        el_showMoreLess.setProperty('aria-expanded', bln_showLess ? 'true' : 'false');
    },

    getVisibleOptionInputs: function(el_field) {
        var els_inputs = new Elements();

        Array.each(
            el_field.getElements('[data-fast-filter-option]'),
            function(el_option) {
                var el_input = el_option.getElement('input');

                if (
                    typeOf(el_input) !== 'element'
                    || el_input.getProperty('disabled')
                    || el_option.hasClass('hidden')
                    || el_option.hasClass('show-more-hidden')
                    || el_option.hasClass('range-hidden')
                ) {
                    return;
                }

                els_inputs.push(el_input);
            }
        );

        return els_inputs;
    },

    getFieldUiState: function(el_field) {
        var str_fieldId = el_field.retrieve('fastFilterFieldId') || el_field.getProperty('data-filter-field-id') || 'unknown';
        var obj_states = this.getStoredFieldUiStates();

        if (obj_states[str_fieldId] === undefined) {
            obj_states[str_fieldId] = {
                str_foldingStatus: 'open',
                bln_showAllOptions: false
            };
        }

        return obj_states[str_fieldId];
    },

    getStoredFieldUiStates: function() {
        if (lsjs.__moduleHelpers[str_moduleName].obj_fieldUiStates === undefined) {
            lsjs.__moduleHelpers[str_moduleName].obj_fieldUiStates = {};
        }

        return lsjs.__moduleHelpers[str_moduleName].obj_fieldUiStates;
    },

    refreshOptionState: function(el_option) {
        if (typeOf(el_option) !== 'element') {
            return;
        }

        var el_input = el_option.getElement('input');

        if (typeOf(el_input) !== 'element') {
            return;
        }

        if (el_input.getProperty('checked')) {
            el_option.addClass('is-checked');
        } else {
            el_option.removeClass('is-checked');
        }
    },

    refreshFieldState: function(el_field) {
        var bln_hasActiveOption = false;

        Array.each(
            el_field.getElements('[data-fast-filter-option] input'),
            function(el_input) {
                if (el_input.getProperty('checked')) {
                    bln_hasActiveOption = true;
                }
            }
        );

        if (bln_hasActiveOption) {
            el_field.addClass('has-active-options');
        } else {
            el_field.removeClass('has-active-options');
        }
    },

    clearFieldValues: function(el_container) {
        Array.each(
            el_container.getElements('[data-fast-filter-field] input'),
            function(el_input) {
                if (['checkbox', 'radio'].includes(el_input.getProperty('type'))) {
                    el_input.setProperty('checked', false);
                    this.refreshOptionState(el_input.getParent('[data-fast-filter-option]'));
                }
            }.bind(this)
        );

        this.prepareFields(el_container);
        this.updateResetButtonState(el_container);
    },

    submitForm: function(el_filterForm) {
        var obj_additionalFormData = {};

        if (this.bln_currentlySubmitting) {
            return;
        }

        this.bln_currentlySubmitting = true;
        obj_additionalFormData['cajaxRequestData[requestedElementClass]'] = this.__models.options.data.str_reloadElementClass;

        if (el_filterForm.retrieve('fastFilterResetRequested')) {
            obj_additionalFormData.resetFastFilter = '1';
        }

        el_filterForm.store('fastFilterResetRequested', false);

        this.showLoadingIndicator();

        new Request.cajax({
            url: el_filterForm.getProperty('action') || window.location.href,
            method: 'post',
            noCache: true,
            cajaxMode: 'updateCompletely',
            el_formToUseForFormData: el_filterForm,
            obj_additionalFormData: obj_additionalFormData,

            onComplete: function() {
                this.bln_currentlySubmitting = false;
                this.hideLoadingIndicator();
            }.bind(this),

            onFailure: function() {
                this.bln_currentlySubmitting = false;
                this.hideLoadingIndicator();
            }.bind(this)
        }).send();
    },

    showLoadingIndicator: function() {
        if (
            lsjs.loadingIndicator !== undefined
            && lsjs.loadingIndicator.__controller !== undefined
        ) {
            lsjs.loadingIndicator.__controller.show();
        }
    },

    hideLoadingIndicator: function() {
        if (
            lsjs.loadingIndicator !== undefined
            && lsjs.loadingIndicator.__controller !== undefined
        ) {
            lsjs.loadingIndicator.__controller.hide();
        }
    }
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();
