<?php

/*
 * Fields
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['title']										= array('Bezeichnung');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['text01']										= array('Textbereich 1');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['text02']										= array('Textbereich 2');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['template']									= array('Darstellungstemplate');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackCrossSeller']						= array('Alternativer CrossSeller (optional)', 'Wählen Sie hier optional einen CrossSeller aus, der als Alternative dargestellt werden soll, wenn der aktuelle CrossSeller keine darstellbaren Produkte enthält.');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackOutput']								= array('Alternative Ausgabe (optional)', 'Definieren Sie hier optional eine alternative Ausgabe, falls der aktuelle CrossSeller keine darstellbaren Produkte enthält und Sie auch keinen alternativen CrossSeller für diesen Fall festgelegt haben.');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productDirectSelection']						= array('Produkt-Auswahl');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType']						= array('Art der Produkt-Auswahl');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate']									= array('Verwenden', 'Aktivieren, um das Suchkriterium zu verwenden');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['published']									= array('Aktiv');

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['maxNumProducts']								= array('Maximale Anzahl an Produkten', 'Bestimmen Sie, wieviele Produkte maximal für die Darstellung in diesem CrossSeller berücksichtigt werden. Geben Sie "0" ein, um alle passenden Produkte auszugeben.');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['noOutputIfMoreThanMaxResults']					= array('Keine Ausgabe bei Überschreiten');

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionNewProduct']					= array('Neues Produkt');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionSpecialPrice']				= array('Sonderpreis');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionCategory']					= array('Seite/Kategorie');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionProducer']					= array('Hersteller', 'Folgende Platzhalter stehen Ihnen zur Verfügung, um Eigenschaften eines in der Produktdetailansicht geöffneten Produktes in der Suche zu verwenden: "{{currentProduct_name}}" (Produktbezeichnung), "{{currentProduct_articleNr}}" (Artikelnummer), "{{currentProduct_producer}}" (Hersteller)');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionProductName']					= array('Produktbezeichnung', 'Folgende Platzhalter stehen Ihnen zur Verfügung, um Eigenschaften eines in der Produktdetailansicht geöffneten Produktes in der Suche zu verwenden: "{{currentProduct_name}}" (Produktbezeichnung), "{{currentProduct_articleNr}}" (Artikelnummer), "{{currentProduct_producer}}" (Hersteller)');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionArticleNr']					= array('Artikelnummer', 'Folgende Platzhalter stehen Ihnen zur Verfügung, um Eigenschaften eines in der Produktdetailansicht geöffneten Produktes in der Suche zu verwenden: "{{currentProduct_name}}" (Produktbezeichnung), "{{currentProduct_articleNr}}" (Artikelnummer), "{{currentProduct_producer}}" (Hersteller)');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionTags']						= array('Suchbegriffe', 'Folgende Platzhalter stehen Ihnen zur Verfügung, um Eigenschaften eines in der Produktdetailansicht geöffneten Produktes in der Suche zu verwenden: "{{currentProduct_name}}" (Produktbezeichnung), "{{currentProduct_articleNr}}" (Artikelnummer), "{{currentProduct_producer}}" (Hersteller)');

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['canBeFiltered']								= array('Kann gefiltert werden');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['doNotUseCrossSellerOutputDefinitions']		= array('Übersichts-Produktdarstellung verwenden', 'Anstatt der CrossSeller-Einstellungen der Darstellungsvorgabe die Einstellungen für Produktübersichten verwenden');

/*
 * Misc
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionNewProduct'] = 'Suchkriterium "Neues Produkt"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionSpecialPrice'] = 'Suchkriterium "Sonderpreis"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionCategory'] = 'Suchkriterium "Seite/Kategorie"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['subHeadlineSearchSelectionCategory'] = 'Bitte beachten Sie, dass die aktuell aufgerufene Seite standardmäßig auf die Suche angewendet wird, wenn Sie dieses Suchkriterium aktivieren, aber nicht explizit eine Seiten-/Kategorieauswahl treffen.';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionProducer'] = 'Suchkriterium "Hersteller"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionProductName'] = 'Suchkriterium "Produktbezeichnung"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionArticleNr'] = 'Suchkriterium "Artikelnummer"';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionTags'] = 'Suchkriterium "Suchbegriffe"';

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['title_legend']   = 'Bezeichnung';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['textOutput_legend']   = 'Text-Ausgaben';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['template_legend']   = 'Darstellung';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackCrossSeller_legend']   = 'Alternativer CrossSeller/Alternative Ausgabe';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType_legend']   = 'Art der Produkt-Auswahl';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['directSelection_legend']   = 'Einfache Produkt-Auswahl';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelection_legend']   = 'Produkt-Auswahl aufgrund detaillierter Suche';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['lastSeen_legend']   = 'Zuletzt gesehene Produkte';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['frontendProductSearch_legend']   = 'Frontend-Produktsuche';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['recommendedProducts_legend']   = 'Empfohlene Produkte';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['filterSettings_legend'] = 'Filter-Verhalten';
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['published_legend'] = 'Aktivierung';

/*
 * References
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType']['options'] = array(
    'hookSelection' => array('Individuell über Hook', 'Mit dieser Auswahl-Art können Sie die tatsächliche Produktauswahl mittels des Hook "crossSellerHookSelection" vornehmen.'),
    'directSelection' => array('Direkte Produktauswahl', 'Mit dieser Auswahl-Art können Sie die gewünschten Produkte direkt bestimmen.'),
    'searchSelection' => array('Produkt-Suche', 'Die im CrossSeller darzustellenden Produkte werden anhand vorgegebener Suchkriterien ermittelt.'),
    'lastSeen' => array('Zuletzt gesehene Produkte', 'Im CrossSeller werden die zuletzt gesehenen Produkte dargestellt.'),
    'favorites' => array('Favoriten/Merkliste', 'Im CrossSeller werden die Produkte dargestellt, die der Besucher seinen Favoriten/seiner Merkliste hinzugefügt hat.'),
    'restockInfoList' => array('Produkte mit aktiver Wiederverfügbarkeitsbenachrichtigung', 'Im CrossSeller werden die Produkte dargestellt, für die der Besucher die Wiederverfügbarkeitsbenachrichtigung aktiviert hat.'),
    'recommendedProducts' => array('Empfohlene Produkte', 'Im CrossSeller werden Produkte dargestellt, die Sie dem in der Detailansicht dargestellten Produkt als "empfohlene Produkte" zugeordnet haben.'),
    'frontendProductSearch' => array('Frontend-Produktsuche', 'Im CrossSeller wird das Suchergebnis einer im Frontend ausgeführten Produktsuche dargestellt.')
);

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionNewProduct']['options'] = array(
    'new' => array('Produkt ist als neu markiert'),
    'notNew' => array('Produkt ist nicht als neu markiert')
);

$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionSpecialPrice']['options'] = array(
    'specialPrice' => array('Produkt ist als Sonderpreis markiert'),
    'noSpecialPrice' => array('Produkt ist nicht als Sonderpreis markiert')
);


/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['new']        = array('Neuer CrossSeller', 'Einen neuen CrossSeller anlegen');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['edit']        = array('CrossSeller bearbeiten', 'CrossSeller ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['delete']        = array('CrossSeller löschen', 'CrossSeller ID %s löschen');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['copy']        = array('CrossSeller kopieren', 'CrossSeller ID %s kopieren');
$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['show']        = array('Details anzeigen', 'Details des CrossSeller ID %s anzeigen');
