(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
    start: function() {
        Array.each(
            this.getFieldContainers(),
            function(el_container) {
                if (!el_container.retrieve('alreadyHandledBy_' + str_moduleName)) {
                    el_container.store('alreadyHandledBy_' + str_moduleName, true);
                } else {
                    return;
                }

                lsjs.createModule({
                    __name: 'filterRangeSliderInstance',
                    __parentModule: this.__module,
                    __el_container: el_container
                });
            }.bind(this)
        );
    },

    getFieldContainers: function() {
        var arr_containers = [];

        Array.each(
            this.getDomReferences(),
            function(el_domReference) {
                if (typeOf(el_domReference) !== 'element') {
                    return;
                }

                if (this.matchesFieldSelector(el_domReference)) {
                    arr_containers.push(el_domReference);
                }

                Array.each(
                    el_domReference.getElements(this.__models.options.data.str_selector),
                    function(el_candidate) {
                        if (!arr_containers.contains(el_candidate)) {
                            arr_containers.push(el_candidate);
                        }
                    }
                );
            }.bind(this)
        );

        return new Elements(arr_containers);
    },

    getDomReferences: function() {
        var var_domReference = this.__models.options.data.el_domReference;

        if (typeOf(var_domReference) === 'element') {
            return new Elements([var_domReference]);
        }

        if (typeOf(var_domReference) === 'elements') {
            return var_domReference;
        }

        if (typeOf(var_domReference) === 'array') {
            return new Elements(var_domReference);
        }

        return $$('body');
    },

    matchesFieldSelector: function(el_candidate) {
        return typeOf(el_candidate) === 'element'
            && typeof el_candidate.match === 'function'
            && el_candidate.match(this.__models.options.data.str_selector);
    }
};

lsjs.addControllerClass(str_moduleName, obj_classdef);

lsjs.__moduleHelpers[str_moduleName] = {
    self: null,
    obj_sliderStates: {},

    ensureSliderStateNamespace: function() {
        if (lsjs.__moduleHelpers.filterRangeSlider === undefined) {
            lsjs.__moduleHelpers.filterRangeSlider = {};
        }

        if (lsjs.__moduleHelpers.filterRangeSlider.obj_sliderStates === undefined) {
            lsjs.__moduleHelpers.filterRangeSlider.obj_sliderStates = this.obj_sliderStates;
        }

        this.obj_sliderStates = lsjs.__moduleHelpers.filterRangeSlider.obj_sliderStates;

        return this.obj_sliderStates;
    },

    start: function(obj_options) {
        this.ensureSliderStateNamespace();
        this.self = lsjs.createModule({
            __name: str_moduleName
        });
        this.self.__models.options.set(obj_options);
    }
};

lsjs.__moduleHelpers[str_moduleName].ensureSliderStateNamespace();

})();
