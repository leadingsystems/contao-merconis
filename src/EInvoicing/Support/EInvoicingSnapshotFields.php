<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Support;

final class EInvoicingSnapshotFields
{
    public const SHOP_SETTINGS_CREDITOR_ID_FIELD = 'ls_shop_einvoicingSellerCreditorId';
    public const MEMBER_MANDATE_REFERENCE_FIELD = 'lsShopEinvoicingMandateReference';
    public const MEMBER_DEBTOR_IBAN_FIELD = 'lsShopEinvoicingDebtorIban';
    public const PRODUCT_UNIT_CODE_FIELD = 'einvoicingUnitCode';
    public const PAYMENT_METHOD_MEANS_CODE_FIELD = 'einvoicingPaymentMeansCode';
    public const ORDER_PAYMENT_MEANS_CODE_COLUMN = 'paymentMethod_einvoicingPaymentMeansCode';
    public const ORDER_PAYMENT_MEANS_CODE_ORDER_KEY = 'paymentMethod_einvoicingPaymentMeansCode';
    public const ORDER_TAX_COUNTRY_COLUMN = 'einvoicingTaxCountry';
    public const ORDER_TAX_COUNTRY_ORDER_KEY = 'einvoicingTaxCountry';
    public const ORDER_VAT_EXEMPTION_STATUS_COLUMN = 'einvoicingVatExemptionStatus';
    public const ORDER_VAT_EXEMPTION_STATUS_ORDER_KEY = 'einvoicingVatExemptionStatus';
    public const ORDER_SHIPPING_TAX_CATEGORY_COLUMN = 'shippingMethod_einvoicingTaxCategory';
    public const ORDER_SHIPPING_TAX_CATEGORY_ORDER_KEY = 'shippingMethod_einvoicingTaxCategory';
    public const ORDER_PAYMENT_TAX_CATEGORY_COLUMN = 'paymentMethod_einvoicingTaxCategory';
    public const ORDER_PAYMENT_TAX_CATEGORY_ORDER_KEY = 'paymentMethod_einvoicingTaxCategory';
    public const ORDER_MANDATE_REFERENCE_COLUMN = 'einvoicingMandateReference';
    public const ORDER_MANDATE_REFERENCE_ORDER_KEY = 'einvoicingMandateReference';
    public const ORDER_CREDITOR_ID_COLUMN = 'einvoicingCreditorId';
    public const ORDER_CREDITOR_ID_ORDER_KEY = 'einvoicingCreditorId';
    public const ORDER_DEBTOR_IBAN_COLUMN = 'einvoicingDebtorIban';
    public const ORDER_DEBTOR_IBAN_ORDER_KEY = 'einvoicingDebtorIban';
    public const TAX_CONFIGURATION_CATEGORY_FIELD = 'einvoicingTaxCategory';
    public const ITEM_TAX_CATEGORY_CODE = 'einvoicingTaxCategory';
    public const ITEM_UNIT_CODE = 'einvoicingUnitCode';
    public const ITEM_TAX_EXEMPTION_REASON = 'einvoicingTaxExemptionReason';
    public const ITEM_TAX_EXEMPTION_REASON_CODE = 'einvoicingTaxExemptionReasonCode';
}
