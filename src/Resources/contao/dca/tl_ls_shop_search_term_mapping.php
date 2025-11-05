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
				'sourceNormalized' => 'index',
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
		'__selector__' => array('matchType'),
		'default' => '{general_legend},matchType,targetTerm,removeSource,active'
	),
	'subpalettes' => array(
		'matchType_exact' => 'sourceTerm',
		'matchType_regex' => 'pattern,caseInsensitive'
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
		'matchType' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['matchType'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('exact', 'regex'),
			'eval' => array('mandatory' => true, 'includeBlankOption' => false, 'submitOnChange' => true, 'tl_class' => 'w50'),
			'sql' => "varchar(16) NOT NULL default 'exact'"
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
		'pattern' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['pattern'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 1024, 'decodeEntities' => true, 'tl_class' => 'w50'),
			'save_callback' => array(
				array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'validatePattern')
			),
			'sql' => "varchar(1024) NOT NULL default ''"
		),
		'caseInsensitive' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['caseInsensitive'],
			'exclude' => true,
			'inputType' => 'checkbox',
			'eval' => array('tl_class' => 'w50 m12'),
			'filter' => true,
			'sql' => "char(1) NOT NULL default ''"
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
		$matchType = null;
		if ($dc && $dc->activeRecord && property_exists($dc->activeRecord, 'matchType')) {
			$matchType = (string) $dc->activeRecord->matchType;
		} else {
			// Fallback fetch
			$rec = Database::getInstance()->prepare("SELECT matchType FROM tl_ls_shop_search_term_mapping WHERE id=?")->limit(1)->execute($dc->id);
			$matchType = $rec->next() ? (string) $rec->matchType : 'exact';
		}
		if ($matchType === 'exact') {
			$normalized = trim(mb_strtolower((string) $value));
			Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET sourceNormalized=? WHERE id=?")
				->execute($normalized, $dc->id);
		} else {
			// Ensure normalized is cleared when switching to regex
			Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET sourceNormalized='' WHERE id=?")
				->execute($dc->id);
		}
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

	public function validatePattern($value = '', DataContainer $dc = null) {
		// Only validate when matchType is regex
		$matchType = null;
		if ($dc && $dc->activeRecord && property_exists($dc->activeRecord, 'matchType')) {
			$matchType = (string) $dc->activeRecord->matchType;
		}
		if ($matchType !== 'regex') {
			return $value;
		}
		$raw = (string) $value;
		$raw = trim($raw);
		if ($raw === '') {
			return $value;
		}
		// Determine caseInsensitive flag
		$ci = '';
		if ($dc && $dc->activeRecord && property_exists($dc->activeRecord, 'caseInsensitive')) {
			$ci = (string) $dc->activeRecord->caseInsensitive;
		}
		$delimiter = '#';
		$escaped = str_replace($delimiter, '\\' . $delimiter, $raw);
		$flags = 'u' . ($ci ? 'i' : '');
		$compiled = $delimiter . $escaped . $delimiter . $flags;
		set_error_handler(function() {});
		try {
			$ok = @preg_match($compiled, '') !== false;
		} finally {
			restore_error_handler();
		}
		if (!$ok) {
			throw new \RuntimeException('Invalid regex pattern for mapping: ' . $compiled);
		}
		return $value;
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


