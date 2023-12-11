var ls_shop_productSelection = {
	loadProduct: function(productID, targetContainer) {
		if (typeof(targetContainer) != 'object') {
			targetContainer = $(targetContainer);
		}
		new Request({
			url: window.location.href,
			data: 'REQUEST_TOKEN=' + REQUEST_TOKEN + '&isAjax=1&action=ls_shop_productSelection::loadProduct&productID=' + productID,
			onRequest: AjaxRequest.displayBox('Loading data …'),
			onComplete: function (txt, xml) {
				var objResponse = JSON.decode(txt);
				targetContainer.set('html', objResponse.html);
				AjaxRequest.hideBox();
			}
		}).send();
		
		return false;
	},
	
	selectProduct: function(productID) {
		if (parent.document.currentlyActiveWidgetElement != undefined) {
			var elInput = parent.document.currentlyActiveWidgetElement.getElement('input');
			var elSelectedProductOutput = parent.document.currentlyActiveWidgetElement.getElement('.selectedProductOutput');
			
			elInput.setProperty('value', productID);
			parent.ls_shop_productSelection.loadProduct(productID, elSelectedProductOutput);
			
			/*
			 * close the modal iframe with a simulated click on the background overlay
			 * because either there's no easy-to-use function to close the modal iframe
			 * from a click within or we just couldn't find it.
			 */
			parent.$('simple-modal-overlay').fireEvent('click');
		}
	},
	
	setCurrentlyActiveWidgetElement: function(el) {
		document.currentlyActiveWidgetElement = el;
	}
};

var ls_shop_backend = {
	updateAttributeValuesWidget: function(elCaller, attributeID) {
		new Request({
			url: window.location.href,
			data: 'REQUEST_TOKEN=' + Contao.request_token + '&isAjax=1&action=ls_shop_loadCorrespondingAttributeValuesAsOptions&attributeID=' + attributeID,
			onRequest: AjaxRequest.displayBox('Loading data …'),
			onComplete: function (txt, xml) {
				var objResponse = JSON.decode(txt);
				elTargetSelectField = elCaller.getParent('tr').getElement('.attributeValuesField');
				elTargetSelectField.empty();
				Object.each(objResponse.attributeValuesOptions, function(value, key) {
					var elOption = new Element('option').setProperties({'value': value.value, 'html': value.label});
					elTargetSelectField.adopt(elOption);
				});
				
				elTargetSelectField.fireEvent('liszt:updated');
				AjaxRequest.hideBox();
			}
		}).send();
		
		return false;
	},
	
	listWizard: function(el, command, id) {
		var list = $(id);
		var parent = $(el).getParent('li');
		var items = list.getChildren();
		Backend.getScrollOffset();

		switch (command) {
			case 'copy':
				var clone = parent.clone(true).inject(parent, 'before');
				if (input = parent.getFirst('input')) {
					clone.getFirst('input').value = input.value;
				}
				break;
			case 'up':
				if (previous = parent.getPrevious('li')) {
					parent.inject(previous, 'before');
				} else {
					parent.inject(list, 'bottom');
				}
				break;
			case 'down':
				if (next = parent.getNext('li')) {
					parent.inject(next, 'after');
				} else {
					parent.inject(list.getFirst('li'), 'before');
				}
				break;
			case 'delete':
				if (items.length > 1) {
					parent.destroy();
				} else if (items.length == 1) {
					parent.getElements('input').each(function(value, key) {
						value.setProperty('value', '');
						value.fireEvent('change');
					});
					if (parent.getElement('.selectedProductOutput') != undefined) {
						parent.getElement('.selectedProductOutput').set('html', '');
					}
				}
				break;
		}

		rows = list.getChildren();
		var tabindex = 1;

		for (var i=0; i<rows.length; i++) {
			if (input = rows[i].getFirst('input[type="text"]')) {
				input.set('tabindex', tabindex++);
			}
		}
	},
	
	divWizard: function(el, parentClass, command, id) {
		var list = $(id);
		var parent = $(el).getParent('.' + parentClass);
		var items = list.getChildren();
		Backend.getScrollOffset();

		switch (command) {
			case 'copy':
				var clone = parent.clone(true).inject(parent, 'before');
				if (input = parent.getFirst('input')) {
					clone.getFirst('input').value = input.value;
				}
				break;
			case 'up':
				if (previous = parent.getPrevious('.' + parentClass)) {
					parent.inject(previous, 'before');
				} else {
					parent.inject(list, 'bottom');
				}
				break;
			case 'down':
				if (next = parent.getNext('.' + parentClass)) {
					parent.inject(next, 'after');
				} else {
					parent.inject(list.getFirst('.' + parentClass), 'before');
				}
				break;
			case 'delete':
				if (items.length > 1) {
					parent.destroy();
				} else if (items.length == 1) {
					parent.getElements('input').each(function(value, key) {
						value.setProperty('value', '');
						value.fireEvent('change');
					});
					parent.getElements('textarea').each(function(value, key) {
						value.setProperty('value', '');
						value.fireEvent('change');
					});
					if (parent.getElement('.selectedProductOutput') != undefined) {
						parent.getElement('.selectedProductOutput').set('html', '');
					}
				}
				break;
		}

		rows = list.getChildren();
		var tabindex = 1;

		for (var i=0; i<rows.length; i++) {
			if (input = rows[i].getFirst('input[type="text"]')) {
				input.set('tabindex', tabindex++);
			}
		}
	},
	
	/**
	 * Open the value picker wizard in a modal iframe
	 * @param string
	 */
	pickValue: function(id,requestedTable,requestedValue,pickerHeadline) {
		var width = 320;
		var height = 112;

		Backend.currentId = id;
		Backend.ppValue = $(id).value;

		Backend.getScrollOffset();
		Backend.openModalIframe({'width':765,'title':pickerHeadline,'url': 'contao/merconis/value?requestedTable=' + requestedTable + '&requestedValue=' + requestedValue + '&value=' + Backend.ppValue,'id':id});
	},
	
	toggleLsShopMainLanguagePagetree: function (el, id, field, name, level) {
		el.blur();
		var item = $(id);
		var image = $(el).getFirst('img');

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', 'inline');
				image.src = image.src.replace('folPlus.svg', 'folMinus.svg');
				$(el).title = CONTAO_COLLAPSE;
				new Request.Contao({field:el}).post({'action':'toggleLsShopMainLanguagePagetree', 'id':id, 'state':1, 'REQUEST_TOKEN':REQUEST_TOKEN});
			} else {
				item.setStyle('display', 'none');
				image.src = image.src.replace('folMinus.svg', 'folPlus.svg');
				$(el).title = CONTAO_EXPAND;
				new Request.Contao({field:el}).post({'action':'toggleLsShopMainLanguagePagetree', 'id':id, 'state':0, 'REQUEST_TOKEN':REQUEST_TOKEN});
			}
			return false;
		}

		new Request.Contao({
			field: el,
			evalScripts: true,
			onRequest: AjaxRequest.displayBox(CONTAO_LOADING + ' …'),
			onSuccess: function(txt, json) {
				var li = new Element('li', {
					'id': id,
					'class': 'parent',
					'styles': {
						'display': 'inline'
					}
				});

				var ul = new Element('ul', {
					'class': 'level_' + level,
					'html': txt
				}).inject(li, 'bottom');

				li.inject($(el).getParent('li'), 'after');
				$(el).title = CONTAO_COLLAPSE;
				image.src = image.src.replace('folPlus.svg', 'folMinus.svg');
				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('ajax_change');
   			}
		}).post({'action':'loadLsShopMainLanguagePagetree', 'id':id, 'level':level, 'field':field, 'name':name, 'state':1, 'REQUEST_TOKEN':REQUEST_TOKEN});

		return false;
	},
	
	sendOrderMessage: function(elToUpdate, messageTypeID, orderID) {
		elToUpdate.getElement('.messageIcon').adopt(
			new Element('span.loadingOverlay')
		);
		
		new Request.HTML({
			url: window.location.href,
			noCache: true,
			data: 'REQUEST_TOKEN=' + Contao.request_token + '&isAjax=1&action=sendOrderMessage&messageTypeID=' + messageTypeID + '&orderID=' + orderID,
			update: elToUpdate
		}).send();
		
		return false;
	},
	
	addHeaderImageLinkElements: function () {
		var elHeader = $$('#main h1.main_headline')[0];
		if (elHeader == undefined) {
			return;
		}
		var paddingBottom = elHeader.getStyle('padding-bottom');
		paddingBottom = parseInt(paddingBottom.substring(0,paddingBottom.length - 2));
		if (paddingBottom > 100) {
			var elLink1 = new Element('a', {id: 'merconisLink', href: 'http://www.merconis.com', target: '_blank'});
			elLink1.addEvent('click', function() {
				this.blur();
			});
			var elLink2 = new Element('a', {id: 'merconisForumLink', href: 'http://www.merconisforum.com', target: '_blank'});
			elLink2.addEvent('click', function() {
				this.blur();
			});
			elHeader.adopt(elLink1);
			elHeader.adopt(elLink2);
		}
	},
	
	makeMerconisWizardsSortable: function() {
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
				}
				tr.inject(parent, 'after');
				
				Array.each(tr.getElements('.chzn-container'), function(el) {
					el.destroy();
				});
				
				Array.each(tr.getElements('select.tl_select'), function(el) {
					new Chosen(el);
				});
				
				Stylect.convertSelects();
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
				if (a = children[j].getFirst('a.chzn-single')) {
					a.set('tabindex', tabindex++);
				}
				if (select = children[j].getFirst('')) {
					select.name = select.name.replace(/\[[0-9]+\](\[[0-9]+\])/g, '[' + i + ']$1');
				}
			}
		}

		new Sortables(tbody, {
			contstrain: true,
			opacity: 0.6,
			handle: '.drag-handle'
		});
	}
};

window.addEvent('domready', function() {
	ls_shop_backend.addHeaderImageLinkElements();
	ls_shop_backend.makeMerconisWizardsSortable();
});

window.addEvent('ajax_change', function() {
	ls_shop_backend.makeMerconisWizardsSortable();
});
