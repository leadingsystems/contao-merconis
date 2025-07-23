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
            // Wenn es keine obere Paginierung gibt, scrollen wir einfach ganz nach oben.
            el_topPagination = $$('body')[0];
        }

        var el_header = document.querySelector(var_header);
        var headerHeight = 0;

        el_bottomPagination.getElements('a').addEvent(
            'click',
            function(e) {
                e.preventDefault(); // Verhindert, dass der Link tatsächlich navigiert (falls href="#" als Fallback wenn kein js vorhanden ist oder sonst was)

                // getBoundingClientRect().top gibt die Position relativ zum sichtbaren Fenster (Viewport) an, window.scrollY ist, wie weit bereits gescrollt wurde
                var elementTopPosition = el_topPagination.getBoundingClientRect().top + window.scrollY;

                let int_headerHeight = 0
                if (el_header) {
                    int_headerHeight = parseInt(el_header.offsetHeight)
                }

                let int_topOffset = parseInt(var_topOffset) + int_headerHeight;

                var int_scrollTargetPositionY = elementTopPosition - int_topOffset;


                //sollte man schon über dem element sein wird nicht gescrollt
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