<?php

namespace Merconis\Core;

use Contao\System;

class ls_shop_msg {

	/**
	 * Diese Funktion setzt am Ende eines Seitenaufrufs die Lifetime der einzelnen Message
	 * um 1 zurück und entfernt Msgs, deren Lifetime abgelaufen ist (lifetime < 1)
	 *
	 * Wichtig: Der Aufruf dieser Funktion darf bei internen Weiterleitungen nicht erfolgen, da die Lebensdauer
	 * einer Nachricht ja nur heruntergezählt werden darf, wenn auch eine Seite dargestellt (oder der Inhalt per Ajax
	 * ausgeliefert) wurde.
	 */
	public static function decreaseLifetime() {

	    $tl_mode = System::getContainer()->get('merconis.routing.scope')->getTLMode();

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);

		if (isset($session_lsShop) && isset($session_lsShop['ls_shop_msg']) && isset($session_lsShop['ls_shop_msg'][$tl_mode])) {
			foreach ($session_lsShop['ls_shop_msg'][$tl_mode] as $class => $arrClassMsgs) {
				foreach ($session_lsShop['ls_shop_msg'][$tl_mode][$class] as $reference => $arrMsg) {
					if ($session_lsShop['ls_shop_msg'][$tl_mode][$class][$reference]->killManually) {
						continue;
					}
                    $session_lsShop['ls_shop_msg'][$tl_mode][$class][$reference]->lifetime--;
					if ($session_lsShop['ls_shop_msg'][$tl_mode][$class][$reference]->lifetime < 1) {
						unset ($session_lsShop['ls_shop_msg'][$tl_mode][$class][$reference]);
					}
				}
				if (!count($session_lsShop['ls_shop_msg'][$tl_mode][$class])) {
					unset ($session_lsShop['ls_shop_msg'][$tl_mode][$class]);
				}
			}
		}
        $session->set('lsShop', $session_lsShop);
	}

	/*
	 * Diese Funktion nimmt Message-Informationen entgegen und schreibt sie in der Session fest.
	 * Als Parameter wird ein Array mit den Details zur Message erwartet. Dieses Array wird zur
	 * weiteren Verarbeitung in ein Objekt getypecasted, da der Zugriff auf die Elemente so ein klein wenig
	 * übersichtlicher notiert werden kann.
	 */
	public static function setMsg() {
		$args = func_get_args();
		$arrMsg = isset($args[0]) ? $args[0] : array();

		// Konvertieren in ein Objekt
		$om = (object) $arrMsg;

		// Wurde keine reference angegeben, so kann die Msg nicht vearbeitet werden, da sie ohne Bezug nirgends gezielt ausgegeben werden könnte
		if (!isset($om->reference) || !$om->reference) {
			error_log('a reference is required');
			return;
		}

		/*
		 * Ermitteln der Msg-Informationen auf Basis der übergebenen Werte, erzeugen
		 * der passenden Default-Werte für fehlende Angaben
		 */
		// Backend-Bereich für Benachrichtigungen ist false, fals nicht explizit als true übergeben
		$om->BE = isset($om->be) && $om->be ? true : false;

		/*
		 * Ob der Frontend- oder Backend-Modus genutzt werden soll (de facto ein anderer Key des Session-Arrays),
		 * wird abhängig von der gesetzten "BE"-Eigenschaft in der Eigenschaft "mode" festgehalten, um den richtigen
		 * Session-Bereich leichter im folgenden Code angeben zu können
		 */
		if ($om->BE) {
			$om->mode = 'BE';
		} else {
			$om->mode = 'FE';
		}
		unset ($om->BE);

		/*
		 * Wurde keine Msg-Lifetime angegeben, so wird als Lifetime 1 angenommen.
		 * Der Lifetime-Wert gibt an, für wieviele Seitenaufrufe die Msg festgehalten wird.
		 * Standardmäßig werden Nachrichten also für den nächsten Seitenaufruf bereitgehalten
		 * und danach entfernt. In Fällen, in denen aber durch mehrere Redirects oder Reloads
		 * eine längere Lifetime erforderlich ist oder durch die sofortige Ausgabe der Msg
		 * während des gleichen Seitenaufrufs, lässt sich der Wert anpassen.
		 */
		$om->lifetime = isset($om->lifetime) ? $om->lifetime : 1;

		$om->killManually = isset($om->killManually) ? $om->killManually : false;

		// Wurde keine Msg-Klasse angegeben, so wird die Klasse "misc" angenommen
		$om->class = isset($om->class) ? $om->class : 'misc';

		// Wurde keine Nachricht angegeben, so wird hierfür ein Leerstring angenommen
		$om->msg = isset($om->msg) ? $om->msg : '';

		// Wurde kein Array mit Details zur Nachricht angegeben, so wird ein leeres Array verwendet
		$om->arrDetails = isset($om->arrDetails) && is_array($om->arrDetails) ? $om->arrDetails : array();

		// Festhalten der Msg in der Session
        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);
        $session_lsShop['ls_shop_msg'][$om->mode][$om->class][$om->reference] = $om;
        $session->set('lsShop', $session_lsShop);
	}

	public static function getMsg() {
		$args = func_get_args();
		$class = isset($args[0]) ? $args[0] : '';
		$reference = isset($args[1]) ? $args[1] : '';
		$mode = isset($args[2]) ? $args[2] : 'FE';

		if (!self::checkParameters($class, $reference, $mode)) {
			return false;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);
		return $session_lsShop['ls_shop_msg'][$mode][$class][$reference]->msg;
	}

	public static function getMsgDetails() {
		$args = func_get_args();
		$class = isset($args[0]) ? $args[0] : '';
		$reference = isset($args[1]) ? $args[1] : '';
		$mode = isset($args[2]) ? $args[2] : 'FE';

		if (!self::checkParameters($class, $reference, $mode)) {
			return false;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);
		return $session_lsShop['ls_shop_msg'][$mode][$class][$reference]->arrDetails;
	}

	public static function delMsg() {
		$args = func_get_args();
		$class = isset($args[0]) ? $args[0] : '';
		$reference = isset($args[1]) ? $args[1] : '';
		$mode = isset($args[2]) ? $args[2] : 'FE';

		if (!self::checkParameters($class, $reference, $mode)) {
			return false;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);
		unset ($session_lsShop['ls_shop_msg'][$mode][$class][$reference]);
        $session->set('lsShop', $session_lsShop);
	}

	public static function checkMsg() {
		$args = func_get_args();
		$class = isset($args[0]) ? $args[0] : '';
		$reference = isset($args[1]) ? $args[1] : '';
		$mode = isset($args[2]) ? $args[2] : 'FE';

		if (!self::checkParameters($class, $reference, $mode)) {
			return false;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);

		if (isset($session_lsShop['ls_shop_msg'][$mode][$class][$reference])) {
			return true;
		}

		return false;
	}

	public static function checkMsgClass() {
		$args = func_get_args();
		$class = isset($args[0]) ? $args[0] : '';
		$mode = isset($args[1]) ? $args[1] : 'FE';

		// "void" übergeben, da der Parameter nicht benötigt wird, die "checkParameters"-Funktion aber deshalb keinen Fehler ausspucken soll
		if (!self::checkParameters($class, 'void', $mode)) {
			return false;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);
		if (isset($session_lsShop['ls_shop_msg'][$mode][$class]) && count($session_lsShop['ls_shop_msg'][$mode][$class])) {
			return true;
		}

		return false;
	}

	public static function errorlogAllMsgs() {
		$args = func_get_args();
		$class = isset($args[0]) ? $args[0] : '';
		$reference = isset($args[1]) ? $args[1] : '';
		$mode = isset($args[2]) ? $args[2] : 'FE';

		if ($mode != 'FE' && $mode != 'BE') {
			error_log('wrong value give for parameter $mode');
			return false;
		}

        $session = \System::getContainer()->get('merconis.session')->getSession();
        $session_lsShop =  $session->get('lsShop', []);

		ob_start();
		print_r($session_lsShop['ls_shop_msg'][$mode]);
		$output = ob_get_clean();
		error_log('ls_shop_msg::errorlogAllMsgs ==>');
		error_log($output);
	}

	private static function checkParameters($class = '', $reference = '', $mode = 'FE') {
		if ($mode != 'FE' && $mode != 'BE') {
			trigger_error('wrong value give for parameter $mode');
			return false;
		}

		if (!$class) {
			trigger_error('a class name is required');
			return false;
		}

		if (!$reference) {
			trigger_error('a reference is required');
			return false;
		}

		return true;
	}
}