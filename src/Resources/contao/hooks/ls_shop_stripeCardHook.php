<?php

namespace Merconis\Core;

/**
 * Merconis hook implementation providing the default Stripe "card" behaviour.
 *
 * This is the base behaviour for selectionType "card". More specific selections (e.g. Apple Pay,
 * Google Pay) can override this in later hooks with a higher priority.
 */
class ls_shop_stripeCardHook
{
    /**
     * @param array<string, mixed> $paymentBehaviour
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $paymentInfo
     *
     * @return array<string, mixed>
     */
    public function modifyStripePaymentBehaviour(array $paymentBehaviour, array $settings): array
    {
        $selectionType = (string) ($paymentBehaviour['stripeType'] ?? '');
        if ($selectionType !== 'card') {
            return $paymentBehaviour;
        }

        $selection = (string) ($paymentBehaviour['stripeSelection'] ?? '');
        if ($selection !== 'card') {
            return $paymentBehaviour;
        }

        $merchantName = trim((string) ($settings['stripe_merchantName'] ?? ''));

        $paymentBehaviour['stripeElementOptions'] = [];
        $paymentBehaviour['stripeCreatePaymentOptions'] = [
            'business'=> [
                'name'=> $merchantName,
            ],
            'paymentMethodOrder'=> ['card'],
            'wallets' => [
                'applePay'=> 'never',
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


