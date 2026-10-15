# Manuelle Migration 004: Order-Steuerkontext-Snapshot-Felder

**Erstellt:** 2026-10-15

## Kontext

Stage 005 erweitert den Checkout-Snapshot auf
`tl_ls_shop_orders` um vier neue Felder:

- `einvoicingTaxCountry`
- `einvoicingVatExemptionStatus`
- `einvoicingShippingTaxCategory`
- `einvoicingPaymentTaxCategory`

Der Checkout-Hook schreibt diese Werte beim
Bestellabschluss. Der `SemanticInvoiceBuilder`
liest sie später für Steuerland,
Befreiungsstatus und Zuschlagskategorien aus dem
Bestell-Snapshot.

Bestehende Installationen benötigen daher ein
einmaliges `ALTER TABLE`, damit die neuen
Spalten vor produktiver Nutzung vorhanden sind.

## PRE-DEPLOY

1. Datenbanksicherung von `tl_ls_shop_orders`
   erstellen.
2. Sicherstellen, dass während des Deployments
   keine gleichzeitigen Checkout-Schreibvorgänge
   laufen.

## POST-DEPLOY

1. Neue Spalten anlegen:

```sql
ALTER TABLE `tl_ls_shop_orders`
  ADD `einvoicingTaxCountry`
    varchar(2) NOT NULL default ''
    AFTER `einvoicingPaymentMeansCode`,
  ADD `einvoicingVatExemptionStatus`
    varchar(32) NOT NULL default ''
    AFTER `einvoicingTaxCountry`,
  ADD `einvoicingShippingTaxCategory`
    varchar(16) NOT NULL default ''
    AFTER `einvoicingVatExemptionStatus`,
  ADD `einvoicingPaymentTaxCategory`
    varchar(16) NOT NULL default ''
    AFTER `einvoicingShippingTaxCategory`;
```

2. Code deployen.
3. Contao-/Symfony-Cache leeren.
4. Contao-Schema-Abgleich ausführen.

## Verifikation

Prüfen:

1. `SHOW COLUMNS FROM tl_ls_shop_orders`
   enthält alle vier neuen Spalten.
2. Eine neue Bestellung schreibt im
   Bestell-Datensatz:
   - das Steuerland,
   - den Befreiungsstatus,
   - die Steuerkategorie für Versand,
   - die Steuerkategorie für Zahlungsgebühren.
3. Eine E-Rechnung liest diese Werte aus dem
   Snapshot und erzeugt bei abweichender
   Lieferadresse eine befüllte BG-13-
   Lieferadresse.
