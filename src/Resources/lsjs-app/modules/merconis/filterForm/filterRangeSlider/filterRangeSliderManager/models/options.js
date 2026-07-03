var obj_classdef_model = {
    name: 'options',

    data: {},

    start: function() {
        this.data = {
            el_domReference: null,
            str_selector: '[data-filter-display-mode="sliderRange"],[data-filter-display-mode="sliderDirect"]'
        };
    },

    set: function(obj_options) {
        Object.merge(this.data, obj_options);
        this.__module.onModelLoaded();
    }
};
