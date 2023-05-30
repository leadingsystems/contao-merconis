<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['title']										= array('Bezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['alias']	= array('Alias', 'Eindeutige Bezeichnung, welche zur Referenzierung verwendet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['description']								= array('Beschreibung');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['infoAfterCheckout']							= array('Information nach Bestellabschluss');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['additionalInfo']								= array('Zusätzliche Information');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['formAdditionalData']							= array('Formular für Kundeneingaben');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['dynamicSteuersatzType']						= array('Dynamischer Steuersatz', 'Wählen Sie, auf welche Art der Steuersatz ggf. dynamisch ausgewählt wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['steuersatz']									= array('Steuersatz', 'Wählen Sie hier den Steuersatz aus, mit dem ggf. anfallende Kosten für diese Versandoption besteuert werden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type']										= array('Art der Versandoption','Wählen Sie hier die Art der Versandoption aus. Die meisten Versandoptionen lassen sich mit der Auswahl "Standard" realisieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['published']									= array('Veröffentlicht','Setzen Sie dieses Häkchen, um diese Versandoption im Shop zur Auswahl anzubieten.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['sorting']									= array('Sortierzahl');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeType']									= array('Art der Kostenberechnung','Bestimmen Sie hier, wie die Kosten berechnet werden');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeValue']									= array('Preis');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeAddCouponToValueOfGoods']				= array('Gutscheine in Warenwert einbeziehen', 'Wird bei der Berechnung der Warenwert als Grundlage genommen, so kann mit dieser Checkbox bestimmt werden, ob Gutschein-Werte in den Warenwert mit einbezogen werden sollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeFormula']									= array('Formel zur Gebührenberechnung', 'Bitte geben Sie als Dezimaltrennzeichen stets einen Punkt ein. Verfügbare Platzhalter: ##totalValueOfGoods##, ##totalWeightOfGoods##, ##totalValueOfCoupons##. Neben klassischen Berechnungen ist auch die Verwendung ternärer Operatoren möglich, sodass folgendes Beispiel funktioniert: ##totalValueOfGoods## > 300 ? 0 : (##totalWeightOfGoods## <= 10 ? 10 : (##totalWeightOfGoods## <= 20 ? 20 : (##totalWeightOfGoods## <= 30 ? 30 : 40)))');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeFormulaResultConvertToDisplayPrice']		= array('Errechnete Gebühr in Ausgabepreis umwandeln', 'Wählen Sie diese Option, wenn das Ergebnis Ihrer Berechnung ein Netto- oder Bruttopreis entsprechend der definierten Grundeinstellung zur Preiserfassung ist, damit der Preis aus Sicht des Kunden korrekt ausgegeben wird. Wählen Sie diese Option nicht, wenn Sie eine Berechnung z. B. auf Basis des Warenwert-Platzhalters durchführen, da das Ergebnis Ihrer Berechnung dann bereits den aus Kundensicht richtigen Ausgabepreis darstellt.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeWeightValues']							= array('Preis nach Gewicht (links: bis zu welchem Gewicht, rechts: welcher Preis)','Definieren Sie hier, bis zu welchem Gewicht welcher Festbetrag für den Versand berechnet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feePriceValues']								= array('Preis nach Warenwert (links: bis zu welchem Warenwert, rechts: welcher Preis)','Definieren Sie hier, bis zu welchem Warenwert welcher Festbetrag für den Versand berechnet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['excludedGroups']								= array('Auszuschließende Gruppen', 'Wählen Sie hier die Gruppen aus, für die diese Versandoption nicht zur Verfügung stehen soll.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimitMin']								= array('Mindestgewicht', 'Tragen Sie hier das Mindestgewicht für die Lieferung ein, ab der diese Versandoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimitMax']								= array('Maximalgewicht', 'Tragen Sie hier das Maximalgewicht für die Lieferung ein, bis zu der diese Versandoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitMin']								= array('Mindestwarenwert', 'Tragen Sie hier den Mindestwarenwert ein, ab dem diese Versandoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitMax']								= array('Maximalwarenwert', 'Tragen Sie hier den Maximalwarenwert ein, bis zu dem diese Versandoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimitAddCouponToValueOfGoods']			= array('Gutscheine in Warenwert einbeziehen', 'Mit dieser Checkbox kann bestimmt werden, ob bei der Prüfung des Mindest- bzw. Maximalwarenwerts Gutschein-Werte in den Warenwert mit einbezogen werden sollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countries']									= array('Länder-Auswahl', 'Bitte geben Sie die Länder als kommagetrennte Liste an.');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countriesAsBlacklist']						= array('Länder-Auswahl als Blacklist interpretieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['cssID']										= array('CSS-ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['cssClass']									= array('CSS-Klasse');
    $GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['notSelectable']									= array('Nicht auswählbar');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['title_legend']			= 'Bezeichnung';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['steuersatz_legend']	= 'Steuersatz';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['afterCheckout_legend']			= 'Nach Bestellabschluss';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type_legend']			= 'Art der Versandoption';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['published_legend']		= 'Veröffentlichen';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['fee_legend']				= 'Kostenberechnung';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['excludedGroups_legend']	= 'Gruppenbezogene Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['weightLimit_legend']		= 'Gewichtsbeschränkungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['priceLimit_legend']		= 'Beschränkungen des Warenwerts';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['countryLimit_legend']		= 'Beschränkungen der erlaubten Länder';
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['misc_legend']		= 'Sonstiges';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['new']        = array('Neue Versandoption', 'Eine neue Versandoption anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['edit']        = array('Versandoption bearbeiten', 'Versandoption ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['delete']        = array('Versandoption löschen', 'Versandoption ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['copy']        = array('Versandoption kopieren', 'Versandoption ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['show']        = array('Details anzeigen', 'Details der Versandoption ID %s anzeigen');
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['feeType']['options'] = array(
		'none' => array('Keine Berechnung (kostenlos)'),
		'fixed' => array('Festbetrag', 'Wählen Sie diese Option, wenn Sie die Kosten als Festbetrag definieren möchten.'),
		'percentaged' => array('Prozentual', 'Wählen Sie diese Option, wenn Sie die Kosten als prozentualen Wert definieren möchten.'),
		'weight' => array('nach Gewicht', 'Wählen Sie diese Option, wenn Sie die Kosten als Festbetrag abhängig vom Gesamtgewicht der bestellten Ware definieren möchten.'),
		'price' => array('nach Warenwert', 'Wählen Sie diese Option, wenn Sie die Kosten als Festbetrag abhängig vom Wert der bestellten Ware definieren möchten. Diese Einstellung ist z. B. für versicherten Versand sinnvoll.'),
		'weightAndPrice' => array('nach Gewicht und Warenwert', 'Wählen Sie diese Option, wenn Sie die Kosten als Festbetrag abhängig vom Gewicht und dem Wert der bestellten Ware definieren möchten. Bitte beachten Sie, dass die Kosten, die Sie im Folgenden nach Gewicht und Preis angeben, entsprechend addiert werden. Diese Einstellung ist z. B. für versicherten Versand sinnvoll, um Transport- und Versicherungskosten separat handhaben zu können.'),
		'formula' => array('Berechnungsformel', 'Definieren Sie eine Berechnungsformel, in der Sie verschiedene Platzhalter benutzen können.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['dynamicSteuersatzType']['options'] = array(
		'none' => 'keine Dynamik',
		'main' => 'der Hauptleistung folgen',
		'max' => 'der höchste verwendete',
		'min' => 'der niedrigste verwendete'
	);
		
	$GLOBALS['TL_LANG']['tl_ls_shop_shipping_methods']['type']['options'] = array(
		'Standard' => array('Standard-Versand', 'Die meisten Versand-Optionen lassen sich mit dem Standard-Versand-Modul realisieren. Bitte wenden Sie sich an Ihren Administrator, falls Sie für eine bestimmte Versand-Methode ein maßgeschneidertes Versand-Modul benötigen.')
	);
