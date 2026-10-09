# Manuelle Migration 002: GLL-Checkout- und Infoseiten-Umstellung

**Erstellt:** 2026-10-02

## Kontext

Mit Stage 011 wurde die Ausgabe der gesetzlichen
Gewährleistung und der Herstellergarantie umgestellt:

- Das Checkout-Review rendert die Ausgaben jetzt direkt
  aus `ModuleOrderReview` und verwendet keine
  Checkout-Insert-Tags mehr.
- Die frühere Shop-Einstellung
  `ls_shop_legalGuaranteeInfoPages` wurde entfernt.
- Die GLL-Infoseite wird jetzt nur noch über feste
  Alias-Namen aufgelöst:
  `gewaehrleistungslabel` für Deutsch und
  `legal-guarantee` für Englisch.

Bestehende Installationen müssen ihre Seiten- und
Formular-Konfiguration daran anpassen.

## PRE-DEPLOY

Keine.

## POST-DEPLOY

1. Prüfen, ob im Checkout-Formular oder in
   zugehörigen Templates noch die Insert-Tags
   `{{gll_checkout_link}}` oder
   `{{garan_checkout_block}}` verwendet werden.
   Diese Ausgaben entfallen und müssen entfernt
   werden, damit keine veralteten Platzhalter
   zurückbleiben.
2. Prüfen, ob redaktionelle Inhalte noch das alte
   Insert-Tag `{{gll_notice}}` verwenden. Falls die
   GLL-Ausgabe weiterhin benötigt wird, dieses
   Insert-Tag durch `{{shop_eu_lgn}}` ersetzen.
3. Für deutschsprachige Seiten sicherstellen, dass
   die gewünschte GLL-Infoseite den Alias
   `gewaehrleistungslabel` trägt.
4. Für englischsprachige Seiten sicherstellen, dass
   die gewünschte GLL-Infoseite den Alias
   `legal-guarantee` trägt.
5. Die frühere DCA-Belegung zu
   `ls_shop_legalGuaranteeInfoPages` wird nicht mehr
   ausgewertet und muss nicht weiter gepflegt werden.

## Verifikation

Folgende Punkte im Frontend prüfen:

1. Eine Produktdetailseite mit GLL zeigt das SVG
   weiterhin an und ergänzt darunter den EU-Textlink.
2. Eine Produktdetailseite mit GARAN zeigt das SVG
   weiterhin an und ergänzt darunter den EU-Textlink.
3. Im Checkout-Review werden GLL und GARAN ohne
   Insert-Tags ausgegeben.
4. Die GLL-Verlinkung im Checkout führt bei deutscher
   Sprache zur Alias-Seite `gewaehrleistungslabel`,
   bei englischer Sprache zu `legal-guarantee`;
   falls keine passende Seite existiert, bleibt
   mindestens der EU-Link sichtbar.
