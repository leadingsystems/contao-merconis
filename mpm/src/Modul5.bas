Attribute VB_Name = "Modul5"
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Public Sub ExportCSV(Optional nurSelektierte As Boolean)
'Produkte als CSV exportieren. Dabei werden auch Zeichenersetzungen vorgenommen

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("ExportCSV")
Application.EnableCancelKey = xlDisabled

Dim bereich As Object, zeile As Object, zelle As Object
Dim strTemp As String
Dim strDateiname As Variant
Dim strTrennzeichen As String
Dim strMappenpfad As String
Dim zelleRange As Range
Dim anzahlExportiert As Long
Dim warteschleifeAnzeigezähler As Long
Dim zellpositionSchriftformatMethodeZelle As String
Dim zellpositionSchriftformatUmwandlungDeaktivieren As String
Dim zeileIstMarkiert As Boolean
Dim aktiveZelleInhalt As Variant
Dim kommentarMarkierungLinks As Long
Dim kommentarMarkierungRechts As Long
Dim kommentarMarkierungFehlt As Boolean
Dim strZelle As Variant
Dim UTF8String As Variant
Dim bereichLagerbestandsänderungen As String
Dim anzahlLagerbestandsänderungen As Long

WaitFenster.Repaint

'Wenn übergeordnete Artikelnummern nicht automatisch errechnet werden, dann fragen, ob die auch alle korrekt sind.
If Range(modusÜbergeordneteArtikelnummerEmittelnZelle) = "semiautomode" Then
    antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvparentcodeskorrekt02"), 4, mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvparentcodeskorrekt01"))
    If antwort = vbNo Then
        GoTo exitHandler
    End If
End If

'Sofern Lagerbestandsänderungen hinterlegt sind, fragen, ob das so gewollt ist.
bereichLagerbestandsänderungen = Cells(zentralwertZeileDatenbeginn, zentralwertProduktverwaltungSpalteLagerbestandsänderung).Address & ":" & Cells(letzteVerwendeteZeile, zentralwertProduktverwaltungSpalteLagerbestandsänderung).Address
anzahlLagerbestandsänderungen = Application.WorksheetFunction.CountA(Sheets(1).Range(bereichLagerbestandsänderungen))
If anzahlLagerbestandsänderungen > 0 Then
    If nurSelektierte <> True Then
        antwort = MsgBox(Replace(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvlagerbestandsänderungenkorrekt02"), "###", anzahlLagerbestandsänderungen), 4, Replace(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvlagerbestandsänderungenkorrekt01"), "###", anzahlLagerbestandsänderungen))
    Else
        antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvlagerbestandsänderungenkorrekt04"), 4, mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvlagerbestandsänderungenkorrekt03"))
    End If
    If antwort = vbNo Then
        GoTo exitHandler
    End If
End If


strMappenpfad = ActiveWorkbook.FullName
strMappenpfad = Replace(strMappenpfad, ".xlsm", ".csv")
strMappenpfad = Replace(strMappenpfad, ".xls", ".csv")
strTrennzeichen = ";"

anzahlExportiert = 0

schriftgrößenTransformationenInArray 'Kopiert die Einstellungen zu Schriftgrößen-Transformationen in ein Array

strDateiname = Application.GetSaveAsFilename("products.csv", "CSV-Dateien,*.csv,Alle Dateien,*.*")
If strDateiname = False Then GoTo exitHandler

Set bereich = ActiveSheet.UsedRange

'Prüfen, ob wortweise oder buchstabendweise Konvertierung verwendet werden soll
zellpositionSchriftformatMethodeZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Method: Words (faster)"))
zellpositionSchriftformatMethodeZelle = Cells(Range(zellpositionSchriftformatMethodeZelle).Row, Range(zellpositionSchriftformatMethodeZelle).Column + 1).Address
zellpositionSchriftformatUmwandlungDeaktivieren = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Deactivate transformation"))
zellpositionSchriftformatUmwandlungDeaktivieren = Cells(Range(zellpositionSchriftformatUmwandlungDeaktivieren).Row, Range(zellpositionSchriftformatUmwandlungDeaktivieren).Column + 1).Address

'Frisch alle Formeln neu berechnen
Application.CalculateFull

Open strDateiname For Output As #1

For Each zeile In bereich.Rows
    
    'Prüfen, ob Zeile markiert (falls nur markierte exportiert werden sollen)
    If nurSelektierte = True Then
        If Intersect(Range(Cells(zeile.Row, 1).Address), Selection) Is Nothing Then
           zeileIstMarkiert = False
        Else
           zeileIstMarkiert = True
        End If
    Else
        zeileIstMarkiert = True
    End If
    If zeile.Row < zentralwertZeileDatenbeginn Then
           zeileIstMarkiert = True
    End If
    
    'Falls nachfolgende Bedingungen zutreffen, Zellen der Zeile auslesen
    If (zeile.Row >= zentralwertZeileDatenbeginn And Cells(zeile.Row, zentralwertProduktverwaltungSpalteImportsperre) <> 1 And zeileIstMarkiert = True And Cells(zeile.Row, zentralwertProduktverwaltungSpalteTyp) <> "") Or (zeile.Row >= zentralwertZeileExportbeginn And zeile.Row < zentralwertZeileDatenbeginn And zeile.Row <> zentralwertZeileÜberschriften) Then
        For Each zelle In zeile.Cells
            If Left(Range(Cells(zentralwertZeileDatenbanknamen, zelle.Column).Address), 2) <> "_t" Then 'Nur exportieren, wenn es keine Trenner-Spalte ist
                aktiveZelleInhalt = zelle.Value
                'Falls ein Kommentar enthalten ist ( [[ich bin ein Kommentar]] ), dann entfernen.
                kommentarMarkierungFehlt = False
                Do While kommentarMarkierungFehlt <> True
                    kommentarMarkierungLinks = InStr(1, aktiveZelleInhalt, "[{")
                    kommentarMarkierungRechts = InStr(1, aktiveZelleInhalt, "}]")
                    If kommentarMarkierungLinks > 0 Then
                        aktiveZelleInhalt = Left(aktiveZelleInhalt, kommentarMarkierungLinks - 1) & Mid(aktiveZelleInhalt, kommentarMarkierungRechts + 2)
                    Else
                        kommentarMarkierungFehlt = True
                        aktiveZelleInhalt = RTrim(aktiveZelleInhalt)
                    End If
                Loop
                
                'Zeichenersetzungen vornehmen (aber nur bei bestimmten Spalten) und nur, wenn die Umwandlung nicht deaktiviert wurde
                If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatUmwandlungDeaktivieren) <> True _
                    And zeile.Row >= zentralwertZeileDatenbeginn _
                    And zelle.Column = zentralwertProduktverwaltungSpalteBeschreibung _
                    And zelle <> "" Then
                    If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatMethodeZelle) <> True Then 'Falls die schnellere aber unsaubere Konvertierungsmethode nach ganzen Wörtern gewählt wurde, diese verwenden
                        aktiveZelleInhalt = zelleInhaltZuHTML(Range(zelle.Address))
                    Else
                        aktiveZelleInhalt = zelleInhaltZuHTMLWörter(zelle.Address)
                    End If
                End If
                'Bestimmte Werte für Export ersetzen, aber nur die reinen Datenzeilen, nicht die Kopfzeilen

                If zeile.Row >= zentralwertZeileDatenbeginn Then
                    Select Case zelle.Column
                        Case zentralwertProduktverwaltungSpaltePreis, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationen, zentralwertProduktverwaltungSpalteAlterPreis, zentralwertProduktverwaltungSpalteTeilerMengenvergleichspreis, zentralwertProduktverwaltungSpalteGewicht, zentralwertProduktverwaltungSpalteMengeNachkommastellen, zentralwertProduktverwaltungSpalteLagerbestandsänderung, _
                            zentralwertProduktverwaltungSpaltePreisGruppe1, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe1, zentralwertProduktverwaltungSpalteAlterPreisGruppe1, _
                            zentralwertProduktverwaltungSpaltePreisGruppe2, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe2, zentralwertProduktverwaltungSpalteAlterPreisGruppe2, _
                            zentralwertProduktverwaltungSpaltePreisGruppe3, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe3, zentralwertProduktverwaltungSpalteAlterPreisGruppe3, _
                            zentralwertProduktverwaltungSpaltePreisGruppe4, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe4, zentralwertProduktverwaltungSpalteAlterPreisGruppe4, _
                            zentralwertProduktverwaltungSpaltePreisGruppe5, zentralwertProduktverwaltungSpalteStaffelpreisMengenPreisKombinationenGruppe5, zentralwertProduktverwaltungSpalteAlterPreisGruppe5  'Bei den Zahlenspalten die Tausender- und die Dezimalstellen-Trenner DB-tauglich abändern
                            aktiveZelleInhalt = Replace(aktiveZelleInhalt, Application.DecimalSeparator, "D")
                            aktiveZelleInhalt = Replace(aktiveZelleInhalt, Application.ThousandsSeparator, "T")
                            aktiveZelleInhalt = Replace(aktiveZelleInhalt, "D", ".")
                            aktiveZelleInhalt = Replace(aktiveZelleInhalt, "T", "")
                        Case zentralwertProduktverwaltungSpalteTyp 'Wenn Typ
                            Select Case aktiveZelleInhalt
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Product")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Variant")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Product (foreign language)")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Product (foreign language)")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Variant (foreign language)")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Variant (foreign language)")
                            End Select
                        Case zentralwertProduktverwaltungSpaltePreisTyp, zentralwertProduktverwaltungSpaltePreisTypGruppe1, zentralwertProduktverwaltungSpaltePreisTypGruppe2, zentralwertProduktverwaltungSpaltePreisTypGruppe3, zentralwertProduktverwaltungSpaltePreisTypGruppe4, zentralwertProduktverwaltungSpaltePreisTypGruppe5 'Wenn Preisart
                            Select Case aktiveZelleInhalt
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Percent")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Fixed price")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Independent price")
                            End Select
                        Case zentralwertProduktverwaltungSpalteStaffelpreisArt, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe1, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe2, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe3, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe4, zentralwertProduktverwaltungSpalteStaffelpreisArtGruppe5 'Wenn Staffelpreis Art
                            Select Case aktiveZelleInhalt
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed scale price")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Fixed scale price")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percentaged adjustment")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Percentaged adjustment")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Adjustment with a fixed value")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Adjustment with a fixed value")
                            End Select
                        Case zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethode, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe1, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe2, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe3, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe4, zentralwertProduktverwaltungSpalteStaffelpreisMengenermittlungMethodeGruppe5 'Wenn Staffelpreis Mengenermittlung Methode
                            Select Case aktiveZelleInhalt
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products, variants and configurations")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Separated by products, variants and configurations")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products and variants")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Separated by products and variants")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Separated by products")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Separated by products")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Summarized by scale price keyword")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Summarized by scale price keyword")
                            End Select
                        Case zentralwertProduktverwaltungSpalteAlterPreisTyp, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe1, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe2, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe3, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe4, zentralwertProduktverwaltungSpalteAlterPreisTypGruppe5 'Wenn Preisart des alten Preises
                            Select Case aktiveZelleInhalt
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Percent")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed price")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Fixed price")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent price")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Independent price")
                            End Select
                        Case zentralwertProduktverwaltungSpalteGewichtTyp 'Wenn Gewichtsart
                            Select Case aktiveZelleInhalt
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Percent")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Percent")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Fixed weight")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Fixed weight")
                            Case mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Independent weight")
                                aktiveZelleInhalt = mehrsprachigkeitBegriffsrückgabe(99, "Independent weight")
                            End Select
                        Case zentralwertProduktverwaltungSpalteVerfuegbarAb
                            Rem 31.05.2023, TBU Umwandlung des dt. Datumsformats in das für den Import notwendige  15.12.2023 -> 2023-12-15
                            aktiveZelleInhalt = Format(aktiveZelleInhalt, "yyyy-mm-dd")
                    End Select
                End If
    
                If InStr(1, aktiveZelleInhalt, strTrennzeichen) > 0 Or InStr(1, aktiveZelleInhalt, Chr(10)) > 0 Or InStr(1, aktiveZelleInhalt, zeichen_gf) > 0 Then 'Zellen, die ein Trennzeichen oder Anführungszeichen enthalten, gesondert behandeln
                    'Wenn Anführungszeichen
                    If InStr(1, aktiveZelleInhalt, zeichen_gf) > 0 Then
                        strZelle = Replace(CStr(aktiveZelleInhalt), zeichen_gf, zeichen_gf & zeichen_gf)
                    Else
                        strZelle = CStr(aktiveZelleInhalt)
                    End If
                    strTemp = strTemp & zeichen_gf & strZelle & zeichen_gf & strTrennzeichen
                Else
                    strTemp = strTemp & CStr(aktiveZelleInhalt) & strTrennzeichen
                End If
            End If
        Next
        
        If Right(strTemp, 1) = strTrennzeichen Then strTemp = Left(strTemp, Len(strTemp) - 1)
        UTF8String = GetUTF8String(strTemp)
        If UTF8String = "UTF8-Konvertierungsfehler" Then GoTo errHandler
        Print #1, UTF8String
        strTemp = ""
        If zeile.Row >= zentralwertZeileDatenbeginn Then
            anzahlExportiert = anzahlExportiert + 1
            warteschleifeAnzeigezähler = warteschleifeAnzeigezähler + 1
        End If
        If warteschleifeAnzeigezähler = 100 Then
            WaitFenster.LabelInfotext.Caption = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "wait") & vbLf & vbLf & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvVerarbeiteteZeilen") & anzahlExportiert
            waitFensterRepaint
            warteschleife 0.02, True 'Nötig, da sonst die StatusBar nicht immer refreshed wird
            warteschleifeAnzeigezähler = 0
        End If
    End If
Next

exitHandler:
WaitFenster.LabelInfotext.Caption = mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "wait") & vbLf & vbLf & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvVerarbeiteteZeilen") & anzahlExportiert
waitFensterRepaint
Close #1
Set bereich = Nothing
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvExportierteZeilen") & anzahlExportiert & ". " & vbCrLf & mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvExportierterDateiname") & strDateiname
Unload WaitFenster
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
MsgBox (mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "csvExportFehler"))
GoTo exitHandler

End Sub


Public Sub schriftgrößenTransformationenInArray()
'Kopiert die Einstellungen zu Schriftgrößen-Transformationen in ein Array

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("schriftgrößenTransformationenInArray")
Application.EnableCancelKey = xlDisabled

Dim zellpositionSchriftgrößenTransformationÜberschrift As String
Dim aktSchriftgrößeProduktmanager As Variant

zellpositionSchriftgrößenTransformationÜberschrift = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Transformation: Font size"))

Dim X As Long

For X = 1 To 50 Step 1
    aktSchriftgrößeProduktmanager = Sheets(zentralwertTabellenblattNameKonfiguration).Cells(Range(zellpositionSchriftgrößenTransformationÜberschrift).Row + X, Range(zellpositionSchriftgrößenTransformationÜberschrift).Column)
    If aktSchriftgrößeProduktmanager <> 0 Then
        zentralwertSchriftgrößenTransformationenArray(aktSchriftgrößeProduktmanager) = Sheets(zentralwertTabellenblattNameKonfiguration).Cells(Range(zellpositionSchriftgrößenTransformationÜberschrift).Row + X, Range(zellpositionSchriftgrößenTransformationÜberschrift).Column + 1)
    End If
Next X

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Function GetUTF8String(s As String) As String
'UTF8 erstellen

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("GetUTF8String")
Application.EnableCancelKey = xlDisabled

Dim i As Long  ' Zähler über die einzelnen Zeichen des utf16-Strings
Dim utf16 As Long, uc(2) As Byte

GetUTF8String = ""
For i = 1 To Len(s)
   utf16 = AscW(Mid(s, i, 1))
   If utf16 < 0 Then utf16 = utf16 + 65536
   If utf16 < &H80 Then       ' 1 Byte
      GetUTF8String = GetUTF8String & Chr(utf16)
   ElseIf utf16 < &H800 Then  ' 2 Byte
      uc(1) = &H80 + (utf16 And &H3F)  ' Least Significant 6 bits
      utf16 = utf16 \ &H40             ' Shift UTF16 number right 6 bits
      uc(0) = &HC0 + (utf16 And &H1F)  ' Use 5 remaining bits
      GetUTF8String = GetUTF8String & Chr(uc(0)) & Chr(uc(1))
   Else                       ' 3 Byte
      uc(2) = &H80 + (utf16 And &H3F)  ' Least Significant 6 bits
      utf16 = utf16 \ &H40             ' Shift UTF16 number right 6 bits
      uc(1) = &H80 + (utf16 And &H3F)  ' Use next 6 bits
      utf16 = utf16 \ &H40             ' Shift UTF16 number right 6 bits again
      uc(0) = &HE0 + (utf16 And &HF)   ' Use 4 remaining bits
      GetUTF8String = GetUTF8String & Chr(uc(0)) & Chr(uc(1)) & Chr(uc(2))
   End If
Next

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GetUTF8String = "UTF8-Konvertierungsfehler"
GoTo exitHandler


End Function


Sub SaveBackup()
'Backup erstellen

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("SaveBackup")
Application.EnableCancelKey = xlDisabled

Dim bereich As Object, zeile As Object, zelle As Object
Dim strTemp As String
Dim strDateiname As Variant
Dim strNurDateiname As String
Dim strNurDateinameOhneSuffix As String
Dim strNurSuffix As String
Dim strMappenpfad As String
Dim StrNurPfad As String
Dim strNeuerDateiname As String

strMappenpfad = ActiveWorkbook.FullName
StrNurPfad = ActiveWorkbook.Path
strNurDateiname = ActiveWorkbook.Name
strNurDateinameOhneSuffix = entferneSuffix(strNurDateiname)
strNurSuffix = gebeSuffixZurück(strNurDateiname)
strNeuerDateiname = strNurDateinameOhneSuffix & "-BACKUP-" & Format(Date, "yyyy-mm-dd") & "--" & Format(Time, "hh-mm-ss") & strNurSuffix

strDateiname = Application.GetSaveAsFilename(strNeuerDateiname, "BACKUP-Dateien,*BACKUP*" & strNurSuffix & ",Alle Dateien,*.*")
If strDateiname = False Then Exit Sub

ActiveWorkbook.SaveCopyAs strDateiname
MsgBox mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "backupErstellt") & vbCrLf & mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "File") & ": " & dateinameAusPfad(strDateiname)

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
MsgBox ("Speichern fehlgeschlagen!")
GoTo exitHandler

End Sub

Function zelleInhaltZuHTMLWörter(xRange)
'Fügt in den übergebenen Text HTML-Tags ein

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zelleInhaltZuHTMLWörter")
Application.EnableCancelKey = xlDisabled

Dim sResult As String
Dim sFront As String
Dim alterWert As String
Dim suchZeichenArray(20) As Double
Dim help As Double
Dim change As Boolean
Dim spalteKonfigurationExporteinstellungen As Long
Dim zellpositionSchriftformatFettZelle As String
Dim zellpositionSchriftformatKursivZelle As String
Dim zellpositionSchriftformatUnterstrichenZelle As String
Dim zellpositionSchriftformatFarbeZelle As String
Dim zellpositionSchriftformatGrößeZelle As String
Dim zellpositionSchriftformatStandardgrößeZelle As String
Dim schriftformatStandardschriftgröße As Variant
Dim zellpositionSchriftformatZeilenumbruchZelle As String
Dim trennzeichenPos As String
Dim trennzeichenPos_alt As String
Dim stringStart As Long
Dim stringStart_alt As Long
Dim stringLänge As Long
Dim stringLänge_alt As Long
Dim textendeErreicht As Boolean
Dim sucheStart As Long
Dim tmp As Variant
Dim sBehind As String
Dim aktSchriftfarbeText As Variant
Dim chrCol As Variant
Dim aktSchriftgrößeText As Long
Dim i As Long
Dim j As Long

spalteKonfigurationExporteinstellungen = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Export Configuration"))
    
zellpositionSchriftformatFettZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Bold"))
zellpositionSchriftformatFettZelle = Cells(Range(zellpositionSchriftformatFettZelle).Row, Range(zellpositionSchriftformatFettZelle).Column + 1).Address
zellpositionSchriftformatKursivZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Italic"))
zellpositionSchriftformatKursivZelle = Cells(Range(zellpositionSchriftformatKursivZelle).Row, Range(zellpositionSchriftformatKursivZelle).Column + 1).Address
zellpositionSchriftformatUnterstrichenZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Underline"))
zellpositionSchriftformatUnterstrichenZelle = Cells(Range(zellpositionSchriftformatUnterstrichenZelle).Row, Range(zellpositionSchriftformatUnterstrichenZelle).Column + 1).Address
zellpositionSchriftformatFarbeZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Color"))
zellpositionSchriftformatFarbeZelle = Cells(Range(zellpositionSchriftformatFarbeZelle).Row, Range(zellpositionSchriftformatFarbeZelle).Column + 1).Address
zellpositionSchriftformatGrößeZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Size"))
zellpositionSchriftformatGrößeZelle = Cells(Range(zellpositionSchriftformatGrößeZelle).Row, Range(zellpositionSchriftformatGrößeZelle).Column + 1).Address
zellpositionSchriftformatStandardgrößeZelle = Cells(zentralwertZeileDatenbeginn, spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Standard size"))).Address
schriftformatStandardschriftgröße = Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatStandardgrößeZelle)
zellpositionSchriftformatZeilenumbruchZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Line break"))
zellpositionSchriftformatZeilenumbruchZelle = Cells(Range(zellpositionSchriftformatZeilenumbruchZelle).Row, Range(zellpositionSchriftformatZeilenumbruchZelle).Column + 1).Address

schriftformatFettZuletzt = False
schriftformatKursivZuletzt = False
schriftformatUnterstrichenZuletzt = False
schriftformatFarbeZuletzt = ""
schriftformatGrößeZuletzt = 0

'Nur eine Zelle auf einmal
If Range(xRange).Count > 1 Then GoTo exitHandler

trennzeichenPos = 0
trennzeichenPos_alt = 0
stringStart_alt = 1
stringLänge = 0
textendeErreicht = False


'Wenn nur der Zeilenumbruch umgewandelt werden soll, dann hier ein einfaches Replace über den Text. Daher dann deutlich schneller, als zeichenweise auslesen, über die reguläre nachfolgende Schleife.
If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatZeilenumbruchZelle) = True And (Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFettZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatKursivZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatUnterstrichenZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFarbeZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatGrößeZelle) = False) Then
    sResult = Replace(Range(xRange), vbLf, "<br />")
    GoTo exitHandler
End If


Do
    'Falls aktuelles Zeichen ein Trennzeichen ist, dann dieses als einzelnes Zeichen verarbeiten.
    'Ansonsten prüfen, wo das nächste Trennzeichen ist.
    trennzeichenPos = 0
    sucheStart = stringStart_alt + stringLänge_alt
        
    If InStr(1, " .,!?:" & vbLf, Range(xRange).Characters(sucheStart, 1).text) > 0 Then
        stringStart = sucheStart
        trennzeichenPos = stringStart
        stringLänge = 1
    Else
        'In einem Array festhalten, an welche Position das nächste Trennzeichen kommt
        suchZeichenArray(1) = InStr(sucheStart, Range(xRange).Value, " ")
        suchZeichenArray(2) = InStr(sucheStart, Range(xRange).Value, ".")
        suchZeichenArray(3) = InStr(sucheStart, Range(xRange).Value, ",")
        suchZeichenArray(4) = InStr(sucheStart, Range(xRange).Value, "!")
        suchZeichenArray(5) = InStr(sucheStart, Range(xRange).Value, "?")
        suchZeichenArray(6) = InStr(sucheStart, Range(xRange).Value, ":")
        suchZeichenArray(7) = InStr(sucheStart, Range(xRange).Value, vbLf)
        'Array aufsteigend sortieren
        For i = 1 To 7
            For j = 1 To 7
                    If suchZeichenArray(i) < suchZeichenArray(j) Then
                    tmp = suchZeichenArray(i)
                    suchZeichenArray(i) = suchZeichenArray(j)
                    suchZeichenArray(j) = tmp
                End If
            Next
        Next
        'Die kleinste Trennzeichen-Position (ohne 0er) als nächste Position verwenden
        For i = 1 To 7
            If suchZeichenArray(i) <> 0 Then
                trennzeichenPos = suchZeichenArray(i)
                Exit For
            End If
        Next
        'Falls kein Trennzeichen mehr folgt, Trennzeichen-Position hier faken (letztes Zeichen+1)
        If trennzeichenPos = 0 Then
            trennzeichenPos = Len(Range(xRange).Value) + 1
        End If
        
        stringStart = stringStart_alt + stringLänge
        stringLänge = trennzeichenPos - trennzeichenPos_alt - 1
    End If
    
    'Merken, wenn Textende erreicht
    If stringStart + stringLänge - 1 = Len(Range(xRange).Value) Then textendeErreicht = True
    
    With Range(xRange).Characters(stringStart, stringLänge).Font
        
        'Fett
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFettZelle) = True Then
            If .Bold And schriftformatFettZuletzt <> True Then
                sFront = "<b>" & sFront
                schriftformatFettZuletzt = True
            End If
            'Falls Formatierung nicht mehr in Benutzung und zuvor war sie aktiv, dann Formatierung schließen
            If Not .Bold And schriftformatFettZuletzt <> False Then
                sFront = "</b>" & sFront
                schriftformatFettZuletzt = False
            End If
            'Falls am Textende und die Formatierung ist noch aktiv, dann noch Formatierung schließen
            If textendeErreicht = True And schriftformatFettZuletzt <> False Then
                sBehind = sBehind & "</b>"
                schriftformatFettZuletzt = False
            End If
        End If
        
        'Kursiv
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatKursivZelle) = True Then
            If .Italic And schriftformatKursivZuletzt <> True Then
                sFront = "<i>" & sFront
                schriftformatKursivZuletzt = True
            End If
            'Falls Formatierung nicht mehr in Benutzung und zuvor war sie aktiv, dann Formatierung schließen
            If Not .Italic And schriftformatKursivZuletzt <> False Then
                sFront = "</i>" & sFront
                schriftformatKursivZuletzt = False
            End If
            'Falls am Textende und die Formatierung ist noch aktiv, dann noch Formatierung schließen
            If textendeErreicht = True And schriftformatKursivZuletzt <> False Then
                sBehind = sBehind & "</i>"
                schriftformatKursivZuletzt = False
            End If
        End If
        
        'Unterstrichen
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatUnterstrichenZelle) = True Then
            If .Italic And schriftformatUnterstrichenZuletzt <> True Then
                sFront = "<u>" & sFront
                schriftformatUnterstrichenZuletzt = True
            End If
            'Falls Formatierung nicht mehr in Benutzung und zuvor war sie aktiv, dann Formatierung schließen
            If Not .Italic And schriftformatUnterstrichenZuletzt <> False Then
                sFront = "</u>" & sFront
                schriftformatUnterstrichenZuletzt = False
            End If
            'Falls am Textende und die Formatierung ist noch aktiv, dann noch Formatierung schließen
            If textendeErreicht = True And schriftformatUnterstrichenZuletzt <> False Then
                sBehind = sBehind & "</u>"
                schriftformatUnterstrichenZuletzt = False
            End If
        End If
        
        
        'Farbe
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFarbeZelle) = True Then
            aktSchriftfarbeText = .Color
            If VarType(aktSchriftfarbeText) = vbError Then aktSchriftfarbeText = "" 'wenn kein Fehler (Zeilenumbrüchen generieren Fehler)
                If aktSchriftfarbeText <> schriftformatFarbeZuletzt And schriftformatFarbeZuletzt = "" Then
                    If aktSchriftfarbeText <> 0 And aktSchriftfarbeText <> False Then
                        chrCol = fnGetCol(.Color)
                        If chrCol <> "NONE" Then
                            sFront = "<font color=#" & chrCol & ">" & sFront
                            schriftformatFarbeZuletzt = aktSchriftfarbeText
                        End If
                    End If
                End If
            'Falls Formatierung nicht mehr in Benutzung und zuvor war sie aktiv, dann Formatierung schließen
            If aktSchriftfarbeText <> schriftformatFarbeZuletzt And schriftformatFarbeZuletzt <> "" Then
                sFront = "</font>" & sFront
                schriftformatFarbeZuletzt = ""
            End If
            'Falls am Textende und die Formatierung ist noch aktiv, dann noch Formatierung schließen
            If textendeErreicht = True And schriftformatFarbeZuletzt <> "" Then
                sBehind = sBehind & "</font>"
                schriftformatFarbeZuletzt = ""
            End If
        End If

        'Schriftgröße
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        '- die Schriftgröße nicht der Standardschriftgröße entspricht
        '- für diese Schriftgröße eine Export-Schriftgröße definiert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatGrößeZelle) = True Then
            aktSchriftgrößeText = .Size
            If zentralwertSchriftgrößenTransformationenArray(aktSchriftgrößeText) <> 0 And aktSchriftgrößeText <> schriftformatStandardschriftgröße And aktSchriftgrößeText <> schriftformatGrößeZuletzt And (schriftformatGrößeZuletzt = 0 Or schriftformatGrößeZuletzt = schriftformatStandardschriftgröße) Then 'wenn nicht Standardschriftgröße und die Textschriftgröße entspricht einer zu transformierenden Schriftgröße
                sFront = "<h" & zentralwertSchriftgrößenTransformationenArray(aktSchriftgrößeText) & ">" & sFront
                schriftformatGrößeZuletzt = aktSchriftgrößeText
            End If
            'Falls Formatierung nicht mehr in Benutzung und zuvor war sie aktiv, dann Formatierung schließen
            If aktSchriftgrößeText <> schriftformatGrößeZuletzt And schriftformatGrößeZuletzt <> 0 And schriftformatGrößeZuletzt <> schriftformatStandardschriftgröße Then
                'sBehind = sBehind & "</h" & zentralwertSchriftgrößenTransformationenArray(schriftformatGrößeZuletzt) & ">"
                sFront = "</h" & zentralwertSchriftgrößenTransformationenArray(schriftformatGrößeZuletzt) & ">" & sFront
                schriftformatGrößeZuletzt = aktSchriftgrößeText
            End If
            'Falls am Textende und die Formatierung ist noch aktiv, dann noch Formatierung schließen
            If textendeErreicht = True And schriftformatGrößeZuletzt <> 0 And aktSchriftgrößeText <> schriftformatStandardschriftgröße Then
                sBehind = sBehind & "</h" & zentralwertSchriftgrößenTransformationenArray(schriftformatGrößeZuletzt) & ">"
                schriftformatGrößeZuletzt = aktSchriftgrößeText
            End If
        End If
    
        'Zeilenumbruch
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatZeilenumbruchZelle) = True Then
            If Range(xRange).Characters(stringStart, stringLänge).text = vbLf Then
                sFront = sFront & "<br />"
            End If
        End If
        
    End With
    
    sResult = sResult & sFront & Range(xRange).Characters(stringStart, stringLänge).text & sBehind
    
    sFront = ""
    sBehind = ""
    trennzeichenPos_alt = trennzeichenPos
    stringStart_alt = stringStart
    stringLänge_alt = stringLänge
    
    
    If textendeErreicht = True Then GoTo exitHandler
    
Loop While 1 = 1
    
exitHandler:
zelleInhaltZuHTMLWörter = sResult
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
sResult = Range(xRange).Value
GoTo exitHandler
    
End Function


Public Function zelleInhaltZuHTML(myCell As Range, Optional startPos As Long, Optional endePos As Long) As String
'Fügt in den Text HTML-Tags ein (geht Zeichen für Zeichen durch)

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("zelleInhaltZuHTML")
Application.EnableCancelKey = xlDisabled

Dim bldTagOn, itlTagOn, ulnTagOn, colTagOn As Boolean
Dim i, chrCount As Long
Dim chrCol, chrLastCol, htmlTxt As String
Dim spalteKonfigurationExporteinstellungen As Long
Dim zellpositionSchriftformatFettZelle As String
Dim zellpositionSchriftformatKursivZelle As String
Dim zellpositionSchriftformatUnterstrichenZelle As String
Dim zellpositionSchriftformatFarbeZelle As String
Dim zellpositionSchriftformatGrößeZelle As String
Dim zellpositionSchriftformatStandardgrößeZelle As String
Dim schriftformatStandardschriftgröße As Variant
Dim zellpositionSchriftformatZeilenumbruchZelle As String
Dim aktSchriftgrößeText As Variant
Dim heightTagOn As Boolean

bldTagOn = False
itlTagOn = False
ulnTagOn = False
colTagOn = False
chrCol = "NONE"
htmlTxt = ""
chrCount = myCell.Characters.Count

'schriftgrößenTransformationenInArray 'Kopiert die Einstellungen zu Schriftgrößen-Transformationen in ein Array


spalteKonfigurationExporteinstellungen = spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Export Configuration"))
    
zellpositionSchriftformatFettZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Bold"))
zellpositionSchriftformatFettZelle = Cells(Range(zellpositionSchriftformatFettZelle).Row, Range(zellpositionSchriftformatFettZelle).Column + 1).Address
zellpositionSchriftformatKursivZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Italic"))
zellpositionSchriftformatKursivZelle = Cells(Range(zellpositionSchriftformatKursivZelle).Row, Range(zellpositionSchriftformatKursivZelle).Column + 1).Address
zellpositionSchriftformatUnterstrichenZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Underline"))
zellpositionSchriftformatUnterstrichenZelle = Cells(Range(zellpositionSchriftformatUnterstrichenZelle).Row, Range(zellpositionSchriftformatUnterstrichenZelle).Column + 1).Address
zellpositionSchriftformatFarbeZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Color"))
zellpositionSchriftformatFarbeZelle = Cells(Range(zellpositionSchriftformatFarbeZelle).Row, Range(zellpositionSchriftformatFarbeZelle).Column + 1).Address
zellpositionSchriftformatGrößeZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Size"))
zellpositionSchriftformatGrößeZelle = Cells(Range(zellpositionSchriftformatGrößeZelle).Row, Range(zellpositionSchriftformatGrößeZelle).Column + 1).Address
zellpositionSchriftformatStandardgrößeZelle = Cells(zentralwertZeileDatenbeginn, spaltenNummernKonfigurationZurückgeben(mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Standard size"))).Address
schriftformatStandardschriftgröße = Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatStandardgrößeZelle)
zellpositionSchriftformatZeilenumbruchZelle = zellpositionZurückgeben(zentralwertTabellenblattNameKonfiguration, mehrsprachigkeitBegriffsrückgabe(gewählteSpracheZurückgeben(), "Font style: Line break"))
zellpositionSchriftformatZeilenumbruchZelle = Cells(Range(zellpositionSchriftformatZeilenumbruchZelle).Row, Range(zellpositionSchriftformatZeilenumbruchZelle).Column + 1).Address

If startPos = 0 Then startPos = 1
If endePos = 0 Then endePos = chrCount


'Wenn nur der Zeilenumbruch umgewandelt werden soll, dann hier ein einfaches Replace über den Text. Daher dann deutlich schneller, als zeichenweise auslesen, über die reguläre nachfolgende Schleife.
If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatZeilenumbruchZelle) = True And (Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFettZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatKursivZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatUnterstrichenZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFarbeZelle) = False And Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatGrößeZelle) = False) Then
    htmlTxt = Replace(myCell, vbLf, "<br />")
    GoTo exitHandler
End If


For i = startPos To endePos
    With myCell.Characters(i, 1)
    warteschleife 0, True
        'Farbe
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFarbeZelle) = True Then
            If (.Font.Color) Then
                chrCol = fnGetCol(.Font.Color)
                If Not colTagOn Then
                    htmlTxt = htmlTxt & "<font color=#" & chrCol & ">"
                    colTagOn = True
                Else
                    If chrCol <> chrLastCol Then htmlTxt = htmlTxt & "</font><font color=#" & chrCol & ">"
                End If
            Else
                chrCol = "NONE"
                If colTagOn Then
                    htmlTxt = htmlTxt & "</font>"
                    colTagOn = False
                End If
            End If
            chrLastCol = chrCol
        End If
        
        'Fett
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatFettZelle) = True Then
            If .Font.Bold = True Then
                If Not bldTagOn Then
                    htmlTxt = htmlTxt & "<b>"
                    bldTagOn = True
                End If
            Else
                If bldTagOn Then
                    htmlTxt = htmlTxt & "</b>"
                    bldTagOn = False
                End If
            End If
        End If
        
        'Kursiv
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatKursivZelle) = True Then
            If .Font.Italic = True Then
                If Not itlTagOn Then
                    htmlTxt = htmlTxt & "<i>"
                    itlTagOn = True
                End If
            Else
                If itlTagOn Then
                    htmlTxt = htmlTxt & "</i>"
                    itlTagOn = False
                End If
            End If
        End If
        
        'Unterstrichen
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatUnterstrichenZelle) = True Then
            If .Font.Underline > 0 Then
                If Not ulnTagOn Then
                    htmlTxt = htmlTxt & "<u>"
                    ulnTagOn = True
                End If
            Else
                If ulnTagOn Then
                    htmlTxt = htmlTxt & "</u>"
                    ulnTagOn = False
                End If
            End If
        End If
        
        'Schriftgröße
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        '- die Schriftgröße nicht der Standardschriftgröße entspricht
        '- für diese Schriftgröße eine Export-Schriftgröße definiert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatGrößeZelle) = True Then
            aktSchriftgrößeText = .Font.Size
            If zentralwertSchriftgrößenTransformationenArray(aktSchriftgrößeText) <> 0 And aktSchriftgrößeText <> schriftformatStandardschriftgröße Then 'wenn nicht Standardschriftgröße und die Textschriftgröße entspricht einer zu transformierenden Schriftgröße
                If Not heightTagOn Then
                    htmlTxt = htmlTxt & "<h" & zentralwertSchriftgrößenTransformationenArray(aktSchriftgrößeText) & ">"
                    heightTagOn = True
                    schriftformatGrößeZuletzt = aktSchriftgrößeText
                End If
            Else
                If heightTagOn Then
                    htmlTxt = htmlTxt & "</h" & zentralwertSchriftgrößenTransformationenArray(schriftformatGrößeZuletzt) & ">"
                    heightTagOn = False
                End If
            End If
        End If
        
        'Zeilenumbruch
        'Nur exportieren, wenn
        '- in der Konfiguration auch der Export aktiviert wurde
        If Sheets(zentralwertTabellenblattNameKonfiguration).Range(zellpositionSchriftformatZeilenumbruchZelle) = True Then
            If (Asc(.text) = 10) Then
                htmlTxt = htmlTxt & "<br />"
            End If
        End If
    
        'Jetzt noch das eigentliche Zeichen hinzufügen
        htmlTxt = htmlTxt & .text
    
    End With
Next

If colTagOn Then
    htmlTxt = htmlTxt & "</font>"
    colTagOn = False
End If
If bldTagOn Then
    htmlTxt = htmlTxt & "</b>"
    bldTagOn = False
End If
If itlTagOn Then
    htmlTxt = htmlTxt & "</i>"
    itlTagOn = False
End If
If ulnTagOn Then
    htmlTxt = htmlTxt & "</u>"
    ulnTagOn = False
End If
If heightTagOn Then
    htmlTxt = htmlTxt & "</h" & zentralwertSchriftgrößenTransformationenArray(schriftformatGrößeZuletzt) & ">"
    ulnTagOn = False
End If
htmlTxt = htmlTxt & ""
zelleInhaltZuHTML = htmlTxt

exitHandler:
zelleInhaltZuHTML = htmlTxt
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
htmlTxt = Range(myCell.Address).Value
GoTo exitHandler

End Function


Function fnGetCol(strCol As String) As String
'Gibt den Farbwert zurück

On Error GoTo errHandler
aktuelleFunktionsnummer = crc32HashErmitteln("fnGetCol")
Application.EnableCancelKey = xlDisabled

Dim rVal, gVal, bVal As String
strCol = Right("000000" & Hex(strCol), 6)
bVal = Left(strCol, 2)
gVal = Mid(strCol, 3, 2)
rVal = Right(strCol, 2)
fnGetCol = rVal & gVal & bVal

exitHandler:
fnGetCol = rVal & gVal & bVal
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
rVal = ""
gVal = ""
bVal = ""
GoTo exitHandler

End Function
