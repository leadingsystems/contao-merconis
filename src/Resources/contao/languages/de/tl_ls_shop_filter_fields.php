<?php

	/*
	 * Fields
	 */

	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['alias']								= array('Alias', 'Eindeutige Bezeichnung, welche zur Referenzierung verwendet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['title']								= array('Bezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource']							= array('Datenquelle', 'Geben Sie an, woher das Filter-Feld seine Werte bezieht.');
    $GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['flexContentLIKey']                     = array('Flex-Content-Key', 'Geben Sie den Key eines sprachunabhängigen FlexContent-Feldes an, von dem das Filter-Feld seine Werte bezieht.');
    $GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['flexContentLDKey']                     = array('Flex-Content-Key LD', 'Geben Sie den Key eines sprachabhängigen FlexContent-Feldes an, von dem das Filter-Feld seine Werte bezieht.');
    $GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['flexContentLIMinMaxKey']               = array('Flex-Content-Key LI MinMax', 'Geben Sie den Key eines sprachunabhängigen FlexContent-MinMax-Feldes an, von dem das Filter-Feld seine Werte bezieht.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['sourceAttribute']						= array('Quellmerkmal');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['classForFilterFormField']				= array('CSS-Klasse', 'Diese CSS-Klasse wird im Filter-Formular-Feld verwendet.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['numItemsInReducedMode']				= array('Anzahl dargestellter Werte im "Reduziert-Modus"', 'Tragen Sie 0 ein, um im "Reduziert-Modus" alle Werte darzustellen oder, sofern einzelne Werte als "wichtig" gekennzeichnet sind, alle "wichtigen"');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterFormFieldType']					= array('Art des Auswahlfeldes');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['priority']								= array('Priorität', 'Die Priorität wird als Sortierkriterium für die Ausgabe der Felder im Filter-Formular verwendet. Felder mit höherer Priorität werden über Feldern mit niederer Priorität angezeigt.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterMode']							= array('Filter-Modus', 'Bestimmen Sie, mit welcher logischen Verknüpfung mehrere gewählte Filter-Optionen interpretiert werden.');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['makeFilterModeUserAdjustable']			= array('Einstellung des Filter-Modus im Frontend ermöglichen');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUse']						= array('Template');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForPriceField']           = array('Template');
    $GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForFlexContentLIField']     = array('Template');
    $GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForFlexContentLDField']     = array('Template');
    $GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForFlexContentLIMinMaxField']     = array('Template');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['published']							= array('Aktiv');
    $GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['templateToUseForRangeField']           = array('Template');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['title_legend']   = 'Bezeichnung und Alias';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['output_legend'] = 'Ausgabe-Einstellungen';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource_legend'] = 'Datenquelle';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['published_legend'] = 'Aktivierung';
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterLogic_legend'] = 'Filter-Logik';
	
	/*
	 * Reference
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterFormFieldType']['options'] = array(
		'checkbox' => 'Checkbox-Menü',
		'radio' => 'Radio-Menü'
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['filterMode']['options'] = array(
		'and' => 'und',
		'or' => 'oder'
	);
	
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['dataSource']['options'] = array(
		'attribute' => array('Produktmerkmal', 'Die Ausprägungen des als Datenquelle gewählten Merkmals werden verwendet. Legen Sie in diesem Fall keine Feldwerte als Kinddatensätze dieses Filter-Felds an.'),
		'producer' => array('Hersteller', 'Die Hersteller, die Sie Ihren Produkten hinterlegt haben, werden als Feldwerte verwendet. Legen Sie Datensätze mit übereinstimmenden Werten an, um einzelne Feldwerte sortieren, priorisieren und mit individuellen Klassen versehen zu können.'),
		'price' => array('Preis', 'Es werden Felder zur Eingabe eines Minimal- und Maximalpreises ausgegeben. Legen Sie in diesem Fall keine Feldwerte als Kinddatensätze dieses Filter-Felds an.'),
        'flexContentLI' => array('Flexible Produktinformation (sprachunabhängig)', 'Flexible Produktinformationen (FlexContents) können für die Filterung herangezogen werden. Legen Sie in diesem Fall keine Feldwerte als Kinddatensätze dieses Filter-Felds an.'),
        'flexContentLD' => array('Flexible Produktinformation (sprachabhängig)', 'Flexible Produktinformationen (FlexContents) können für die Filterung herangezogen werden. Legen Sie in diesem Fall keine Feldwerte als Kinddatensätze dieses Filter-Felds an.'),
        'flexContentLIMinMax' => array('Bereich: Flexible Produktinformation (sprachunabhängig)', 'Flexible Produktinformationen (FlexContents) können für die Filterung herangezogen werden. Legen Sie in diesem Fall keine Feldwerte als Kinddatensätze dieses Filter-Felds an.'),
        'attributesMinMax' => array('Bereich: Produktmerkmal', 'Die Ausprägungen des als Datenquelle gewählten Merkmals werden verwendet. Legen Sie in diesem Fall keine Feldwerte als Kinddatensätze dieses Filter-Felds an.')
	);
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['new']        = array('Neues Feld', 'Ein neues Filter-Feld anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['edit']        = array('Feld bearbeiten', 'Filter-Feld ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['children'] = array('Feld bearbeiten', 'Feld mit ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['delete']        = array('Feld löschen', 'Filter-Feld ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['copy']        = array('Feld kopieren', 'Filter-Feld ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_filter_fields']['show']        = array('Details anzeigen', 'Details des Filter-Felds ID %s anzeigen');
