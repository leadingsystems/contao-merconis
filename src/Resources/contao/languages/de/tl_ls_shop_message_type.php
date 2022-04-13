<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['title']	= array('Bezeichnung');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['alias'] = array('Alias', 'Eindeutige Bezeichnung, welche zur Referenzierung verwendet wird.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['sendWhen']			= array('Versandart');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation01'] = array('Abhängigkeit von Status 1');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation02'] = array('Abhängigkeit von Status 2');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation03'] = array('Abhängigkeit von Status 3');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation04'] = array('Abhängigkeit von Status 4');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useStatusCorrelation05'] = array('Abhängigkeit von Status 5');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['usePaymentStatusCorrelation'] = array('Abhängigkeit von Payment-Provider-Status');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation01'] = array('Versenden bei Status');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation02'] = array('Versenden bei Status');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation03'] = array('Versenden bei Status');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation04'] = array('Versenden bei Status');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['statusCorrelation05'] = array('Versenden bei Status');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['paymentStatusCorrelation_paymentProvider'] = array('Payment Provider');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['paymentStatusCorrelation_statusValue'] = array('Versenden bei Status');

	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['useCounter']			= array('Nachrichtennummerierung verwenden');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counter']			= array('Aktueller Zählerstand');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterString']			= array('Format der Nachrichtennummer', 'Hier können Sie definieren, in welchem Format die Nachrichtennummer ausgegeben/angezeigt werden soll. Verwenden Sie den Platzhalter {{counter}}, um den Zählerstand einzufügen. Einen vorgenullten Zählerstand können Sie einfügen, indem Sie den Platzhalter z. B. als {{counter:7}} angeben, wobei nach dem Doppelpunkt die Gesamtanzahl der Stellen angegeben wird, mit welcher der Zähler ausgegeben werden soll. Bei Bedarf steht Ihnen auch der Platzhalter {{date:}} zur Verfügung, um eine variable Datumsangabe automatisch einzufügen. Hinter dem Doppelpunkt können Sie jede beliebige Datumsangabe in der Syntax der PHP-Funktion "date()" notieren. Beispiel: "{{date:Y}}-{{counter}}" ergibt für die 147. Nachricht im Jahr 2012 die Nachrichten.-Nr. "2012-147".');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterStart']			= array('Startwert des Zählers ({{counter}})', 'Bestimmen Sie hier, bei welchem Wert der Zähler ({{counter}}) beginnen soll.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterRestartCycle']		= array('Rücksetzung des Zählers ({{counter}})', 'Definieren Sie bei Bedarf, in welchem Rhythmus der Zähler ({{counter}}) zurückgesetzt werden soll.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterRestartNow']		= array('Zähler ({{counter}}) sofort zurücksetzen', 'Aktivieren Sie diese Option, wenn Sie möchten, dass der Zähler ({{counter}}) beim Speichern des Datensatzes sofort auf den Startwert zurückgesetzt werden soll.');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['lastDispatchDateUnixTimestamp'] = array('Datum des letzten Versands');

	
	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['title_legend']   = 'Bezeichnung';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counter_legend']   = 'Nachrichtennummerierung';
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['sendingOptions_legend'] = 'Versandeinstellungen';
	
	/*
	 * References
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['counterRestartCycle']['options'] = array(
		'never' => 'nie',
		'year' => 'neues Jahr',
		'month' => 'neuer Monat',
		'week' => 'neue Woche',
		'day' => 'neuer Tag'	
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['sendWhen']['options'] = array(
		'manual' => 'manuell',
		'onStatusChangeImmediately' => 'sofort nach Statusänderung',
		'onStatusChangeCronDaily' => 'statusabhängig mittels Cronjob (täglich)',
		'onStatusChangeCronHourly' => 'statusabhängig mittels Cronjob (stündlich)',
		'asOrderConfirmation' => 'als Bestellbestätigung',
		'asOrderNotice' => 'als Bestellbenachrichtigung',
		'onRestock' => 'als Wiederverfügbarkeitsbenachrichtigung'
	);

	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['paymentStatusCorrelation_paymentProvider']['options'] = array(
		'payPalPlus' => 'PayPalPlus',
		'payone' => 'Payone',
		'saferpay' => 'Saferpay',
		'vrpay' => 'VR Pay',
		'sofortbanking' => 'Sofort.'
	);

	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['new']        = array('Neue Nachrichtenart', 'Eine neue Nachrichtenart anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['edit']        = array('Nachrichtenvorlagen bearbeiten', 'Nachrichtenvorlagen der Nachrichtenart ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['editheader']        = array('Nachrichtenart bearbeiten', 'Nachrichtenart ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['delete']        = array('Nachrichtenart löschen', 'Nachrichtenart ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['copy']        = array('Nachrichtenart kopieren', 'Nachrichtenart ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_message_type']['show']        = array('Details anzeigen', 'Details der Nachrichtenart ID %s anzeigen');
