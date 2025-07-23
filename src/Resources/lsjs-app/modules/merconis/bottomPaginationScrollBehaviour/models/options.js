var obj_classdef_model = {
    name: 'options',

    data: {
        var_bottom_pagination: '.bottom-pagination',
        var_top_pagination: '.top-pagination'
    },

    start: function() {
    },

    set: function(obj_options) {
        Object.merge(this.data, obj_options);
        this.__module.onModelLoaded();
    }
};