<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdProfiles;
use LeadingSystems\MerconisBundle\EInvoicing\Model\SemanticInvoice;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;

final class ZugferdXmlGenerator
{
    public function generate(SemanticInvoice $invoice): string
    {
        $this->ensureLibraryIsLoaded();

        $builder = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_EN16931);

        $builder
            ->setDocumentInformation(
                $invoice->invoiceNumber,
                $invoice->invoiceTypeCode,
                $invoice->invoiceDate,
                $invoice->currencyCode
            )
            ->setDocumentSupplyChainEvent($invoice->deliveryDate)
            ->setDocumentGeneralPaymentInformation(
                $invoice->payment['meansCode'] === EInvoicingCodeLists::SEPA_DIRECT_DEBIT_CODE ? $invoice->payment['creditorId'] : null,
                $invoice->invoiceNumber
            )
            ->setDocumentSeller($invoice->seller['name'])
            ->setDocumentSellerAddress(
                $invoice->seller['street'],
                null,
                null,
                $invoice->seller['postalCode'],
                $invoice->seller['city'],
                $invoice->seller['countryCode']
            )
            ->setDocumentSellerCommunication(
                $invoice->seller['electronicAddressScheme'],
                $invoice->seller['electronicAddress']
            )
            ->setDocumentBuyer($invoice->buyer['name'])
            ->setDocumentBuyerAddress(
                $invoice->buyer['street'],
                null,
                null,
                $invoice->buyer['postalCode'],
                $invoice->buyer['city'],
                $invoice->buyer['countryCode']
            )
        ;

        $builder
            ->setDocumentShipTo(
                $invoice->shipTo['name'],
                null,
                null
            )
            ->setDocumentShipToAddress(
                $invoice->shipTo['street'],
                null,
                null,
                $invoice->shipTo['postalCode'],
                $invoice->shipTo['city'],
                $invoice->shipTo['countryCode']
            )
        ;

        if ($invoice->seller['vatId'] !== null) {
            $builder->addDocumentSellerTaxRegistration('VA', $invoice->seller['vatId']);
        }

        if ($invoice->seller['taxNumber'] !== null) {
            $builder->addDocumentSellerTaxRegistration('FC', $invoice->seller['taxNumber']);
        }

        if ($invoice->buyer['vatId'] !== null) {
            $builder->addDocumentBuyerTaxRegistration('VA', $invoice->buyer['vatId']);
        }

        if ($invoice->buyer['email'] !== null) {
            $builder->setDocumentBuyerCommunication('EM', $invoice->buyer['email']);
        }

        if ($invoice->payment['meansCode'] === EInvoicingCodeLists::SEPA_DIRECT_DEBIT_CODE) {
            $builder->addDocumentPaymentMean(
                $invoice->payment['meansCode'],
                null,
                null,
                null,
                null,
                $invoice->payment['debtorIban'],
                $invoice->payment['iban'],
                null,
                null,
                $invoice->payment['bic']
            );

        } else {
            $builder->addDocumentPaymentMean(
                $invoice->payment['meansCode'],
                null,
                null,
                null,
                null,
                null,
                $invoice->payment['iban'],
                null,
                null,
                $invoice->payment['bic']
            );
        }

        if (
            $invoice->payment['termsNote'] !== null
            || $invoice->payment['dueDate'] !== null
            || $invoice->payment['mandateReference'] !== null
        ) {
            $builder->addDocumentPaymentTerm(
                $invoice->payment['termsNote'],
                $invoice->payment['dueDate'],
                $invoice->payment['mandateReference']
            );
        }

        foreach ($invoice->notes as $note) {
            $builder->addDocumentNote($note);
        }

        foreach ($invoice->lineItems as $lineItem) {
            $builder
                ->addNewPosition($lineItem['lineId'])
                ->setDocumentPositionProductDetails(
                    $lineItem['name'],
                    null,
                    $lineItem['sellerAssignedId']
                )
                ->setDocumentPositionGrossPrice($lineItem['unitGrossAmount'])
                ->setDocumentPositionNetPrice($lineItem['unitNetAmount'])
                ->setDocumentPositionQuantity($lineItem['quantity'], $lineItem['unitCode'])
                ->addDocumentPositionTax(
                    $lineItem['taxCategoryCode'],
                    'VAT',
                    $lineItem['taxRate']
                )
                ->setDocumentPositionLineSummation($lineItem['lineNetAmount'])
            ;

            if ($lineItem['note'] !== null) {
                $builder->setDocumentPositionNote($lineItem['note']);
            }
        }

        foreach ($invoice->documentCharges as $documentCharge) {
            $builder->addDocumentAllowanceCharge(
                $documentCharge['amount'],
                $documentCharge['isCharge'],
                $documentCharge['taxCategoryCode'],
                'VAT',
                $documentCharge['taxRate'],
                null,
                null,
                null,
                null,
                null,
                null,
                $documentCharge['reason']
            );
        }

        foreach ($invoice->taxSummaries as $taxSummary) {
            $builder->addDocumentTax(
                $taxSummary['taxCategoryCode'],
                $taxSummary['taxTypeCode'],
                $taxSummary['taxableAmount'],
                $taxSummary['taxAmount'],
                $taxSummary['taxRate'],
                $taxSummary['exemptionReason'],
                $taxSummary['exemptionReasonCode'],
                $taxSummary['lineTotalBasisAmount'],
                $taxSummary['allowanceChargeBasisAmount']
            );
        }

        $builder->setDocumentSummation(
            $invoice->totals['grandTotalAmount'],
            $invoice->totals['duePayableAmount'],
            $invoice->totals['lineTotalAmount'],
            $invoice->totals['chargeTotalAmount'],
            $invoice->totals['allowanceTotalAmount'],
            $invoice->totals['taxBasisTotalAmount'],
            $invoice->totals['taxTotalAmount'],
            $invoice->totals['roundingAmount'],
            $invoice->totals['totalPrepaidAmount']
        );

        return $builder->getContent();
    }

    private function ensureLibraryIsLoaded(): void
    {
        if (class_exists(ZugferdDocumentBuilder::class)) {
            return;
        }

        $bundleAutoloadPath = dirname(__DIR__, 3) . '/vendor/autoload.php';

        if (is_file($bundleAutoloadPath)) {
            require_once $bundleAutoloadPath;
        }
    }
}
