<?php

namespace Merconis\Core;

use Contao\Database;
use Contao\FormModel;
use Contao\System;
use Contao\Widget;

class ls_shop_configuratorController
{
	/*
	 * Diese Funktion wird durch einen Hook nach der Verarbeitung von Formulardaten aufgerufen und sofort wieder abgebrochen,
	 * wenn kein POST-Key "configurator_productVariantID" vorliegt. (es würde sich dann
	 * also um die Verarbeitung eines Formulars handeln, das den Konfigurator nichts angeht).
	 * 
	 * Die Funktion schreibt dann die empfangenen Daten in die Session, damit die passende Konfigurator-Instanz die Daten beziehen kann.
	 */
	public function ls_shop_configuratorProcessFormData($arrSubmitted, $arrForm) {
		$GLOBALS['merconis_globals']['configurator']['currentlySubmittedFormID'] = $arrForm['id'];

		// Keine Formular-Verarbeitung, die den Konfigurator etwas angeht, also Abbruch!
		if (!isset($_POST['configurator_productVariantID']) || !$_POST['configurator_productVariantID']) {
			return;
		}

        $session = System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop');

		/*
		 * Memorize the product variant id in the array of product variant ids for which the configurator has already been used
		 */
		if (!isset($session_lsShop['productVariantIDsAlreadyConfigured']) || !is_array($session_lsShop['productVariantIDsAlreadyConfigured'])) {
			$session_lsShop['productVariantIDsAlreadyConfigured'] = array();
		}
		if (!in_array($_POST['configurator_productVariantID'], $session_lsShop['productVariantIDsAlreadyConfigured'])) {
			$session_lsShop['productVariantIDsAlreadyConfigured'][] = $_POST['configurator_productVariantID'];
		}

		// Wenn das Configurator-Session-Array noch keinen Key für die aktuell zu verarbeitende configurator-ProduktVarianten-ID enthält, so wird er erstellt
		if (!isset($session_lsShop['configurator'][$_POST['configurator_productVariantID']])) {
			$session_lsShop['configurator'][$_POST['configurator_productVariantID']] = array();
		}

		// Setzen des Flags, das für die Konfigurator-Klasse kennzeichnet, dass zugehörige Daten empfangen wurden
		$session_lsShop['configurator'][$_POST['configurator_productVariantID']]['blnReceivedFormDataJustNow'] = true;



		// Das Received-Post-Array wird zunächst geleert ...
		$session_lsShop['configurator'][$_POST['configurator_productVariantID']]['arrReceivedPost'] = array();

        $session->set('lsShop', $session_lsShop);

		// ... dann werden die Datenbankfelder für das aktuelle Formular ausgelesen ...
		$objFormFields = Database::getInstance()->prepare("
			SELECT		*
			FROM		`tl_form_field`
			WHERE		`pid` = ?
				AND		`invisible` != 1
				AND		`name` != ''
			ORDER BY	`sorting`
		")
		->execute($arrForm['id']);

		// Abbruch, wenn keine Formularfelder ermittelt werden konnten
		if (!$objFormFields->numRows) {
			return;
		}

		// ... dann Durchlaufen der ermittelten Formularfelder ...
		while ($objFormFields->next()) {
			// ... und für jedes ermittelte Formularfeld einen Eintrag im Received-Post-Array machen, welches die Feldinformationen sowie als Value den per Post übergebenen Wert enthält.
            $session_lsShop['configurator'][$_POST['configurator_productVariantID']]['arrReceivedPost'][$objFormFields->name] = array(
				'name' => $objFormFields->name,
				'arrData' => $objFormFields->row(),
				'value' => $arrSubmitted[$objFormFields->name] ? $arrSubmitted[$objFormFields->name] : ''
			);
			
		}

		if (isset($GLOBALS['MERCONIS_HOOKS']['onReceivingConfiguratorInput']) && is_array($GLOBALS['MERCONIS_HOOKS']['onReceivingConfiguratorInput'])) {
			foreach ($GLOBALS['MERCONIS_HOOKS']['onReceivingConfiguratorInput'] as $mccb) {
				$objMccb = System::importStatic($mccb[0]);
				$objMccb->{$mccb[1]}();
			}
		}

		/*
		 * Generate the configuratorHash and write it to the session
		 */
		$session_lsShop['configurator'][$_POST['configurator_productVariantID']]['strConfiguratorHash'] = sha1(serialize($session_lsShop['configurator'][$_POST['configurator_productVariantID']]['arrReceivedPost']));

        $session->set('lsShop', $session_lsShop);
	}
	
	/*
	 * Diese Funktion wird beim Laden eines Formularfelds per Hook eingebunden. Sie bricht sofort ab, wenn nicht in der globalen
	 * Variablen die aktuellen POST-Daten der aktuellen Konfigurator-Instanz vorliegen. Liegen diese Daten vor, so werden
	 * sie verwendet, um das Formularfeld mit dem aktuellen Wert zu füllen.
	 */
	public function ls_shop_configuratorLoadFormField(Widget $objWidget, $strForm, $arrForm) {
		if (!isset($GLOBALS['merconis_globals']['configurator']['currentArrReceivedPost']) || !is_array($GLOBALS['merconis_globals']['configurator']['currentArrReceivedPost'])) {
			return $objWidget;
		}
		
		return ls_shop_generalHelper::prefillFormField($objWidget, $GLOBALS['merconis_globals']['configurator']['currentArrReceivedPost']);
	}

	/*
	 * The 'configuratorFormHook_' methods are being called from Contao's form hooks and they execute the corresponding
	 * method in a configurator's custom logic file.
	 *
	 * The 'configuratorFormHook_' methods of the custom logic file can not be registered directly with Contao's form hooks
	 * because we need to make sure that the custom logic class' __construct function is always executed and therefore we
	 * need to refresh the custom logic class with each execution of a Contao form hook, which Contao wouldn't do on its own.
	 *
	 * The Merconis class 'ls_shop_productConfigurator' makes sure that the 'configuratorFormHook_' methods are registered with
	 * Contao's form hooks when the configurator form is rendered and that they are not registered before and after that.
	 */
	public function configuratorFormHook_getForm(FormModel $objRow, $strBuffer, $objElement) {
		return static::importStatic($GLOBALS['merconis_globals']['configurator']['objConfigurator']->customLogicClassName, null, true)->configuratorFormHook_getForm($objRow, $strBuffer, $objElement);
	}

	public function configuratorFormHook_compileFormFields($arrFields, $intFormId, $objForm) {
		return static::importStatic($GLOBALS['merconis_globals']['configurator']['objConfigurator']->customLogicClassName, null, true)->configuratorFormHook_compileFormFields($arrFields, $intFormId, $objForm);
	}

	public function configuratorFormHook_loadFormField(Widget $objWidget, $strForm, $arrForm) {
		return static::importStatic($GLOBALS['merconis_globals']['configurator']['objConfigurator']->customLogicClassName, null, true)->configuratorFormHook_loadFormField($objWidget, $strForm, $arrForm);
	}

	public function configuratorFormHook_prepareFormData(&$arrSubmitted, $arrLabels, $objForm) {
		static::importStatic($GLOBALS['merconis_globals']['configurator']['objConfigurator']->customLogicClassName, null, true)->configuratorFormHook_prepareFormData($arrSubmitted, $arrLabels, $objForm);
	}

	public function configuratorFormHook_processFormData($arrPost, $arrForm, $arrFiles) {
		static::importStatic($GLOBALS['merconis_globals']['configurator']['objConfigurator']->customLogicClassName, null, true)->configuratorFormHook_processFormData($arrPost, $arrForm, $arrFiles);
	}

	public function configuratorFormHook_storeFormData($arrSet, $objForm) {
		return static::importStatic($GLOBALS['merconis_globals']['configurator']['objConfigurator']->customLogicClassName, null, true)->configuratorFormHook_storeFormData($arrSet, $objForm);
	}

	public function configuratorFormHook_validateFormField(Widget $objWidget, $intId, $arrForm) {
		return static::importStatic($GLOBALS['merconis_globals']['configurator']['objConfigurator']->customLogicClassName, null, true)->configuratorFormHook_validateFormField($objWidget, $intId, $arrForm);
	}
}