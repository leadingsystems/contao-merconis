<?php

namespace Merconis\Core;

class dashboard extends \BackendModule {

	protected $strTemplate = 'lsm_dashboard';
	
	
	protected function compile() {
		$this->Template->str_output = '';

		$obj_installer = Installer::getInstance();
		$this->Template->str_output .= $obj_installer->parse();
	}
}
?>