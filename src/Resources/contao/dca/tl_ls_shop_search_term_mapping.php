<?php

namespace Merconis\Core;

use Contao\Backend;
use Contao\DC_Table;
use Contao\DataContainer;
use Contao\Database;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_ls_shop_search_term_mapping'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'enableVersioning' => true,
		'onsubmit_callback' => array(
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'invalidateCache')
		),
		'ondelete_callback' => array(
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'invalidateCache')
		),
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
				'sourceNormalized' => 'unique',
				'active' => 'index'
			)
		)
	),

	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_SORTABLE,
			'fields' => array('sorting'),
			'flag' => DataContainer::SORT_ASC,
			'panelLayout' => 'filter,sort;search,limit'
		),

		'label' => array(
			'fields' => array('sourceTerm', 'targetTerm', 'active'),
			'format' => '%s',
			'label_callback' => array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller','createLabel')
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
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['toggle'],
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'toggleIcon')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)

		)
	),
	'palettes' => array(
		'default' => '{general_legend},sourceTerm,targetTerm,removeSource,active'
	),

	'fields' => array(
		'id' => array(
			'sql' => 'int(10) unsigned NOT NULL auto_increment'
		),
		'tstamp' => array (
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sorting' => array (
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sourceTerm' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['sourceTerm'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'),
			'save_callback' => array(
				array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'updateSourceNormalized')
			),
			'flag' => 1,
			'filter' => true,
			'search' => true,
			'sql' => "varchar(255) NOT NULL default ''"
		),
		'sourceNormalized' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['sourceNormalized'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('readonly' => true, 'tl_class' => 'w50'),
			'sql' => "varchar(255) NOT NULL default ''"
		),
		'targetTerm' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['targetTerm'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'),
			'flag' => 1,
			'filter' => true,
			'search' => true,
			'sql' => "varchar(255) NOT NULL default ''"
		),
		'removeSource' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['removeSource'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'w50 m12'),
			'filter' => true,
			'sql' => "char(1) NOT NULL default '1'"
		),
		'active' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['active'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'w50 m12'),
			'filter' => true,
			'sql' => "char(1) NOT NULL default ''"
		)
	)
);

class tl_ls_shop_search_term_mapping_controller extends Backend {
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function updateSourceNormalized($value = '', DataContainer $dc = null) {
		$normalized = trim(mb_strtolower((string) $value));
		Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET sourceNormalized=? WHERE id=?")
			->execute($normalized, $dc->id);
		return $value;
	}

	public function createLabel($row, $label) {
		$activeSuffix = ($row['active'] ? '' : ' (inactive)');
		return sprintf('%s → %s%s', $row['sourceTerm'], $row['targetTerm'], $activeSuffix);
	}

	public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
		if (strlen(Input::get('tid'))) {
			$this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1));
			$this->redirect($this->getReferer());
		}

		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_search_term_mapping::active', 'alexf')) {
			return '';
		}

		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['active'] ? '' : 1);

		if (!$row['active']) {
			$icon = 'invisible.svg';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}

	public function toggleVisibility($intId, $blnVisible) {
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_search_term_mapping::active', 'alexf')) {
			System::log('Not enough permissions to publish/unpublish mapping ID "'.$intId.'"', 'tl_ls_shop_search_term_mapping toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET tstamp=". time() .", active='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
			->execute($intId);
	}

	public function invalidateCache(): void {
		try {
			$container = System::getContainer();
			if ($container && $container->has('LeadingSystems\\MerconisBundle\\ProductSearch\\SearchTermMappingService')) {
				$service = $container->get('LeadingSystems\\MerconisBundle\\ProductSearch\\SearchTermMappingService');
				if (method_exists($service, 'clearCache')) {
					$service->clearCache();
				}
			}
		} catch (\Throwable $e) {
		}
	}
}


