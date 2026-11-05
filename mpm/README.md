# Merconis Product Manager (MPM)

## Zweck

Dieses Verzeichnis enthält den versionierten VBA-Quellcode des
Merconis Product Managers sowie die dazugehörige `.xlsm`-Datei und
die Wrapper-Skripte für Export und Build.

## Verzeichnislayout

- `src/`: Exportierter VBA-Quellcode (`.bas`, `.cls`, `.frm`, `.frx`)
- `dist/`: Binäre Arbeitsmappe `MPM.xlsm`
- `build/`: Python-Wrapper `build_mpm.py` und `export_mpm.py` (WSL-Host),
  Windows-Runner `vba_runner.py` (nutzt `vba-edit` als Bibliothek),
  Formularbereinigung `form_cleanup.py` (Export) und gemeinsame Hilfsmodule

## Voraussetzungen

- WSL mit verfügbarem `wslpath`
- Python 3 auf dem WSL-Host
- Excel auf der Windows-Seite
- Windows-Python mit dem Launcher `py`, aufrufbar in `powershell.exe`
- `vba-edit` in genau der Version 0.5.1 für dieses Windows-Python:

```powershell
py -m pip install --user vba-edit==0.5.1
```

- `pywin32` (wird mit `vba-edit` installiert; sonst
  `py -m pip install --user pywin32`)
- In Excel ist die Option "Zugriff auf das VBA-Projektobjektmodell
  vertrauen" aktiviert
- Die kanonische Ausgangsdatei liegt unter `dist/MPM.xlsm`

Der Runner bricht ab, wenn eine andere `vba-edit`-Version installiert ist,
weil er interne Methoden genau dieser Version überschreibt. Ein Update von
`vba-edit` ist eine bewusste Änderung: `REQUIRED_VBA_EDIT_VERSION` in
`build/vba_runner.py` anpassen und Build und Export vollständig erneut
prüfen.

## Ablauf von Build und Export

- Die Wrapper rufen `build/vba_runner.py` über `powershell.exe` mit `py` auf.
- Der Runner startet eine eigene, unsichtbare Excel-Instanz. Eine bereits
  laufende Excel-Instanz und deren Mappen bleiben unberührt.
- Die Mappe wird ohne Events und ohne Dialoge geöffnet. `Workbook_Open`
  läuft nicht; Splashscreen und Backup-Frage erscheinen daher nicht.
- Export: Die Mappe wird ohne Makroausführung geöffnet und nicht
  gespeichert.
- Build: Makros sind erlaubt. Nach dem Import aller Komponenten ruft der
  Runner nur `sFuelleCollection` auf (Übersetzungen für die Formelfunktionen)
  und berechnet alle Formeln neu (`Application.CalculateFull`). Erst danach
  wird gespeichert. Weitere MPM-Startlogik läuft nicht. Schlägt der Aufruf
  oder die Neuberechnung fehl, wird nicht gespeichert. Zeigt dieser Code eine
  Meldung (`MsgBox`), bleibt der Lauf in der unsichtbaren Instanz bis zur
  Zeitüberschreitung stehen; gespeichert wird dann ebenfalls nicht.
- Schutzprüfung beim Build: Der Runner zählt je Blatt die Formelzellen mit
  Fehlerwert direkt nach dem Öffnen (gespeicherte Werte), nach der
  Neuberechnung und nach dem Speichern. Steigt die Zahl in einem Blatt, wird
  nicht gespeichert bzw. der Ausgangsstand aus einer Sicherung im
  Windows-Temp-Verzeichnis wiederhergestellt. Die Prüfung erkennt nur eine
  Verschlechterung gegenüber dem Stand vor dem Build.
- Nach jedem Lauf schließt der Runner die Mappe und beendet seine
  Excel-Instanz, auch im Fehlerfall.
- Ist die eigene Excel-Instanz beschäftigt, lehnt sie Aufrufe ab
  (`RPC_E_CALL_REJECTED` -2147418111, `RPC_E_SERVERCALL_RETRYLATER`
  -2147417846). Ein abgelehnter Aufruf wurde nicht ausgeführt. Der Runner
  wiederholt ihn alle 500 ms, höchstens 30 Sekunden lang, und meldet das mit
  `Hinweis:` auf stderr. Das gilt für die Aufrufe des Runners selbst:
  Einstellungen der Instanz, Öffnen, Schutzprüfung, Übersetzungen,
  Neuberechnung, Speichern, Schließen und Beenden. Import und Export der
  einzelnen Komponenten durch `vba-edit` werden nicht wiederholt. Andere
  Fehler werden nie wiederholt. Lehnt Excel länger ab, endet der Lauf mit
  dem Exit-Code des betroffenen Schritts, z. B. 60 beim Öffnen.
- `.frx`-Dateien werden nicht zwischen `dist/` und `src/` kopiert. Excel
  liest und schreibt sie direkt in `src/`. Sie werden nicht schreibgeschützt
  gesetzt; die Meldung von `vba-edit`, sie seien "exported as read-only",
  trifft daher nicht zu. Nach dem Export entfernt der Wrapper das
  Formular-Rauschen (siehe "Formular-Rauschen beim Export").
- Schlägt beim Build der Import einer Komponente fehl, wird die Mappe nicht
  gespeichert.
- Existiert `dist/~$MPM.xlsm`, starten Build und Export nicht.
- Ein Lauf wird nach 300 Sekunden abgebrochen (änderbar mit
  `--timeout SEKUNDEN`). Der Wrapper beendet dann gezielt den Runner und
  dessen Excel-Instanz und entfernt die temporären Dateien dieses Laufs in
  `src/`. `dist/~$MPM.xlsm` entfernt er nur, wenn danach kein Excel mehr
  läuft; sonst weist er nur darauf hin.
- Der Runner meldet seinen Prozess und den seiner Excel-Instanz mit PID,
  Prozessname und Windows-Startzeit. Als eigene Instanz gilt nur ein
  Excel-Prozess, der vor dem Start noch nicht lief. Gehört die gestartete
  Instanz zu einem Excel-Prozess, der schon vorher lief, bricht der Runner
  mit Exit-Code 60 ab: Er gibt die Instanz frei, ohne sie zu beenden oder
  etwas an ihr einzustellen, und öffnet die Mappe nicht. Bei Zeitüberschreitung
  oder Strg+C beendet der Wrapper einen Prozess nur, wenn alle drei Angaben
  übereinstimmen; sonst gibt er nur einen Hinweis aus. Ein anderes Excel
  wird nie beendet, auch nicht, wenn es eine frei gewordene PID übernommen
  hat.

## Sicherung der Mappe beim Build

- Vor dem Öffnen legt der Runner eine Kopie von `dist/MPM.xlsm` im
  Windows-Temp-Verzeichnis an (`%TEMP%`, z. B.
  `C:\Users\<Name>\AppData\Local\Temp`, in WSL
  `/mnt/c/Users/<Name>/AppData/Local/Temp`).
- Kopiert wird zuerst in eine Zwischendatei
  `MPM-build-sicherung-<Kennung>.unvollstaendig`. Erst wenn Größe und
  SHA-256 mit der Mappe übereinstimmen, benennt der Runner sie in
  `MPM-build-sicherung-<Kennung>.xlsm` um. Nur Dateien mit der Endung
  `.xlsm` sind vollständige Sicherungen.
- Dateien mit der Endung `.unvollstaendig` sind keine Sicherung und dürfen
  nie zur Wiederherstellung verwendet werden. Runner bzw. Wrapper entfernen
  die Zwischendatei ihres Laufs; bleibt sie nach einem zwangsweisen Abbruch
  liegen, weist der Wrapper darauf hin. Sie kann gelöscht werden, sobald
  kein Build mehr läuft.
- Die Sicherung wird nur entfernt, wenn der Stand der Mappe belegt ist:
  Build erfolgreich (Schutzprüfung nach dem Speichern bestanden), Mappe
  aus der Sicherung wiederhergestellt und per Inhaltsvergleich geprüft
  (45), oder Abbruch vor dem Speichern und Mappe inhaltsgleich mit der
  Sicherung (z. B. 31, 41, 46, 45 vor dem Speichern).
- Sie bleibt erhalten nach Zeitüberschreitung, nach Strg+C nach Beginn
  des Speicherns, bei 43, bei 48 und bei unerwarteten Fehlern nach Beginn
  des Speicherns, außerdem, wenn der Runner zwangsweise beendet wurde.
  Runner und Wrapper nennen dann den Windows- und den WSL-Pfad und den
  Befehl zum Wiederherstellen. Als Sicherung nennen sie nur Dateien mit der
  Endung `.xlsm`.
- Wiederherstellen nur aus einer vollständigen Sicherung (Endung `.xlsm`)
  und bei geschlossenem Excel (keine `dist/~$MPM.xlsm`), z. B. auf dem
  WSL-Host:

```bash
cp /mnt/c/Users/<Name>/AppData/Local/Temp/MPM-build-sicherung-<Kennung>.xlsm mpm/dist/MPM.xlsm
```

- `git checkout -- mpm/dist/MPM.xlsm` stellt nur den committeten Stand
  her, nicht ungesicherte Änderungen an der Mappe.
- Ein späterer Lauf räumt verbliebene Sicherungen nicht auf. Sobald die
  Mappe geprüft ist (ggf. nach dem Wiederherstellen), können sie gefahrlos
  gelöscht werden.

## Formular-Rauschen beim Export

Excel schreibt bei jedem Export ein anderes `.frx` (Zeitstempel und zufällige
Füllbytes), auch wenn das Formular unverändert ist. Zusätzlich weichen im
Kopfbereich des `.frm` die Werte `ClientLeft`, `ClientTop`, `ClientWidth` und
`ClientHeight` je nach Rechner um wenige Twips ab. Nach einem erfolgreichen
Export entfernt der Wrapper dieses Rauschen automatisch. Er vergleicht jedes
exportierte Formular nur lesend mit seinem Stand in `HEAD`
(`git show HEAD:<pfad>`):

1. `.frm`: Im Kopfbereich werden `ClientLeft` und `ClientTop` immer auf den
   Wert aus `HEAD` gesetzt; die Position ist wegen `StartUpPosition = 1`
   ohne Wirkung. `ClientWidth` und `ClientHeight` werden nur bei einer
   Abweichung bis 30 Twips zurückgesetzt (`CLIENT_SIZE_TOLERANCE_TWIPS` in
   `build/form_cleanup.py`). Größere Abweichungen gelten als bewusste
   Größenänderung und bleiben. Alle übrigen Zeilen, Encoding (CP1252) und
   Zeilenenden bleiben wie exportiert.
2. `.frx`: Ist das bereinigte `.frm` byte-identisch mit `HEAD`, gilt das
   Formular als unverändert, und das `.frx` wird byte-genau aus `HEAD`
   geschrieben. Sonst bleibt das exportierte `.frx` stehen. Rauschen und echte
   Änderungen lassen sich im `.frx` nicht unterscheiden.
3. Formulare ohne Stand in `HEAD` (neue Formulare) bleiben unverändert.

Je Formular gibt der Export eine Zeile `Formular <Name>: <Ergebnis>` aus:

- `unverändert, Rauschen entfernt`: `.frx` aus `HEAD` übernommen bzw.
  `Client*`-Werte zurückgesetzt; das Formular zeigt keinen Diff.
- `unverändert, kein Rauschen`: Der Export war bereits identisch mit `HEAD`.
- `geändert, Client*-Rauschen entfernt` bzw. `geändert`: Das `.frm` enthält
  eine echte Änderung; das exportierte `.frx` bleibt stehen.
- `beibehalten (--keep-forms)`: siehe unten.
- `neu (nicht in HEAD), unverändert übernommen`

In Klammern folgen die zurückgesetzten `Client*`-Werte (Export -> `HEAD`) und
die beibehaltenen Abweichungen oberhalb der Toleranz.

Liegt `mpm/src` in keinem Git-Repository, ist `git` nicht installiert oder hat
das Repository noch keinen Commit, gibt der Wrapper nur einen Hinweis aus; die
exportierten Dateien bleiben dann unverändert. Git wird nur lesend
aufgerufen (`rev-parse`, `ls-tree`, `show`).

### Option `--keep-forms`

Eine Änderung ausschließlich im Designer (z. B. ein verschobenes
Steuerelement) steht nur im `.frx`. Das `.frm` bliebe unverändert, und der
Export würde das `.frx` aus `HEAD` wiederherstellen und die Änderung in
`mpm/src/` verwerfen. Für solche Formulare die Option angeben:

```bash
python3 mpm/build/export_mpm.py --keep-forms VariantCreator,EPMInfo
```

- Für die genannten Formulare bleibt das exportierte `.frx` stehen. Die
  Bereinigung der `Client*`-Werte im `.frm` gilt weiterhin. Änderungen an
  `ClientWidth` oder `ClientHeight` bis einschließlich 30 Twips werden daher
  immer auf `HEAD` zurückgesetzt, auch mit der Option. Bewusste
  Größenänderungen deshalb um mehr als 30 Twips vornehmen.
- Namen ohne Rücksicht auf Groß-/Kleinschreibung, durch Komma getrennt; die
  Option ist wiederholbar. Ist ein Name kein exportiertes Formular, endet der
  Export mit 71, und es wird nichts bereinigt.
- Ohne die Option ist die Designer-Änderung nur in `mpm/src/` verworfen; die
  Mappe enthält sie weiterhin. Ein erneuter Export mit der Option stellt sie
  wieder her, solange kein Build die Mappe aus `mpm/src/` neu aufgebaut hat.
- Verglichen wird immer mit `HEAD`, nicht mit dem Stand in `mpm/src/` vor dem
  Export. Die Option daher bei jedem Export angeben, bis die Designer-Änderung
  committet ist. Ein Export ohne die Option setzt das `.frx` sonst wieder auf
  `HEAD` zurück und meldet `unverändert, Rauschen entfernt`; folgt dann ein
  Build oder ein Commit, ist die Änderung verloren.

## Export-Workflow

1. Die aktuelle `dist/MPM.xlsm` bereitstellen.
2. Export auf dem WSL-Host ausführen:

```bash
python3 mpm/build/export_mpm.py
```

Wurde ein Formular ausschließlich im Designer geändert, `--keep-forms <Name>`
angeben (siehe "Formular-Rauschen beim Export").

3. Den exportierten Inhalt in `mpm/src/` und die Meldungen je Formular
   prüfen und versionieren.

## Build-Workflow

1. Änderungen im VBA-Quellcode unter `mpm/src/` vornehmen.
2. Import auf dem WSL-Host ausführen:

```bash
python3 mpm/build/build_mpm.py
```

3. Die aktualisierte `dist/MPM.xlsm` frisch in Excel mit aktivierten Makros
   öffnen und prüfen, einschließlich "Debuggen > Kompilieren von
   VBAProject" im VBA-Editor. Der Build führt außer `sFuelleCollection` und
   den Formelfunktionen keinen MPM-Code aus und kompiliert das VBA-Projekt
   nicht vollständig.

## Round-Trip-Workflow

1. Ausgangsdatei nach `dist/MPM.xlsm` legen.
2. Erst-Export mit `python3 mpm/build/export_mpm.py`.
3. Erst-Export committen (Diff-Baseline für Schritt 7).
4. Gewünschte Änderung in `mpm/src/` durchführen.
5. Build mit `python3 mpm/build/build_mpm.py`.
6. Die Mappe frisch in Excel mit aktivierten Makros öffnen und manuell
   prüfen.
7. Erneut exportieren und den Diff kontrollieren. Unveränderte Formulare
   zeigen keinen Diff. Formulare mit einer noch nicht committeten
   Designer-Änderung bei jedem Export mit `--keep-forms <Name>` angeben;
   sonst wird ihr `.frx` auf `HEAD` zurückgesetzt, und die Änderung geht
   verloren (siehe "Option `--keep-forms`").

## Exit-Codes

Jede Fehlerart hat einen festen Exit-Code. Der Wrapper reicht den Exit-Code
des Runners unverändert durch, gibt die Fehlerart auf stderr aus und endet
mit einer Zeile, die Exit-Code und Fehlerart nennt. Die Codes 70 bis 72
stammen aus der Formularbereinigung nach einem erfolgreichen Export.

| Code | Fehlerart | Erkannt durch |
|------|-----------|---------------|
| 0 | Erfolg | - |
| 1 | Unerwarteter Fehler | Runner, Wrapper |
| 2 | Ungültiger Aufruf | Wrapper, Runner |
| 10 | Mappe, Quellverzeichnis oder VBA-Dateien fehlen | Wrapper, Runner |
| 11 | Windows-Pfadkonvertierung (`wslpath -w`) fehlgeschlagen | Wrapper |
| 12 | `powershell.exe` oder Windows-Python (`py`) nicht verfügbar | Wrapper |
| 13 | Runner nicht gestartet (`powershell.exe`, `py` oder `python.exe`) | Wrapper |
| 20 | `vba-edit` fehlt oder hat nicht die geforderte Version | Runner |
| 21 | `pywin32` fehlt | Runner |
| 30 | Sperrdatei `dist/~$MPM.xlsm` vor dem Lauf vorhanden | Wrapper |
| 31 | Mappe nur schreibgeschützt geöffnet | Runner |
| 32 | Sperrdatei `dist/~$MPM.xlsm` nach dem Lauf vorhanden | Wrapper |
| 40 | Zugriff auf das VBA-Projekt verweigert | Runner |
| 41 | Importfehler einzelner Komponenten, nicht gespeichert | Runner |
| 42 | Exportfehler einzelner Komponenten | Runner |
| 43 | Speichern der Mappe fehlgeschlagen | Runner |
| 44 | Eigene Excel-Instanz ließ sich nicht beenden | Runner |
| 45 | Schutzprüfung der Formelzellen fehlgeschlagen, Mappe unverändert | Runner |
| 46 | Übersetzungen oder Neuberechnung fehlgeschlagen, nicht gespeichert | Runner |
| 47 | Sicherung der Mappe vor dem Build fehlgeschlagen | Runner |
| 48 | Wiederherstellung der Mappe fehlgeschlagen | Runner |
| 50 | Zeitüberschreitung | Wrapper |
| 60 | Sonstiger COM- oder Excel-Fehler | Runner |
| 70 | Vergleich der Formulare mit `HEAD` per Git fehlgeschlagen | Wrapper |
| 71 | Unbekanntes Formular in `--keep-forms` | Wrapper |
| 72 | Formular nicht bereinigt (Datei- oder Formatfehler) | Wrapper |
| 130 | Abbruch mit Strg+C | Wrapper, Runner |

- 13: Der Exit-Code stammt nicht vom Runner, sondern z. B. vom `py`-Launcher
  (keine passende Python-Laufzeit) oder von `python.exe` (Runner nicht
  lesbar). Der ursprüngliche Exit-Code steht in der Fehlermeldung;
  `py --version` in `powershell.exe` prüfen.
- 30 und 31: Excel schließen, das `MPM.xlsm` geöffnet hat. Bleibt
  `dist/~$MPM.xlsm` ohne geöffnete Mappe bestehen, die Datei entfernen.
- 40: Trust-Center-Option "Zugriff auf das VBA-Projektobjektmodell
  vertrauen" prüfen.
- 41 und 42: Die betroffenen Komponenten stehen in der Fehlermeldung.
- 43: Der Zustand von `dist/MPM.xlsm` ist nicht nachgewiesen. Die Sicherung
  bleibt erhalten; die Meldung nennt ihren Pfad (siehe "Sicherung der Mappe
  beim Build").
- 45: Fehlerwerte in Formelzellen sind gestiegen oder ließen sich nicht
  zählen. Die Meldung nennt je Blatt die Zahl vorher und nachher und
  Beispielzellen. `dist/MPM.xlsm` hat den Stand vor dem Build.
- 46: Die Meldung nennt den fehlgeschlagenen Aufruf. `dist/MPM.xlsm` hat den
  Stand vor dem Build.
- 47: Die Sicherung im Windows-Temp-Verzeichnis ließ sich nicht anlegen
  oder nicht prüfen; der Build wurde nicht gestartet.
- 48: `dist/MPM.xlsm` enthält den fehlerhaften Stand. Die Meldung nennt den
  Pfad der Sicherung; alternativ `git checkout -- mpm/dist/MPM.xlsm`.
- 50: Beim Build kann ein Dialog im MPM-Code die Ursache sein, z. B. eine
  `MsgBox` in `sFuelleCollection` oder in den Formelfunktionen oder eine
  Kompilierfehlermeldung beim Aufruf. Die unsichtbare Instanz wartet dann
  auf eine Eingabe. Eine verbliebene Sicherung nennt der Wrapper.
- 60: Lautet die Meldung "Excel lieferte keine nachweislich neue Instanz",
  wurde keine Excel-Instanz beendet und die Mappe nicht geöffnet. Laufende
  Excel-Instanzen im Task-Manager prüfen und den Lauf wiederholen. Nennt die
  Meldung -2147418111 oder -2147417846, hat Excel den Aufruf auch nach
  30 Sekunden Wiederholung abgelehnt; den Lauf wiederholen.
- 70 bis 72: Der Export selbst ist abgeschlossen; Formulare mit einer Zeile
  `Formular <Name>: ...` sind fertig behandelt. Ursache beheben und den Export
  wiederholen.
  - 70: Die Meldung nennt den Git-Aufruf. Das genannte und alle folgenden
    Formulare stehen wie exportiert.
  - 71: Die Meldung nennt die exportierten Formulare. Kein Formular wurde
    bereinigt.
  - 72: Die Meldung nennt den Datei- oder Formatfehler, z. B. einen nicht
    erkannten Kopfbereich. Das genannte Formular ist nicht oder nur teilweise
    bereinigt; die übrigen werden weiter bereinigt.
- 130: Strg+C erreicht Wrapper und Runner. Der Runner schließt die Mappe
  und beendet seine Instanz, soweit er dazu noch kommt; der Wrapper beendet
  Runner und Instanz wie bei einer Zeitüberschreitung. Eine verbliebene
  Sicherung nennen Runner bzw. Wrapper.

## Hinweise

- Die Skripte laufen direkt auf dem WSL-Host und nicht in DDEV,
  weil sie `powershell.exe` für die Windows-COM-Automation
  verwenden.
- Das Encoding der exportierten Dateien wird nicht konvertiert.
  Export und Import verwenden bewusst dasselbe Format.
- `mpm/.gitattributes` schützt VBA-Quell- und Binärdateien vor
  unerwünschter Git-Behandlung.
- Build vor Export: Ein Export überschreibt `mpm/src/` mit dem Stand der
  Mappe. Den Export daher nur nach einem erfolgreichen Build (Exit-Code 0)
  starten. Build und Export dürfen einzeln nacheinander oder mit `&&`
  verkettet aufgerufen werden
  (`python3 mpm/build/build_mpm.py && python3 mpm/build/export_mpm.py`).
  Bei `&&` läuft der Export nur, wenn der Build mit Exit-Code 0 endet.
