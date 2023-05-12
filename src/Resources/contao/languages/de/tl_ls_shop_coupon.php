<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['title']										= array('Bezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['description']									= array('Beschreibung');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productCode']									= array('Artikelnummer', 'Hier können Sie eine Artikelnummer hinterlegen, die bei der Bestellung zur Identifizierung des verwendeten Gutscheins angezeigt wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponCode']									= array('Gutschein-Code', 'Tragen Sie hier den Code ein, den ein Kunde im Warenkorb eingeben muss, um diesen Gutschein einzulösen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValueType']								= array('Art des Gutschein-Werts', 'Wählen Sie hier, ob der angegebene Gutschein-Wert als fester oder prozentualer Abzug berechnet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValue']									= array('Gutschein-Wert', 'Tragen Sie hier den Gutschein-Wert als Festbetrag oder prozentualen Betrag ein. Der Gutschein-Wert wird stets vom für den Kunden sichtbaren Gesamtwarenwert abgezogen. Ob ein eingegebener Festbetrag als Brutto- oder Nettobetrag interpretiert wird, hängt dementsprechend davon ab, ob der Kunde im Shop Brutto- oder Nettopreise sieht. Um Wertunterschiede eines Gutscheins bei Nutzung durch unterschiedliche Kundengruppen zu vermeiden, machen Sie bitte von der Möglichkeit Gebrauch, die Kundengruppen, für die ein Gutschein gilt, zu definieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValue']							= array('Mindestbestellwert', 'Wichtig: Tragen Sie hier bitte den Betrag als Brutto- oder Netto-Wert entsprechend der für diese Gruppe gültigen Ausgabepreis-Einstellung an. Die globale Einstellung für eingegebene Preise findet hier keine Anwendung!');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['allowedForGroups']							= array('Gültig für folgende Kundengruppen', 'Bitte bestimmen Sie hier, für welche Kundengruppen dieser Gutschein gültig ist.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['start']										= array('Gültigkeitszeitraum von');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['stop']										= array('Gültigkeitszeitraum bis');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['limitNumAvailable']							= array('Anzahl beschränken');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['numAvailable']								= array('Verfügbare Anzahl');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['changeNumAvailable']							= array('Verfügbare Anzahl ändern', 'Tragen Sie z. B. "+100" ein, um die Anzahl um 100 zu erhöhen, "-100", um die Anzahl um 100 zu verringern und "100" ohne Vorzeichen, um die Anzahl auf exakt 100 zu setzen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['published']									= array('Aktiv');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productDirectSelection']						= array('Produkt-Auswahl');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType']						= array('Art der Produkt-Auswahl');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate']									= array('Verwenden', 'Aktivieren, um das Suchkriterium zu verwenden');
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValueforCouponActivate']          = array('Mindestbestellwert nur für Produkte-Auswahl', 'Bei Aktivierung bezieht sich der Mindestbestellwert nur auf die Produkt-Auswahl');


	
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionNewProduct']					= array('Neues Produkt');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionSpecialPrice']				= array('Sonderpreis');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionCategory']					= array('Seite/Kategorie');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionProducer']					= array('Hersteller');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionProductName']					= array('Produktbezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionArticleNr']					= array('Artikelnummer');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionTags']						= array('Suchbegriffe');
	
	/*
	 * Misc
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionNewProduct'] = 'Suchkriterium "Neues Produkt"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionSpecialPrice'] = 'Suchkriterium "Sonderpreis"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionCategory'] = 'Suchkriterium "Seite/Kategorie"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['subHeadlineSearchSelectionCategory'] = 'Bitte beachten Sie, dass die aktuell aufgerufene Seite standardmäßig auf die Suche angewendet wird, wenn Sie dieses Suchkriterium aktivieren, aber nicht explizit eine Seiten-/Kategorieauswahl treffen.';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionProducer'] = 'Suchkriterium "Hersteller"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionProductName'] = 'Suchkriterium "Produktbezeichnung"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionArticleNr'] = 'Suchkriterium "Artikelnummer"';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionTags'] = 'Suchkriterium "Suchbegriffe"';

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['title_legend']   = 'Bezeichnung';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['status_legend']   = 'Status';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['generalSettings_legend']   = 'Allgemeine Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['numAvailable_legend']   = 'Verfügbare Anzahl';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType_legend']   = 'Art der Produkt-Auswahl';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['directSelection_legend']   = 'Einfache Produkt-Auswahl';
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelection_legend']   = 'Produkt-Auswahl aufgrund detaillierter Suche';
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productBlacklist']   = 'Produktauswahl als Blacklist interpretieren';
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['blacklistActivate']   = array('Produkt(e) ausschließen', 'Produktauswahl als Blacklist interpretieren und somit für den Coupon ausschließen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValueforCoupon']   = 'Mindestbestellwert nur für ausgewählte Produkte';

	
	/*
	 * References
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValueType']['options'] = array(
		'percentaged' => array('Prozentwert', 'Der Gutschein-Wert wird als prozentualer Abzug berechnet.'),
		'fixed' => array('Festbetrag', 'Der Gutschein-Wert wird als Festbetrag abgezogen.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType']['options'] = array(
		'noSelection' => array('Keine Produktauswahl', 'Treffen Sie diese Auswahl, wenn Sie die Gültigkeit des Gutscheins nicht auf bestimmte Produkte beschränken möchten.'),
		'directSelection' => array('Direkte Produktauswahl', 'Mit dieser Auswahl-Art können Sie die Produkte, für die der Gutschein gültig sein soll, direkt bestimmen.'),
		'searchSelection' => array('Produkt-Suche', 'Die Produkte, für die der Gutschein gültig sein soll, werden anhand vorgegebener Suchkriterien ermittelt.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionNewProduct']['options'] = array(
		'new' => array('Produkt ist als neu markiert'),
		'notNew' => array('Produkt ist nicht als neu markiert')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionSpecialPrice']['options'] = array(
		'specialPrice' => array('Produkt ist als Sonderpreis markiert'),
		'noSpecialPrice' => array('Produkt ist nicht als Sonderpreis markiert')
	);
	
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['new']        = array('Neuer Gutschein', 'Einen neuen Gutschein anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['edit']        = array('Gutschein bearbeiten', 'Gutschein ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['delete']        = array('Gutschein löschen', 'Gutschein ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['copy']        = array('Gutschein kopieren', 'Gutschein ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['show']        = array('Details anzeigen', 'Details des Gutscheins mit ID %s anzeigen');
