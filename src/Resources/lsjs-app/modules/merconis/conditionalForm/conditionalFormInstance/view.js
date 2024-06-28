(function() {

// ### ENTER MODULE NAME HERE ######
    var str_moduleName = '__moduleName__';
// #################################

    var obj_classdef = {
        els_allFormFields: null,

        el_checkbox_useDeviantShippingAddress: null,

        start: function() {
            this.registerElements(this.__el_container, 'main');
            this.initializeFunctionality();
        },

        getAllFormFields: function() {
            this.els_allFormFields = new Elements();
            Array.prototype.push.apply(this.els_allFormFields, this.__autoElements.main.formField);
        },

        initializeFunctionality: function() {
            var self = this;

            this.getAllFormFields();

            Array.each(
                self.els_allFormFields,
                function(el_selected) {
                    self.addTriggerEvent(
                        self,
                        el_selected,
                        self.setRequiredFormfield,
                        self.removeRequiredFormfield,
                        "data-required-field",
                        "data-required-value",
                        "data-required-boolean"
                    )
                    self.addTriggerEvent(
                        self,
                        el_selected,
                        self.setRequiredFormfield,
                        self.removeRequiredFormfield,
                        "data-required-field2",
                        "data-required-value2",
                        "data-required-boolean2"
                    )
                    self.addTriggerEvent(
                        self,
                        el_selected,
                        self.showFormfield,
                        self.hideFormfield,
                        "data-showoncondition-field",
                        "data-showoncondition-value",
                        "data-showoncondition-boolean"
                    )
                }
            )
        },

        addTriggerEvent: function(self, el_selected, functionTrue, functionFalse, dataFieldName, dataValueName, dataBoolName){
            if(el_selected.getAttribute(dataFieldName)){

                var dataField = el_selected.getAttribute(dataFieldName);
                var dataValue = el_selected.getAttribute(dataValueName);
                var dataBool = el_selected.getAttribute(dataBoolName);

                //get Element that triggers that current Element
                var el_trigger = null;
                Array.each(
                    self.els_allFormFields,
                    function(element) {
                        if(element.name === dataField){
                            el_trigger = element;
                        }
                    }
                );

                //run one time for each element so this element will be hidden or required if needed
                self.triggerEvent(el_trigger, functionTrue, functionFalse, dataBool, dataValue, el_selected, dataValueName)

                //add event to element that triggers this Event
                el_trigger.addEvent(
                    'change',
                    function () {
                        self.triggerEvent(this, functionTrue, functionFalse, dataBool, dataValue, el_selected, dataValueName)
                    }
                );
            }
        },

        triggerEvent: function(element, functionTrue, functionFalse, dataBool, dataValue, el_selected, dataValueName) {

            var checkbox = 0;
            if(el_selected.getAttribute(dataValueName)){
                checkbox = 1;
            }

            if(
                (element.type === "checkbox" && element.checked && checkbox === 1 ) ||
                (element.type === "checkbox" && !element.checked && checkbox === 0 ) ||
                (element.type !== "checkbox" && element.value === dataValue)
            ){
                if(parseInt(dataBool) === 1){
                    functionFalse(el_selected);
                }else{
                    functionTrue(el_selected);
                }

            } else {
                if(parseInt(dataBool) === 1){
                    functionTrue(el_selected);
                }else{
                    functionFalse(el_selected);
                }

            }
        },

        setRequiredFormfield: function(element) {
            element.setProperty('required', '');
        },

        removeRequiredFormfield: function(element) {
            element.removeProperty('required');
        },

        showFormfield: function(element) {
            if(element.tagName === "SELECT" || element.tagName === "INPUT" ){
                element.parentElement.removeClass('hideElement');
            }else{
                element.removeClass('hideElement');
            }
        },

        hideFormfield: function(element) {
            if(element.tagName === "SELECT" || element.tagName === "INPUT" ){
                element.parentElement.addClass('hideElement');
            }else{
                element.addClass('hideElement');
            }
        },


    };

    lsjs.addViewClass(str_moduleName, obj_classdef);

})();