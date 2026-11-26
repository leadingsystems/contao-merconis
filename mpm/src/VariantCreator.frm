VERSION 5.00
Begin {C62A69F0-16DC-11CE-9E98-00AA00574A4F} VariantCreator 
   ClientHeight    =   11160
   ClientLeft      =   45
   ClientTop       =   375
   ClientWidth     =   17610
   OleObjectBlob   =   "VariantCreator.frx":0000
   ShowModal       =   0   'False
   StartUpPosition =   1  'Fenstermitte
End
Attribute VB_Name = "VariantCreator"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Private Sub bildHilfe_MouseUp(ByVal Button As Integer, ByVal Shift As Integer, ByVal X As Single, ByVal Y As Single)
'Erstellen der Varianten

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_bildHilfe_MouseUp")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled
    
    VariantCreatorHelp.Show

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorfehlerpassiertvariantenprüfen"), vbOKOnly
GoTo exitHandler

End Sub

Public Sub buttonErstellen_Click()
'Erstellen der Varianten

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_buttonErstellen_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus

Dim erstellungsInfoanzeigen As String
Dim zuvorErstellteVariantenUndFremdsprachen As Long
Dim aktuelleZeile As Long
Dim zeilenzähler As Long
Dim erstellteVariantenHinweis As String

'Plausibilitätsprüfungen

'Preis
If Trim(VariantCreator.variantenwertPreis) <> "" And Trim(VariantCreator.variantenwertPreisTyp) = "" Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Price: Price type")
    GoTo exitHandler
End If
If VariantCreator.variantenwertCheckboxHauptartikelPreisVerwenden <> True And (Trim(VariantCreator.variantenwertPreis) = "" Or Trim(VariantCreator.variantenwertPreisTyp) = "") Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Price") & " oder " & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Price: price type")
    GoTo exitHandler
End If

'das hier nur prüfen, wenn Staffelpreis anwenden angeklickt wurde
If VariantCreator.variantenwertCheckboxStaffelpreisAnwenden = True And VariantCreator.variantenwertCheckboxStaffelpreisHauptartikelPreisVerwenden <> True Then
    If Trim(VariantCreator.variantenwertStaffelpreisTyp) = "" Then
        MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Scale price type")
        GoTo exitHandler
    End If
    If Trim(VariantCreator.variantenwertStaffelpreisMengenermittlungTyp) = "" Then
        MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Quantity detection method")
        GoTo exitHandler
    End If
    If Trim(VariantCreator.variantenwertStaffelpreisPreis) = "" Then
        MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Scale price")
        GoTo exitHandler
    End If
End If

'das hier nur prüfen, wenn Staffelpreis anwenden nicht angeklickt wurde
If VariantCreator.variantenwertCheckboxStaffelpreisAnwenden <> True And (VariantCreator.variantenwertCheckboxStaffelpreisKonfigsTrennen = True Or Trim(VariantCreator.variantenwertStaffelpreisTyp) <> "" Or Trim(VariantCreator.variantenwertStaffelpreisMengenermittlungTyp) <> "" Or Trim(VariantCreator.variantenwertStaffelpreisSchlüsselwort) <> "" Or VariantCreator.variantenwertCheckboxStaffelpreisHauptartikelPreisVerwenden = True Or Trim(VariantCreator.variantenwertStaffelpreisPreis) <> "") Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Use scale price")
    GoTo exitHandler
End If

'Alter Preis
If Trim(VariantCreator.variantenwertAlterPreis) <> "" And Trim(VariantCreator.variantenwertAlterPreisTyp) = "" Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Old price: Price type")
    GoTo exitHandler
End If

'Gewicht
If Trim(VariantCreator.variantenwertGewicht) <> "" And Trim(VariantCreator.variantenwertGewichtTyp) = "" Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Weight type")
    GoTo exitHandler
End If
If VariantCreator.variantenwertCheckboxHauptartikelGewichtVerwenden <> True And (Trim(VariantCreator.variantenwertGewicht) <> "" And Trim(VariantCreator.variantenwertGewichtTyp) = "") Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Weight") & " oder " & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Weight type")
    GoTo exitHandler
End If

'Lieferzeit
If VariantCreator.variantenwertCheckboxHauptartikelLagerbestandLieferzeitVerwenden <> True And Trim(VariantCreator.variantenwertLieferzeit) = "" Then
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFeldFehlt") & vbLf & vbLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Delivery time")
    GoTo exitHandler
End If

'In einer Schleife die markierten Zeilen durchgehen (Benutzer kann ja auch auf einmal Varianten für zig Produkte erstellen)
zuvorErstellteVariantenUndFremdsprachen = 0
If Selection.Rows.Count > 1 Then
    erstellungsInfoanzeigen = "nein"
Else
    erstellungsInfoanzeigen = "ja"
End If

For zeilenzähler = 0 To Selection.Rows.Count - 1
    aktuelleZeile = Selection.Row + zuvorErstellteVariantenUndFremdsprachen
    zuvorErstellteVariantenUndFremdsprachen = variantCreatorVariantenErstellen(aktuelleZeile, erstellungsInfoanzeigen) + 1 'Varianten erstellen
    If zuvorErstellteVariantenUndFremdsprachen < 0 Then 'wenn Fehler zurückgegeben wurde
        erstellteVariantenHinweis = Replace(Replace(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorAbbruchInZeile"), "####", aktuelleZeile), "###", zeilenzähler)
        MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFehlerPassiert") & vbLf & vbLf & erstellteVariantenHinweis, vbOKOnly
        GoTo exitHandler
    End If
Next zeilenzähler

'MsgBox "Es wurden Varianten für insgesamt " & zeilenzähler & " Produkte erstellt."

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFehlerPassiertVariantenPrüfen"), vbOKOnly
GoTo exitHandler

End Sub

Public Function variantCreatorVariantenErstellen(aktuellesProduktZeile As Long, erstellungsInfoanzeigen As String) As Long

'Erstellen der Varianten

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_variantCreatorVariantenErstellen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

Dim i1 As Long, i2 As Long, i3 As Long, i4 As Long, i5 As Long, i6 As Long, i7 As Long, i8 As Long
Dim größeAusprägungenArray As Variant

'Temporäres Arrays, die für die eigentliche Kombinatorik verwendet werden
Dim arrayAusprägungenTemp01(9999, 2) As Variant
Dim arrayAusprägungenTemp02(9999, 2) As Variant
Dim arrayAusprägungenTemp03(9999, 2) As Variant
Dim arrayAusprägungenTemp04(9999, 2) As Variant
Dim arrayAusprägungenTemp05(9999, 2) As Variant
Dim arrayAusprägungenTemp06(9999, 2) As Variant
Dim arrayAusprägungenTemp07(9999, 2) As Variant
Dim arrayAusprägungenTemp08(9999, 2) As Variant

Dim zeilenzähler As Long
Dim neueZeileVariante As Long
Dim neueZeileVarianteFremdsprache As Long
Dim fremdsprache As String
Dim anzahlGewählteMerkmale As Long
Dim gewähltesMerkmal_altX As String
Dim gewählteAusprägung As String
Dim ausprägungGewähltStatus As Variant
Dim gewähltesMerkmal As String
Dim arrayzähler As Long
Dim array01Zähler As Long
Dim array02Zähler As Long
Dim array03Zähler As Long
Dim array04Zähler As Long
Dim anzahlNeuerVarianten As Long
Dim präfixzähler As Long
Dim suffixzähler As Long
Dim aktuelleAnzahlAusprägungen As Long
Dim anzahlNeuerVariantenFremdsprache As Long
Dim fremdsprachenArray As Variant
Dim fremdsprachenZähler As Long
Dim X As Long

'Plausibilitätsprüfungen machen
'If zeileMarkiert() = False Then GoTo errZeileNichtMarkiert 'falls nicht nur 1 einzige Zeile markiert (keine oder mehrere), dann raus
If Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteTyp) <> mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product") And Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteTyp) <> mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") Then GoTo errFalscherProdukttypMarkiert

größeAusprägungenArray = UBound(arrayAusprägungenTemp01)

anzahlGewählteMerkmale = 0
gewähltesMerkmal_altX = ""
'Anzahl der ausgewählten Merkmale ermitteln
For X = 1 To UBound(VariantCreatorAusprägungenArray)
    gewählteAusprägung = VariantCreatorAusprägungenArray(X, 1)
    If gewählteAusprägung <> "" Then
        ausprägungGewähltStatus = formularCheckboxStatus("VariantCreator", gewählteAusprägung & "_checkbox")
        gewähltesMerkmal = VariantCreatorAusprägungenArray(X, 2)
        If ausprägungGewähltStatus = True And gewähltesMerkmal <> gewähltesMerkmal_altX Then
            anzahlGewählteMerkmale = anzahlGewählteMerkmale + 1
            gewähltesMerkmal_altX = gewähltesMerkmal
        End If
    End If
Next X

'Prüfen, ob mindestens eines und maximal 8 Merkmale ausgewählt wurden
If anzahlGewählteMerkmale < 1 Then GoTo errAnzahlGewählteMerkmaleZuGering
If anzahlGewählteMerkmale > 8 Then GoTo errAnzahlGewählteMerkmaleZuHoch

gewähltesMerkmal_altX = ""
arrayzähler = 0
'Merkmale und Ausprägungen in temporäre Arrays schreiben
For X = 1 To UBound(VariantCreatorAusprägungenArray)
    gewählteAusprägung = VariantCreatorAusprägungenArray(X, 1)
    If gewählteAusprägung <> "" Then
        ausprägungGewähltStatus = formularCheckboxStatus("VariantCreator", gewählteAusprägung & "_checkbox")
        gewähltesMerkmal = VariantCreatorAusprägungenArray(X, 2)
        If ausprägungGewähltStatus = True Then
            If gewähltesMerkmal <> gewähltesMerkmal_altX Then
                arrayzähler = arrayzähler + 1
                gewähltesMerkmal_altX = gewähltesMerkmal
            End If
            Select Case arrayzähler
            Case 1
                array01Zähler = array01Zähler + 1
                arrayAusprägungenTemp01(array01Zähler, 1) = gewählteAusprägung
                arrayAusprägungenTemp01(array01Zähler, 2) = gewähltesMerkmal
            Case 2
                array02Zähler = array02Zähler + 1
                arrayAusprägungenTemp02(array02Zähler, 1) = gewählteAusprägung
                arrayAusprägungenTemp02(array02Zähler, 2) = gewähltesMerkmal
            Case 3
                array03Zähler = array03Zähler + 1
                arrayAusprägungenTemp03(array03Zähler, 1) = gewählteAusprägung
                arrayAusprägungenTemp03(array03Zähler, 2) = gewähltesMerkmal
            Case 4
                array04Zähler = array04Zähler + 1
                arrayAusprägungenTemp04(array04Zähler, 1) = gewählteAusprägung
                arrayAusprägungenTemp04(array04Zähler, 2) = gewähltesMerkmal
            End Select
        End If
    End If
Next X


anzahlNeuerVarianten = 0
präfixzähler = Val(VariantCreator.variantenwertArtikelnummerPräfixStart)
suffixzähler = Val(VariantCreator.variantenwertArtikelnummerSuffixStart)

anzahlNeuerVariantenFremdsprache = 0

For i1 = 1 To größeAusprägungenArray
    If (arrayAusprägungenTemp01(i1, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp01(i1, 1) & "_checkbox") = True) Or i1 = größeAusprägungenArray Then
        For i2 = 1 To größeAusprägungenArray
            If (arrayAusprägungenTemp02(i2, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp02(i2, 1) & "_checkbox") = True) Or i2 = größeAusprägungenArray Then
                For i3 = 1 To größeAusprägungenArray
                    If (arrayAusprägungenTemp03(i3, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp03(i3, 1) & "_checkbox") = True) Or i3 = größeAusprägungenArray Then
                        For i4 = 1 To größeAusprägungenArray
                            If (arrayAusprägungenTemp04(i4, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp04(i4, 1) & "_checkbox") = True) Or i4 = größeAusprägungenArray Then
                                For i5 = 1 To größeAusprägungenArray
                                    If (arrayAusprägungenTemp05(i5, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp05(i5, 1) & "_checkbox") = True) Or i5 = größeAusprägungenArray Then
                                        For i6 = 1 To größeAusprägungenArray
                                            If (arrayAusprägungenTemp06(i6, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp06(i6, 1) & "_checkbox") = True) Or i6 = größeAusprägungenArray Then
                                                For i7 = 1 To größeAusprägungenArray
                                                    If (arrayAusprägungenTemp07(i7, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp07(i7, 1) & "_checkbox") = True) Or i7 = größeAusprägungenArray Then
                                                        For i8 = 1 To größeAusprägungenArray
                                                            If (arrayAusprägungenTemp08(i8, 1) <> "" And formularCheckboxStatus("VariantCreator", arrayAusprägungenTemp08(i8, 1) & "_checkbox") = True) Or i8 = größeAusprägungenArray Then
                                                                    
                                                                'Prüfen, ob die aktuelle Kombination korrekt ist (die Routine spuckt z. B. auch einzelne Ausprägungen aus)
                                                                'Korrekt ist sie dann, wenn die Anzahl der Ausprägungen der Anzahl der verwendeten Merkmale entspricht
                                                                aktuelleAnzahlAusprägungen = 0
                                                                If arrayAusprägungenTemp01(i1, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If arrayAusprägungenTemp02(i2, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If arrayAusprägungenTemp03(i3, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If arrayAusprägungenTemp04(i4, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If arrayAusprägungenTemp05(i5, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If arrayAusprägungenTemp06(i6, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If arrayAusprägungenTemp07(i7, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If arrayAusprägungenTemp08(i8, 1) <> "" Then aktuelleAnzahlAusprägungen = aktuelleAnzahlAusprägungen + 1
                                                                If anzahlGewählteMerkmale = aktuelleAnzahlAusprägungen Then 'Wenn Anzahl korrekt, dann Variante erstellen
                                                                    'Variante erstellen (neue Zeile und Werte einfügen)
                                                                    Select Case Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteTyp) 'je nach Produkttyp entsprechende Felder schreiben
                                                                    Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product") ' Falls für ein normales (kein Fremdsprachenprodukt) eine Variante angelegt werden soll
                                                                        neueZeileVariante = aktuellesProduktZeile + 1 + anzahlNeuerVarianten + anzahlNeuerVariantenFremdsprache
                                                                        Rows(neueZeileVariante).Insert ' Leere Zeile nach der aktuell markierten einfügen
                                                                        
                                                                        If variantCreatorVarianteErstellen(aktuellesProduktZeile, neueZeileVariante, arrayAusprägungenTemp01(i1, 2), arrayAusprägungenTemp01(i1, 1), arrayAusprägungenTemp02(i2, 2), arrayAusprägungenTemp02(i2, 1), arrayAusprägungenTemp03(i3, 2), arrayAusprägungenTemp03(i3, 1), arrayAusprägungenTemp04(i4, 2), arrayAusprägungenTemp04(i4, 1), arrayAusprägungenTemp05(i5, 2), arrayAusprägungenTemp05(i5, 1), arrayAusprägungenTemp06(i6, 2), arrayAusprägungenTemp06(i6, 1), arrayAusprägungenTemp07(i7, 2), arrayAusprägungenTemp07(i7, 1), arrayAusprägungenTemp08(i8, 2), arrayAusprägungenTemp08(i8, 1), präfixzähler, suffixzähler) <> False Then
                                                                            anzahlNeuerVarianten = anzahlNeuerVarianten + 1
                                                                            'Präfix und Suffix hochzählen, falls diese benutzt werden
                                                                            If präfixzähler <> 0 Then präfixzähler = präfixzähler + 1
                                                                            If suffixzähler <> 0 Then suffixzähler = suffixzähler + 1
                                                                            
                                                                            'Auf Wunsch noch die Fremdsprache(n) anlegen
                                                                            If Trim(VariantCreator.variantenwertTextfeldFremdsprachen) <> "" Then
                                                                                fremdsprachenArray = Split(Trim(VariantCreator.variantenwertTextfeldFremdsprachen), ",")
                                                                                For fremdsprachenZähler = 0 To UBound(fremdsprachenArray)
                                                                                    neueZeileVarianteFremdsprache = aktuellesProduktZeile + 1 + anzahlNeuerVarianten + anzahlNeuerVariantenFremdsprache
                                                                                    Rows(neueZeileVarianteFremdsprache).Insert ' Leere Zeile nach der aktuell markierten einfügen
                                                                                    fremdsprache = Trim(fremdsprachenArray(fremdsprachenZähler))
                                                                                    If fremdsprache <> "" Then
                                                                                        If variantCreatorVarianteFremdspracheErstellen(neueZeileVariante, neueZeileVarianteFremdsprache, fremdsprache, präfixzähler, suffixzähler) = False Then GoTo errHandler
                                                                                    End If
                                                                                    anzahlNeuerVariantenFremdsprache = anzahlNeuerVariantenFremdsprache + 1
                                                                                Next fremdsprachenZähler
                                                                            End If
                                                                        Else
                                                                            GoTo errHandler
                                                                        End If
                                                                    Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant") 'Falls für ein Variante eine Varianten-Fremdsprache angelegt werden soll
                                                                        fremdsprachenArray = Split(Trim(VariantCreator.variantenwertTextfeldFremdsprachen), ",")
                                                                        For fremdsprachenZähler = 0 To UBound(fremdsprachenArray)
                                                                            neueZeileVarianteFremdsprache = aktuellesProduktZeile + 1 + anzahlNeuerVarianten + anzahlNeuerVariantenFremdsprache
                                                                            Rows(neueZeileVarianteFremdsprache).Insert ' Leere Zeile nach der aktuell markierten einfügen
                                                                            fremdsprache = Trim(fremdsprachenArray(fremdsprachenZähler))
                                                                            If fremdsprache <> "" Then
                                                                                If variantCreatorVarianteFremdspracheErstellen(aktuellesProduktZeile, neueZeileVarianteFremdsprache, fremdsprache, präfixzähler, suffixzähler) = False Then GoTo errHandler
                                                                            End If
                                                                            anzahlNeuerVariantenFremdsprache = anzahlNeuerVariantenFremdsprache + 1
                                                                        Next fremdsprachenZähler
                                                                    End Select
                                                                End If
                                                            End If
                                                        Next i8
                                                    End If
                                                Next i7
                                            End If
                                        Next i6
                                    End If
                                Next i5
                            End If
                        Next i4
                    End If
                Next i3
            End If
        Next i2
    End If
Next i1


'Die neu erstellten Varianten (ggf. inkl. Fremdsprachvarianten) gruppieren
If anzahlNeuerVarianten > 0 And erstellungsInfoanzeigen = "ja" Then
    MsgBox Replace(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorVariantenErstellt"), "#anzahlNeuerVarianten#", anzahlNeuerVarianten) & IIf(anzahlNeuerVariantenFremdsprache > 0, Replace(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorVariantenErstelltZusätzlichSprache"), "#anzahlNeuerVariantenFremdsprache#", anzahlNeuerVariantenFremdsprache), ""), vbOKOnly
Else
    If anzahlNeuerVariantenFremdsprache > 0 Then
        If erstellungsInfoanzeigen = "ja" Then
            MsgBox Replace(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFremdsprachenHinzugefügt"), "#anzahlNeuerVariantenFremdsprache#", anzahlNeuerVariantenFremdsprache), vbOKOnly
        End If
    End If
End If
If anzahlNeuerVarianten + anzahlNeuerVariantenFremdsprache = 0 Then
    If erstellungsInfoanzeigen = "ja" Then
        MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorKeineVariantenErstellt"), vbOKOnly
    End If
End If

'Wenn Varianten/Fremdsprachen erstellt wurden, dann Formeln neu setzen, da diese beim Erstellen nicht automatisch hinterlegt werden
If anzahlNeuerVarianten + anzahlNeuerVariantenFremdsprache > 0 Then
    formelnNeuSetzen
End If

exitHandler:
variantCreatorVariantenErstellen = anzahlNeuerVarianten + anzahlNeuerVariantenFremdsprache
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Cells(aktuellesProduktZeile, 1).Select
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorfehlerpassiertvariantenprüfen"), vbOKOnly
variantCreatorVariantenErstellen = -999
GoTo exitHandler

errAnzahlGewählteMerkmaleZuGering:
MsgBox "Sie haben nur " & anzahlGewählteMerkmale & " Merkmal(e) ausgewählt!", vbOKOnly
MsgBox Replace(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorZuWenigMerkmaleAusgewählt"), "#anzahlGewählteMerkmale#", anzahlGewählteMerkmale), vbOKOnly
variantCreatorVariantenErstellen = -999
GoTo exitHandler

errAnzahlGewählteMerkmaleZuHoch:
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorZuVieleMerkmaleAusgewählt"), vbOKOnly
variantCreatorVariantenErstellen = -999
GoTo exitHandler

errZeileNichtMarkiert:
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorKeineZeileMarkiert"), vbOKOnly
variantCreatorVariantenErstellen = -999
GoTo exitHandler

errFalscherProdukttypMarkiert:
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorFalscherProdukttyp"), vbOKOnly
variantCreatorVariantenErstellen = -999
GoTo exitHandler

End Function

Public Function variantCreatorVarianteErstellen(aktuellesProduktZeile As Long, neueZeile As Long, merkmal01, ausprägung01, merkmal02, ausprägung02, merkmal03, ausprägung03, merkmal04, ausprägung04, merkmal05, ausprägung05, merkmal06, ausprägung06, merkmal07, ausprägung07, merkmal08, ausprägung08, präfixzähler, suffixzähler)
'Erstellen der Variante

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_variantCreatorVarianteErstellen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus

Dim fremdsprache As String
Dim neueVariantenArtikelnummerPräfix As String
Dim neueVariantenArtikelnummerSuffix As String
Dim neueVarianteArtikelnummer As String

Cells(neueZeile, zentralwertProduktverwaltungSpalteImportsperre) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteImportsperre)
Cells(neueZeile, zentralwertProduktverwaltungSpalteLöschen) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteLöschen)
Cells(neueZeile, zentralwertProduktverwaltungSpalteVeröffentlichen) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteVeröffentlichen)
Cells(neueZeile, zentralwertProduktverwaltungSpalteTyp).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant"))

'Artikelnummer der Variante zusammenstellen
If präfixzähler <> 0 Then neueVariantenArtikelnummerPräfix = Trim(str(präfixzähler)) & Trim(VariantCreator.variantenwertArtikelnummerTrennzeichen)
If suffixzähler <> 0 Then neueVariantenArtikelnummerSuffix = Trim(VariantCreator.variantenwertArtikelnummerTrennzeichen) & Trim(str(suffixzähler))
If VariantCreator.variantenwertCheckboxHauptartikelnummerVerwenden = True Then
    neueVarianteArtikelnummer = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteArtikelnummer)
    Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer) = neueVariantenArtikelnummerPräfix & Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteArtikelnummer) & neueVariantenArtikelnummerSuffix
Else
    If Trim(VariantCreator.variantenwertCheckboxArtikelnummerIstFormel) <> True Then 'Falls Flag für Formel nicht angehakt ist, dann als normalen Text einfügen und zuvor das Zellenformat auf "Text" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer).NumberFormat = "@"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer) = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertArtikelnummer), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
    Else 'Falls Flag für Formel  angehakt ist, dann als Formel einfügen und zuvor das Zellenformat auf "Standard" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer).NumberFormat = "General"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer).FormulaLocal = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertArtikelnummer), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer).Copy
        Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer).PasteSpecial Paste:=xlPasteValues, Operation:=xlNone, SkipBlanks:=False, Transpose:=False
        Cells(neueZeile, zentralwertProduktverwaltungSpalteArtikelnummer).NumberFormat = "@"
    End If
End If

Cells(neueZeile, zentralwertProduktverwaltungSpalteSprache) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteSprache)
Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteBezeichnung).Copy Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung) 'Per ".copy", damit evtl. Formatierungen mit übernommen werden

'Merkmale und Ausprägungen einfügen
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal1) = merkmal01
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung1) = ausprägung01
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal2) = merkmal02
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung2) = ausprägung02
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal3) = merkmal03
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung3) = ausprägung03
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal4) = merkmal04
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung4) = ausprägung04
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal5) = merkmal05
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung5) = ausprägung05
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal6) = merkmal06
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung6) = ausprägung06
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal7) = merkmal07
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung7) = ausprägung07
Cells(neueZeile, zentralwertProduktverwaltungSpalteMerkmal8) = merkmal08
Cells(neueZeile, zentralwertProduktverwaltungSpalteAusprägung8) = ausprägung08


'Produktbezeichnung
If VariantCreator.variantenwertBezeichnung <> "" Then
    If Trim(VariantCreator.variantenwertCheckboxBezeichnungIstFormel) <> True Then 'Falls Flag für Formel nicht angehakt ist, dann als normalen Text einfügen und zuvor das Zellenformat auf "Text" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung).NumberFormat = "@"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung) = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertBezeichnung), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
    Else 'Falls Flag für Formel  angehakt ist, dann als Formel einfügen und zuvor das Zellenformat auf "Standard" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung).NumberFormat = "General"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung).FormulaLocal = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertBezeichnung), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung).Copy
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung).PasteSpecial Paste:=xlPasteValues, Operation:=xlNone, SkipBlanks:=False, Transpose:=False
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung).NumberFormat = "@"
    End If
End If

'Produktbeschreibung
If VariantCreator.variantenwertBeschreibung <> "" Then
    If Trim(VariantCreator.variantenwertCheckboxBeschrIstFormel) <> True Then 'Falls Flag für Formel nicht angehakt ist, dann als normalen Text einfügen und zuvor das Zellenformat auf "Text" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung).NumberFormat = "@"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung) = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertBeschreibung), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
    Else 'Falls Flag für Formel  angehakt ist, dann als Formel einfügen und zuvor das Zellenformat auf "Standard" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung).NumberFormat = "General"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung).FormulaLocal = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertBeschreibung), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung).Copy
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung).PasteSpecial Paste:=xlPasteValues, Operation:=xlNone, SkipBlanks:=False, Transpose:=False
        Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung).NumberFormat = "@"
    End If
End If

'Produktkurzbeschreibung
If VariantCreator.variantenwertKurzbeschreibung <> "" Then
    If Trim(VariantCreator.variantenwertCheckboxKurzbeschrIstFormel) <> True Then 'Falls Flag für Formel nicht angehakt ist, dann als normalen Text einfügen und zuvor das Zellenformat auf "Text" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung).NumberFormat = "@"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung) = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertKurzbeschreibung), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
    Else 'Falls Flag für Formel  angehakt ist, dann als Formel einfügen und zuvor das Zellenformat auf "Standard" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung).NumberFormat = "General"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung).FormulaLocal = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertKurzbeschreibung), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung).Copy
        Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung).PasteSpecial Paste:=xlPasteValues, Operation:=xlNone, SkipBlanks:=False, Transpose:=False
        Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung).NumberFormat = "@"
    End If
End If

'Preis
If Trim(VariantCreator.variantenwertPreis) <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpaltePreis) = CDbl(VariantCreator.variantenwertPreis) 'In Zahl umwandeln
Else
    If VariantCreator.variantenwertCheckboxHauptartikelPreisVerwenden = True Then
        Cells(neueZeile, zentralwertProduktverwaltungSpaltePreis) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpaltePreis)
        Cells(neueZeile, zentralwertProduktverwaltungSpaltePreisTyp).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price"))
    End If
End If
If VariantCreator.variantenwertPreisTyp <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpaltePreisTyp).Formula = inhaltZuFormelUmwandeln(VariantCreator.variantenwertPreisTyp)
End If

'Staffelpreise (nur, wenn Staffelpreise anwenden angehakt wurde)
If VariantCreator.variantenwertCheckboxStaffelpreisAnwenden = True Then
    'Staffelpreise, anwenden
    If VariantCreator.variantenwertCheckboxStaffelpreisAnwenden = True Then
            Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisAnwenden) = 1
    End If
    'StaffelPreis, alles übernehmen, falls von Hauptprodukt übernommen werden soll
    If VariantCreator.variantenwertCheckboxStaffelpreisHauptartikelPreisVerwenden = True Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennen) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennen)
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationen) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationen)
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisArt).Formula = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteStaffelpreisArt).Formula
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethode).Formula = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethode).Formula
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwort) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwort)
    End If
    'Staffelpreis, Preis-Mengen-Kombinationen (unbedint nach "alles übernehmen" bringen, da dies bei expliziter Auswahl nochmals geschrieben wird)
    If Trim(VariantCreator.variantenwertStaffelpreisPreis) <> "" Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationen) = Trim(VariantCreator.variantenwertStaffelpreisPreis)
    End If
    'Staffelpreis, Konfigs trennen (unbedint nach "alles übernehmen" bringen, da dies bei expliziter Auswahl nochmals geschrieben wird)
    If VariantCreator.variantenwertCheckboxStaffelpreisKonfigsTrennen = True Then
            Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungKonfigurationenTrennen) = 1
    End If
    'Staffelpreis, Typ (unbedint nach "alles übernehmen" bringen, da dies bei expliziter Auswahl nochmals geschrieben wird)
    If Trim(VariantCreator.variantenwertStaffelpreisTyp) <> "" Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisArt).Formula = inhaltZuFormelUmwandeln(VariantCreator.variantenwertStaffelpreisTyp)
    End If
    'Staffelpreis, Methode für Mengenermittlung (unbedint nach "alles übernehmen" bringen, da dies bei expliziter Auswahl nochmals geschrieben wird)
    If Trim(VariantCreator.variantenwertStaffelpreisMengenermittlungTyp) <> "" Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethode).Formula = inhaltZuFormelUmwandeln(VariantCreator.variantenwertStaffelpreisMengenermittlungTyp)
    End If
    'Staffelpreis, Staffelpreis-Schlüsselwort (unbedint nach "alles übernehmen" bringen, da dies bei expliziter Auswahl nochmals geschrieben wird)
    If Trim(VariantCreator.variantenwertStaffelpreisSchlüsselwort) <> "" Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteStaffelpreisSchlüsselwort) = VariantCreator.variantenwertStaffelpreisSchlüsselwort
    End If
End If


'Alter Preis
If VariantCreator.variantenwertAlterPreis <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteAlterPreis) = CDbl(VariantCreator.variantenwertAlterPreis) 'In Zahl umwandeln
Else
    If VariantCreator.variantenwertCheckboxHauptartikelAlterPreisVerwenden = True Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteAlterPreis) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteAlterPreis)
        Cells(neueZeile, zentralwertProduktverwaltungSpalteAlterPreisTyp).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price"))
    End If
End If
'Alter Preis-Typ
If VariantCreator.variantenwertAlterPreisTyp <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteAlterPreisTyp).Formula = inhaltZuFormelUmwandeln(VariantCreator.variantenwertAlterPreisTyp)
End If

'Gewicht
If VariantCreator.variantenwertGewicht <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteGewicht) = CDbl(VariantCreator.variantenwertGewicht) 'In Zahl umwandeln
Else
    If VariantCreator.variantenwertCheckboxHauptartikelGewichtVerwenden = True Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteGewicht) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteGewicht)
        Cells(neueZeile, zentralwertProduktverwaltungSpalteGewichtTyp).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed weight"))
    End If
End If
'Gewicht-Typ
If VariantCreator.variantenwertGewichtTyp <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteGewichtTyp).Formula = inhaltZuFormelUmwandeln(VariantCreator.variantenwertGewichtTyp)
End If

'Lagerbestandsänderung
If VariantCreator.variantenwertLagerbestandsänderung <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteLagerbestandsänderung) = CDbl(VariantCreator.variantenwertLagerbestandsänderung) 'In Zahl umwandeln
Else
    If VariantCreator.variantenwertCheckboxHauptartikelLagerbestandLieferzeitVerwenden = True Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteLagerbestandsänderung) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteLagerbestandsänderung)
    End If
End If

'Lieferzeit
If VariantCreator.variantenwertLieferzeit <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteLagerbestandUndLieferzeit) = VariantCreator.variantenwertLieferzeit
Else
    If VariantCreator.variantenwertCheckboxHauptartikelLagerbestandLieferzeitVerwenden = True Then
        Cells(neueZeile, zentralwertProduktverwaltungSpalteLagerbestandUndLieferzeit) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteLagerbestandUndLieferzeit)
    End If
End If


Rem 05.06.2023, TBU, 4 neue Felder. Hier für "Vorbestellungen erlaubt"
If VariantCreator.variantenwertCheckboxVorbestellungErlaubt = True Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteVorbestellungErlaubt) = 1
End If

Rem "Einstellungen zu Lagerbestand und Lieferzeit in Vorbestellungsphase"
If VariantCreator.variantenwertLagerbestandUndLieferzeitInVorbestellungsphase <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteLagerbestandUndLieferzeitInVorbestellung) = VariantCreator.variantenwertLagerbestandUndLieferzeitInVorbestellungsphase
End If

Rem "Verfügbar ab"
If VariantCreator.variantenwertVerfuegbarAb <> "" Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteVerfuegbarAb) = VariantCreator.variantenwertVerfuegbarAb
End If

Rem "Verfügbarkeits-Einstellungen des übergeordneten Produkts überschreiben"
If VariantCreator.variantenwertCheckboxVerfuegbarkeitsEinstellungenBeimUebergeordnetenProduktUeberschreiben = True Then
    Cells(neueZeile, zentralwertProduktverwaltungSpalteVerfuegbarkeitsEinstellungenParentUeberschreiben) = 1
End If


'Hauptbild
If VariantCreator.variantenwertHauptbild <> True Then
    If Trim(VariantCreator.variantenwertCheckboxHauptbildIstFormel) <> True Then 'Falls Flag für Formel nicht angehakt ist, dann als normalen Text einfügen und zuvor das Zellenformat auf "Text" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteHauptbild).NumberFormat = "@"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteHauptbild) = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertHauptbild), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
    Else 'Falls Flag für Formel  angehakt ist, dann als Formel einfügen und zuvor das Zellenformat auf "Standard" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteHauptbild).NumberFormat = "General"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteHauptbild).FormulaLocal = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertHauptbild), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteHauptbild).Copy
        Cells(neueZeile, zentralwertProduktverwaltungSpalteHauptbild).PasteSpecial Paste:=xlPasteValues, Operation:=xlNone, SkipBlanks:=False, Transpose:=False
        Cells(neueZeile, zentralwertProduktverwaltungSpalteHauptbild).NumberFormat = "@"
    End If
End If

'Weitere Bilder
If VariantCreator.variantenwertWeitereBilder <> True Then
    If Trim(VariantCreator.variantenwertCheckboxWeitereBilderIstFormel) <> True Then 'Falls Flag für Formel nicht angehakt ist, dann als normalen Text einfügen und zuvor das Zellenformat auf "Text" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteWeitereBilder).NumberFormat = "@"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteWeitereBilder) = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertWeitereBilder), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
    Else 'Falls Flag für Formel  angehakt ist, dann als Formel einfügen und zuvor das Zellenformat auf "Standard" setzen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteWeitereBilder).NumberFormat = "General"
        Cells(neueZeile, zentralwertProduktverwaltungSpalteWeitereBilder).FormulaLocal = variantCreatorPlatzhalterErsetzen(Trim(VariantCreator.variantenwertWeitereBilder), aktuellesProduktZeile, präfixzähler, suffixzähler) 'Platzhalter-Ersetzungen vornehmen
        Cells(neueZeile, zentralwertProduktverwaltungSpalteWeitereBilder).Copy
        Cells(neueZeile, zentralwertProduktverwaltungSpalteWeitereBilder).PasteSpecial Paste:=xlPasteValues, Operation:=xlNone, SkipBlanks:=False, Transpose:=False
        Cells(neueZeile, zentralwertProduktverwaltungSpalteWeitereBilder).NumberFormat = "@"
    End If
End If


variantCreatorVarianteErstellen = True

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
variantCreatorVarianteErstellen = False
GoTo exitHandler

End Function


Public Function variantCreatorVarianteFremdspracheErstellen(aktuellesProduktZeile As Long, neueZeile As Long, fremdsprache As String, präfixzähler, suffixzähler)
'Erstellen der Fremdsprachvarianten
'Präfix und Suffix sowie Platzhalterersetzungen sind hier bis auf Weiteres nicht vorgesehen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_variantCreatorVarianteFremdspracheErstellen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus

Cells(neueZeile, zentralwertProduktverwaltungSpalteImportsperre) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteImportsperre)
Cells(neueZeile, zentralwertProduktverwaltungSpalteLöschen) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteLöschen)
Cells(neueZeile, zentralwertProduktverwaltungSpalteTyp).Formula = inhaltZuFormelUmwandeln(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)"))
Cells(neueZeile, zentralwertProduktverwaltungSpalteSprache) = fremdsprache
Cells(neueZeile, zentralwertProduktverwaltungSpalteBezeichnung) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteBezeichnung)
Cells(neueZeile, zentralwertProduktverwaltungSpalteBeschreibung) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteBeschreibung)
Cells(neueZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung) = Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteKurzbeschreibung)


variantCreatorVarianteFremdspracheErstellen = True

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
variantCreatorVarianteFremdspracheErstellen = False
GoTo exitHandler

End Function


Public Function variantCreatorPlatzhalterErsetzen(zeichenkette, Optional aktuellesProduktZeile, Optional präfixzähler, Optional suffixzähler)
'Platzhalter ersetzen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_variantCreatorPlatzhalterErsetzen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim zeichenketteNeu As String

zeichenketteNeu = zeichenkette

On Error Resume Next
If aktuellesProduktZeile <> 0 Then zeichenketteNeu = Replace(zeichenketteNeu, "##epmMainCode##", Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteArtikelnummer)) 'Artikelnummer des Hauptartikels einfügen
If aktuellesProduktZeile <> 0 Then zeichenketteNeu = Replace(zeichenketteNeu, "##epmMainName##", Cells(aktuellesProduktZeile, zentralwertProduktverwaltungSpalteBezeichnung)) 'Bezeichnung des Hauptartikels einfügen
zeichenketteNeu = Replace(zeichenketteNeu, "##epmPrefix##", präfixzähler) 'Präfix einfügen
zeichenketteNeu = Replace(zeichenketteNeu, "##epmSuffix##", suffixzähler) 'Suffix einfügen
zeichenketteNeu = Replace(zeichenketteNeu, "##epmSep##", Trim(VariantCreator.variantenwertArtikelnummerTrennzeichen)) 'Trennzeichen einfügen
On Error GoTo errHandler

variantCreatorPlatzhalterErsetzen = zeichenketteNeu

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
variantCreatorPlatzhalterErsetzen = zeichenkette
GoTo exitHandler

End Function


Private Sub buttonAbbrechen_Click()
'Formular schließen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_buttonAbbrechen_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

GoTo exitHandler

exitHandler:
bedingteFormatierungenNeuSetzen 'Bedingte Formatierungen neu setzen, damit die richtig dargestellt werden (z. B. die FettKursiv-Darstellung der Produkte mit den neuen Varainten)
Unload Me
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub


Private Sub buttonLaden_Click()
'Einstellungen laden

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_buttonLaden_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

    VariantCreatorEinstellungenLaden (Me.listenfeldAuswahlLaden) 'Einstellungen laden
    
exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Private Sub buttonLöschen_Click()
'Einstellungen löschen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_buttonLöschen_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

    VariantCreatorEinstellungenLöschen (Me.listenfeldAuswahlLaden) 'Einstellungen löschen
    
exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Private Sub buttonSichern_Click()
'Sicher der im VariantCreator aktuell getätigten Auswahl

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_buttonSichern_Click")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

VariantCreatorEinstellungenSichern VariantCreator.listenfeldAuswahlLaden 'Funktion für die Einstellungssicherung aufrufen

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Private Function VariantCreatorEinstellungenSichern(einstellungsname)
'Sichern der im VariantCreator gewählten einstellungen bzw. der aktuell getätigten Auswahl (über "AUTOSAVE")
'Es werden automatisch die Controls durchgegangen und diejenigen, die einer speziellen Benennung ensprechen, verarbeitet

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_VariantCreatorEinstellungenSichern")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
Application.EnableEvents = False

Dim EinstellungswerteArray(100, 2) As String
Dim spalteKonfigtabelleEinstellungen As Long
Dim spalteKonfigtabelleEinstellungenWerte As Long
Dim settings As String
Dim cContr As Control
Dim gesichert As Boolean
Dim nameControl As String
Dim controlWert As Variant
Dim einstellungen As Variant
Dim aktuelleZelleInhalt As Variant
Dim X As Long

gesichert = False

'Plausibilitätsprüfung(en)
If InStr(1, einstellungsname, "§§§") Then 'Die Zeichenfolge ist im Namen nicht erlaubt
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantCreatorFalscheZeichenfolge"), vbOKOnly
    GoTo exitHandler
End If
If einstellungsname = "" And einstellungsname <> "AUTOSAVE" Then 'Name fehlt
    MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "variantcreatorKeinNameEingegeben"), vbOKOnly
    GoTo exitHandler
End If
    

spalteKonfigtabelleEinstellungen = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Settings"))
spalteKonfigtabelleEinstellungenWerte = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Setting Values"))

'Die Controls im Formular durchgehen und nur die relevanten Werte festschreiben
On Error Resume Next
For Each cContr In VariantCreator.Controls
    If LCase(Left(cContr.Name, 13)) = "variantenwert" Or (LCase(Right(cContr.Name, 9)) = "_checkbox" And cContr = True) Then
        nameControl = cContr.Name
        controlWert = cContr
        'Bestimmte Werte noch umbenennen
        If TypeOf cContr Is MSForms.CheckBox Then
            Select Case controlWert
            Case "Wahr"
                controlWert = "True"
            Case "Falsch", Null
                controlWert = "False"
            End Select
        End If
        einstellungen = einstellungen & nameControl & "§§§" & controlWert & "§§§"
    End If
Next cContr
If Right(einstellungen, 3) = "§§§" Then einstellungen = Left(einstellungen, Len(einstellungen) - 3) 'Hintere Feldtrenner entfernen

On Error GoTo errHandler

'Die entsprechende Konfigspalte durchgehen und die Zeile mit den geladenen Werten suchen oder die erste leere Zeile
For X = zentralwertZeileDatenbeginn To 10000
    aktuelleZelleInhalt = Trim(Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungen))
    If aktuelleZelleInhalt = einstellungsname Then
        Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungenWerte) = einstellungen
        gesichert = True
        Exit For
    End If
Next X
If gesichert = False Then 'wenn Name zuvor nicht gefunden (also unter diesem Namen in der Verganenheit keine Einstellungen gesichert wurden), dann nach der ersten leeren Zeile suchen
    For X = zentralwertZeileDatenbeginn To 10000
        aktuelleZelleInhalt = Trim(Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungen))
        If aktuelleZelleInhalt = "" Then
            Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungen) = listenfeldAuswahlLaden
            Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungenWerte) = einstellungen
            gesichert = True
            Exit For
        End If
Next X
End If

If gesichert = True Then 'Wenn zuvor erfolgreich gesichert wurde
    listenfeldAuswahlLadenSortieren 'Nach dem Sichern die Liste (konkret die entsprechenden Zellen in der Konfigtabelle) neu sortieren
    VariantCreatorEinstellungenSichern = True
    If einstellungsname <> "AUTOSAVE" Then MsgBox "Die Einstellungen wurden gesichert.", vbOKOnly 'Hinweis bringen, nur wenn nicht im Falle von AUTOSAVE
End If


exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
VariantCreatorEinstellungenSichern = False
GoTo exitHandler


End Function


Private Function VariantCreatorEinstellungenLaden(einstellungsname)
'Laden der im VariantCreator gewählten Einstellungen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_VariantCreatorEinstellungenLaden")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
Application.EnableEvents = False

Dim EinstellungswerteArray(100, 2) As String
Dim spalteKonfigtabelleEinstellungen As Long
Dim spalteKonfigtabelleEinstellungenWerte As Long
Dim settings As String
Dim cContr As Control
Dim aktuelleZelleInhalt As Variant
Dim einstellungen As Variant
Dim werteArray As Variant
Dim controlZähler As Long
Dim X As Long


'Plausibilitätsprüfung(en)
If VariantCreator.listenfeldAuswahlLaden = "" And einstellungsname <> "AUTOSAVE" Then  'Name fehlt
    MsgBox "Sie haben keinen Namen ausgewählt!", vbOKOnly
    GoTo exitHandler
End If
    

spalteKonfigtabelleEinstellungen = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Settings"))
spalteKonfigtabelleEinstellungenWerte = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Setting Values"))

'Die entsprechende Konfigspalte durchgehen und die Zeile mit den geladenen Werten suchen
For X = zentralwertZeileDatenbeginn To 10000
    aktuelleZelleInhalt = Trim(Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungen))
    If aktuelleZelleInhalt = einstellungsname Then
        einstellungen = Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungenWerte)
        Exit For
    End If
Next X

If einstellungen = "" Then GoTo errNichtGefunden

'Die Controls im Formular vor dem Laden zuerst durchgehen und zurücksetzen
On Error Resume Next
For Each cContr In VariantCreator.Controls
    If LCase(Left(cContr.Name, 13)) = "variantenwert" Or (LCase(Right(cContr.Name, 9)) = "_checkbox" And cContr = True) Then
        'Bestimmte Werte noch umbenennen
        If TypeOf cContr Is MSForms.CheckBox Then
            cContr = False
        Else
            cContr = ""
        End If
    End If
Next cContr
On Error GoTo errHandler

'Die gespeicherten Einstellungen durchgehen und im Formular setzen
On Error Resume Next
werteArray = Split(einstellungen, "§§§")
For controlZähler = 0 To UBound(werteArray) Step 2
        VariantCreator(werteArray(controlZähler)) = werteArray(controlZähler + 1)
Next controlZähler
On Error GoTo errHandler

VariantCreatorEinstellungenLaden = True

If einstellungsname <> "AUTOSAVE" Then MsgBox "Die Einstellungen wurden geladen.", vbOKOnly 'Hinweis bringen, nur wenn nicht im Falle von AUTOSA

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
VariantCreatorEinstellungenLaden = False
GoTo exitHandler

errNichtGefunden:

GoTo exitHandler

End Function


Private Function VariantCreatorEinstellungenLöschen(einstellungsname)
'Laden der im VariantCreator gewählten Einstellungen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_VariantCreatorEinstellungenLöschen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
Application.EnableEvents = False

Dim EinstellungswerteArray(100, 2) As String
Dim spalteKonfigtabelleEinstellungen As Long
Dim spalteKonfigtabelleEinstellungenWerte As Long
Dim settings As String
Dim cContr As Control
Dim aktuelleZelleInhalt As Variant
Dim X As Long

'Plausibilitätsprüfung(en)
If VariantCreator.listenfeldAuswahlLaden = "" Then  'Name fehlt
    MsgBox "Sie haben keinen Namen ausgewählt!", vbOKOnly
    GoTo exitHandler
End If
    
'Zur Sicherheit nachfragen, ob wirklich gelöscht werden soll
If MsgBox("Sind Sie sicher, dass Sie diese gespeicherten Einstellungen löschen möchten?", vbYesNo) <> vbYes Then GoTo exitHandler

spalteKonfigtabelleEinstellungen = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Settings"))
spalteKonfigtabelleEinstellungenWerte = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Setting Values"))

'Die entsprechende Konfigspalte durchgehen und die Zeile mit den geladenen Werten suchen
For X = zentralwertZeileDatenbeginn To 10000
    aktuelleZelleInhalt = Trim(Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungen))
    If aktuelleZelleInhalt = einstellungsname Then
        Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungen) = ""
        Sheets(zentralwertTabellenblattNameKonfiguration).Cells(X, spalteKonfigtabelleEinstellungenWerte) = ""
        Exit For
    End If
Next X

listenfeldAuswahlLadenSortieren 'Nach dem Löschen die Liste (konkret die entsprechenden Zellen in der Konfigtabelle) neu sortieren

VariantCreatorEinstellungenLöschen = True

MsgBox "Die Einstellungen wurden gelöscht.", vbOKOnly

exitHandler:
Application.EnableEvents = aktuellerEnableEventsStatus
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
VariantCreatorEinstellungenLöschen = False
GoTo exitHandler

End Function


Public Sub listenfeldAuswahlLadenSortieren()
'Rowsource in der Tabelle sortieren.
aktuelleFunktionsnummer = crc32HashErmitteln("listenfeldAuswahlLadenSortieren")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

If zentralwertExcelVersion >= zentralwertAltesExcelVorVersion Then
    listenfeldAuswahlLadenSortierenAbExcel2007
Else
    listenfeldAuswahlLadenSortierenBisExcel2003
End If

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub listenfeldAuswahlLadenSortierenAbExcel2007()
'Rowsource in der Tabelle sortieren.
aktuelleFunktionsnummer = crc32HashErmitteln("listenfeldAuswahlLadenSortierenAbExcel2007")
On Error GoTo errHandler

Application.EnableCancelKey = xlDisabled

Dim alterScreenUpdateingStatus As Boolean
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.ScreenUpdating = False

Dim spalteKonfigtabelleEinstellungen As Long
Dim spalteKonfigtabelleEinstellungenWerte As Long
Dim sortierRangeSortierkriterium As String
Dim sortierRangeGesamt As String

'Sortier-Range definieren (dabei den ersten Wert überspringen ["AUTOSAVE"])
spalteKonfigtabelleEinstellungen = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Settings"))
spalteKonfigtabelleEinstellungenWerte = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "VariantCreator Setting Values"))

sortierRangeSortierkriterium = Cells(zentralwertZeileDatenbeginn + 1, spalteKonfigtabelleEinstellungen).Address & ":" & Cells(10000, spalteKonfigtabelleEinstellungen).Address
sortierRangeGesamt = Cells(zentralwertZeileDatenbeginn + 1, spalteKonfigtabelleEinstellungen).Address & ":" & Cells(10000, spalteKonfigtabelleEinstellungenWerte).Address

With Sheets(zentralwertTabellenblattNameKonfiguration).Sort
    .SetRange Range(sortierRangeGesamt)
    .Header = xlGuess
    .MatchCase = False
    .Orientation = xlTopToBottom
    .SortMethod = xlPinYin
    .Apply
End With

Sheets(zentralwertTabellenblattNameKonfiguration).Select
Cells(zentralwertZeileDatenbeginn, 1).Select
Sheets(zentralwertTabellenblattNameProduktverwaltung).Select

exitHandler:
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub listenfeldAuswahlLadenSortierenBisExcel2003()
'Rowsource in der Tabelle sortieren.
'Keine Sortierung bis Excel2003 aufgrund der Problematik mit geschützten Blättern
aktuelleFunktionsnummer = crc32HashErmitteln("listenfeldAuswahlLadenSortierenBisExcel2003")

On Error GoTo errHandler

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Private Sub Label3_Click()

End Sub

Private Sub UserForm_Terminate()
'Aktionen, wenn Formular geschlossen wird

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_UserForm_Terminate")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

    If Me.checkboxAuswahlMerken = True Then
        VariantCreatorEinstellungenSichern ("AUTOSAVE") 'Einstellungen per Autosave sicher, sofern Flag gewünscht
    End If

exitHandler:
On Error Resume Next
Unload VariantCreatorHelp 'ggf. noch offenes Unterformular schließen
Application.EnableEvents = True 'Hier auf jeden Fall EnableEvents zur Sicherheit wieder aktivieren, falls während der Arbeit mit dem VariantCreator etwas schief gegangen sein sollte
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Private Sub UserForm_Initialize()
'Die Merkmale und Ausprägungen auslesen und in einem Formular dafür Felder erstellen.
'Zusätzlich weitere Felder erstellen.
'Bei Klick auf den "Erstellen"-Button wird mittels einer Klasse der Click ermittelt (geht nur per Klasse, da der Button zur Laufzeit dynamisch generiert wird)

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_UserForm_Initialize")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

Dim spalteMerkmale As Long
Dim spalteAusprägungen As Long

Dim positionBeginnControlMerkmaleTop As Long
Dim positionControlMerkmaleLeft As Long
Dim positionBeginnControlAusprägungenTop As Long
Dim positionControlAusprägungenLeft As Long
Dim positionBeginnControlAusprägungenCheckboxTop As Long
Dim positionControlAusprägungenCheckboxLeft As Long
Dim positionControlButtonErstellenLeft As Long
Dim positionControlButtonAbbrechenLeft As Long
Dim zeilenzähler As Long
Dim X As Long
Dim Y As Long
Dim merkmalGefunden As Boolean
Dim ausprägungGefunden As Long
Dim merkmalInhalt As String
Dim merkmalInhalt_alt As String
Dim ausprägungInhalt As String
Dim maxAngezeigteZeichen As Long
Dim cCntrl As Control
Dim VariantCreatorMerkmalszähler As Long


Erase VariantCreatorAusprägungenArray

VariantCreatorMerkmalszähler = 0
VariantCreatorAusprägungszähler = 0
maxAngezeigteZeichen = 30

spalteMerkmale = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Property"))
spalteAusprägungen = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Value"))

positionBeginnControlMerkmaleTop = 0
positionControlMerkmaleLeft = 0
positionBeginnControlAusprägungenTop = 0
positionControlAusprägungenLeft = 156
positionBeginnControlAusprägungenCheckboxTop = 0
positionControlAusprägungenCheckboxLeft = 290
positionControlButtonErstellenLeft = 0
positionControlButtonAbbrechenLeft = 80

zeilenzähler = 0
For X = zentralwertZeileDatenbeginn To 9999
    ausprägungGefunden = False
    With Sheets(zentralwertTabellenblattNameKonfiguration)
        merkmalInhalt = .Cells(X, spalteMerkmale)
        If merkmalInhalt <> "" And merkmalInhalt <> merkmalInhalt_alt Then
            Set cCntrl = Me.rahmenSelektionen.Controls.Add("Forms.Label.1", merkmalInhalt, True)
            With cCntrl
                .Caption = Trim(Left(merkmalInhalt, maxAngezeigteZeichen)) & IIf(Len(merkmalInhalt) > maxAngezeigteZeichen, "...", "") 'Caption setzen und dabei vorher kürzen und ggf. drei Punkte setzen, falls eigentliche Bezeichnung länger ist
                .Width = 150
                .Height = 50
                .Top = positionBeginnControlMerkmaleTop + (zeilenzähler) * 15
                .Left = positionControlMerkmaleLeft
                .ZOrder (0)
                .ForeColor = RGB(255, 255, 255)
                .BackStyle = fmBackStyleTransparent
                .SpecialEffect = fmSpecialEffectFlat
            End With
            merkmalGefunden = True
            merkmalInhalt_alt = merkmalInhalt
            VariantCreatorMerkmalszähler = VariantCreatorMerkmalszähler + 1
        End If
        
        If merkmalGefunden = True Then
            ausprägungInhalt = .Cells(X, spalteAusprägungen)
            If ausprägungInhalt <> "" Then
                Set cCntrl = Me.rahmenSelektionen.Controls.Add("Forms.Label.1", ausprägungInhalt, True)
                With cCntrl
                    .Caption = Trim(Left(ausprägungInhalt, maxAngezeigteZeichen)) & IIf(Len(ausprägungInhalt) > maxAngezeigteZeichen, "...", "") 'Caption setzen und dabei vorher kürzen und ggf. drei Punkte setzen, falls eigentliche Bezeichnung länger ist
                    .Width = 150
                    .Height = 15
                    .Top = positionBeginnControlAusprägungenTop + (zeilenzähler) * 15
                    .Left = positionControlAusprägungenLeft
                    .ZOrder (0)
                    .ForeColor = RGB(255, 255, 255)
                    .BackStyle = fmBackStyleTransparent
                    .SpecialEffect = fmSpecialEffectFlat
                End With
                Set cCntrl = Me.rahmenSelektionen.Controls.Add("Forms.Checkbox.1", ausprägungInhalt & "_checkbox", True)
                With cCntrl
                    .Width = 15
                    .Height = 15
                    .Top = positionBeginnControlAusprägungenCheckboxTop + (zeilenzähler) * 15
                    .Left = positionControlAusprägungenCheckboxLeft
                    .ZOrder (0)
                    .ForeColor = RGB(255, 255, 255)
                    .BackStyle = fmBackStyleTransparent
                End With
                ausprägungGefunden = True
                VariantCreatorAusprägungszähler = VariantCreatorAusprägungszähler + 1
                VariantCreatorAusprägungenArray(VariantCreatorAusprägungszähler, 1) = ausprägungInhalt
                VariantCreatorAusprägungenArray(VariantCreatorAusprägungszähler, 2) = merkmalInhalt_alt
            Else
                merkmalGefunden = False
                zeilenzähler = zeilenzähler + 1 'zwei extra Zeilen Abstand einfügen
            End If
        End If
    End With
If merkmalGefunden = True Or ausprägungGefunden = True Then zeilenzähler = zeilenzähler + 1
Next X

'Datenquellen für Listenfelder bestimmen

Me.listenfeldAuswahlLaden.RowSource = zentralwertTabellenblattNameKonfiguration & "!$AB$13:$AB$9999"
'Me.listenfeldAuswahlLaden.BackColor = RGB(50, 100, 150)
Me.variantenwertPreisTyp.RowSource = zentralwertTabellenblattNameBasisparameter & "!$E$12:$E$9999"
Me.variantenwertPreisTyp.BackColor = RGB(50, 100, 150)

Me.variantenwertStaffelpreisTyp.RowSource = zentralwertTabellenblattNameBasisparameter & "!$L$12:$L$9999"
Me.variantenwertStaffelpreisTyp.BackColor = RGB(50, 100, 150)
Me.variantenwertStaffelpreisMengenermittlungTyp.RowSource = zentralwertTabellenblattNameBasisparameter & "!$M$12:$M$9999"
Me.variantenwertStaffelpreisMengenermittlungTyp.BackColor = RGB(50, 100, 150)

Me.variantenwertAlterPreisTyp.RowSource = zentralwertTabellenblattNameBasisparameter & "!$F$12:$F$9999"
Me.variantenwertAlterPreisTyp.BackColor = RGB(50, 100, 150)
Me.variantenwertGewichtTyp.RowSource = zentralwertTabellenblattNameBasisparameter & "!$H$12:$H$9999"
Me.variantenwertGewichtTyp.BackColor = RGB(50, 100, 150)
Me.variantenwertLieferzeit.RowSource = zentralwertTabellenblattNameKonfiguration & "!$H$12:$H$9999"
Me.variantenwertLieferzeit.BackColor = RGB(50, 100, 150)


Rem 02.06.2023, TBU, für das eine Feld (der 4 neuen Felder) die gleiche Datenquelle wie "Einstellungen zu Lagerbestand und Lieferzeit"
Me.variantenwertLagerbestandUndLieferzeitInVorbestellungsphase.RowSource = zentralwertTabellenblattNameKonfiguration & "!$I$12:$I$9999"
Me.variantenwertLagerbestandUndLieferzeitInVorbestellungsphase = ""
Me.variantenwertCheckboxVorbestellungErlaubt = False
Me.variantenwertCheckboxVerfuegbarkeitsEinstellungenBeimUebergeordnetenProduktUeberschreiben = False
Me.variantenwertVerfuegbarAb = ""
DoEvents


'Den Rahmen für die Merkmale und Ausprägungen formatieren
Me.rahmenSelektionen.ScrollHeight = 100 + VariantCreatorAusprägungszähler * 20
Me.rahmenSelektionen.BackColor = RGB(68, 130, 59)

'Labels (mehrsprachig) beschriften
Me.LabelListenfeldAuswahlLaden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Load/save settings")
Me.LabelCheckboxAuswahlMerken.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Keep")
Me.LabelvariantenwertTextfeldFremdsprachen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Languages incl.?")
Me.LabelvariantenwertCheckboxHauptartikelnummerVerwenden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Code from main product")
Me.LabelvariantenwertArtikelnummerPräfixStart.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Prefix start")
Me.LabelvariantenwertArtikelnummerSuffixStart.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Suffix start")
Me.LabelvariantenwertArtikelnummerTrennzeichen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Sep.")
Me.LabelvariantenwertPreis.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Price")
Me.LabelvariantenwertPreisTyp.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Price: price type")
Me.LabelvariantenwertCheckboxStaffelpreisAnwenden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Use scale price")
Me.LabelvariantenwertCheckboxStaffelpreisKonfigsTrennen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separate diff. configs")
Me.LabelvariantenwertStaffelpreisTyp.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Scale price type")
Me.LabelvariantenwertStaffelpreisMengenermittlungTyp.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Quantity detection method")
Me.LabelvariantenwertStaffelpreisSchlüsselwort.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Scale price keyword")
Me.LabelvariantenwertStaffelpreisPreis.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Scale price")
Me.LabelvariantenwertAlterPreis.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Old price")
Me.LabelvariantenwertAlterPreisTyp.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Old price: price type")
Me.LabelvariantenwertGewicht.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Weight")
Me.LabelvariantenwertGewichtTyp.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Weight type")
Me.LabelvariantenwertLagerbestandsänderung.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Change stock")
Me.LabelvariantenwertLieferzeit.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Delivery time")
Me.LabelvariantenwertHauptbild.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Main Image")
Me.LabelvariantenwertWeitereBilder.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "More images")
Me.LabelvariantenwertCheckboxHauptartikelPreisVerwenden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "MC")
Me.LabelvariantenwertCheckboxHauptartikelAlterPreisVerwenden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "MC")
Me.LabelvariantenwertCheckboxHauptartikelGewichtVerwenden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "MC")
Me.LabelvariantenwertCheckboxHauptartikelLagerbestandLieferzeitVerwenden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "MC")
Me.LabelvariantenwertCheckboxStaffelpreisHauptartikelPreisVerwenden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "MC")
Me.LabelvariantenwertArtikelnummer.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product code")
Me.LabelvariantenwertBezeichnung.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Name")
Me.LabelvariantenwertBeschreibung.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Description")
Me.LabelvariantenwertKurzbeschreibung.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Short description")
Me.LabelvariantenwertCheckboxArtikelnummerIstFormel.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Formula")
Me.LabelvariantenwertCheckboxBezeichnungIstFormel.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Formula")
Me.LabelvariantenwertCheckboxBeschrIstFormel.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Formula")
Me.LabelvariantenwertCheckboxKurzbeschrIstFormel.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Formula")

'Grafiken sprachabhängig einblenden
Select Case aktuellGewählteSprache
Case 1
Me.überschrift01English.Visible = True
Me.überschrift02English.Visible = True
Me.überschrift01Deutsch.Visible = False
Me.überschrift02Deutsch.Visible = False
Case 2
Me.überschrift01English.Visible = False
Me.überschrift02English.Visible = False
Me.überschrift01Deutsch.Visible = True
Me.überschrift02Deutsch.Visible = True
Case Else
Me.überschrift01English.Visible = True
Me.überschrift02English.Visible = True
Me.überschrift01Deutsch.Visible = False
Me.überschrift02Deutsch.Visible = False
End Select


'Buttons (mehrsprachig) beschriften
Me.buttonLaden.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "LOAD")
Me.buttonSichern.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "SAVE")
Me.buttonLöschen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "DELETE")
Me.buttonErstellen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "CREATE")
Me.buttonAbbrechen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "CLOSE")
Me.buttonAusprägungenZurücksetzen.Caption = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "DELETE")

VariantCreatorEinstellungenLaden ("AUTOSAVE") 'Autosave-Einstellungen laden

listenfeldAuswahlLadenSortieren 'Listenfeld mit den Einstellungen sortieren

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

errZeileNichtMarkiert:
MsgBox "Sie haben keine Zeile markiert!", vbOKOnly
GoTo exitHandler

End Sub


Public Function formularCheckboxStatus(formularName, checkboxName)
'Gibt den Status einer Checkbox im angegebenen Formular zurück

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_formularCheckboxStatus")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim formularNummer As Long
Dim X As Long

'ID der Userform anhand des Namens ermitteln
For X = 0 To UserForms.Count
    If UserForms(X).Name = formularName Then
        formularNummer = X
        Exit For
    End If
Next X

'Prüfen, ob die Checkbox angehakt ist
If checkboxName <> "_checkbox" Then
    formularCheckboxStatus = UserForms(formularNummer).Item(checkboxName).Value
Else
    formularCheckboxStatus = False
End If

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Private Sub variantenwertHauptbild_DblClick(ByVal Cancel As MSForms.ReturnBoolean)
'Bei Doppelklick Dateiauswahldialog öffnen und ausgewählte Dateinamen übernehmen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_variantenwertHauptbild_DblClick")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus

Dim strOld As String
Dim dateienAuswahl As String

Application.EnableEvents = False
strOld = Me.variantenwertHauptbild
dateienAuswahl = dateidialogDateinamen(strOld, True, True) 'Dateidialog öffnen
Me.variantenwertHauptbild = strOld & dateienAuswahl


exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub




Private Sub variantenwertVerfuegbarAb_AfterUpdate()
  Rem 02.06.2023, TBU, Eingaben in korrektes Datumsformat umwandeln
    variantenwertVerfuegbarAb = Format(variantenwertVerfuegbarAb, "dd.mm.yyyy")
End Sub

Private Sub variantenwertVerfuegbarAb_Exit(ByVal Cancel As MSForms.ReturnBoolean)
  Rem 02.06.2023, TBU, löschen (Leerstring) wenn Wert kein richtiges Datum ist. Kein Cancel da sonst immer eine Angabe notwendig ist
    If Not IsDate(variantenwertVerfuegbarAb) Then
        variantenwertVerfuegbarAb = ""
        'Cancel = True
    End If
End Sub


Private Sub variantenwertWeitereBilder_DblClick(ByVal Cancel As MSForms.ReturnBoolean)
'Bei Doppelklick Dateiauswahldialog öffnen und ausgewählte Dateinamen übernehmen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_variantenwertWeitereBilder_DblClick")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus

Dim strOld As String
Dim dateienAuswahl As String

Application.EnableEvents = False
strOld = Me.variantenwertWeitereBilder
dateienAuswahl = dateidialogDateinamen(strOld, True, True) 'Dateidialog öffnen
Me.variantenwertWeitereBilder = strOld & dateienAuswahl


exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Sub



Private Sub buttonAusprägungenZurücksetzen_Click()
'Laden der im VariantCreator gewählten Einstellungen

aktuelleFunktionsnummer = crc32HashErmitteln("VariantCreator_VariantCreatorAuspraegungenAuswahlzurücksetzen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
Application.EnableEvents = False

Dim EinstellungswerteArray(100, 2) As String
Dim spalteKonfigtabelleEinstellungen As Long
Dim spalteKonfigtabelleEinstellungenWerte As Long
Dim settings As String
Dim cContr As Control
Dim aktuelleZelleInhalt As Variant
Dim einstellungen As Variant
Dim werteArray As Variant
Dim controlZähler As Long
Dim X As Long


'Die Controls im Formular vor dem Laden zuerst durchgehen und zurücksetzen
On Error Resume Next
For Each cContr In VariantCreator.Controls
    If (LCase(Right(cContr.Name, 9)) = "_checkbox" And cContr = True) Then
        'Bestimmte Werte noch umbenennen
        If TypeOf cContr Is MSForms.CheckBox Then
            cContr = False
        End If
    End If
Next cContr
On Error GoTo errHandler

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

errNichtGefunden:

GoTo exitHandler

End Sub
