(function() {




// ### ENTER MODULE NAME HERE ######

    var str_moduleName = '__moduleName__';

// #################################




    var obj_classdef = 	{

        el_form: null,

        el_btn_addRow: null,

        el_btn_sendData: null,

        el_requestOutput: null,

        el_responseOutput: null,

        el_apiUrl: null,

        str_apiUrl: '',

        int_numRows: 1,

        int_highestTabindex: 0,

        str_httpRequestMethod: 'post',

        str_absoluteRequestUrl: '',

        str_responseType: '',

        bln_expectsMultipleDataRows: false,




        start: function() {

            this.initializeForm();

            this.initializeAddRowButton();

        },




        addRow: function() {

            this.int_numRows++;

            Array.each(

                this.__el_container.getElements('.widget input:last-child'),

                function(el) {

                    var el_clone = el.clone();

                    this.int_highestTabindex++;

                    el_clone.setProperty('tabindex', this.int_highestTabindex);

                    el_clone.inject(el,'after');

                }.bind(this)

            );

        },




        initializeAddRowButton: function() {

            this.el_btn_addRow = this.__el_container.getElement('button.addRow');




            if (typeOf(this.el_btn_addRow) !== 'element') {

                console.log('add row button not found');

                return;

            }




            if (!this.bln_expectsMultipleDataRows) {

                this.el_btn_addRow.setStyle('display', 'none');

                return;

            }




            Array.each(

                this.__el_container.getElements('.widget input:last-child'),

                function(el) {

                    this.int_highestTabindex = el.getProperty('tabindex');

                }.bind(this)

            );




            this.el_btn_addRow.addEvent(

                'click',

                this.addRow.bind(this)

            );

        },




        initializeForm: function() {

            var self = this;




            this.el_form = typeOf(this.__el_container.getElement('form')) === 'element' ? this.__el_container.getElement('form') : this.__el_container;

            this.el_btn_sendData = this.__el_container.getElement('button.sendData');

            this.el_apiUrl = this.__el_container.getElement('input[name="APIURL"]');

            this.el_requestOutput = this.__el_container.getElement('.requestOutput .output');

            this.el_responseOutput = this.__el_container.getElement('.responseOutput .output');

            this.str_httpRequestMethod = this.__el_container.getProperty('data-merconis-http-request-method');

            this.str_absoluteRequestUrl = this.__el_container.getProperty('data-merconis-absolute-request-url');

            this.str_responseType = this.__el_container.getProperty('data-merconis-response-type');

            this.bln_expectsMultipleDataRows = this.__el_container.getProperty('data-merconis-expects-multiple-data-rows') == 1 ? true : false;




            if (typeOf(this.el_form) !== 'element') {

                console.log('no form element found');

                return;

            }




            if (typeOf(this.el_apiUrl) !== 'element') {

                console.log('no APIURL element found');

                return;

            }




            if (typeOf(this.el_requestOutput) !== 'element') {

                console.log('no request output element found');

                return;

            }




            if (typeOf(this.el_responseOutput) !== 'element') {

                console.log('no response output element found');

                return;

            }




            if (typeOf(this.el_btn_sendData) !== 'element') {

                console.log('no send data button element found');

                return;

            }




            this.str_apiUrl = this.el_apiUrl.getProperty('value');




            if (!this.str_apiUrl) {

                console.log('api url could not be determined');

                return;

            }




            this.el_btn_sendData.addEvent('click', function (event) {

                self.performRequest();

            });




            this.el_form.addEvent('submit', function (event) {

                event.stop();

            });

        },




        performRequest: function() {

            var self = this,

                arr_dataCollection,

                json_data,

                obj_requestParameter = {};




            lsjs.loadingIndicator.__controller.show();




            arr_dataCollection = this.createDataCollection();




            json_data = JSON.stringify(this.bln_expectsMultipleDataRows ? arr_dataCollection : arr_dataCollection[0], null, "\t");




            this.el_requestOutput.setProperty(

                'html',

                this.str_httpRequestMethod === 'post' ? ('data=' + json_data) : (this.str_absoluteRequestUrl + '?data=' + JSON.stringify(this.bln_expectsMultipleDataRows ? arr_dataCollection : arr_dataCollection[0], null, ""))

            );




            obj_requestParameter = {

                url: this.str_apiUrl,

                noCache: true,

                method: this.str_httpRequestMethod,

                data: {

                    'data': json_data

                },

                onComplete: function(obj_response) {

                    lsjs.loadingIndicator.__controller.hide();

                    this.el_responseOutput.setProperty('html', JSON.stringify(obj_response, null, "\t"));

                    console.log(obj_response);

                }.bind(this)

            };




            if (this.str_responseType === 'json') {

                new Request.JSON(obj_requestParameter).send();

            } else {

                new Request(obj_requestParameter).send();

            }

        },




        createDataCollection: function() {

            var int_rowNumber,

                els_inputs,

                obj_dataRow = {},

                arr_dataCollection = [];




            for (var int_rowNumber = 1; int_rowNumber <= this.int_numRows; int_rowNumber++) {

                /*

                 * Since the label is the first child element inside the widget container, we have to add 1 to the row number

                 * to get the correct input fields with the nth-child selector.

                 */

                els_inputs = this.__el_container.getElements('.widget input:nth-child(' + (int_rowNumber + 1) + ')');

                Array.each(

                    els_inputs,

                    function(el_input) {

                        obj_dataRow[el_input.getProperty('name')] = el_input.getProperty('value');

                    }

                )

                arr_dataCollection.push(Object.clone(obj_dataRow));

            }




            return arr_dataCollection;

        }

    };




    lsjs.addViewClass(str_moduleName, obj_classdef);




})();