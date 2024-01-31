<?php

namespace Merconis\Core;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\System;
use function LeadingSystems\Helpers\ls_getFilePathFromVariableSources;

class ls_shop_productConfigurator {
	public $configuratorID = 0;
	public $strTemplate = 'template_configurator_standard';
	
	public $arrData = null;
	
	public $formID = 0;
	
	public $productVariantID = 0;
	public $arrProductOrVariantData = array();
	
	public $blnReceivedFormDataJustNow = false;
	
	public $blnReceivedFormDataAtLeastOnce = false;
	
	public $arrReceivedPost = array();

	/*
	 * This variable is meant for a customLogic file to store data if needed.
	 */
	public $arr_customLogicData = [];

	public $changeConfigurationUrl = '';

	public $bln_ignoreRequiredDataFields = false;

	public $blnDataEntryMode = null;
	
	public $blnIsValid = true; // Standardmäßig gilt ein Konfigurator als valide, da ja noch nicht sicher ist, ob überhaupt ein tatsächlicher Konfigurator geladen wird.
	
	public $configuratorCacheKey = '';

	/*
	 * Der Konfigurator-Hash ist ein SHA1-Hash des serialisierten Konfigurator-Objektes bzw. seiner relevanten Daten/Eigenschaften,
	 * mit dem das Objekt in einem bestimmten Zustand einen eindeutigen "Fingerabdruck bekommt" (fast eindeutig, weil SHA1 theoretisch,
	 * wenn auch extremst unwahrscheinlich, für zwei unterschiedliche Konfigurator-Zustände denselben Hash ermitteln könnte. Bei
	 * SHA1 gilt diese Kollision aber als praktisch ausgeschlossen.)
	 * 
	 * Dieser Fingerabdruck ist nötig, um im Warenkorb den für ein Produkt vorliegenden Konfigurator-Zustand eindeutig zu kennzeichnen. Die
	 * Bestellung desselben Produktes mit anderen Einstellungen soll von der Bestellung des Produktes mit den selben Einstellungen unterschieden
	 * werden können.
	 * 
	 * Bei der ersten Erstellung des Konfigurator-Objektes ist dieser Hash-Wert zunächst leer, damit die zur Erstellung benötigte Performance
	 * nicht im Extremfall bei der ersten Darstellung tausender Produkte auf ein Mal benötigt wird.
	 * 
	 * Der Hash wird erst dann erstellt, wenn er tatsächlich benötigt wird, konkret also, wenn er über den Getter "configuratorHash" abgefragt wird oder
	 * wenn Daten für den Konfigurator empfangen wurden. Wird der Hash über den Getter abgefragt, so wird er - sofern er bereits erstellt ist - nur noch
	 * ausgegeben, damit dies keine Performance kostet. Es ist also sichergestellt, dass stets ein aktueller Hash existiert, aber kein Performancebedarf
	 * besteht, wenn der Hash nicht explizit generiert bzw. geupdatet werden muss.
	 * 
	 * Wichtig: Ein bei der Objekterstellung an die __construct-Funktion übergebener configuratorHash hat nichts mehr damit zu tun, den aktuellen
	 * Zustand des Konfigurators in einem Hash abzubilden sondern hat den Zweck einen mit diesem configuratorHash bereits im Warenkorb repräsentierten
	 * Konfigurator in der Session von einer anderen Instanz des gleichen Konfigurators mit dem gleichen Produkt zu trennen. Der Hintergrund ist hier,
	 * dass mit dem Ablegen eines Produktes im Warenkorb automatisch eine isolierte Produkt-Instanz (und damit auch Konfigurator-Instanz) entstehen muss.
	 * 
	 * Wurde ein configuratorHash bei der Objekterstellung übergeben, so soll der configuratorHash dieses Konfigurators nicht mehr veränderbar sein, da
	 * die eindeutige Kennzeichnung des Konfigurator-Zustands ja nicht mehr nötig ist. Zu diesem Zweck wird das Flag "blnConfiguratorHashFixed" gesetzt.
	 */
	public $strConfiguratorHash = '';
	public $blnConfiguratorHashFixed = false;
	
	public $objCustomLogic = null;
	public $customLogicClassName = '';
	
	public $objProductOrVariant;
	
	public function __construct($configuratorID = 0, $productVariantID = false, $arrProductOrVariantData = array(), $configuratorHash = '', &$objProductOrVariant = null) {
		if (!$productVariantID || !is_array($arrProductOrVariantData) || !count($arrProductOrVariantData)) {
			throw new \Exception('insufficient parameters given');
		}
		
		$this->objProductOrVariant = &$objProductOrVariant;
		
		$this->configuratorID = $configuratorID;
		$this->productVariantID = $productVariantID;
		$this->arrProductOrVariantData = $arrProductOrVariantData;
		
		$this->configuratorCacheKey = $this->createCacheKey();
		
		if ($configuratorHash) {
			$this->strConfiguratorHash = $configuratorHash;
			$this->blnConfiguratorHashFixed = true;
			$this->configuratorCacheKey = $this->createCacheKey(true);
		}
		
		/*
		 * Einlesen der Konfigurator-Daten, sofern Konfigurator-ID vorhanden
		 */
		if ($this->configuratorID) {
			$objConfiguratorData = Database::getInstance()->prepare("
				SELECT		*
				FROM		`tl_ls_shop_configurator`
				WHERE		`id` = ?
			")
			->execute($this->configuratorID);
			
			/*
			 * Konfigurator-Zustand aus der Session einlesen, sofern in der Session schon vorhanden
			 */
			$this->arrReceivedPost = isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['arrReceivedPost']) ? $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['arrReceivedPost'] : $this->arrReceivedPost;
			$this->arr_customLogicData = isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['arr_customLogicData']) ? $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['arr_customLogicData'] : $this->arr_customLogicData;
			$this->strConfiguratorHash = isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['strConfiguratorHash']) ? $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['strConfiguratorHash'] : $this->strConfiguratorHash;
			$this->blnReceivedFormDataAtLeastOnce = isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataAtLeastOnce']) ? $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataAtLeastOnce'] : $this->blnReceivedFormDataAtLeastOnce;

			if ($objConfiguratorData->numRows) {
				$objConfiguratorData->first();
				$this->arrData = $objConfiguratorData->row();
				$this->formID = $this->arrData['form'];

				if (!$this->formID) {
                    $this->arrData['startWithDataEntryMode'] = true;
                    $this->arrData['stayInDataEntryMode'] = true;
                    $this->arrData['skipStandardFormValidation'] = true;
                }

				$this->strTemplate = $this->arrData['template'];
				
				/*
				 * Einbinden einer eventuell vorhandenen PHP-Datei mit der customLogic
				 * und Erstellen des customLogic-Objektes
				 */

                $str_projectDir = System::getContainer()->getParameter('kernel.project_dir');
				$pathToCustomLogicFile = ls_getFilePathFromVariableSources($objConfiguratorData->customLogicFile);
				
				if ($pathToCustomLogicFile && file_exists($str_projectDir."/".$pathToCustomLogicFile) && is_file($str_projectDir."/".$pathToCustomLogicFile)) {
					require_once($str_projectDir."/".$pathToCustomLogicFile);
					$customLogicClassName = '\Merconis\Core\\'.preg_replace('/(^.*\/)([^\/\.]*)(\.php$)/', '\\2', $pathToCustomLogicFile);
					$this->customLogicClassName = $customLogicClassName;

					/*
					 * Die __construct-Funktion der im customLogicFile angegebenen Klasse muss $this
					 * als Referenz entgegennehmen (also z. B. &$objConfigurator), damit dort der
					 * direkte Zugriff auf die aktuellen Configurator-Werte stattfindet!
					 */
					$this->objCustomLogic = new $customLogicClassName($this);
				}
			}
						
			/*
			 * Ob sich der Konfigurator im Datenerfassungsmodus befindet oder nicht, wird aus der Session eingelesen, sofern die Information dort vorhanden ist.
			 * Ist die Information dort nicht vorhanden, so wird der Datenerfassungsmodus abhängig von der im Backend für diesen Konfigurator definierten Starteinstellung gesetzt.
			 */
			$this->blnDataEntryMode = isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnDataEntryMode']) ? $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnDataEntryMode'] : $this->blnDataEntryMode;
			if ($this->blnDataEntryMode === null) {
				$this->blnDataEntryMode = $this->arrData['startWithDataEntryMode'] ? true : false;
			}
			
			
			// Die in der Session gespeicherte Information, ob gerade eben Daten empfangen wurden, wird in das Objekt eingelesen, in der Session dann aber sofort zurückgesetzt, da dieser Zustand natürlich nicht über mehrere Seitenaufrufe bestehen bleiben soll.
			$this->blnReceivedFormDataJustNow = isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataJustNow']) ? $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataJustNow'] : $this->blnReceivedFormDataJustNow;
			if (isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataJustNow'])) {
				$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataJustNow'] = false;
			}
			
			if ($this->blnReceivedFormDataJustNow) {
				$this->blnReceivedFormDataAtLeastOnce = true;
			}
			
			/*
			 * Wenn gerade Daten empfangen wurden ist klar, dass der Zustand jetzt im Moment valide sein muss (sonst wäre das Formular schon nicht validiert worden und hätte die Daten nicht durchgelassen).
			 * In diesem Moment wird also der Datenerfassungsmodus abgeschaltet.
			 */
			if ($this->blnReceivedFormDataJustNow) {
				$this->blnDataEntryMode = $this->arrData['stayInDataEntryMode'] ? true : false;
				$this->saveBlnDataEntryMode();
			}
			
			$this->analyzeRequiredConfiguratorData();
			
			$this->handleConfigurationChangeRequests();
			
			$this->blnIsValid = $this->isValid();
			
			// Ist der Zustand nicht valide, so wird auf jeden Fall in den Datenerfassungsmodus gewechselt.
			if (!$this->blnIsValid) {
				$this->blnDataEntryMode = true;
				$this->saveBlnDataEntryMode();
			}
			
			if ($this->blnReceivedFormDataJustNow) {
				$this->updateConfiguratorHash();
			}
		}
	}

	public function storeToSession() {
		/*
		 * Don't write anything to the session unless there has been post data received at least once
		 */
		if (!$this->blnReceivedFormDataAtLeastOnce) {
			return;
		}
		
		/*
		 * Konfigurator-Zustand in Session schreiben, es sei denn "blnReceivedFormDataJustNow" ist true. In diesem Fall wurden 
		 * gerade die neu empfangenen Daten von der Hook-Funktion "ls_shop_configuratorController::ls_shop_configuratorProcessFormData()" in die Session geschrieben
		 * und die dürfen natürlich jetzt nicht durch die alten Daten der Konfigurator-Instanz überschrieben werden.
		 */
		if (isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataJustNow']) && $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataJustNow']) {
			return;
		}
		
		if (!isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey])) {
			$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey] = array();
		}
		$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['arrReceivedPost'] = $this->arrReceivedPost;
		$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['arr_customLogicData'] = $this->arr_customLogicData;
		$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['strConfiguratorHash'] = $this->strConfiguratorHash;
		$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnReceivedFormDataAtLeastOnce'] = $this->blnReceivedFormDataAtLeastOnce;
	}

	public function saveBlnDataEntryMode() {
		$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['blnDataEntryMode'] = $this->blnDataEntryMode;
	}
	
	public function updateConfiguratorHash() {
		if ($this->blnConfiguratorHashFixed) {
			return;
		}
		$this->strConfiguratorHash = sha1(serialize($this->arrReceivedPost));
	}
	
	public function getConfiguratorHash() {
		if (!$this->strConfiguratorHash) {
			$this->updateConfiguratorHash();
		}
		return $this->strConfiguratorHash;
	}
	
	public function __get($what) {
		switch ($what) {
			case 'configuratorHash':
				return $this->getConfiguratorHash();
				break;
				
			case 'representation':
				return $this->getRepresentationOfConfiguratorSettings();
				break;
				
			case 'cartRepresentation':
				return $this->getCartRepresentationOfConfiguratorSettings();
				break;
				
			case 'merchantRepresentation':
				return $this->getMerchantRepresentationOfConfiguratorSettings();
				break;
				
			case 'referenceNumber':
				return $this->getReferenceNumber();
				break;
				
			case 'hasValue':
				return $this->hasValue();
				break;
		}
	}
	
	public function __set($key, $value) {
		switch ($key) {
			default:
				break;
		}
	}
	
	public function parse($representationToUse = 'representation') {
		/*
		 * Existieren keine Konfigurator-Daten - dem Produkt, das diesen Konfigurator initialisiert hat, ist also offenbar kein Konfigurator zugeordnet
		 * oder es ist einer zugeordnet, aber der Datensatz existiert nicht mehr - so wird stets ein Leerstring zurückgegeben, was dem
		 * kompletten Ignorieren des Konfigurators entspricht.
		 */
		if (!$this->arrData) {
			return '';
		}
		
		/*
		 * Wurde dieses Objekt bereits geparsed, so wird es nicht erneut geparsed, um eine mehrfache Verarbeitung desselben
		 * Formulars usw. zu verhindern. Es wird stattdessen die in der Session abgelegte HTML-Ausgabe zurückgegeben.
		 * Die HTML-Ausgabe kann auf diese Art konfliktfrei mehrfach ausgegeben werden.
		 */
		if (isset($GLOBALS['merconis_globals']['configurator'][$this->configuratorCacheKey]['output'])) {
			return $GLOBALS['merconis_globals']['configurator'][$this->configuratorCacheKey]['output'];
		}
		
		$template = new FrontendTemplate($this->strTemplate);
		$template->arrReceivedPost = $this->arrReceivedPost;
		$template->arr_customLogicData = $this->arr_customLogicData;
		$template->blnReceivedFormDataJustNow = $this->blnReceivedFormDataJustNow;

        // Hinterlegen der aktuellen Daten in einer globalen Transfervariablen, die in der Hook-Funktion "ls_shop_configuratorController::ls_shop_configuratorLoadFormField()" verwendet wird.
        if ($this->arrData['stayInDataEntryMode']) {
            $GLOBALS['merconis_globals']['configurator']['currentArrReceivedPost'] = $this->arrReceivedPost;
        } else {
            $GLOBALS['merconis_globals']['configurator']['currentArrReceivedPost'] = isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['tmpLastReceivedPostForFormPrefillAfterChangeConfiguration']) ? $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['tmpLastReceivedPostForFormPrefillAfterChangeConfiguration'] : ($this->blnReceivedFormDataAtLeastOnce ? $this->arrReceivedPost : null);
        }

		/*
		 * Entfernen der nur temporär gemerkten "LastReceivedPost"-Daten, sofern vorhanden. Diese dienten nur dazu, nach dem Zurücksetzen der empfangenen Konfigurator-Daten
		 * und nach dem darauffolgenden Reload das Formular mit den zuletzt vorhandenen Daten zu befüllen.
		 */
		if (isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['tmpLastReceivedPostForFormPrefillAfterChangeConfiguration'])) {
			unset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['tmpLastReceivedPostForFormPrefillAfterChangeConfiguration']);
		}





		/*
		 * Generating the form. If data is sent, the processFormData Hook comes into operation.
		 * 
		 * Important: Since the same form can be displayed many times on one page if multiple products with the same configurator
		 * are displayd in the overview with a template that is actually showing and using the configurator, we have to make sure
		 * that only the one instance of the form that has actually sent the data, processes the data. Every form has the same
		 * FORM_SUBMIT value because the form is generated by the contao standard function and we don't modify this value. If
		 * we generate a form with getForm(), every form would feel responsible for the sent data even if it wasn't the one that
		 * has really been used.
		 * 
		 * The trick is to check whether a form is responsible for the data or not before calling getForm() and, if it's not,
		 * changing the FORM_SUBMIT value in the post data in a way that makes the form that's being generated with the following
		 * getForm() call not responsible. Of course we have to temporarily memorize the original FORM_SUBMIT value and reset
		 * it right after the getForm() call.
		 * 
		 * We do this for every form and if we come to the form that actually sent the data, we know that this is the right
		 * one by checking the post value configurator_productVariantID and comparing it to the current configurator's
		 * configuratorCacheKey. In this case we do not alter the FORM_SUBMIT value and the form will process the data
		 * in the original contao way.
		 */
		$tmpFormSubmitValue = null;
		if (
				Input::post('configurator_productVariantID')
			&&	Input::post('configurator_productVariantID') != $this->configuratorCacheKey
			
			/*
			 * Check for the completely raw post value "FORM_SUBMIT" to make sure that
			 * this condition is only true if a form has just been sent! Using \Input::post('FORM_SUBMIT')
			 * could falsely detect a value for FORM_SUBMIT even though no form has currently
			 * been submitted.
			 */
			&&	$_POST['FORM_SUBMIT']
		) {
			$tmpFormSubmitValue = Input::post('FORM_SUBMIT');
			Input::setPost('FORM_SUBMIT', 'ignore me');
		}

		/* ->
		 * If the custom logic file has one of the "configuratorFormHook_" methods implemented, we register it in contao's getForm.
		 *
		 * We have to register the custom logic 'configuratorFormHook_' methods indirectly via the
		 * 'Merconis\Core\ls_shop_configuratorController::configuratorFormHook_' methods because we need to make sure
		 * that the custom logic class' __construct function is always executed and therefore we need to refresh the
		 * custom logic class with each execution of the a form hook, which Contao wouldn't do on its own.
		 *
		 * Since we only want the methods to be hooked once (because the next configurator that might be
		 * rendered and displayed on the same page could behave differently), we have to remove the hook entries
		 * from the TL_HOOKS array right after calling $this->getForm()
		 */
		$arr_configuratorFormHookNames = array(
			'configuratorFormHook_getForm',
			'configuratorFormHook_compileFormFields',
			'configuratorFormHook_loadFormField',
			'configuratorFormHook_prepareFormData',
			'configuratorFormHook_processFormData',
			'configuratorFormHook_storeFormData',
			'configuratorFormHook_validateFormField'
		);

		foreach ($arr_configuratorFormHookNames as $arr_configuratorFormHookName) {
			if ($this->objCustomLogic && method_exists($this->objCustomLogic, $arr_configuratorFormHookName)) {
				$GLOBALS['TL_HOOKS'][str_replace('configuratorFormHook_', '', $arr_configuratorFormHookName)][] = array('Merconis\Core\ls_shop_configuratorController', $arr_configuratorFormHookName);
			}
		}

		/*
		 * Register the standard configurator hook methods right after the 'configuratorFormHook_' methods
		 * to make sure that the 'configuratorFormHook_' methods will always be executed first.
		 */
		$GLOBALS['TL_HOOKS']['processFormData'][] = array('Merconis\Core\ls_shop_configuratorController', 'ls_shop_configuratorProcessFormData');
		$GLOBALS['TL_HOOKS']['loadFormField'][] = array('Merconis\Core\ls_shop_configuratorController', 'ls_shop_configuratorLoadFormField');

		/*
		 * <-
		 */

		/*
		 * A reference to the configurator object ($this) is stored in merconis_globals to make it available in
		 * the 'configuratorFormHook' methods. Since these methods are being called from Contao hooks, there's no
		 * other way to pass the configurator object to the custom logic object respectively the 'configuratorFormHook'
		 * methods.
		 *
		 * Right after calling Contao's 'getForm' method (all Contao form hooks have been executed by then) we remove
		 * the configurator object from merconis_globals as it is no longer needed.
		 */
		$GLOBALS['merconis_globals']['configurator']['objConfigurator'] = &$this;

		$form = Controller::getForm($this->formID);

		unset($GLOBALS['merconis_globals']['configurator']['objConfigurator']);

		/*
		 * Remove the standard configurator hook methods
		 */
		array_pop($GLOBALS['TL_HOOKS']['processFormData']);
		array_pop($GLOBALS['TL_HOOKS']['loadFormField']);

		/*
		 * Remove the hooked "configuratorFormHook_" methods from the TL_HOOKS array
		 */
		foreach ($arr_configuratorFormHookNames as $arr_configuratorFormHookName) {
			if ($this->objCustomLogic && method_exists($this->objCustomLogic, $arr_configuratorFormHookName)) {
				array_pop($GLOBALS['TL_HOOKS'][str_replace('configuratorFormHook_', '', $arr_configuratorFormHookName)]);
			}
		}
		
		if ($tmpFormSubmitValue !== null) {
			Input::setPost('FORM_SUBMIT', $tmpFormSubmitValue);
		}



		
		
		// Entfernen der globalen Transfervariablen
		unset($GLOBALS['merconis_globals']['configurator']['currentArrReceivedPost']);
		
		/*
		 * Einfügen eines Hidden-Fields in das Formular, um beim Absenden des Formulars die Produkt-Varianten-ID zur Identifizierung der aktuellen Konfigurator-Instanz mitzugeben.
		 * Dies ist wichtig, da die Hook-Funktion "ls_shop_configuratorController::ls_shop_configuratorProcessFormData()" nur so in der Lage ist,
		 * die empfangenen Daten der richtigen Konfigurator-Instanz zuzuweisen.
		 */
		$form = preg_replace('/(<form.*>)/', '\\1'."\r\n".'<div><input type="hidden" name="configurator_productVariantID" value="'.$this->configuratorCacheKey.'" /></div>', $form);
		$form = preg_replace('/(<form.*action=")(.*)(")/siU', '\\1'.Environment::get('request').'#'.$this->arrProductOrVariantData['anchor'].'\\3', $form);
		$template->form = $form;

		$template->blnIsValid = $this->blnIsValid;
		$template->changeConfigurationUrl = $this->changeConfigurationUrl;
		$template->blnDataEntryMode = $this->blnDataEntryMode;
		$template->representationToUse = $representationToUse;
		$template->representation = $this->{$representationToUse};
		$template->configuratorCacheKey = $this->configuratorCacheKey;
		$template->strConfiguratorHash = $this->strConfiguratorHash;
		$template->obj_productOrVariant = $this->objProductOrVariant;

		// Geparsed'es Template in globale Variable schreiben, um es bei erneutem Bedarf aus dieser direkt ausgeben zu können.
		$GLOBALS['merconis_globals']['configurator'][$this->configuratorCacheKey]['output'] = $template->parse();

		// Ausgeben des geparsed'en Templates aus der globalen Variable
		return $GLOBALS['merconis_globals']['configurator'][$this->configuratorCacheKey]['output'];
	}

	public function analyzeRequiredConfiguratorData() {
	    if (!$this->formID || $this->bln_ignoreRequiredDataFields) {
	        return;
        }
		$this->arrReceivedPost = ls_shop_generalHelper::analyzeRequiredDataFields($this->formID, $this->arrReceivedPost, !$this->blnReceivedFormDataAtLeastOnce);
	}

	/**
	 * Diese Funktion prüft, ob der Zustand des Konfigurators für eine Produktbestellung valide ist. Sprich: Wurden relevante Angaben
	 * erfasst und wurden diese positiv validiert?
	 */
	public function isValid() {
		/*
		 * If the standard form validation should be skipped, the standard validation is not being executed
		 * and the result of the skipped validation is set to a "fake" true, meaning that the result of the standard
		 * validation is considered to be valid. This way, the result of the customValidator method masters
		 * the result of this whole "isValid" method on its own.
		 */
		if ($this->arrData['skipStandardFormValidation']) {
			$blnFormDataValid = true;
		} else {
			$blnFormDataValid = ls_shop_generalHelper::validateCollectedFormData($this->arrReceivedPost, $this->formID);
		}

		$blnCustomValidatorValid = $this->customValidator();
		
		return $blnFormDataValid && $blnCustomValidatorValid;
	}
	
	/**
	 * Diese Funktion erstellt den Link, mit dem ein Konfigurations-Änderungswunsch signalisiert werden kann und
	 * verarbeitet auch eine entsprechende per POST oder GET übergebene Anforderung
	 */
	public function handleConfigurationChangeRequests() {
		// Erstellen der URL, um einen Konfigurations-Änderungswunsch zu signalisieren
		$this->changeConfigurationUrl = Environment::get('request').(preg_match('/\?/', Environment::get('request')) ? '&' : '?').'changeConfiguration='.$this->configuratorCacheKey.'#'.$this->arrProductOrVariantData['anchor'];
		
		/*
		 * Liegt eine Änderungsanforderung vor, so wird diese zunächst in die Session geschrieben und die Seite ohne den GET-Parameter erneut aufgerufen.
		 * Liegt die Änderungsanforderung per POST vor, so wird sie auch in die Session geschrieben und die Seite neu aufgerufen, um die POST-Daten zu entfernen.
		 */
		if (
				Input::get('changeConfiguration') && Input::get('changeConfiguration') == $this->configuratorCacheKey
			||	Input::post('changeConfiguration') && Input::post('changeConfiguration') == $this->configuratorCacheKey
		) {
			$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['changeConfiguration'] = true;
			Controller::redirect(preg_replace('/(\?|&)changeConfiguration='.$this->configuratorCacheKey.'/', '', Environment::get('request')).'#'.$this->arrProductOrVariantData['anchor']);
		}
		
		/*
		 * Liegt eine Änderungsanforderung bereits in der Session vor, so wird sie ausgeführt und
		 * in der Session verworfen
		 */
		if (isset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['changeConfiguration']) && $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['changeConfiguration']) {
			unset($_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['changeConfiguration']);
			
			$_SESSION['lsShop']['configurator'][$this->configuratorCacheKey]['tmpLastReceivedPostForFormPrefillAfterChangeConfiguration'] = $this->arrReceivedPost;
			
			/*
			 * Hier ist nicht klar, ob es eher sinnvoll oder eher unsinnig ist, die bereits empfangenen Daten zu verwerfen.
			 * Werden sie verworfen, so sind nach einem Klick auf "Produktdefinition ändern" die Daten verloren, wenn nicht
			 * das Formular mit den gleichen oder eben geänderten Werten wieder bestätigt wird. Das könnte eine häufige
			 * Ursache für den Verlust eingegebener Daten sein. Deshalb wird auf das Verwerfen der empfangenen Daten verzichtet.
			 * Andererseits besteht, wenn die Daten nicht verworfen werden, die Gefahr, dass jemand Daten im Formular ändert,
			 * sie aber nicht abschickt sondern direkt das Produkt mit dem anderen Button in den Warenkorb legt.
			 * 
			 * Letzteres scheint aber insgesamt das kleinere Problem zu sein, zumal man diese Situation auf Template-Ebene
			 * vermeiden kann, indem man im Dateneingabemodus standardmäßig das "In den Warenkorb"-Feld ausblednet.

			 $this->resetReceivedData();
			 
			 */

			
			// Die Änderungsanforderung führt automatisch den Wechsel in den Datenerfassungsmodus bei
			$this->blnDataEntryMode = true;
			$this->saveBlnDataEntryMode();

			Controller::redirect(Environment::get('request').'#'.$this->arrProductOrVariantData['anchor']);
		}		
	}

	/**
	 * Diese Funktion setzt bereits empfangene Daten zurück
	 */
	public function resetReceivedData() {
		$this->arrReceivedPost = array();
	}
	
	/*
	 * Diese Funktion erstellt den cacheKey, der abhängig vom übergebenen Parameter den Hash entweder enthält oder nicht
	 */
	public function createCacheKey($withHash = false) {
		return $this->productVariantID.($withHash ? '_'.$this->strConfiguratorHash : '');
	}
	
	public function saveConfiguratorForCurrentCartKey() {
		$_SESSION['lsShop']['configurator'][$this->createCacheKey(true)] = $_SESSION['lsShop']['configurator'][$this->configuratorCacheKey];
	}
	
	/*
	 * Diese Funktion stellt die Repräsentation der vom Kunden vorgenommenen Produkt-Konfiguration bereit
	 * und stellt hierfür auch eine Schnittstelle zu einer customFunction zur Verfügung
	 */
	public function getRepresentationOfConfiguratorSettings($blnReplaceInsertTags = true) {
		$cartRepresentation = '';

		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'getRepresentationOfConfiguratorSettings')) {
			$cartRepresentation = $this->objCustomLogic->getRepresentationOfConfiguratorSettings();
		}

		if (!$cartRepresentation && $this->formID) {
            $arr_receivedPostForReview = ls_shop_generalHelper::getArrDataReview($this->arrReceivedPost);
            $arr_formFieldLabels = ls_shop_generalHelper::getFormFieldLabels($this->formID);

            $obj_template_defaultConfiguratorRepresentation = new FrontendTemplate('template_defaultConfiguratorDataReview');
            $obj_template_defaultConfiguratorRepresentation->arr_data = $arr_receivedPostForReview;
            $obj_template_defaultConfiguratorRepresentation->arr_formFieldLabels = $arr_formFieldLabels;
            $cartRepresentation = $obj_template_defaultConfiguratorRepresentation->parse();
        }

		return $blnReplaceInsertTags ? System::getContainer()->get('contao.insert_tag.parser')->replace($cartRepresentation) : $cartRepresentation;
	}
	
	/*
	 * Diese Funktion stellt die warenkorbtaugliche Repräsentation der vom Kunden vorgenommenen Produkt-Konfiguration bereit
	 * und stellt hierfür auch eine Schnittstelle zu einer customFunction zur Verfügung. Existiert hierfür keine
	 * spezielle customFunction, so wird als Fallback die Funktion getRepresentationOfConfiguratorSettings() aufgerufen.
	 */
	public function getCartRepresentationOfConfiguratorSettings($blnReplaceInsertTags = true) {
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'getCartRepresentationOfConfiguratorSettings')) {
			$cartRepresentation = $this->objCustomLogic->getCartRepresentationOfConfiguratorSettings();
		} else {
			$cartRepresentation = $this->getRepresentationOfConfiguratorSettings(false);
		}
		
		return $blnReplaceInsertTags ? System::getContainer()->get('contao.insert_tag.parser')->replace($cartRepresentation) : $cartRepresentation;
	}
	
	/*
	 * Diese Funktion stellt die speziell für den Händler geeignete Repräsentation der vom Kunden vorgenommenen Produkt-Konfiguration bereit
	 * und stellt hierfür auch eine Schnittstelle zu einer customFunction zur Verfügung. Existiert hierfür keine
	 * spezielle customFunction, so wird als Fallback die Funktion getRepresentationOfConfiguratorSettings() aufgerufen.
	 */
	public function getMerchantRepresentationOfConfiguratorSettings($blnReplaceInsertTags = true) {
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'getMerchantRepresentationOfConfiguratorSettings')) {
			$merchantRepresentation = $this->objCustomLogic->getMerchantRepresentationOfConfiguratorSettings();
		} else {
			$merchantRepresentation = $this->getRepresentationOfConfiguratorSettings(false);
		}
		
		/*
		 * FIXME: In order to make sure that language insert tags in the merchant representation are replaced
		 * considering the shop language and not the current frontend language we probably could manipulate the current
		 * $objPage->language if we are okay with this kind of intervention. Currently, we are not using this technique
		 * because we haven't thought it through yet.
		 */
		return $blnReplaceInsertTags ? System::getContainer()->get('contao.insert_tag.parser')->replace($merchantRepresentation) : $merchantRepresentation;
	}
	
	public function getReferenceNumber() {
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'getReferenceNumber')) {
			$referenceNumber = $this->objCustomLogic->getReferenceNumber();
		} else {
			$referenceNumber = strtoupper(substr(sha1(serialize($this->arrReceivedPost).$this->productVariantID), 0, 8));
		}
		
		return $referenceNumber;
	}
	
	public function hasValue() {
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'hasValue')) {
			return $this->objCustomLogic->hasValue();
		}

		$blnHasValue = '';
		
		if (!is_array($this->arrReceivedPost)) {
			return $blnHasValue;
		}
		
		foreach ($this->arrReceivedPost as $k => $v) {
			if (!empty($v['value'])) {
				$blnHasValue = '1';
			}
		}
		
		return $blnHasValue;
	}
	
	/**
	 * Diese Funktion stellt die Schnittstelle zu einer customFunction für die Berechnung einer konfigurationsabhängigen Preisanpassung zur Verfügung.
	 * Der Rückgabewert dieser Funktion ist der preisliche Auf- oder Abschlag als Geldbetrag.
	 */
	public function getPriceModification() {
		$priceModification = 0;
		
		/*
		 * Ist gar kein spezieller Konfigurator geladen, so wird 0 als Preismodifikation zurückgegeben
		 */
		if (!$this->configuratorID) {
			return $priceModification;
		}
		
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'getPriceModification')) {
			$priceModification = $this->objCustomLogic->getPriceModification();
		}

		return $priceModification;
	}

	/**
	 * Diese Funktion stellt die Schnittstelle zu einer customFunction für die Berechnung einer konfigurationsabhängigen Preisanpassung (ungestaffelt) zur Verfügung.
	 * Der Rückgabewert dieser Funktion ist der preisliche Auf- oder Abschlag als Geldbetrag.
	 */
	public function getUnscaledPriceModification() {
		$priceModification = 0;
		
		/*
		 * Ist gar kein spezieller Konfigurator geladen, so wird 0 als Preismodifikation zurückgegeben
		 */
		if (!$this->configuratorID) {
			return $priceModification;
		}
		
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'getUnscaledPriceModification')) {
			$priceModification = $this->objCustomLogic->getUnscaledPriceModification();
		}

		return $priceModification;
	}

	/**
	 * Diese Funktion stellt die Schnittstelle zu einer customFunction für die Berechnung einer konfigurationsabhängigen Gewichtsanpassung zur Verfügung.
	 * Der Rückgabewert dieser Funktion ist der Auf- oder Abschlag des Gewichts.
	 */
	public function getWeightModification() {
		$weightModification = 0;
		
		/*
		 * Ist gar kein spezieller Konfigurator geladen, so wird 0 als Gewichtsmodifikation zurückgegeben
		 */
		if (!$this->configuratorID) {
			return $weightModification;
		}
		
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'getWeightModification')) {
			$weightModification = $this->objCustomLogic->getWeightModification();
		}

		return $weightModification;
	}

	/**
	 * Diese Funktion stellt die Schnittstelle zu einer customFunction für die Validierung bereit. Der Rückgabewert
	 * der customFunction muss true oder false sein.
	 */
	public function customValidator() {
		$blnValid = true;
		$strMessage = '';
		
		/*
		 * Ist gar kein spezieller Konfigurator geladen, so der Default-Wert zurückgegeben
		 */
		if (!$this->configuratorID) {
			return $blnValid;
		}
		
		if ($this->objCustomLogic && method_exists($this->objCustomLogic, 'customValidator')) {
			$arrCustomValidatorResult = $this->objCustomLogic->customValidator();
			
			if (isset($arrCustomValidatorResult['blnValid'])) {
				$blnValid = $arrCustomValidatorResult['blnValid'];
			}
			
			if (isset($arrCustomValidatorResult['strMessage'])) {
				$strMessage = $arrCustomValidatorResult['strMessage'];
			}
			
			/*
			 * Remove validation messages that might still exist from a previous validation
			 */
			ls_shop_msg::delMsg('customValidationError', $this->configuratorCacheKey);
			ls_shop_msg::delMsg('customValidationSuccess', $this->configuratorCacheKey);
			
			/*
			 * Setzen einer Fehlermeldung, wenn Zustand nicht valide ist bzw. einer Erfolgsmeldung,
			 * wenn der Zustand valide ist und eine Meldung angegeben ist.
			 */
			if (!$blnValid) {
				ls_shop_msg::setMsg(array(
					'class' => 'customValidationError',
					'reference' => $this->configuratorCacheKey,
					'msg' => $strMessage
				));
			} else if ($strMessage) {
				ls_shop_msg::setMsg(array(
					'class' => 'customValidationSuccess',
					'reference' => $this->configuratorCacheKey,
					'msg' => $strMessage
				));				
			}
		}

		return $blnValid;
	}
}