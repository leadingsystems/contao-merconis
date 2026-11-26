# Manuelle Migration 001: Order confirmation legal guarantee attachments

**Erstellt:** 2026-11-10
**Aktualisiert:** 2026-11-26 (Release 5.1.18)

## Kontext

Ab Merconis 5.1.18 kann die Bestellbestätigung die Mitteilung über die gesetzliche
Gewährleistung (LGN) und die Kennzeichnung der Herstellergarantie (GARAN) als
PDF-Anhänge enthalten: höchstens ein LGN-PDF und ein GARAN-PDF. Die Labels stehen
ausschließlich in den Anhängen; der E-Mail-Body enthält keine Label-Ausgabe und keine
EU-Textlinks.

Die Anhänge entstehen über einen dynamischen Anhang, auf demselben Weg wie die
Rechnung. Merconis liefert dafür eine Generator-Datei aus, die der Shopbetreiber
anpassen darf. Sie wird bei keiner Installation automatisch eingerichtet, auch nicht
bei einer Neuinstallation. Erst wenn die Datei im Contao-Dateisystem liegt und dem
Nachrichtenmodell der Bestellbestätigung zugeordnet ist, enthalten Bestellbestätigungen
Label-Anhänge. Ohne Zuordnung wird die Bestellbestätigung wie bisher ohne Label-Anhänge
versendet.

## POST-DEPLOY

Die Schritte folgen auf das Update über den Contao Manager einschließlich
Datenbank-Update.

Quelle der Generator-Datei:

```text
vendor/leadingsystems/contao-merconis/pcf-migrations/001-order-confirmation-legal-guarantee-attachments/dynamicAttachment_legalGuaranteeLabels_01.php
```

Ziel:

```text
files/merconisfiles/theme/dynamicAttachmentFiles/dynamicAttachment_legalGuaranteeLabels_01.php
```

1. Die Generator-Datei von der Quelle zum Ziel kopieren, zum Beispiel per SFTP oder
   auf der Shell. Fehlt der Zielordner, ihn anlegen. Der Upload in der
   Dateiverwaltung des Backends nimmt PHP-Dateien in der Regel nicht an.
2. Die Dateiverwaltung synchronisieren: im Backend unter "Dateiverwaltung" mit
   "Synchronisieren" oder auf der Shell mit
   `php vendor/bin/contao-console contao:filesync`.
3. Im Nachrichtenmodell der Bestellbestätigung (Nachrichtentyp `asOrderConfirmation`)
   die kopierte Datei unter den dynamischen Anhängen auswählen, und zwar für jede
   Sprache, in der Bestellbestätigungen versendet werden.
4. Ist die Datei bei der Auswahl nicht sichtbar, den Cache leeren: im Contao Manager
   unter "Systemwartung" oder auf der Shell mit
   `php vendor/bin/contao-console cache:clear`.

## Verifikation

Voraussetzungen für eine aussagekräftige Prüfung:

- Bei der Mitgliedergruppe des Testkäufers ist die Einstellung "Gesetzliche
  Gewährleistung" auf "Anzeigen" gesetzt. Nach dem Update ist sie bei allen
  Mitgliedergruppen leer; solange das so ist, zeigt der Shop weder LGN noch GARAN, und
  die Bestellbestätigung erhält keine Label-Anhänge. Das Dashboard weist darauf hin.
  Für Gäste gilt die Einstellung der Standard-Benutzergruppe.
- Die Testbestellung enthält ein Produkt mit LGN-Output und, für das GARAN-PDF, ein
  Produkt mit eingeschalteter Herstellergarantie und vollständigen Angaben (Dauer,
  Marke, Modell).
- Die Bestellung wurde nach dem Update aufgegeben. Maßgeblich ist der Label-Zustand
  zum Bestellzeitpunkt, der mit der Bestellung gespeichert wird.

Prüfschritte:

1. Die Testbestellung abschließen und die Bestellbestätigung öffnen. Erwartet: ein
   LGN-PDF und, falls eine Position eine Herstellergarantie hat, ein GARAN-PDF; der
   E-Mail-Body ohne Label-Ausgabe und ohne EU-Textlinks.
2. Im Backend unter den gesendeten Nachrichten prüfen, dass die Anhänge verlinkt sind
   und sich öffnen lassen:
   - LGN-PDF: Pfad unter `bundles/leadingsystemsmerconis/legal-guarantee/lgn/`
     (mit Merconis ausgelieferte Datei).
   - GARAN-PDF: Pfad unter
     `files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/`
     in Unterordnern nach dem Bestelldatum (`<yyyy>/<mm>/<dd>/`).
3. Optional, mit Shell-Zugang:
   `php vendor/bin/contao-console merconis:legal-guarantee:order-attachments <ORDER_ID>`
   gibt die relativen Pfade der Anhänge einer gespeicherten Bestellung aus, ohne eine
   Nachricht zu versenden. Der Befehl erzeugt dabei jedes Mal ein neues GARAN-PDF
   (mit Laufnummer `_2`, `_3`, ...).

## Hinweise

- Erzeugte GARAN-PDFs bleiben unter
  `files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/` liegen.
  Aufräumen ist Sache des Shopbetreibers. Für das LGN-PDF entsteht keine Datei.
- Die Bestellbestätigung wird immer versendet, auch wenn ein Label nicht aufbereitet
  werden kann. An der Stelle eines solchen Labels steht im GARAN-PDF eine Hinweisseite,
  und Merconis schreibt einen Eintrag in das System-Log.
- Die kopierte Generator-Datei gehört dem Shopbetreiber. Liefert eine spätere
  Merconis-Version eine geänderte Fassung aus, wird die Kopie nicht automatisch
  aktualisiert.
- Anpassen darf der Shopbetreiber den Aufbau um die GARAN-Grafik. Die amtliche Grafik,
  die drei Datenfelder und das LGN-PDF ersetzt die Datei nicht.
