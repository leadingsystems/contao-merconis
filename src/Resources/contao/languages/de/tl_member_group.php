<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopOutputPriceType']										= array('Ausgabepreise brutto/netto', 'Stellen Sie hier ein, ob Kunden, die dieser Gruppe angehören Brutto- oder Nettopreise sehen.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopPriceAdjustment']										= array('Preisanpassung (prozentual)', 'Hier können Sie für alle Kunden, die dieser Gruppe angehören, einen prozentualen Auf- bzw. Abschlag auf alle Produktpreise festlegen.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopMinimumOrderValue']									= array('Mindestbestellwert', 'Wichtig: Tragen Sie hier bitte den Betrag als Brutto- oder Netto-Wert entsprechend der für diese Gruppe gültigen Ausgabepreis-Einstellung an. Die globale Einstellung für eingegebene Preise findet hier keine Anwendung!');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopMinimumOrderValueAddCouponToValueOfGoods']			= array('Gutscheine bei Mindestbestellwert berücksichtigen', 'Bestimmen Sie, ob Gutscheine bei der Prüfung des Mindestbestellwertes berücksichtigt werden sollen oder nicht.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopFormCustomerData']									= array('Formular für Kundendaten', 'Bitte wählen Sie hier das Formular aus, mit dem Sie beim Bestellvorgang die Kundendaten erfassen möchten.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopFormConfirmOrder']									= array('Formular zur Bestellbestätigung', 'Bitte wählen Sie hier das Formular aus, mit dem die Kunden dieser Kundengruppe ihre Bestellung bestätigen sollen.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopStandardPaymentMethod']								= array('Vorausgewählte Zahlungsart', 'Wenn Sie möchten, dass beim Abschluss der Bestellung eine bestimmte Zahlungsart automatisch ausgewählt ist, so können Sie diese hier definieren.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopStandardShippingMethod']								= array('Vorausgewählte Versandsart', 'Wenn Sie möchten, dass beim Abschluss der Bestellung eine bestimmte Versandart automatisch ausgewählt ist, so können Sie diese hier definieren.');
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopLgnMode'] = ['Gesetzliche Gewährleistung', 'Steuert die Anzeige für Mitglieder dieser Gruppe. Eine Änderung passt keine Produkte an.'];
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopLgnModeBlankOption'] = 'Bitte wählen';
	
	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_member_group']['lsShop_legend']			= 'MERCONIS-Einstellungen';

	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_member_group']['lsShopOutputPriceType']['options'] = array(
		'brutto' => array('Brutto-Preise','Wählen Sie diese Option, wenn Mitglieder dieser Gruppe Brutto-Preise sehen sollen'),
		'netto' => array('Netto-Preise','Wählen Sie diese Option, wenn Mitglieder dieser Gruppe Netto-Preise sehen sollen')
	);

	$GLOBALS['TL_LANG']['tl_member_group']['lsShopLgnModeOptions'] = [
		'on' => ['Anzeigen', "Produkte mit 'Wie Mitgliedergruppe' oder 'Immer anzeigen' zeigen die Mitteilung und, falls eingeschaltet, die Herstellergarantie."],
		'off' => ["Nur Produkte mit 'Immer anzeigen'", "Nur Produkte mit 'Immer anzeigen' zeigen Mitteilung und Herstellergarantie. Für Shops mit überwiegend Dienstleistungen oder digitalen Inhalten."],
		'blocked' => ['Nie anzeigen (z. B. Geschäftskunden)', "Weder Mitteilung noch Herstellergarantie, auch nicht bei 'Immer anzeigen'. Für Gruppen ohne Verbraucher, zum Beispiel Geschäftskunden."],
	];
