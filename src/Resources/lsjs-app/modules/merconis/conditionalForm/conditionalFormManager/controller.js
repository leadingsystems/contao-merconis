/*
 * -- ACTIVATION: --
 *
 * To activate this module, the following code has to be put in the app.js:
 *
     lsjs.__moduleHelpers.conditionalFormManager.start({
         el_domReference: el_domReference
     });
 *
 * The el_domReference parameter is only required if this module initialization code is called in a cajax_domUpdate event.
 *
 *
 *
 * -- FUNCTIONALITY AND USAGE: --
 *
 * This module's purpose is to dynamically control whether a form field should be displayed or hidden or whether it
 * should be mandatory based on another field's value. In Merconis it is by default being used to display all fields
 * for a deviant shipping address only when a checkbox, indicating that a deviant shipping address should be used,
 * is checked and to only display select fields which offer provinces only when in another select field a country where
 * provinces are relevant, is selected.
 *
 * Add the following attribute to a DOM element to apply this module:
 * data-lsjs-component="conditionalForm"
 *
 * In the Contao backend Merconis adds specific controls to configure form fields to be visible or mandatory based
 * on other fields.
 *
 */

(function() {

// ### ENTER MODULE NAME HERE ######
var str_moduleName = '__moduleName__';
// #################################

var obj_classdef = {
    start: function() {
        var els_toEnhance;
        /*
         * Look for elements to enrich with the lsjs-module and then
         * instantiate instances for each element found.
         */
        if (this.__models.options.data.el_domReference !== undefined && typeOf(this.__models.options.data.el_domReference) === 'element') {
            els_toEnhance = this.__models.options.data.el_domReference.getElements(this.__models.options.data.str_selector);
        } else {
            els_toEnhance = $$(this.__models.options.data.str_selectors);
        }

        Array.each(els_toEnhance, function(el_container) {
            /* ->
             * Make sure not to handle an element more than once
             */
            if (!el_container.retrieve('alreadyHandledBy_' + str_moduleName)) {
                el_container.store('alreadyHandledBy_' + str_moduleName, true);
            } else {
                return;
            }
            /*
             * <-
             */

            el_container.addClass(this.__models.options.data.str_classToSetWhenModuleApplied);

            lsjs.createModule({
                __name: 'conditionalFormInstance',
                __parentModule: this.__module,
                __useLoadingIndicator: false,
                __el_container: el_container
            });
        }.bind(this));
    }
};

lsjs.addControllerClass(str_moduleName, obj_classdef);

lsjs.__moduleHelpers[str_moduleName] = {
    self: null,

    start: function(obj_options) {
        this.self = lsjs.createModule({
            __name: str_moduleName
        });
        this.self.__models.options.set(obj_options);
    }
};

})();