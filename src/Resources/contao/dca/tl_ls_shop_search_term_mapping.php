<?php
declare(strict_types=1);

namespace Merconis\Core;

use Contao\Backend;
use Contao\DC_Table;
use Contao\DataContainer;
use Contao\Database;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Message;
use LeadingSystems\MerconisBundle\ProductSearch\SearchTermMappingService;
use Psr\Log\LoggerInterface;

$GLOBALS['TL_DCA']['tl_ls_shop_search_term_mapping'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
		'enableVersioning' => true,
		'onload_callback' => array(
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'adjustSubpalettes')
		),
		'onsubmit_callback' => array(
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'validateNodePlacement'),
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'invalidateCache'),
			array('Merconis\\Core\\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'ondelete_callback' => array(
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'invalidateCache'),
			array('Merconis\\Core\\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'oncopy_callback' => array(
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'invalidateCache'),
			array('Merconis\\Core\\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'onrestore_callback' => array(
			array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'invalidateCache'),
			array('Merconis\\Core\\ls_shop_generalHelper', 'saveLastBackendDataChangeTimestamp')
		),
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
				'pid' => 'index',
				'sourceNormalized' => 'index',
				'active' => 'index'
			)
		)
	),

	'list' => array(
		'sorting' => array(
			'mode' => DataContainer::MODE_TREE,
			'fields' => array('sorting'),
			'flag' => DataContainer::SORT_ASC,
			'rootPaste' => true,
			'showRootTrails' => true,
			'panelLayout' => 'filter,sort;search,limit',
			'paste_button_callback' => array('Merconis\\Core\\tl_ls_shop_search_term_mapping_controller', 'pasteButtons')
		),

		'label' => array(
			'fields' => array('sourceTerm', 'targetTerm', 'active', 'nodeType'),
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
			'cut' => array
			(
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset()"'
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
		'__selector__' => array('type', 'matchType', 'removeSource'),
		'default' => '{general_legend},type,title,sorting,active',
		'group' => '{general_legend},type,title,sorting,active',
		'mapping' => '{general_legend},type,title,sorting,matchType,mode,targetTerm,removeSource,active'
	),
	'subpalettes' => array(
		'matchType_exact' => 'sourceTerm',
		'matchType_regex' => 'pattern,caseInsensitive',
		'removeSource' => 'removeSourceTiming'
	),

	'fields' => array(
		'id' => array(
			'sql' => 'int(10) unsigned NOT NULL auto_increment'
		),
		'pid' => array(
			'sql' => "int(10) unsigned NOT NULL default '0'"
		),
		'type' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['nodeType'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('group', 'mapping'),
			'reference' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['nodeType_options'],
			'eval' => array('mandatory' => true, 'includeBlankOption' => false, 'submitOnChange' => true, 'tl_class' => 'w50'),
			'sql' => "varchar(16) NOT NULL default 'mapping'"
		),
		'mode' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['mode'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('both', 'full', 'quick'),
			'reference' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['mode_options'],
			'eval' => array('includeBlankOption' => false, 'tl_class' => 'w50'),
			'filter' => true,
			'sql' => "varchar(12) NOT NULL default 'both'"
		),
		'tstamp' => array (
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sorting' => array (
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['sorting'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('rgxp' => 'digit', 'maxlength' => 10, 'tl_class' => 'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'),
			'flag' => 1,
			'filter' => true,
			'search' => true,
			'sql' => "varchar(255) NOT NULL default ''"
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
			'eval' => array('tl_class' => 'w50 m12', 'submitOnChange' => true),
			'filter' => true,
			'sql' => "char(1) NOT NULL default '1'"
		),
		'removeSourceTiming' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['removeSourceTiming'],
			'exclude' => true,
			'inputType' => 'select',
			'options' => array('after', 'immediate'),
			'reference' => &$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['removeSourceTiming_options'],
			'eval' => array('includeBlankOption' => false, 'tl_class' => 'w50'),
			'sql' => "varchar(16) NOT NULL default ''"
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

    public function updateSourceNormalized(string $value = '', ?DataContainer $dc = null): string {
        $matchType = null;
        if ($dc && $dc->activeRecord && property_exists($dc->activeRecord, 'matchType')) {
            $matchType = (string) $dc->activeRecord->matchType;
        } else {
            // Fallback fetch
            $matchTypeQueryResult = Database::getInstance()->prepare("SELECT matchType FROM tl_ls_shop_search_term_mapping WHERE id=?")->limit(1)->execute($dc->id);
            $matchType = $matchTypeQueryResult->next() ? (string) $matchTypeQueryResult->matchType : 'exact';
        }
        if ($matchType === 'exact') {
            $normalizedSourceTerm = trim(mb_strtolower($value));
            Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET sourceNormalized=? WHERE id=?")
                ->execute($normalizedSourceTerm, $dc->id);
        } else {
            // Ensure normalized is cleared when switching to regex
            Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET sourceNormalized='' WHERE id=?")
                ->execute($dc->id);
        }
        return $value;
    }

    public function createLabel(array $row, string $label): string {
		$activeSuffix = ($row['active'] ? '' : ' (inactive)');
		$modeKey = (string)($row['mode'] ?? 'both');
		$modeLabel = $GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['mode_options'][$modeKey] ?? $modeKey;
		$modeSuffix = sprintf(' [%s]', $modeLabel);
		$type = (string)($row['type'] ?? 'mapping');
		$icon = $type === 'group' ? 'folder.svg' : 'file.svg';
		$iconHtml = Image::getHtml($icon, $type);
		$title = (string)($row['title'] ?? '');
		if ($type === 'group') {
			$display = $title !== '' ? $title : ($GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['defaultGroupTitle'] ?? 'Group');
			return sprintf('%s %s%s', $iconHtml, $display, $activeSuffix);
		}
		if ($title !== '') {
			return sprintf('%s %s%s%s', $iconHtml, $title, $modeSuffix, $activeSuffix);
		}
		$sourceDisplay = (isset($row['matchType']) && $row['matchType'] === 'regex')
			? (function(array $r): string {
				$pattern = (string)($r['pattern'] ?? '');
				$flags = ((string)($r['caseInsensitive'] ?? '') === '1') ? 'i' : '';
				return $flags !== '' ? sprintf('/%s/%s', $pattern, $flags) : sprintf('/%s/', $pattern);
			})($row)
			: (string)($row['sourceTerm'] ?? '');
		return sprintf('%s %s → %s%s%s', $iconHtml, $sourceDisplay, (string)($row['targetTerm'] ?? ''), $modeSuffix, $activeSuffix);
	}

    public function toggleIcon($row, $href, $label, $title, $icon, $attributes): string {
		if ((string) Input::get('tid') !== '') {
			$this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1));
			$this->redirect($this->getReferer());
		}

		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_search_term_mapping::active', 'alexf')) {
			return '';
		}

		$href = (string) $href;
		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['active'] ? '' : 1);

		if (!$row['active']) {
			$icon = 'invisible.svg';
		}

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
	}

    public function toggleVisibility(int $intId, bool $blnVisible): void {
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_ls_shop_search_term_mapping::active', 'alexf')) {
			$container = System::getContainer();
			if ($container && $container->has('logger')) {
				$logger = $container->get('logger');
				if ($logger instanceof LoggerInterface) {
					$logger->error('Not enough permissions to publish/unpublish mapping', [
						'mappingId' => $intId,
						'context' => 'tl_ls_shop_search_term_mapping toggleVisibility',
					]);
				}
			}
			$this->redirect('contao/main.php?act=error');
		}

		Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET tstamp=". time() .", active='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
			->execute($intId);
	}

	public function validateNodePlacement(?DataContainer $dc = null): void {
		if (!$dc || !$dc->activeRecord) { return; }
		$nodeType = (string) ($dc->activeRecord->type ?? 'mapping');
		$pid = (int) ($dc->activeRecord->pid ?? 0);

		// Prevent mapping under mapping (no children for mappings)
		if ($pid > 0) {
			$parent = Database::getInstance()->prepare("SELECT id, pid, type FROM tl_ls_shop_search_term_mapping WHERE id=?")->limit(1)->execute($pid);
			if ($parent->next()) {
				$parentType = (string) ($parent->type ?? 'mapping');
				if ($parentType === 'mapping') {
					$newPid = (int) ($parent->pid ?? 0); // lift alongside the parent
					Message::addError($GLOBALS['TL_LANG']['ERR']['merconis_mapping_no_children_for_mapping'] ?? 'A Mapping cannot be a parent. The record has been moved up one level.');
					Database::getInstance()->prepare("UPDATE tl_ls_shop_search_term_mapping SET pid=? WHERE id=?")->execute($newPid, (int)$dc->activeRecord->id);
				}
			}
		}
	}

	public function pasteButtons(DataContainer $dc, $row, $table, $cr, $arrClipboard=null) {
		$disablePA = false;
		$disablePI = false;

		// Circular reference checks (from core)
		if ($arrClipboard !== false && (($arrClipboard['mode'] == 'cut' && ($cr == 1 || $arrClipboard['id'] == $row['id'])) || ($arrClipboard['mode'] == 'cutAll' && ($cr == 1 || (is_array($arrClipboard['id']) && in_array($row['id'], $arrClipboard['id'])))))) {
			$disablePA = true;
			$disablePI = true;
		}

		$type = (string) ($row['type'] ?? 'mapping');

		// Never allow "paste into" under a mapping
		if ($type === 'mapping') {
			$disablePI = true;
		}

		// Moving a group: allow deep nesting; only forbid paste-into under a mapping (handled above)
		if ($arrClipboard !== false && !empty($arrClipboard['id'])) {
			$movingId = is_array($arrClipboard['id']) ? (int) reset($arrClipboard['id']) : (int) $arrClipboard['id'];
			if ($movingId > 0) {
				$moving = Database::getInstance()->prepare("SELECT type FROM tl_ls_shop_search_term_mapping WHERE id=?")->limit(1)->execute($movingId);
				if ($moving->next()) {
					$movingType = (string) ($moving->type ?? 'mapping');
					// No extra restriction for groups; mapping restriction already applied
				}
			}
		}

		$imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1] ?? 'Paste after ID %s', $row['id'] ?? 0));
		$imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1] ?? 'Paste into ID %s', $row['id'] ?? 0));

		$return = '';
		if (($row['id'] ?? 0) > 0) {
			$return = $disablePA
				? Image::getHtml('pasteafter_.svg') . ' '
				: '<a href="' . $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=1&amp;pid=' . $row['id'] . (!is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')) . '" title="' . StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1] ?? 'Paste after ID %s', $row['id'])) . '" onclick="Backend.getScrollOffset()">' . $imagePasteAfter . '</a> ';
		}

		$return .= $disablePI
			? Image::getHtml('pasteinto_.svg') . ' '
			: '<a href="' . $this->addToUrl('act=' . $arrClipboard['mode'] . '&amp;mode=2&amp;pid=' . ($row['id'] ?? 0) . (!is_array($arrClipboard['id']) ? '&amp;id=' . $arrClipboard['id'] : '')) . '" title="' . StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][($row['id'] ?? 0) > 0 ? 1 : 0] ?? 'Paste into ID %s', $row['id'] ?? 0)) . '" onclick="Backend.getScrollOffset()">' . $imagePasteInto . '</a> ';

		return $return;
	}

    public function validatePattern(string $value = '', ?DataContainer $dc = null): string {
		// Only validate when matchType is regex
		$matchType = null;
		if ($dc && $dc->activeRecord && property_exists($dc->activeRecord, 'matchType')) {
			$matchType = (string) $dc->activeRecord->matchType;
		}
		if ($matchType !== 'regex') {
			return $value;
		}
        $patternRaw = trim($value);
        if ($patternRaw === '') {
			return $value;
		}
		// Determine caseInsensitive flag
        $caseInsensitiveFlag = '';
		if ($dc && $dc->activeRecord && property_exists($dc->activeRecord, 'caseInsensitive')) {
            $caseInsensitiveFlag = (string) $dc->activeRecord->caseInsensitive;
		}
		$delimiter = '#';
        $escaped = str_replace($delimiter, '\\' . $delimiter, $patternRaw);
        $flags = 'u' . ($caseInsensitiveFlag ? 'i' : '');
		$compiled = $delimiter . $escaped . $delimiter . $flags;
		set_error_handler(function() {});
		try {
            $isValidPattern = @preg_match($compiled, '') !== false;
		} finally {
			restore_error_handler();
		}
        if (!$isValidPattern) {
			throw new \RuntimeException('Invalid regex pattern for mapping: ' . $compiled);
		}
		return $value;
	}

    public function invalidateCache(): void {
		$container = System::getContainer();
		/** @var SearchTermMappingService $searchTermMappingService */
		$searchTermMappingService = $container->get(SearchTermMappingService::class);
		$searchTermMappingService->clearCache();
    }

	public function adjustSubpalettes(?DataContainer $dc = null): void {
		// Only display removeSourceTiming when matchType is 'regex'
		$matchType = null;
		if ($dc && $dc->activeRecord && property_exists($dc->activeRecord, 'matchType')) {
			$matchType = (string) $dc->activeRecord->matchType;
		} elseif ($dc && $dc->id) {
			$recordQueryResult = Database::getInstance()->prepare("SELECT matchType FROM tl_ls_shop_search_term_mapping WHERE id=?")->limit(1)->execute($dc->id);
			if ($recordQueryResult->next()) {
				$matchType = (string) $recordQueryResult->matchType;
			}
		}
		if ($matchType !== 'regex') {
			// Remove subpalette so the selector 'removeSource' does not reveal timing on exact matches
			if (isset($GLOBALS['TL_DCA']['tl_ls_shop_search_term_mapping']['subpalettes']['removeSource'])) {
				unset($GLOBALS['TL_DCA']['tl_ls_shop_search_term_mapping']['subpalettes']['removeSource']);
			}
		}
	}
}


