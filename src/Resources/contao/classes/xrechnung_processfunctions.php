<?php
namespace Merconis\Core;


/*  Enthält Funktionen für einen individuellen Ablauf der Auswertung der Informationselemente
 *
 */
class xrechnung_processfunctions
{

    public function repeatForEveryKey(xrechnung_element $parent,string $node )
    {
        $xmlSubCode = '';
        $cnt = 0;

        //reguläre Unter-Knoten Auswertung stoppen
        $parent->setIgnoreSubElements(true);

        //Für jeden Steuer-Kategorien Schlüssel soll die reguläre Unterknoten-Auswertung stattfinden
        $keyGroups = array_keys($parent->arrOrder[$node]);
        foreach ($keyGroups as $groupKey)
        {       //Schließen und öffnen der XML Tags läuft in der evalIE. Sobald es aber mehr als 1 Element ist müssen die XML Tags hier drin erneut geschlossen werden
            $cnt++;
            if ($cnt > 1) {
                $xmlSubCode .= $parent->tabs.'</'.$parent->xml.'>
';
                $xmlSubCode .= $parent->tabs.'<'.$parent->xml.'>';
            }
            $parent->additionalParams = array('groupKey' => $groupKey);

//TODO: Kann die Funktion ´subElems´ auch bei repeatForEveryTaxKey eingesetzt werden
#$xmlSubCode = $parent->subElems($parent);

            if ($parent->firstSub != '') {

                $xmlSubCode .= '
';
                foreach ($parent->sub as $subElementId => $subElement) {
                    //Unter-Informations-Element muss die gleichen Parameter erhalten
                    $subElement->additionalParams = $parent->additionalParams;
                    $xmlSubCode .= $subElement->evalIE();
                }
            }

        }

        return $xmlSubCode;
    }
/*
    public function repeatForEveryTaxKey($parent)
    {
        $xmlSubCode = '';
        $cnt = 0;

        //reguläre Unter-Knoten Auswertung stoppen
        $parent->setIgnoreSubElements(true);

        //Für jeden Steuer-Kategorien Schlüssel soll die reguläre Unterknoten-Auswertung stattfinden
        $taxCategories = array_keys($parent->arrOrder['totalTaxedWith']);
        foreach ($taxCategories as $taxCategory)
        {
            $cnt++;
            if ($cnt > 1) {
                $xmlSubCode .= $parent->tabs.'</'.$parent->xml.'>
';
                $xmlSubCode .= $parent->tabs.'<'.$parent->xml.'>';
            }
            $parent->additionalParams = array('taxCategory' => $taxCategory);

//TODO: Kann die Funktion ´subElems´ auch bei repeatForEveryTaxKey eingesetzt werden
#$xmlSubCode = $parent->subElems($parent);

            if ($parent->firstSub != '') {

                $xmlSubCode .= '
';
                foreach ($parent->sub as $subElementId => $subElement) {
                    //Unter-Informations-Element muss die gleichen Parameter erhalten
                    $subElement->additionalParams = $parent->additionalParams;
                    $xmlSubCode .= $subElement->evalIE();
                }
            }

        }

        return $xmlSubCode;
    }
*/

}