<?php

namespace Merconis\Core;

class Installer
{
	protected $obj_template = null;

	protected $bln_installedCompletely = false;

	/*
	 * Current object instance (Singleton)
	 */
	protected static $objInstance;

	/*
	 * Prevent cloning of the object (Singleton)
	 */
	final private function __clone() {}


	/*
	 * Return the current object instance (Singleton)
	 */
	public static function getInstance() {
		if (!is_object(self::$objInstance))	{
			self::$objInstance = new self();
		}
		return self::$objInstance;
	}

	/*
	 * Prevent direct instantiation (Singleton)
	 */
	protected function __construct()
	{
		\System::loadLanguageFile('lsm_installer');
		$this->obj_template = new \FrontendTemplate('lsm_installer');

		if (isset($GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']) && $GLOBALS['TL_CONFIG']['ls_shop_installedCompletely']) {
			$this->bln_installedCompletely = true;
		}

		$this->obj_template->__set('bln_installedCompletely', $this->bln_installedCompletely);
	}

	public function parse()
	{
		return $this->obj_template->parse();
	}
}