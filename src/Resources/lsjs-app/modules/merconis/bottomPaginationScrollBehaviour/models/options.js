var obj_classdef_model = {
    name: 'options',

    data: {
        //wird nicht zwingend benötigt, springt falls es nicht exestiert zum top des documents
        var_topPagination: '.top-pagination',

        //wird zwingend benötigt da darauf das scrollverhalten liegt
        var_bottomPagination: '.bottom-pagination',

        //offset wird berechnet aus der größe des elements var_header und dem offset
        var_topOffset: '25', //offset in pixel
        var_header: '#header-part-2'
    },

    start: function() {
    },

    set: function(obj_options) {
        Object.merge(this.data, obj_options);
        this.__module.onModelLoaded();
    }
};