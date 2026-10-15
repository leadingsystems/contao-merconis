# Manuelle Migration 003: Zahlungskonditionen-Kaskade

**Erstellt:** 2026-10-15

## Kontext

Die Zahlungskonditionen für E-Rechnungen werden
nicht mehr in `tl_lsShopSettings` gepflegt,
sondern an der Zahlungsmethode
`tl_ls_shop_payment_methods`. Die
Mitgliedergruppe bleibt als absoluter Override
erhalten.

Für bestehende Installationen müssen zwei neue
Spalten an `tl_ls_shop_payment_methods` angelegt
und vorhandene Werte aus `localconfig.php`
einmalig in die betroffene Zahlungsmethode
übernommen werden.

## PRE-DEPLOY

1. Datenbanksicherung von
   `tl_ls_shop_payment_methods` erstellen.
2. Aktuelle Werte von
   `ls_shop_einvoicingPaymentTermsDays` und
   `ls_shop_einvoicingPaymentTermsNote` aus
   `system/config/localconfig.php` notieren.
3. Ermitteln, welche Zahlungsmethode bisher die
   Standard-Zahlungskonditionen repräsentiert
   (für die S1-Fixtures: Alias `vorauskasse`,
   ID 11).

## POST-DEPLOY

1. Neue Spalten anlegen:

```sql
ALTER TABLE `tl_ls_shop_payment_methods`
  ADD `ls_shop_einvoicingPaymentTermsDays`
    varchar(16) NOT NULL default ''
    AFTER `einvoicingPaymentMeansCode`,
  ADD `ls_shop_einvoicingPaymentTermsNote`
    text NULL
    AFTER `ls_shop_einvoicingPaymentTermsDays`;
```

2. Vorhandene Standardwerte aus `localconfig.php`
   in die gewünschte Zahlungsmethode übernehmen:

```sql
UPDATE `tl_ls_shop_payment_methods`
SET
  `ls_shop_einvoicingPaymentTermsDays` = '14',
  `ls_shop_einvoicingPaymentTermsNote` = 'Zahlbar innerhalb von 14 Tagen netto.'
WHERE `id` = 11;
```

3. Alte Konfigurationswerte aus
   `system/config/localconfig.php` entfernen:

   - `$GLOBALS['TL_CONFIG']['ls_shop_einvoicingPaymentTermsDays']`
   - `$GLOBALS['TL_CONFIG']['ls_shop_einvoicingPaymentTermsNote']`

4. Cache leeren und den Contao-Schema-Abgleich
   ausführen.

## Verifikation

Prüfen:

1. `SHOW COLUMNS FROM tl_ls_shop_payment_methods`
   enthält die beiden neuen Spalten.
2. Die gewünschte Zahlungsmethode trägt die
   migrierten Werte.
3. `tl_lsShopSettings` zeigt im Backend keine
   Felder für BT-9 oder BT-20 mehr an.
4. Eine E-Rechnung und eine Bestellnachricht mit
   Platzhaltern liefern dieselben
   Zahlungskonditionen.
