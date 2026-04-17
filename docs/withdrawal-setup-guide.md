# Widerrufsfunktion einrichten

## Ablauf der Widerrufsfunktion

Die Widerrufsfunktion bietet Kunden drei Wege,
einen Widerruf online abzugeben. Im Anschluss
erhält der Kunde eine Eingangsbestätigung per
E-Mail und der Shopbetreiber eine
Benachrichtigung.

### Einstiegswege

**1. Direktlink aus der Bestellbestätigung oder
dem Kundenaccount:**
Der Kunde erhält mit seiner Bestellbestätigung
per E-Mail einen Direktlink und eine
Identifikationsnummer für den Widerruf.
Eingeloggte Kunden finden den Direktlink
zusätzlich in der Bestellübersicht und den
Bestelldetails. Der Direktlink führt direkt zum
Widerrufsformular mit den Bestellpositionen
(ohne erneute Eingabe der
Identifikationsnummer).

**2. Widerrufsbutton auf der Webseite:**
Über den dauerhaft sichtbaren Link "Vertrag
widerrufen" (z. B. im Footer) gelangt der Kunde
auf die Einstiegsseite. Dort gibt er seine
Identifikationsnummer ein, die er aus der
Bestellbestätigung oder der Rechnung entnehmen
kann. Nach erfolgreicher Eingabe wird er zum
Widerrufsformular weitergeleitet.

**3. Freitextformular (Fallback):**
Kann eine Identifikationsnummer nicht zugeordnet
werden (z. B. weil der Bestelldatensatz nicht
mehr existiert), oder hat der Kunde
Schwierigkeiten bei der Eingabe, steht ein
Freitextformular als Fallback zur Verfügung.
Der Fallback-Link ist auf der Einstiegsseite
jederzeit sichtbar und wird nach drei
fehlgeschlagenen Eingabeversuchen besonders
hervorgehoben. Im Freitextformular gibt der
Kunde Name, E-Mail-Adresse und eine freie
Beschreibung des Vertrags an.

### Widerrufsformular (bei zugeordneter Bestellung)

Wurde die Bestellung identifiziert, zeigt das
Widerrufsformular alle Bestellpositionen mit
Produktbezeichnung, Artikelnummer, Einzelpreis
und Menge an. Der Kunde wählt die zu
widerrufenden Positionen aus und kann die Menge
bei Bedarf anpassen (Teilwiderruf). Zusätzlich
bestätigt er seinen Namen und die
E-Mail-Adresse, an die die Eingangsbestätigung
gesendet werden soll. Der Widerruf wird über
die Schaltfläche "Widerruf bestätigen"
abgesendet (zweistufiges Verfahren gemäß
gesetzlicher Vorgabe).

### Nach dem Absenden

Nach dem Absenden wird der Kunde auf eine
Bestätigungsseite weitergeleitet, die die
Widerrufs-ID und einen Bestätigungstext anzeigt.
Gleichzeitig erhält er eine Eingangsbestätigung
per E-Mail mit Widerrufs-ID, Datum und Uhrzeit.
Der Shopbetreiber erhält eine
Benachrichtigungs-E-Mail mit allen relevanten
Daten zum Widerruf.

### Nach Eingang beim Händler

Eingegangene Widerrufe können über "Gesendete
Nachrichten" eingesehen und nach
Nachrichtenart gefiltert werden. Von dort aus
kann der Shopbetreiber den Widerruf
weiterbearbeiten.

## Hinweise zu dieser Anleitung

Diese Anleitung richtet sich primär an
Shopbetreiber, die nach einem Merconis-Update die
Widerrufsfunktion in einem bereits bestehenden
Shop nachträglich einrichten möchten. Sie
beschreibt alle notwendigen Schritte, um die
Funktion betriebsbereit zu machen.

Bei einer Neuinstallation von Merconis mit
Grundeinrichtung sind die hier beschriebenen
Schritte bereits voreingerichtet. In diesem Fall
dient die Anleitung als Referenz, um die
Zusammenhänge der einzelnen Komponenten
(Seiten, Module, Nachrichtenvorlagen,
Platzhalter) zu verstehen und bei Bedarf
Anpassungen vorzunehmen.

**Rechtlicher Hinweis:** Diese Anleitung
beschreibt die technische Einrichtung der
Widerrufsfunktion in Merconis. Soweit im Text auf
gesetzliche Vorgaben Bezug genommen wird, dient
dies ausschließlich der Einordnung und stellt
keine Rechtsberatung dar. Die Verantwortung für
die Einhaltung der rechtlichen
Rahmenbedingungen liegt beim Shopbetreiber. Wir
empfehlen, die erfolgte Einrichtung
gegebenenfalls durch eine Rechtsberatung prüfen
zu lassen.

## Setup-Schritte

### Schritt 1: Seiten anlegen

Legen Sie zwei neue Contao-Seiten an: eine
Widerrufs-Einstiegsseite und eine
Widerrufs-Bestätigungsseite.

Als Ablageort empfiehlt sich der Bereich
unterhalb von "MERCONIS-SYSTEM" im Seitenbaum,
wo auch die anderen internen
Merconis-Prozessseiten liegen. Sie können die
Seiten aber auch an beliebiger anderer Stelle
anlegen, z. B. direkt im Hauptmenü, wenn sie
dort sichtbar sein sollen.

Die Bestätigungsseite sollten Sie auf "Im Menü
verstecken" setzen, da sie beim direkten Aufruf
keine Funktionalität bietet. Seitennamen und
Aliase können Sie frei wählen -- sie haben
keinen Einfluss auf die Funktionalität.

**Mehrsprachige Shops:** Legen Sie die Seiten pro
Sprachbaum an. Wählen Sie in der
Seitenkonfiguration der Fremdsprach-Seite die
"Korrespondierende Seite in der Hauptsprache"
aus.

### Schritt 2: Module anlegen und auf den Seiten platzieren

Legen Sie im Contao-Backend unter "Themes" ->
Module zwei neue Frontend-Module an:

- **Modul "Widerruf"**: Wählen Sie den Modultyp
  "Widerruf" (unter MERCONIS - Shop). Weitere
  Einstellungen sind nicht nötig.
- **Modul "Widerrufsbestätigung"**: Wählen Sie
  den Modultyp "Widerrufsbestätigung" (unter
  MERCONIS - Shop). Dieses Modul verfügt über
  ein optionales Textfeld "Text für
  Widerrufsbestätigung", mit dem Sie den
  Standardtext überschreiben können. Empfehlung:
  Lassen Sie das Feld leer, da aktuell keine
  dynamischen Platzhalter unterstützt werden. Bei
  leerem Feld greift der Sprach-Fallback.

Platzieren Sie die Module anschließend auf den
entsprechenden Seiten: Legen Sie in den Artikeln
der zuvor angelegten Seiten jeweils ein
Inhaltselement vom Typ "Modul" an und wählen Sie
das passende Modul aus:

- Artikel der Widerrufs-Einstiegsseite: Modul
  "Widerruf" auswählen (Hauptspalte)
- Artikel der Bestätigungsseite: Modul
  "Widerrufsbestätigung" auswählen
  (Hauptspalte)

### Schritt 3: Seiten in Merconis registrieren

Navigieren Sie zu MERCONIS-SHOP ->
Grundeinstellungen -> Bereich
"Seiten-Einstellungen". Konfigurieren Sie dort
die beiden folgenden Einstellungen:

- **Seite "Widerruf"**: Wählen Sie die in
  Schritt 1 angelegte Widerrufs-Einstiegsseite
  aus. Bei mehrsprachigen Shops wählen Sie alle
  Sprachvarianten aus (z. B.
  "Widerrufs-Einstieg
  (de/widerrufs-einstieg/)" und "Start of the
  Cancellation Process
  (en/start-of-the-cancellation-process/)").
- **Seite "Widerrufsbestätigung"**: Wählen Sie
  die angelegte Bestätigungsseite aus. Bei
  mehrsprachigen Shops analog alle
  Sprachvarianten auswählen (z. B.
  "Widerrufs-Bestätigungsseite
  (de/widerrufs-bestaetigungsseite/)" und
  "Cancellation Confirmation Page
  (en/cancellation-confirmation-page/)").

Mit Schritt 3 ist die eigentliche
Grundeinrichtung für den Widerrufsablauf
eingerichtet.

### Schritt 4: Widerrufsbutton auf der Webseite platzieren

Der Gesetzgeber schreibt vor, dass die
Widerrufsfunktion von jeder Seite des Webshops
aus ständig und leicht auffindbar erreichbar
sein muss -- vergleichbar mit dem Impressums-
oder Datenschutzlink.

Setzen Sie einen dauerhaft sichtbaren,
hervorgehobenen Link auf die in Schritt 1
angelegte Widerrufs-Einstiegsseite. Der Footer
des Seitenlayouts ist der empfohlene Ort.
Empfohlene Beschriftung: "Vertrag widerrufen"
oder eine gleichbedeutende, eindeutige
Formulierung.

**Wichtig:** Der Link darf nicht ausschließlich
im eingeloggten Kundenbereich platziert werden.
Nur wenn ein Vertragsabschluss ausschließlich
mit Kundenkonto möglich ist, wäre das
zulässig. Eine zusätzliche Platzierung im
Kundenbereich ist dagegen jederzeit
unproblematisch.

Die konkrete Einbindung eines solchen Links kann
mit den bekannten Inhaltselementen von Contao
realisiert werden (Inhaltselement,
Custom-Template oder Navigationsmodul) und wird
in diesem Handbuch nicht gesondert beschrieben.

### Schritt 5: Nachrichtenvorlagen einrichten

#### 5a: Bestellbestätigungs-Vorlage anpassen

Damit der Kunde bereits mit der
Bestellbestätigung die notwendigen Informationen
zum Widerruf erhält, muss die bestehende
Nachrichtenvorlage für die Bestellbestätigung
angepasst werden. Hierzu stehen folgende
Platzhalter zur Verfügung:

- `##orderWithdrawalLink##`: Klickbarer Link zur
  Widerrufsseite (für den HTML-Part der Vorlage).
  Der Linktext wird über eine Sprachvariable
  gesteuert (Standard: "Widerrufs-Link").
- `##orderWithdrawalUrl##`: Reine URL zur
  Widerrufsseite (für den Plain-Text-Part, da
  der HTML-Link dort nicht funktioniert).
- `##orderWithdrawalIdentifier##`:
  Identifikationsnummer als lesbarer Text
  (z. B. "2026000001-G54FQ9"), damit der Kunde
  sie auch manuell eingeben kann.

Beispiel für den HTML-Part:

```
Widerrufsrecht
--------------
Sie haben das Recht, Ihren Vertrag innerhalb der
gesetzlichen Frist zu widerrufen. Nutzen Sie dazu
die Widerrufsfunktion auf unserer Webseite:

##orderWithdrawalLink##

Ihre Identifikationsnummer für den Widerruf:
##orderWithdrawalIdentifier##
```

Im Plain-Text-Part stattdessen
`##orderWithdrawalUrl##` anstelle von
`##orderWithdrawalLink##` verwenden.

**Hinweis:** Die Identifikationsnummer wird erst
bei Bestellungen vergeben, die nach dem Update und
der Anpassung der Nachrichtenvorlage eingehen.
Ältere Bestellungen enthalten keine
Identifikationsnummer und können den
Widerrufsablauf über den Direktlink nicht nutzen.
Für diese Bestellungen steht das
Freitextformular als Fallback zur Verfügung.

#### 5b: Neue Nachrichtenvorlagen anlegen

Es müssen mindestens zwei neue
Nachrichtenvorlagen angelegt werden:

1. **Eingangsbestätigung für den Kunden**:
   Nachrichtenart "als Eingangsbestätigung für
   Widerruf". Diese E-Mail bestätigt dem Kunden
   den Eingang seines Widerrufs.

2. **Widerrufsbenachrichtigung an den Händler**:
   Nachrichtenart "als Widerrufsbenachrichtigung
   an Händler". Diese E-Mail informiert den
   Shopbetreiber über den eingegangenen
   Widerruf.

#### 5c: Empfänger konfigurieren

- **Eingangsbestätigung (Kunde)**: Im Bereich
  "Empfänger" die Option "Kunden-Adresse"
  aktivieren. Bei "Art des
  Kundendaten-Eingabefelds" muss "Widerrufsdaten"
  gewählt sein und bei "Name des
  Kundendaten-Eingabefelds" muss "email"
  eingetragen sein. Dies stellt sicher, dass die
  E-Mail an die im Widerrufsformular angegebene
  Adresse gesendet wird (die von der
  Bestell-E-Mail abweichen kann).

- **Widerrufsbenachrichtigung (Händler)**: Im
  Bereich "Empfänger" die Option "Spezielle
  Adresse" aktivieren und die gewünschte
  Händler-E-Mail-Adresse eintragen -- analog
  zur Konfiguration bei der
  Bestellbenachrichtigung.

#### 5d: Verfügbare Platzhalter

Für die Widerrufs-Nachrichtenvorlagen stehen
folgende Platzhalter zur Verfügung:

**Allgemeine Widerrufsdaten (Kunden- und
Händler-Vorlage):**

| Platzhalter | Beschreibung |
|---|---|
| `##withdrawal::withdrawalId##` | Widerrufs-ID (z. B. "W-00001") |
| `##withdrawal::date##` | Datum des Eingangs |
| `##withdrawal::time##` | Uhrzeit des Eingangs |
| `##withdrawal::name##` | Name der widerrufenden Person |
| `##withdrawal::email##` | E-Mail-Adresse der widerrufenden Person |
| `##withdrawal::freetext##` | Freitextangaben (nur bei nicht zugeordneter Bestellung) |
| `##withdrawal::snapshotOrderNr##` | Bestellnummer |
| `##withdrawal::snapshotOrderDate##` | Bestelldatum |

**Zusätzliche Bestelldaten (primär für die
Händler-Vorlage, damit der Widerruf ohne
Nachschlagen im Backend bearbeitet werden kann):**

| Platzhalter | Beschreibung |
|---|---|
| `##withdrawal::snapshotBillingAddress##` | Rechnungsadresse |
| `##withdrawal::snapshotShippingAddress##` | Versandadresse |
| `##withdrawal::snapshotPaymentMethod##` | Zahlungsoption |
| `##withdrawal::snapshotShippingMethod##` | Versandoption |

#### 5e: Sub-Templates

Zusätzlich zu den Platzhaltern stehen
Sub-Templates zur Verfügung, die die
widerrufenen Positionen bzw. den Freitext und
die Bestelldaten als formatierte Blöcke
ausgeben. Sub-Templates werden als Platzhalter
in die Vorlage eingefügt.

**Für den HTML-Part:**

- `##template::template_mail_withdrawal##`:
  Gibt bei zugeordneter Bestellung eine
  Positionstabelle aus (Produktbezeichnung,
  Artikelnummer, Einzelpreis, Menge). Bei nicht
  zugeordneter Bestellung wird der Freitext
  ausgegeben.
- `##template::template_mail_withdrawal_order_data##`:
  Gibt die Bestelldaten aus (Bestellnummer,
  Bestelldatum, Rechnungsadresse,
  Versandadresse, Zahlungs- und Versandoption).
  Bei nicht zugeordneter Bestellung wird ein
  Fallback-Hinweis angezeigt.

**Für den Plain-Text-Part:**

- `##template::template_mail_withdrawal_plaintext##`:
  Wie oben, aber als strukturierter Text ohne
  HTML-Markup.
- `##template::template_mail_withdrawal_order_data_plaintext##`:
  Wie oben, aber als zeilenbasierte
  Key-Value-Ausgabe ohne HTML-Markup.

Wichtig: Im HTML-Part der Vorlage die
HTML-Varianten verwenden, im Plain-Text-Part die
Plain-Text-Varianten. Die skalaren Platzhalter
(`##withdrawal::...##`) funktionieren in beiden
Parts ohne Einschränkung.

#### 5f: Beispieltexte

**Eingangsbestätigung (Kunde) -- HTML-Part:**

Betreff: Eingangsbestätigung zu Ihrem Widerruf
(##withdrawal::withdrawalId##)

```
Guten Tag ##withdrawal::name##,

wir bestätigen den Eingang Ihres Widerrufs.

Widerrufs-ID: ##withdrawal::withdrawalId##
Eingegangen am: ##withdrawal::date##
um ##withdrawal::time## Uhr

##template::template_mail_withdrawal##

Bitte bewahren Sie diese E-Mail als
Nachweis auf.

Mit freundlichen Grüßen
Ihr Shop-Team
```

**Eingangsbestätigung (Kunde) -- Plain-Text-Part:**

```
Guten Tag ##withdrawal::name##,

wir bestätigen den Eingang Ihres Widerrufs.

Widerrufs-ID: ##withdrawal::withdrawalId##
Eingegangen am: ##withdrawal::date##
um ##withdrawal::time## Uhr

##template::template_mail_withdrawal_plaintext##

Bitte bewahren Sie diese E-Mail als
Nachweis auf.

Mit freundlichen Grüßen
Ihr Shop-Team
```

**Widerrufsbenachrichtigung (Händler) --
HTML-Part:**

Betreff: Neuer Widerruf eingegangen
(##withdrawal::withdrawalId##)

```
Neuer Widerruf eingegangen.

Widerrufs-ID: ##withdrawal::withdrawalId##
Eingegangen am: ##withdrawal::date##
um ##withdrawal::time## Uhr
Name: ##withdrawal::name##
E-Mail: ##withdrawal::email##

##template::template_mail_withdrawal##

##template::template_mail_withdrawal_order_data##
```

**Widerrufsbenachrichtigung (Händler) --
Plain-Text-Part:**

```
Neuer Widerruf eingegangen.

Widerrufs-ID: ##withdrawal::withdrawalId##
Eingegangen am: ##withdrawal::date##
um ##withdrawal::time## Uhr
Name: ##withdrawal::name##
E-Mail: ##withdrawal::email##

##template::template_mail_withdrawal_plaintext##

##template::template_mail_withdrawal_order_data_plaintext##
```

Hinweis: Bei der Händler-E-Mail werden beide
Sub-Templates verwendet (Widerrufsinhalte und
Bestelldaten), damit der Händler den Widerruf
ohne Nachschlagen im Backend bearbeiten kann. Bei
der Kunden-E-Mail genügt das
Widerrufsinhalte-Sub-Template.

### Schritt 6: Identifikationsnummer auf Frontend-Seiten platzieren

Neben der Ausgabe in E-Mail-Nachrichtenvorlagen
(Schritt 5) kann die Identifikationsnummer auch
direkt auf Frontend-Seiten ausgegeben werden,
zum Beispiel auf der Bestellbestätigungsseite
nach dem Checkout. Hierfür steht ein Insert-Tag
zur Verfügung:

`{{shop_order_withdrawal_identifier}}`

**Kontextbindung:** Das Insert-Tag liefert nur
dann einen Wert, wenn ein aktiver Bestellkontext
vorliegt -- also auf Seiten, die einer konkreten
Bestellung zugeordnet sind (zum Beispiel
Bestellbestätigungsseite direkt nach dem
Checkout, Bestelldetailseite im Kundenaccount).
Auf Seiten ohne Bestellkontext gibt das
Insert-Tag einen leeren String aus; es entsteht
kein Fehler.

Diese Kontextbindung ist bewusst gewählt. Ein
parameterbasierter Zugriff könnte die
Identifikationsnummer einer beliebigen
Bestellung ungewollt preisgeben. Ein Insert-Tag
ohne Parameter verhindert das strukturell.

**Typische Einsatzorte:**

- Bestellbestätigungsseite (Artikel oder
  Template nach dem Checkout)

In der Bestellübersicht und den Bestelldetails
des Kundenaccounts wird die Identifikationsnummer
bereits automatisch über einen Direktlink
mitgeführt. Eine manuelle Platzierung ist dort
nicht erforderlich (siehe Abschnitt
"Kundenaccount-Integration").

**Einbindung:** Das Insert-Tag lässt sich an
jeder Stelle einsetzen, an der Contao Insert-Tags
verarbeitet -- zum Beispiel in
Contao-Inhaltselementen oder in entliehenen
Templates. Beispiel:

Ihre Identifikationsnummer für einen Widerruf:
`{{shop_order_withdrawal_identifier}}`

### Schritt 7: Testmodus für Styling

Nach der Einrichtung möchten Sie oder Ihr
Webdesigner das Styling der Widerrufsseiten im
Frontend prüfen und anpassen. Die Einstiegsseite
(Screen A) und das Freitextformular (Screen C)
sind frei zugänglich und können direkt im
Browser aufgerufen werden.

Das Widerrufsformular mit Positionsliste
(Screen B) und die Bestätigungsseite sind im
normalen Betrieb nur
mit einer gültigen Identifikationsnummer bzw.
nach einem echten Absendevorgang erreichbar. Für
diese beiden Seiten steht ein Testmodus zur
Verfügung.

**Testmodus aktivieren:** Kopieren Sie den
Order-Identification-Hash (OIH) einer beliebigen
Bestellung aus dem Backend und hängen Sie ihn als
URL-Parameter `testmode` an die Adresse der
Widerrufsseite an. Beispiel:

`https://www.meinshop.de/vertrag-widerrufen/?testmode=<OIH>`

**Verhalten im Testmodus:** Das
Widerrufsformular wird mit den realen
Bestelldaten der gewählten Bestellung
dargestellt. Alle Felder, Positionen und Buttons
sind sichtbar und interaktiv -- die Darstellung
ist identisch zum regulären Betrieb. Beim Klick
auf "Widerruf bestätigen" wird jedoch **kein**
Widerrufsdatensatz angelegt und **keine** E-Mail
versendet. Stattdessen erfolgt eine Weiterleitung
auf die Bestätigungsseite, die Platzhalter-Daten
anzeigt (z. B. Widerrufs-ID "W-00000").

**Kein visueller Hinweis:** Der Testmodus zeigt
bewusst kein Banner oder Label im Frontend an, da
dies die Darstellung verfälschen und das Styling
beeinflussen würde.

**Kein E-Mail-Testversand:** Im Testmodus werden
keine E-Mails versendet.

### Eingegangene Widerrufe im Backend einsehen

Eingegangene Widerrufe finden Sie im
Contao-Backend unter "Gesendete Nachrichten".
Über den Filter "Nachrichtenart" können Sie
gezielt nach Widerrufsbenachrichtigungen filtern
und so alle eingegangenen Widerrufe auf einen
Blick einsehen.

### Kundenaccount-Integration

In der Bestellübersicht und den Bestelldetails
des Kundenaccounts werden Direktlinks zur
Widerrufsfunktion automatisch angezeigt. Hierfür
ist keine zusätzliche Einrichtung nötig.

**Ausnahme:** Wenn Sie das entsprechende Template
aus dem Merconis-Core angepasst haben (entliehen),
werden Änderungen aus einem Update nicht
automatisch übernommen. Prüfen Sie nach einem
Update, ob das entliehene Template manuell
nachgezogen werden muss.
