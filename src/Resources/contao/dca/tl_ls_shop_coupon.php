<?php

namespace Merconis\Core;

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_ls_shop_coupon'] = array(
	'config' => array(
		'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
		'onsubmit_callback' => array(
			array('Merconis\Core\tl_ls_shop_coupon_controller', 'changeNumAvailable'),
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
			'mode' => DataContainer::MODE_SORTABLE,
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'fields' => array('title','productCode','couponCode'),
			'disableGrouping' => true,
			'panelLayout' => 'filter;sort,search,limit'
		),
		
		'label' => array(
			'fields' => array('title','productCode','couponCode'),
			'format' => '%s (%s | %s)'
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		
		)	
	),
	
	'palettes' => array(

        '__selector__' => array('limitNumAvailable','productSelectionType'),
		'default' => '{title_legend},title;{status_legend},published;{generalSettings_legend},productCode,couponCode,couponValueType,couponValue,description,minimumOrderValue,allowedForGroups,start,stop;{numAvailable_legend},limitNumAvailable;{productSelectionType_legend},productSelectionType',
		'noSelection' => '{title_legend},title;{status_legend},published;{generalSettings_legend},productCode,couponCode,couponValueType,couponValue,description,minimumOrderValue,allowedForGroups,start,stop;{numAvailable_legend},limitNumAvailable;{productSelectionType_legend},productSelectionType',
		'directSelection' => '{title_legend},title;{status_legend},published;{generalSettings_legend},productCode,couponCode,couponValueType,couponValue,description,minimumOrderValue,allowedForGroups,start,stop;{numAvailable_legend},limitNumAvailable;{productSelectionType_legend},productSelectionType;{directSelection_legend},productBlacklist, minimumOrderValueforCoupon,productDirectSelection',
		'searchSelection' => '{title_legend},title;{status_legend},published;{generalSettings_legend},productCode,couponCode,couponValueType,couponValue,description,minimumOrderValue,allowedForGroups,start,stop;{numAvailable_legend},limitNumAvailable;{productSelectionType_legend},productSelectionType;{searchSelection_legend},productBlacklist, minimumOrderValueforCoupon,
									groupStartSearchSelectionNewProduct,
									activateSearchSelectionNewProduct,
									searchSelectionNewProduct,
									groupStopSearchSelectionNewProduct,
									
									groupStartSearchSelectionSpecialPrice,
									activateSearchSelectionSpecialPrice,
									searchSelectionSpecialPrice,
									groupStopSearchSelectionSpecialPrice,
									
									groupStartSearchSelectionCategory,
									activateSearchSelectionCategory,
									searchSelectionCategory,
									groupStopSearchSelectionCategory,
									
									groupStartSearchSelectionProducer,
									activateSearchSelectionProducer,
									searchSelectionProducer,
									groupStopSearchSelectionProducer,
									
									groupStartSearchSelectionProductName,
									activateSearchSelectionProductName,
									searchSelectionProductName,
									groupStopSearchSelectionProductName,
									
									groupStartSearchSelectionArticleNr,
									activateSearchSelectionArticleNr,
									searchSelectionArticleNr,
									groupStopSearchSelectionArticleNr,
									
									groupStartSearchSelectionTags,
									activateSearchSelectionTags,
									searchSelectionTags,
									groupStopSearchSelectionTags'

	),
	
	'subpalettes' => array(
		'limitNumAvailable'           => 'numAvailable,changeNumAvailable'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

        'maxNumProducts' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),

		'title' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['title'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'		              => array('tl_class'=>'w50', 'mandatory' => true, 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true, 'maxlength'=>255),
			'sorting'                 => true,
			'flag'                    => 11,
			'search'                  => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'published' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['published'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class' => 'clr'),
			'filter'                  => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),

        'productBlacklist' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['blacklistActivate'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class' => 'clr w50 cbx m12'),
            'filter'                  => true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'minimumOrderValueforCoupon' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValueforCouponActivate'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class' => 'w50 cbx m12'),
            'filter'                  => true,
            'sql'                     => "char(1) NOT NULL default ''"
        ),
		
		'productCode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productCode'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class' => 'w50', 'maxlength'=>255),
			'sorting'                 => true,
			'flag'                    => 11,
			'search'                  => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'couponCode' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponCode'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class' => 'w50', 'mandatory' => true, 'unique' => true, 'maxlength'=>255),
			'sorting'                 => true,
			'flag'                    => 11,
			'search'                  => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'couponValueType' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValueType'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('fixed', 'percentaged'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValueType']['options'],
			'eval'                    => array('tl_class' => 'w50'),
			'filter'                  => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'couponValue' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValue'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'helpwizard' => true, 'mandatory' => true),
			'reference'               => array($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['couponValue']),
			'filter'                  => true,
            'sql'                     => "decimal(10,2) NOT NULL default '0.00'"
		),

		'description' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['description'],
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true),
			'search'                  => true,
            'sql'                     => "text NULL"
		),
		
		'minimumOrderValue' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['minimumOrderValue'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp' => 'numberWithDecimals', 'tl_class' => 'w50', 'mandatory' => true),
			'filter'                  => true,
            'sql'                     => "decimal(10,2) NOT NULL default '0.00'"
		),

		'allowedForGroups' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['allowedForGroups'],
			'exclude'                 => true,
			'inputType'               => 'checkboxWizard',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('tl_class'=>'clr','multiple'=>true),
            'sql'                     => "blob NULL"
		),
		
		'start' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['start'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard', 'mandatory' => true),
            'sql'                     => "varchar(10) NOT NULL default ''"
		),
		
		'stop' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['stop'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard', 'mandatory' => true),
            'sql'                     => "varchar(10) NOT NULL default ''"
		),
		
		'limitNumAvailable' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['limitNumAvailable'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class' => 'clr', 'submitOnChange' => true),
			'filter'                  => true,
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'numAvailable' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['numAvailable'],
			'exclude'                 => true,
			'inputType'               => 'simpleOutput',
			'eval'                    => array('tl_class' => 'w50', 'mandatory' => true, 'rgxp' => 'digit'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'changeNumAvailable' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['changeNumAvailable'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('tl_class' => 'w50', 'mandatory' => false),
            'sql'                     => "varchar(10) NOT NULL default ''"
		),

		'productSelectionType' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType'],
			'default'                 => 'noSelection',
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('noSelection', 'directSelection', 'searchSelection'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productSelectionType']['options'],
			'eval'					  => array('helpwizard' => true, 'submitOnChange' => true),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),

        'productDirectSelection' => array(
            'label'			          =>	&$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['productDirectSelection'],
            'exclude'                 => true,
            'inputType'		          =>	'ls_shop_productSelectionWizard',
            'eval'			          => array('tl_class'=>'clr'),
            'sql'                     => "blob NULL"
        ),

        'groupStartSearchSelectionNewProduct' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionNewProduct'] ?? '').'</h3>')
        ),

        'groupStopSearchSelectionNewProduct' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
        ),

        'activateSearchSelectionNewProduct' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'searchSelectionNewProduct' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionNewProduct'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'options'				  => array('new', 'notNew'),
            'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionNewProduct']['options'],
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "varchar(64) NOT NULL default ''"
        ),

        'groupStartSearchSelectionSpecialPrice' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionSpecialPrice'] ?? '').'</h3>')
        ),

        'groupStopSearchSelectionSpecialPrice' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
        ),

        'activateSearchSelectionSpecialPrice' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'searchSelectionSpecialPrice' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionSpecialPrice'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'options'				  => array('specialPrice', 'noSpecialPrice'),
            'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionSpecialPrice']['options'],
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "varchar(64) NOT NULL default ''"
        ),

        'groupStartSearchSelectionCategory' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionCategory'] ?? '').'</h3><p>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['subHeadlineSearchSelectionCategory'] ?? '').'</p>')
        ),

        'groupStopSearchSelectionCategory' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
        ),

        'activateSearchSelectionCategory' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'searchSelectionCategory' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionCategory'],
            'exclude'                 => true,
            'inputType'               => 'pageTree',
            'eval'                    => array('fieldType'=>'checkbox', 'multiple' => true),
            'sql'                     => "blob NULL"
        ),

        'groupStartSearchSelectionProducer' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionProducer'] ?? '').'</h3>')
        ),

        'groupStopSearchSelectionProducer' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
        ),

        'activateSearchSelectionProducer' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'searchSelectionProducer' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionProducer'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'groupStartSearchSelectionProductName' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionProductName'] ?? '').'</h3>')
        ),

        'groupStopSearchSelectionProductName' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
        ),

        'activateSearchSelectionProductName' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'searchSelectionProductName' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionProductName'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'groupStartSearchSelectionArticleNr' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionArticleNr'] ?? '').'</h3>')
        ),

        'groupStopSearchSelectionArticleNr' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
        ),

        'activateSearchSelectionArticleNr' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate'],
            'exclude' => true,
            'inputType'               => 'checkbox',
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'searchSelectionArticleNr' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionArticleNr'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),

        'groupStartSearchSelectionTags' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.($GLOBALS['TL_LANG']['tl_ls_shop_coupon']['headlineSearchSelectionTags'] ?? '').'</h3>')
        ),

        'groupStopSearchSelectionTags' => array(
            'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
            'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
        ),

        'activateSearchSelectionTags' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['activate'],
            'exclude' => true,
            'inputType'               => 'checkbox',
            'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
        ),

        'searchSelectionTags' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_coupon']['searchSelectionTags'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        )
	)
);





class tl_ls_shop_coupon_controller extends \Backend {

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}
	
	/*
	 * Update "numAvailable" by adding the value of "changeNumAvailable" or
	 * by simply setting to the value of "changeNumAvailable" if "changeNumAvailable"
	 * does not contain a minus or plus sign.
	 */
	public function changeNumAvailable($dc) {
		if ($dc->activeRecord->limitNumAvailable && $dc->activeRecord->changeNumAvailable !== '') {
			$obj_dbquery = \Database::getInstance()->prepare("
				UPDATE	`tl_ls_shop_coupon`
				SET		`numAvailable` = ".(
						strpos($dc->activeRecord->changeNumAvailable, '+') === false && strpos($dc->activeRecord->changeNumAvailable, '-') === false
					?	((int) $dc->activeRecord->changeNumAvailable)
					:	"`numAvailable` + ".((int) $dc->activeRecord->changeNumAvailable)
				).",
						`changeNumAvailable` = ''
				WHERE	`id` = ?
			")
			->limit(1)
			->execute($dc->activeRecord->id);
		}
	}
}