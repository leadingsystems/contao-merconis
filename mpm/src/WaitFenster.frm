VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} WaitFenster 
   Caption         =   "Wait"
   ClientHeight    =   2655
   ClientLeft      =   45
   ClientTop       =   375
   ClientWidth     =   6480
   OleObjectBlob   =   "WaitFenster.frx":0000
   StartUpPosition =   1  'Fenstermitte
End
Attribute VB_Name = "WaitFenster"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Private Sub UserForm_Activate()

Me.Repaint

Select Case Range(aktionswählerZelle)
    Case 3
        Range(modusÜbergeordneteArtikelnummerEmittelnZelle) = "semiautomode" 'Modus für die Ermittlung der übergeordneten Artikelnummer setzen
        parentProductCodeManuellNeuSetzen
    Case 4
        antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "parentcodefullautomode02"), 4, mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "parentcodefullautomode01"))
        If antwort = vbYes Then
            Range(modusÜbergeordneteArtikelnummerEmittelnZelle) = "automode" 'Modus für die Ermittlung der übergeordneten Artikelnummer setzen
            formelnNeuSetzen "parentProductCode"
        End If
    Case 5
        ExportCSV
    Case 6
        ExportCSV True
    Case 9
        variantenAusEinblenden
    Case 11
        zeilenAusEinblendenKopfbereich
    Case 12
        spaltenAusEinblendenMerkmaleUndAusprägungen
    Case 13
        spaltenAusEinblendenStaffelpreise
    Case 14
        spaltenAusEinblendenGruppenpreise
    Case 15
        spaltenAusEinblendenFlexContent
End Select
Unload Me
End Sub

Private Sub UserForm_Initialize()
aktuelleFunktionsnummer = crc32HashErmitteln("Wait_UserForm_Initialize")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

'Text (mehrsprachig) einfügen
Me.Caption = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "wait")
Me.LabelInfotext.Caption = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "wait")

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub

Private Sub UserForm_QueryClose(Cancel As Integer, CloseMode As Integer)
'   Schließen über den Button verhindern
    If CloseMode = vbFormControlMenu Then
        Cancel = True
    End If
End Sub
