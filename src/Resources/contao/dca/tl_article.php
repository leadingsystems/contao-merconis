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

$GLOBALS['TL_DCA']['tl_article']['fields']['lsShopIsProducerInfo'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_article']['lsShopIsProducerInfo'],
    'exclude'       => true,
    'inputType'		=> 'checkbox',
    'eval'			=> array('submitOnChange'=>true, 'tl_class' => 'w33-cbx-middle aliasClassForProducerInfoCheckbox'),
    'sql'           => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_article']['fields']['lsShopProducerInfoText'] = array(
    'label'			=> &$GLOBALS['TL_LANG']['tl_article']['lsShopProducerInfoText'],
    'exclude'       => true,
    'inputType'		=> 'text',
    'eval'			=> array('tl_class' => 'w33', 'mandatory' => true),
    'save_callback' => array
    (
        array('Merconis\Core\tl_article', 'saveCallbackProducer')
    ),
    'sql'           => "varchar(255) NOT NULL default ''"
);






array_unshift($GLOBALS['TL_DCA']['tl_article']['fields']['alias']['save_callback'], array('Merconis\Core\tl_article', 'saveCallbackAlias'));

$GLOBALS['TL_DCA']['tl_article']['fields']['alias']['eval']['tl_class'] = $GLOBALS['TL_DCA']['tl_article']['fields']['alias']['eval']['tl_class'].' aliasClassForProducerInfoText';


use Contao\Backend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Database;
use Contao\Input;

PaletteManipulator::create()
    ->addField('lsShopIsProducerInfo', 'alias')
    ->applyToPalette('default', 'tl_article');

array_push($GLOBALS['TL_DCA']['tl_article']['palettes']['__selector__'], 'lsShopIsProducerInfo');
$GLOBALS['TL_DCA']['tl_article']['subpalettes']['lsShopIsProducerInfo'] = 'lsShopProducerInfoText';


class tl_article extends Backend
{

    public function __construct()
    {
        parent::__construct();
        $this->import(\BackendUser::class, 'User');
    }

    public function saveCallbackAlias($varValue, \DataContainer $dc)
    {

        if ($this->isProducerInfo($dc))
        {
            $generatedAlias = $this->getGeneratedAlias($dc);

            if (preg_match('/^[1-9]\d*$/', $generatedAlias))
            {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $generatedAlias));
            }

            if ($this->aliasExists($dc)($generatedAlias))
            {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['producerExists'], $generatedAlias));
            }

            return $generatedAlias;
        }

        return $varValue;
    }



    public function saveCallbackProducer($varValue, \DataContainer $dc)
    {

        if ($this->isProducerInfo($dc))
        {
            $generatedAlias = $this->getGeneratedAlias($dc);

            if ($this->aliasExists($dc)($generatedAlias))
            {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['producerExists'], $varValue));
            }

            if (preg_match('/^[1-9]\d*$/', $generatedAlias))
            {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
            }
        }

        return $varValue;
    }

    protected function isProducerInfo($dc)
    {
        if($this->getDcaFieldValue($dc, 'lsShopIsProducerInfo') == '1'){
            return true;
        }
        return false;
    }

    protected function getGeneratedAlias($dc)
    {
        return \System::getContainer()->get('contao.slug')->generate($this->getDcaFieldValue($dc, 'lsShopProducerInfoText'), $dc->activeRecord->pid);
    }

    protected function aliasExists($dc)
    {
        return function (string $alias) use ($dc): bool
        {
            if (in_array($alias, array('top', 'wrapper', 'header', 'container', 'main', 'left', 'right', 'footer'), true))
            {
                return true;
            }

            return $this->Database->prepare("SELECT id FROM tl_article WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };
    }


    protected static function getDcaFieldValue($dc, $fieldName, $fromDb = false)
    {
        $value = null;

        if (Input::post('FORM_SUBMIT') === $dc->table && !$fromDb) {
            $value = Input::post($fieldName);
            if ($value !== null) {
                return $value;
            }
        }

        if ($dc->activeRecord) {
            $value = $dc->activeRecord->$fieldName;
        }
        else {

            $table = $dc->table;
            $id = $dc->id;

            if (Input::get('target')) {
                $table = explode('.', Input::get('target'), 2)[0];
                $id = (int) explode('.', Input::get('target'), 3)[2];
            }

            if ($table && $id) {
                $record = Database::getInstance()
                    ->prepare("SELECT * FROM {$table} WHERE id=?")
                    ->execute($id);
                if ($record->next()) {
                    $value = $record->$fieldName;
                }
            }

        }

        return $value;
    }

}