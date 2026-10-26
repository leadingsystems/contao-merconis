<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

final class EInvoicingPaymentModuleHook
{
    /**
     * @param array<string, array<string, mixed>> $types
     *
     * @return array<string, array<string, mixed>>
     */
    public function modifyPaymentModuleTypes(array $types): array
    {
        if (!isset($types['standard']) || !is_array($types['standard'])) {
            return $types;
        }

        $types['standard']['className'] = EInvoicingStandardPaymentModule::class;

        return $types;
    }
}
