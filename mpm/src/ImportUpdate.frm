VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} ImportUpdate 
   ClientHeight    =   6096
   ClientLeft      =   45
   ClientTop       =   375
   ClientWidth     =   9375.001
   OleObjectBlob   =   "ImportUpdate.frx":0000
   StartUpPosition =   1  'Fenstermitte
End
Attribute VB_Name = "ImportUpdate"
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

aktuelleFunktionsnummer = crc32HashErmitteln("ImportUpdate_buttonAbbrechen_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Application.EnableEvents = True

Unload Me

exitHandler:
Application.EnableEvents = True
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub



Private Sub buttonImportieren_Click()
'Import ausführen

aktuelleFunktionsnummer = crc32HashErmitteln("ImportUpdate_buttonImportieren_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

Dim aktuellerEPMName As String
Dim quelleEPM As String
Dim fehlerInQuelleFehlendeSpalten As String
Dim felderBlackList As String
Dim einfügenKonfigurationAbZeile As Long
Dim quelleLetzteVerwendeteZeile As Long
Dim quelleLetzteVerwendeteSpalte As Long
Dim aktuellerEPMSpaltenzähler As Long
Dim aktuellesFeld As String
Dim quelleAktuellesFeldSpalte As Long
Dim quelleEPMSpaltenzähler As Long
Dim quelleAktuellesFeldErstesFeld As String
Dim quelleAktuellesFeldLetztesFeld As String
Dim quelleKopierbereich As String
Dim zellenzähler As Long
Dim quelleZelle As Object
Dim importfehler As Boolean
Dim einfügenProdukteAbZeile As Long
Dim quelleAnzahlZeilen As Long
Dim quelleProduktzähler As Long
Dim produktzählerNeu As Long
Dim X As Long
Dim lZeileContentTransformationLetzte&
Dim lSpalteContentTransformation&


If Trim(Me.textboxQuelleEPM.Value) = "" Then GoTo errDateiNichtAusgewählt 'Raus, falls noch keine (gültige) Datei gewählt wurde

antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importFrage01"), 4, "Import/Update")
If antwort <> vbYes Then
    GoTo manuellAbgebrochen
End If

aktuellerEPMName = ThisWorkbook.Name
quelleEPM = Me.textboxQuelleEPM.Value
Me.textboxImportresultat = ""
fehlerInQuelleFehlendeSpalten = ""

'Die Konfigurationsdaten übernehmen
Me.textboxImportschritt.Value = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Importing configuration")
warteschleife 0.1, True 'Wegen Screen-Refresh nötig, da ansonsten der aktuelle importschritt nicht angezeigt wird
felderBlackList = "/contentTranformation/" 'Diese Felder nicht übernehmen
Sheets(zentralwertTabellenblattNameKonfiguration).Select


    Rem 06.09.2023, tbu bestehende Daten sollen gelöscht werden - KONFIGURATIONSDATEN
    Rem Merging der vertikalen Spalten entfernen
    sVertikaleUeberschriftenFormatieren "Content transformation", False
    sVertikaleUeberschriftenFormatieren "Auto row height", False
    sVertikaleUeberschriftenFormatieren "VariantCreator Settings", False
    sVertikaleUeberschriftenFormatieren "Name of (Text-)template", False
    
    
    Rem 12.09.2023, die Spalte "contentTranformation" ist in der Blacklist, darf daher nicht gelöscht werden. Sie enthält
    Rem darüber hinaus Checkboxen und Werte-felder in der versteckten Spalte rechts daneben.
    Rem Lösung: letzte Zeile mit Daten ermitteln, löschen "um diese Spalte herum" und dann Übernahme wie gehabt
    fSpaltenKeyZuBezeichner "contentTranformation", lSpalteContentTransformation



    Rem den letzten Wert suchen
    For lZeileContentTransformationLetzte = ActiveSheet.UsedRange.Rows.Count To zentralwertZeileDatenbeginn Step -1
        If ActiveSheet.Cells(lZeileContentTransformationLetzte, lSpalteContentTransformation) <> "" Then
            Exit For
        End If
    Next


    Rem Range VOR der contentTransformation Spalte markieren
    Range( _
        Cells(zentralwertZeileDatenbeginn, 1), _
        Cells(lZeileContentTransformationLetzte, lSpalteContentTransformation - 1)).ClearContents
    
    Rem Range NACH der contentTransformation Spalte (plus der versteckten Werte-Spalte markieren
    Range( _
        Cells(zentralwertZeileDatenbeginn, lSpalteContentTransformation + 1), _
        Cells(lZeileContentTransformationLetzte, zentralwertLetzteVerwendeteSpalteKonfiguration)).ClearContents
    
    

    Rem Zeilen komplett löschen
    If ActiveSheet.UsedRange.Rows.Count > lZeileContentTransformationLetzte Then
        Rem 12.09.2023, nicht mehr den ganzen Datenbereich sondern nur unterhalb der letzten Zeile der contentTranformation
        Range(Rows(lZeileContentTransformationLetzte + 1), Rows(ActiveSheet.UsedRange.Rows.Count)).Select
    End If
    Selection.Delete Shift:=xlUp

    
    Rem Vertikale Spalten wieder zusammenfügen
    sVertikaleUeberschriftenFormatieren "Content transformation", True, 10 + 2
    sVertikaleUeberschriftenFormatieren "Auto row height", True, 16 + 2
    sVertikaleUeberschriftenFormatieren "VariantCreator Settings", True, 27 + 2
    sVertikaleUeberschriftenFormatieren "Name of (Text-)template", True, 45 + 2


einfügenKonfigurationAbZeile = zentralwertZeileDatenbeginn 'Hier festhalten, ab welcher Zeile (im Ziel-EPM) die zu übernehmenden Daten eingefügt werden sollen.
With Application.Workbooks(quelleEPM).Sheets(zentralwertTabellenblattNameKonfiguration)
    .Activate 'Tabellenblatt aktivieren
    'Im Quell-EPM die letzte Zeile und letzte Spalte ermitteln
    quelleLetzteVerwendeteZeile = .UsedRange.Rows.Count
    quelleLetzteVerwendeteSpalte = .UsedRange.Columns.Count
    
    'Jetzt spaltenweise übernehmen
    For aktuellerEPMSpaltenzähler = 1 To zentralwertLetzteVerwendeteSpalteKonfiguration 'Jede Spalte im aktuellen EPM durchgehen und diese aus dem zu übernehmenden EPM übertragen.
        aktuellesFeld = Cells(zentralwertZeileDatenbanknamen, aktuellerEPMSpaltenzähler)
        quelleAktuellesFeldSpalte = 0
        If aktuellesFeld <> "" And Not InStr(1, felderBlackList, aktuellesFeld) > 0 Then 'Leere Spalten sowie Blacklist-Felder überspringen
            For quelleEPMSpaltenzähler = 1 To quelleLetzteVerwendeteSpalte 'Die Zeile mit den Datenbanknamen durchgehen und das aktuelle Feld suchen
                If .Cells(zentralwertZeileDatenbanknamen, quelleEPMSpaltenzähler) = aktuellesFeld Then
                    quelleAktuellesFeldSpalte = quelleEPMSpaltenzähler
                    Exit For
                End If
            Next quelleEPMSpaltenzähler
            
            If quelleAktuellesFeldSpalte > 0 Then 'Falls Feld im Quell-EPM gefunden wurde, die Daten dieser Spalte übernehmen (und zwar Zelle für Zelle und gesperrte nicht überschreiben, da sonst Fehlermeldung)
                .Activate
                quelleAktuellesFeldErstesFeld = Cells(zentralwertZeileDatenbeginn, quelleAktuellesFeldSpalte).Address
                quelleAktuellesFeldLetztesFeld = Cells(quelleLetzteVerwendeteZeile, quelleAktuellesFeldSpalte).Address
                quelleKopierbereich = quelleAktuellesFeldErstesFeld & ":" & quelleAktuellesFeldLetztesFeld
                zellenzähler = 0
                For Each quelleZelle In .Range(quelleKopierbereich).Cells 'Zelle für Zelle der Quellspalte durchgehen
                    quelleZelle.Copy
                    Windows(aktuellerEPMName).Activate
                    Cells(einfügenKonfigurationAbZeile + zellenzähler, aktuellerEPMSpaltenzähler).Select
                    If Cells(einfügenKonfigurationAbZeile + zellenzähler, aktuellerEPMSpaltenzähler).Locked <> True Then '(gesperrte auslassen, da sonst Fehlermeldung)
                        ActiveSheet.Paste
                    End If
                    zellenzähler = zellenzähler + 1
                Next
            Else 'Hier die Spalten festhalten, die nicht gefunden wurden
                Rem 04.09.2023, tbu, Ausgabe des Klarschrift Namens (Bezeichner) anstelle des internen Schlüssels
                'fehlerInQuelleFehlendeSpalten = fehlerInQuelleFehlendeSpalten & aktuellesFeld & ", "
                fehlerInQuelleFehlendeSpalten = fehlerInQuelleFehlendeSpalten & "´" & fSpaltenKeyZuBezeichner(aktuellesFeld) & "´, "
                importfehler = True
            End If
        End If
    Next aktuellerEPMSpaltenzähler
    If fehlerInQuelleFehlendeSpalten = "" Then
        Me.textboxImportresultat = Me.textboxImportresultat & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Columns (configuration)") & ": " & "Ok"
    Else
        Me.textboxImportresultat = Me.textboxImportresultat & vbLf _
            & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Missing columns (products)") _
            & ": " & Left(fehlerInQuelleFehlendeSpalten, Len(fehlerInQuelleFehlendeSpalten) - 2)
    End If
    .Activate
    .Cells(zentralwertZeileDatenbeginn, 1).Select 'Einzelne Zelle selektieren, damit nichts im Zwischenspeicher ist und keine Nachfrage beim Schließen kommt
End With
Windows(aktuellerEPMName).Activate
Cells(zentralwertZeileDatenbeginn, 1).Select 'Der Ordnung halber wieder auf die erste Zelle springen


'Die Produktdaten übernehmen
felderBlackList = "//"
Sheets(zentralwertTabellenblattNameProduktverwaltung).Select

    Rem 05.09.2023, bestehende Daten sollen gelöscht werden - Produktblatt
    If ActiveSheet.UsedRange.Rows.Count > zentralwertZeileDatenbeginn Then
        Range(Rows(zentralwertZeileDatenbeginn), Rows(ActiveSheet.UsedRange.Rows.Count)).Select
    End If
    Selection.Delete Shift:=xlUp


einfügenProdukteAbZeile = letzteVerwendeteZeileErmitteln(zentralwertTabellenblattNameProduktverwaltung) + 1 'Hier festhalten, ab welcher Zeile (im Ziel-EPM) die zu übernehmenden Daten eingefügt werden sollen.
fehlerInQuelleFehlendeSpalten = ""
With Application.Workbooks(quelleEPM).Sheets(zentralwertTabellenblattNameProduktverwaltung)
    Me.textboxImportschritt.Value = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Importing structure")
    warteschleife 0.1, True 'Wegen Screen-Refresh nötig, da ansonsten der aktuelle importschritt nicht angezeigt wird
    .Activate 'Tabellenblatt aktivieren
    'Im Quell-EPM die letzte Zeile und letzte Spalte ermitteln
    quelleLetzteVerwendeteZeile = .UsedRange.Rows.Count
    quelleLetzteVerwendeteSpalte = .UsedRange.Columns.Count
    
    'Aus dem Quell-EPM zuerst sämtliche Zeilen kopieren. Wichtig, da nur so auch Gruppierungen mitgenommen werden.
    'Danach werden die Inhalte im Ziel-EPM aber zuerst wieder gelöscht, um sie dann wieder spaltenweise zu übernehmen
    .Rows(zentralwertZeileDatenbeginn & ":" & quelleLetzteVerwendeteZeile).Copy
    quelleAnzahlZeilen = .Rows(zentralwertZeileDatenbeginn & ":" & quelleLetzteVerwendeteZeile).Rows.Count
    Windows(aktuellerEPMName).Activate
    Cells(zentralwertZeileDatenbeginn, 1).Select
    Cells(einfügenProdukteAbZeile, 1).Select
    ActiveSheet.Paste
    Selection.ClearContents
    Me.textboxImportresultat = Me.textboxImportresultat & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Structure of products") & ": " & "Ok"
    
    'Jetzt spaltenweise übernehmen
    For aktuellerEPMSpaltenzähler = 1 To zentralwertLetzteVerwendeteSpalteProduktverwaltung 'Jedes Feld im aktuellen EPM durchgehen und dieses aus dem zu übernehmenden EPM übertragen.
        aktuellesFeld = Cells(zentralwertZeileDatenbanknamen, aktuellerEPMSpaltenzähler)
        quelleAktuellesFeldSpalte = 0
        If aktuellesFeld <> "" And Not InStr(1, felderBlackList, aktuellesFeld) > 0 Then 'Leere Spalten sowie Blacklist-Felder überspringen
            For quelleEPMSpaltenzähler = 1 To quelleLetzteVerwendeteSpalte 'Die Zeile mit den Datenbanknamen durchgehen und das aktuelle Feld suchen
                If .Cells(zentralwertZeileDatenbanknamen, quelleEPMSpaltenzähler) = aktuellesFeld Then
                    quelleAktuellesFeldSpalte = quelleEPMSpaltenzähler
                    Exit For
                End If
            Next quelleEPMSpaltenzähler
            
            If quelleAktuellesFeldSpalte > 0 Then 'Falls Feld im Quell-EPM gefunden wurde, die Daten dieser Spalte übernehmen 'Wenn Spalte gefunden wurde
                Me.textboxImportschritt.Value = Trim(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Importing column") & ": " & aktuellesFeld)
                warteschleife 0.1, True 'Wegen Screen-Refresh nötig, da ansonsten der aktuelle importschritt nicht angezeigt wird
                quelleAktuellesFeldErstesFeld = Cells(zentralwertZeileDatenbeginn, quelleAktuellesFeldSpalte).Address
                quelleAktuellesFeldLetztesFeld = Cells(quelleLetzteVerwendeteZeile, quelleAktuellesFeldSpalte).Address
                quelleKopierbereich = quelleAktuellesFeldErstesFeld & ":" & quelleAktuellesFeldLetztesFeld
                .Range(quelleKopierbereich).Copy
                Windows(aktuellerEPMName).Activate
                Cells(einfügenProdukteAbZeile, aktuellerEPMSpaltenzähler).Select
                ActiveSheet.Paste
            Else 'Hier die Spalten festhalten, die nicht gefunden wurden
                Rem 04.09.2023, tbu, Ausgabe des Klarschrift Namens (Bezeichner) anstelle des internen Schlüssels
                'fehlerInQuelleFehlendeSpalten = fehlerInQuelleFehlendeSpalten & aktuellesFeld & ", "
                fehlerInQuelleFehlendeSpalten = fehlerInQuelleFehlendeSpalten & "´" & fSpaltenKeyZuBezeichner(aktuellesFeld) & "´, "
                importfehler = True
            End If
        End If
    Next aktuellerEPMSpaltenzähler
    If fehlerInQuelleFehlendeSpalten = "" Then
        Me.textboxImportresultat = Me.textboxImportresultat & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Columns (products)") & ": " & "Ok"
    Else
        Me.textboxImportresultat = Me.textboxImportresultat & _
            mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Missing columns (configuration)") _
            & ": " _
            & Left(fehlerInQuelleFehlendeSpalten, Len(fehlerInQuelleFehlendeSpalten) - 2)
    End If
    .Activate 'Tabellenblatt aktivieren
    .Cells(zentralwertZeileDatenbeginn, 1).Select 'Einzelne Zelle selektieren, damit nichts im Zwischenspeicher ist und keine Nachfrage beim Schließen kommt
End With
Windows(aktuellerEPMName).Activate
Cells(zentralwertZeileDatenbeginn, 1).Select 'Der Ordnung halber wieder auf die erste Zelle springen


'Jetzt noch die Anzahl der übernommenen Produkte in Quelle und Ziel vergleichen
'Quelle
quelleProduktzähler = 0
With Application.Workbooks(quelleEPM).Sheets(zentralwertTabellenblattNameProduktverwaltung)
    quelleLetzteVerwendeteZeile = .UsedRange.Rows.Count
    For X = zentralwertZeileDatenbeginn To quelleLetzteVerwendeteZeile
        If Trim(Cells(X, zentralwertProduktverwaltungSpalteTyp)) <> "" Then quelleProduktzähler = quelleProduktzähler + 1
    Next X
End With
'Aktueller EPM (Ziel)
produktzählerNeu = 0
With Sheets(zentralwertTabellenblattNameProduktverwaltung)
    letzteVerwendeteZeile = .UsedRange.Rows.Count
    For X = einfügenProdukteAbZeile To letzteVerwendeteZeile
        If Trim(Cells(X, zentralwertProduktverwaltungSpalteTyp)) <> "" Then produktzählerNeu = produktzählerNeu + 1
    Next X
End With
If quelleProduktzähler = produktzählerNeu Then
    Me.textboxImportresultat = Me.textboxImportresultat & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Number of imported products") & ": " & "Ok"
Else
    Me.textboxImportresultat = Me.textboxImportresultat & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Number of imported products") & ": " & "Not equal! (" & quelleProduktzähler & " vs. " & produktzählerNeu & ")"
End If



'Info über die (erfolgreiche) Übernahme ausgeben
If importfehler <> True Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importOk01") & einfügenProdukteAbZeile & "." & vbLf & vbLf & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importOk02"), vbOKOnly, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Ready!")
Else
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importFehler"), vbOKOnly, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Ready!")
End If

exitHandler:
Me.textboxImportschritt.Value = ""
datenüberprüfungNeuSetzen 'Datenüberprüfungen neu setzen. Wichtig, da diese durch das Kopieren Verweise auf die Quell-Tabelle enthalten
bedingteFormatierungenNeuSetzen 'Bedingte Formatierungen neu setzen. Wichtig, da diese durch das Kopieren Verweise auf die Quell-Tabelle enthalten
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
GoTo exitHandler

errDateiNichtAusgewählt:
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importKeineAusgewählt"), vbOKOnly
GoTo exitHandler

manuellAbgebrochen:
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importKeineDaten"), vbOKOnly, "Abbruch"
GoTo exitHandler

End Sub

Private Sub buttonQuelleWählen_Click()
'Datei auswählen

aktuelleFunktionsnummer = crc32HashErmitteln("ImportUpdate_buttonQuelleWählen_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = Application.EnableEvents
Application.EnableEvents = False

Dim dateiName As Variant
Dim quelleEPMVersion As String
Dim quelleEPMVersionNum As Variant

'Den zu übernehmenden EPM bestimmen
dateiName = dateidialogDateinamen("", False, False)
If Not dateiName = "" And Not dateiName = False Then 'Wenn keine Datei ausgewählt, dann raus
    Me.textboxQuelleEPMPfad = dateiName
Else
    GoTo errHandler
End If

'Quelldatei öffnen
Me.textboxQuelleEPM.Value = dateinameAusPfad(Me.textboxQuelleEPMPfad)
Workbooks.Open Me.textboxQuelleEPMPfad, , True
Me.textboxAktuellerEPMName = ThisWorkbook.Name
ThisWorkbook.Activate

Me.textboxQuelleEPMOk.Value = "Ok"

'Prüfen, ob die Version des QuellEPM ok ist (wenn kleiner/gleich wie die eigene Version)
quelleEPMVersion = Application.Workbooks(Me.textboxQuelleEPM.Value).Sheets(1).Range("epmVersion")
quelleEPMVersionNum = Val(quelleEPMVersion)
If quelleEPMVersionNum <= zentralwertEPMVersionNum Then
    Me.textboxQuelleEPMVersion.Value = quelleEPMVersion
    Me.textboxQuelleEPMVersionOk.Value = "Ok"
Else
    GoTo errVersionNichtKompatibel
End If

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
'Quell-EPM schließen, falls geöffnet
On Error Resume Next
Workbooks(Me.textboxQuelleEPM.Value).Saved = True 'Wichtig, damit keine "Möchten Sie speichern"-Frage kommt
Workbooks(Me.textboxQuelleEPM.Value).Close
Me.textboxQuelleEPM.Value = ""
Me.textboxQuelleEPMOk.Value = ""
Me.textboxQuelleEPMVersion.Value = ""
Me.textboxQuelleEPMVersionOk.Value = ""
GoTo exitHandler

errVersionNichtKompatibel:
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importManuell"), vbOKOnly
GoTo errHandler

End Sub



Private Sub UserForm_Initialize()
'Initialisierung des Formulars


aktuelleFunktionsnummer = crc32HashErmitteln("ImportUpdate_UserForm_Initialize")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = Application.EnableEvents
Application.EnableEvents = False


'Buttons (mehrsprachig) beschriften
Me.buttonQuelleWählen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "SOURCE")
Me.buttonImportieren.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "IMPORT")
Me.buttonAbbrechen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "CLOSE")


'Labels beschriften
Me.labelQuelldatei.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Source file")
Me.labelEPMVersion.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "MPM version")
Me.labelImportschritt.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Current step of import")
Me.labelImportresultat.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Import result/errors")


'Grafiken sprachabhängig einblenden
Select Case aktuellGewählteSprache
Case 1
Me.überschrift01English.Visible = True
Me.überschrift01Deutsch.Visible = False
Me.überschrift02English.Visible = True
Me.überschrift02Deutsch.Visible = False
Case 2
Me.überschrift01English.Visible = False
Me.überschrift01Deutsch.Visible = True
Me.überschrift02English.Visible = False
Me.überschrift02Deutsch.Visible = True
Case Else
Me.überschrift01English.Visible = True
Me.überschrift01Deutsch.Visible = False
Me.überschrift02English.Visible = True
Me.überschrift02Deutsch.Visible = False
End Select

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub


Private Function quelleinträgeZählen()
'Zählen, wie viele Produkte, Varianten etc. in der Quelle vorhanden sind

aktuelleFunktionsnummer = crc32HashErmitteln("ImportUpdate_quelleinträgeZählen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

If Trim(Me.textboxQuelleEPMPfad) = "" Then GoTo errDateiNichtAusgewählt

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

errDateiNichtAusgewählt:
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "importKeineAusgewählt"), vbOKOnly

End Function

Private Sub UserForm_Terminate()
'Wenn Formular geschlossen wird

aktuelleFunktionsnummer = crc32HashErmitteln("ImportUpdate_UserForm_Terminate")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = Application.EnableEvents
Application.EnableEvents = False

'Quell-EPM schließen
On Error Resume Next
If Me.textboxQuelleEPM.Value <> "" Then
    Workbooks(Me.textboxQuelleEPM.Value).Saved = True 'Wichtig, damit keine "Möchten Sie speichern"-Frage kommt
    Workbooks(Me.textboxQuelleEPM.Value).Close
End If

exitHandler:
Application.EnableEvents = True 'Zur Sicherheit nach Beenden des Importers aktivieren
Application.ScreenUpdating = True 'Zur Sicherheit nach Beenden des Importers aktivieren
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub
