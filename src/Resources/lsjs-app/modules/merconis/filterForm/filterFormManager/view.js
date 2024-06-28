(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
    el_filterForm: null,
    el_btnReset: null,
    el_summary: null,
    el_headlineContainer: null,
    el_placeholderForFilterFieldName: null,
    obj_filterOptionsBoxes: {},
    bln_currentlySubmitting: false,
    bln_doNotToggleOcFlexOnNextSubmit: false,

    start: function() {
        this.__el_container = $$(this.__models.options.data.str_containerSelector)[0];

        if (typeOf(this.__el_container) !== 'element') {
            console.warn('__el_container is not an element');
            return;
        }

        /* ->
         * Make sure not to handle the filter form more than once
         */
        if (!this.__el_container.retrieve('alreadyHandledBy_' + str_moduleName)) {
            this.__el_container.store('alreadyHandledBy_' + str_moduleName, true);
        } else {
            return;
        }
        /*
         * <-
         */

        if (typeOf(this.__el_container) !== 'element') {
            console.warn('__el_container is not an element');
            return;
        }

        if (!this.initializeFilterForm()) {
            return;
        }

        this.initializeFilterHeadline();
        this.initializeFilterSummary();

        window.addEvent(
            'ocFlexClose',
            this.reactOnClosingOcFlex.bind(this)
        );
    },

    initializeFilterForm: function() {
        var self = this;

        this.el_filterForm = this.__el_container.getElement('form');
        if (typeOf(this.el_filterForm) !== 'element') {
            console.warn('could not find filter form element');
            return false;
        }

        this.el_btnReset = this.el_filterForm.getElement('.resetFilter');

        this.el_placeholderForFilterFieldName = this.__el_container.getElement('.placeholder-for-filter-field-name');

        this.__autoElements = {};
        this.registerElements(this.__el_container, 'main', true);

        this.prepareFilterOptionBoxes();

        lsjs.helpers.prepareFormForCajaxRequest(this.el_filterForm);

        if (!this.__models.options.data.bln_doNotUseAjax) {
            this.el_filterForm.addEvent('submit', function(event) {
                if (event !== undefined && event !== null) {
                    event.stop();
                }

                self.bln_currentlySubmitting = true;

                lsjs.loadingIndicator.__controller.show();

                if (
                    self.__models.options.data.str_ocFlexInstanceName !== ''
                    && lsjs.__moduleHelpers.ocFlex.self[self.__models.options.data.str_ocFlexInstanceName] !== undefined
                ) {
                    if (self.bln_doNotToggleOcFlexOnNextSubmit) {
                        self.bln_doNotToggleOcFlexOnNextSubmit = false;
                    } else {
                        lsjs.__moduleHelpers.ocFlex.self[self.__models.options.data.str_ocFlexInstanceName].__view.toggle();
                    }
                }

                new Request.cajax({
                    url: this.getProperty('action'),
                    method: 'post',
                    noCache: true,
                    cajaxMode: 'updateCompletely',
                    el_formToUseForFormData: this,
                    obj_additionalFormData: {
                        'cajaxRequestData[requestedElementClass]': 'ajax-reload-by-filter'
                    },

                    // data: this.toQueryString() + '&cajaxRequestData[requestedElementClass]=ajax-reload-by-configurator_' + self.int_productVariantId,

                    onComplete: function() {
                        lsjs.loadingIndicator.__controller.hide();
                        self.initializeFilterForm();
                        self.initializeFilterHeadline();
                        self.initializeFilterSummary();
                        self.bln_currentlySubmitting = false;
                    }
                }).send();
            });
        }

        return true;
    },

    initializeFilterHeadline: function() {
        var self = this;
        this.el_headlineContainer = this.__el_container.getElement('#filter-headline-container');
        if (typeOf(this.el_headlineContainer) !== 'element') {
            return;
        }

        this.el_headlineContainer.getElements('.off-canvas-filter-form-toggler-remote').addEvent(
            'click',
            function() {
                if (
                    self.__models.options.data.str_ocFlexInstanceName !== ''
                    && lsjs.__moduleHelpers.ocFlex.self[self.__models.options.data.str_ocFlexInstanceName] !== undefined
                ) {
                    lsjs.__moduleHelpers.ocFlex.self[self.__models.options.data.str_ocFlexInstanceName].__view.toggle();
                    self.displayWholeFilterForm();
                }
            }
        );
    },

    initializeFilterSummary: function() {
        var self = this;
        this.el_summary = this.__el_container.getElement('.filter-summary-container');
        if (typeOf(this.el_summary) !== 'element') {
            return;
        }

        this.el_summary.getElements('.off-canvas-filter-form-toggler-remote').addEvent(
            'click',
            function() {
                if (
                    self.__models.options.data.str_ocFlexInstanceName !== ''
                    && lsjs.__moduleHelpers.ocFlex.self[self.__models.options.data.str_ocFlexInstanceName] !== undefined
                ) {
                    lsjs.__moduleHelpers.ocFlex.self[self.__models.options.data.str_ocFlexInstanceName].__view.toggle();
                    self.displayOnlyRelevantPartOfFilterForm(this.getParent('[data-lsjs-filter-section-id]').getProperty('data-lsjs-filter-section-id'));
                }
            }
        );

        this.el_summary.getElements('.reset-this-criterion').addEvent(
            'click',
            function() {
                self.obj_filterOptionsBoxes[this.getParent('[data-lsjs-filter-section-id]').getProperty('data-lsjs-filter-section-id')].__view.clear();
                self.bln_doNotToggleOcFlexOnNextSubmit = true;
                if (!self.__models.options.data.bln_doNotUseAjax) {
                    self.el_filterForm.fireEvent('submit');
                } else{
                    self.el_filterForm.submit();
                }
            }
        );
    },

    displayOnlyRelevantPartOfFilterForm: function(str_filterSectionId) {
        this.el_filterForm.getElements('[data-lsjs-filter-section-id]').removeClass('filter-section-hidden');
        this.el_filterForm.getElements('[data-lsjs-filter-section-id]:not([data-lsjs-filter-section-id="' + str_filterSectionId + '"])').addClass('filter-section-hidden');
        this.__el_container.addClass('partial-filter-form-display');
        var el_filterFieldLabel = this.el_filterForm.getElement('[data-lsjs-filter-section-id][data-lsjs-filter-section-id="' + str_filterSectionId + '"]').getElement('.label');
        this.el_placeholderForFilterFieldName.setProperty('html', el_filterFieldLabel.getProperty('html'));
        el_filterFieldLabel.addClass('hide');
        this.obj_filterOptionsBoxes[str_filterSectionId].__view.obj_unfold.__view.instantlyOpen();
    },

    displayWholeFilterForm: function() {
        Array.each(
            this.el_filterForm.getElements('[data-lsjs-filter-section-id]'),
            function(el_section) {
                el_section.removeClass('filter-section-hidden');
                var str_filterSectionId = el_section.getProperty('data-lsjs-filter-section-id');
                el_section.getElement('.label').removeClass('hide');
                this.el_placeholderForFilterFieldName.setProperty('html', '');

                var el_correspondingSummaryField = this.el_summary.getElement('[data-lsjs-filter-section-id="' + str_filterSectionId + '"]');

                if (!el_correspondingSummaryField.hasClass('currently-filtering')) {
                    this.obj_filterOptionsBoxes[str_filterSectionId].__view.obj_unfold.__view.instantlyClose();
                } else {
                    this.obj_filterOptionsBoxes[str_filterSectionId].__view.obj_unfold.__view.instantlyOpen();
                }
            }.bind(this)
        );
        this.__el_container.removeClass('partial-filter-form-display');
    },

    prepareFilterOptionBoxes: function() {
        if (this.__autoElements.main.filterOptionsBox !== undefined) {
            Array.each(this.__autoElements.main.filterOptionsBox, function(el_filterOptionsBox) {
                this.obj_filterOptionsBoxes[el_filterOptionsBox.getProperty('data-lsjs-filter-section-id')] = lsjs.createModule({
                    __name: 'filterFormOptionsBox',
                    __parentModule: this.__module,
                    __el_container: el_filterOptionsBox
                });
            }.bind(this));
        }

        if (this.__autoElements.main.filterRangeBox !== undefined) {
            Array.each(this.__autoElements.main.filterRangeBox, function(el_filterRangeBox) {
                this.obj_filterOptionsBoxes[el_filterRangeBox.getProperty('data-lsjs-filter-section-id')] = lsjs.createModule({
                    __name: 'filterFormOptionsBox',
                    __parentModule: this.__module,
                    __el_container: el_filterRangeBox
                });
            }.bind(this));
        }

        if (this.__autoElements.main.filterPriceBox !== undefined) {
            Array.each(this.__autoElements.main.filterPriceBox, function(el_filterPriceBox) {
                this.obj_filterOptionsBoxes[el_filterPriceBox.getProperty('data-lsjs-filter-section-id')] = lsjs.createModule({
                    __name: 'filterFormOptionsBox',
                    __parentModule: this.__module,
                    __el_container: el_filterPriceBox
                });
            }.bind(this));
        }
    },

    reactOnClosingOcFlex: function(str_ocFlexInstanceName) {
        if (!this.bln_currentlySubmitting) {
            this.el_filterForm.reset();
        }
    }
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();