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


    /*  Depending on the payment method, it is decided whether bank account information
     *  should be output. This is only the case with real bank transfers
     *  Not for online payment methods
     *
     * @return  bool    $result
     */
    public function accountDataByPayment(): bool
    {
        $paymentMethod = $this->arrOrder['paymentMethod_alias'];

        $result = match ($paymentMethod) {
            'paypal' => false,
            'paypal-plus' => false,
            'PayPal Checkout', 'paypal-checkout' => false,
            'saferpay' => false,
            'payone' => false,
            'santander-finanzierung' => false,
            'sofort' => false,
            'vr-pay' => false,
            'lastschrift' => true,
            'vorauskasse', 'vorkasse' => true,
            default => false
        };

        return $result;
    }


}