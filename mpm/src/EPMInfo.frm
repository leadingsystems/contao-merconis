VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} EPMInfo 
   ClientHeight    =   3015
   ClientLeft      =   45
   ClientTop       =   375
   ClientWidth     =   6525
   OleObjectBlob   =   "EPMInfo.frx":0000
   ShowModal       =   0   'False
   StartUpPosition =   1  'Fenstermitte
End
Attribute VB_Name = "EPMInfo"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Private Sub UserForm_Initialize()
aktuelleFunktionsnummer = crc32HashErmitteln("EPMInfo_UserForm_Initialize")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

'Buttons (mehrsprachig) beschriften
Me.buttonAbbrechen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "CLOSE")

'Text (mehrsprachig) einfügen
Me.LabelInfotext.Caption = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "infotext")

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub

Private Sub buttonAbbrechen_Click()
'Formular schließen

aktuelleFunktionsnummer = crc32HashErmitteln("EPMInfo_buttonAbbrechen_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Unload Me

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub


Private Sub UserForm_Terminate()
aktuelleFunktionsnummer = crc32HashErmitteln("EPMInfo_UserForm_Terminate")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Unload Me

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub
