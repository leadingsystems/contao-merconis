<?php

namespace Merconis\Core;

use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class ls_shop_productSelection extends Widget
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
		$this->loadLanguageFile('be_productSearch');

		ob_start();
		?>
		<div class="widgetProductSelection">
			<input type="hidden" id="<?php echo $this->strId; ?>" name="<?php echo $this->strName; ?>" value="<?php echo $this->varValue; ?>" />
			<a onclick="ls_shop_productSelection.setCurrentlyActiveWidgetElement(getParent()); Backend.getScrollOffset();Backend.openModalIframe({'width':765,'title':'<?php echo StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['be_productSearch']['text008'])); ?>','url':this.href,'id':'<?php echo $this->strId; ?>'});return false;" href="<?= System::getContainer()->get('router')->generate('merconis.backend.product_search', ['mode' => 'productSelection']) ?>" title="<?php echo StringUtil::specialchars($GLOBALS['TL_LANG']['be_productSearch']['text008']); ?>">
				<?php echo Image::getHtml('filemanager.svg', $GLOBALS['TL_LANG']['MSC']['fileManager'], 'style="vertical-align:text-bottom"'); ?>
			</a>
			<div class="selectedProductOutput">
				<?php
					if ($this->varValue) {
						$objProductOutput = new ls_shop_productOutput($this->varValue, '', 'template_productBackendOverview_03');
						echo $objProductOutput->parseOutput();
					}
				?>					
			</div>
		</div>
		<?php
		$outputBuffer = ob_get_contents();
		ob_end_clean();

		return $outputBuffer;
	}
}

?>
