Attribute VB_Name = "Modul3"
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Public Sub konfigänderungenSyncro(konfigTabellenblatt As String, ByVal Target As Excel.Range, spalteDatenbankname As String)
'Werden Änderungen von bestehenden Werten auf der Konfigseite gemacht, dann werden diese Änderungen mittels dieser Routine auch in der Produktverwaltung (mittels Suchen und Ersetzen) durchgeführt

aktuelleFunktionsnummer = crc32HashErmitteln("konfigänderungenSyncro")
On Error GoTo errHandler

Dim strOld As String
Dim strNew As String
Dim rng As Range
Dim aktiveSpalte As Long
Dim spaltenBezeichnung As String
Dim zuAktualisierendeProduktSpalte As Long
Dim spaltennummerZähler As Long

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = Application.EnableEvents
Application.EnableEvents = False

If Target.Count > 1 Then GoTo exitHandler

If Target.Row <= zentralwertZeileÜberschriften Then GoTo exitHandler

aktiveSpalte = ActiveCell.Column
spaltenBezeichnung = Cells(zentralwertZeileDatenbanknamen, ActiveCell.Column) 'Merken, welche Spaltenbezeichnung (hier der Datenbankname) bearbeitet wird

If spaltenBezeichnung = "" Then GoTo exitHandler

strNew = Target.Value 'neuen Wert merken
Application.Undo
strOld = Target.Value 'alten Wert merken
Target.Value = strNew

'Falls gelöscht wurde
If strOld <> "" And strNew = "" Then 'Fragen, falls der Wert gelöscht wurde
    antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "konfigFrageLöschen"), 4, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Question"))
    If antwort = vbNo Then
        Target.Value = strOld
        GoTo exitHandler
    End If
End If

'Falls geändert wurde
If strOld <> "" And strOld <> strNew Then 'wenn auch wirklich ein neuer Wert eingegeben wurde, dann weiter
    If strNew <> "" Then 'im Falle einer Änderung die Ersetzungen durchführen
    
        Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
        Rem Vorher gings von 1 bis 20 jetzt 1 bis 100
        
        Select Case spaltenBezeichnung
        Case "property" 'Wenn ein Merkmal oder eine Ausprägung geändert wurde, dann sind es ja insg. 40 Spalten. Daher dies separat berücksichtigen
            For spaltennummerZähler = zentralwertProduktverwaltungSpalteMerkmal1 To zentralwertProduktverwaltungSpalteMerkmal100 Step 2
                'Inhalte ersetzen
                zellinhalteErsetzenInSpalte strOld, strNew, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "ProductManagement"), spaltennummerZähler
            Next spaltennummerZähler
        Case "value" 'Wenn ein Merkmal oder eine Ausprägung geändert wurde, dann sind es ja insg. 40 Spalten. Daher dies separat berücksichtigen
            For spaltennummerZähler = zentralwertProduktverwaltungSpalteAusprägung1 To zentralwertProduktverwaltungSpalteAusprägung100 Step 2
                'Inhalte ersetzen
                zellinhalteErsetzenInSpalte strOld, strNew, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "ProductManagement"), spaltennummerZähler
            Next spaltennummerZähler
        Case Else
            'In der Produktverwaltung die Spaltennummer anhand der übereinstimmenden Spaltenbezeichnung suchen
            Set rng = Sheets(zentralwertTabellenblattNameProduktverwaltung).Rows(zentralwertZeileDatenbanknamen).Find(What:=spaltenBezeichnung, SearchDirection:=xlNext, MatchCase:=False)
            If Not rng Is Nothing Then
                zuAktualisierendeProduktSpalte = rng.Column
            Else
                MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "konfigSyncroSpalteFehlt") & vbLf & vbLf & spaltenBezeichnung
                GoTo exitHandler
            End If
            'Inhalte ersetzen
            zellinhalteErsetzenInSpalte strOld, strNew, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "ProductManagement"), zuAktualisierendeProduktSpalte
        End Select
        MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "konfigSyncroÜbernommen")
        GoTo exitHandler
    Else
        MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "konfigSyncroNichtÜbernommen01") & vbLf & vbLf & strOld
        GoTo exitHandler
    End If
End If

exitHandler:
    Application.EnableEvents = alterEnableEventsStatus
    Exit Sub
  
errHandler:
    MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "konfigSyncroNichtÜbernommen02")
    GoTo exitHandler
End Sub


Public Sub zellinhalteErsetzenInSpalte(quellwert, zielwert, tabellenblatt, Spalte)
'Ersetzt den Inhalt von Zellen in einer Spalte eines bestimmten Tabellenblatts

aktuelleFunktionsnummer = crc32HashErmitteln("zellinhalteErsetzenInSpalte")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterScreenUpdateingStatus As Boolean
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.ScreenUpdating = False

Dim altesTabellenblatt As String
Dim verwendeteZeilen As Long
Dim X As Long

altesTabellenblatt = ActiveSheet.Name

verwendeteZeilen = Sheets(tabellenblatt).UsedRange.Rows.Count

On Error Resume Next
With Sheets(tabellenblatt)
    For X = zentralwertZeileDatenbeginn To verwendeteZeilen
        If .Cells(X, Spalte).Value = quellwert Then
            .Cells(X, Spalte) = zielwert
        End If
    Next X
End With
On Error GoTo errHandler

exitHandler:
Application.ScreenUpdating = alterScreenUpdateingStatus
Sheets(altesTabellenblatt).Select
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Function hashwerteAusgeben()

Debug.Print "------------Modul1-------------"
Debug.Print crc32HashErmitteln("zentraleVariablen1")
Debug.Print crc32HashErmitteln("zentraleVariablen2")
Debug.Print crc32HashErmitteln("bedingteFormatierungenNeuSetzen")
Debug.Print crc32HashErmitteln("bedingteFormatierungenNeuSetzenAbExcel2007")
Debug.Print crc32HashErmitteln("bedingteFormatierungenNeuSetzenBisExcel2003")
Debug.Print crc32HashErmitteln("trennspaltenFormatieren")
Debug.Print crc32HashErmitteln("datenüberprüfungNeuSetzen")
Debug.Print crc32HashErmitteln("datenüberprüfungNeuSetzenKategorie")
Debug.Print crc32HashErmitteln("formelnNeuSetzen")
Debug.Print crc32HashErmitteln("parentProductCodeManuellNeuSetzen")

Debug.Print "------------Modul2-------------"
Debug.Print crc32HashErmitteln("standardeinstellungen")
Debug.Print crc32HashErmitteln("spaltenDatenbanknamenProduktverwaltungInArray")
Debug.Print crc32HashErmitteln("spaltenNummernProduktverwaltungDatenbanknamenZurückgeben")
Debug.Print crc32HashErmitteln("spaltenBezeichnungProduktverwaltungDatenbanknamenZurückgeben")
Debug.Print crc32HashErmitteln("spaltenÜberschriftenProduktverwaltungInArray")
Debug.Print crc32HashErmitteln("spaltenNummernProduktverwaltungZurückgeben")
Debug.Print crc32HashErmitteln("spaltenBezeichnungProduktverwaltungZurückgeben")
Debug.Print crc32HashErmitteln("spaltenÜberschriftenKonfigurationInArray")
Debug.Print crc32HashErmitteln("spaltenNummernKonfigurationZurückgeben")
Debug.Print crc32HashErmitteln("spaltenBezeichnungKonfigurationZurückgeben")
Debug.Print crc32HashErmitteln("spaltenÜberschriftenBasisparameterInArray")
Debug.Print crc32HashErmitteln("spaltenNummernBasisparameterZurückgeben")
Debug.Print crc32HashErmitteln("spaltenBezeichnungBasisparameterZurückgeben")
Debug.Print crc32HashErmitteln("zellpositionZurückgeben")
Debug.Print crc32HashErmitteln("IsArrayEmpty")
Debug.Print crc32HashErmitteln("kontextmenue_erweitern")
Debug.Print crc32HashErmitteln("variantenEinblenden")
Debug.Print crc32HashErmitteln("variantenAusblenden")
Debug.Print crc32HashErmitteln("variantenEinblendenAlle")
Debug.Print crc32HashErmitteln("variantenAusblendenAlle")
Debug.Print crc32HashErmitteln("kontextmenue_loeschen")
Debug.Print crc32HashErmitteln("letzteVerwendeteZeileErmitteln")
Debug.Print crc32HashErmitteln("letzteVerwendeteSpalteErmitteln")
Debug.Print crc32HashErmitteln("xZuWert")
Debug.Print crc32HashErmitteln("entferneSuffix")
Debug.Print crc32HashErmitteln("gebeSuffixZurück")
Debug.Print crc32HashErmitteln("aktiviereTabellenblatt")
Debug.Print crc32HashErmitteln("aktiviereTabellenblatt")
Debug.Print crc32HashErmitteln("tabellenblattNameZurückgeben")
Debug.Print crc32HashErmitteln("tabellenblattNummerZurückgeben")
Debug.Print crc32HashErmitteln("zeileMarkiert")
Debug.Print crc32HashErmitteln("spalteMarkiert")
Debug.Print crc32HashErmitteln("aktuelleZelleLeer")
Debug.Print crc32HashErmitteln("seitenwähler")
Debug.Print crc32HashErmitteln("aktuellerEnableEventsStatus")
Debug.Print crc32HashErmitteln("aktuellerScreenUpdatingStatus")
Debug.Print crc32HashErmitteln("spaltenAusEinblenden")
Debug.Print crc32HashErmitteln("spaltenAusEinblendenMerkmaleUndAusprägungen")
Debug.Print crc32HashErmitteln("spaltenAusEinblendenStaffelpreise")
Debug.Print crc32HashErmitteln("spaltenAusEinblendenGruppenpreise")
Debug.Print crc32HashErmitteln("kategorieAuswählenEinAus")
Debug.Print crc32HashErmitteln("zeilenAusEinblendenKopfbereich")
Debug.Print crc32HashErmitteln("aufAllgemeinenProgrammfehlerPrüfen")
Debug.Print crc32HashErmitteln("zeilenhöhenSetzen")
Debug.Print crc32HashErmitteln("substringHäufigkeit")
Debug.Print crc32HashErmitteln("inhaltZuFormelUmwandeln")
Debug.Print crc32HashErmitteln("dateidialogDateinamen")
Debug.Print crc32HashErmitteln("dateinameAusPfad")
Debug.Print crc32HashErmitteln("istProduktzeileLeer")
Debug.Print crc32HashErmitteln("warteschleife")
Debug.Print crc32HashErmitteln("zeigeEPMInfo")
Debug.Print crc32HashErmitteln("maxMöglicheZeilenanzahl")
Debug.Print crc32HashErmitteln("maxMöglicheSpaltenanzahl")
Debug.Print crc32HashErmitteln("formelLokal")
Debug.Print crc32HashErmitteln("VariantenFremdsprachenProduktZugehoerigkeit")
Debug.Print crc32HashErmitteln("istFormularOffen")
Debug.Print crc32HashErmitteln("waitFensterRepaint")
Debug.Print crc32HashErmitteln("listenfeldElementeBestimmen_Ausprägung")
Debug.Print crc32HashErmitteln("zellbereichLeerzeilenKappen")
Debug.Print crc32HashErmitteln("zentraleVariablen1")
Debug.Print crc32HashErmitteln("zentraleVariablen2")
Debug.Print crc32HashErmitteln("bedingteFormatierungenNeuSetzen")
Debug.Print crc32HashErmitteln("bedingteFormatierungenNeuSetzenAbExcel2007")
Debug.Print crc32HashErmitteln("bedingteFormatierungenNeuSetzenBisExcel2003")
Debug.Print crc32HashErmitteln("trennspaltenFormatieren")
Debug.Print crc32HashErmitteln("datenüberprüfungNeuSetzen")
Debug.Print crc32HashErmitteln("datenüberprüfungNeuSetzenKategorie")
Debug.Print crc32HashErmitteln("formelnNeuSetzen")
Debug.Print crc32HashErmitteln("parentProductCodeManuellNeuSetzen")

Debug.Print "------------Modul6-------------"
'Debug.Print crc32HashErmitteln("mehrsprachigkeitBegriffsdefinitionenInArray")
Debug.Print crc32HashErmitteln("mehrsprachigkeitTextrückgabeAktuelleSprache")
Debug.Print crc32HashErmitteln("mehrsprachigkeitBegriffsrückgabe")
Debug.Print crc32HashErmitteln("gewählteSpracheZurückgeben")
Debug.Print crc32HashErmitteln("mehrsprachigkeitTabellenNamenÄndern")
Debug.Print crc32HashErmitteln("toDosNachSprachwechsel")

End Function



Public Function fSpalteInGruppeDerMerkmale(ByVal lSpalte&) As Boolean
  Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
  Dim lIndex&
  
    On Error GoTo errHandler

    Rem ?!? die eingebaute "Filter" Funktion kann nur String Arrays Filtern, aber kein Long-Array
    'lRes = Filter(zentralwertProduktverwaltungsSpaltenMerkmal, (lSpalte))
    
    lIndex = fSearchArrayL(zentralwertProduktverwaltungsSpaltenMerkmal, lSpalte)
    fSpalteInGruppeDerMerkmale = lIndex <> -1

exitHandler:
    Exit Function

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("fSpalteInGruppeDerMerkmale")
    MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
    GoTo exitHandler

End Function


Public Function fSpalteInGruppeDerAusprägungen(ByVal lSpalte&) As Boolean
  Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
  Rem Liefert ein True zurück, wenn die übergebene lSpalte in der Liste der Ausprägungsspalten enthalten ist
  Dim lIndex&
  
    On Error GoTo errHandler
    
    lIndex = fSearchArrayL(zentralwertProduktverwaltungsSpaltenAusprägung, lSpalte)
    fSpalteInGruppeDerAusprägungen = lIndex <> -1

exitHandler:
    Exit Function

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("fSpalteInGruppeDerAusprägungen")
    MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
    GoTo exitHandler

End Function


Public Function fSearchArrayL(arSource() As Long, ByVal lValue) As Long
  Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
  Rem Durchsucht das übergebene Long-Array nach dem übergebenen Suchwert und liefert die Indexnummer zurück
  Dim lIndex&

    On Error GoTo errHandler
    
    fSearchArrayL = -1              'Standardwert für "nicht gefunden"

    For lIndex = LBound(arSource) To UBound(arSource)
    
        If arSource(lIndex) = lValue Then
            fSearchArrayL = lIndex
            Exit For
        End If
    Next


exitHandler:
    Exit Function

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("fSearchArrayL")
    MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
    GoTo exitHandler
  
End Function



Public Sub sAddToArrayL(arSource() As Long, ByVal lValue)
  Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
  Rem diese Funktion fügt dem übergebenen QuellArray (vom Typ Long) arSource einen Wert an

    On Error GoTo errHandler
    
    If fArrUnInitializedL(arSource) Then
        ReDim arSource(0)
    Else
        ReDim Preserve arSource(0 To UBound(arSource) + 1)
    End If
    
    arSource(UBound(arSource)) = lValue

exitHandler:
    Exit Sub

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("sAddToArrayL")
    MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
    GoTo exitHandler
End Sub


Public Function fArrUnInitializedL(arSource() As Long) As Boolean
  Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
  Rem Prüft ob das übergebene Long-Array überhaupt dimensioniert ist (return=false). Falls nicht wird ein
  Rem   true zurück geliefert.
  Dim lowBound&

    On Error Resume Next
    lowBound = LBound(arSource)
    
    If Err.Number <> 0 Then
        fArrUnInitializedL = True
    End If

End Function
