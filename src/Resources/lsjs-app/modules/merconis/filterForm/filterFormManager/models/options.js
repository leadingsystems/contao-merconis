var obj_classdef_model = {
    name: 'options',

    data: {},

    start: function() {
        /*
         * Initializing the options in the data object with default values which
         * can later be overwritten when the "set" method is called with other options
         */
        this.data = {
            str_containerSelector: '.template_filterForm_default',

            /*
             * "true" if the filter fields should use a folding effect
             */
            bln_useFolding: true,

            /*
             * If the filter form is displayed in an ocFlex container an instance name can be provided
             * here so that submitting the form can close the ocFlex container.
             */
            str_ocFlexInstanceName: '',

            bln_doNotUseAjax: false
        };
    },

    set: function(obj_options) {
        Object.merge(this.data, obj_options);
        this.__module.onModelLoaded();
    }
};