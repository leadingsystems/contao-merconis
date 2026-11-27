<?php

namespace Merconis\Core;

/**
 * Merconis hook implementation to add Apple Pay support to Stripe Payment Element behaviour.
 *
 * This keeps Apple Pay integration out of the core Stripe behaviour mapping, so it can be
 * customized/overridden via hooks without hardcoding it in `ls_shop_generalHelper`.
 */
class ls_shop_stripeApplePayHook
{
    public function modifyStripePaymentMapping(array $paymentMapping): array
    {
        $paymentMapping['apple_pay'] = 'card';

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

        if ($selectionType !== 'card' || $selection !== 'apple_pay') {
            return $paymentBehaviour;
        }

        $merchantName = trim((string) ($settings['stripe_merchantName'] ?? ''));

        $paymentBehaviour['stripeElementOptions'] = [];
        $paymentBehaviour['stripeCreatePaymentOptions'] = [
            'business'=> [
                'name'=> $merchantName,
            ],
            'paymentMethodOrder'=> ['apple_pay'],
            'wallets' => [
                'applePay'=> 'auto',
                'googlePay' => 'never',
            ],
            'layout'=> [
                'type' => 'tabs',
                'defaultCollapsed' => false,
            ],
        ];

        return $paymentBehaviour;
    }
}


