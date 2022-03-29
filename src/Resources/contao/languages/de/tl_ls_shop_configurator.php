<?php

	/*
	 * Fields
	 */

	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['alias']										= array('Alias', 'Eindeutige Bezeichnung, welche zur Referenzierung verwendet wird.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['title']										= array('Bezeichnung');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form']										= array('Formular');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['template']									= array('Template');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['startWithDataEntryMode']					= array('Im Datenerfassungsmodus beginnen', 'Diese Einstellung bestimmt, ob der Konfigurator bei seinem ersten Aufruf für ein Produkt auch dann zunächst das Formular zeigt, wenn das Produkt auch ohne erfolgte Dateneingabe bestellt werden kann. Hat das Konfigurator-Formular Pflichtfelder, so wird es zwangsläufig dargestellt, solange die Dateneingabe noch nicht erfolgt ist.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['stayInDataEntryMode']						= array('Im Datenerfassungsmodus bleiben', 'Mit dieser Einstellung bleibt der Konfigurator auch nach dem Abschicken des Formulars im Datenerfassungsmodus.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['skipStandardFormValidation']				= array('Keine Standard-Formular-Validierung', 'Mit dieser Einstellung wird auf standardmäßige Formular-Validierung verzichtet. Die Validierung der im Konfigurator erfassten Daten erfolgt dann ausschließlich über eine in der Verarbeitungslogik-Datei programmierte "customValidator"-Funktion. Ist eine solche Funktion nicht definiert, so gelten die erfassten Daten grundsätzlich als valide. Sinnvoll ist diese Einstellung, wenn Sie das Formular in der Verarbeitungslogik-Datei z. B. mittels der dort verfügbaren Formular-Hooks manipulieren und dadurch einen Zustand herbeiführen, indem die Standard-Formular-Validierung kein korrektes Ergebnis ermitteln kann.');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['customLogicFile']							= array('Datei mit eigener Verarbeitungslogik', 'Bitte geben Sie hier bei Bedarf die Datei an, die das Programm mit Ihrer eigenen Verarbeitungslogik enthält.');

	/*
	 * Misc
	 */
    $GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form_blankOptionLabel']                     = 'Konfigurator ohne Contao-Formular nutzen (fortgeschritten)';

	/*
	 * Legends
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['title_legend']   = 'Bezeichnung und Alias';
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['form_legend']   = 'Formular';
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['template_legend']   = 'Template';
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['customLogic_legend'] = 'Eigene Verarbeitungslogik';
	
	/*
	 * Buttons
	 */
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['new']        = array('Neuer Konfigurator', 'Einen neuen Produkt-Konfigurator anlegen');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['edit']        = array('Konfigurator bearbeiten', 'Produkt-Konfigurator ID %s bearbeiten');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['delete']        = array('Konfigurator löschen', 'Produkt-Konfigurator ID %s löschen');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['copy']        = array('Konfigurator kopieren', 'Produkt-Konfigurator ID %s kopieren');
	$GLOBALS['TL_LANG']['tl_ls_shop_configurator']['show']        = array('Details anzeigen', 'Details des Produkt-Konfigurators ID %s anzeigen');
