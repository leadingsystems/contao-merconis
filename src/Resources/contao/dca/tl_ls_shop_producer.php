<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_ls_shop_producer'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
		'onsubmit_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'ondelete_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'oncopy_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'onrestore_callback' => array(
			array('Merconis\Core\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary'
            )
        )
	),

	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_SORTED,
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'fields' => array('producer'),
			'disableGrouping' => true,
			'panelLayout' => 'search,limit'
		),

		'label' => array(
			'fields' => array('producer'),
			'format' => '<strong>%s</strong>'
		),

		'global_operations' => array(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),

		'operations' => array(
			'edit' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"',
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)

		)
	),

	'palettes' => array(
		'default' => '{producer_legend},producer,article,description;'
	),

	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),

        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

		'producer' => array(
			'exclude' => true,
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['producer'],
            'inputType' => 'select',
            'foreignKey' => 'tl_ls_shop_product.lsShopProductProducer',
			'eval' => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50', 'maxlength'=>255),
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

        'article' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['article'],
            'inputType' => 'select',
            'foreignKey' => 'tl_article.title',
            'eval' => array('chosen' => true, 'tl_class' => 'w50', 'maxlength'=>255),
            'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'description' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['description'],
            'exclude' => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true),
            'search' => true,
            'sql'                     => "text NULL"
        ),

	)
);





class producer extends \Backend {
	public function generateAlias($varValue, \DataContainer $dc) {
		$autoAlias = false;

		// Generate an alias if there is none
		if ($varValue == '') {
			$autoAlias = true;
			$varValue = \StringUtil::generateAlias($dc->activeRecord->title);
		}

		$objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_ls_shop_producer WHERE id=? OR alias=?")
								   ->execute($dc->id, $varValue);

		// Check whether the alias exists
		if ($objAlias->numRows > 1) {
			if (!$autoAlias) {
				throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
			}
			$varValue .= '-' . $dc->id;
		}

		return $varValue;
	}

	/*
	 * In order to understand what this function is all about, take a look at ls_shop_generalHelper::parseSteuersatz(),
	 * $GLOBALS['MERCONIS_HOOKS']['customTaxRateCalculation'] and the comments for this hook!
	 *
	 * This function checks if this tax class is used with one or more products and if it
	 * is it checks if a wildcard is used as a tax value because that is not allowed
	 * for tax classes used with products.
	 */
	public function checkIfWildcardsUsedAndAllowed($varValue, \DataContainer $dc) {
		$objProducts = \Database::getInstance()->prepare("
			SELECT	`id`
			FROM	`tl_ls_shop_product`
			WHERE	`lsShopProductSteuersatz` = ?
		")
		->execute($dc->id);

		if (!$objProducts->numRows) {
			// return the value without further checks if the tax class is not used with at least one product
			return $varValue;
		}

		if (strpos($varValue, '#') !== false) {
			throw new \Exception($GLOBALS['TL_LANG']['tl_ls_shop_producer']['wildcardNotAllowed']);
		} else {
			return $varValue;
		}
	}
}
