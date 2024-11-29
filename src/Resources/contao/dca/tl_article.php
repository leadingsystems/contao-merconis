<?php

namespace Merconis\Core;

/*
 * Conditional Output
 */
foreach ($GLOBALS['TL_DCA']['tl_article']['palettes'] as $paletteName => $palette)  {
	if ($paletteName == '__selector__') {
		continue;
	}
	$GLOBALS['TL_DCA']['tl_article']['palettes'][$paletteName] .= ';{lsShopConditionalOutput_legend},lsShopOutputCondition';
}
			
$GLOBALS['TL_DCA']['tl_article']['fields']['lsShopOutputCondition'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition'],
	'exclude'       => true,
	'inputType'		=> 'select',
	'options'		=> array('always', 'onlyInOverview', 'onlyInSingleview', 'onlyIfCartNotEmpty', 'onlyIfCartEmpty', 'onlyIfFeUserLoggedIn', 'onlyIfFeUserNotLoggedIn'),
	'reference'		=> &$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition']['options'],
	'eval'			=> array('tl_class' => 'w50', 'helpwizard' => true),
    'sql'                     => "varchar(32) NOT NULL default ''"
);


//-------------


$GLOBALS['TL_DCA']['tl_article']['fields']['lsShopIsProductInfo'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_article']['lsShopIsProductInfo'],
    'exclude'       => true,
    'inputType'		=> 'checkbox',
    'eval'			=> array('submitOnChange'=>true, 'tl_class' => 'w33-cbx-middle'),
    'sql'           => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_article']['fields']['lsShopIsProductInfoTextfield'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_article']['lsShopIsProductInfoTextfield'],
    'exclude'       => true,
    'inputType'		=> 'text',
    'eval'			=> array('tl_class' => 'w33'),
    'save_callback' => array
    (
        array('Merconis\Core\tl_article', 'generateAlias')
    ),
    'sql'           => "varchar(255) NOT NULL default ''"
);




array_unshift($GLOBALS['TL_DCA']['tl_article']['fields']['alias']['save_callback'], array('Merconis\Core\tl_article', 'generateAlias2'));


/*$GLOBALS['TL_DCA']['tl_article']['fields']['alias']['eval']['disabled'] = true;*/

/*

$GLOBALS['TL_DCA']['tl_article']['palettes']['__selector__'] = array('lsShopIsProductInfo');





//TODO: nicht überschreiben
$GLOBALS['TL_DCA']['tl_article']['subpalettes']['lsShopIsProductInfo'] = array(
    'lsShopIsProductInfoTextfield',
);*/

use Contao\Backend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    // adding the field as usual
    ->addField('lsShopIsProductInfo', 'alias')

    // now the field is registered in the PaletteManipulator
    // but it still has to be registered in the globals array:
    ->applyToPalette('default', 'tl_article')

    /*
    // remove a field from the subpalette
    ->removeField('floating')

    // applying the new configuration to the "addImage" subpalette
    ->applyToSubpalette('addImage', 'tl_content')*/
;




array_push($GLOBALS['TL_DCA']['tl_article']['palettes']['__selector__'], 'lsShopIsProductInfo');

$GLOBALS['TL_DCA']['tl_article']['subpalettes']['lsShopIsProductInfo'] = 'lsShopIsProductInfoTextfield';


/*
$GLOBALS['TL_DCA']['tl_article']['subpalettes'] = array
(
    'lsShopIsProductInfo' => 'lsShopIsProductInfoTextfield'
);*/

/*
PaletteManipulator::create()
    // adding the field as usual
    ->addField('lsShopIsProductInfoTextfield', 'alias')

    // remove a field from the subpalette
    //->removeField('floating')

    // applying the new configuration to the "addImage" subpalette
    ->applyToSubpalette('lsShopIsProductInfo', 'tl_article')
;*/





$GLOBALS['TL_DCA']['tl_article']['config']['onsubmit_callback'][] = array('Merconis\Core\tl_article', 'onSubmitCallback');





class tl_article extends Backend
{
    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import(\BackendUser::class, 'User');
    }

    public function generateAlias($varValue, \DataContainer $dc)
    {

        dump($dc);
        dump($dc->activeRecord);
        dump($dc->activeRecord->id);
        dump($dc->activeRecord->alias);

        $aliasExists = function (string $alias) use ($dc): bool
        {
            if (in_array($alias, array('top', 'wrapper', 'header', 'container', 'main', 'left', 'right', 'footer'), true))
            {
                return true;
            }

            return $this->Database->prepare("SELECT id FROM tl_article WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        // Generate an alias if there is none
        if ($dc->activeRecord->lsShopIsProductInfo == "1")
        {
            //dump("generate slug");
            $generatedAlias = \System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, $dc->activeRecord->pid, $aliasExists);

            //overwrite Alias
            /*
            \Database::getInstance()->prepare("UPDATE tl_article SET alias=? WHERE id=?")
                ->execute($generatedAlias, $dc->activeRecord->id);*/
        }
        elseif (preg_match('/^[1-9]\d*$/', $varValue))
        {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));

        }
        elseif ($aliasExists($varValue))
        {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));

        }

        return $varValue;
    }

    public function generateAlias2($varValue, \DataContainer $dc)
    {

        dump("generateAlias2");
        dump($dc);
        dump($dc->activeRecord);
        dump($dc->activeRecord->id);
        dump($dc->activeRecord->alias);
        dump($dc->activeRecord->lsShopIsProductInfo);
        dump($dc->activeRecord->lsShopIsProductInfoTextfield);

        $aliasExists = function (string $alias) use ($dc): bool
        {
            if (in_array($alias, array('top', 'wrapper', 'header', 'container', 'main', 'left', 'right', 'footer'), true))
            {
                return true;
            }

            return $this->Database->prepare("SELECT id FROM tl_article WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        // Generate an alias if there is none
        if ($dc->activeRecord->lsShopIsProductInfo == "1")
        {
            //dump("generate slug");
            $generatedAlias = \System::getContainer()->get('contao.slug')->generate($dc->activeRecord->lsShopIsProductInfoTextfield, $dc->activeRecord->pid, $aliasExists);
            dump("generated Alias2: ".$generatedAlias);

        }
        elseif (preg_match('/^[1-9]\d*$/', $varValue))
        {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));

        }
        elseif ($aliasExists($varValue))
        {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));

        }

        return $generatedAlias;
    }

    public function onSubmitCallback(\DataContainer $dc)
    {
        // Return if there is no ID
        /*
        if (!$dc->activeRecord || !$dc->activeRecord->id)
        {
            return;
        }*/

        /*
        dump($dc);
        dump($dc->activeRecord);
        dump($dc->activeRecord->id);
        dump($dc->activeRecord->alias);*/

        if($dc->activeRecord->lsShopIsProductInfo == '1'){
            $producer = $dc->activeRecord->lsShopIsProductInfoTextfield;

            //TODO check if alias already exists

            dump("createAliasAndSave");
            $generatedAlias = \System::getContainer()->get('contao.slug')->generate($producer, ['locale' => 'de']);

            /*
            \Database::getInstance()->prepare("UPDATE tl_article SET alias=? WHERE id=?")
                ->execute($generatedAlias, $dc->activeRecord->id);*/
        }



        /*
        $aliasExists = function (string $alias) use ($dc): bool
        {
            if (in_array($alias, array('top', 'wrapper', 'header', 'container', 'main', 'left', 'right', 'footer'), true))
            {
                return true;
            }

            return $this->Database->prepare("SELECT id FROM tl_article WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        //dump("generate slug");
        $varValue = \System::getContainer()->get('contao.slug')->generate($producer, $dc->activeRecord->pid, $aliasExists);
        //dump("generated alias: ".$varValue);



        if (preg_match('/^[1-9]\d*$/', $varValue))
        {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));

        }
        elseif ($aliasExists($varValue))
        {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));

        }*/


    }


}