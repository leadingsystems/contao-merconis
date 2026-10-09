<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Model;

use DateTimeImmutable;

/**
 * Trägt die fachliche Rechnungssicht zwischen Merconis-Snapshot und CII-XML.
 *
 * @phpstan-type Party array{
 *   name: string,
 *   street: string,
 *   postalCode: string,
 *   city: string,
 *   countryCode: string,
 *   vatId: ?string,
 *   taxNumber: ?string,
 *   electronicAddress: ?string,
 *   electronicAddressScheme: ?string,
 *   iban: ?string,
 *   bic: ?string,
 *   email: ?string
 * }
 * @phpstan-type Payment array{
 *   meansCode: string,
 *   dueDate: ?DateTimeImmutable,
 *   termsNote: ?string,
 *   iban: ?string,
 *   bic: ?string
 * }
 * @phpstan-type LineItem array{
 *   lineId: string,
 *   name: string,
 *   sellerAssignedId: ?string,
 *   quantity: float,
 *   unitCode: string,
 *   unitNetAmount: float,
 *   unitGrossAmount: float,
 *   lineNetAmount: float,
 *   taxCategoryCode: string,
 *   taxRate: float,
 *   note: ?string
 * }
 * @phpstan-type DocumentCharge array{
 *   reason: string,
 *   amount: float,
 *   taxCategoryCode: string,
 *   taxRate: float,
 *   isCharge: bool
 * }
 * @phpstan-type TaxSummary array{
 *   taxCategoryCode: string,
 *   taxTypeCode: string,
 *   taxRate: float,
 *   taxableAmount: float,
 *   taxAmount: float,
 *   lineTotalBasisAmount: float,
 *   allowanceChargeBasisAmount: float,
 *   exemptionReason: ?string,
 *   exemptionReasonCode: ?string
 * }
 * @phpstan-type Totals array{
 *   lineTotalAmount: float,
 *   chargeTotalAmount: float,
 *   allowanceTotalAmount: float,
 *   taxBasisTotalAmount: float,
 *   taxTotalAmount: float,
 *   grandTotalAmount: float,
 *   duePayableAmount: float,
 *   roundingAmount: float,
 *   totalPrepaidAmount: float
 * }
 */
final readonly class SemanticInvoice
{
    /**
     * @param Party               $seller
     * @param Party               $buyer
     * @param Payment             $payment
     * @param list<LineItem>      $lineItems
     * @param list<DocumentCharge> $documentCharges
     * @param list<TaxSummary>    $taxSummaries
     * @param Totals              $totals
     * @param list<string>        $notes
     */
    public function __construct(
        public string $guidelineId,
        public string $invoiceTypeCode,
        public string $invoiceNumber,
        public string $orderNumber,
        public DateTimeImmutable $invoiceDate,
        public string $currencyCode,
        public array $seller,
        public array $buyer,
        public array $payment,
        public array $lineItems,
        public array $documentCharges,
        public array $taxSummaries,
        public array $totals,
        public array $notes = [],
    ) {
    }
}
