# Merconis Product Manager (MPM)

## Zweck

Dieses Verzeichnis enthält den versionierten VBA-Quellcode des
Merconis Product Managers sowie die dazugehörige `.xlsm`-Datei und
die Wrapper-Skripte für Export und Build.

## Verzeichnislayout

- `src/`: Exportierter VBA-Quellcode (`.bas`, `.cls`, `.frm`, `.frx`)
- `dist/`: Binäre Arbeitsmappe `MPM.xlsm`
- `build/`: Python-Wrapper für `excel-vba export` und
  `excel-vba import`

## Voraussetzungen

- WSL mit verfügbarem `wslpath`
- Python 3 auf dem WSL-Host
- Excel auf der Windows-Seite
- `vba-edit` auf der Windows-Seite installiert, so dass
  `excel-vba --version` in `powershell.exe` antwortet
- In Excel ist die Option "Zugriff auf das VBA-Projektobjektmodell
  vertrauen" aktiviert
- Die kanonische Ausgangsdatei liegt unter `dist/MPM.xlsm`

## Export-Workflow

1. Die aktuelle `dist/MPM.xlsm` bereitstellen.
2. Export auf dem WSL-Host ausführen:

```bash
python3 mpm/build/export_mpm.py
```

3. Den exportierten Inhalt in `mpm/src/` prüfen und versionieren.

## Build-Workflow

1. Änderungen im VBA-Quellcode unter `mpm/src/` vornehmen.
2. Import auf dem WSL-Host ausführen:

```bash
python3 mpm/build/build_mpm.py
```

3. Die aktualisierte `dist/MPM.xlsm` in Excel prüfen.

## Round-Trip-Workflow

1. Ausgangsdatei nach `dist/MPM.xlsm` legen.
2. Erst-Export mit `python3 mpm/build/export_mpm.py`.
3. Erst-Export committen (Diff-Baseline für Schritt 7).
4. Gewünschte Änderung in `mpm/src/` durchführen.
5. Build mit `python3 mpm/build/build_mpm.py`.
6. Die Mappe in Excel öffnen und manuell prüfen.
7. Erneut exportieren und den Diff kontrollieren.

## Hinweise

- Die Skripte laufen direkt auf dem WSL-Host und nicht in DDEV,
  weil sie `powershell.exe` für die Windows-COM-Automation
  verwenden.
- Das Encoding der exportierten Dateien wird nicht konvertiert.
  Export und Import verwenden bewusst dasselbe Format.
- `mpm/.gitattributes` schützt VBA-Quell- und Binärdateien vor
  unerwünschter Git-Behandlung.
- Build und Export nicht mit `&&` verketten. Nach erfolgreichem
  Import kann der verkettete Prozess hängen. Die Befehle einzeln
  aufrufen.
