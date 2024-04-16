<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['id']												=	array('ID');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['artType']										=	array('Art des Produkts', 'Wählen Sie hier, ob Sie ein Produkt oder ein Fremdsprachprodukt anlegen möchten.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['pages']											=	array('Seiten, auf denen das Produkt dargestellt wird','Wählen Sie hier die Seite aus, auf der das Produkt dargestellt wird. Sofern Sie einen mehrsprachigen Shop betreiben, wählen Sie bitte nur die Seiten in der Hauptsprache aus.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['title']											=	array('Produktbezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['alias']											=	array('Alias');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductAlias']								=	array('Produktalias','Wird automatisch ausgefüllt, sofern Sie keine manuelle Eingabe vornehmen');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['description']									=	array('Produktbeschreibung');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['shortDescription']								=	array('Kurzbeschreibung');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['published']										=	array('Veröffentlicht');
    $GLOBALS['TL_LANG']['tl_ls_shop_product']['productTypeCollectiveOrder']						=	array('Sammelkaufprodukt','Das Produkt ist ein Sammelkaufprodukt. Die konfiguration wird in der entsprechenden Lieferanten-Variante vorgenommen.');

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupRestrictions']							=	array('Gruppeneinschränkungen');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['allowedGroups']							        =	array('Erlaubt für folgende Gruppen');

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_1']								=	array('Preisabweichung Gruppe 1');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_2']								=	array('Preisabweichung Gruppe 2');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_3']								=	array('Preisabweichung Gruppe 3');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_4']								=	array('Preisabweichung Gruppe 4');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useGroupPrices_5']								=	array('Preisabweichung Gruppe 5');

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['priceForGroups']									=	array('Gültig für Gruppen');

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPrice']								=	array('Preis in '.($GLOBALS['TL_CONFIG']['ls_shop_currency'] ? $GLOBALS['TL_CONFIG']['ls_shop_currency'] : '<i>&lt;noch keine Währung in den Grundeinstellungen definiert&gt;</i>'), 'Ob Sie hier einen Brutto- oder Nettopreis hinterlegen, hängt von der globalen Shop-Grundeinstellung ab. Falls sie Staffelpreise verwenden, tragen Sie hier bitte auf jeden Fall den Preis für eine Einheit ein.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useScalePrice']									=	array('Staffelpreis anwenden');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceType']									=	array('Art der Staffelpreisangabe');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionMethod']				=	array('Methode zur Mengenermittlung');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceQuantityDetectionAlwaysSeparateConfigurations']	=	array('Unterschiedliche Konfigurationen stets trennen', 'Produkte und Varianten, die mit einem Konfigurator individualisiert wurden, werden nicht mit Produkten und Varianten zusammengefasst, bei denen die Konfiguration abweicht.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePriceKeyword']								=	array('Staffelpreis-Schlüsselwort', 'Geben Sie hier ein Schlüsselwort ein, das zur Zusammenfassung mehrerer Warenkorb-Positionen bei der Mengenermittlung verwendet werden kann.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['scalePrice']										=	array('Staffelpreis (links: ab welcher Menge, rechts: welcher Preis bzw. welche Preisanpassung)');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductPriceOld']							=	array('Alter Preis in '.($GLOBALS['TL_CONFIG']['ls_shop_currency'] ? $GLOBALS['TL_CONFIG']['ls_shop_currency'] : '<i>&lt;noch keine Währung in den Grundeinstellungen definiert&gt;</i>'), 'Ob Sie hier einen Brutto- oder Nettopreis hinterlegen, hängt von der globalen Shop-Grundeinstellung ab.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductSteuersatz']						=	array('Steuersatz', 'Wählen Sie hier den Steuersatz, mit dem dieses Produkt besteuert wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductCode']								=	array('Artikelnummer');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['variationGroupCode']								=	array('Gehört zu Variationsgruppe');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductWeight']							=	array('Gewicht in '.($GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] ? $GLOBALS['TL_CONFIG']['ls_shop_weightUnit'] : '<i>&lt;noch keine Gewichtseinheit in den Grundeinstellungen definiert&gt;</i>'), 'Das Gewicht des Produktes kann z. B. für die Berechnung der Versandkosten von Bedeutung sein.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityUnit']						=	array('Mengeneinheit', 'Tragen Sie hier die Mengeneinheit (z. B. Stk., Liter, lfm etc.) ein');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductQuantityDecimals']					=	array('Nachkommastellen für die Menge', 'Bestimmen Sie hier, wieviele Nachkommastellen für die Menge erlaubt sind. Bei einem Produkt, das in Stück verkauft wird, tragen Sie z. B. "0" ein, damit nur die Bestellung ganzer Stück möglich ist. Bei einem Produkt, das in laufenden Metern verkauft wird, tragen Sie stattdessen z. B. "2" ein, damit auch die Bestellung von z. B. 2,75 lfm ermöglicht wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichUnit']				=	array('Einheit für Mengenvergleichspreis', 'Tragen Sie hier z. B. "100 g" ein, wenn Sie den Preis einer Packung zur besseren Vergleichbarkeit auf 100 g umrechnen und dies auch so darstellen möchten. Sie können den Platzhalter "%s" verwenden, um den berechneten Mengenvergleichspreis einzufügen. So können Sie z. B. eine Angabe wie "entspricht 1,95 EUR pro 100 g" realisieren.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMengenvergleichDivisor']			=	array('Teiler zur Berechnung des Mengenvergleichspreises', 'Geben Sie hier den Teiler ein, mit dem der Mengenvergleichspreis errechnet wird. Sie ermitteln diesen Teiler, in dem Sie die in der Packung enthaltene Menge durch die Vergleichsmenge teilen. Beispiel: Sie haben ein Produkt in einer 275g-Packung und möchten den Mengenvergleichspreis für 100 g darstellen. Geben Sie in diesem Fall "2.75" (275 g / 100 g = 2,75) ein. Möchten Sie den Preis für eine Vergleichsmenge von 1000 g ausgeben, geben Sie bitte "0.275" (275 g / 1000 g = 0,275) ein.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMainImage']							=	array('Produkt-Abbildung', 'Wählen Sie hier das Hauptbild für dieses Produkt aus.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductMoreImages']						=	array('Weitere Bilder', 'Wählen Sie hier weitere Bilder für dieses Produkt aus.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDetailsTemplate']					=	array('Template für Produkt-Detaildarstellung');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsNew']								=	array('Neuheit');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductIsOnSale']							=	array('Sonderangebot');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDeliveryInfoSet']					=	array('Einstellungen zu Lagerbestand/Lieferzeit');
    $GLOBALS['TL_LANG']['tl_ls_shop_product']['availableFrom']				                	=	array('Verfügbar ab');
    $GLOBALS['TL_LANG']['tl_ls_shop_product']['preorderingAllowed']				                =	array('Vorbestellbar', 'Ist diese Option aktiviert, so kann das Produkt, sofern es noch nicht verfügbar ist, vorbestellt werden. Unabhängig von der dem Produkt zugeordneten Einstellung für das Lagerbestands-Handling kann der Lagerbestand bei aktivierter Vorbestellfunktion ins Minus laufen.');
    $GLOBALS['TL_LANG']['tl_ls_shop_product']['deliveryInfoSetToUseInPreorderPhase']            =   array('Lagerbestand/Lieferzeit in Vorbestellungsphase', 'Legen Sie hier abweichende Einstellungen von Lagerbestand und Lieferbedingungen in der Vorbestellungsphase fest.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductDeliveryTime']						=	array('Lieferzeit');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductRecommendedProducts']				=	array('Empfohlene Produkte', 'Wählen Sie hier Produkte aus, die in einem entsprechenden CrossSeller dargestellt werden können.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['associatedProducts']								=	array('Verbundene Produkte', 'Wählen Sie hier Produkte aus, die Sie zur Realisierung individueller Funktionen mit diesem Produkt in Verbindung setzen möchten.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductProducer']							= 	array('Hersteller');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['keywords']										= 	array('Schlüsselwörter');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['pageTitle']										=	array('Seitentitel', 'Tragen Sie hier den Seitentitel ein, der auf der Produktdetailseite verwendet werden soll. Sie können die Bedeutung der Produktdetailseite für Suchmaschinen damit verbessern. Falls Sie nichts eintragen, so wird dem von Contao regulär erstellten Seitentitel die Produktbezeichnung vorangestellt.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['pageDescription']								=	array('Meta-Seitenbeschreibung', 'Tragen Sie hier die Seitenbeschreibung ein, die auf der Produktdetailseite im suchmaschinenrelevanten Meta-Element "description" verwendet werden soll. Möglich sind bis zu 255 Zeichen, empfehlenswert sind maximal 160. Sofern Sie hier etwas eintragen, wird dieser Text unter allen Umständen verwendet. Lassen Sie das Feld leer, so kann je nach Grundeinstellung entweder die Seitenbeschreibung verwendet werden, die Contao regulär erstellt, oder es kommt die normale Produktbeschreibung zum Einsatz.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['sorting']										=	array('Sortierzahl');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['configurator']									=	array('Konfigurator verwenden');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['customizerLogicFile']							=	array('Customizer-Datei anwenden');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['useOldPrice']									=	array('Alten Preis verwenden');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents']									=	array('Flexible Produktinformationen','Hier können Sie beliebig viele Informationen hinterlegen, die in Templates über das Schlüsselwort referenziert und als Produkteigenschaft genutzt werden können.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent']									=	array('Flexible Produktinformationen (sprachunabhängig)','Hier können Sie beliebig viele Informationen hinterlegen, die in Templates über das Schlüsselwort referenziert und als Produkteigenschaft genutzt werden können.');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductAttributesValues'] 					=	array('Zuordnung Merkmal zu Ausprägung','Wählen Sie hier Merkmale und Ausprägungen, welche Sie zuvor unter dem Menüpunkt "Produkt-Merkmale" angelegt haben.');


	/*
	 * Legends
	 */
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProductCode_legend']						= 'Artikelnummer und Alias';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPublishAndState_legend']					= 'Status';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopTitleAndDescriptions_legend']				= 'Bezeichnung und Beschreibungen';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopUnits_legend']							= 'Einheiten (sprachabhängig)';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPages_legend']							= 'Seiten-/Kategorien-Zuordnung';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['groupRestrictions_legend']					    = 'Gruppeneinschränkungen';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopProducer_legend']							= 'Hersteller';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopImages_legend']							= 'Bilder';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopAttributesAndValues_legend']				= 'Merkmale und Ausprägungen';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_legend']							= 'Preis- und Gewichtsangaben';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_1_legend']							= 'Für Gruppe Nr. 1 abweichende Preisangaben';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_2_legend']							= 'Für Gruppe Nr. 2 abweichende Preisangaben';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_3_legend']							= 'Für Gruppe Nr. 3 abweichende Preisangaben';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_4_legend']							= 'Für Gruppe Nr. 4 abweichende Preisangaben';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopPrice_5_legend']							= 'Für Gruppe Nr. 5 abweichende Preisangaben';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopStockDeliveryTimeAndAvailability_legend']							= 'Lagerbestand, Lieferzeit und Verfügbarkeit';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopRecommendedProducts_legend']				= 'Empfohlene Produkte';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['associatedProducts_legend']						= 'Verbundene Produkte';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['lsShopTemplate_legend']							= 'Darstellungstemplate';
	 $GLOBALS['TL_LANG']['tl_ls_shop_product']['configurator_legend']							= 'Konfigurator-Einstellungen';
	
	/*
	 * Options
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['artType']['options'] = array(
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceType'] = array(
		'scalePriceStandalone' => array('Feste Preisangabe', 'Der eingegebene Preis wird direkt verwendet.'),
		'scalePricePercentaged' => array('Prozentuale Anpassung', 'Auf den Basispreis wird die angegebene prozentuale Anpassung angewendet. Bitte stellen Sie dem angegebenen Prozentwert für einen Abschlag ein Minuszeichen voran.'),
		'scalePriceFixedAdjustment' => array('Anpassung mit festem Wert', 'Auf den Basispreis wird die angegebene Anpassung angewendet. Bitte stellen Sie dem angegebenen Wert für einen Abschlag ein Minuszeichen voran.')
	);

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['options']['scalePriceQuantityDetectionMethod'] = array(
		'separatedVariantsAndConfigurations' => array('Produkte, Varianten und Konfigurationen getrennt', 'Jede Warenkorbposition - egal ob sich das Produkt, die Variante oder lediglich die Konfiguration unterscheidet - wird separat gezählt.'),
		'separatedVariants' => array('Produkte und Varianten getrennt', 'Warenkorbpositionen, die sich lediglich in ihrer Konfiguration unterscheiden, werden zusammengefasst. Unterschiedliche Varianten werden aber separat gezählt.'),
		'separatedProducts' => array('Produkte getrennt', 'Unterschiedliche Produkte im Warenkorb werden separat gezählt. Abweichende Varianten oder Konfigurationen werden aber zusammengefasst.'),
		'separatedScalePriceKeywords' => array('Zusammengefasst nach Staffelpreis-Schlüsselwort', 'Warenkorbpositionen, die ein gemeinsames Staffelpreis-Schlüsselwort hinterlegt haben, werden zusammengefasst. Ansonsten findet eine separate Zählung einzelner Warenkorbpositionen statt.')
	);
		
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['new']        = array('Neues Produkt', 'Ein neues Produkt anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['edit']        = array('Varianten bearbeiten', 'Varianten des Produktes mit ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['editheader']  = array('Produkt bearbeiten', 'Produkt mit ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['delete']        = array('Produkt löschen', 'Produkt ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['toggle']        = array('Produkt veröffentlichen', 'Produkt ID %s veröffentlichen');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['copy']        = array('Produkt kopieren', 'Produkt mit ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['show']        = array('Details anzeigen', 'Details des Produktes mit ID %s anzeigen');

	/*
	 * Misc
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['blankOptionLabel'] = 'Standard';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label01'] = 'Schlüsselwort';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contents_label02'] = 'Produktinformation';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent_label01'] = 'Schlüsselwort';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['flex_contentsLanguageIndependent_label02'] = 'Produktinformation';

	$GLOBALS['TL_LANG']['tl_ls_shop_product']['attributesValues_label01'] = 'Merkmal';
	$GLOBALS['TL_LANG']['tl_ls_shop_product']['attributesValues_label02'] = 'Ausprägung';
