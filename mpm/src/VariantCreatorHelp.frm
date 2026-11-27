VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} VariantCreatorHelp 
   ClientHeight    =   6090
   ClientLeft      =   45
   ClientTop       =   375
   ClientWidth     =   7770
   OleObjectBlob   =   "VariantCreatorHelp.frx":0000
   StartUpPosition =   1  'Fenstermitte
End
Attribute VB_Name = "VariantCreatorHelp"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Private Sub buttonAbbrechen_Click()
'Formular schließen

aktuelleFunktionsnummer = crc32HashErmitteln("variantCreatorHelp_buttonAbbrechen_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Me.Hide

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub

Private Sub UserForm_Initialize()
'Formular initialisieren

aktuelleFunktionsnummer = crc32HashErmitteln("variantCreatorHelp_UserForm_Initialize")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim hilfetext As String
Dim textboxHilfetextUnten As String

hilfetext = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp01")
hilfetext = hilfetext & "##epmMainCode##" & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp02")
hilfetext = hilfetext & "##epmMainName##" & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp03")
hilfetext = hilfetext & "##epmPrefix##" & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp04")
hilfetext = hilfetext & "##epmSuffix##" & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp05")
hilfetext = hilfetext & "##epmSep##" & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp06")
hilfetext = hilfetext & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp07")
hilfetext = hilfetext & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp08")

textboxHilfetextUnten = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "VariantCreatorHelp09")

Me.textboxHilfetext = hilfetext
Me.textboxHilfetextUnten = textboxHilfetextUnten

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub
