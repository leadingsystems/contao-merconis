<?php
namespace Merconis\Core;


/*  Contains functions for conditional processing
 *
 */
class xrechnung_conditions
{
    /*  Array containing all the data of a Merconis order
     *  @var    array
     */
    private $arrOrder = [];


    /*  Sets a reference to the order array.
     *
     *  @param  array   $arrOrder   byreference, Array containing all the data of a Merconis order
     *  @return void
     */
    public function setReference(array &$arrOrder): void
    {
        $this->arrOrder = &$arrOrder;
    }


    /*  Abhängig von der Zahlungsweise wird entschieden, ob Bankkonto Informationen
     *  ausgegeben werden sollen. Dies ist nur bei echten Banküberweisungen der Fall
     *  Bei Online-Zahlungsweisen nicht
     * */
    public function accountDataByPayment()
    {
//TODO: Alle Zahlungsweisen eintragen
        $paymentMethod = $this->arrOrder['paymentMethod_alias'];

        $result = match ($paymentMethod) {
            'lastschrift' => true,
            'vorauskasse', 'vorkasse' => true,
            default => false
        };

        return $result;
    }


}