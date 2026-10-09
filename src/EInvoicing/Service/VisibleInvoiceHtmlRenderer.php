<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use LeadingSystems\MerconisBundle\EInvoicing\Model\SemanticInvoice;

final class VisibleInvoiceHtmlRenderer
{
    public function render(SemanticInvoice $invoice): string
    {
        $lineItemsMarkup = '';

        foreach ($invoice->lineItems as $lineItem) {
            $lineItemsMarkup .= '<tr>'
                . '<td>' . $this->escape((string) $lineItem['lineId']) . '</td>'
                . '<td>' . $this->escape((string) $lineItem['name']) . '</td>'
                . '<td class="numeric">' . $this->formatAmount((float) $lineItem['quantity']) . '</td>'
                . '<td>' . $this->escape((string) $lineItem['unitCode']) . '</td>'
                . '<td class="numeric">' . $this->formatAmount((float) $lineItem['unitNetAmount'], 4) . '</td>'
                . '<td class="numeric">' . $this->formatAmount((float) $lineItem['taxRate']) . ' %</td>'
                . '<td class="numeric">' . $this->formatAmount((float) $lineItem['lineNetAmount']) . '</td>'
                . '</tr>';
        }

        $taxSummariesMarkup = '';

        foreach ($invoice->taxSummaries as $taxSummary) {
            $taxSummariesMarkup .= '<tr>'
                . '<td>' . $this->escape((string) $taxSummary['taxCategoryCode']) . '</td>'
                . '<td class="numeric">' . $this->formatAmount((float) $taxSummary['taxableAmount']) . '</td>'
                . '<td class="numeric">' . $this->formatAmount((float) $taxSummary['taxRate']) . ' %</td>'
                . '<td class="numeric">' . $this->formatAmount((float) $taxSummary['taxAmount']) . '</td>'
                . '</tr>';
        }

        $paymentTermsMarkup = '';

        if ($invoice->payment['termsNote'] !== null) {
            $paymentTermsMarkup .= '<p><strong>Zahlungsbedingungen:</strong> '
                . $this->escape((string) $invoice->payment['termsNote'])
                . '</p>';
        }

        if ($invoice->payment['dueDate'] !== null) {
            $paymentTermsMarkup .= '<p><strong>Fällig am:</strong> '
                . $this->escape($invoice->payment['dueDate']->format('d.m.Y'))
                . '</p>';
        }

        return '<!DOCTYPE html>'
            . '<html lang="de">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<title>Rechnung ' . $this->escape($invoice->invoiceNumber) . '</title>'
            . '<style>'
            . 'body{font-family:dejavusans,sans-serif;font-size:10pt;color:#000;}'
            . 'h1,h2{margin:0 0 8px 0;}'
            . '.meta,.party,.totals,.taxes,.items{margin-top:18px;}'
            . '.grid{width:100%;border-collapse:collapse;}'
            . '.grid th,.grid td{border:1px solid #000;padding:6px;vertical-align:top;}'
            . '.grid th{text-align:left;}'
            . '.numeric{text-align:right;}'
            . '.party-table{width:100%;border-collapse:collapse;}'
            . '.party-table td{width:50%;vertical-align:top;padding-right:12px;}'
            . '.muted{color:#222;}'
            . '</style>'
            . '</head>'
            . '<body>'
            . '<h1>Rechnung ' . $this->escape($invoice->invoiceNumber) . '</h1>'
            . '<p class="muted">Bestellnummer: ' . $this->escape($invoice->orderNumber)
            . '<br>Rechnungsdatum: ' . $this->escape($invoice->invoiceDate->format('d.m.Y'))
            . '<br>Währung: ' . $this->escape($invoice->currencyCode) . '</p>'
            . '<table class="party-table party"><tr>'
            . '<td><h2>Verkäufer</h2>'
            . '<p>' . $this->escape((string) $invoice->seller['name'])
            . '<br>' . $this->escape((string) $invoice->seller['street'])
            . '<br>' . $this->escape((string) $invoice->seller['postalCode']) . ' '
            . $this->escape((string) $invoice->seller['city'])
            . '<br>' . $this->escape((string) $invoice->seller['countryCode']) . '</p>'
            . '</td>'
            . '<td><h2>Käufer</h2>'
            . '<p>' . $this->escape((string) $invoice->buyer['name'])
            . '<br>' . $this->escape((string) $invoice->buyer['street'])
            . '<br>' . $this->escape((string) $invoice->buyer['postalCode']) . ' '
            . $this->escape((string) $invoice->buyer['city'])
            . '<br>' . $this->escape((string) $invoice->buyer['countryCode']) . '</p>'
            . '</td>'
            . '</tr></table>'
            . '<div class="items">'
            . '<h2>Positionen</h2>'
            . '<table class="grid">'
            . '<thead><tr>'
            . '<th>Pos.</th><th>Beschreibung</th><th class="numeric">Menge</th><th>Einheit</th>'
            . '<th class="numeric">Nettopreis</th><th class="numeric">Steuersatz</th><th class="numeric">Nettobetrag</th>'
            . '</tr></thead>'
            . '<tbody>' . $lineItemsMarkup . '</tbody>'
            . '</table>'
            . '</div>'
            . '<div class="taxes">'
            . '<h2>Steueraufschlüsselung</h2>'
            . '<table class="grid">'
            . '<thead><tr>'
            . '<th>Kategorie</th><th class="numeric">Basisbetrag</th><th class="numeric">Steuersatz</th><th class="numeric">Steuerbetrag</th>'
            . '</tr></thead>'
            . '<tbody>' . $taxSummariesMarkup . '</tbody>'
            . '</table>'
            . '</div>'
            . '<div class="totals">'
            . '<h2>Summen</h2>'
            . '<table class="grid">'
            . '<tbody>'
            . '<tr><th>Positionssumme</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['lineTotalAmount']) . '</td></tr>'
            . '<tr><th>Zuschläge</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['chargeTotalAmount']) . '</td></tr>'
            . '<tr><th>Abschläge</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['allowanceTotalAmount']) . '</td></tr>'
            . '<tr><th>Steuerbasis</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['taxBasisTotalAmount']) . '</td></tr>'
            . '<tr><th>Steuerbetrag</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['taxTotalAmount']) . '</td></tr>'
            . '<tr><th>Rundungsbetrag</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['roundingAmount']) . '</td></tr>'
            . '<tr><th>Gesamtbetrag</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['grandTotalAmount']) . '</td></tr>'
            . '<tr><th>Zahlbetrag</th><td class="numeric">' . $this->formatAmount((float) $invoice->totals['duePayableAmount']) . '</td></tr>'
            . '</tbody>'
            . '</table>'
            . '</div>'
            . '<div class="meta">'
            . '<p><strong>Payment Means Code:</strong> ' . $this->escape((string) $invoice->payment['meansCode']) . '</p>'
            . $paymentTermsMarkup
            . '</div>'
            . '</body>'
            . '</html>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatAmount(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals, ',', '.');
    }
}
