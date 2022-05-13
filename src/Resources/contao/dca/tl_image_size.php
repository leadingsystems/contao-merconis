<?php

namespace Merconis\Core;


// Anpassung der Palette
$GLOBALS['TL_DCA']['tl_image_size']['palettes']['default'] = str_replace
(
    'name,',
    'name,merconis_alias,',
    $GLOBALS['TL_DCA']['tl_image_size']['palettes']['default']
);

// Hinzufügen der Feld-Konfiguration
$GLOBALS['TL_DCA']['tl_image_size']['fields']['merconis_alias'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_image_size']['merconis_alias'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'  => array('rgxp'=>'alias', 'doNotCopy'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
    'sql'       => "varchar(255) NOT NULL default ''",
    'search'                  => true,
    'save_callback' => array
    (
        array('Merconis\Core\tl_image_size', 'generateAlias')
    ),


);




class tl_image_size extends \Backend
{
    public function __construct()
    {
        parent::__construct();
    }

    public function generateAlias($varValue, \Contao\DataContainer $dc)
    {
        $autoAlias = false;

        //if empty alias set alias value to name value
        if ($varValue == '') {
            $autoAlias = true;
            $varValue = \System::getContainer()->get('contao.slug')->generate (
                $dc->activeRecord->name, ['validChars' => 'a-zA-Z0-9','locale' => 'de','delimiter' => '-']
            );
        }

        $objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_image_size WHERE id=? OR merconis_alias=?")
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
}


?>