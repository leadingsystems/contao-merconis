# Manuelle Migration 002: Dynamic-Attachment-E-Invoicing-Script bereitstellen

**Erstellt:** 2026-10-15

## Kontext

Das Dynamic-Attachment-Script für E-Rechnungen
wird nicht mehr aus
`src/Resources/contao/installerResources/`
ausgeliefert. Der frühere Ablageort war ein
historisches Überbleibsel und ist nicht Teil
des aktiven Lieferwegs.

Bis zur späteren Theme-basierten Auslieferung
muss die Datei
`pcf-migrations/dynamicAttachment_einvoice_01.php`
manuell nach
`files/merconisfiles/theme/dynamicAttachmentFiles/dynamicAttachment_einvoice_01.php`
kopiert und in der betroffenen
Nachrichtenvorlage als Dynamic Attachment
zugewiesen werden.

Bei einer späteren Extension-Extraktion soll
das Script in das Theme-Paket verschoben und
über `merconis-theme-resources` ausgeliefert
werden.

## PRE-DEPLOY

1. Sicherstellen, dass im Zielsystem das
   Verzeichnis
   `files/merconisfiles/theme/dynamicAttachmentFiles/`
   existiert oder angelegt werden kann.
2. Die bisherige Konfiguration der
   Nachrichtenvorlage notieren, falls bereits
   ein anderes Dynamic-Attachment-Script
   hinterlegt ist.

## POST-DEPLOY

1. Datei aus dem Paket-Repository kopieren:

   - Quelle:
     `vendor/leadingsystems/contao-merconis/pcf-migrations/dynamicAttachment_einvoice_01.php`
   - Ziel:
     `files/merconisfiles/theme/dynamicAttachmentFiles/dynamicAttachment_einvoice_01.php`

2. Im Contao-Backend die betroffene
   Nachrichtenvorlage für E-Rechnungen
   öffnen.
3. Das Script
   `dynamicAttachment_einvoice_01.php` als
   Dynamic Attachment zuweisen.
4. Cache leeren, falls das Zielsystem
   Dateiänderungen nicht sofort sichtbar
   macht.

## Verifikation

Prüfen:

1. Die Datei liegt unter
   `files/merconisfiles/theme/dynamicAttachmentFiles/dynamicAttachment_einvoice_01.php`.
2. Die Nachrichtenvorlage verweist im Backend
   auf genau dieses Script.
3. Eine Testbestellung mit E-Rechnung erzeugt
   einen PDF-Anhang ohne Fehler im
   Nachrichtenversand.
