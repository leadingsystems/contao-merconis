VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "Tabelle3"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = True
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Private Sub Worksheet_SelectionChange(ByVal Target As Range)

aktuelleFunktionsnummer = crc32HashErmitteln("Deletion_Log_Worksheet_SelectionChange")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

aufAllgemeinenProgrammfehlerPrüfen 'Hier zur Sicherheit den EPM neu initialisieren, falls aufgrund eines Programmfehlers die globalen Variablen weg sind.

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub
