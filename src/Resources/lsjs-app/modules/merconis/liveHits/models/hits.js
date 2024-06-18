var obj_classdef_model = {
    name: 'hits',

    data: {},

    obj_searchRequest: null,

    start: function() {
        this.__module.onModelLoaded();
    },

    getPossibleHits: function() {
        if (
            this.__models.configuration.data.ls_shop_liveHitsMinLengthSearchTerm > 0
            &&	this.__view.el_input.getProperty('value').length < this.__models.configuration.data.ls_shop_liveHitsMinLengthSearchTerm
        ) {
            return;
        }

        if (this.obj_searchRequest !== null && this.obj_searchRequest.isRunning()) {
            this.obj_searchRequest.cancel();
            this.obj_searchRequest = null;
        }

        this.obj_searchRequest = new Request.JSON({
            url: lsjs.__appHelpers.merconisApp.obj_config.str_ajaxUrl,
            noCache: true,
            method: 'post',
            data: {
                'REQUEST_TOKEN': lsjs.__appHelpers.merconisApp.obj_config.REQUEST_TOKEN,
                'isAjax': 1,
                'requestedClass': 'ModuleProductSearch',
                'action': 'getPossibleHits',
                'searchWord': this.__view.el_input.getProperty('value')
            },
            onComplete: function(objResponse) {
                if (objResponse === undefined || !objResponse.success) {
                    return;
                }
                this.data = objResponse.value;
                this.__view.showHitSelector();
            }.bind(this)
        }).send();
    }
};