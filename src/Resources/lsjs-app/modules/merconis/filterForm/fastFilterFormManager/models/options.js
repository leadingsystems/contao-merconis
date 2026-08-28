var obj_classdef_model = {
    name: 'options',

    data: {},

    start: function() {
        /*
         * Standardwerte für den schnellen Filter. `el_domReference` begrenzt
         * die Initialisierung nach `cajax`-Updates auf den aktualisierten DOM-Bereich.
         */
        this.data = {
            str_containerSelector: '.template_fastFilterForm_default',
            str_reloadElementClass: 'ajax-reload-by-filter',
            el_domReference: null,
            bln_debug: false
        };
    },

    set: function(obj_options) {
        Object.merge(this.data, obj_options);
        this.__module.onModelLoaded();
    }
};
