(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = 	{
    el_input: null,
    el_parentForHitSelector: null,
    el_currentHitSelector: null,

    start: function() {
        this.el_input = typeOf(this.__models.options.data.var_inputField) === 'element' ? this.__models.options.data.var_inputField : $$(this.__models.options.data.var_inputField)[0];
        if (typeOf(this.el_input) !== 'element') {
            console.warn(str_moduleName + ': required input element not found.');
            return;
        }

        this.el_parentForHitSelector = this.el_input.getParent();

        this.el_input.setProperty('autocomplete', 'off');
        this.el_input.addEvent('keyup', this.handleKeyup.bind(this));
        this.el_input.addEvent('focus', this.__models.hits.getPossibleHits.bind(this.__models.hits));
    },

    handleKeyup: function(event) {
        switch (event.key) {
            default:
                this.__models.hits.getPossibleHits();
                break;
        }
    },

    showHitSelector: function() {
        var self = this,
            el_parentForHitSelector;

        /*
         * Close the hit selector if it is already opened
         */
        this.closeHitSelector();

        this.el_currentHitSelector = this.tplAdd({
            parent: this.el_parentForHitSelector,
            name: 'hitselector'
        });
    },

    closeHitSelector: function() {
        if (this.el_currentHitSelector !== null) {
            this.el_currentHitSelector.destroy();
            this.el_currentHitSelector = null;
        }
    }
};

lsjs.addViewClass(str_moduleName, obj_classdef);

})();