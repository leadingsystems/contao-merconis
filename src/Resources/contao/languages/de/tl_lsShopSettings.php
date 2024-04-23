<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_country']					= array('Land, in dem der Shop betrieben wird', 'Tragen Sie hier die zweistellige Länderkennung (ISO-3166-1-Kodierliste) des Landes ein, in dem der Shop betrieben wird.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_serial']					= array('Seriennummer', 'Bitte tragen Sie hier die Seriennummer ein, die Sie beim Kauf der Software erhalten haben.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownVATID']				= array('Eigene USt-IdNr.', 'Sofern sie über eine USt-IdNr. verfügen, tragen Sie diese hier bitte ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_euCountrycodes']			= array('EU-Länder', 'Tragen Sie hier die zweistelligen Länderkennungen (ISO-3166-1-Kodierliste) der EU-Staaten ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currency']				= array('Währung', 'Tragen Sie hier die Währung ein, wie Sie überall im Shop verwendet werden soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_currencyCode']			= array('Währungs-Code gemäß ISO-4217', 'Tragen Sie hier für die von Ihnen verwendete Währung den dreistelligen Währungs-Code gemäß ISO-4217 ein (z. B. "EUR" für Euro, "GBP" für britische Pfund oder "USD" für den US-Dollar.)');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_weightUnit']				= array('Gewichtseinheit', 'Tragen Sie hier die Einheit ein, in der Sie Gewichte im Shop angeben. Gewichtsangaben werden im Falle einer Versandkostenberechnung nach Gewicht benötigt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_quantityDefault']			= array('Vorbelegung für Mengen-Eingabefeld');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimals']				= array('Anzahl der Dezimalstellen bei Preisangaben', 'Geben Sie hier an, mit wievielen Dezimalstellen Preise im Shop dargestellt werden sollen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor']		= array('Rundungsmodell für Preise (z. B. Rappenrundung)', 'Bitte wählen Sie hier das Rundungsmodell, mit dem alle Preise in Merconis sowohl in der Preisausgabe als auch in der Kalkulation gerundet werden. Z. B. für Schweizer Händler ist hier in den meisten Fällen die Auswahl der Rappenrundung zu empfehlen. Falls beim Verkauf von Kleinteilen in sehr großer Menge positionsbezogen eine höhere Genauigkeit benötigt wird, so kann hier die Hundertstel-Rundung eingestellt und lediglich in den Templates der ausgegebene Rechnungsbetrag und ggf. noch die ausgewiesene Steuer z. B. in 0,05er-Schritten gerundet werden. Bitte beachten Sie, dass Payment-Provider teilweise eine exakte Kalkulationsprüfung vornehmen und es nicht akzeptieren würden, wenn die kumulierten Einzelpositionen aufgrund von Rundungsungenauigkeiten nicht exakt dem Rechnungsbetrag entsprechen. Um die Ablehnung von Zahlungen durch Payment Provider zu vermeiden, arbeitet Merconis deshalb im Falle von templateseitig gerundeten Ausgabebeträgen intern weiterhin mit der ursprünglichen Genauigkeit.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numDecimalsWeight']		= array('Anzahl der Dezimalstellen bei Gewichtsangaben', 'Geben Sie hier an, mit wievielen Dezimalstellen Gewichtsangaben im Shop dargestellt werden sollen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType']				= array('Art der eingegebenen Preise brutto/netto', 'WICHTIG: Geben Sie hier an, ob Sie in der Verwaltungsoberfläche überall Brutto- oder Netto-Preise angeben. Wenn Sie grenzüberschreitend Produkte verkaufen, dann hinterlegen Sie Ihre Preise zwingend netto. Hinweis zur Frontend-Ausgabe: Ob Sie im Frontend Brutto oder Netto ausgeben, bestimmen Sie innerhalb der Contao-Mitgliedergruppe(n).');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardGroup']			= array('Standard-Benutzergruppe', 'Einige Shop-Einstellungen werden für unterschiedliche Kunden- bzw. Mitgliedergruppen separat eingestellt. Definieren Sie hier, welcher dieser Gruppen ein nicht angemeldeter Kunde zugeordnet werden soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout']			= array('Abschluss der Bestellung mit oder ohne Login', 'Bestimmen Sie hier, ob der Abschluss der Bestellung mit oder ohne Login möglich sein soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_autoSelectCheapestPossibleShippingAndPaymentMethods'] = array('Günstigste Zahlungs- und Versandoption automatisch wählen', 'Wählen Sie diese Checkbox, wenn Sie möchten, dass die günstigste Zahlungs- und Versandoption automatisch ausgewählt werden soll, sofern der Kunde noch keine Auswahl getroffen hat oder eine bereits getroffene Auswahl durch Änderungen der Bestellung nicht mehr möglich ist. Die automatische Auswahl bewirkt, dass der Kunde die Bestellung unter Verwendung der günstigsten Zahlungs- und Versandoption direkt abschließen kann, ohne diese Auswahl selbst noch treffen zu müssen. Sofern Sie diese Möglichkeit nicht nutzen, wird die günstigste Zahlungs- und Versandoption als Vorschlag in der Kalkulation berücksichtigt, der Abschluss der Bestellung ist aber nur möglich, wenn der Kunde seine Auswahl noch selbst vornimmt. Bitte prüfen Sie, ob rechtliche Vorgaben in Ihrem Land eventuell gegen die Verwendung dieser Einstellung sprechen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useProductDescriptionAsSeoDescription'] = array('Produktbeschreibung als Meta-Description', 'Wählen Sie diese Checkbox, wenn Sie möchten, dass die Beschreibung eines Produktes als Meta-Description verwendet werden soll. Sofern vorhanden, wird die Kurzbeschreibung verwendet, ansonsten die normale Beschreibung. Ist keine der beiden Beschreibungen für ein Produkt vorhanden, so wird die normale Page-Description von Contao verwendet. Bitte beachten Sie: Ist dem Produkt eine eigene Seitenbeschreibung ausdrücklich hinterlegt, so findet diese unabhängig von dieser Einstellung auf jeden Fall Anwendung.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_loginModuleID'] 			= array('Login-Modul zur Verwendung beim Bestellabschluss');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_miniCartModuleID'] 		= array('Mini-Warenkorb-Modul', 'Das Modul, welches per AJAX aktualisiert werden soll');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useVATIDValidation']		= array('Online-Prüfung der USt-IdNr. verwenden');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_useAjaxForPaginationAndSorting'] = array('Ajax für Paginierung und Sortierung verwenden', 'Bei der Darstellung von Produktlisten wird die Ajax-Technik verwendet, um die Produktliste bei der Verwendung der Paginierung und Sortierung nachzuladen, ohne dabei die gesamte Seite neu laden zu müssen');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_VATIDValidationSOAPOptions'] = array('Optionen für SOAP-Client', 'Bitte ändern Sie die Standardeinstellungen nur, wenn Sie genau wissen, was Sie tun. Fehlerhafte Einstellungen können dazu führen, dass die Validierung der USt-IdNr deutlich verzögert wird. Bitte beachten Sie, dass es abhängig von Ihrer Serverkonfiguration auch möglich ist, dass die Standardeinstellungen nicht optimal sind, und zu einer Verzögerung führen. Testen Sie in diesem Fall bitte, ob mit Ihrer Serverkonfiguration das Entfernen aller Options-Angaben eine Validierung ohne Verzögerung ermöglicht.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateOverview'] = array('Backend-Template für Bestellübersicht');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_beOrderTemplateDetails'] = array('Backend-Template für Bestellungsdetails');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrCounter']			= array('Aktueller Zählerstand');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrString']			= array('Bestellnummer-Format', 'Hier können Sie definieren, in welchem Format die Bestellnummer ausgegeben/angezeigt werden soll. Verwenden Sie den Platzhalter {{counter}}, um den Zählerstand einzufügen. Einen vorgenullten Zählerstand können Sie einfügen, indem Sie den Platzhalter z. B. als {{counter:7}} angeben, wobei nach dem Doppelpunkt die Gesamtanzahl der Stellen angegeben wird, mit welcher der Zähler ausgegeben werden soll. Bei Bedarf steht Ihnen auch der Platzhalter {{date:}} zur Verfügung, um eine variable Datumsangabe automatisch einzufügen. Hinter dem Doppelpunkt können Sie jede beliebige Datumsangabe in der Syntax der PHP-Funktion "date()" notieren. Beispiel: "{{date:Y}}-{{counter}}" ergibt für die 147. Bestellung im Jahr 2012 die Best.-Nr. "2012-147".');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrStart']			= array('Startwert des Zählers ({{counter}})', 'Bestimmen Sie hier, bei welchem Wert der Zähler ({{counter}}) beginnen soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle']		= array('Rücksetzung des Zählers ({{counter}})', 'Definieren Sie bei Bedarf, in welchem Rhythmus der Zähler ({{counter}}) zurückgesetzt werden soll.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartNow']		= array('Zähler ({{counter}}) sofort zurücksetzen', 'Aktivieren Sie diese Option, wenn Sie möchten, dass der Zähler ({{counter}}) beim Speichern Ihrer Einstellungen sofort auf den Startwert zurückgesetzt werden soll.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues01']		= array('Statusmöglichkeiten für Status 1', 'Tragen Sie hier bitte die möglichen Werte für diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status für eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht benötigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues02']		= array('Statusmöglichkeiten für Status 2', 'Tragen Sie hier bitte die möglichen Werte für diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status für eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht benötigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues03']		= array('Statusmöglichkeiten für Status 3', 'Tragen Sie hier bitte die möglichen Werte für diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status für eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht benötigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues04']		= array('Statusmöglichkeiten für Status 4', 'Tragen Sie hier bitte die möglichen Werte für diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status für eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht benötigen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderStatusValues05']		= array('Statusmöglichkeiten für Status 5', 'Tragen Sie hier bitte die möglichen Werte für diese Status-Art als kommagetrennte Liste ein. Der an erster Stelle angegebene Wert wird als Default-Status für eine neue Bestellung verwendet. Lassen Sie das Feld leer, wenn Sie eine Status-Art nicht benötigen.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_shippingInfoPages']		= array('Seite "Versandbedingungen"', 'Geben Sie hier die Seite an, auf der Sie Ihre Versandbedingungen erläutern. Diese Seite wird bei der Produktdarstellung verlinkt. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_collectivePurchasePages']		= array('Seite "Laufende Sammelkäufe"', 'Geben Sie hier die Seite an wo die laufenden Sammeläufe abgebildet werden sollen');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_cartPages']				= array('Seite "Warenkorb"', 'Wählen Sie hier die Seite aus, die das Modul "Warenkorb" enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_reviewPages'] = array('Seite "Kasse - Prüfung der Bestellung"', 'Wählen Sie hier die Seite aus, die das Modul "Prüfung der Bestellung" enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_signUpPages']			= array('Seite "Registrierung"', 'Wählen Sie hier die Seite aus, die das Modul "Registrierung" enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutPaymentErrorPages']	= array('Seite "Fehler bei Verarbeitung der Zahlungsoption"', 'Wählen Sie hier die Seite aus, die die Fehlermeldung bei missglückter Verarbeitung der Zahlungsoption enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutShippingErrorPages']	= array('Seite "Fehler bei Verarbeitung der Versandoption"', 'Wählen Sie hier die Seite aus, die die Fehlermeldung bei missglückter Verarbeitung der Versandoption enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_checkoutFinishPages']	= array('Seite "Bestell-Abschluss"', 'Wählen Sie hier die Seite aus, die das Modul "Bestell-Abschluss" enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_paymentAfterCheckoutPages']		= array('Seite "Bezahlung nach Bestell-Abschluss"', 'Wählen Sie hier die Seite aus, die das Modul "Bezahlung nach Bestellabschluss" enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_afterCheckoutPages']		= array('Seite "Informationen nach Bestell-Abschluss"', 'Wählen Sie hier die Seite aus, die das Modul "Informationen nach Bestellabschluss" enthält. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ajaxPages']			=  array('Seite "AJAX"', 'Wählen Sie hier die Seite aus, die für AJAX-Anfragen verwendet wird. Diese Seite sollte ausschließlich die relevanten MERCONIS-Module enthalten. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchResultPages'] = array('Seite "Ergebnisse der Produktsuche"', 'Wählen Sie hier die Seite aus, auf der die Ergebnisse einer Produktsuche dargestellt werden. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrdersPages'] = array('Seite "Meine Bestellungen"', 'Wählen Sie hier die Seite aus, in der das Modul "Meine Bestellungen" eingebunden ist. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_myOrderDetailsPages'] = array('Seite "Meine Bestellungen - Details"', 'Wählen Sie hier die Seite aus, in der das Modul "Meine Bestellungen - Details" eingebunden ist. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_videoDummyCover']	= array('Default-Cover für Videos', 'Wählen Sie hier eine Grafik aus, die als Coverbild für Videos verwendet werden soll, wenn keine zum Video passende Cover-Grafik ermittelt werden kann.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_systemImages_noProductImage']	= array('Standardbild für Produkte', 'Wählen Sie hier eine Grafik aus, die verwendet wird wenn kein Bild für ein Produkt ermittelt werden kann.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMainImage']			= array('Produkt-Hauptbilder: Bildbreite und Bildhöhe', 'Bitte stellen Sie hier die Bildbreite und Bildhöhe für die Darstellung von Produkt-Hauptbildern ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMoreImages']			= array('Weitere Produkt-Bilder: Bildbreite und Bildhöhe', 'Bitte stellen Sie hier die Bildbreite und Bildhöhe für die Darstellung weiterer Produkt-Bilder ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_moreImagesMargin']			= array('Weitere Produkt-Bilder: Bildabstand', 'Bitte stellen Sie hier den oberen, rechten, unteren und linken Bildabstand für die Darstellung weiterer Produkt-Bilder ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_imagesFullsize']			= array('Großansicht/Neues Fenster','Produkt-Bilder bei Klick als Großansicht in einer Lightbox oder einem neuen Fenster öffnen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_moreImagesSortBy']			= array('Sortieren nach','Bitte bestimmen Sie hier die Sortierreihenfolge');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_sizeMainImage02']			= array('Produkt-Bild in Galerie: Bildbreite und Bildhöhe', 'Bitte stellen Sie hier die Bildbreite und Bildhöhe für die Darstellung von Produkt-Bildern in der Galerie-Ansicht ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemWidth']			= array('Breite eines einzelnen Produktbereichs');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemHeight']		= array('Höhe eines einzelnen Produktbereichs');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_galleryItemMargin']		= array('Außenabstand eines einzelnen Produktbereichs', 'Bitte stellen Sie hier den oberen, rechten, unteren und linken Abstand für einen einzelnen Produktbereich ein.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType']			= array('Versandkosten inkl./zzgl.', 'Bestimmen Sie hier, ob Sie Ihre Preise inklusive oder zuzüglich Versandkosten angeben.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_output_definitionset']	= array('Standard-Darstellungsvorgabe', 'Bitte wählen Sie hier die Darstellungsvorgabe, die standardmäßig verwendet wird.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_delivery_infoSet']		= array('Standardeinstellung für Lagerbestand/Lieferzeit', 'Bitte wählen Sie hier die Vorgabe für Lagerbestand und Lieferzeit, die standardmäßig verwendet wird.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_productDetailsTemplate'] =	array('Standard-Template für Produkt-Detaildarstellung');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageFolder'] =	array('Standardordner für Produktbilder', 'Definieren Sie hier den Ordner, in dem der Shop anhand der Artikelnummern automatisch nach passenden Produktabbildungen suchen soll. Erkannt werden Bilder, deren Dateiname mit der Artikelnummer gefolgt von einem Trennzeichen (siehe nächstes Eingabefeld) beginnt (z. B. 1234_bildname.jpg) oder deren Dateiname lediglich die Artikelnummer enthält (z. B. 123.jpg)');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImageDelimiter'] =	array('Trennzeichen für Artikelnummer in Produktbildern', 'Bestimmen Sie hier, welches Trennzeichen die Artikelnummer in einem Bildnamen vom restlichen Dateinamen abgrenzt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection'] = array('Standardsortierung für Produktbilder');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_standardProductImportFolder'] =	array('Standardordner für Import-Dateien', 'Definieren Sie hier den Ordner, in den die Importfunktion eine zu importierende Datei nach dem Upload ablegt.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvDelimiter'] = array('CSV-Trennzeichen');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEnclosure'] = array('CSV-Feldbegrenzungszeichen');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvEscape'] = array('CSV-Maskierungszeichen');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importCsvLocale'] = array('CSV-Spracheinstellung');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_numMaxImportRecordsPerRound'] = array('Anzahl Datensätze für Import-Splitting', 'Um bei sehr großen Importen keine sehr lange Laufzeit einzelner PHP-Skripte und dadurch entsprechend hohe Server-Limits zu erfordern, wird der Import in Einzelschritte geteilt. Definieren Sie hier, wieviele Datensätze auf einmal verarbeitet werden können.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeWidth'] = array('Bildausgabe-Breite', 'Breite der LiveHits-Bildausgabe in px');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitImageSizeHeight'] = array('Bildausgabe-Höhe', 'Höhe der LiveHits-Bildausgabe in px');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMaxNumHits'] = array('Maximale Anzahl übermittelter Hits');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_liveHitsMinLengthSearchTerm'] = array('Mindestlänge des Suchbegriffs');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeys'] = array('Import-Spalten für flexible Produktinformationen','Geben Sie hier als kommagetrennte Liste die Spaltenüberschriften der Felder an, die als flexible Produktinformationen importiert werden sollen. Bitte beachten Sie, dass die Spaltenüberschriften als Schlüsselworte für die flexiblen Produktinformationen verwendet werden.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_importFlexFieldKeysLanguageIndependent'] = array('Import-Spalten für flexible Produktinform. (sprachunabhängig)','Geben Sie hier als kommagetrennte Liste die Spaltenüberschriften der Felder an, die als flexible Produktinformationen importiert werden sollen. Bitte beachten Sie, dass die Spaltenüberschriften als Schlüsselworte für die flexiblen Produktinformationen verwendet werden.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ownEmailAddress'] = array('Eigene E-Mail-Adresse', 'Wird zum Empfang diverser Systemmeldungen verwendet.');
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_maxNumParallelSearchCaches'] = array('Maximale Anzahl parallel möglicher Suchcaches');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchCacheLifetimeSec'] = array('Lebensdauer der Suchcaches in Sekunden');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_considerGroupPricesInFilterAndSorting'] = array('Gruppenpreise für Filterung und Sortierung berücksichtigen','Bitte deaktivieren Sie diese Option aus Performancegründen, wenn Sie keinem Produkt bzw. keiner Variante abweichende Gruppenpreise hinterlegt haben.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ignoreGroupRestrictionsInSearch'] = array('Gruppeneinschränkungen in Suche ignorieren', 'Gibt es keine Produkte mit Gruppeneinschränkungen, so kann es die Suchperformance verbessern, wenn dieses Suchkriterium vollständig ignoriert wird. Falls diese Einstellung gewählt wird, es aber doch Produkte mit Gruppeneinschränkungen gibt, so werden diese Produkte gefunden aber dennoch nicht dargestellt. Stattdessen entstehen Lücken in ausgegebenen Produktlisten.');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_alwaysAddIdToAliasDuringProductImport'] = array('Bei Produkt-Import Alias immer mit ID ergänzen', 'Mit dieser Einstellung wird beim Produkt-Import auf die Eindeutigkeitsprüfung von Aliasen verzichtet und damit ein deutlicher Performance-Vorteil erreicht. Die Eindeutigkeit wird hierbei durch das automatische Anhängen der internen Produkt-ID an den Alias erreicht.');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_title'] = array('Produktbezeichnung: Ganzer Suchtext entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_keywords'] = array('Schlüsselwörter: Ganzer Suchtext entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_shortDescription'] = array('Kurzbeschreibung: Ganzer Suchtext entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_description'] = array('Beschreibung: Ganzer Suchtext entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_productCode'] = array('Artikelnummer: Ganzer Suchtext entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesWholeField_producer'] = array('Hersteller: Ganzer Suchtext entspricht komplettem Feldwert');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_title'] = array('Produktbezeichnung: Ganzer Suchtext in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_keywords'] = array('Schlüsselwörter: Ganzer Suchtext in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_shortDescription'] = array('Kurzbeschreibung: Ganzer Suchtext in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_description'] = array('Beschreibung: Ganzer Suchtext in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_productCode'] = array('Artikelnummer: Ganzer Suchtext in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_wholeSearchStringMatchesPartialField_producer'] = array('Hersteller: Ganzer Suchtext in Feldwert enthalten');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_title'] = array('Produktbezeichnung: Einzelner Suchbegriff entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_keywords'] = array('Schlüsselwörter: Einzelner Suchbegriff entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_shortDescription'] = array('Kurzbeschreibung: Einzelner Suchbegriff entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_description'] = array('Beschreibung: Einzelner Suchbegriff entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_productCode'] = array('Artikelnummer: Einzelner Suchbegriff entspricht komplettem Feldwert');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesWholeField_producer'] = array('Hersteller: Einzelner Suchbegriff entspricht komplettem Feldwert');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_title'] = array('Produktbezeichnung: Einzelner Suchbegriff in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_keywords'] = array('Schlüsselwörter: Einzelner Suchbegriff in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_shortDescription'] = array('Kurzbeschreibung: Einzelner Suchbegriff in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_description'] = array('Beschreibung: Einzelner Suchbegriff in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_productCode'] = array('Artikelnummer: Einzelner Suchbegriff in Feldwert enthalten');
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchWeighting_partialSearchStringMatchesPartialField_producer'] = array('Hersteller: Einzelner Suchbegriff in Feldwert enthalten');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_ipWhitelist'] = array('IP-Whitelist', 'Tragen Sie hier kommagetrennt alle IPs ein, denen Sie den Aufruf Ihres Systems zur Übermittlung z. B. von Zahlungsinformationen ohne Referer-Check erlauben wollen.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_urlWhitelist'] = array('URL-Whitelist', 'Tragen Sie hier einen regulären Ausdruck ein, der als Suchmuster auf die Request-URL angewandt wird. Bei einem Treffer in der URL wird der Referer-Check übergangen.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_blnCompatMode2-1-4'] = array('Kompatibilitätsmodus für Updates von Versionen < 2.1.5', 'Mit der Version 2.1.5 hat sich die Dateiablagestruktur teilweise verändert. Dieser Kompatibilitätsmodus veranlasst Merconis dazu, die alte Dateiablagestruktur anstatt der neuen zu erwarten.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_sortingCharacterTranslationTable'] = array('Ersetzungstabelle für Sortierung', 'Um die Berücksichtigung von Sonderzeichen bei der Sortierung zu kontrollieren, können Sie hier definieren, mit welchem Zeichen bzw. welcher Zeichenfolge Sonderzeichen bei der Sortierung ersetzt werden.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_dcaNamesWithoutMultilanguageSupport'] = array('Bei Mehrsprachinitialisierung auszulassende DCAs', 'Kommagetrennte Liste von DCA-Namen, die bei der Mehrsprachinitialisierung übersprungen werden sollen.');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsDebugMode'] = array('Debug-Modus');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lsjsNoMinifierMode'] = array('Komprimierung deaktivieren');

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssFileToLoad'] = array('Zu ladende SCSS-Datei', 'Standardmäßig wird eine von Merconis mitgelieferte SCSS-Datei verwendet und es muss/soll hier keine andere Datei ausgewählt werden. Eine abweichende Auswahl ist nur nötig, wenn z. B. aufgrund eigener Erweiterungen zusätzliche/geänderte Styles eingesetzt werden sollen. Achtung: Durch die Auswahl einer eigenen SCSS-Datei wird die von Merconis standardmäßig mitgelieferte Datei nicht mehr geladen. Es ist daher sinnvoll, die eigene Datei als Kopie der Merconis-Original-Datei zu erstellen und dann zu ergänzen/ändern.');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssDebugMode'] = array('Debug-Modus');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssNoCacheMode'] = array('Caching deaktivieren');
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_lscssNoMinifierMode'] = array('Komprimierung deaktivieren');

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_coupon_debug'] = array('Debug Modus für Coupon', 'Schaltet die Debug anzeige im Frontend an');


    /*
     * Legends
     */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['basic_legend']   = 'Grundlegende Angaben';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['euSettings_legend'] = 'Einstellungen bzgl. der Europäischen Union';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['userSettings_legend']   = 'Benutzerbezogene Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['pageSettings_legend']   = 'Seiten-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['systemImages_legend']   = 'Systembilder';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['orderNr_legend']   = 'Bestellnummer';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['systemSettings_legend']   = 'System-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['liveHits_legend']   = 'Einstellungen für MERCONIS LiveHits';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['orderStatusTypes_legend'] = 'Statusmöglichkeiten';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['productPresentationTemplate01_legend'] = 'Einstellungen für Darstellung in Produkt-Detailansicht';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['productPresentationTemplate02_legend'] = 'Einstellungen für Darstellung in Galerie-Ansicht';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['performanceSettings_legend'] = 'Performance-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['productSearchSettings_legend'] = 'Treffer-Gewichtung bei Produktsuche';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['importSettings_legend'] = 'Import-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['compatSettings_legend'] = 'Kompatibilitäts-Einstellungen';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ipWhitelist_legend'] = 'Whitelist für Referer-Prüfung';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['backendLsjs_legend'] = 'Einstellungen für LSJS im Backend';

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['backendLscss_legend'] = 'Einstellungen für LSCSS im Backend';

	$GLOBALS['TL_LANG']['tl_lsShopSettings']['misc_legend'] = 'Erweiterte Einstellungen';

    $GLOBALS['TL_LANG']['tl_lsShopSettings']['debug_menu'] = 'Debug Menü';


	/*
	 * References
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['edit'] = 'Die grundlegenden Shop-Einstellungen bearbeiten';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['name_asc']  = 'Dateiname (aufsteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['name_desc'] = 'Dateiname (absteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['date_asc']  = 'Datum (aufsteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['date_desc'] = 'Datum (absteigend)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['meta']      = 'Meta Datei (meta.txt)';
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['random']    = 'Zufällige Reihenfolge';
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_orderNrRestartCycle']['options'] = array(
		'never' => 'nie',
		'year' => 'neues Jahr',
		'month' => 'neuer Monat',
		'week' => 'neue Woche',
		'day' => 'neuer Tag'	
	);
	

	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceRoundingFactor']['options'] = array(
		'100' => array('0,01er-Schritte', 'Rundung in 0,01er-Schritten (Standard für die meisten Währungen)'),
		'20' => array('0,05er-Schritte (Rappenrundung)', 'Rundung in 0,05er-Schritten (z. B. Rappenrundung in der Schweiz)')
	);
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_imageSortingStandardDirection']['options'] = array(
		'name_asc' => 'Dateiname (aufsteigend)',
		'name_desc' => 'Dateiname (absteigend)',
		'date_asc' => 'Datum (aufsteigend)',
		'date_desc' => 'Datum (absteigend)',
		'random' => 'Zufällige Reihenfolge'
	);
	
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_priceType']['options'] = array(
		'brutto' => array('Brutto-Preise','Wählen Sie diese Option, wenn Sie Ihren Produkten Brutto-Preise hinterlegen'),
		'netto' => array('Netto-Preise','Wählen Sie diese Option, wenn Sie Ihren Produkten Netto-Preise hinterlegen')
	);
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_versandkostenType']['options'] = array(
		'incl' => array('inklusive','Wählen Sie diese Option, wenn Sie Ihren Produkten Preise inklusive Versandkosten hinterlegen. Ein entsprechender Hinweis wird bei der Preisdarstellung ausgegeben.'),
		'excl' => array('zuzüglich','Wählen Sie diese Option, wenn Sie Ihren Produkten Preise zuzüglich Versandkosten hinterlegen. Ein entsprechender Hinweis wird bei der Preisdarstellung ausgegeben.')
	);
	$GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_allowCheckout']['options'] = array(
		'withLogin' => array('Mit Login', 'Wählen Sie diese Option, wenn Sie den Abschluss der Bestellung nur für angemeldete Besucher ermöglichen wollen.'),
		'withoutLogin' => array('Ohne Login', 'Wählen Sie diese Option, wenn Sie den Abschluss der Bestellung für nicht angemeldete Besucher ermöglichen und auch keine Login-Möglichkeit bei der Bestellung anbieten wollen.'),
		'both' => array('Mit und ohne Login', 'Wählen Sie diese Option, wenn Sie den Abschluss der Bestellung für angemeldete und nicht angemeldete Besucher ermöglichen wollen und hierfür bei der Bestellung eine entsprechende Login-Möglichkeit angeboten werden soll.')
	);
    $GLOBALS['TL_LANG']['tl_lsShopSettings']['ls_shop_searchType']['options'] = array(
        'And-Search' => 'Finde alle Wörter',
        'Or-Search' => 'Finde irgendein Wort'
    );


