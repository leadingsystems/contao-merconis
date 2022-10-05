<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['title']										= array('Bezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['alias']	= array('Alias', 'Eindeutige Bezeichnung, welche zur Referenzierung verwendet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['description']								= array('Beschreibung');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['infoAfterCheckout']							= array('Information nach Bestellabschluss');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['additionalInfo']								= array('Zusätzliche Information (z. B. Zahlungsbedingung)');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['formAdditionalData']							= array('Formular für Kundeneingaben');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['dynamicSteuersatzType']						= array('Dynamischer Steuersatz', 'Wählen Sie, auf welche Art der Steuersatz ggf. dynamisch ausgewählt wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['steuersatz']									= array('Steuersatz', 'Wählen Sie hier den Steuersatz aus, mit dem ggf. anfallende Gebühren für diese Zahlungsoption besteuert werden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type']										= array('Art der Zahlungsoption','Wählen Sie hier die Art der Zahlungsoption aus. Die meisten Zahlungsoptionen (z. B. Nachnahme, Einzugsermächtigung usw.) lassen sich mit der Auswahl "Standard" realisieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['published']									= array('Veröffentlicht','Setzen Sie dieses Häkchen, um diese Zahlungsoption im Shop zur Auswahl anzubieten.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['sorting']									= array('Sortierzahl');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeType']										= array('Art der Gebühren-Berechnung','Bestimmen Sie hier, wie die Gebühren berechnet werden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeValue']									= array('Gebühr');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeAddCouponToValueOfGoods']					= array('Gutscheine in Warenwert einbeziehen', 'Wird bei der Berechnung der Warenwert als Grundlage genommen, so kann mit dieser Checkbox bestimmt werden, ob Gutschein-Werte in den Warenwert mit einbezogen werden sollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeAddShippingToValueOfGoods']				= array('Versandkosten in Warenwert einbeziehen', 'Wird bei der Berechnung der Warenwert als Grundlage genommen, so kann mit dieser Checkbox bestimmt werden, ob die Versandkosten in den Warenwert mit einbezogen werden sollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeFormula']									= array('Formel zur Gebührenberechnung', 'Bitte geben Sie als Dezimaltrennzeichen stets einen Punkt ein. Verfügbare Platzhalter: ##totalValueOfGoods##, ##totalWeightOfGoods##, ##totalValueOfCoupons##, ##shippingFee##. Neben klassischen Berechnungen ist auch die Verwendung ternärer Operatoren möglich, sodass folgendes Beispiel funktioniert: ##totalValueOfGoods## > 300 ? 0 : (##totalWeightOfGoods## <= 10 ? 10 : (##totalWeightOfGoods## <= 20 ? 20 : (##totalWeightOfGoods## <= 30 ? 30 : 40)))');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeFormulaResultConvertToDisplayPrice']		= array('Errechnete Gebühr in Ausgabepreis umwandeln', 'Wählen Sie diese Option, wenn das Ergebnis Ihrer Berechnung ein Netto- oder Bruttopreis entsprechend der definierten Grundeinstellung zur Preiserfassung ist, damit der Preis aus Sicht des Kunden korrekt ausgegeben wird. Wählen Sie diese Option nicht, wenn Sie eine Berechnung z. B. auf Basis des Warenwert-Platzhalters durchführen, da das Ergebnis Ihrer Berechnung dann bereits den aus Kundensicht richtigen Ausgabepreis darstellt.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeWeightValues']								= array('Gebühr nach Gewicht (links: bis zu welchem Gewicht, rechts: welcher Preis)','Definieren Sie hier, bis zu welchem Gewicht welcher Festbetrag für die Zahlung berechnet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feePriceValues']								= array('Gebühr nach Warenwert (links: bis zu welchem Warenwert, rechts: welcher Preis)','Definieren Sie hier, bis zu welchem Warenwert welcher Festbetrag für die Zahlung berechnet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['excludedGroups']								= array('Auszuschließende Gruppen', 'Wählen Sie hier die Gruppen aus, für die diese Zahlungsoption nicht zur Verfügung stehen soll.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimitMin']								= array('Mindestgewicht', 'Tragen Sie hier das Mindestgewicht für die Lieferung ein, ab der diese Zahlungsoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimitMax']								= array('Maximalgewicht', 'Tragen Sie hier das Maximalgewicht für die Lieferung ein, bis zu der diese Zahlungsoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitMin']								= array('Mindestwarenwert', 'Tragen Sie hier den Mindestwarenwert ein, ab dem diese Zahlungsoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitMax']								= array('Maximalwarenwert', 'Tragen Sie hier den Maximalwarenwert ein, bis zu dem diese Zahlungsoption zur Verfügung steht. Geben Sie "0" ein, um diesen Wert zu ignorieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitAddCouponToValueOfGoods']			= array('Gutscheine in Warenwert einbeziehen', 'Mit dieser Checkbox kann bestimmt werden, ob bei der Prüfung des Mindest- bzw. Maximalwarenwerts Gutschein-Werte in den Warenwert mit einbezogen werden sollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimitAddShippingToValueOfGoods']		= array('Versandkosten in Warenwert einbeziehen', 'Mit dieser Checkbox kann bestimmt werden, ob bei der Prüfung des Mindest- bzw. Maximalwarenwerts Versandkosten in den Warenwert mit einbezogen werden sollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countries']									= array('Länder-Auswahl', 'Bitte geben Sie die Länder als kommagetrennte Liste an.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countriesAsBlacklist']						= array('Länder-Auswahl als Blacklist interpretieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['cssID']										= array('CSS-ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['cssClass']									= array('CSS-Klasse');
	
	/*
	 * PayPal-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalAPIUsername']							= array('PayPal-API-Benutzername', 'Bitte tragen Sie hier den Wert ein, den Sie in Ihrem PayPal-Konto erfahren, wenn Sie sich dort Ihre API-Zugangsdaten anzeigen lassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalAPIPassword']							= array('PayPal-API-Passwort', 'Bitte tragen Sie hier den Wert ein, den Sie in Ihrem PayPal-Konto erfahren, wenn Sie sich dort Ihre API-Zugangsdaten anzeigen lassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalAPISignature']							= array('PayPal-API-Unterschrift', 'Bitte tragen Sie hier den Wert ein, den Sie in Ihrem PayPal-Konto erfahren, wenn Sie sich dort Ihre API-Zugangsdaten anzeigen lassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalSecondForm']							= array('Formular nach PayPal-Autentifizierung', 'Bitte wählen Sie hier das Formular, das dargestellt werden soll, um nach der ersten Autentifizierung erneut zu PayPal zu gelangen, um dort evtl. Angaben zu ändern.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalGiropayRedirectForm']					= array('Formular zur Giropay-/Banktransfer-Weiterleitung', 'Bitte wählen Sie hier das Formular, das nach Abschluss der Bestellung dargestellt werden soll, um zur Fertigstellung der PayPal-Bezahlung per Giropay/Banktransfer weiterzuleiten.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalGiropaySuccessPages']					= array('Seite nach erfolgreicher Giropay-Zahlung', 'Wählen Sie hier die Seite aus, die nach einer erfolgreichen Giropay-Zahlung angezeigt werden soll. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalGiropayCancelPages']					= array('Seite nach Giropay-Abbruch', 'Wählen Sie hier die Seite aus, die nach einem eventuellen Abbruch einer Giropay-Zahlung angezeigt werden soll. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalBanktransferPendingPages']				= array('Seite nach Zahlung per Banktransfer', 'Wählen Sie hier die Seite aus, die nach einer Zahlung per Banktransfer angezeigt werden soll. Falls Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte für jede Sprache die entsprechende Seite aus.');
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameFirstname']				= array('Feldname in Checkout-Formular für "Vorname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameLastname']				= array('Feldname in Checkout-Formular für "Nachname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameStreet']					= array('Feldname in Checkout-Formular für "Straße"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameCity']					= array('Feldname in Checkout-Formular für "Stadt"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNamePostal']					= array('Feldname in Checkout-Formular für "PLZ"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameState']					= array('Feldname in Checkout-Formular für "Bundesland/Region"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShipToFieldNameCountryCode']			= array('Feldname in Checkout-Formular für "Land"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalLiveMode']								= array('Live-Modus', 'Bitte aktivieren Sie diese Checkbox, wenn sich Ihr PayPal-Zahlungsmodul im Echtbetrieb befinden soll. Bitte beachten Sie, dass die API-Zugangsdaten, die Sie diesem Zahlungsmodul hinterlegen, unterschiedlich sind, abhängig vom Live- oder Sandbox-Modus. Ist diese Checkbox nicht aktiviert, so kommuniziert das Zahlungsmodul mit der PayPal-Sandbox, einer Testumgebung, bei der keine echten Zahlungen stattfinden. Der Sandbox-Modus sollte für erste Tests mit diesem Zahlungsmodul verwendet werden, wenn sichergestellt ist, dass echte Kunden in dieser Zeit diese Zahlungsoption nicht verwenden können.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypalShowItems']								= array('Einzelpositionen an PayPal übermitteln', 'Aktivieren Sie diese Checkbox, wenn Sie möchten, dass Informationen über die einzelnen Positionen dieser Bestellung an PayPal übermittelt werden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['paypal_legend']								= 'PayPal-Einstellungen';

    /*
    * PayPalCheckout-Bezeichnungen
    */
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_legend']                      = 'PayPal-Checkout-Einstellungen';
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_clientID']                    = array('PayPal Checkout Client ID', 'Bitte tragen Sie hier den Wert ein, den Sie in Ihrem PayPal-Konto erfahren, wenn Sie sich dort Ihre API-Zugangsdaten anzeigen lassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_clientSecret']                = array('PayPal Checkout Client Secret', 'Bitte tragen Sie hier den Wert ein, den Sie in Ihrem PayPal-Konto erfahren, wenn Sie sich dort Ihre API-Zugangsdaten anzeigen lassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_liveMode']                    = array('Live-Modus', 'Bitte aktivieren Sie diese Checkbox, wenn sich Ihr PayPal-Zahlungsmodul im Echtbetrieb befinden soll. Bitte beachten Sie, dass die API-Zugangsdaten, die Sie diesem Zahlungsmodul hinterlegen, unterschiedlich sind, abhängig vom Live- oder Sandbox-Modus. Ist diese Checkbox nicht aktiviert, so kommuniziert das Zahlungsmodul mit der PayPal-Sandbox, einer Testumgebung, bei der keine echten Zahlungen stattfinden. Der Sandbox-Modus sollte für erste Tests mit diesem Zahlungsmodul verwendet werden, wenn sichergestellt ist, dass echte Kunden in dieser Zeit diese Zahlungsoption nicht verwenden können.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_logMode']						= array('Logging', 'Bitte stellen Sie ein, ob ein Log-File in /system/logs geschrieben werden soll und welcher Log-Level verwendet werden soll.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameFirstname']				= array('Feldname in Checkout-Formular für "Vorname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameLastname']				= array('Feldname in Checkout-Formular für "Nachname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameStreet']					= array('Feldname in Checkout-Formular für "Straße"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameCity']					= array('Feldname in Checkout-Formular für "Stadt"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNamePostal']					= array('Feldname in Checkout-Formular für "PLZ"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameState']					= array('Feldname in Checkout-Formular für "Bundesland/Region" (Platzhalter möglich)', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Der Platzhalter "##value::feldname##" ermöglicht es, einen dynamischen Feldnamen in Abhängigkeit eines in einem anderen Formularfeld vorliegenden Wertes anzugeben. Beispiel: Die Eingabe "province_##value::country##" ergibt den Feldnamen "province_us", sofern im Feld "country" als Land "USA" ausgewählt ist, oder "province_ca", sofern als Land Kanada ausgewählt ist. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNameCountryCode']			= array('Feldname in Checkout-Formular für "Land"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalCheckout_shipToFieldNamePhone']			= array('Feldname in Checkout-Formular für "Telefon"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');

    /*
     * PayPalPlus-Bezeichnungen
     */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_legend']						= 'PayPal-Plus-Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_clientID']							= array('PayPal Plus Client ID', 'Bitte tragen Sie hier den Wert ein, den Sie in Ihrem PayPal-Konto erfahren, wenn Sie sich dort Ihre API-Zugangsdaten anzeigen lassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_clientSecret']						= array('PayPal Plus Client Secret', 'Bitte tragen Sie hier den Wert ein, den Sie in Ihrem PayPal-Konto erfahren, wenn Sie sich dort Ihre API-Zugangsdaten anzeigen lassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_liveMode']						= array('Live-Modus', 'Bitte aktivieren Sie diese Checkbox, wenn sich Ihr PayPal-Zahlungsmodul im Echtbetrieb befinden soll. Bitte beachten Sie, dass die API-Zugangsdaten, die Sie diesem Zahlungsmodul hinterlegen, unterschiedlich sind, abhängig vom Live- oder Sandbox-Modus. Ist diese Checkbox nicht aktiviert, so kommuniziert das Zahlungsmodul mit der PayPal-Sandbox, einer Testumgebung, bei der keine echten Zahlungen stattfinden. Der Sandbox-Modus sollte für erste Tests mit diesem Zahlungsmodul verwendet werden, wenn sichergestellt ist, dass echte Kunden in dieser Zeit diese Zahlungsoption nicht verwenden können.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_logMode']						= array('Logging', 'Bitte stellen Sie ein, ob ein Log-File in /system/logs geschrieben werden soll und welcher Log-Level verwendet werden soll.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameFirstname']				= array('Feldname in Checkout-Formular für "Vorname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameLastname']				= array('Feldname in Checkout-Formular für "Nachname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameStreet']					= array('Feldname in Checkout-Formular für "Straße"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameCity']					= array('Feldname in Checkout-Formular für "Stadt"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNamePostal']					= array('Feldname in Checkout-Formular für "PLZ"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameState']					= array('Feldname in Checkout-Formular für "Bundesland/Region" (Platzhalter möglich)', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Der Platzhalter "##value::feldname##" ermöglicht es, einen dynamischen Feldnamen in Abhängigkeit eines in einem anderen Formularfeld vorliegenden Wertes anzugeben. Beispiel: Die Eingabe "province_##value::country##" ergibt den Feldnamen "province_us", sofern im Feld "country" als Land "USA" ausgewählt ist, oder "province_ca", sofern als Land Kanada ausgewählt ist. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNameCountryCode']			= array('Feldname in Checkout-Formular für "Land"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payPalPlus_shipToFieldNamePhone']			= array('Feldname in Checkout-Formular für "Telefon"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Werden diese Werte nicht korrekt an PayPal übermittelt, können Sie unter Umständen verschiedene Service-Optionen von PayPal nicht in Anspruch nehmen. Bitte achten Sie in diesem Zusammenhang auch darauf, im Checkout-Formular sinnvolle Pflichtfelder zu definieren. Achten Sie bitte zusätzlich darauf, nur von PayPal für die jeweiligen Felder akzeptierte Werte zuzulassen.');

	/*
	 * PayOne-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_legend']						= 'PAYONE-Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_subaccountId']						= array('PAYONE Sub-Account-ID', 'Bitte tragen Sie hier den Wert ein, den Sie im PAYONE Merchant Interface im Bereich "API-Parameter" Ihres Zahlungsportals vom Typ "Shop" erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_portalId']							= array('PAYONE Portal-ID', 'Bitte tragen Sie hier den Wert ein, den Sie im PAYONE Merchant Interface im Bereich "API-Parameter" Ihres Zahlungsportals vom Typ "Shop" erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_key']							= array('PAYONE Portal-Key', 'Bitte tragen Sie hier den Wert ein, den Sie im PAYONE Merchant Interface im Bereich "API-Parameter" Ihres Zahlungsportals vom Typ "Shop" erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_liveMode']						= array('Live Modus', 'Bitte setzen Sie das Häkchen, wenn Sie nach Abschluss der Testphase echte Zahlungen mit diesem Modul durchführen wollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_clearingtype']	= array('Abwicklungsart', 'Bitte wählen Sie die von PAYONE zu nutzende Abwicklungsart');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_clearingtype']['options'] = array(
		'elv' => 'Lastschriftverfahren',
		'cc' => 'Kreditkarte',
		'vor' => 'Vorauszahlung',
		'rec' => 'Rechnung',
		'sb' => 'Online-Überweisung',
		'wlt' => 'E-wallet',
		'fnc' => 'Finanzierung'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameFirstname']	= array('Feldname in Checkout-Formular für "Vorname" (Payone-Parameter "firstname")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameLastname']	= array('Feldname in Checkout-Formular für "Nachname" (Payone-Parameter "lastname")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameCompany']	= array('Feldname in Checkout-Formular für "Firma" (Payone-Parameter "company")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameStreet']	= array('Feldname in Checkout-Formular für "Straße" (Payone-Parameter "street")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameAddressaddition']	= array('Feldname in Checkout-Formular für "Adresszusatz" (Payone-Parameter "addressaddition")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameZip']	= array('Feldname in Checkout-Formular für "PLZ" (Payone-Parameter "zip")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameCity']	= array('Feldname in Checkout-Formular für "Stadt" (Payone-Parameter "city")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameCountry']	= array('Feldname in Checkout-Formular für "Land" (Payone-Parameter "country")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameEmail']	= array('Feldname in Checkout-Formular für "E-Mail" (Payone-Parameter "email")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameTelephonenumber']	= array('Feldname in Checkout-Formular für "Telefon" (Payone-Parameter "telephonenumber")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameBirthday']	= array('Feldname in Checkout-Formular für "Geburtstag" (Payone-Parameter "birthday")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNameGender']	= array('Feldname in Checkout-Formular für "Geschlecht" (Payone-Parameter "gender")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['payone_fieldNamePersonalid']	= array('Feldname in Checkout-Formular für "Personen-ID" (Payone-Parameter "personalid")', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Bitte beachten Sie die Dokumentation der Payone-Frontend-Plattform, um mehr über die benötigten Felder und erlaubte Werte zu erfahren.');

	/*
	 * VR-Pay-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_legend'] = 'VR-Pay-Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_userId'] = array('User-ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_password'] = array('Passwort');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_token'] = array('Token');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_entityId'] = array('Entity-ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_liveMode'] = array('Live-Betrieb');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_testMode'] = array('Test-Modus');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_testMode']['options'] = array(
		'INTERNAL' => 'INTERNAL (VR-Pay-Simulatoren)',
		'EXTERNAL' => 'EXTERNAL (Sandboxes der Zahlungsmittel-Anbieter)'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_paymentInstrument'] = array('Zahlungsmittel');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_paymentInstrument']['options'] = array(
		'creditcard' => 'Kreditkarte',
		'giropay' => 'Giropay',
		'paydirekt' => 'Paydirekt',
		'klarna_invoice' => 'Klarna Rechnung',
		'directdebit_sepa' => 'SEPA Lastschrift',
		'sofortueberweisung' => 'Sofortüberweisung',
		'paypal' => 'Pay Pal',
		'easycredit_ratenkauf' => 'Ratenkauf by easyCredit'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_creditCardBrands'] = array('Akzeptierte Kreditkarten');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_creditCardBrands']['options']		= array(
		'AMEX' => 'AMEX',
		'DINERS' => 'DINERS',
		'JCB' => 'JCB',
		'MASTERCARD' => 'MASTER',
		'VISA' => 'VISA'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_street1']	= array('Feldname in Checkout-Formular für "Straße"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, im Formular dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_city']	= array('Feldname in Checkout-Formular für "Ort"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, im Formular dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_postcode']	= array('Feldname in Checkout-Formular für "PLZ"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, im Formular dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_country']	= array('Feldname in Checkout-Formular für "Land"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, im Formular dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_givenName']	= array('Feldname in Checkout-Formular für "Vorname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, im Formular dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['vrpay_fieldName_surname']	= array('Feldname in Checkout-Formular für "Nachname"', 'Tragen Sie hier den Feldnamen des entsprechenden Eingabefelds im Checkout-Formular ein. Sofern Sie Felder für eine alternative Versandanschrift verwenden, achten Sie bitte darauf, im Formular dieselben Feldnamen für korrespondierende Felder mit der am Ende angehängten Zeichenfolge "_alternative" zu verwenden.');


	/*
	 * Saferpay-Bezeichnungen
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_legend']						= 'SAFERPAY-Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_username']						= array('Benutzername');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_password']						= array('Passwort');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_customerId']					= array('Kunden-ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_terminalId']					= array('Terminal-ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_merchantEmail']					= array('E-Mail-Adresse des Händlers');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_liveMode']						= array('Live Modus', 'Bitte setzen Sie das Häkchen, wenn Sie nach Abschluss der Testphase echte Zahlungen mit diesem Modul durchführen wollen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_captureInstantly']				= array('Sofort verbuchen, wenn möglich');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_paymentMethods']				= array('Zahlungsarten', 'Bitte wählen Sie die von SAFERPAY zu nutzenden Zahlungsarten');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_paymentMethods']['options']		= array(
		'AMEX' => 'AMEX',
		'BONUS' => 'BONUS',
		'DINERS' => 'DINERS',
		'DIRECTDEBIT' => 'DIRECTDEBIT',
		'EPRZELEWY' => 'EPRZELEWY',
		'EPS' => 'EPS',
		'GIROPAY' => 'GIROPAY',
		'IDEAL' => 'IDEAL',
		'INVOICE' => 'INVOICE',
		'JCB' => 'JCB',
		'MAESTRO' => 'MAESTRO',
		'MASTERCARD' => 'MASTERCARD',
		'MYONE' => 'MYONE',
		'PAYPAL' => 'PAYPAL',
		'POSTCARD' => 'POSTCARD',
		'POSTFINANCE' => 'POSTFINANCE',
		'SAFERPAYTEST' => 'SAFERPAYTEST',
		'SOFORT' => 'SOFORT',
		'VISA' => 'VISA',
		'VPAY' => 'VPAY',
		'TWINT' => 'TWINT'
	);
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_wallets']				= array('Wallets', 'Bitte wählen Sie die von SAFERPAY zu nutzenden Wallets');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['saferpay_wallets']['options']		= array(
		'MASTERPASS' => 'MASTERPASS'
	);
	
	/*
	 * sofortueberweisung
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['sofortueberweisungConfigkey']				= array('"Sofort." Konfigurationsschlüssel', 'Bitte tragen Sie hier den Wert ein, der Ihnen in Ihrem Benutzerkonto bei "Sofort." als Konfigurationsschlüssel Ihres Projektes angezeigt wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['sofortueberweisungUseCustomerProtection']	= array('Käuferschutz verwenden (mit SOFORT-Bankkonto)', 'Bestimmen Sie, ob diese Zahlungsart den Käuferschutz von "Sofort." nutzen soll. Bitte beachten Sie, dass Sie diese Option nur wählen dürfen, wenn Sie ein SOFORT-Bankkonto besitzen und ggf. weitere Rahmenbedingungen zutreffen. Klären Sie Ihre Berechtigung, den Käuferschutz anzubieten, im Zweifel mit dem Payment Provider.');
	
	/*
	 * Santander WebQuick
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickVendorNumber']				= array('Santander WebQuick Händlernummer');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickVendorPassword']	= array('Santander WebQuick Passwort');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickLiveMode']	= array('Santander WebQuick im Live-Modus betreiben');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickMinAge'] = array('benötigtes Mindestalter');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameSalutation']	= array('Feldname im Checkoutformular für "Anrede"', 'Bitte beachten Sie, dass Feld nur die Werte "Herr" und "Frau" liefern darf. Die Übergabe dieser Information an Santander ist optional. Lassen Sie das Feld einfach leer, wenn Sie diese Information von Ihrem Kunden nicht einholen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameFirstName']	= array('Feldname im Checkoutformular für "Vorname"', 'Sie müssen diese Information von Ihrem Kunden einholen und an Santander übergeben, tragen Sie hier deshalb bitte auf jeden Fall den entsprechenden Feldnamen ein.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameLastName']	= array('Feldname im Checkoutformular für "Nachname"', 'Sie müssen diese Information von Ihrem Kunden einholen und an Santander übergeben, tragen Sie hier deshalb bitte auf jeden Fall den entsprechenden Feldnamen ein.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameEmailAddress']	= array('Feldname im Checkoutformular für "E-Mail-Adresse"', 'Die Übergabe dieser Information an Santander ist optional. Lassen Sie das Feld einfach leer, wenn Sie diese Information von Ihrem Kunden nicht einholen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameStreet']	= array('Feldname im Checkoutformular für "Straße"', 'Die Übergabe dieser Information an Santander ist optional. Lassen Sie das Feld einfach leer, wenn Sie diese Information von Ihrem Kunden nicht einholen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameCity']	= array('Feldname im Checkoutformular für "Stadt"', 'Die Übergabe dieser Information an Santander ist optional. Lassen Sie das Feld einfach leer, wenn Sie diese Information von Ihrem Kunden nicht einholen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameZipCode']	= array('Feldname im Checkoutformular für "PLZ"', 'Die Übergabe dieser Information an Santander ist optional. Lassen Sie das Feld einfach leer, wenn Sie diese Information von Ihrem Kunden nicht einholen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['santanderWebQuickFieldNameCountry']	= array('Feldname im Checkoutformular für "Land"', 'Die Übergabe dieser Information an Santander ist optional. Lassen Sie das Feld einfach leer, wenn Sie diese Information von Ihrem Kunden nicht einholen.');
	
	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['title_legend']			= 'Bezeichnung';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['steuersatz_legend']	= 'Steuersatz';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['afterCheckout_legend']			= 'Nach Bestellabschluss';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type_legend']				= 'Art der Zahlungsoption';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['published_legend']		= 'Veröffentlichen';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['fee_legend']				= 'Gebühren';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['excludedGroups_legend']	= 'Gruppenbezogene Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['weightLimit_legend']		= 'Gewichtsbeschränkungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['priceLimit_legend']		= 'Beschränkungen des Warenwerts';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['countryLimit_legend']		= 'Beschränkungen der erlaubten Länder';
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['misc_legend']		= 'Sonstiges';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['new']        = array('Neue Zahlungsoption', 'Eine neue Zahlungsoption anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['edit']        = array('Zahlungsoption bearbeiten', 'Zahlungsoption ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['delete']        = array('Zahlungsoption löschen', 'Zahlungsoption ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['copy']        = array('Zahlungsoption kopieren', 'Zahlungsoption ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['show']        = array('Details anzeigen', 'Details der Zahlungsoption ID %s anzeigen');
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['feeType']['options'] = array(
		'none' => array('Keine Berechnung (kostenlos)'),
		'fixed' => array('Festbetrag', 'Wählen Sie diese Option, wenn Sie die Gebühr als Festbetrag definieren möchten.'),
		'percentaged' => array('Prozentual', 'Wählen Sie diese Option, wenn Sie die Gebühr als prozentualen Wert definieren möchten.'),
		'weight' => array('nach Gewicht', 'Wählen Sie diese Option, wenn Sie die Gebühr als Festbetrag abhängig vom Gesamtgewicht der bestellten Ware definieren möchten.'),
		'price' => array('nach Warenwert', 'Wählen Sie diese Option, wenn Sie die Gebühr als Festbetrag abhängig vom Wert der bestellten Ware definieren möchten.'),
		'weightAndPrice' => array('nach Gewicht und Warenwert', 'Wählen Sie diese Option, wenn Sie die Gebühr als Festbetrag abhängig vom Gewicht und dem Wert der bestellten Ware definieren möchten. Bitte beachten Sie, dass die Gebühren, die Sie im Folgenden nach Gewicht und Preis angeben, entsprechend addiert werden.'),
		'formula' => array('Berechnungsformel', 'Definieren Sie eine Berechnungsformel, in der Sie verschiedene Platzhalter benutzen können.')
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['dynamicSteuersatzType']['options'] = array(
		'none' => 'keine Dynamik',
		'main' => 'der Hauptleistung folgen',
		'max' => 'der höchste verwendete',
		'min' => 'der niedrigste verwendete'
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_payment_methods']['type']['options'] = array(
		'Standard' => array('Standard', 'Die meisten Zahlungsoptionen, die Sie selbst abwickeln, wie z. B. "Rechnung", "Nachnahme" und "Einzugsermächtigung", lassen sich mit diesem Standardmodul realisieren.'),
		'PayPal' => array('Zahlung per PayPal', 'Wählen Sie diese Option, wenn Sie mit diesem Zahlungsmodul die Bezahlung per PayPal ermöglichen möchten. Bitte beachten Sie, dass Sie hierfür bei PayPal registriert sein müssen und die von PayPal zur Verfügung gestellten API-Zugangsdaten benötigen.'),
		'Sofortueberweisung' => array('Vorauskasse per "Sofort."'),
		'Santander WebQuick' => array('Finanzierung mit Santander'),
		'PayPal Plus' => array('Zahlung per PayPal Plus'),
		'PAYONE' => array('Zahlung per PAYONE'),
		'SAFERPAY' => array('Zahlung per Saferpay'),
		'VR Pay' => array('Zahlung per VR Pay')
	);
