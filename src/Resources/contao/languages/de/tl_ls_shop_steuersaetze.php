<?php

	/*
	 * Fields
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['title']										= array('Bezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['alias']										= array('Alias', 'Eindeutige Bezeichnung, welche zur Referenzierung verwendet wird. Dieses Feld kann freigelassen werden, der passende Wert wird dann automatisch eingetragen.');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerProzentPeriod1']						= array('Steuersatz/Steuerzone (links Steuersatz in Prozent, rechts Länder der Steuerzone)', 'Bitte geben Sie die Länder der Steuerzone als kommagetrennte Liste zweistelliger Länderkennungen (ISO-3166-1-Kodierliste) an. Lassen Sie das rechte Feld (Länder der Steuerzone) frei, so gilt der festgelegte Prozentwert für alle Kunden, deren Land nicht mit einem explizit in einer Steuerzone definierten Land übereinstimmt.');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['startPeriod1']								= array('Gültigkeit ab', 'Geben Sie hier das Datum an, ab dem dieser Prozentwert für diesen Steuersatz gilt (ab 0 Uhr des entsprechenden Tages)');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['stopPeriod1']								= array('Gültigkeit bis', 'Geben Sie hier das Datum an, bis zu dem dieser Prozentwert für diesen Steuersatz gilt (bis 23:59 Uhr des entsprechenden Tages)');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerProzentPeriod2']						= array('Steuersatz/Steuerzone (links Steuersatz in Prozent, rechts Länder der Steuerzone)', 'Bitte geben Sie die Länder der Steuerzone als kommagetrennte Liste zweistelliger Länderkennungen (ISO-3166-1-Kodierliste) an.');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['startPeriod2']								= array('Gültigkeit ab', 'Geben Sie hier das Datum an, ab dem dieser Prozentwert für diesen Steuersatz gilt (ab 0 Uhr des entsprechenden Tages)');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['stopPeriod2']								= array('Gültigkeit bis', 'Geben Sie hier das Datum an, bis zu dem dieser Prozentwert für diesen Steuersatz gilt (bis 23:59 Uhr des entsprechenden Tages)');

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['title_legend']   = 'Bezeichnung';
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerPeriod1_legend']   = 'Gültigkeitszeitraum 1';
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['steuerPeriod2_legend']   = 'Gültigkeitszeitraum 2';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['new']        = array('Neuer Steuersatz', 'Einen neuen Steuersatz anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['edit']        = array('Steuersatz bearbeiten', 'Steuersatz ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['delete']        = array('Steuersatz löschen', 'Steuersatz ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['copy']        = array('Steuersatz kopieren', 'Steuersatz ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['show']        = array('Details anzeigen', 'Details des Steuersatz ID %s anzeigen');
	
	/*
	 * Misc
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['wildcardNotAllowed'] = 'Da Sie diesen Steuersatz bereits mindestens einem Produkt zugeordnet haben, können Sie keine dynamischen Werte verwenden.';


    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStopDatePeriod1'] = 'Für Gültigkeitsraum 1 wurde ein Startdatum, aber kein Enddatum eingegeben.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStartDatePeriod1'] = 'Für Gültigkeitsraum 1 wurde ein Enddatum, aber kein Startdatum eingegeben.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_stopDateBeforeStartDatePeriod1'] = 'Das Enddatum von Gültigkeitsraum 1 darf nicht vor dem Startdatum liegen.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStopDatePeriod2'] = 'Für Gültigkeitsraum 2 wurde ein Startdatum, aber kein Enddatum eingegeben.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_missingStartDatePeriod2'] = 'Für Gültigkeitsraum 2 wurde ein Enddatum, aber kein Startdatum eingegeben.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_stopDateBeforeStartDatePeriod2'] = 'Das Enddatum von Gültigkeitsraum 2 darf nicht vor dem Startdatum liegen.';
    $GLOBALS['TL_LANG']['tl_ls_shop_steuersaetze']['err_periodsOverlap'] = 'Die Zeiträume für Gültigkeitsraum 1 und Gültigkeitsraum 2 dürfen sich nicht überschneiden.';
