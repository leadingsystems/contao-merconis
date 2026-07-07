Attribute VB_Name = "Modul2"
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Public Sub standardeinstellungen()
Dim starttimer As Long
starttimer = Timer
On Error GoTo errHandler

aktuelleFunktionsnummer = crc32HashErmitteln("standardeinstellungen")
Application.EnableCancelKey = xlDisabled

Application.ScreenUpdating = False
Application.EnableEvents = False
Application.ScreenUpdating = False
Application.CellDragAndDrop = False
Application.Calculation = xlCalculationManual 'Automatische Formelberechnung durch Excel DEAKTIVIEREN

Dim tabellenblattZähler As Long

'Die Begriffe in Deutsch und Englisch in einem Array festhalten
'mehrsprachigkeitBegriffsdefinitionenInArray
sFuelleCollection           '02.06.2023, TBU, Mehrsprachigkeit über Collection


zentraleVariablen1

'Die Spaltenköpfe in Arrays hinterlegen
mehrsprachigkeitTabellenNamenÄndern
'Sprachbezogene Änderungen durchführen
toDosNachSprachwechsel

'Events nochmals deaktivieren, da toDosNachSprachwechsel diese standardmäßig zur Sicherheit immer aktivieren
Application.EnableEvents = False

zentraleVariablen2


Rem TBU, LIFE, den Startbildschirm wieder einkommentieren
If cModus = "Life" Then
    If zentralwertProgrammstart = True Then
        Splashscreen.Show 'Splashscreen zeigen und warten, bis er geschlossen ist
        warteschleife 1, True
    End If
End If

aktiviereTabellenblatt 1 'Blatt 1 (Produktverwaltung) aktivieren
zentralwertLetzteVerwendeteSpalteProduktverwaltung = letzteVerwendeteSpalteErmitteln(zentralwertTabellenblattNameProduktverwaltung)
zentralwertLetzteVerwendeteSpalteKonfiguration = letzteVerwendeteSpalteErmitteln(zentralwertTabellenblattNameKonfiguration)
zentralwertLetzteVerwendeteSpalteBasisparameter = letzteVerwendeteSpalteErmitteln(zentralwertTabellenblattNameBasisparameter)

'Kopfbereich zu Beginn immer einblenden
Range(kopfbereichAusblendenZelle) = False
zeilenAusEinblendenKopfbereich

Range(zentralwertMakrohinweisZelle).Font.Color = RGB(255, 255, 255) 'Makro-Hinweis ausblenden

'Fenstereinstellungen
On Error Resume Next


Rem TBU, LIFE, während der Entwicklung behindert das Vollbild eher
If cModus = "Life" Then
    Application.WindowState = xlMaximized
    ActiveWindow.WindowState = xlMaximized
End If


aktiviereTabellenblatt zentralwertTabellenblattNameProduktverwaltung
ActiveWindow.Split = False
ActiveWindow.SplitRow = zentralwertZeileÜberschriften
ActiveWindow.FreezePanes = True
Cells(zentralwertZeileDatenbeginn, 1).Select
aktiviereTabellenblatt zentralwertTabellenblattNameKonfiguration
ActiveWindow.Split = False
Cells(zentralwertZeileDatenbeginn, 1).Select
letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
ActiveWindow.SplitRow = zentralwertZeileÜberschriften
ActiveWindow.FreezePanes = True
aktiviereTabellenblatt zentralwertTabellenblattNameBasisparameter
ActiveWindow.Split = False
Cells(zentralwertZeileDatenbeginn, 1).Select
ActiveWindow.SplitRow = zentralwertZeileÜberschriften
ActiveWindow.FreezePanes = True
aktiviereTabellenblatt zentralwertTabellenblattNameLöschprotokoll
ActiveWindow.Split = False
Cells(zentralwertZeileDatenbeginn, 1).Select
ActiveWindow.SplitRow = zentralwertZeileÜberschriften
ActiveWindow.FreezePanes = True
aktiviereTabellenblatt zentralwertTabellenblattNameProduktverwaltung
Cells(zentralwertZeileÜberschriften, zentralwertProduktverwaltungSpalteBezeichnung).Select
Cells(zentralwertZeileDatenbeginn, 1).Select
On Error GoTo errHandler

'Versteckte Zeilen auf allen Tabellenblättern ausblenden
On Error Resume Next 'Bei aktivem Blattschutz sonst Fehler
For tabellenblattZähler = 1 To 4
    Sheets(tabellenblattZähler).Range(zentralwertZeileVersteckteWerte & ":" & zentralwertZeileDatenbanknamen).EntireRow.Hidden = True
Next tabellenblattZähler
On Error GoTo errHandler

Application.GoTo Cells(zentralwertZeileDatenbeginn, 1), Scroll:=True  'Links oben in erste Datenzelle springen

'Gitter ausblenden
ActiveWindow.DisplayGridlines = False

'Alle bedingten Formatierungen, Datenüberprüfungen sowie feste Formeln frisch setzen
bedingteFormatierungenNeuSetzen
datenüberprüfungNeuSetzen
formelnNeuSetzen

'Kontextmenü hinzufügen
kontextmenue_erweitern

'Darstellung der Gruppierung ändern
With Sheets(zentralwertTabellenblattNameProduktverwaltung).Outline
    .AutomaticStyles = False
    .SummaryRow = xlAbove
    .SummaryColumn = xlRight
End With

Application.GoTo Cells(zentralwertZeileDatenbeginn, 1), Scroll:=True  'Links oben in erste Datenzelle springen


'Backup erstellen
'LIFE: diesen Antwort-Block wieder einkommentieren
If cModus = "Life" Then
    antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "backupErstellen01"), 4, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "backupErstellen02"))
    If antwort = vbYes Then
        SaveBackup
    End If
End If

exitHandler:
Application.EnableEvents = True
Application.CalculateFull
Application.ScreenUpdating = True
Application.Calculation = xlCalculationManual 'Automatische Formelberechnung durch Excel DEAKTIVIEREN

Rem TBU, LIFE, den Startbildschirm wieder einkommentieren
If cModus = "Life" Then
    If zentralwertProgrammstart = True Then
        zentralwertProgrammstart = False
        Splashscreen.buttonAbbrechen.Visible = True 'Beenden-Button im Splashscreen bei Programmstart aktivieren
    End If
End If
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
MsgBox "Fehler in Standardeinstellungen", vbOKOnly
GoTo exitHandler

End Sub


Public Sub spaltenDatenbanknamenProduktverwaltungInArray()
'In einem Array merken, in welcher Spalte welcher Datenbankname ist
'Hier der Einfachheit halber für alle Sprachen die Infos festhalten, da so im Handling leichter

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenDatenbanknamenProduktverwaltungInArray")
Application.EnableCancelKey = xlDisabled

Dim aktuellerDatenbankname As String

Dim X As Long

Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
letzteVerwendeteSpalte = letzteVerwendeteSpalteErmitteln(ActiveSheet.Name)

With Sheets(zentralwertTabellenblattNameProduktverwaltung)
    For X = 1 To letzteVerwendeteSpalte
        aktuellerDatenbankname = Trim(.Cells(zentralwertZeileDatenbanknamen, X))
        If aktuellerDatenbankname <> "" Then
            zentralwertProduktverwaltungDatenbanknamenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = aktuellerDatenbankname
            zentralwertProduktverwaltungDatenbanknamenSpaltenNummernArray(aktuellGewählteSprache, X) = X
        End If
    Next X
End With

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function spaltenNummernProduktverwaltungDatenbanknamenZurückgeben(Überschrift)
'Aus dem Array die Spaltennummern für eine bestimmte Spaltenüberschrift zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenNummernProduktverwaltungDatenbanknamenZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
For X = 1 To UBound(zentralwertProduktverwaltungDatenbanknamenSpaltenBezeichnungenArray, 2)
    If zentralwertProduktverwaltungDatenbanknamenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = Überschrift Then
        spaltenNummernProduktverwaltungDatenbanknamenZurückgeben = X
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Public Function spaltenBezeichnungProduktverwaltungDatenbanknamenZurückgeben(spaltennummer)
'Aus dem Array die Bezeichnung für eine bestimmte Spaltennummer zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenBezeichnungProduktverwaltungDatenbanknamenZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To 250
    If zentralwertProduktverwaltungDatenbanknamenSpaltenNummernArray(aktuellGewählteSprache, X) = spaltennummer Then
        spaltenBezeichnungProduktverwaltungDatenbanknamenZurückgeben = zentralwertProduktverwaltungDatenbanknamenSpaltenBezeichnungenArray(aktuellGewählteSprache, X)
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Sub spaltenÜberschriftenProduktverwaltungInArray()
'In einem Array merken, in welcher Spalte welche Spaltenüberschrift ist

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenÜberschriftenProduktverwaltungInArray")
Application.EnableCancelKey = xlDisabled

Dim aktuelleÜberschrift As String

Dim X As Long

Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
letzteVerwendeteSpalte = letzteVerwendeteSpalteErmitteln(ActiveSheet.Name)

With Sheets(zentralwertTabellenblattNameProduktverwaltung)
    For X = 1 To letzteVerwendeteSpalte
        aktuelleÜberschrift = Trim(.Cells(zentralwertZeileÜberschriften, X))
        If aktuelleÜberschrift <> "" Then
            zentralwertProduktverwaltungÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = aktuelleÜberschrift
            zentralwertProduktverwaltungÜberschriftenSpaltenNummernArray(aktuellGewählteSprache, X) = X
        End If
    Next X
End With

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function spaltenNummernProduktverwaltungZurückgeben(Überschrift)
'Aus dem Array die Spaltennummern für eine bestimmte Spaltenüberschrift zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenNummernProduktverwaltungZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To 250
    If zentralwertProduktverwaltungÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = Überschrift Then
        spaltenNummernProduktverwaltungZurückgeben = X
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Public Function spaltenBezeichnungProduktverwaltungZurückgeben(spaltennummer)
'Aus dem Array die Bezeichnung für eine bestimmte Spaltennummer zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenBezeichnungProduktverwaltungZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To 250
    If zentralwertProduktverwaltungÜberschriftenSpaltenNummernArray(aktuellGewählteSprache, X) = spaltennummer Then
        spaltenBezeichnungProduktverwaltungZurückgeben = zentralwertProduktverwaltungÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X)
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Sub spaltenÜberschriftenKonfigurationInArray()
'In einem Array merken, in welcher Spalte welche Spaltenüberschrift ist

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenÜberschriftenKonfigurationInArray")
Application.EnableCancelKey = xlDisabled

Dim aktuelleÜberschrift As String

Dim X As Long

With Sheets(zentralwertTabellenblattNameKonfiguration)
    For X = 1 To 250
        aktuelleÜberschrift = Trim(.Cells(zentralwertZeileÜberschriften, X))
        If aktuelleÜberschrift <> "" Then
            zentralwertKonfigurationÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = aktuelleÜberschrift
            zentralwertKonfigurationÜberschriftenSpaltenNummernArray(aktuellGewählteSprache, X) = X
        End If
    Next X
End With

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function spaltenNummernKonfigurationZurückgeben(Überschrift)
'Aus dem Array die Spaltennummern für eine bestimmte Spaltenüberschrift zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenNummernKonfigurationZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To 250
    If zentralwertKonfigurationÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = Überschrift Then
        spaltenNummernKonfigurationZurückgeben = X
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Public Function spaltenBezeichnungKonfigurationZurückgeben(spaltennummer)
'Aus dem Array die Bezeichnung für eine bestimmte Spaltennummer zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenBezeichnungKonfigurationZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To 250
    If zentralwertKonfigurationÜberschriftenSpaltenNummernArray(aktuellGewählteSprache, X) = spaltennummer Then
        spaltenBezeichnungKonfigurationZurückgeben = zentralwertKonfigurationÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X)
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Sub spaltenÜberschriftenBasisparameterInArray()
'In einem Array merken, in welcher Spalte welche Spaltenüberschrift ist

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenÜberschriftenBasisparameterInArray")
Application.EnableCancelKey = xlDisabled

Dim aktuelleÜberschrift As String

Dim X As Long

With Sheets(zentralwertTabellenblattNameBasisparameter)
    For X = 1 To 250
        aktuelleÜberschrift = Trim(.Cells(zentralwertZeileÜberschriften, X))
        If aktuelleÜberschrift <> "" Then
            zentralwertBasisparameterÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = aktuelleÜberschrift
            zentralwertBasisparameterÜberschriftenSpaltenNummernArray(aktuellGewählteSprache, X) = X
        End If
    Next X
End With

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function spaltenNummernBasisparameterZurückgeben(Überschrift)
'Aus dem Array die Spaltennummern für eine bestimmte Spaltenüberschrift zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenNummernBasisparameterZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To 250
    If zentralwertBasisparameterÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X) = Überschrift Then
        spaltenNummernBasisparameterZurückgeben = X
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Public Function spaltenBezeichnungBasisparameterZurückgeben(spaltennummer)
'Aus dem Array die Bezeichnung für eine bestimmte Spaltennummer zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenBezeichnungBasisparameterZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To 250
    If zentralwertBasisparameterÜberschriftenSpaltenNummernArray(aktuellGewählteSprache, X) = spaltennummer Then
        spaltenBezeichnungBasisparameterZurückgeben = zentralwertBasisparameterÜberschriftenSpaltenBezeichnungenArray(aktuellGewählteSprache, X)
        Exit Function
    End If
Next X

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Function zellpositionZurückgeben(tabellenblatt, inhalt)
'Sucht in einem Tabellenblatt einen Inhalt und gibt die Zellposition zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zellpositionZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim rngBereich      As Range
Dim rngZelle        As Range

Set rngBereich = Sheets(tabellenblatt).UsedRange

For Each rngZelle In rngBereich
    If rngZelle = inhalt Then
        zellpositionZurückgeben = rngZelle.Address
        GoTo exitHandler
    End If
Next

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Function IsArrayEmpty(arr As Variant)
'Prüfen, ob Array leer ist

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("IsArrayEmpty")
Application.EnableCancelKey = xlDisabled


Dim l As Long

On Error Resume Next
l = Len(Join(arr))
If l = 0 Then
    IsArrayEmpty = True
Else
    IsArrayEmpty = False
End If

If Err.Number > 0 Then
    IsArrayEmpty = True
End If
On Error GoTo 0

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Sub kontextmenue_erweitern()
'Im Kontextmenü eigene Einträge einfügen

'Deaktiviert
'Exit Sub

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("kontextmenue_erweitern")
Application.EnableCancelKey = xlDisabled

    'Den Eintrag löschen
    Call kontextmenue_loeschen
    
    Dim Kontext As Object
    
    'Eigene Einträge hinzufügen
        Set Kontext = CommandBars("Row").Controls.Add
        Kontext.BeginGroup = True
        With Kontext
            .Caption = mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben, "Delete row(s)!")
            .OnAction = "'" & ThisWorkbook.Name & "'!" & "zeilenLöschen"
            .FaceId = 122
        End With
        Set Kontext = CommandBars("Row").Controls.Add
        Kontext.BeginGroup = True
        With Kontext
            .Caption = mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben, "Create variants")
            .OnAction = "'" & ThisWorkbook.Name & "'!" & "variantenErstellen"
            .FaceId = 122
        End With
        Set Kontext = CommandBars("Row").Controls.Add
        Kontext.BeginGroup = True
        With Kontext
            .Caption = mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben, "Variants visible")
            .OnAction = "'" & ThisWorkbook.Name & "'!" & "variantenEinblenden"
            .FaceId = 122
        End With
        Set Kontext = CommandBars("Row").Controls.Add
        Kontext.BeginGroup = True
        With Kontext
            .Caption = mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben, "Variants hidden")
            .OnAction = "'" & ThisWorkbook.Name & "'!" & "variantenAusblenden"
            .FaceId = 122
        End With
exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Sub variantenEinblenden()
'Ein- bzw. Ausblenden der Varianten

Dim spalteProduktnummer As Long
Dim spalteProduktTyp As Long
Dim spalteÜbergeordneteArtikelnummer As Long
Dim aktuelleZeile As Long
Dim aktuelleZelleÜbergeordneteArtikelnummer As String
Dim aktuelleZelleProduktnummer As String
Dim aktuelleZelleProdukttyp As String
Dim zuPrüfendeZeile As Long
Dim zelle As String
Dim zeilePlus As Long
Dim zuPrüfendeZelleProdukttyp As String
Dim versteckt As Boolean

versteckt = False

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("variantenEinblenden")
Application.EnableCancelKey = xlDisabled

spalteProduktnummer = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product code"))
spalteProduktTyp = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Type"))
spalteÜbergeordneteArtikelnummer = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Parent product code"))

aktuelleZeile = ActiveCell.Row
aktuelleZelleProduktnummer = Cells(aktuelleZeile, spalteProduktnummer).Address
aktuelleZelleÜbergeordneteArtikelnummer = Cells(aktuelleZeile, spalteÜbergeordneteArtikelnummer).Address
aktuelleZelleProdukttyp = Cells(aktuelleZeile, spalteProduktTyp).Address
  
'Nur, wenn der aktuelle Typ Produkt ist
If Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product") Then
    Do While True
        zeilePlus = zeilePlus + 1
        zuPrüfendeZelleProdukttyp = Cells(aktuelleZeile + zeilePlus, spalteProduktTyp).Address
        'Sobald wieder Typ Produkt oder letzte Zeile, dann raus
        If Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product") Or aktuelleZeile + zeilePlus > letzteVerwendeteZeileErmitteln(ActiveSheet.Name) Then
            Exit Do
        End If
        'Nur, wenn Variante oder Fremdsprachvariante
        If Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") Or Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)") Then
            Rows(Range(zuPrüfendeZelleProdukttyp).Row).EntireRow.Hidden = versteckt
        End If
    Loop
End If


exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub variantenAusEinblenden()
'Varianten ein-/ausblenden

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("variantenAusEinblenden")
Application.EnableCancelKey = xlDisabled

Dim tempZähler

'ExcelVersionsCheck
Select Case zentralwertExcelVersion
Case Is <= 9
    MsgBox "Diese Funktion steht Ihnen in dieser (älteren) Excel-Version nicht zur verfügung!"
Case Else
    If Range(variantenAlleAusblendenZelle) = True Then
        Range(variantenAlleAusblendenZelle) = False
        variantenAusblendenAlle
    Else
        Range(variantenAlleAusblendenZelle) = True
        variantenEinblendenAlle
    End If
End Select

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Sub variantenAusblenden()
'Ein- bzw. Ausblenden der Varianten

Dim spalteProduktnummer As Long
Dim spalteProduktTyp As Long
Dim spalteÜbergeordneteArtikelnummer As Long
Dim aktuelleZeile As Long
Dim aktuelleZelleÜbergeordneteArtikelnummer As String
Dim aktuelleZelleProduktnummer As String
Dim aktuelleZelleProdukttyp As String
Dim zuPrüfendeZeile As Long
Dim zelle As String
Dim zeilePlus As Long
Dim zuPrüfendeZelleProdukttyp As String
Dim versteckt As Boolean

versteckt = True

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("variantenAusblenden")
Application.EnableCancelKey = xlDisabled

spalteProduktnummer = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product code"))
spalteProduktTyp = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Type"))
spalteÜbergeordneteArtikelnummer = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Parent product code"))

aktuelleZeile = ActiveCell.Row
aktuelleZelleProduktnummer = Cells(aktuelleZeile, spalteProduktnummer).Address
aktuelleZelleÜbergeordneteArtikelnummer = Cells(aktuelleZeile, spalteÜbergeordneteArtikelnummer).Address
aktuelleZelleProdukttyp = Cells(aktuelleZeile, spalteProduktTyp).Address
  
'Nur, wenn der aktuelle Typ Produkt ist
If Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product") Then
    Do While True
        zeilePlus = zeilePlus + 1
        zuPrüfendeZelleProdukttyp = Cells(aktuelleZeile + zeilePlus, spalteProduktTyp).Address
        'Sobald wieder Typ Produkt oder letzte Zeile, dann raus
        If Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product") Or aktuelleZeile + zeilePlus > letzteVerwendeteZeileErmitteln(ActiveSheet.Name) Then
            Exit Do
        End If
        'Nur, wenn Variante oder Fremdsprachvariante
        If Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") Or Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)") Then
            Rows(Range(zuPrüfendeZelleProdukttyp).Row).EntireRow.Hidden = versteckt
        End If
    Loop
End If


exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Sub variantenEinblendenAlle()
'Ein- bzw. Ausblenden der Varianten

waitFensterRepaint

Dim spalteProduktnummer As Long
Dim spalteProduktTyp As Long

Dim letzteZeile As Long
Dim zuPrüfendeZelleProdukttyp As String
Dim versteckt As Boolean

versteckt = False

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("variantenEinblendenAlle")
Application.EnableCancelKey = xlDisabled
Application.ScreenUpdating = False

spalteProduktTyp = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Type"))
  
letzteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
Dim X As Long
For X = zentralwertZeileDatenbeginn To letzteZeile
    'Nur, wenn Variante oder Fremdsprachvariante
    zuPrüfendeZelleProdukttyp = Cells(X, spalteProduktTyp).Address
    If Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") Or Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)") Then
        Rows(X).EntireRow.Hidden = versteckt
    End If
Next X
  

exitHandler:
Application.ScreenUpdating = True
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Sub variantenAusblendenAlle()
'Ein- bzw. Ausblenden der Varianten

Dim spalteProduktnummer As Long
Dim spalteProduktTyp As Long

Dim letzteZeile As Long
Dim zuPrüfendeZelleProdukttyp As String
Dim versteckt As Boolean

waitFensterRepaint

versteckt = True

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("variantenAusblendenAlle")
Application.EnableCancelKey = xlDisabled
Application.ScreenUpdating = False

spalteProduktTyp = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Type"))
  
letzteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
Dim X As Long
For X = zentralwertZeileDatenbeginn To letzteZeile
    'Nur, wenn Variante oder Fremdsprachvariante
    zuPrüfendeZelleProdukttyp = Cells(X, spalteProduktTyp).Address
    If Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") Or Range(zuPrüfendeZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)") Then
        Rows(X).EntireRow.Hidden = versteckt
    End If
Next X

exitHandler:
Application.ScreenUpdating = True
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Sub kontextmenue_loeschen()
'Einträge in Kontextmenüs löschen

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("kontextmenue_loeschen")
Application.EnableCancelKey = xlDisabled

Dim X As Long
Dim Y As Long

'Eintrag löschen
On Error Resume Next
For X = 1 To 5 'Zur Sicherheit mehrmals aufrufen, um auch wirklich alle Menüeinträge/Menüleichen zu entfernen
    For Y = 1 To 10 'Natürlich auch sämtliche Sprachvarianten entfernen
        CommandBars("Row").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Delete row(s)!")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Delete row(s)!")).Delete
        CommandBars("Row").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Create variants")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Create variants")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "ProductManagement")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Configuration")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Basic parameters")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Deletion log")).Delete
        CommandBars("Row").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Variants visible")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Variants visible")).Delete
        CommandBars("Row").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Variants hidden")).Delete
        CommandBars("Cell").Controls(mehrsprachigkeitBegriffsrückgabe(Y, "Variants hidden")).Delete
        CommandBars("Cell").Controls("").Delete
    Next Y
Next X

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function letzteVerwendeteZeileErmitteln(tabellenblatt)
'Letzte Zeile ermitteln

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("letzteVerwendeteZeileErmitteln")
Application.EnableCancelKey = xlDisabled

letzteVerwendeteZeileErmitteln = Sheets(tabellenblatt).UsedRange.Rows.Count

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler


End Function

Public Function letzteVerwendeteSpalteErmitteln(tabellenblatt)
'Letzte Spalte ermitteln

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("letzteVerwendeteSpalteErmitteln")
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = Application.EnableEvents
Application.EnableEvents = False

letzteVerwendeteSpalteErmitteln = Sheets(tabellenblatt).UsedRange.Columns.Count

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Function xZuWert(zelle)
'Gibt 1 zurück, wenn "x"

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("xZuWert")
Application.EnableCancelKey = xlDisabled

If LCase(Range(zelle)) = "x" Then
    xZuWert = 1
Else
    xZuWert = 0
End If

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Function entferneSuffix(xName As String) As String
'Entfernt das Suffix
On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("entferneSuffix")
Application.EnableCancelKey = xlDisabled

Dim xLen As Long, xStep As Long
Dim i As Long

xLen = Len(xName)

For i = xLen To 1 Step -1
    If Mid(xName, i, 1) = "." Then Exit For
Next i
entferneSuffix = Left(xName, i - 1)
    
exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Function

Function gebeSuffixZurück(xName As String) As String
'Entfernt das Suffix
On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("gebeSuffixZurück")
Application.EnableCancelKey = xlDisabled

Dim xLen As Long, xStep As Long
Dim i As Long

xLen = Len(xName)

For i = xLen To 1 Step -1
    If Mid(xName, i, 1) = "." Then Exit For
Next i
gebeSuffixZurück = Right(xName, xLen - i + 1)
    
exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Function

Public Sub aktiviereTabellenblatt(tabellenblatt)
'Aktiviert ein Tabellenblatt (Nummer oder Bezeichnung kann übergeben werden)
On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("aktiviereTabellenblatt")
Application.EnableCancelKey = xlDisabled

Sheets(tabellenblatt).Activate
    
exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub

Public Function aktuellAktiviertesTabellenblatt()
'Gibt den Index des gerade aktiven Tabellenblattes zurück

aktuellAktiviertesTabellenblatt = 1

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("aktiviereTabellenblatt")
Application.EnableCancelKey = xlDisabled

aktuellAktiviertesTabellenblatt = Application.ActiveSheet.Index
    
exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Function

Public Function tabellenblattNameZurückgeben(tabellenblattName)
'Gibt die Blatt-Nummer zu einem Tabellenblatt zurück
On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("tabellenblattNameZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To Sheets.Count
    If LCase(Sheets(X).Name) = LCase(tabellenblattName) Then
        tabellenblattNameZurückgeben = Sheets(X).Name
        GoTo exitHandler
    End If
Next X
    
exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Function


Public Function tabellenblattNummerZurückgeben(tabellenblattName)
'Gibt die Blatt-Nummer zu einem Tabellenblatt zurück
On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("tabellenblattNummerZurückgeben")
Application.EnableCancelKey = xlDisabled

Dim X As Long

For X = 1 To Sheets.Count
    If LCase(Sheets(X).Name) = LCase(tabellenblattName) Then
        tabellenblattNummerZurückgeben = X
        GoTo exitHandler
    End If
Next X
    
exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Function



Function zeileMarkiert() As Boolean
'Prüft ob Zeile(n) markiert wurde(n)

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zeileMarkiert")
Application.EnableCancelKey = xlDisabled

With Selection
    zeileMarkiert = (.Row & ":" & .Row + .Rows.Count - 1) = .Address(rowAbsolute:=False)
End With

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Function spalteMarkiert() As Boolean
'Prüft ob Spalte(n) markiert wurde(n)

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spalteMarkiert")
Application.EnableCancelKey = xlDisabled

With Selection
    spalteMarkiert = (.Column & ":" & .Column + .Columns.Count - 1) = .Address(columnAbsolute:=False)
End With

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Function aktuelleZelleLeer()
'Prüft ob Zeile markiert wurde

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("aktuelleZelleLeer")
Application.EnableCancelKey = xlDisabled

With Selection
    aktuelleZelleLeer = IsEmpty(ActiveCell.Value)
End With

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Sub seitenwähler()
'Entsprechende Tabellenblatt aktivieren

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("seitenwähler")
Application.EnableCancelKey = xlDisabled

Dim tabellenblatt As Long

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = Application.EnableEvents
Application.EnableEvents = False
tabellenblatt = Sheets(zentralwertTabellenblattNameProduktverwaltung).Range(seitenwählerZelle)
Sheets(zentralwertTabellenblattNameProduktverwaltung).Range(seitenwählerZelle) = 0 'Zelle mit Seitenwähler leeren (vorher Events deaktivieren, da sonst wieder Worksheet_Change aktiv wird)
aktiviereTabellenblatt tabellenblatt
Application.EnableEvents = alterEnableEventsStatus

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Function aktuellerEnableEventsStatus()
'Arbeiten nach einem Sprachwechsel

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("aktuellerEnableEventsStatus")
On Error Resume Next
Application.EnableCancelKey = xlDisabled
On Error GoTo errHandler

aktuellerEnableEventsStatus = Application.EnableEvents

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Function aktuellerScreenUpdatingStatus()
'Arbeiten nach einem Sprachwechsel

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("aktuellerScreenUpdatingStatus")
Application.EnableCancelKey = xlDisabled

aktuellerScreenUpdatingStatus = Application.ScreenUpdating

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Sub spaltenAusEinblenden(ausEin, spaltentyp, tabellenblattName)
'Bestimmte Spaltentypen aublenden
  Dim lSpalte&

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenAusEinblenden")
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

letzteVerwendeteSpalte = letzteVerwendeteSpalteErmitteln(ActiveSheet.Name)

Dim X As Long

For X = 1 To letzteVerwendeteSpalte
    Select Case spaltentyp
    Case "merkmale"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmaleUndAusprägungenTrenner).Hidden = ausEin
        
        Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
        For lSpalte = 1 To UBound(zentralwertProduktverwaltungsSpaltenMerkmal)
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungsSpaltenMerkmal(lSpalte)).Hidden = ausEin
        Next
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal1).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal2).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal3).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal4).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal5).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal6).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal7).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal8).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal9).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal10).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal11).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal12).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal13).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal14).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal15).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal16).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal17).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal18).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal19).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteMerkmal20).Hidden = ausEin
        GoTo exitHandler
        
    Case "ausprägungen"
    
        Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
        For lSpalte = 1 To UBound(zentralwertProduktverwaltungsSpaltenAusprägung)
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungsSpaltenAusprägung(lSpalte)).Hidden = ausEin
        Next
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung1).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung2).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung3).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung4).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung5).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung6).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung7).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung8).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung9).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung10).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung11).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung12).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung13).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung14).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung15).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung16).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung17).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung18).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung19).Hidden = ausEin
'        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAusprägung20).Hidden = ausEin
        GoTo exitHandler
        
    Case "staffelpreise"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisTrenner).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwenden).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArt).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethode).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennen).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwort).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationen).Hidden = ausEin
        
        If ausEin = True Or (ausEin = False And Range(gruppenpreiseAusblendenZelle) >= 1) Then
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe1).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe1).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe1).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe1).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe1).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe1).Hidden = ausEin
        End If
        
        If ausEin = True Or ausEin = False And Range(gruppenpreiseAusblendenZelle) >= 2 Then
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe2).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe2).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe2).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe2).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe2).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe2).Hidden = ausEin
        End If
        
        If ausEin = True Or ausEin = False And Range(gruppenpreiseAusblendenZelle) >= 3 Then
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe3).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe3).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe3).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe3).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe3).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe3).Hidden = ausEin
        End If
        
        If ausEin = True Or ausEin = False And Range(gruppenpreiseAusblendenZelle) >= 4 Then
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe4).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe4).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe4).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe4).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe4).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe4).Hidden = ausEin
        End If
        
        If ausEin = True Or ausEin = False And Range(gruppenpreiseAusblendenZelle) >= 5 Then
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe5).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe5).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe5).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe5).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe5).Hidden = ausEin
            Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe5).Hidden = ausEin
        End If
        
        GoTo exitHandler
        
    Case "gruppenpreise1"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteTrennerGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAnwendenGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisTypGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisTypGruppe1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisVerwendenGruppe1).Hidden = ausEin
        GoTo exitHandler
        
    Case "gruppenpreise2"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteTrennerGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAnwendenGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisTypGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisTypGruppe2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisVerwendenGruppe2).Hidden = ausEin
        GoTo exitHandler
        
    Case "gruppenpreise3"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteTrennerGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAnwendenGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisTypGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisTypGruppe3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisVerwendenGruppe3).Hidden = ausEin
        GoTo exitHandler
        
    Case "gruppenpreise4"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteTrennerGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAnwendenGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisTypGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisTypGruppe4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisVerwendenGruppe4).Hidden = ausEin
        GoTo exitHandler
        
    Case "gruppenpreise5"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteTrennerGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAnwendenGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpaltePreisTypGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisAnwendenGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennenGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwortGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisTypGruppe5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteAlterPreisVerwendenGruppe5).Hidden = ausEin
        GoTo exitHandler
        
    Case "flexContent"
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContentTrenner).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent1).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent2).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent3).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent4).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent5).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent6).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent7).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent8).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent9).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent10).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContentSprachunabhängigTrenner).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent1Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent2Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent3Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent4Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent5Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent6Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent7Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent8Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent9Sprachunabhängig).Hidden = ausEin
        Sheets(tabellenblattName).Columns(zentralwertProduktverwaltungSpalteFlexContent10Sprachunabhängig).Hidden = ausEin
        GoTo exitHandler
        
    End Select
Next X

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Sub spaltenAusEinblendenMerkmaleUndAusprägungen()
'Spalten der Merkmale und Ausprägungen ausblenden

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenAusEinblendenMerkmaleUndAusprägungen")
Application.EnableCancelKey = xlDisabled

'ExcelVersionsCheck
Select Case zentralwertExcelVersion
Case Is <= 9
    MsgBox "Diese Funktion steht Ihnen in dieser (älteren) Excel-Version nicht zur verfügung!"
Case Else
    If Range(merkmaleAusprägungenAusblendenZelle) = True Then
        Range(merkmaleAusprägungenAusblendenZelle) = False
    Else
        Range(merkmaleAusprägungenAusblendenZelle) = True
    End If
    spaltenAusEinblenden Range(merkmaleAusprägungenAusblendenZelle), "merkmale", zentralwertTabellenblattNameProduktverwaltung
    spaltenAusEinblenden Range(merkmaleAusprägungenAusblendenZelle), "ausprägungen", zentralwertTabellenblattNameProduktverwaltung
End Select


exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Sub spaltenAusEinblendenStaffelpreise()
'Spalten der Staffelpreise ausblenden

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenAusEinblendenStaffelpreise")
Application.EnableCancelKey = xlDisabled

'ExcelVersionsCheck
Select Case zentralwertExcelVersion
Case Is <= 9
    MsgBox "Diese Funktion steht Ihnen in dieser (älteren) Excel-Version nicht zur verfügung!"
Case Else
    If Range(staffelpreiseAusblendenZelle) = True Then
        Range(staffelpreiseAusblendenZelle) = False
    Else
        Range(staffelpreiseAusblendenZelle) = True
    End If
    spaltenAusEinblenden Range(staffelpreiseAusblendenZelle), "staffelpreise", zentralwertTabellenblattNameProduktverwaltung
End Select


exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub spaltenAusEinblendenGruppenpreise()
'Spalten der Gruppenpreise ein-/ausblenden

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenAusEinblendenGruppenpreise")
Application.EnableCancelKey = xlDisabled

Dim tempZähler

'ExcelVersionsCheck
Select Case zentralwertExcelVersion
Case Is <= 9
    MsgBox "Diese Funktion steht Ihnen in dieser (älteren) Excel-Version nicht zur verfügung!"
Case Else
    If Range(gruppenpreiseAusblendenZelle) < 5 Then
        Range(gruppenpreiseAusblendenZelle) = Range(gruppenpreiseAusblendenZelle) + 1
    Else
        Range(gruppenpreiseAusblendenZelle) = 0
    End If
End Select

If Range(gruppenpreiseAusblendenZelle) > 0 Then
    For tempZähler = 1 To Range(gruppenpreiseAusblendenZelle)
        spaltenAusEinblenden False, "gruppenpreise" & tempZähler, zentralwertTabellenblattNameProduktverwaltung
    Next
End If
If Range(gruppenpreiseAusblendenZelle) < 5 Then
    For tempZähler = Range(gruppenpreiseAusblendenZelle) + 1 To 5
        spaltenAusEinblenden True, "gruppenpreise" & tempZähler, zentralwertTabellenblattNameProduktverwaltung
    Next
End If

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub spaltenAusEinblendenFlexContent()
'Spalten der Staffelpreise ausblenden

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("spaltenAusEinblendenFlexContent")
Application.EnableCancelKey = xlDisabled

'ExcelVersionsCheck
Select Case zentralwertExcelVersion
Case Is <= 9
    MsgBox "Diese Funktion steht Ihnen in dieser (älteren) Excel-Version nicht zur verfügung!"
Case Else
    If Range(flexContentAusblendenZelle) = True Then
        Range(flexContentAusblendenZelle) = False
    Else
        Range(flexContentAusblendenZelle) = True
    End If
    spaltenAusEinblenden Range(flexContentAusblendenZelle), "flexContent", zentralwertTabellenblattNameProduktverwaltung
End Select


exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Sub kategorieAuswählenEinAus()
'Kategorie-Auswahl an-/abschalten

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("kategorieAuswählenEinAus")
Application.EnableCancelKey = xlDisabled

'ExcelVersionsCheck
Select Case zentralwertExcelVersion
Case Is <= 9
    MsgBox "Diese Funktion steht Ihnen in dieser (älteren) Excel-Version nicht zur verfügung!"
Case Else
    If Range(kategorienAuswaehlenZelle) = True Then
        Range(kategorienAuswaehlenZelle) = False
    Else
        Range(kategorienAuswaehlenZelle) = True
    End If
    datenüberprüfungNeuSetzenKategorie
End Select


exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub zeilenAusEinblendenKopfbereich()
'Kopfbereich kleiner oder größer machen (ein-/ausblenden)

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zeilenAusEinblendenKopfbereich")
Application.EnableCancelKey = xlDisabled

Dim alterScreenUpdateingStatus As Boolean
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.ScreenUpdating = False

Dim höheRow1 As Variant
Dim höheRow2 As Variant
Dim X As Long
Dim Y As Long
Dim logoSichtbarkeit As Boolean

'ExcelVersionsCheck
Select Case zentralwertExcelVersion
Case Is <= 9
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "funktionNichtUnterstützt")
Case Else
    If Range(kopfbereichAusblendenZelle) = True Then 'verkleinern
        höheRow1 = 0.1
        höheRow2 = 0.1
        logoSichtbarkeit = False
        Range(kopfbereichAusblendenZelle) = False
        
    Else 'vergrößern
        höheRow1 = 111
        höheRow2 = 37.5
        logoSichtbarkeit = True
        Range(kopfbereichAusblendenZelle) = True
    End If
    For X = 1 To Sheets.Count
        For Y = 1 To zentralwertZeileKopfbereichende
            Sheets(X).Pictures("grafikLogo").Visible = logoSichtbarkeit
            Sheets(X).Rows(1).RowHeight = höheRow1
            Sheets(X).Rows(2).RowHeight = höheRow2
        Next Y
    Next X

End Select


exitHandler:
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Sub aufAllgemeinenProgrammfehlerPrüfen()
'Falls aufgrund eines Fehler die globalen Variablen weg sind, dann Hinweis bringen und eine Neuinitialisierung ausführen

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("aufAllgemeinenProgrammfehlerPrüfen")
Application.EnableCancelKey = xlDisabled

If zentralwertEPMVersion = "" Or zentralwertProduktverwaltungSpalteTyp = 0 Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "fehlerAufgetretenNeuInitialisierung")
    standardeinstellungen
End If

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub zeilenhöhenSetzen()
'Setzt überall die Zeilenhöhen neu

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zeilenhöhenSetzen")
Application.EnableCancelKey = xlDisabled

Dim alterScreenUpdateingStatus As Boolean
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.ScreenUpdating = False

Dim autoZeilenHöhe As Variant

autoZeilenHöhe = Sheets(zentralwertTabellenblattNameKonfiguration).Cells(zentralwertZeileDatenbeginn, spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Auto row height")))

Rows(zentralwertZeileDatenbeginn & ":" & letzteVerwendeteZeileErmitteln(ActiveSheet.Name)).Select
Selection.RowHeight = autoZeilenHöhe
Range(letzteAktiveZelle).Select

exitHandler:
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Function substringHäufigkeit(OrigString As String, Chars As String, Optional CaseSensitive As Boolean = False) As Long
'Prüft, wie häufig ein Substring in einem String vorkommt und gibt die Zahl zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("substringHäufigkeit")
Application.EnableCancelKey = xlDisabled

Dim lLen As Long
Dim lCharLen As Long
Dim lAns As Long
Dim sInput As String
Dim sChar As String
Dim lCtr As Long
Dim lEndOfLoop As Long
Dim bytCompareType As Byte

sInput = OrigString
If sInput = "" Then Exit Function
lLen = Len(sInput)
lCharLen = Len(Chars)
lEndOfLoop = (lLen - lCharLen) + 1
bytCompareType = IIf(CaseSensitive, vbBinaryCompare, _
   vbTextCompare)

    For lCtr = 1 To lEndOfLoop
        sChar = Mid(sInput, lCtr, lCharLen)
        If StrComp(sChar, Chars, bytCompareType) = 0 Then _
            lAns = lAns + 1
    Next

substringHäufigkeit = lAns

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Public Function inhaltZuFormelUmwandeln(begriff)
'Gibt die zu einem Feld gehörende Formel zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("inhaltZuFormelUmwandeln")
Application.EnableCancelKey = xlDisabled

        Select Case begriff
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Product" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Variant" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product (foreign language)")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Product (foreign language)" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Variant (foreign language)" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Percent" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Fixed price" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Independent price" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Percent" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Fixed price" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Independent price" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Percent" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed weight")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Fixed weight" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent weight")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Independent weight" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed scale price")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Fixed scale price" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percentaged adjustment")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Percentaged adjustment" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Adjustment with a fixed value")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Adjustment with a fixed value" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products, variants and configurations")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Separated by products, variants and configurations" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products and variants")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Separated by products and variants" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Separated by products" & zeichen_gf & ")"
        Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Summarized by scale price keyword")
            inhaltZuFormelUmwandeln = "=mehrsprachigkeitBegriffsrückgabe(" & "gewählteSpracheZurückgeben()" & ", " & zeichen_gf & "Summarized by scale price keyword" & zeichen_gf & ")"
        
        Case Else
        inhaltZuFormelUmwandeln = ""
        End Select


exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Function dateidialogDateinamen(bisherigerFeldinhalt, multiselect, nurDateiname)
'Bei Doppelklick Dateiauswahldialog öffnen und ausgewählte Dateinamen zurückgeben

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("dateidialogDateinamen")
Application.EnableCancelKey = xlDisabled
    
Dim varDatei As Variant
Dim wurdenDateienAusgewählt As Boolean
Dim komma As String
Dim iCounter As Long
Dim dateiAuswahl As String
    
    varDatei = Application.GetOpenFilename(, , , , multiselect)
    If IsArrayEmpty(varDatei) = False Then
        wurdenDateienAusgewählt = True
    Else
        If varDatei <> "" Then
            wurdenDateienAusgewählt = True
        Else
            wurdenDateienAusgewählt = False
        End If
    End If

    If Trim(bisherigerFeldinhalt) <> "" Then
        komma = ","
    Else
        komma = ""
    End If
    
    If wurdenDateienAusgewählt = True Then
        If multiselect = True Then
            For iCounter = 1 To UBound(varDatei)
                dateiAuswahl = dateiAuswahl & Dir(varDatei(iCounter)) & ","
            Next iCounter
            dateiAuswahl = komma & dateiAuswahl
            If Right(dateiAuswahl, 1) = "," Then dateiAuswahl = Left(dateiAuswahl, Len(dateiAuswahl) - 1)
            dateidialogDateinamen = dateiAuswahl
        Else
            If nurDateiname <> True Then
                dateidialogDateinamen = varDatei
            Else
                dateidialogDateinamen = dateinameAusPfad(varDatei)
            End If
        End If
    End If

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
dateidialogDateinamen = ""
GoTo exitHandler

End Function

Public Function dateinameAusPfad(pfad)
'Gibt den Dateinamen aus einem Dateipfad zurück
On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("dateinameAusPfad")
Application.EnableCancelKey = xlDisabled

dateinameAusPfad = Right(pfad, Len(pfad) - InStrRev(pfad, "\"))

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
dateinameAusPfad = ""
GoTo exitHandler
End Function


Public Function istProduktzeileLeer(zeile)
'Prüft,ob im Produkt-Tabellenblatt die angegebenen Zeile leer ist (kein Produkttyp ausgewählt wurde)

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("istProduktzeileLeer")
Application.EnableCancelKey = xlDisabled

If Sheets(zentralwertTabellenblattNameProduktverwaltung).Cells(zeile, zentralwertProduktverwaltungSpalteTyp) = "" Then
    istProduktzeileLeer = True
Else
    istProduktzeileLeer = False
End If

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
istProduktzeileLeer = False
GoTo exitHandler

End Function

Public Function warteschleife(Dauer, event_ja_nein)
'Wartet die angegebene Dauer in Sekunden

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("warteschleife")
Application.EnableCancelKey = xlDisabled

Dim zeit_start As Variant
Dim zeit_jetzt As Variant

zeit_start = Round(Timer, 2)
zeit_jetzt = Timer
Do While (Round(zeit_jetzt, 2) - zeit_start) < Dauer
    zeit_jetzt = Timer
    If event_ja_nein = True Then DoEvents
Loop

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly

GoTo exitHandler
End Function


Public Sub zeigeEPMInfo()
' Zeige Programminfos

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zeigeEPMInfo")
Application.EnableCancelKey = xlDisabled

'EPMInfo-Formular anzeigen
EPMInfo.Show

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function maxMöglicheZeilenanzahl()
'Gibt maximal mögliche Zeilenanzahl dieser Excelversion zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("maxMöglicheZeilenanzahl")
Application.EnableCancelKey = xlDisabled

If zentralwertExcelVersion >= zentralwertAltesExcelVorVersion Then
    maxMöglicheZeilenanzahl = Rows.CountLarge
Else
    maxMöglicheZeilenanzahl = Rows.Count 'Bei den alten Excel-Versionen gibt es kein CountLarge
End If


exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
maxMöglicheZeilenanzahl = 65000 'Bei Fehler das zurückgeben
GoTo exitHandler

End Function

Public Function maxMöglicheSpaltenanzahl()
'Gibt maximal mögliche Zeilenanzahl dieser Excelversion zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("maxMöglicheSpaltenanzahl")
Application.EnableCancelKey = xlDisabled

maxMöglicheSpaltenanzahl = Columns.CountLarge

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
maxMöglicheSpaltenanzahl = 1000 'Bei Fehler das zurückgeben
GoTo exitHandler

End Function

Public Function formelLokal(formel)
'Gibt die lokale Formelversion zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("formelLokal")
Application.EnableCancelKey = xlDisabled

Range(formelLokalTemporärUmwandelnZelle).Formula = formel
formelLokal = Range(formelLokalTemporärUmwandelnZelle).FormulaLocal
Range(formelLokalTemporärUmwandelnZelle) = ""

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
formelLokal = formel 'Bei Fehler das zurückgeben
GoTo exitHandler

End Function


Public Function VariantenFremdsprachenProduktZugehoerigkeit()
Exit Function
'Wenn ein Produkttyp für Fremdsprache ausgewählt wird, dann wird automatisch die übergeordnete Artikel-Nummer ermittelt und eingetragen

Dim spalteProduktnummer As Long
Dim spalteProduktTyp As Long
Dim spalteÜbergeordneteArtikelnummer As Long
Dim aktuelleZeile As Long
Dim aktuelleZelleÜbergeordneteArtikelnummer As String
Dim aktuelleZelleProduktnummer As String
Dim aktuelleZelleProdukttyp As String
Dim zuPrüfendeZeile As Long
Dim zelle As String

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("VariantenFremdsprachenProduktZugehoerigkeit")

spalteProduktnummer = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product code"))
spalteProduktTyp = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Type"))
spalteÜbergeordneteArtikelnummer = spaltenNummernProduktverwaltungZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Parent product code"))

aktuelleZeile = ActiveCell.Row
aktuelleZelleProduktnummer = Cells(aktuelleZeile, spalteProduktnummer).Address
aktuelleZelleÜbergeordneteArtikelnummer = Cells(aktuelleZeile, spalteÜbergeordneteArtikelnummer).Address
aktuelleZelleProdukttyp = Cells(aktuelleZeile, spalteProduktTyp).Address

'Wenn nicht Variante oder Fremdsprachenprodukt ausgewählt, dann übergeordnete Artikelnummer leeren
If Not (Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") Or Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product (foreign language)") Or Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)")) Then
    If Range(aktuelleZelleÜbergeordneteArtikelnummer) <> "" Then
        Range(aktuelleZelleÜbergeordneteArtikelnummer) = ""
    End If
    GoTo exitHandler
End If

'Wenn Fremdsprachenprodukt ausgewählt, dann Artikelnummer leeren
If Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product (foreign language)") Or Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)") Then
    If Range(aktuelleZelleProduktnummer) <> "" Then
        Range(aktuelleZelleProduktnummer) = ""
    End If
End If

If aktuelleZeile > zentralwertZeileDatenbeginn Then
    For zuPrüfendeZeile = aktuelleZeile - 1 To zentralwertZeileDatenbeginn Step -1
        zelle = Cells(zuPrüfendeZeile, spalteProduktTyp).Address
        If (Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product (foreign language)") And Range(zelle) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product")) Or (Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)") And Range(zelle) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant")) Or (Range(aktuelleZelleProdukttyp) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") And Range(zelle) = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product")) Then
            Range(aktuelleZelleÜbergeordneteArtikelnummer) = Range(Cells(zuPrüfendeZeile, spalteProduktnummer).Address)
            GoTo exitHandler
        End If
    Next
End If

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Function crc32HashErmitteln(str As String)
'Erstellt einen CRC32-Hash


Rem TBU, LIFE, 30.05.2023, die Funktion soll zum debuggen den Klarschrift-Funktionsnamen zurückliefern
If cModus = "Entwicklung" Then
    crc32HashErmitteln = str
    Exit Function
End If


    On Error GoTo errHandler

    Dim crc32Table(256) As Long
    crc32Table(0) = 0
    crc32Table(1) = 1996959894
    crc32Table(2) = -301047508
    crc32Table(3) = -1727442502
    crc32Table(4) = 124634137
    crc32Table(5) = 1886057615
    crc32Table(6) = -379345611
    crc32Table(7) = -1637575261
    crc32Table(8) = 249268274
    crc32Table(9) = 2044508324
    crc32Table(10) = -522852066
    crc32Table(11) = -1747789432
    crc32Table(12) = 162941995
    crc32Table(13) = 2125561021
    crc32Table(14) = -407360249
    crc32Table(15) = -1866523247
    crc32Table(16) = 498536548
    crc32Table(17) = 1789927666
    crc32Table(18) = -205950648
    crc32Table(19) = -2067906082
    crc32Table(20) = 450548861
    crc32Table(21) = 1843258603
    crc32Table(22) = -187386543
    crc32Table(23) = -2083289657
    crc32Table(24) = 325883990
    crc32Table(25) = 1684777152
    crc32Table(26) = -43845254
    crc32Table(27) = -1973040660
    crc32Table(28) = 335633487
    crc32Table(29) = 1661365465
    crc32Table(30) = -99664541
    crc32Table(31) = -1928851979
    crc32Table(32) = 997073096
    crc32Table(33) = 1281953886
    crc32Table(34) = -715111964
    crc32Table(35) = -1570279054
    crc32Table(36) = 1006888145
    crc32Table(37) = 1258607687
    crc32Table(38) = -770865667
    crc32Table(39) = -1526024853
    crc32Table(40) = 901097722
    crc32Table(41) = 1119000684
    crc32Table(42) = -608450090
    crc32Table(43) = -1396901568
    crc32Table(44) = 853044451
    crc32Table(45) = 1172266101
    crc32Table(46) = -589951537
    crc32Table(47) = -1412350631
    crc32Table(48) = 651767980
    crc32Table(49) = 1373503546
    crc32Table(50) = -925412992
    crc32Table(51) = -1076862698
    crc32Table(52) = 565507253
    crc32Table(53) = 1454621731
    crc32Table(54) = -809855591
    crc32Table(55) = -1195530993
    crc32Table(56) = 671266974
    crc32Table(57) = 1594198024
    crc32Table(58) = -972236366
    crc32Table(59) = -1324619484
    crc32Table(60) = 795835527
    crc32Table(61) = 1483230225
    crc32Table(62) = -1050600021
    crc32Table(63) = -1234817731
    crc32Table(64) = 1994146192
    crc32Table(65) = 31158534
    crc32Table(66) = -1731059524
    crc32Table(67) = -271249366
    crc32Table(68) = 1907459465
    crc32Table(69) = 112637215
    crc32Table(70) = -1614814043
    crc32Table(71) = -390540237
    crc32Table(72) = 2013776290
    crc32Table(73) = 251722036
    crc32Table(74) = -1777751922
    crc32Table(75) = -519137256
    crc32Table(76) = 2137656763
    crc32Table(77) = 141376813
    crc32Table(78) = -1855689577
    crc32Table(79) = -429695999
    crc32Table(80) = 1802195444
    crc32Table(81) = 476864866
    crc32Table(82) = -2056965928
    crc32Table(83) = -228458418
    crc32Table(84) = 1812370925
    crc32Table(85) = 453092731
    crc32Table(86) = -2113342271
    crc32Table(87) = -183516073
    crc32Table(88) = 1706088902
    crc32Table(89) = 314042704
    crc32Table(90) = -1950435094
    crc32Table(91) = -54949764
    crc32Table(92) = 1658658271
    crc32Table(93) = 366619977
    crc32Table(94) = -1932296973
    crc32Table(95) = -69972891
    crc32Table(96) = 1303535960
    crc32Table(97) = 984961486
    crc32Table(98) = -1547960204
    crc32Table(99) = -725929758
    crc32Table(100) = 1256170817
    crc32Table(101) = 1037604311
    crc32Table(102) = -1529756563
    crc32Table(103) = -740887301
    crc32Table(104) = 1131014506
    crc32Table(105) = 879679996
    crc32Table(106) = -1385723834
    crc32Table(107) = -631195440
    crc32Table(108) = 1141124467
    crc32Table(109) = 855842277
    crc32Table(110) = -1442165665
    crc32Table(111) = -586318647
    crc32Table(112) = 1342533948
    crc32Table(113) = 654459306
    crc32Table(114) = -1106571248
    crc32Table(115) = -921952122
    crc32Table(116) = 1466479909
    crc32Table(117) = 544179635
    crc32Table(118) = -1184443383
    crc32Table(119) = -832445281
    crc32Table(120) = 1591671054
    crc32Table(121) = 702138776
    crc32Table(122) = -1328506846
    crc32Table(123) = -942167884
    crc32Table(124) = 1504918807
    crc32Table(125) = 783551873
    crc32Table(126) = -1212326853
    crc32Table(127) = -1061524307
    crc32Table(128) = -306674912
    crc32Table(129) = -1698712650
    crc32Table(130) = 62317068
    crc32Table(131) = 1957810842
    crc32Table(132) = -355121351
    crc32Table(133) = -1647151185
    crc32Table(134) = 81470997
    crc32Table(135) = 1943803523
    crc32Table(136) = -480048366
    crc32Table(137) = -1805370492
    crc32Table(138) = 225274430
    crc32Table(139) = 2053790376
    crc32Table(140) = -468791541
    crc32Table(141) = -1828061283
    crc32Table(142) = 167816743
    crc32Table(143) = 2097651377
    crc32Table(144) = -267414716
    crc32Table(145) = -2029476910
    crc32Table(146) = 503444072
    crc32Table(147) = 1762050814
    crc32Table(148) = -144550051
    crc32Table(149) = -2140837941
    crc32Table(150) = 426522225
    crc32Table(151) = 1852507879
    crc32Table(152) = -19653770
    crc32Table(153) = -1982649376
    crc32Table(154) = 282753626
    crc32Table(155) = 1742555852
    crc32Table(156) = -105259153
    crc32Table(157) = -1900089351
    crc32Table(158) = 397917763
    crc32Table(159) = 1622183637
    crc32Table(160) = -690576408
    crc32Table(161) = -1580100738
    crc32Table(162) = 953729732
    crc32Table(163) = 1340076626
    crc32Table(164) = -776247311
    crc32Table(165) = -1497606297
    crc32Table(166) = 1068828381
    crc32Table(167) = 1219638859
    crc32Table(168) = -670225446
    crc32Table(169) = -1358292148
    crc32Table(170) = 906185462
    crc32Table(171) = 1090812512
    crc32Table(172) = -547295293
    crc32Table(173) = -1469587627
    crc32Table(174) = 829329135
    crc32Table(175) = 1181335161
    crc32Table(176) = -882789492
    crc32Table(177) = -1134132454
    crc32Table(178) = 628085408
    crc32Table(179) = 1382605366
    crc32Table(180) = -871598187
    crc32Table(181) = -1156888829
    crc32Table(182) = 570562233
    crc32Table(183) = 1426400815
    crc32Table(184) = -977650754
    crc32Table(185) = -1296233688
    crc32Table(186) = 733239954
    crc32Table(187) = 1555261956
    crc32Table(188) = -1026031705
    crc32Table(189) = -1244606671
    crc32Table(190) = 752459403
    crc32Table(191) = 1541320221
    crc32Table(192) = -1687895376
    crc32Table(193) = -328994266
    crc32Table(194) = 1969922972
    crc32Table(195) = 40735498
    crc32Table(196) = -1677130071
    crc32Table(197) = -351390145
    crc32Table(198) = 1913087877
    crc32Table(199) = 83908371
    crc32Table(200) = -1782625662
    crc32Table(201) = -491226604
    crc32Table(202) = 2075208622
    crc32Table(203) = 213261112
    crc32Table(204) = -1831694693
    crc32Table(205) = -438977011
    crc32Table(206) = 2094854071
    crc32Table(207) = 198958881
    crc32Table(208) = -2032938284
    crc32Table(209) = -237706686
    crc32Table(210) = 1759359992
    crc32Table(211) = 534414190
    crc32Table(212) = -2118248755
    crc32Table(213) = -155638181
    crc32Table(214) = 1873836001
    crc32Table(215) = 414664567
    crc32Table(216) = -2012718362
    crc32Table(217) = -15766928
    crc32Table(218) = 1711684554
    crc32Table(219) = 285281116
    crc32Table(220) = -1889165569
    crc32Table(221) = -127750551
    crc32Table(222) = 1634467795
    crc32Table(223) = 376229701
    crc32Table(224) = -1609899400
    crc32Table(225) = -686959890
    crc32Table(226) = 1308918612
    crc32Table(227) = 956543938
    crc32Table(228) = -1486412191
    crc32Table(229) = -799009033
    crc32Table(230) = 1231636301
    crc32Table(231) = 1047427035
    crc32Table(232) = -1362007478
    crc32Table(233) = -640263460
    crc32Table(234) = 1088359270
    crc32Table(235) = 936918000
    crc32Table(236) = -1447252397
    crc32Table(237) = -558129467
    crc32Table(238) = 1202900863
    crc32Table(239) = 817233897
    crc32Table(240) = -1111625188
    crc32Table(241) = -893730166
    crc32Table(242) = 1404277552
    crc32Table(243) = 615818150
    crc32Table(244) = -1160759803
    crc32Table(245) = -841546093
    crc32Table(246) = 1423857449
    crc32Table(247) = 601450431
    crc32Table(248) = -1285129682
    crc32Table(249) = -1000256840
    crc32Table(250) = 1567103746
    crc32Table(251) = 711928724
    crc32Table(252) = -1274298825
    crc32Table(253) = -1022587231
    crc32Table(254) = 1510334235
    crc32Table(255) = 755167117

   Dim crc32Result As Long
   crc32Result = &HFFFFFFFF
      
   Dim i As Integer
   Dim iLookup As Integer
   Dim buffer() As Byte
   buffer = StrConv(str, vbFromUnicode)
   
   For i = LBound(buffer) To UBound(buffer)
      iLookup = (crc32Result And &HFF) Xor buffer(i)
      crc32Result = ((crc32Result And &HFFFFFF00) \ &H100) And 16777215
      ' nasty shr 8 with vb :/
      crc32Result = crc32Result Xor crc32Table(iLookup)
   Next i
   
   If crc32Result < 0 Then crc32Result = crc32Result * -1
   
   crc32HashErmitteln = crc32Result

exitHandler:
Exit Function

errHandler:
GoTo exitHandler

End Function


Public Function istFormularOffen(ByVal UFName As String) As Boolean
' Prüft ob Formular offen ist

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("istFormularOffen")

Dim UForm As Object
 
istFormularOffen = False
For Each UForm In VBA.UserForms
    If UForm.Name = UFName Then
        istFormularOffen = True
        Exit For
    End If
Next

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function

Public Function waitFensterRepaint()
'Refresht das waitFenster

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("waitFensterRepaint")

WaitFenster.Repaint

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Sub listenfeldElementeBestimmen_Ausprägung()
'Die zu einem Merkmal zugehörigen Ausprägungen ermitteln und zurückgeben (für Aufbau des jeweiligen Ausprägungslistenfeldes)

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("listenfeldElementeBestimmen_Ausprägung")

Dim entsprechendeMerkmalszelle As String
Dim entsprechendeMerkmalszelleWert As String
Dim entsprechendeAusprägungszelle As String
Dim letzteZeileMerkmaleSpalte As Long
Dim letzteZeileAusprägungenSpalte As Long
Dim aktuellesMerkmalInKonfigurationsblatt As String
Dim aktuelleAusprägungInKonfigurationsblatt As String
Dim aktuelleZeile As Long
Dim ausprägungsbereichAnfangszeile As Long
Dim ausprägungsbereichEndzeile As Long
Dim merkmalGefunden As Byte
Dim ausprägungsbereichRange As String
Dim spaltenoffsetMerkmal As Long
Dim spaltenoffsetAusprägung As Long
Dim formel As String

Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
If fSpalteInGruppeDerMerkmale(ActiveCell.Column) Then
    spaltenoffsetMerkmal = 0
    spaltenoffsetAusprägung = 1

ElseIf fSpalteInGruppeDerAusprägungen(ActiveCell.Column) Then
    spaltenoffsetMerkmal = -1
    spaltenoffsetAusprägung = 0
End If
'Select Case ActiveCell.Column
'Case zentralwertProduktverwaltungSpalteMerkmal1, zentralwertProduktverwaltungSpalteMerkmal2, zentralwertProduktverwaltungSpalteMerkmal3, zentralwertProduktverwaltungSpalteMerkmal4, zentralwertProduktverwaltungSpalteMerkmal5, zentralwertProduktverwaltungSpalteMerkmal6, zentralwertProduktverwaltungSpalteMerkmal7, zentralwertProduktverwaltungSpalteMerkmal8, zentralwertProduktverwaltungSpalteMerkmal9, zentralwertProduktverwaltungSpalteMerkmal10, zentralwertProduktverwaltungSpalteMerkmal11, zentralwertProduktverwaltungSpalteMerkmal12, zentralwertProduktverwaltungSpalteMerkmal13, zentralwertProduktverwaltungSpalteMerkmal14, zentralwertProduktverwaltungSpalteMerkmal15, zentralwertProduktverwaltungSpalteMerkmal16, zentralwertProduktverwaltungSpalteMerkmal17, zentralwertProduktverwaltungSpalteMerkmal18, zentralwertProduktverwaltungSpalteMerkmal19, zentralwertProduktverwaltungSpalteMerkmal20
'    spaltenoffsetMerkmal = 0
'    spaltenoffsetAusprägung = 1
'Case zentralwertProduktverwaltungSpalteAusprägung1, zentralwertProduktverwaltungSpalteAusprägung2, zentralwertProduktverwaltungSpalteAusprägung3, zentralwertProduktverwaltungSpalteAusprägung4, zentralwertProduktverwaltungSpalteAusprägung5, zentralwertProduktverwaltungSpalteAusprägung6, zentralwertProduktverwaltungSpalteAusprägung7, zentralwertProduktverwaltungSpalteAusprägung8, zentralwertProduktverwaltungSpalteAusprägung9, zentralwertProduktverwaltungSpalteAusprägung10, zentralwertProduktverwaltungSpalteAusprägung11, zentralwertProduktverwaltungSpalteAusprägung12, zentralwertProduktverwaltungSpalteAusprägung13, zentralwertProduktverwaltungSpalteAusprägung14, zentralwertProduktverwaltungSpalteAusprägung15, zentralwertProduktverwaltungSpalteAusprägung16, zentralwertProduktverwaltungSpalteAusprägung17, zentralwertProduktverwaltungSpalteAusprägung18, zentralwertProduktverwaltungSpalteAusprägung19, zentralwertProduktverwaltungSpalteAusprägung20
'    spaltenoffsetMerkmal = -1
'    spaltenoffsetAusprägung = 0
'End Select

entsprechendeMerkmalszelle = Cells(ActiveCell.Row, ActiveCell.Column + spaltenoffsetMerkmal).Address
entsprechendeAusprägungszelle = Cells(ActiveCell.Row, ActiveCell.Column + spaltenoffsetAusprägung).Address
entsprechendeMerkmalszelleWert = Range(entsprechendeMerkmalszelle)
If entsprechendeMerkmalszelleWert = "" Then GoTo nachAusprägungsbereichBestimmen
letzteZeileMerkmaleSpalte = Worksheets(zentralwertTabellenblattNameKonfiguration).Cells(Worksheets(zentralwertTabellenblattNameKonfiguration).Rows.Count, zentralwertKonfigurationSpalteMerkmal).End(xlUp).Row
letzteZeileAusprägungenSpalte = Worksheets(zentralwertTabellenblattNameKonfiguration).Cells(Worksheets(zentralwertTabellenblattNameKonfiguration).Rows.Count, zentralwertKonfigurationSpalteAusprägung).End(xlUp).Row
merkmalGefunden = False
For aktuelleZeile = zentralwertZeileDatenbeginn To letzteZeileAusprägungenSpalte
    aktuellesMerkmalInKonfigurationsblatt = Worksheets(zentralwertTabellenblattNameKonfiguration).Cells(aktuelleZeile, zentralwertKonfigurationSpalteMerkmal)
    aktuelleAusprägungInKonfigurationsblatt = Worksheets(zentralwertTabellenblattNameKonfiguration).Cells(aktuelleZeile, zentralwertKonfigurationSpalteAusprägung)
    If aktuellesMerkmalInKonfigurationsblatt = entsprechendeMerkmalszelleWert And merkmalGefunden = False Then
        ausprägungsbereichAnfangszeile = aktuelleZeile
        merkmalGefunden = True
    End If
    If (aktuelleAusprägungInKonfigurationsblatt = "" Or aktuelleZeile = letzteZeileAusprägungenSpalte Or (aktuellesMerkmalInKonfigurationsblatt <> entsprechendeMerkmalszelleWert And aktuellesMerkmalInKonfigurationsblatt <> "")) And merkmalGefunden = True Then
        ausprägungsbereichEndzeile = aktuelleZeile - IIf(aktuelleZeile = letzteZeileAusprägungenSpalte, 0, 1)
        ausprägungsbereichRange = Cells(ausprägungsbereichAnfangszeile, zentralwertKonfigurationSpalteAusprägung).Address & ":" & Cells(ausprägungsbereichEndzeile, zentralwertKonfigurationSpalteAusprägung).Address
        Exit For
    End If
Next

nachAusprägungsbereichBestimmen:
On Error GoTo fehlerInErmitteltemAusprägungsbereich
nachAusprägungsbereichBestimmen2:
'Bestimmen der Range für das Listenfeld
If ausprägungsbereichRange <> "" Then
    If zentralwertExcelVersion >= zentralwertAltesExcelVorVersion Then
        formel = "=" & zentralwertTabellenblattNameKonfiguration & "!" & ausprägungsbereichRange 'Bereich für diese Spalte ermitteln. Dieser wird für die Datenprüfungs-Formel verwendet
    Else
        formel = "=" & "konfigurationAusprägung" 'Bei älteren Excel-Versionen die gesamte Range anhand des Namens hinterlegen
    End If
Else
    formel = "=" & "konfigurationAusprägung"
End If

With Range(entsprechendeAusprägungszelle).Validation
    .Delete
    .Add Type:=xlValidateList, AlertStyle:=xlValidAlertStop, Operator:= _
    xlBetween, Formula1:=formel
    .IgnoreBlank = True
    .InCellDropdown = True
    .InputTitle = ""
    .ErrorTitle = ""
    .InputMessage = ""
    .ErrorMessage = ""
    .ShowInput = True
    .ShowError = True
End With

exitHandler:
Exit Sub


fehlerInErmitteltemAusprägungsbereich:
'Wenn Formel in Fehler, dann Standardrange nehmen
On Error GoTo errHandler
ausprägungsbereichRange = Cells(zentralwertZeileDatenbeginn, zentralwertKonfigurationSpalteAusprägung).Address & ":" & Cells(letzteZeileAusprägungenSpalte, zentralwertKonfigurationSpalteAusprägung).Address
GoTo nachAusprägungsbereichBestimmen2

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function zellbereichLeerzeilenKappen(tabellenblatt As String, zellbereich As String)
'Kappt den übergebenen Zellbereich ab der ersten Leerzeile und gibt den korrigierten Zellbereich zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zellbereichLeerzeilenKappen")

Dim zelle As Range
Dim AnzahlZeilen As Long
Dim X As Long
Dim letzteBelegteZelle As String
Dim belegteZeileZähler As Long

For Each zelle In Sheets(tabellenblatt).Range(zellbereich)
    If zelle = "" Then
        letzteBelegteZelle = Cells(Range(zelle.Address).Row, Range(zelle.Address).Column).Address 'wenn nur Leerzeilen
        Exit For
    End If
Next zelle


zellbereichLeerzeilenKappen = Range(zellbereich).Cells(1, 1).Address & ":" & letzteBelegteZelle

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function
