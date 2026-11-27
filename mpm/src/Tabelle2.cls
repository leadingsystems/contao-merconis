VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "Tabelle2"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = True
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Private Sub Worksheet_Change(ByVal Target As Range)
'Wenn Eingaben/Änderungen getätigt wurden, Aktion durchführen

aktuelleFunktionsnummer = crc32HashErmitteln("Configuration_Worksheet_Change")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
Application.EnableEvents = False
    
aufAllgemeinenProgrammfehlerPrüfen 'Hier zur Sicherheit den EPM neu initialisieren, falls aufgrund eines Programmfehlers die globalen Variablen weg sind.

Dim aktiveSpalte As Long
Dim aktiveZeile As Long
Dim aktiveZelle As String

aktiveSpalte = ActiveCell.Column
aktiveZeile = ActiveCell.Row
aktiveZelle = ActiveCell.Address

aufAllgemeinenProgrammfehlerPrüfen 'Hier zur Sicherheit den EPM neu initialisieren, falls aufgrund eines Programmfehlers die globalen Variablen weg sind.

'Änderungen verbieten, wenn im Kopfbereich
If Target.Row < zentralwertZeileDatenbeginn Then
    On Error Resume Next
    Application.Undo
    On Error GoTo errHandler
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "änderungenVerboten")
    GoTo exitHandler
End If
    
'Wenn die Änderung die Spalten betreffen, die auch in der Produktverwaltung geändert werden, dann diese Änderungen in der Produktverwaltung ebenfalls durchführen
If Target.Column <= zentralwertKonfigurationLetzteSpalteFürÄnderungssynchro Then
    konfigänderungenSyncro zentralwertTabellenblattNameKonfiguration, Target, "Spaltenname"
End If

'Datenüberprüfung neu durchführen, damit die Datenquellen für die Listenfelder neu ermittelt werden
datenüberprüfungNeuSetzen

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Private Sub Worksheet_SelectionChange(ByVal Target As Range)

aktuelleFunktionsnummer = crc32HashErmitteln("Configuration_Worksheet_SelectionChange")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

aufAllgemeinenProgrammfehlerPrüfen 'Hier zur Sicherheit den EPM neu initialisieren, falls aufgrund eines Programmfehlers die globalen Variablen weg sind.

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub
