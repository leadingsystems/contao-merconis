<?php

namespace Merconis\Core;

$GLOBALS['TL_DCA']['tl_ls_shop_cross_seller'] = array(
	'config' => array(
		'dataContainer' => 'Table',
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
			'mode' => 1,
			'flag' => 1,
			'fields' => array('title'),
			'disableGrouping' => true,
			'panelLayout' => 'filter;search,limit'
		),
		
		'label' => array(
			'fields' => array('title'),
			'format' => '%s'
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
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		
		)	
	),
	
	'palettes' => array(
		'__selector__' => array('productSelectionType'),
		'default' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType;',
		'hookSelection' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType',
		'directSelection' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType;{directSelection_legend},productDirectSelection',
		'searchSelection' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType;{searchSelection_legend},maxNumProducts,
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
									groupStopSearchSelectionTags',
		'lastSeen' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType;{lastSeen_legend},maxNumProducts',
		'favorites' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType',
		'restockInfoList' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType',
		'recommendedProducts' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType;{recommendedProducts_legend},maxNumProducts',
		'frontendProductSearch' => '{title_legend},title;{textOutput_legend},text01,text02;{template_legend},template,doNotUseCrossSellerOutputDefinitions;{fallbackCrossSeller_legend},fallbackCrossSeller,fallbackOutput;{filterSettings_legend},canBeFiltered;{published_legend},published;{productSelectionType_legend},productSelectionType;{frontendProductSearch_legend},maxNumProducts,noOutputIfMoreThanMaxResults'
	),
	
	'fields' => array(
        'id' => array (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
		'title' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['title'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength'=>255),
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'text01' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['text01'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true, 'merconis_multilanguage_noTopLinedGroup' => true),
			'search' => true,
            'sql'                     => "text NULL"
		),
		
		'text02' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['text02'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true),
			'search' => true,
            'sql'                     => "text NULL"
		),
		
		'template' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['template'],
			'default'                 => 'template_crossSeller_default',
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('template_crossSeller_'),
			'filter' => true,
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "varchar(64) NOT NULL default ''"
		),
		
		'doNotUseCrossSellerOutputDefinitions' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['doNotUseCrossSellerOutputDefinitions'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'fallbackCrossSeller' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackCrossSeller'],
			'default'                 => '0',
			'exclude' => true,
			'inputType'               => 'select',
			'options_callback'		  => array('Merconis\Core\ls_shop_generalHelper', 'getAlternativeCrossSellerOptions'),
			'filter' => true,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'fallbackOutput' => array (
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['fallbackOutput'],
			'exclude' => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true),
			'search' => true,
            'sql'                     => "text NULL"
		),
		
		'canBeFiltered' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['canBeFiltered'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),

		'published' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['published'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true, 'tl_class'=>'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'productSelectionType' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType'],
			'default'                 => 'directSelection',
			'exclude' => true,
			'inputType'               => 'select',
			'options'                 => array(
			    'directSelection',
                'searchSelection',
                'lastSeen',
                'recommendedProducts',
                'frontendProductSearch',
                'favorites',

/*
 * Deactivate this type of CrossSeller as long as product lists aren't able to show variants
 */
//                'restockInfoList',


                'hookSelection'
            ),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productSelectionType']['options'],
			'eval'					  => array('helpwizard' => true, 'submitOnChange' => true),
			'filter' => true,
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		'productDirectSelection' => array(
			'label'			=>	&$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['productDirectSelection'],
			'exclude' => true,
			'inputType'		=>	'ls_shop_productSelectionWizard',
			'eval'			=> array('tl_class'=>'clr ls_beBlock'),
            'sql'                     => "blob NULL"
		),
		
		'maxNumProducts' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['maxNumProducts'],
			'exclude' => true,
			'inputType' => 'text',
			'filter' => true,
			'eval'					  => array('tl_class' => 'clr', 'mandatory' => true, 'rgxp'=>'digit'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		
		'noOutputIfMoreThanMaxResults' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['noOutputIfMoreThanMaxResults'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50 m12'),
            'sql'                     => "char(1) NOT NULL default ''"
		),


		
		'groupStartSearchSelectionNewProduct' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionNewProduct'].'</h3>')
		),
		
		'groupStopSearchSelectionNewProduct' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),
		
		'activateSearchSelectionNewProduct' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'searchSelectionNewProduct' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionNewProduct'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('new', 'notNew'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionNewProduct']['options'],
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "varchar(64) NOT NULL default ''"
		),


		
		'groupStartSearchSelectionSpecialPrice' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionSpecialPrice'].'</h3>')
		),
		
		'groupStopSearchSelectionSpecialPrice' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),
		
		'activateSearchSelectionSpecialPrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'searchSelectionSpecialPrice' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionSpecialPrice'],
			'exclude' => true,
			'inputType'               => 'select',
			'options'				  => array('specialPrice', 'noSpecialPrice'),
			'reference'				  => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionSpecialPrice']['options'],
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "varchar(64) NOT NULL default ''"
		),
		
		
		
		'groupStartSearchSelectionCategory' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionCategory'].'</h3><p>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['subHeadlineSearchSelectionCategory'].'</p>')
		),
		
		'groupStopSearchSelectionCategory' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),
		
		'activateSearchSelectionCategory' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'searchSelectionCategory' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionCategory'],
			'exclude' => true,
			'inputType'               => 'pageTree',
			'eval'                    => array('fieldType'=>'checkbox', 'multiple' => true),
            'sql'                     => "blob NULL"
		),
		
		
		
		'groupStartSearchSelectionProducer' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionProducer'].'</h3>')
		),
		
		'groupStopSearchSelectionProducer' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),
		
		'activateSearchSelectionProducer' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'searchSelectionProducer' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionProducer'],
			'exclude' => true,
			'inputType' => 'text',
			'eval'					  => array('tl_class' => 'w50', 'maxlength' => 255),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		
		
		'groupStartSearchSelectionProductName' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionProductName'].'</h3>')
		),
		
		'groupStopSearchSelectionProductName' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),
		
		'activateSearchSelectionProductName' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'searchSelectionProductName' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionProductName'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		
		
		'groupStartSearchSelectionArticleNr' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionArticleNr'].'</h3>')
		),
		
		'groupStopSearchSelectionArticleNr' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),
		
		'activateSearchSelectionArticleNr' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'searchSelectionArticleNr' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionArticleNr'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		),
		
		
		
		'groupStartSearchSelectionTags' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputBefore' => '<div class="ls_shop_beSubGroup"><div>', 'output' => '<h3>'.$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['headlineSearchSelectionTags'].'</h3>')
		),
		
		'groupStopSearchSelectionTags' => array(
			'input_field_callback'	  => array('Merconis\Core\ls_shop_generalHelper', 'simpleHTMLOutputForBE'),
			'eval'					  => array('outputAfter' => '<div class="clearFloat"></div></div></div>')
		),
		
		'activateSearchSelectionTags' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['activate'],
			'exclude' => true,
			'inputType'               => 'checkbox',
			'eval'					  => array('tl_class' => 'w50'),
            'sql'                     => "char(1) NOT NULL default ''"
		),
		
		'searchSelectionTags' => array(
			'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_cross_seller']['searchSelectionTags'],
			'exclude' => true,
			'inputType' => 'text',
			'eval' => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
		)
	)
);




