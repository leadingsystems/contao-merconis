(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
    el_input: null,
    el_parentForHitSelector: null,
    el_currentHitSelector: null,

    start: function() {

        console.log("start bottompage")
        console.log(this.__models.options.data)

        this.setBottomPaginationScrollBehaviour(
            this.__models.options.data.el_domReference,
            this.__models.options.data.var_bottom_pagination,
            this.__models.options.data.var_top_pagination
        )

    },

    setBottomPaginationScrollBehaviour: function(el_domReference, var_bottom_pagination, var_top_pagination) {
        var el_bottomPagination = el_domReference.getElement(var_bottom_pagination);
        if (typeOf(el_bottomPagination) !== 'element') {
            return;
        }

        var el_topPagination = el_domReference.getElement(var_top_pagination);
        if (typeOf(el_topPagination) !== 'element') {
            return;
        }

        var obj_scroll = new Fx.Scroll($$('body')[0]);

        var int_scrollTargetPositionY = el_topPagination.getPosition().y - (window.innerHeight / 2);
        if (int_scrollTargetPositionY < 0) {
            int_scrollTargetPositionY = 0;
        }

        el_bottomPagination.getElements('a').addEvent(
            'click',
            function() {
                if (window.scrollY < int_scrollTargetPositionY) {
                    /*
                     * If the current scroll position is already closer to the top than the target scroll position,
                     * we don't scroll.
                     */
                    return;
                }
                obj_scroll.start(0, int_scrollTargetPositionY);
            }
        )
    }

};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();