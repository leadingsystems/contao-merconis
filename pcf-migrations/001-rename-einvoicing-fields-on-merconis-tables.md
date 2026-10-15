# Manuelle Migration 001: E-Invoicing-Feldnamen auf
# Merconis-Tabellen korrigieren

**Erstellt:** 2026-10-15

## Kontext

Die E-Invoicing-Implementierung verwendet auf
Merconis-eigenen Tabellen künftig die
bestehende Merconis-Namenskonvention ohne
`ls_shop_`-Prefix.

Betroffen sind drei bereits befüllbare
Spalten:

- `tl_ls_shop_payment_methods`:
  `ls_shop_einvoicingPaymentMeansCode`
  -> `einvoicingPaymentMeansCode`
- `tl_ls_shop_steuersaetze`:
  `ls_shop_einvoicingTaxCategory`
  -> `einvoicingTaxCategory`
- `tl_ls_shop_orders`:
  `ls_shop_einvoicingPaymentMeansCode`
  -> `einvoicingPaymentMeansCode`

Die Codebasis wurde bereits auf die neuen
Spaltennamen umgestellt. Damit bestehende
Daten erhalten bleiben, muss die Umbenennung
vor der produktiven Nutzung per SQL auf der
Datenbank ausgeführt werden.

## PRE-DEPLOY

1. Datenbanksicherung der betroffenen Tabellen
   erstellen.
2. Sicherstellen, dass keine gleichzeitigen
   Schreibvorgänge auf Bestellungen oder
   Zahlungsarten laufen.
3. Folgende SQL-Befehle ausführen:

```sql
ALTER TABLE `tl_ls_shop_payment_methods`
  CHANGE `ls_shop_einvoicingPaymentMeansCode`
  `einvoicingPaymentMeansCode`
  varchar(16) NOT NULL default '58';

ALTER TABLE `tl_ls_shop_steuersaetze`
  CHANGE `ls_shop_einvoicingTaxCategory`
  `einvoicingTaxCategory`
  varchar(16) NOT NULL default 'auto';

ALTER TABLE `tl_ls_shop_orders`
  CHANGE `ls_shop_einvoicingPaymentMeansCode`
  `einvoicingPaymentMeansCode`
  varchar(16) NOT NULL default '';
```

## POST-DEPLOY

1. Code mit den umgestellten Feldnamen
   deployen.
2. Contao-/Symfony-Cache leeren.
3. Falls im Deploy-Prozess vorgesehen:
   Contao-Migration bzw. DCA-Schema-Abgleich
   ausführen, damit keine veralteten
   Felddefinitionen mehr angezeigt werden.

## Verifikation

Prüfen:

1. `SHOW COLUMNS` auf allen drei Tabellen
   enthält nur noch die neuen Spaltennamen.
2. Bestehende Werte für Payment Means Code und
   Steuerkategorie sind nach der Umbenennung
   weiterhin vorhanden.
3. Im Backend:
   - `tl_ls_shop_payment_methods` zeigt das Feld
     "Zahlungsart-Code (EN 16931)" zwischen
     Zahlungsoptionsart und Steuersatz.
   - `tl_ls_shop_steuersaetze` zeigt weiterhin
     die Steuerkategorie mit vorhandenen Werten.
4. Eine bestehende Bestellung mit
   E-Invoicing-Snapshot lässt sich ohne Fehler
   laden und liefert im Order-Array den Key
   `paymentMethod_einvoicingPaymentMeansCode`.
