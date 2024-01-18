<?php

namespace Merconis\Core;

use Contao\Widget;

class ls_shop_htmlDiv extends Widget
{

	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = false;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';


	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate() {
		return sprintf('<div style="padding: 25px;">%s</div>%s',
						$this->varValue,
						$this->wizard);
	}
}

?>