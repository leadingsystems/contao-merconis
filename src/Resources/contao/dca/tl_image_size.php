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

        //if empty alias set alias value to name value
        if(empty($varValue)){
            $varValue = $dc->activeRecord->name;
        }

        //replace whitespace to underscore
        $varValue = preg_replace('/\s+/', '_', $varValue);

        $aliasExists = function (string $alias) use ($dc): bool {
            return $this->Database->prepare("SELECT id FROM tl_image_size WHERE merconis_alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        //while alias Exist add _id
        while($aliasExists($varValue)){
            $varValue =  $varValue.'_'.$dc->activeRecord->id;
        }

        return $varValue;
    }
}


?>