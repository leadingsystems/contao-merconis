(function() {
var classdef_ls_x_controller = {
	initialize: function() {
	},
	
	makeLsXwizardsSortable: function() {
		$$('.listWizardMultiValue').each(function(el) {
			new Sortables(el.getElement('.sortable'), {
				contstrain: true,
				opacity: 0.6,
				handle: '.drag-handle'
			});
		});
	},

	listWizardMultiValue: function(el, command, id) {
		var table = $(id);
		var tbody = table.getElement('tbody');
		var parent = $(el).getParent('tr');
		var rows = tbody.getChildren();
		var tabindex = tbody.get('data-tabindex');
		var	input;
		var select;
		var children;
		var a;
		var i;
		var j;

		Backend.getScrollOffset();

		switch (command) {
			case 'copy':
				var tr = new Element('tr');
				var children = parent.getChildren();
				for (i=0; i<children.length; i++) {
					var next = children[i].clone(true).inject(tr, 'bottom');
					if (select = children[i].getFirst('select')) {
						next.getFirst('select').value = select.value;
					}
					next.getElements('input[type=text].waehrung').each( function( el, index ){
						if( el.getStyle( 'display') == 'none' ){
							el.setStyle( 'display', 'inline' ); 
							el.getSiblings('input[type=text].waehrung').dispose();
							haendelWaehrung( el , true );
						}
					});
				}
				tr.inject(parent, 'after');
				
				Array.each(tr.getElements('.chzn-container'), function(el) {
					el.destroy();
				});
				
				Array.each(tr.getElements('select.tl_select'), function(el) {
					new Chosen(el);
				});
				
				Stylect.convertSelects();
				
				this.applyDatepicker();
				break;
				
			case 'up':
				if (tr = parent.getPrevious('tr')) {
					parent.inject(tr, 'before');
				} else {
					parent.inject(tbody, 'bottom');
				}
				break;
				
			case 'down':
				if (tr = parent.getNext('tr')) {
					parent.inject(tr, 'after');
				} else {
					parent.inject(tbody, 'top');
				}
				break;
				
			case 'delete':
				if (rows.length > 1) {
					parent.destroy();
				} else {
					Array.each(parent.getElements('input.tl_text'), function(el) {
						el.setProperty('value', '');
					});
					Array.each(parent.getElements('textarea'), function(el) {
						el.setProperties({
							'value': '',
							'html': ''
						});
					});
				}
				break;
		}

		rows = tbody.getChildren();

		for (i = 0; i < rows.length; i++) {
			children = rows[i].getChildren();
			for (j = 0; j < children.length; j++) {
				Array.each(children[j].getElements('a.chzn-single, input, select, textarea'), function(el) {
					el.setProperty('tabindex', tabindex++);
				});

				Array.each(children[j].getElements('input, select, textarea'), function(el) {
					var str_name = el.getProperty('name');
					if (str_name == undefined || str_name == null) {
						return;
					}
					
					str_name = str_name.replace(/\[[0-9]+\](\[.+\])/g, '[' + i + ']$1');
					el.setProperty('name', str_name);
				});
			}
		}

		new Sortables(tbody, {
			contstrain: true,
			opacity: 0.6,
			handle: '.drag-handle'
		});
	},
	
	applyDatepicker: function() {
		var obj_classesToDatepickerDefinitions = {
			'standard': {
				togglesOnly: false,
				pickerClass: 'datepicker_bootstrap',
				format: '%d.%m.%Y'
			},
			
			'monthYear': {
				startView: 'month',
				onlyView: 'month',
				pickOnly: 'months',
				togglesOnly: false,
				canAlwaysGoUp: ['months'],
				pickerClass: 'datepicker_bootstrap',
				format: '%b %Y'
			},
			
			'year': {
				startView: 'year',
				onlyView: 'year',
				pickOnly: 'years',
				togglesOnly: false,
				canAlwaysGoUp: ['years'],
				pickerClass: 'datepicker_bootstrap',
				format: '%Y'
			}
		};
		
		/*
		 * Enhance the DOM structure around input fields with the class 'ls_x_turnToDatepicker'
		 */
		Array.each($$('input[type=text].ls_x_turnToDatepicker, .ls_x_turnToDatepicker input[type=text]'), function(el_input) {
			/*
			 * Skip fields that are already enclosed in a datepicker box
			 */
			if (el_input.getParent('.ls_x_datepickerBox')) {
				return;
			}
			
			/*
			 * If the input field itself doesn't have the ls_x_turnToDatepicker class we look for the parent element
			 * with this class and look for a type indicator class (e.g. monthYear) that we can apply to the input field
			 */
			if (!el_input.hasClass('ls_x_turnToDatepicker')) {
				var el_turnToDatepickerParent = el_input.getParent('.ls_x_turnToDatepicker');
				if (el_turnToDatepickerParent != undefined && el_turnToDatepickerParent != null) {
					Object.each(obj_classesToDatepickerDefinitions, function(obj_definition, str_definitionKey) {
						if (el_turnToDatepickerParent.hasClass(str_definitionKey)) {
							el_input.addClass(str_definitionKey);
						}
					})
				}
			}
			
			var el_datepickerBox = new Element('div.ls_x_datepickerBox');
			el_datepickerBox.wraps(el_input);
			//@TODO please check if change from gif to svg is ok
			el_datepickerBox.adopt(
				new Element('img.toggler')
					.setProperties({
						'width': 20,
						'height': 20,
						'src': 'assets/mootools/datepicker/2.0.0/icon.svg'
					})
					.setStyles({
						'vertical-align': '-6px',
						'cursor': 'pointer'
					})
			)
		});
		
		Array.each($$('.ls_x_datepickerBox.'), function(el) {
			if (el.retrieve('bln_alreadyAppliedDatepicker', false)) {
				return;
			} else {
				el.store('bln_alreadyAppliedDatepicker', true);
			}
			
			var el_input = null,
				el_toggler = null,
				obj_definitionToUse = null;
			
			el_input = el.getElement('input[type=text]');
			el_toggler = el.getElement('.toggler');
			
			if (
					el_input == undefined || el_input == null
				||	el_toggler == undefined || el_toggler == null
			) {
				return;
			}
			
			Object.each(obj_classesToDatepickerDefinitions, function(obj_definition, str_definitionKey) {
				if (obj_definitionToUse != null) {
					return;
				}
				if (el_input.hasClass(str_definitionKey)) {
					obj_definitionToUse = Object.clone(obj_definition);
				}
			});
			
			if (obj_definitionToUse == null) {
				obj_definitionToUse = Object.clone(obj_classesToDatepickerDefinitions['standard']);
			}
						
			obj_definitionToUse.toggle = el_toggler;
			
			new Picker.Date(el_input, obj_definitionToUse);
		});		
	}
};

var class_ls_x_controller = new Class(classdef_ls_x_controller);

window.addEvent('domready', function() {
	ls_x_controller = new class_ls_x_controller();
	ls_x_controller.makeLsXwizardsSortable();
	ls_x_controller.applyDatepicker();
});

window.addEvent('ajax_change', function() {
	ls_x_controller.makeLsXwizardsSortable();
});
})();