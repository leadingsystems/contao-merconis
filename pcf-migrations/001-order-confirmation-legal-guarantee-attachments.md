# Manuelle Migration 001: Order confirmation legal guarantee attachments

**Erstellt:** 2026-11-10

## Kontext

Die Label-Anhänge der Bestellbestätigung entstehen nicht mehr über einen
programmatischen Eingriff in den Mail-Body oder einen fest im Bundle liegenden
dynamischen Anhang. Stattdessen liefert Merconis eine händleranpassbare
Generator-Datei unter `pcf-migrations/` aus. Erst wenn diese Datei ins
Contao-Dateisystem kopiert, mit `contao:filesync` registriert und dem
Nachrichtenmodell der Bestellbestätigung zugeordnet wurde, hängen
Bestellbestätigungen GLL- und GARAN-PDFs an.

## POST-DEPLOY

1. Die Datei
   `vendor/leadingsystems/contao-merconis/pcf-migrations/001-order-confirmation-legal-guarantee-attachments/dynamicAttachment_legalGuaranteeLabels_01.php`
   nach
   `files/merconisfiles/theme/dynamicAttachmentFiles/dynamicAttachment_legalGuaranteeLabels_01.php`
   kopieren.
2. Die Dateiverwaltung synchronisieren:
   `php vendor/bin/contao-console contao:filesync`
3. Im Nachrichtenmodell der Bestellbestätigung die kopierte Datei je Sprache
   unter den dynamischen Anhängen auswählen:
   - Nachrichtentyp `asOrderConfirmation`
   - Sprache `de`
   - Sprache `en`
4. Cache leeren, falls die Auswahl im Backend nicht sofort sichtbar ist:
   `php vendor/bin/contao-console cache:clear`

## Verifikation

1. `php vendor/bin/contao-console merconis:legal-guarantee:order-attachments <ORDER_ID>`
   ausführen. Erwartet sind relative Pfade unter `bundles/` und/oder `files/`.
2. Eine Bestellbestätigung für eine gespeicherte Bestellung versenden.
3. Im Backend unter den gesendeten Nachrichten prüfen, dass die gespeicherten
   dynamischen Anhangspfade verlinkt sind und dass der Mail-Body keine
   Label-Ausgabe und keine EU-Textlinks enthält.
