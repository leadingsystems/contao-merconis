# Manuelle Migration 001: GLL-Infoseite und Checkout-Form-Child

**Erstellt:** 2026-09-23

## Kontext

Stage 005 ergänzt im Merconis-Core die Seitenzuweisung für die
GLL-Infoseite sowie die Insert-Tags `{{gll_notice}}`,
`{{gll_checkout_link}}` und `{{garan_checkout_block}}`.

Bei Bestandsshops kann der Core bestehende Contao-Seiten und bereits
angelegte Checkout-Formulare nicht zuverlässig automatisch anpassen.
Deshalb bleibt die Einrichtung der GLL-Infoseite und des zusätzlichen
Form-Childs im Bestellbestätigungsformular eine manuelle
Backend-Migration.

## POST-DEPLOY

1. Pro Sprach-Root eine Contao-Seite für die GLL-Infoseite anlegen oder
   auswählen.
2. Auf dieser Seite ein Inhaltselement mit dem Insert-Tag
   `{{gll_notice}}` platzieren.
3. In den Merconis-Grundeinstellungen unter
   `ls_shop_legalGuaranteeInfoPages` die passenden Seiten pro Sprache
   zuweisen.
4. Das in der verwendeten Kundengruppe konfigurierte Formular
   `lsShopFormConfirmOrder` im Contao-Backend öffnen.
5. Im Formular ein zusätzliches Child-Inhaltselement für die letzte
   Bestellseite anlegen und dort mindestens die Insert-Tags
   `{{gll_checkout_link}}` und `{{garan_checkout_block}}` platzieren.
6. Sicherstellen, dass das neue Form-Child nur auf der letzten
   Bestellseite sichtbar ist und nicht in Warenkorb, Adresse oder
   Zahlungsschritt dupliziert erscheint.

## Verifikation

- Auf der GLL-Infoseite wird die GLL-Grafik als Inline-SVG ausgegeben.
- Auf der letzten Bestellseite erscheint bei betroffenen Warenkorb-
  positionen der GLL-Link zur Infoseite sowie der GARAN-Zuordnungsblock.
- In Warenkorb, Rechnungsansichten, Versandmails und
  Checkout-Zwischenschritten erscheinen keine GLL-/GARAN-Labels.
