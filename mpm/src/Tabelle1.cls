VERSION 1.0 CLASS
BEGIN
  MultiUse = -1  'True
END
Attribute VB_Name = "Tabelle1"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = True
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Public Sub aktionswähler()
'Führt entsprechende Aktion aus

aktuelleFunktionsnummer = crc32HashErmitteln("aktionswähler")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled


Rem 02.06.2023, TBU, da Fehler beim Aktionswähler (wegen verloren gegangener Variablenwerte) kamen wird das durch die bereits vorhandene Funktion abgefangen
aufAllgemeinenProgrammfehlerPrüfen


Select Case Range(aktionswählerZelle)
    
    Case 1
    LöschenProdukteFürLöschenMarkiert
    Case 2
    LöschenProdukteFürNichtImportMarkiert
    Case 3
    WaitFenster.Show
    Unload WaitFenster
    Case 4
    WaitFenster.Show
    Unload WaitFenster
    Case 5
    WaitFenster.Show
    Unload WaitFenster
    Case 6
    WaitFenster.Show
    Unload WaitFenster
    Case 7
    zeilenhöhenSetzen
    Case 8
    variantenErstellen
    Case 9
    WaitFenster.Show
    Unload WaitFenster
    Case 10
    'nichts
    Case 11
    WaitFenster.Show
    Unload WaitFenster
    Case 12
    WaitFenster.Show
    Unload WaitFenster
    Case 13
    WaitFenster.Show
    Unload WaitFenster
    Case 14
    WaitFenster.Show
    Unload WaitFenster
    Case 15
    WaitFenster.Show
    Unload WaitFenster
    Case 16
    kategorieAuswählenEinAus
    Case 17
    'nichts
    Case 18
    SaveBackup
    Case 19
    importUpdateDurchführen
    Case 20
    'nichts
    Case 21
    zeigeEPMInfo
End Select

exitHandler:
Unload WaitFenster
Range(aktionswählerZelle) = 0
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer & " " & Err.Description, vbOKOnly
GoTo exitHandler
End Sub



Public Sub LöschenProdukteFürLöschenMarkiert()
'Löschen der für das Löschen markierten Zeilen

aktuelleFunktionsnummer = crc32HashErmitteln("LöschenProdukteFürLöschenMarkiert")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

Dim i As Long
Dim löschZähler As Long
Dim StatusbarSichtbarZuvor As Boolean

antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "loeschen01"), 4, mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "loeschen02"))

löschZähler = 0
StatusbarSichtbarZuvor = Application.DisplayStatusBar
If antwort = vbYes Then
    Application.DisplayStatusBar = True
    Application.StatusBar = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "bitteWarten")
    letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
    For i = letzteVerwendeteZeile To zentralwertZeileDatenbeginn Step -1
        Application.StatusBar = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "bitteWarten") & ": " & (i - zentralwertZeileDatenbeginn) & "%. " & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "bitteWartenGelöscht") & löschZähler
        If LCase(ActiveSheet.Cells(i, zentralwertProduktverwaltungSpalteLöschen)) = 1 Then
            Rows(i).EntireRow.Delete
            löschZähler = löschZähler + 1
        End If
    Next i
End If


exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
MsgBox (mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "gelöschteZeilen") & löschZähler)
Application.StatusBar = ""
Application.DisplayStatusBar = StatusbarSichtbarZuvor
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub


Public Sub LöschenProdukteFürNichtImportMarkiert()
'Löschen der für die Importsperre markierten Zeilen

aktuelleFunktionsnummer = crc32HashErmitteln("LöschenProdukteFürNichtImportMarkiert")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

Dim i As Long
Dim löschZähler As Long
Dim StatusbarSichtbarZuvor As Boolean

antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "loeschen03"), 4, mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "loeschen02"))

löschZähler = 0
StatusbarSichtbarZuvor = Application.DisplayStatusBar
If antwort = vbYes Then
    Application.DisplayStatusBar = True
    Application.StatusBar = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "bitteWarten")
    letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
    For i = letzteVerwendeteZeile To zentralwertZeileDatenbeginn Step -1
        Application.StatusBar = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "bitteWarten") & ": " & (i - zentralwertZeileDatenbeginn) & "%. " & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "bitteWartenGelöscht") & löschZähler
        If LCase(ActiveSheet.Cells(i, zentralwertProduktverwaltungSpalteImportsperre)) = 1 Then
            Rows(i).EntireRow.Delete
            löschZähler = löschZähler + 1
        End If
    Next i
End If

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
MsgBox (mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "gelöschteZeilen") & löschZähler)
Application.StatusBar = ""
Application.DisplayStatusBar = StatusbarSichtbarZuvor
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub


Private Sub Worksheet_BeforeDoubleClick(ByVal Target As Range, Cancel As Boolean)
'Bei Doppelklick Dateiauswahldialog öffnen und ausgewählte Dateinamen übernehmen

aktuelleFunktionsnummer = crc32HashErmitteln("ProductManagement_Worksheet_BeforeDoubleClick")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus

Dim strOld As String
Dim dateienAuswahl As String

aufAllgemeinenProgrammfehlerPrüfen 'Hier zur Sicherheit den EPM neu initialisieren, falls aufgrund eines Programmfehlers die globalen Variablen weg sind.

Select Case ActiveCell.Column

Case zentralwertProduktverwaltungSpalteHauptbild, zentralwertProduktverwaltungSpalteWeitereBilder 'Hauptbild oder weitere Bilder
    alterEnableEventsStatus = aktuellerEnableEventsStatus
    Application.EnableEvents = False
    strOld = ActiveCell
    dateienAuswahl = dateidialogDateinamen(strOld, True, True) 'Dateidialog öffnen
    ActiveCell = strOld & dateienAuswahl
End Select

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Private Sub Worksheet_Change(ByVal Target As Range)
'Bei Eingaben/Änderungen Aktionen durchführen

On Error GoTo errHandlerRausOhneFehlermeldung 'Wichtiger Error-Handler, gleich zu Beginn (Kommentar siehe im Error-Handler selbst)
Exit Sub
aktuelleFunktionsnummer = crc32HashErmitteln("ProductManagement_Worksheet_Change")
Application.EnableCancelKey = xlDisabled

On Error GoTo errHandler

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False

aufAllgemeinenProgrammfehlerPrüfen 'Hier zur Sicherheit den EPM neu initialisieren, falls aufgrund eines Programmfehlers die globalen Variablen weg sind.

Dim rngBereich As Range
Dim rngZelle As Range
Dim aktiveSpalte As Long
Dim aktiveZeile As Long
Dim aktiveZelle As String
Dim entsprechendeAusprägungszelle As String
Dim merkmalszelleWert As String
Dim datenrange As String
Dim formelrelevanteÄnderungDurchgeführt As Boolean

Set rngBereich = Range(Target.Address)

aktiveSpalte = Target.Column
aktiveZeile = Target.Row
aktiveZelle = Target.Address


'Änderungen verbieten, wenn im Kopfbereich und nicht in einer speziellen Zelle (spezielle Zellen wegen Seiten- und Sprachwähler hier abfangen)
If Target.Row < zentralwertZeileDatenbeginn _
    And Target.Address <> gewählteSpracheZelle _
    And Target.Address <> seitenwählerZelle _
    And Target.Address <> aktionswählerZelle _
    And Target.Address <> kopfbereichAusblendenZelle _
    And Target.Address <> merkmaleAusprägungenAusblendenZelle _
    And Target.Address <> kategorienAuswaehlenZelle _
    And Target.Address <> variantenAlleAusblendenZelle _
    And Target.Address <> staffelpreiseAusblendenZelle _
    And Target.Address <> gruppenpreiseAusblendenZelle _
    And Target.Address <> flexContentAusblendenZelle _
    And Target.Address <> modusÜbergeordneteArtikelnummerEmittelnZelle Then
'LIFE: diese 5 Zeilen wieder einkommentieren
    On Error Resume Next
    Application.Undo
    On Error GoTo errHandler
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "änderungenVerboten")
    GoTo exitHandler
'Debug.Print "änderungenVerboten"
End If

'Falls Werte in Spalten eingetragen wurden, in die Formeln gehören, dann diese Werte ersetzen
For Each rngZelle In rngBereich
    Select Case aktiveSpalte
        Case zentralwertProduktverwaltungSpalteTyp 'Wenn Produkt-Typ geändert, dann Formel hinterlegen
            Select Case rngZelle
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product (foreign language)")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product (foreign language)"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)"))
        End Select
        Case zentralwertProduktverwaltungSpaltePreisTyp, zentralwertProduktverwaltungSpaltePreisTypGruppe1, zentralwertProduktverwaltungSpaltePreisTypGruppe2, zentralwertProduktverwaltungSpaltePreisTypGruppe3, zentralwertProduktverwaltungSpaltePreisTypGruppe4, zentralwertProduktverwaltungSpaltePreisTypGruppe5 'Wenn Preisart geändert wurde
            Select Case rngZelle
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price"))
        End Select
        Case zentralwertProduktverwaltungSpalteStaffelpreisArt, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe1, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe2, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe3, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe4, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe5 'Wenn Staffelpreis Art geändert wurde
            Select Case rngZelle
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed scale price")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed scale price"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percentaged adjustment")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percentaged adjustment"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Adjustment with a fixed value")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Adjustment with a fixed value"))
        End Select
        Case zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethode, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe1, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe2, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe3, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe4, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe5 'Wenn Staffelpreis Mengenermittlung Methode geändert wurde
            Select Case rngZelle
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products, variants and configurations")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products, variants and configurations"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products and variants")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products and variants"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Summarized by scale price keyword")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Summarized by scale price keyword"))
        End Select
        Case zentralwertProduktverwaltungSpalteAlterPreisTyp, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe1, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe2, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe3, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe4, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe5 'Wenn Preisart des alten Preises geändert wurde
            Select Case rngZelle
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price"))
        End Select
        Case zentralwertProduktverwaltungSpalteGewichtTyp 'Wenn Gewichtsart geändert wurde
            Select Case rngZelle
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed weight")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed weight"))
            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent weight")
                Range(aktiveZelle).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent weight"))
        End Select
    End Select
Next

'Nur dann zulassen, wenn man dabei nicht Inhalte in Spalten einfügt, in die Formeln reingehören und wenn es sich nicht um komplette Zeilen(-Selektionen) handelt (wie z. B. beim Löschen)
If zeileMarkiert = False _
    And Target.Address <> gewählteSpracheZelle _
    And Target.Address <> seitenwählerZelle _
    And Target.Address <> aktionswählerZelle _
    And Target.Address <> kopfbereichAusblendenZelle _
    And Target.Address <> merkmaleAusprägungenAusblendenZelle Then          'Nur prüfen, wenn es sich nicht um komplette Zeilen(-Selektionen)
                                                                            'handelt (wie z. B. beim Löschen) und wenn nicht spezielle Zellen
                                                                            'betroffen sind
    For Each rngZelle In rngBereich
        If Cells(zentralwertZeileSpaltentyp, rngZelle.Column) = "F" Then
            If Not rngZelle.HasFormula And Not IsEmpty(rngZelle) Then
                On Error Resume Next
                Application.Undo
                MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "achtungFormelnÜberschreiben")
                GoTo exitHandler
            End If
        End If
    Next
End If

'Falls das Feld ein Merkmal ist, die Datenquelle (auf der Konfiguratorseite) des Listenfeldes für die Ausprägungen bestimmen (anhand des dazugehörigen/linken Merkmals)
Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
If fSpalteInGruppeDerMerkmale(ActiveCell.Column) Then
'Select Case ActiveCell.Column
'Case zentralwertProduktverwaltungSpalteMerkmal1, zentralwertProduktverwaltungSpalteMerkmal2, zentralwertProduktverwaltungSpalteMerkmal3, _
    zentralwertProduktverwaltungSpalteMerkmal4, zentralwertProduktverwaltungSpalteMerkmal5, zentralwertProduktverwaltungSpalteMerkmal6, _
    zentralwertProduktverwaltungSpalteMerkmal7, zentralwertProduktverwaltungSpalteMerkmal8, zentralwertProduktverwaltungSpalteMerkmal9, _
    zentralwertProduktverwaltungSpalteMerkmal10, zentralwertProduktverwaltungSpalteMerkmal11, zentralwertProduktverwaltungSpalteMerkmal12, _
    zentralwertProduktverwaltungSpalteMerkmal13, zentralwertProduktverwaltungSpalteMerkmal14, zentralwertProduktverwaltungSpalteMerkmal15, _
    zentralwertProduktverwaltungSpalteMerkmal16, zentralwertProduktverwaltungSpalteMerkmal17, zentralwertProduktverwaltungSpalteMerkmal18, _
    zentralwertProduktverwaltungSpalteMerkmal19, zentralwertProduktverwaltungSpalteMerkmal20
    
    entsprechendeAusprägungszelle = Cells(ActiveCell.Row, ActiveCell.Column + 1).Address
    merkmalszelleWert = ActiveCell.Value
    Range(entsprechendeAusprägungszelle) = ""
    listenfeldElementeBestimmen_Ausprägung 'Zum Merkmal zugehörige Ausprägungen ermitteln und Listenfeld-Datenquelle setzen
'End Select
End If

'Wenn Zeilen eingefügt wurden, dann bedingte Formatierungen sowie Formeln hinterlegen, da ansonsten die Formeln und Formeln für bedingte Formatierungen nicht stimmen
Dim letzteVerwendeteZeileNeu As Long
'ActiveSheet.UsedRange
letzteVerwendeteZeileNeu = ActiveSheet.UsedRange.Rows.Count
If letzteVerwendeteZeileZuvor = letzteVerwendeteZeileNeu Then
ElseIf letzteVerwendeteZeileZuvor > letzteVerwendeteZeileNeu Then
    letzteVerwendeteZeileZuvor = letzteVerwendeteZeileNeu
    letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
    formelrelevanteÄnderungDurchgeführt = True
ElseIf letzteVerwendeteZeileZuvor < letzteVerwendeteZeileNeu Then
    'Alles neu errechnen, damit die Formatierungen, Datenprüfungen und Formeln stimmen, wenn man eine neue Zeile eingefügt hat
    bedingteFormatierungenNeuSetzen
    datenüberprüfungNeuSetzen
    datenüberprüfungNeuSetzenKategorie
    formelnNeuSetzen
    
    letzteVerwendeteZeileZuvor = letzteVerwendeteZeileNeu
    letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
    formelrelevanteÄnderungDurchgeführt = True
End If

'Formeln neu berechnen (wenn neue Zeile eingefügt oder Zeile gelöscht wurde; eine bestimmtes Feld geändert wurde; die übergeordnete Artikelnummer vollautomatisch errechnet wird)
If formelrelevanteÄnderungDurchgeführt = True Or _
    aktiveSpalte = zentralwertProduktverwaltungSpalteTyp Or _
    aktiveSpalte = zentralwertProduktverwaltungSpalteArtikelnummer Or _
    Range(modusÜbergeordneteArtikelnummerEmittelnZelle) = "automode" Then
    
    'zurück zur letzten aktiven Zelle
    If letzteAktiveZelle <> "" And Range(letzteAktiveZelle).Row > zentralwertZeileDatenbeginn Then
        Range(letzteAktiveZelle).Select
    Else
        Cells(zentralwertZeileDatenbeginn, 1).Select
    End If
    letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
    'Die Bereich mit den Daten ermitteln und Excel anweisen, alles neu zu berechnen
    datenrange = Cells(zentralwertZeileDatenbeginn, zentralwertProduktverwaltungSpalteÜbergeordneteArtikelnummer).Address & ":" & Cells(letzteVerwendeteZeile, zentralwertProduktverwaltungSpalteÜbergeordneteArtikelnummer).Address
    Range(datenrange).Calculate
End If


Rem TBU, 05.06.2023, Datumsfunktion
If aktiveSpalte = zentralwertProduktverwaltungSpalteVerfuegbarAb Then
    If Not IsDate(Cells(aktiveZeile, aktiveSpalte)) Then
        Cells(aktiveZeile, aktiveSpalte) = ""
        GoTo exitHandler
    End If
End If




exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = True 'Sicherheitshalber ausführen, da in alten Excelversionen wird der Refresh manchmal nicht richtig ausgeführt (Zellen sind teils nicht gefärbt)
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

errHandlerRausOhneFehlermeldung:
'Dieser Error-Handler ist nötig, wenn man in einem Listenfeld (z. B. Merkmale oder Ausprägungen) zuerst in das Feld klickt
'und dann gleich danach auf den Pfeil für die Liste klickt (man also nicht sofort auf den Pfeil klickt). Dann führt das zu
'Fehlermeldungen schon bei Application.EnableCancelKey, Application.EnableEvents oder anderen Application-Eigenschaften.
'Daher dann gleich zu Beginn diese Prozedur beenden

Exit Sub

End Sub


Private Sub Worksheet_SelectionChange(ByVal Target As Range)
'Falls der User eine Zeile aktiviert, die nicht für den gewählten Produkttyp geeignet ist, in die nächste geeignete Spalte springen.
'Falls in der Vorwärts-Prüfung bis zur letzten Spalte keine erlaubte Zelle mehr gefunden wird, Rückwärtsprüfung durchführen

aktuelleFunktionsnummer = crc32HashErmitteln("ProductManagement_Worksheet_SelectionChange")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled
Dim i As Long

aufAllgemeinenProgrammfehlerPrüfen 'Hier zur Sicherheit den EPM neu initialisieren, falls aufgrund eines Programmfehlers die globalen Variablen weg sind.

If zeileMarkiert Then GoTo exitHandler

letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)


'Raus, falls man sich im oberen Abschnitt befindet, hier keine weitere Prüfung
If ActiveCell.Row < zentralwertZeileDatenbeginn Then GoTo exitHandler

If IsEmpty(Cells(ActiveCell.Row, zentralwertProduktverwaltungSpalteTyp)) Then
    Cells(ActiveCell.Row, zentralwertProduktverwaltungSpalteTyp).Select
    GoTo exitHandler
End If

'Falls das Feld eine Ausprägung ist, die Datenquelle (auf der Konfiguratorseite) des Listenfeldes für die Ausprägungen bestimmen (anhand des dazugehörigen/linken Merkmals)
Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
If fSpalteInGruppeDerAusprägungen(ActiveCell.Column) Then
    listenfeldElementeBestimmen_Ausprägung 'Zum Merkmal zugehörige Ausprägungen ermitteln und Listenfeld-Datenquelle setzen
End If


'Wenn man sich in einer erlaubten Zelle befindet, dann alles ok und keine weitere Prüfung
If InStr(1, Cells(zentralwertZeileProdukttypen, ActiveCell.Column), "/" & Cells(ActiveCell.Row, zentralwertProduktverwaltungSpalteTyp) & "/") > 0 Then
    GoTo exitHandler
Else
    If Range(letzteAktiveZelle).Row <> Target.Row And Range(letzteAktiveZelle).Column <> Target.Column Then GoTo zurückZurLetztenAktivenZelle
End If
        
    If Range(letzteAktiveZelle).Column <> Target.Column Then 'Bei horizontaler Änderung
        If Range(letzteAktiveZelle).Column < Target.Column Then
            'Vorwärts prüfen
            i = 0
            Do While 1 = 1
                i = i + 1
                If InStr(1, Cells(zentralwertZeileProdukttypen, ActiveCell.Column + i), "/" & Cells(ActiveCell.Row, zentralwertProduktverwaltungSpalteTyp) & "/") > 0 Then
                    If ActiveCell.Column + i < zentralwertLetzteVerwendeteSpalteProduktverwaltung Then
                        Cells(ActiveCell.Row, ActiveCell.Column + i).Select
                        GoTo exitHandler
                    Else
                        GoTo zurückZurLetztenAktivenZelle
                    End If
                End If
                'Rückwärts prüfen, falls am Ende angelangt
                If ActiveCell.Column + i > zentralwertLetzteVerwendeteSpalteProduktverwaltung Then
                    i = 0
                    Do While 1 = 1
                        i = i + 1
                        If InStr(1, Cells(zentralwertZeileProdukttypen, ActiveCell.Column - i), "/" & Cells(ActiveCell.Row, zentralwertProduktverwaltungSpalteTyp) & "/") > 0 And Cells(zentralwertZeileProdukttypen, ActiveCell.Column - i).Columns.Hidden = False Then
                            If ActiveCell.Column - i > 1 Then
                                Cells(ActiveCell.Row, ActiveCell.Column - i).Select
                                GoTo exitHandler
                            Else
                                GoTo zurückZurLetztenAktivenZelle
                            End If
                        End If
                    Loop
                End If
            Loop
        Else
            'Rückwärts prüfen
               i = 0
               Do While 1 = 1
                   i = i + 1
                   If ActiveCell.Column - i > 0 Then
                         If InStr(1, Cells(zentralwertZeileProdukttypen, ActiveCell.Column - i), "/" & Cells(ActiveCell.Row, zentralwertProduktverwaltungSpalteTyp) & "/") > 0 And Cells(zentralwertZeileProdukttypen, ActiveCell.Column - i).Columns.Hidden = False Then
                            If ActiveCell.Column - i > 1 Then
                                Cells(ActiveCell.Row, ActiveCell.Column - i).Select
                                GoTo exitHandler
                            Else
                                GoTo zurückZurLetztenAktivenZelle
                            End If
                        End If
                    Else
                        GoTo zurückZurLetztenAktivenZelle
                    End If
               Loop
        End If
    End If

    If Range(letzteAktiveZelle).Row <> Target.Row Then 'Bei vertikaler Änderung

        If Range(letzteAktiveZelle).Row < Target.Row Then
            'Runter prüfen
            i = 0
            Do While 1 = 1
                i = i + 1
                If ActiveCell.Row + i <= letzteVerwendeteZeile Then 'nur wenn noch nicht letzte verwendete Zeile
                    If InStr(1, Cells(zentralwertZeileProdukttypen, ActiveCell.Column), "/" & Cells(ActiveCell.Row + i, zentralwertProduktverwaltungSpalteTyp) & "/") > 0 Then
                        Cells(ActiveCell.Row + i, ActiveCell.Column).Select
                        GoTo exitHandler
                    End If
                Else
                    GoTo zurückZurLetztenAktivenZelle
                End If
            Loop
        Else
            'Hoch prüfen
            i = 0
            Do While 1 = 1
                i = i + 1
                If ActiveCell.Row - i >= zentralwertZeileDatenbeginn Then 'nur wenn noch nicht erste verwendete Zeile
                    If InStr(1, Cells(zentralwertZeileProdukttypen, ActiveCell.Column), "/" & Cells(ActiveCell.Row - i, zentralwertProduktverwaltungSpalteTyp) & "/") > 0 Then
                        Cells(ActiveCell.Row - i, ActiveCell.Column).Select
                        GoTo exitHandler
                    End If
                Else
                    GoTo zurückZurLetztenAktivenZelle
                End If
                Loop
        End If
    End If
            
exitHandler:
letzteAktiveZelle = ActiveCell.Address
Exit Sub

zurückZurLetztenAktivenZelle:

Rem TBU, LIFE, zum debuggen muss das markieren in weisse Felder möglich sein (z.B. um bedingte Formatierungen zur prüfen)
If letzteAktiveZelle <> "" And Range(letzteAktiveZelle).Row >= zentralwertZeileDatenbeginn Then
    Range(letzteAktiveZelle).Select
Else
    Cells(zentralwertZeileDatenbeginn, 1).Select
End If
GoTo exitHandler

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub
