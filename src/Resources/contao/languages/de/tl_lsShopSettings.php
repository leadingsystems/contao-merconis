<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_country']					= array('Land, in dem der Shop betrieben wird', 'Tragen Sie hier die zweistellige L&auml;nderkennung (ISO-3166-1-Kodierliste) des Landes ein, in dem der Shop betrieben wird.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_serial']					= array('Seriennummer', 'Bitte tragen Sie hier die Seriennummer ein, die Sie beim Kauf der Software erhalten haben.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownVATID']				= array('Eigene USt-IdNr.', 'Sofern sie &uuml;ber eine USt-IdNr. verf&uuml;gen, tragen Sie diese hier bitte ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_euCountrycodes']			= array('EU-L&auml;nder', 'Tragen Sie hier die zweistelligen L&auml;nderkennungen (ISO-3166-1-Kodierliste) der EU-Staaten ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currency']				= array('W&auml;hrung', 'Tragen Sie hier die W&auml;hrung ein, wie Sie &uuml;berall im Shop verwendet werden soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currencyCode']			= array('W&auml;hrungs-Code gem&auml;&szlig; ISO-4217', 'Tragen Sie hier f&uuml;r die von Ihnen verwendete W&auml;hrung den dreistelligen W&auml;hrungs-Code gem&auml;&szlig; ISO-4217 ein (z. B. &quot;EUR&quot; f&uuml;r Euro, &quot;GBP&quot; f&uuml;r britische Pfund oder &quot;USD&quot; f&uuml;r den US-Dollar.)');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_weightUnit']				= array('Gewichtseinheit', 'Tragen Sie hier die Einheit ein, in der Sie Gewichte im Shop angeben. Gewichtsangaben werden im Falle einer Versandkostenberechnung nach Gewicht ben&ouml;tigt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_quantityDefault']			= array('Vorbelegung f&uuml;r Mengen-Eingabefeld');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimals']				= array('Anzahl der Dezimalstellen bei Preisangaben', 'Geben Sie hier an, mit wievielen Dezimalstellen Preise im Shop dargestellt werden sollen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor']		= array('Rundungsmodell f&uuml;r Preise (z. B. Rappenrundung)', 'Bitte w&auml;hlen Sie hier das Rundungsmodell, mit dem alle Preise in Merconis sowohl in der Preisausgabe als auch in der Kalkulation gerundet werden. Z. B. f&uuml;r Schweizer H&auml;ndler ist hier in den meisten F&auml;llen die Auswahl der Rappenrundung zu empfehlen. Falls beim Verkauf von Kleinteilen in sehr gro&szlig;er Menge positionsbezogen eine h&ouml;here Genauigkeit ben&ouml;tigt wird, so kann hier die Hundertstel-Rundung eingestellt und lediglich in den Templates der ausgegebene Rechnungsbetrag und ggf. noch die ausgewiesene Steuer z. B. in 0,05er-Schritten gerundet werden. Bitte beachten Sie, dass Payment-Provider teilweise eine exakte Kalkulationspr&uuml;fung vornehmen und es nicht akzeptieren w&uuml;rden, wenn die kumulierten Einzelpositionen aufgrund von Rundungsungenauigkeiten nicht exakt dem Rechnungsbetrag entsprechen. Um die Ablehnung von Zahlungen durch Payment Provider zu vermeiden, arbeitet Merconis deshalb im Falle von templateseitig gerundeten Ausgabebetr&auml;gen intern weiterhin mit der urspr&uuml;nglichen Genauigkeit.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimalsWeight']		= array('Anzahl der Dezimalstellen bei Gewichtsangaben', 'Geben Sie hier an, mit wievielen Dezimalstellen Gewichtsangaben im Shop dargestellt werden sollen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType']				= array('Art der eingegebenen Preise brutto/netto', 'WICHTIG: Geben Sie hier an, ob Sie in der Verwaltungsoberfl&auml;che &uuml;berall Brutto- oder Netto-Preise angeben. Wenn Sie grenz&uuml;berschreitend Produkte verkaufen, dann hinterlegen Sie Ihre Preise zwingend netto. Hinweis zur Frontend-Ausgabe: Ob Sie im Frontend Brutto oder Netto ausgeben, bestimmen Sie innerhalb der Contao-Mitgliedergruppe(n).');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardGroup']			= array('Standard-Benutzergruppe', 'Einige Shop-Einstellungen werden f&uuml;r unterschiedliche Kunden- bzw. Mitgliedergruppen separat eingestellt. Definieren Sie hier, welcher dieser Gruppen ein nicht angemeldeter Kunde zugeordnet werden soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout']			= array('Abschluss der Bestellung mit oder ohne Login', 'Bestimmen Sie hier, ob der Abschluss der Bestellung mit oder ohne Login m&ouml;glich sein soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods'] = array('G&uuml;nstigste Zahlungs- und Versandoption automatisch w&auml;hlen', 'W&auml;hlen Sie diese Checkbox, wenn Sie m&ouml;chten, dass die g&uuml;nstigste Zahlungs- und Versandoption automatisch ausgew&auml;hlt werden soll, sofern der Kunde noch keine Auswahl getroffen hat oder eine bereits getroffene Auswahl durch &Auml;nderungen der Bestellung nicht mehr m&ouml;glich ist. Die automatische Auswahl bewirkt, dass der Kunde die Bestellung unter Verwendung der g&uuml;nstigsten Zahlungs- und Versandoption direkt abschlie&szlig;en kann, ohne diese Auswahl selbst noch treffen zu m&uuml;ssen. Sofern Sie diese M&ouml;glichkeit nicht nutzen, wird die g&uuml;nstigste Zahlungs- und Versandoption als Vorschlag in der Kalkulation ber&uuml;cksichtigt, der Abschluss der Bestellung ist aber nur m&ouml;glich, wenn der Kunde seine Auswahl noch selbst vornimmt. Bitte pr&uuml;fen Sie, ob rechtliche Vorgaben in Ihrem Land eventuell gegen die Verwendung dieser Einstellung sprechen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_loginModuleID'] 			= array('Login-Modul zur Verwendung beim Bestellabschluss');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_miniCartModuleID'] 		= array('Mini-Warenkorb-Modul', 'Das Modul, welches per AJAX aktualisiert werden soll');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useVATIDValidation']		= array('Online-Pr&uuml;fung der USt-IdNr. verwenden');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_VATIDValidationSOAPOptions'] = array('Optionen f&uuml;r SOAP-Client', 'Bitte &auml;ndern Sie die Standardeinstellungen nur, wenn Sie genau wissen, was Sie tun. Fehlerhafte Einstellungen k&ouml;nnen dazu f&uuml;hren, dass die Validierung der USt-IdNr deutlich verz&ouml;gert wird. Bitte beachten Sie, dass es abh&auml;ngig von Ihrer Serverkonfiguration auch m&ouml;glich ist, dass die Standardeinstellungen nicht optimal sind, und zu einer Verz&ouml;gerung f&uuml;hren. Testen Sie in diesem Fall bitte, ob mit Ihrer Serverkonfiguration das Entfernen aller Options-Angaben eine Validierung ohne Verz&ouml;gerung erm&ouml;glicht.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateOverview'] = array('Backend-Template f&uuml;r Bestell&uuml;bersicht');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateDetails'] = array('Backend-Template f&uuml;r Bestellungsdetails');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrCounter']			= array('Aktueller Z&auml;hlerstand');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrString']			= array('Bestellnummer-Format', 'Hier k&ouml;nnen Sie definieren, in welchem Format die Bestellnummer angegeben werden soll. Verwenden Sie den Platzhalter {{counter}}, um den Z&auml;hlerstand einzuf&uuml;gen sowie bei Bedarf {{date:}}, um eine variable Datumsangabe automatisch einzuf&uuml;gen. Hinter dem Doppelpunkt k&ouml;nnen Sie jede beliebige Datumsangabe in der Syntax der PHP-Funktion &quot;date()&quot; notieren. Beispiel: &quot;{{date:Y}}-{{counter}}&quot; ergibt für die 147. Bestellung im Jahr 2012 die Best.-Nr. &quot;2012-147&quot;.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrStart']			= array('Startwert des Z&auml;hlers ({{counter}})', 'Bestimmen Sie hier, bei welchem Wert der Z&auml;hler ({{counter}}) beginnen soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle']		= array('R&uuml;cksetzung des Z&auml;hlers ({{counter}})', 'Definieren Sie bei Bedarf, in welchem Rhythmus der Z&auml;hler ({{counter}}) zur&uuml;ckgesetzt werden soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartNow']		= array('Z&auml;hler ({{counter}}) sofort zur&uuml;cksetzen', 'Aktivieren Sie diese Option, wenn Sie m&ouml;chten, dass der Z&auml;hler ({{counter}}) beim Speichern Ihrer Einstellungen sofort auf den Startwert zur&uuml;ckgesetzt werden soll.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues01']		= array('Statusm&ouml;glichkeiten f&uuml;r Status 1', 'Tragen Sie hier bitte die m&ouml;glichen Werte f&uuml;r diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status f&uuml;r eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht ben&ouml;tigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues02']		= array('Statusm&ouml;glichkeiten f&uuml;r Status 2', 'Tragen Sie hier bitte die m&ouml;glichen Werte f&uuml;r diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status f&uuml;r eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht ben&ouml;tigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues03']		= array('Statusm&ouml;glichkeiten f&uuml;r Status 3', 'Tragen Sie hier bitte die m&ouml;glichen Werte f&uuml;r diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status f&uuml;r eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht ben&ouml;tigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues04']		= array('Statusm&ouml;glichkeiten f&uuml;r Status 4', 'Tragen Sie hier bitte die m&ouml;glichen Werte f&uuml;r diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status f&uuml;r eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht ben&ouml;tigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues05']		= array('Statusm&ouml;glichkeiten f&uuml;r Status 5', 'Tragen Sie hier bitte die m&ouml;glichen Werte f&uuml;r diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status f&uuml;r eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht ben&ouml;tigen.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_shippingInfoPages']		= array('Seite &quot;Versandbedingungen&quot;', 'Geben Sie hier die Seite an, auf der Sie Ihre Versandbedingungen erl&auml;utern. Diese Seite wird bei der Produktdarstellung verlinkt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_cartPages']				= array('Seite &quot;Warenkorb&quot;', 'W&auml;hlen Sie hier die Seite aus, die das Modul &quot;Warenkorb&quot; enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_reviewPages'] = array('Seite &quot;Kasse - Pr&uuml;fung der Bestellung&quot;', 'W&auml;hlen Sie hier die Seite aus, die das Modul &quot;Pr&uuml;fung der Bestellung&quot; enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_signUpPages']			= array('Seite &quot;Registrierung&quot;', 'W&auml;hlen Sie hier die Seite aus, die das Modul &quot;Registrierung&quot; enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutPaymentErrorPages']	= array('Seite &quot;Fehler bei Verarbeitung der Zahlungsoption&quot;', 'W&auml;hlen Sie hier die Seite aus, die die Fehlermeldung bei missgl&uuml;ckter Verarbeitung der Zahlungsoption enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutShippingErrorPages']	= array('Seite &quot;Fehler bei Verarbeitung der Versandoption&quot;', 'W&auml;hlen Sie hier die Seite aus, die die Fehlermeldung bei missgl&uuml;ckter Verarbeitung der Versandoption enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutFinishPages']	= array('Seite &quot;Bestell-Abschluss&quot;', 'W&auml;hlen Sie hier die Seite aus, die das Modul &quot;Bestell-Abschluss&quot; enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_paymentAfterCheckoutPages']		= array('Seite &quot;Bezahlung nach Bestell-Abschluss&quot;', 'W&auml;hlen Sie hier die Seite aus, die das Modul &quot;Bezahlung nach Bestellabschluss&quot; enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_afterCheckoutPages']		= array('Seite &quot;Informationen nach Bestell-Abschluss&quot;', 'W&auml;hlen Sie hier die Seite aus, die das Modul &quot;Informationen nach Bestellabschluss&quot; enth&auml;lt. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ajaxPages']			=  array('Seite &quot;AJAX&quot;', 'W&auml;hlen Sie hier die Seite aus, die f&uuml;r AJAX-Anfragen verwendet wird. Diese Seite sollte ausschlie&szlig;lich die relevanten MERCONIS-Module enthalten. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchResultPages'] = array('Seite &quot;Ergebnisse der Produktsuche&quot;', 'W&auml;hlen Sie hier die Seite aus, auf der die Ergebnisse einer Produktsuche dargestellt werden. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrdersPages'] = array('Seite &quot;Meine Bestellungen&quot;', 'W&auml;hlen Sie hier die Seite aus, in der das Modul &quot;Meine Bestellungen&quot; eingebunden ist. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrderDetailsPages'] = array('Seite &quot;Meine Bestellungen - Details&quot;', 'W&auml;hlen Sie hier die Seite aus, in der das Modul &quot;Meine Bestellungen - Details&quot; eingebunden ist. Falls Sie einen mehrsprachigen Shop betreiben, w&auml;hlen Sie bitte f&uuml;r jede Sprache die entsprechende Seite aus.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoDummyCover']	= array('Default-Cover f&uuml;r Videos', 'W&auml;hlen Sie hier eine Grafik aus, die als Coverbild f&uuml;r Videos verwendet werden soll, wenn keine zum Video passende Cover-Grafik ermittelt werden kann.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoCoverOverlay']	= array('Hinweis-Grafik für Video-Cover', 'Wenn Sie für ein Produkt neben Bildern auch ein Video ausw&auml;hlen, dann wird das Video zun&auml;chst durch eine Vorschau-Grafik (das Cover) repr&auml;sentiert. Nach Klick auf das Cover wird das Video abgespielt. Damit der Shop-Besucher ein Video auch als solches erkennt, wird die Hinweisgrafik auf das Cover gelegt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoCoverOverlaySmall']	= array('Hinweis-Grafik für Video-Cover (klein)', 'Wenn Sie für ein Produkt neben Bildern auch ein Video ausw&auml;hlen, dann wird das Video zun&auml;chst durch eine Vorschau-Grafik (das Cover) repr&auml;sentiert. Nach Klick auf das Cover wird das Video abgespielt. Damit der Shop-Besucher ein Video auch als solches erkennt, wird die Hinweisgrafik auf das Cover gelegt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isNewOverlay']		= array('Hinweis-Grafik für Neuheiten');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isNewOverlaySmall']		= array('Hinweis-Grafik für Neuheiten (klein)');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isOnSaleOverlay']	= array('Hinweis-Grafik für Sonderangebote');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_isOnSaleOverlaySmall']	= array('Hinweis-Grafik für Sonderangebote (klein)');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMainImage']			= array('Produkt-Hauptbilder: Bildbreite und Bildh&ouml;he', 'Bitte stellen Sie hier die Bildbreite und Bildh&ouml;he f&uuml;r die Darstellung von Produkt-Hauptbildern ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMoreImages']			= array('Weitere Produkt-Bilder: Bildbreite und Bildh&ouml;he', 'Bitte stellen Sie hier die Bildbreite und Bildh&ouml;he f&uuml;r die Darstellung weiterer Produkt-Bilder ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_moreImagesMargin']			= array('Weitere Produkt-Bilder: Bildabstand', 'Bitte stellen Sie hier den oberen, rechten, unteren und linken Bildabstand f&uuml;r die Darstellung weiterer Produkt-Bilder ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_imagesFullsize']			= array('Gro&szlig;ansicht/Neues Fenster','Produkt-Bilder bei Klick als Gro&szlig;ansicht in einer Lightbox oder einem neuen Fenster &ouml;ffnen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_moreImagesSortBy']			= array('Sortieren nach','Bitte bestimmen Sie hier die Sortierreihenfolge');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMainImage02']			= array('Produkt-Bild in Galerie: Bildbreite und Bildh&ouml;he', 'Bitte stellen Sie hier die Bildbreite und Bildh&ouml;he f&uuml;r die Darstellung von Produkt-Bildern in der Galerie-Ansicht ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemWidth']			= array('Breite eines einzelnen Produktbereichs');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemHeight']		= array('H&ouml;he eines einzelnen Produktbereichs');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemMargin']		= array('Au&szlig;enabstand eines einzelnen Produktbereichs', 'Bitte stellen Sie hier den oberen, rechten, unteren und linken Abstand f&uuml;r einen einzelnen Produktbereich ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType']			= array('Versandkosten inkl./zzgl.', 'Bestimmen Sie hier, ob Sie Ihre Preise inklusive oder zuz&uuml;glich Versandkosten angeben.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_output_definitionset']	= array('Standard-Darstellungsvorgabe', 'Bitte w&auml;hlen Sie hier die Darstellungsvorgabe, die standardm&auml;&szlig;ig verwendet wird.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_delivery_infoSet']		= array('Standardeinstellung f&uuml;r Lagerbestand/Lieferzeit', 'Bitte w&auml;hlen Sie hier die Vorgabe f&uuml;r Lagerbestand und Lieferzeit, die standardm&auml;&szlig;ig verwendet wird.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_productDetailsTemplate'] =	array('Standard-Template f&uuml;r Produkt-Detaildarstellung');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageFolder'] =	array('Standardordner für Produktbilder', 'Definieren Sie hier den Ordner, in dem der Shop anhand der Artikelnummern automatisch nach passenden Produktabbildungen suchen soll. Erkannt werden Bilder, deren Dateiname mit der Artikelnummer gefolgt von einem Trennzeichen (siehe n&auml;chstes Eingabefeld) beginnt (z. B. 1234_bildname.jpg) oder deren Dateiname lediglich die Artikelnummer enth&auml;lt (z. B. 123.jpg)');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageDelimiter'] =	array('Trennzeichen f&uuml;r Artikelnummer in Produktbildern', 'Bestimmen Sie hier, welches Trennzeichen die Artikelnummer in einem Bildnamen vom restlichen Dateinamen abgrenzt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection'] = array('Standardsortierung f&uuml;r Produktbilder');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImportFolder'] =	array('Standardordner für Import-Dateien', 'Definieren Sie hier den Ordner, in den die Importfunktion eine zu importierende Datei nach dem Upload ablegt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvDelimiter'] = array('CSV-Trennzeichen');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEnclosure'] = array('CSV-Feldbegrenzungszeichen');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEscape'] = array('CSV-Maskierungszeichen');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvLocale'] = array('CSV-Spracheinstellung');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numMaxImportRecordsPerRound'] = array('Anzahl Datens&auml;tze f&uuml;r Import-Splitting', 'Um bei sehr gro&szlig;en Importen keine sehr lange Laufzeit einzelner PHP-Skripte und dadurch entsprechend hohe Server-Limits zu erfordern, wird der Import in Einzelschritte geteilt. Definieren Sie hier, wieviele Datens&auml;tze auf einmal verarbeitet werden k&ouml;nnen.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitFields'] =	array('Verwendete Produktinformationen', 'Definieren Sie hier, welche Produktinformationen bei einer LiveHits-Anfrage &uuml;bermittelt werden sollen. Bitte beachten Sie, dass die Auswahl des Feldes &quot;Link zum Produkt&quot; nicht die Darstellung des Links zur Folge hat sondern den direkten Aufruf der jeweiligen Produkt-Detailansicht bei Auswahl eines LiveHit!');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeWidth'] = array('Bildausgabe-Breite', 'Breite der LiveHits-Bildausgabe in px');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeHeight'] = array('Bildausgabe-H&ouml;he', 'H&ouml;he der LiveHits-Bildausgabe in px');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMaxNumHits'] = array('Maximale Anzahl &uuml;bermittelter Hits');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMinLengthSearchTerm'] = array('Mindestl&auml;nge des Suchbegriffs');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsDOMSelector'] = array('DOM Einh&auml;ngepunkt', 'Wenn Sie &quot;Keine automatische Positionierung&quot; gew&auml;hlt haben, k&ouml;nnen Sie durch die Eingabe eines CSS-Selektors das DOM-Element bestimmen, in welchem die Hitbox eingeh&auml;ngt wird.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsNoAutoPosition'] = array('Keine automatische Positionierung', 'W&auml;hlen Sie diese Checkbox, wenn Sie nicht m&ouml;chten, dass die LiveHits-Engine die Hitbox automatisch positioniert. So k&ouml;nnen Sie Ma&szlig;e und Positionierung v&ouml;llig frei per CSS selbst bestimmen.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeys'] = array('Import-Spalten f&uuml;r flexible Produktinformationen','Geben Sie hier als kommagetrennte Liste die Spalten&uuml;berschriften der Felder an, die als flexible Produktinformationen importiert werden sollen. Bitte beachten Sie, dass die Spalten&uuml;berschriften als Schl&uuml;sselworte f&uuml;r die flexiblen Produktinformationen verwendet werden.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeysLanguageIndependent'] = array('Import-Spalten f&uuml;r flexible Produktinform. (sprachunabh&auml;ngig)','Geben Sie hier als kommagetrennte Liste die Spalten&uuml;berschriften der Felder an, die als flexible Produktinformationen importiert werden sollen. Bitte beachten Sie, dass die Spalten&uuml;berschriften als Schl&uuml;sselworte f&uuml;r die flexiblen Produktinformationen verwendet werden.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownEmailAddress'] = array('Eigene E-Mail-Adresse', 'Wird zum Empfang diverser Systemmeldungen verwendet.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_maxNumParallelSearchCaches'] = array('Maximale Anzahl parallel möglicher Suchcaches');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchCacheLifetimeSec'] = array('Lebensdauer der Suchcaches in Sekunden');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_considerGroupPricesInFilterAndSorting'] = array('Gruppenpreise f&uuml;r Filterung und Sortierung ber&uuml;cksichtigen','Bitte deaktivieren Sie diese Option aus Performancegr&uuml;nden, wenn Sie keinem Produkt bzw. keiner Variante abweichende Gruppenpreise hinterlegt haben.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ipWhitelist'] = array('IP-Whitelist', 'Tragen Sie hier kommagetrennt alle IPs ein, denen Sie den Aufruf Ihres Systems zur &Uuml;bermittlung z. B. von Zahlungsinformationen ohne Referer-Check erlauben wollen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_urlWhitelist'] = array('URL-Whitelist', 'Tragen Sie hier einen regulären Ausdruck ein, der als Suchmuster auf die Request-URL angewandt wird. Bei einem Treffer in der URL wird der Referer-Check &uuml;bergangen.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_blnCompatMode2-1-4'] = array('Kompatibilit&auml;tsmodus für Updates von Versionen < 2.1.5', 'Mit der Version 2.1.5 hat sich die Dateiablagestruktur teilweise verändert. Dieser Kompatibilit&auml;tsmodus veranlasst Merconis dazu, die alte Dateiablagestruktur anstatt der neuen zu erwarten.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_sortingCharacterTranslationTable'] = array('Ersetzungstabelle f&uuml;r Sortierung', 'Um die Ber&uuml;cksichtigung von Sonderzeichen bei der Sortierung zu kontrollieren, k&ouml;nnen Sie hier definieren, mit welchem Zeichen bzw. welcher Zeichenfolge Sonderzeichen bei der Sortierung ersetzt werden.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_dcaNamesWithoutMultilanguageSupport'] = array('Bei Mehrsprachinitialisierung auszulassende DCAs', 'Kommagetrennte Liste von DCA-Namen, die bei der Mehrsprachinitialisierung übersprungen werden sollen.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsDebugMode'] = array('Debug-Modus');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsNoCacheMode'] = array('Caching deaktivieren');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsNoMinifierMode'] = array('Komprimierung deaktivieren');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['basic_legend']   = 'Grundlegende Angaben';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['euSettings_legend'] = 'Einstellungen bzgl. der Europ&auml;ischen Union';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['userSettings_legend']   = 'Benutzerbezogene Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['pageSettings_legend']   = 'Seiten-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['systemImages_legend']   = 'Systembilder';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['orderNr_legend']   = 'Bestellnummer';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['systemSettings_legend']   = 'System-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['liveHits_legend']   = 'Einstellungen f&uuml;r MERCONIS LiveHits';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['orderStatusTypes_legend'] = 'Statusm&ouml;glichkeiten';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['productPresentationTemplate01_legend'] = 'Einstellungen f&uuml;r Darstellung in Produkt-Detailansicht';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['productPresentationTemplate02_legend'] = 'Einstellungen f&uuml;r Darstellung in Galerie-Ansicht';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['performanceSettings_legend'] = 'Performance-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['importSettings_legend'] = 'Import-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['compatSettings_legend'] = 'Kompatibilit&auml;ts-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ipWhitelist_legend'] = 'Whitelist f&uuml;r Referer-Prüfung';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['backendLsjs_legend'] = 'Einstellungen für LSJS im Backend';

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['misc_legend'] = 'Erweiterte Einstellungen';

	/*
	 * References
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['edit'] = 'Die grundlegenden Shop-Einstellungen bearbeiten';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['name_asc']  = 'Dateiname (aufsteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['name_desc'] = 'Dateiname (absteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['date_asc']  = 'Datum (aufsteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['date_desc'] = 'Datum (absteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['meta']      = 'Meta Datei (meta.txt)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['random']    = 'Zuf&auml;llige Reihenfolge';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle']['options'] = array(
		'never' => 'nie',
		'year' => 'neues Jahr',
		'month' => 'neuer Monat',
		'week' => 'neue Woche',
		'day' => 'neuer Tag'	
	);
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitFields']['options'] = array(
		'_title' => 'Produktbezeichnung',
		'_code' => 'Artikelnummer',
		'_priceAfterTaxFormatted' => 'Preis',
		'_mainImage' => 'Produktabbildung',
		'_shortDescription' => 'Kurzbeschreibung',
		'_linkToProduct' => 'Link zum Produkt'
	);
	
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor']['options'] = array(
		'100' => array('0,01er-Schritte', 'Rundung in 0,01er-Schritten (Standard f&uuml;r die meisten W&auml;hrungen)'),
		'20' => array('0,05er-Schritte (Rappenrundung)', 'Rundung in 0,05er-Schritten (z. B. Rappenrundung in der Schweiz)')
	);
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection']['options'] = array(
		'name_asc' => 'Dateiname (aufsteigend)',
		'name_desc' => 'Dateiname (absteigend)',
		'date_asc' => 'Datum (aufsteigend)',
		'date_desc' => 'Datum (absteigend)',
		'random' => 'Zuf&auml;llige Reihenfolge'
	);
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType']['options'] = array(
		'brutto' => array('Brutto-Preise','W&auml;hlen Sie diese Option, wenn Sie Ihren Produkten Brutto-Preise hinterlegen'),
		'netto' => array('Netto-Preise','W&auml;hlen Sie diese Option, wenn Sie Ihren Produkten Netto-Preise hinterlegen')
	);
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType']['options'] = array(
		'incl' => array('inklusive','W&auml;hlen Sie diese Option, wenn Sie Ihren Produkten Preise inklusive Versandkosten hinterlegen. Ein entsprechender Hinweis wird bei der Preisdarstellung ausgegeben.'),
		'excl' => array('zuz&uuml;glich','W&auml;hlen Sie diese Option, wenn Sie Ihren Produkten Preise zuz&uuml;glich Versandkosten hinterlegen. Ein entsprechender Hinweis wird bei der Preisdarstellung ausgegeben.')
	);
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout']['options'] = array(
		'withLogin' => array('Mit Login', 'W&auml;hlen Sie diese Option, wenn Sie den Abschluss der Bestellung nur f&uuml;r angemeldete Besucher erm&ouml;glichen wollen.'),
		'withoutLogin' => array('Ohne Login', 'W&auml;hlen Sie diese Option, wenn Sie den Abschluss der Bestellung f&uuml;r nicht angemeldete Besucher erm&ouml;glichen und auch keine Login-M&ouml;glichkeit bei der Bestellung anbieten wollen.'),
		'both' => array('Mit und ohne Login', 'W&auml;hlen Sie diese Option, wenn Sie den Abschluss der Bestellung f&uuml;r angemeldete und nicht angemeldete Besucher erm&ouml;glichen wollen und hierf&uuml;r bei der Bestellung eine entsprechende Login-M&ouml;glichkeit angeboten werden soll.')
	);	
