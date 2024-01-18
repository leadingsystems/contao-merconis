<?php

namespace Merconis\Core;

use Contao\Widget;

class ls_shop_simpleOutput extends Widget {

	protected $blnSubmitInput = false;

	protected $strTemplate = 'be_widget';

	protected $arrContents = array();


	public function __set($strKey, $varValue) {
		parent::__set($strKey, $varValue);
	}


	protected function validator($varInput) {
	}


	public function generate() {
		return '<div class="simpleOutput">'.$this->varValue.'</div>';
	}
}

?>