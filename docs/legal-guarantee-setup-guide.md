# Gesetzliche Gewährleistung und Herstellergarantie einrichten

Ab Merconis 5.1.18 unterstützt der Shop die Informationspflichten, die seit dem
27.09.2026 für Verbraucherverträge über Waren gelten. Es gibt zwei amtliche
EU-Kennzeichnungen:

- **Mitteilung über die gesetzliche Gewährleistung**, kurz "gesetzliche
  Gewährleistung": eine amtliche Grafik, die auf das gesetzliche Gewährleistungsrecht
  von mindestens zwei Jahren hinweist.
- **Kennzeichnung der Herstellergarantie**, kurz "Herstellergarantie": das amtliche
  GARAN-Label für eine kostenlose Haltbarkeitsgarantie des Herstellers mit Marke,
  Modell und Garantiedauer.

## Hinweise zu dieser Anleitung

- Die Anleitung beschreibt das Verhalten mit den Standard-Templates von Merconis.
- Feldnamen stehen so, wie sie im Backend erscheinen. Technische Namen in Klammern
  braucht man nur für Import, Product Management API und Templates.
- Die Anleitung ist keine Rechtsberatung. Welche Kunden Verbraucher sind und welche
  Produkte eine Kennzeichnung brauchen, entscheidet der Shopbetreiber.

## 1. Überblick

### Wo die Kennzeichnungen erscheinen

| Ort | Gesetzliche Gewährleistung | Herstellergarantie |
|---|---|---|
| Produktdetailseite | Button, öffnet die Grafik | Button mit kleinem Label |
| Letzte Bestellseite | Button, einmal je Bestellung | Zuordnungsblock mit Labels |
| Bestellbestätigung | PDF-Anhang | PDF-Anhang |

Unter jeder Grafik steht ein Link zur Informationsseite der EU. Merconis setzt ihn
automatisch; der Shopbetreiber muss keine Adressen pflegen.

Im Warenkorb, auf der Rechnung, in der Versandmail und in den Checkout-Schritten
Adresse und Zahlung erscheinen keine Kennzeichnungen.

### Wovon die Anzeige abhängt

- **Gesetzliche Gewährleistung:** Sie ergibt sich aus der Einstellung der
  Mitgliedergruppe des Kunden (Abschnitt 3) und der Einstellung am Produkt
  (Abschnitt 4).
- **Herstellergarantie:** Sie erscheint nur, wenn die gesetzliche Gewährleistung für
  das Produkt angezeigt wird, die Herstellergarantie am Produkt eingeschaltet ist und
  Garantiedauer, Marke und Modell vollständig sind. Varianten können davon abweichen
  (Abschnitt 5).

Grundsatz: Lieber keine Kennzeichnung als eine falsche. Fehlt eine Voraussetzung,
entfällt die Ausgabe, ohne dass der Shop eine Fehlermeldung zeigt.

## 2. Nach dem Update auf 5.1.18

1. Das Datenbank-Update im Contao Manager ausführen.
2. Bei jeder Mitgliedergruppe die Einstellung "Gesetzliche Gewährleistung" festlegen
   (Abschnitt 3). Nach dem Update ist sie bei allen Gruppen leer. Solange das so ist,
   zeigt der Shop weder die gesetzliche Gewährleistung noch die Herstellergarantie. Ein
   Hinweis auf dem Merconis-Dashboard und auf der Startseite des Backends erinnert
   daran, bis alle Gruppen eingestellt sind.
3. Die Produkte prüfen (Abschnitt 4). Alle Produkte stehen nach dem Update auf "Wie
   Mitgliedergruppe". Für reine digitale Inhalte, SaaS und Dienstleistungen "Nie
   anzeigen" wählen. Wo der Hersteller eine qualifizierende Garantie gewährt, die
   Herstellergarantie einschalten und Dauer, Marke und Modell pflegen.
4. Für PDF-Anhänge in der Bestellbestätigung die manuelle Migration
   `pcf-migrations/001-order-confirmation-legal-guarantee-attachments.md` ausführen
   (Abschnitt 8). Ohne sie wird die Bestellbestätigung ohne diese Anhänge versendet.
5. Optional: eine Infoseite zur gesetzlichen Gewährleistung anlegen (Abschnitt 7.6).

## 3. Einstellung an der Mitgliedergruppe

Die Einstellung "Gesetzliche Gewährleistung" steht bei jeder Mitgliedergruppe in der
Palette "MERCONIS-Einstellungen". Sie legt fest, ob Mitglieder dieser Gruppe die
Kennzeichnungen sehen. Das Feld ist ein Pflichtfeld: Wer eine Gruppe speichert, muss
einen Wert wählen.

| Auswahl | Bedeutung | Typischer Einsatz |
|---|---|---|
| "Anzeigen" | Produkte mit "Wie Mitgliedergruppe" und "Immer anzeigen" | Verbraucher |
| "Nur Produkte mit 'Immer anzeigen'" | nur Produkte mit "Immer anzeigen" | siehe unten |
| "Nie anzeigen (z. B. Geschäftskunden)" | keine Kennzeichnung | ohne Verbraucher |
| leer ("Bitte wählen") | nicht festgelegt, wirkt wie "Nie anzeigen" | nach dem Update |

"Nur Produkte mit 'Immer anzeigen'" ist für Shops gedacht, die überwiegend
Dienstleistungen oder digitale Inhalte verkaufen. Dort stehen nur die Waren auf
"Immer anzeigen".

Zusammenspiel von Mitgliedergruppe und Produkt:

| Mitgliedergruppe | Produkt "Wie Mitgliedergruppe" | "Immer anzeigen" | "Nie anzeigen" |
|---|---|---|---|
| "Anzeigen" | angezeigt | angezeigt | nicht angezeigt |
| "Nur Produkte mit 'Immer anzeigen'" | nicht angezeigt | angezeigt | nicht angezeigt |
| "Nie anzeigen" oder leer | nicht angezeigt | nicht angezeigt | nicht angezeigt |

Welche Gruppe gilt:

- **Gäste:** die Standard-Benutzergruppe aus den Merconis-Grundeinstellungen. Ihre
  Einstellung ist damit die wichtigste im Shop.
- **Angemeldete Kunden:** die für sie maßgebliche Gruppe, wie bei Preisen und
  Zahlungsarten. Der Anmeldestatus allein sagt nichts darüber, ob ein Kunde Verbraucher
  ist; das legt allein die Einstellung der Gruppe fest.

Eine Änderung an der Gruppe oder an der Standard-Benutzergruppe passt keine Produkte
an. Sie wirkt sofort auf die Anzeige.

Für Projekte, die nach Seiten-Root oder Domain unterscheiden müssen, lassen sich die
Gruppeneinstellungen über den Hook `getGroupSettingsForUser` anpassen. Das wirkt auf
die Anzeige, nicht auf die automatischen Anpassungen im Backend (Abschnitt 6).

## 4. Einstellungen am Produkt

Die Felder stehen in der Palette "Gewährleistung und Herstellergarantie
(GARAN-Label)". In der Produktliste gibt es Filter für beide Einstellungen.

### 4.1 Gesetzliche Gewährleistung

| Auswahl | Bedeutung |
|---|---|
| "Wie Mitgliedergruppe" (Standard) | folgt der Gruppe; richtig für Waren |
| "Immer anzeigen" | auch in Gruppen mit "Nur Produkte mit 'Immer anzeigen'" |
| "Nie anzeigen" | keine Kennzeichnung, auch keine Herstellergarantie |

- "Wie Mitgliedergruppe" passt für körperliche Waren und Waren mit digitalen
  Elementen, auch für Gebrauchtwaren. Die amtliche Grafik weist selbst auf mögliche
  kürzere Fristen bei Gebrauchtwaren hin.
- "Immer anzeigen" ist für Waren in Shops gedacht, die sonst überwiegend
  Dienstleistungen oder digitale Inhalte verkaufen. In Gruppen mit "Nie anzeigen"
  erscheint auch dann nichts.
- "Nie anzeigen" ist für reine digitale Inhalte, SaaS und Dienstleistungen.

Die Einstellung gibt es nur am Produkt. Varianten übernehmen sie vom Produkt.

### 4.2 Herstellergarantie

Der Schalter "Herstellergarantie anzeigen (GARAN-Label)" ist standardmäßig aus. Ist er
an, erscheinen die Felder "Garantiedauer in Monaten", "Marke (Brand/Trademark)" und
"Modell (Model identifier)". Alle drei sind dann Pflichtfelder.

Mit dem Einschalten bestätigt der Shopbetreiber Voraussetzungen, die Merconis nicht
prüfen kann:

- Der Hersteller gewährt eine Haltbarkeitsgarantie.
- Sie ist ohne zusätzliche Kosten.
- Sie gilt für die gesamte Ware, nicht nur für einzelne Teile.
- Sie dauert länger als zwei Jahre.
- Der Hersteller hat die Garantieinformationen zur Verfügung gestellt.

Zufriedenheitsgarantien, Anschlussgarantien, kostenpflichtige Garantien und
Teilegarantien gehören nicht dazu. Nach Garantieinformationen auf Herstellerwebsites
muss der Shopbetreiber nicht aktiv suchen.

**Garantiedauer:** in ganzen Monaten, von 30 bis 360, in Schritten von 6. Das Label
zeigt Jahre mit Komma: 30 Monate werden "2,5", 36 Monate "3". Ein anderer Wert wird
nicht gespeichert und nicht gerundet.

**Marke:** Ist die gespeicherte Marke leer, zeigt das Feld beim Einblenden den
Hersteller des Produkts als Vorschlag. Gespeichert wird er erst mit dem Datensatz; eine
vorhandene Marke wird nie überschrieben. Ändert man den Hersteller in derselben
Bearbeitung, wird der Vorschlag nicht nachgeführt.

**Modell:** Die Modellbezeichnung gilt für alle Sprachen.

Marke und Modell werden vollständig gespeichert, bis zu 255 Zeichen. Im Label werden
sie bei Platzmangel gekürzt (Abschnitt 7.5).

## 5. Varianten

Bei einer Variante legt die Auswahl "Herstellergarantie der Variante" fest, was gilt:

| Auswahl | Bedeutung |
|---|---|
| "Vom Produkt übernehmen" (Standard) | Herstellergarantie und Werte wie beim Produkt |
| "Keine Ausgabe" | keine Herstellergarantie für diese Variante |
| "Eigene Angaben" | nur die eigene Dauer, Marke und Modell der Variante |

- Bei "Eigene Angaben" sind Dauer, Marke und Modell Pflicht. Es gilt kein Wert vom
  Produkt; leere Felder werden nicht vom Produkt ergänzt.
- Die Marke wird beim Einblenden mit der Marke des Produkts vorgeschlagen, ist diese
  leer, mit dessen Hersteller.
- Bei "Vom Produkt übernehmen" und "Keine Ausgabe" dürfen eigene Werte gespeichert
  bleiben; sie wirken dann nicht.
- Die gesetzliche Gewährleistung kommt immer vom Produkt.

## 6. Automatische Anpassungen und Hinweise im Backend

Die Herstellergarantie setzt die gesetzliche Gewährleistung voraus. Beim Speichern im
Backend gibt es keinen Besucher; Merconis prüft deshalb gegen die Einstellung der
Standard-Benutzergruppe, also gegen das, was Gäste sehen würden.

Als "keine gesetzliche Gewährleistung für Gäste" zählt je nach Standard-Benutzergruppe:

| Standard-Benutzergruppe | Produkteinstellung, die als "keine Anzeige" zählt |
|---|---|
| "Anzeigen" | "Nie anzeigen" |
| "Nur Produkte mit 'Immer anzeigen'" | "Wie Mitgliedergruppe" und "Nie anzeigen" |
| "Nie anzeigen" oder leer | "Nie anzeigen" |

Daraus ergeben sich vier Regeln:

1. **Herstellergarantie eingeschaltet, aber keine gesetzliche Gewährleistung für
   Gäste:** Merconis passt die Einstellung "Gesetzliche Gewährleistung" beim Speichern
   an. Bei Standard-Benutzergruppe "Nur Produkte mit 'Immer anzeigen'" wird sie auf
   "Immer anzeigen" gesetzt, sonst von "Nie anzeigen" auf "Wie Mitgliedergruppe". Das
   gilt auch beim Speichern ohne geänderte Felder.
2. **Gesetzliche Gewährleistung abgewählt:** Ändert man die Einstellung so, dass Gäste
   keine gesetzliche Gewährleistung sehen, und ist die Herstellergarantie an, schaltet
   Merconis die Herstellergarantie aus. Dauer, Marke und Modell bleiben gespeichert.
3. **Variante mit "Eigene Angaben":** Hat das Produkt keine gesetzliche Gewährleistung
   für Gäste, lässt sich "Eigene Angaben" nicht wählen. Die Meldung nennt den Weg:
   zuerst die Einstellung am Produkt ändern und speichern.
4. **Produkt mit Varianten auf "Eigene Angaben":** Die Einstellung "Gesetzliche
   Gewährleistung" lässt sich dann nicht so ändern, dass Gäste keine gesetzliche
   Gewährleistung sehen. Die Meldung nennt die betroffenen Varianten.

Über jede automatische Änderung informiert ein Hinweis nach dem Speichern.

Hinweise beim Öffnen eines Datensatzes:

- **Produkt:** Ist die Herstellergarantie gespeichert an, aber für Gäste keine
  gesetzliche Gewährleistung vorgesehen, nennt ein Hinweis Ursache und Weg. Das kann
  passieren, wenn die Standard-Benutzergruppe später geändert wurde.
- **Variante:** Steht die Variante auf "Eigene Angaben" und hat das Produkt keine
  gesetzliche Gewährleistung für Gäste, weist ein Hinweis auf das Produkt hin.

Weitere Punkte:

- Das Umschalten der Herstellergarantie in der Maske blendet die Felder ein und aus.
  Contao speichert dabei den Schalter. Bleiben Pflichtfelder leer, gibt es keine
  Ausgabe.
- In "Mehrere bearbeiten" und "Mehrere überschreiben" ändert Regel 1 die Einstellung
  "Gesetzliche Gewährleistung" nur, wenn dieses Feld mit ausgewählt ist.
- Import und Product Management API passen nichts automatisch an (Abschnitt 9).

## 7. Darstellung im Shop

### 7.1 Produktdetailseite

- **Gesetzliche Gewährleistung:** ein Button "Ihre gesetzlichen
  Gewährleistungsrechte", im Standard-Template direkt nach der Produktbeschreibung.
- **Herstellergarantie:** ein Button mit dem kleinen Label (Nested Label) und der
  Garantiedauer, im Standard-Template bei den Produktangaben unter dem Titel, vor dem
  Preis. Das Label folgt der gewählten Variante.

Ein Klick öffnet die vollständige Grafik mit dem EU-Link über der Seite. Geschlossen
wird sie mit "×", der Taste Esc oder einem Klick daneben. Kann ein Label nicht erzeugt
werden, entfällt der Button; Merconis schreibt einen Eintrag in das System-Log.

### 7.2 Letzte Bestellseite

Auf der letzten Bestellseite, vor dem Kaufabschluss:

- **Herstellergarantie:** ein eigener Block. Eine Überschrift stellt klar, dass sich
  die Garantie nur auf die genannten Artikel bezieht. Darunter steht je Label ein Kasten
  mit den zugehörigen Artikeln ("Produktname (Variante) - Artikelnummer"), dem
  vollständigen Label und dem EU-Link. Artikel desselben Produkts mit gleicher Marke,
  gleichem Modell und gleicher Dauer teilen sich ein Label.
- **Gesetzliche Gewährleistung:** einmal derselbe Button wie auf der
  Produktdetailseite, wenn mindestens ein Artikel sie hat.

### 7.3 Sprache

Die Grafik der gesetzlichen Gewährleistung erscheint in der Shopsprache. Gibt es dafür
keine amtliche Fassung, erscheint die englische. Das GARAN-Label ist mehrsprachig und
gleich in allen Sprachen.

### 7.4 Templates und Gestaltung

Für eigene Templates stehen diese Ausgaben bereit:

| Ort | Button | Direkte Ausgabe ohne Klick |
|---|---|---|
| Produkt, gesetzliche Gewährleistung | `_lgnNoticeButton` | `_lgnNotice` |
| Produkt, Herstellergarantie | `_garanLabelButton` | `_garanLabel` |
| Bestellseite, Gewährleistung | `checkoutLgnNoticeButton` | `checkoutLgnNotice` |
| Bestellseite, Herstellergarantie | – | `checkoutGaranBlock` |

Die Produktausgaben sind Eigenschaften des Produktobjekts (zum Beispiel
`$this->objProduct->_garanLabelButton`), die Ausgaben der letzten Bestellseite
Variablen des Templates `template_orderReview_default`.

Alle Ausgaben bestehen aus überschreibbaren Bausteinen:

- `template_legalGuaranteeUnit`: Grafik mit EU-Link,
- `template_legalGuaranteeButton`: Button mit geöffneter Ansicht,
- `template_legalGuaranteeAssignment`: Block der Herstellergarantie auf der letzten
  Bestellseite.

Ein Theme kann Aufbau und Gestaltung über eigene Templates ändern; die amtliche Grafik
selbst darf nicht verändert werden. Die Bausteine tragen Klassen mit dem Präfix
`merconis-legal-guarantee`. Die Grundgestaltung liefert Merconis über LSCSS mit; die
Abstimmung auf ein Theme ist Sache der Theme-Erweiterung. Die Schrift Inter für das
GARAN-Label bindet Merconis selbst ein.

### 7.5 Marke und Modell: Kürzung im Label

Im Label stehen Marke und Modell in einer gemeinsamen Zeile, die Marke links, das
Modell rechts, mit einem deutlich sichtbaren Abstand dazwischen. Passen beide nicht
hinein, kürzt Merconis in der Darstellung:

1. Passen beide, erscheinen sie vollständig.
2. Sonst hat das Modell Vorrang. Die Marke behält mindestens etwa so viel Platz, wie
   der amtliche Platzhalter "Brand/Trademark" einnimmt. Das Modell erhält den übrigen
   Platz und wird gekürzt, wenn es länger ist. Platz, den das Modell nicht braucht,
   erhält die Marke.
3. Gekürzt wird am Ende, Zeichen für Zeichen, mit "..." am Schluss.

Wie viel passt, hängt von der Breite der Zeichen ab, nicht von ihrer Anzahl. Breite
Buchstaben wie "W" brauchen mehr Platz als schmale wie "i". Das Ergebnis ist auf der
Website und im PDF gleich und hängt nicht von der Sprache ab. Die Garantiedauer wird nie
gekürzt.

Gespeicherte Daten und Bestellungen enthalten immer die vollständigen Werte. Für
Screenreader trägt das Label auf der Website einen Titel mit den vollständigen Werten.

Das Backend zeigt keinen Hinweis, wenn ein Wert gekürzt erscheint. Prüfen Sie lange
Werte nach dem Speichern auf der Produktdetailseite über den Button der
Herstellergarantie. Marke und Modell sollten so eingetragen werden, wie sie in der
Garantieerklärung des Herstellers stehen; ob eine kürzere Schreibweise zulässig ist,
richtet sich nach dieser Erklärung.

Für Entwickler: Bemessen wird mit den Zeichenbreiten von Inter Regular in der
Schriftgröße der Vorlage, ohne Zeichenabstand und ohne Kerning. Der Mindestabstand
beträgt 1,5 Schriftgrößen, die Mindestbreite der Marke 75 Einheiten der `viewBox`. Die
Breitentabelle liegt in
`src/Resources/legal-guarantee/garan/v1.0/inter-regular-advance-widths.json`.

### 7.6 Infoseite zur gesetzlichen Gewährleistung (optional)

Die Buttons auf Produktdetailseite und letzter Bestellseite zeigen die vollständige
Grafik. Eine eigene Infoseite ist deshalb nicht nötig. Wer sie trotzdem möchte, legt je
Sprach-Root eine normale Contao-Seite an und setzt in ein Inhaltselement den Insert-Tag
`{{shop_eu_lgn}}`. Er gibt die Grafik in der aktuellen Shopsprache mit dem EU-Link aus.
Merconis legt die Seite nicht an und verlinkt sie nicht.

## 8. Bestellungen

### 8.1 Was mit der Bestellung gespeichert wird

Merconis speichert mit jeder Bestellung, welche Kennzeichnungen zum Bestellzeitpunkt
galten, einschließlich Marke, Modell und Dauer. Maßgeblich ist die Gruppe des Käufers
zum Bestellzeitpunkt. Spätere Änderungen an Produkten oder Gruppen verändern das nicht.

### 8.2 Anhänge der Bestellbestätigung

Nach Ausführung der manuellen Migration 001 enthält die Bestellbestätigung höchstens
zwei PDF-Anhänge:

- **Gesetzliche Gewährleistung:** die amtliche Fassung in der Sprache der Bestellung,
  wenn mindestens ein Artikel sie hat. Die Datei liefert Merconis mit; es entsteht
  keine neue Datei.
- **Herstellergarantie:** ein PDF im Format DIN A5 mit einem Label je Seite, darüber
  die zugehörigen Artikel, darunter der EU-Link. Es wird bei jeder Bestellbestätigung
  neu erzeugt.

Die Kennzeichnungen stehen nur in den Anhängen, nicht im Text der E-Mail. Die
Bestellbestätigung wird immer versendet. Kann ein Label nicht erzeugt werden, steht im
PDF an seiner Stelle eine Hinweisseite, und Merconis schreibt einen Eintrag in das
System-Log.

Die Anhänge lassen sich im Backend unter den gesendeten Nachrichten öffnen.

### 8.3 Erzeugte Dateien

Die PDFs der Herstellergarantie liegen unter
`files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/` in
Unterordnern nach dem Bestelldatum. Merconis löscht sie nicht; das Aufräumen ist Sache
des Shopbetreibers.

## 9. Import, Product Management API und MPM

Die Felder sind reguläre Spalten im Import und Felder der Product Management API:

| Spalte | Zeile | Leer oder fehlend | Zulässig |
|---|---|---|---|
| `lgnMode` | Produkt | `inherit` | `inherit`, `always`, `never` |
| `enableGaran` | Produkt | aus | leer und `0` aus, sonst an |
| `garanMode` | Variante | `inherit` | `inherit`, `disabled`, `override` |
| `guaranteeDurationMonths` | Produkt, Variante | leer | 30 bis 360, Schritt 6 |
| `guaranteeBrand` | Produkt, Variante | leer | höchstens 255 Zeichen |
| `guaranteeModelIdentifier` | Produkt, Variante | leer | höchstens 255 Zeichen |

Wichtig:

- **Leere Werte überschreiben.** Eine leere Zelle oder eine fehlende Spalte setzt den
  Standardwert beziehungsweise einen leeren Wert, auch wenn das Produkt vorher andere
  Werte hatte. Eine Datei ohne diese Spalten schaltet die Herstellergarantie der
  enthaltenen Produkte aus und leert Marke, Modell und Dauer.
- **Strenge Prüfung.** Alle Werte werden vor dem Import geprüft. Ein einziger
  unzulässiger Wert macht die ganze Datei ungültig; bei der API wird der ganze Aufruf
  abgelehnt. Die Meldungen nennen alle betroffenen Zeilen auf einmal.
- **Abhängigkeiten in der Datei:** Ist die Herstellergarantie an oder steht eine
  Variante auf `override`, müssen Dauer, Marke und Modell in derselben Zeile stehen.
  Herstellergarantie an und `lgnMode` `never` ist unzulässig, ebenso eine Variante auf
  `override`, deren Produkt in der Datei auf `never` steht.
- **Keine Vorbelegung und keine automatische Anpassung.** Die Marke wird nicht aus dem
  Hersteller übernommen, und die Regeln aus Abschnitt 6 greifen nicht.
- **Export:** Exportiert werden die gespeicherten Werte. Die Spaltennamen der
  Exportdatei legt das Export-Template fest.

**MPM:** Der Merconis Product Manager bis einschließlich Version 1.66 kennt diese
Spalten noch nicht. Jeder Import einer MPM-Datei schaltet deshalb die Herstellergarantie
der enthaltenen Produkte aus und leert Marke, Modell und Dauer; die gesetzliche
Gewährleistung steht danach auf "Wie Mitgliedergruppe". Wer mit dem MPM arbeitet, pflegt
diese Angaben bis zu einer angepassten MPM-Version nach jedem Import im Backend nach.

## 10. Fehlersuche

**Auf der Produktdetailseite erscheint keine gesetzliche Gewährleistung:**

1. Welche Gruppe gilt für den Besucher? Für Gäste ist es die Standard-Benutzergruppe.
2. Steht diese Gruppe auf "Anzeigen"? Ist sie leer oder steht sie auf "Nie anzeigen",
   erscheint nichts.
3. Steht das Produkt auf "Nie anzeigen"? Bei einer Gruppe mit "Nur Produkte mit 'Immer
   anzeigen'" erscheint nur bei "Immer anzeigen" etwas.
4. Verwendet das Projekt den Hook `getGroupSettingsForUser` oder ein eigenes Template
   der Produktdetailseite ohne die Buttons?

**Die Herstellergarantie erscheint nicht:**

1. Erscheint die gesetzliche Gewährleistung? Ohne sie gibt es keine Herstellergarantie.
2. Ist der Schalter "Herstellergarantie anzeigen (GARAN-Label)" an, und sind Dauer,
   Marke und Modell gefüllt?
3. Bei Varianten: Steht die Variante auf "Keine Ausgabe", oder fehlen bei "Eigene
   Angaben" Werte?
4. Gibt es im System-Log einen Eintrag, dass ein Label nicht erzeugt werden konnte?

**Die Bestellbestätigung hat keine Anhänge:**

1. Ist die manuelle Migration 001 ausgeführt, und ist die Datei im Nachrichtenmodell
   der Bestellbestätigung für die Sprache der Bestellung ausgewählt?
2. Galt für den Käufer zum Bestellzeitpunkt eine Gruppe mit Anzeige, und hatte die
   Bestellung Artikel mit Kennzeichnung?

**Nach einem Import fehlen Angaben zur Herstellergarantie:**

Die Importdatei enthielt die Spalten nicht oder leere Zellen, zum Beispiel aus dem MPM
(Abschnitt 9).

**Der Hinweis auf dem Dashboard verschwindet nicht:**

Mindestens eine Mitgliedergruppe hat noch keinen Wert, auch eine selten genutzte.

## 11. Nicht enthalten

Diese Version enthält unter anderem nicht:

- eine automatische Unterscheidung zwischen Geschäfts- und Privatkunden,
- eine Unterscheidung nach Seiten-Root, Domain oder Land (nur über den Hook,
  Abschnitt 3),
- Garantiewerte je Markt oder Vorgaben für Garantiewerte am Hersteller,
- einen Link auf die vollständige Garantieerklärung des Herstellers,
- einen Hinweis im Backend, wenn Marke oder Modell gekürzt dargestellt werden,
- eine mehrsprachige Modellbezeichnung.
