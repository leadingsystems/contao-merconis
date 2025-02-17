<?php
namespace Merconis\Core;

use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;

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
		'default' => '{producer_legend},producer,producerInfoShort,producerInfoExtended;'
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
			'eval' => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50', 'maxlength'=>255),
			'search' => true,
            'sql'                     => "varchar(255) NOT NULL default ''",
            'options_callback' => array('Merconis\Core\tl_ls_shop_producer', 'buttonCallbackSelectProducer'),
            'save_callback' => array(
                array('Merconis\Core\tl_ls_shop_producer', 'saveCallBackSelectProducer')
            )
		),

        'producerInfoShort' => array (
            'label'                   => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['producerInfoShort'],
            'exclude' => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr', 'merconis_multilanguage' => true),
            'search' => true,
            'sql'                     => "text NULL"
        ),

        'producerInfoExtended' => array(
            'exclude' => true,
            'label' => &$GLOBALS['TL_LANG']['tl_ls_shop_producer']['producerInfoExtended'],
            'inputType' => 'picker',
            'relation' => [
                'type' => 'hasOne',
                'load' => 'lazy',
                'table' => 'tl_article'
            ],
            'eval' => ['tl_class' => 'clr'],
            'sql' => [
                'type' => 'integer',
                'unsigned' => true,
                'default' => 0,
            ],
        ),

	)
);





class tl_ls_shop_producer extends \Backend {

    public function saveCallBackSelectProducer($varValue, DataContainer $dc)
    {
        $obj_article = Database::getInstance()->prepare("SELECT * FROM tl_ls_shop_producer WHERE producer=?")
            ->limit(1)
            ->execute($varValue);

        //if the data record already has this producer and it's not the same currently edited
        if ($obj_article->numRows > 0 && $obj_article->fetchAssoc()['id'] != $dc->id)
        {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['producerExists'], $varValue));
        }

        return $varValue;
    }

    public function buttonCallbackSelectProducer(DataContainer $dc)
    {
        $activeRecord = $dc->activeRecord;

        $arrProductProducerNames = [];

        $dbres_producersWithAlreadyExistsFlag = Database::getInstance()->prepare("
            SELECT 
                DISTINCT p.lsShopProductProducer AS producerName, 
                CASE 
                    WHEN pr.producer IS NOT NULL THEN 1 
                    ELSE '' 
                END AS alreadyExists
            FROM 
                tl_ls_shop_product p
            LEFT JOIN 
                tl_ls_shop_producer pr ON p.lsShopProductProducer = pr.producer
            HAVING producerName != ''
        ")
            ->execute();
        while ($dbres_producersWithAlreadyExistsFlag->next()) {
            $arrProductProducerNames[$dbres_producersWithAlreadyExistsFlag->producerName] = $dbres_producersWithAlreadyExistsFlag->producerName . ($dbres_producersWithAlreadyExistsFlag->alreadyExists && $activeRecord->producer != $dbres_producersWithAlreadyExistsFlag->producerName ? ' '.$GLOBALS['TL_LANG']['ERR']['selectProducerExists'] : '');
        }

        return $arrProductProducerNames;
    }
}