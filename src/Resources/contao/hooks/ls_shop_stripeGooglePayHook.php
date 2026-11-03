<?php

namespace Merconis\Core;

/**
 * Merconis hook implementation to add Google Pay support to Stripe Payment Element behaviour.
 */
class ls_shop_stripeGooglePayHook
{
    public function modifyStripePaymentMapping(array $paymentMapping): array
    {
        $paymentMapping['google_pay'] = 'card';

        return $paymentMapping;
    }

    /**
     * @param array<string, mixed> $paymentBehaviour
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $paymentInfo
     *
     * @return array<string, mixed>
     */
    public function modifyStripePaymentBehaviour(array $paymentBehaviour, array $settings): array
    {
        $selection = (string) ($paymentBehaviour['stripeSelection'] ?? '');
        $selectionType = (string) ($paymentBehaviour['stripeType'] ?? '');

        if ($selectionType !== 'card' || $selection !== 'google_pay') {
            return $paymentBehaviour;
        }

        $merchantName = trim((string) ($settings['stripe_merchantName'] ?? ''));

        $paymentBehaviour['stripeElementOptions'] = [];
        $paymentBehaviour['stripeCreatePaymentOptions'] = [
            'business'=> [
                'name'=> $merchantName,
            ],
            'paymentMethodOrder'=> ['google_pay'],
            'wallets' => [
                'applePay'=> 'never',
                'googlePay' => 'auto',
            ],
            'layout'=> [
                'type' => 'tabs',
                'defaultCollapsed' => false,
            ],
        ];

        return $paymentBehaviour;
    }
}


