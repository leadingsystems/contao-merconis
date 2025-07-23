var obj_classdef_model = {
    name: 'options',

    data: {
        // Not strictly required; if it doesn't exist, it defaults to the top of the document.
        var_topPagination: '.top-pagination',

        // Strictly required as the scroll behavior depends on it.
        var_bottomPagination: '.bottom-pagination',

        // The offset is calculated from the height of the 'var_header' element and the initial offset.
        var_topOffset: '25', // Offset in pixel
        var_header: '#header-part-2'
    },

    start: function() {
    },

    set: function(obj_options) {
        Object.merge(this.data, obj_options);
        this.__module.onModelLoaded();
    }
};