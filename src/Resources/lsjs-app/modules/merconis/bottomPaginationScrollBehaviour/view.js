(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
    el_input: null,
    el_parentForHitSelector: null,
    el_currentHitSelector: null,

    start: function() {

        this.setBottomPaginationScrollBehaviour(
            this.__models.options.data.el_domReference,
            this.__models.options.data.var_topPagination,
            this.__models.options.data.var_bottomPagination,
            this.__models.options.data.var_topOffset,
            this.__models.options.data.var_header
        )
    },

    setBottomPaginationScrollBehaviour: function(el_domReference, var_topPagination, var_bottomPagination, var_topOffset, var_header) {
        var el_bottomPagination = el_domReference.getElement(var_bottomPagination);
        if (typeOf(el_bottomPagination) !== 'element') {
            return;
        }

        var el_topPagination = el_domReference.getElement(var_topPagination);
        if (typeOf(el_topPagination) !== 'element') {
            // If no top pagination exists, just scroll to the top.
            el_topPagination = $$('body')[0];
        }

        var el_header = document.querySelector(var_header);
        var headerHeight = 0;

        el_bottomPagination.getElements('a').addEvent(
            'click',
            function(e) {
                e.preventDefault(); // Prevent the default anchor behavior (e.g., for href="#" as a no-js fallback).

                // getBoundingClientRect().top gives the position relative to what's currently visible (the viewport), while window.scrollY is the total amount scrolled from the very top of the page.
                var elementTopPosition = el_topPagination.getBoundingClientRect().top + window.scrollY;

                let int_headerHeight = 0
                if (el_header) {
                    int_headerHeight = parseInt(el_header.offsetHeight)
                }

                let int_topOffset = parseInt(var_topOffset) + int_headerHeight;

                var int_scrollTargetPositionY = elementTopPosition - int_topOffset;


                // Don't scroll if already above the element.
                if (window.scrollY < int_scrollTargetPositionY + 1) {
                    return;
                }

                window.scrollTo({
                    top: int_scrollTargetPositionY,
                    behavior: 'smooth'
                });
            }
        )
    }

};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();