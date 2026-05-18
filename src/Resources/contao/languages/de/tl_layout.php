<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_filterMode'] = ['Produktfilter', 'Bestimmt, ob und mit welcher Filtertechnologie die Produktliste gefiltert werden kann.'];
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_filterMode_options'] = [
		'' => 'Filter deaktiviert',
		'classic' => 'Classic-Filter',
		'fast' => 'Schnelle Filtertechnik (eingeschränkter Funktionsumfang: nur Attributfilter mit Oder-Verknüpfung und Herstellerfilter)'
	];
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterAutoSubmit'] = ['Filter bei Änderung automatisch anwenden', 'Sendet das schnelle Filterformular automatisch ab, sobald sich eine Checkbox- oder Radio-Auswahl ändert.'];
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterHideZeroMatches'] = ['Filteroptionen ohne Treffer ausblenden', 'Blendet Optionen mit 0 erwarteten Treffern im schnellen Filter vollständig aus.'];
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterResetMode'] = ['Rücksetzungslogik des Filters', 'Legt fest, wann aktive schnelle Filter bei einem Kategoriewechsel automatisch zurückgesetzt werden.'];
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_fastFilterResetMode_options'] = [
		'never' => 'Nie automatisch zurücksetzen',
		'always' => 'Bei jedem Kategoriewechsel automatisch zurücksetzen',
		'branch' => 'Nur bei Kategoriewechsel außerhalb des Seitenzweigs zurücksetzen',
	];
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_useFilterInStandardProductlist'] = array('Filter in Standard-Produktübersicht nutzen', 'Wählen Sie diese Option, um den Filter in Produktlisten, die vom Frontend-Modul "Produkt-Übersicht" erzeugt werden (z. B. standardmäßige Kategoriedarstellung), zu nutzen.');
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_useFilterMatchEstimates'] = array('Erwartete Trefferanzahl ermitteln');
    $GLOBALS['TL_LANG']['tl_layout']['ls_shop_numFilterFieldsInSummary'] = array('Anzahl anzuzeigender Filterfelder in Zusammenfassung');
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_matchEstimatesMaxNumProducts'] = array('Produktlimit für erwartete Treffer', 'Abhängig von der Leistungsfähigkeit Ihres Servers empfiehlt es sich unter Umständen, die maximale Produktanzahl in der zu filternden Ergebnisliste zu definieren, für die die Kalkulation der erwarteten Trefferanzahl durchgeführt wird.');
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_matchEstimatesMaxFilterValues'] = array('Wertelimit für erwartete Treffer', 'Abhängig von der Leistungsfähigkeit Ihres Servers empfiehlt es sich unter Umständen, die maximale Anzahl an Werten im Filterformular zu definieren, für die die Kalkulation der erwarteten Trefferanzahl durchgeführt wird.');
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_useFilterInProductDetails'] = array('Filter in Produkt-Detailansicht nutzen', 'Wählen Sie diese Option, um den Filter in Produkt-Detailansichten zu nutzen und damit die Varianten-Filterung zu ermöglichen.');
	$GLOBALS['TL_LANG']['tl_layout']['ls_shop_hideFilterFormInProductDetails'] = array('Filter-Formular in Detailansicht ausblenden');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_layout']['lsShopFilter_legend']   = 'MERCONIS: Filter-Einstellungen';
	$GLOBALS['TL_LANG']['tl_layout']['lsShopJsComponents_legend']   = 'MERCONIS: Javascript Plug-Ins';
