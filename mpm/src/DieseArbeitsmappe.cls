VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "DieseArbeitsmappe"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = True
Option Explicit


'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Private Sub Workbook_BeforeClose(Cancel As Boolean)

aktuelleFunktionsnummer = crc32HashErmitteln("Workbook_BeforeClose")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

kontextmenue_loeschen
    
Range(zentralwertMakrohinweisZelle).Font.Color = RGB(255, 0, 0) 'Makro-Hinweis einblenden (damit die beim nächsten Start wieder da sind)

Application.ScreenUpdating = True
Application.EnableEvents = True
Application.CellDragAndDrop = True
Application.Calculation = zentralwertExcelKalkulationweiseBeiStart
Application.EnableAutoComplete = zentralwertExcelAutovervollstaendigungBeiStart

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Private Sub Workbook_Open()
'Beim Start die Standardeinstellungen vornehmen

'MsgBox "öffnen"

aktuelleFunktionsnummer = crc32HashErmitteln("Workbook_Open")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

zentralwertExcelKalkulationweiseBeiStart = Application.Calculation 'Festhalten, wie Excel die Formeln berechnet (automatisch, manuell etc.)
Application.Calculation = xlCalculationManual 'Automatische Formelberechnung durch Excel DEAKTIVIEREN
aktiviereTabellenblatt 1 'Blatt 1 (Produktverwaltung) aktivieren

zentralwertExcelAutovervollstaendigungBeiStart = Application.EnableAutoComplete 'Festhalten, ob bei Excel die Autovervollständigung aktiviert ist
Application.EnableAutoComplete = False 'Automatisches Autovervollständigen DEAKTIVIEREN

zentralwertProgrammstart = True
standardeinstellungen 'Standardeinstellungen aufrufen

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub
