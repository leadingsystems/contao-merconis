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
	},

	setupGuaranteeBrandPrefill: function() {
		if (document._lsShopGuaranteeBrandPrefillInitialized) {
			return;
		}

		document._lsShopGuaranteeBrandPrefillInitialized = true;
		document.addEventListener('focusout', function(event) {
			ls_shop_backend.prefillGuaranteeBrandFromProducer(event);
		}, true);
	},

	prefillGuaranteeBrandFromProducer: function(event) {
		var producerInput = event.target;
		var form;
		var guaranteeBrandInput;
		var producerValue;

		if (!producerInput || producerInput.name != 'lsShopProductProducer') {
			return;
		}

		form = producerInput.form;
		if (!form) {
			return;
		}

		guaranteeBrandInput = form.querySelector('[name="guaranteeBrand"]');
		if (!guaranteeBrandInput || guaranteeBrandInput.value.trim() !== '') {
			return;
		}

		producerValue = producerInput.value.trim();
		if (producerValue === '') {
			return;
		}

		guaranteeBrandInput.value = producerValue;
	},

	setupGuaranteeFieldVisibility: function(root) {
		var scope = root && root.querySelectorAll ? root : document;

		ls_shop_backend.bindGuaranteeFieldVisibility(scope, 'enableGaran', function(form, suffix) {
			return !ls_shop_backend.findGuaranteeFieldInput(form, 'garanOverride', suffix);
		}, true);
		ls_shop_backend.bindGuaranteeFieldVisibility(scope, 'garanOverride', function() {
			return true;
		}, false);
	},

	bindGuaranteeFieldVisibility: function(root, controllerFieldName, shouldBindCallback, shouldMarkMandatory) {
		var controllerInputs = root.querySelectorAll('input[type="checkbox"][name]');
		var i;

		for (i = 0; i < controllerInputs.length; i++) {
			(function(controllerInput) {
				var form = controllerInput.form;
				var suffix;

				if (!form || !ls_shop_backend.isGuaranteeFieldName(controllerInput.name, controllerFieldName)) {
					return;
				}

				suffix = ls_shop_backend.getGuaranteeFieldSuffix(controllerInput.name, controllerFieldName);

				if (!shouldBindCallback(form, suffix)) {
					return;
				}

				if (!controllerInput._lsShopGuaranteeVisibilityBound) {
					controllerInput.addEventListener('change', function() {
						ls_shop_backend.updateGuaranteeFieldVisibility(controllerInput, controllerFieldName, suffix, shouldMarkMandatory);
					});
					controllerInput._lsShopGuaranteeVisibilityBound = true;
				}

				ls_shop_backend.updateGuaranteeFieldVisibility(controllerInput, controllerFieldName, suffix, shouldMarkMandatory);
			})(controllerInputs[i]);
		}
	},

	updateGuaranteeFieldVisibility: function(controllerInput, controllerFieldName, suffix, shouldMarkMandatory) {
		var form = controllerInput.form;
		var guaranteeFieldNames = ['guaranteeDurationYears', 'guaranteeBrand', 'guaranteeModelIdentifier'];
		var isVisible = !!controllerInput.checked;
		var i;

		if (!form) {
			return;
		}

		for (i = 0; i < guaranteeFieldNames.length; i++) {
			var fieldInput = ls_shop_backend.findGuaranteeFieldInput(form, guaranteeFieldNames[i], suffix);
			var widget;

			if (!fieldInput) {
				continue;
			}

			widget = ls_shop_backend.findGuaranteeFieldWidget(fieldInput);

			if (widget) {
				widget.style.display = isVisible ? '' : 'none';
			}

			if (controllerFieldName === 'enableGaran') {
				ls_shop_backend.toggleGuaranteeFieldMandatoryPresentation(
					fieldInput,
					widget,
					shouldMarkMandatory && isVisible
				);
			}
		}
	},

	toggleGuaranteeFieldMandatoryPresentation: function(fieldInput, widget, isRequired) {
		var label;
		var invisibleMarker;
		var mandatoryMarker;

		if (fieldInput) {
			if (isRequired) {
				fieldInput.setAttribute('aria-required', 'true');
			} else {
				fieldInput.removeAttribute('aria-required');
			}
		}

		if (!widget) {
			return;
		}

		label = widget.querySelector('label');

		if (!label || !label.classList) {
			return;
		}

		label.classList.toggle('mandatory', isRequired);
		mandatoryMarker = ls_shop_backend.ensureGuaranteeFieldMandatoryMarker(label, isRequired);
		if (mandatoryMarker) {
			mandatoryMarker.style.display = isRequired ? '' : 'none';
		}

		invisibleMarker = ls_shop_backend.ensureGuaranteeFieldMandatoryAnnouncement(label, isRequired);
		if (invisibleMarker) {
			invisibleMarker.style.display = isRequired ? '' : 'none';
		}
	},

	ensureGuaranteeFieldMandatoryMarker: function(label, shouldCreate) {
		var mandatoryMarker = label.querySelector('span.mandatory');

		if (!mandatoryMarker && shouldCreate) {
			mandatoryMarker = document.createElement('span');
			mandatoryMarker.className = 'mandatory';
			mandatoryMarker.textContent = '*';
			label.appendChild(mandatoryMarker);
		}

		return mandatoryMarker;
	},

	ensureGuaranteeFieldMandatoryAnnouncement: function(label, shouldCreate) {
		var invisibleMarker = label.querySelector('span.invisible');
		var mandatoryLabel;

		if (!invisibleMarker && shouldCreate) {
			mandatoryLabel = ls_shop_backend.getGuaranteeMandatoryLabel();

			if (mandatoryLabel === '') {
				return null;
			}

			invisibleMarker = document.createElement('span');
			invisibleMarker.className = 'invisible';
			invisibleMarker.textContent = mandatoryLabel + ' ';
			label.insertBefore(invisibleMarker, label.firstChild);
		}

		return invisibleMarker;
	},

	getGuaranteeMandatoryLabel: function() {
		if (
			window.ls_shop_backendConfig
			&& typeof window.ls_shop_backendConfig.guaranteeMandatoryLabel === 'string'
		) {
			return window.ls_shop_backendConfig.guaranteeMandatoryLabel;
		}

		return '';
	},

	findGuaranteeFieldInput: function(form, fieldName, suffix) {
		return form.querySelector('[name="' + fieldName + suffix + '"]');
	},

	findGuaranteeFieldWidget: function(fieldInput) {
		var node = fieldInput;

		while (node) {
			if (node.classList && node.classList.contains('widget')) {
				return node;
			}

			node = node.parentNode;
		}

		return null;
	},

	isGuaranteeFieldName: function(fieldName, baseFieldName) {
		return fieldName === baseFieldName
			|| new RegExp('^' + baseFieldName + '_[A-Za-z0-9]+$').test(fieldName);
	},

	getGuaranteeFieldSuffix: function(fieldName, baseFieldName) {
		if (fieldName === baseFieldName) {
			return '';
		}

		return fieldName.substring(baseFieldName.length);
	}
};

window.addEvent('domready', function() {
	ls_shop_backend.addHeaderImageLinkElements();
	ls_shop_backend.makeMerconisWizardsSortable();
	ls_shop_backend.setupGuaranteeBrandPrefill();
	ls_shop_backend.setupGuaranteeFieldVisibility();
});

window.addEvent('ajax_change', function() {
	ls_shop_backend.makeMerconisWizardsSortable();
	ls_shop_backend.setupGuaranteeFieldVisibility();
});
