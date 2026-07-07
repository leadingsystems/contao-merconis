Attribute VB_Name = "Modul7_Debugging"
Option Explicit




Public Sub standardeinstellungen_schnell()
  Rem 12.09.2023, verkürzte Variante der "standardeinstellungen" um nur notwendige Variablen zu ermitteln

    'Dim starttimer As Long
    'starttimer = Timer
    On Error GoTo errHandler
    
    'aktuelleFunktionsnummer = crc32HashErmitteln("standardeinstellungen")
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
    'mehrsprachigkeitTabellenNamenÄndern
    'Sprachbezogene Änderungen durchführen
    'toDosNachSprachwechsel
    toDosNachSprachwechsel_schnell
    
    'Events nochmals deaktivieren, da toDosNachSprachwechsel diese standardmäßig zur Sicherheit immer aktivieren
    Application.EnableEvents = False
    
    zentraleVariablen2
    
    
    'Rem TBU, LIFE, den Startbildschirm wieder einkommentieren
    'If cModus = "Life" Then
    '    If zentralwertProgrammstart = True Then
    '        Splashscreen.Show 'Splashscreen zeigen und warten, bis er geschlossen ist
    '        warteschleife 1, True
    '    End If
    'End If
    
    aktiviereTabellenblatt 1 'Blatt 1 (Produktverwaltung) aktivieren
    zentralwertLetzteVerwendeteSpalteProduktverwaltung = letzteVerwendeteSpalteErmitteln(zentralwertTabellenblattNameProduktverwaltung)
    zentralwertLetzteVerwendeteSpalteKonfiguration = letzteVerwendeteSpalteErmitteln(zentralwertTabellenblattNameKonfiguration)
    zentralwertLetzteVerwendeteSpalteBasisparameter = letzteVerwendeteSpalteErmitteln(zentralwertTabellenblattNameBasisparameter)
    
    'Kopfbereich zu Beginn immer einblenden
    Range(kopfbereichAusblendenZelle) = False
'    zeilenAusEinblendenKopfbereich
    
'    Range(zentralwertMakrohinweisZelle).Font.Color = RGB(255, 255, 255) 'Makro-Hinweis ausblenden
    
    'Fenstereinstellungen
    'On Error Resume Next
    
    
    Rem TBU, LIFE, während der Entwicklung behindert das Vollbild eher
    'If cModus = "Life" Then
    '    Application.WindowState = xlMaximized
    '    ActiveWindow.WindowState = xlMaximized
    'End If
    
    
    'aktiviereTabellenblatt zentralwertTabellenblattNameProduktverwaltung
    'ActiveWindow.Split = False
    'ActiveWindow.SplitRow = zentralwertZeileÜberschriften
    'ActiveWindow.FreezePanes = True
    'Cells(zentralwertZeileDatenbeginn, 1).Select
    'aktiviereTabellenblatt zentralwertTabellenblattNameKonfiguration
    'ActiveWindow.Split = False
    'Cells(zentralwertZeileDatenbeginn, 1).Select
    'letzteVerwendeteZeile = letzteVerwendeteZeileErmitteln(ActiveSheet.Name)
    'ActiveWindow.SplitRow = zentralwertZeileÜberschriften
    'ActiveWindow.FreezePanes = True
    'aktiviereTabellenblatt zentralwertTabellenblattNameBasisparameter
    'ActiveWindow.Split = False
    'Cells(zentralwertZeileDatenbeginn, 1).Select
    'ActiveWindow.SplitRow = zentralwertZeileÜberschriften
    'ActiveWindow.FreezePanes = True
    'aktiviereTabellenblatt zentralwertTabellenblattNameLöschprotokoll
    'ActiveWindow.Split = False
    'Cells(zentralwertZeileDatenbeginn, 1).Select
    'ActiveWindow.SplitRow = zentralwertZeileÜberschriften
    'ActiveWindow.FreezePanes = True
    'aktiviereTabellenblatt zentralwertTabellenblattNameProduktverwaltung
    'Cells(zentralwertZeileÜberschriften, zentralwertProduktverwaltungSpalteBezeichnung).Select
    'Cells(zentralwertZeileDatenbeginn, 1).Select
    'On Error GoTo errHandler
    
    'Versteckte Zeilen auf allen Tabellenblättern ausblenden
    'On Error Resume Next 'Bei aktivem Blattschutz sonst Fehler
    'For tabellenblattZähler = 1 To 4
    '    Sheets(tabellenblattZähler).Range(zentralwertZeileVersteckteWerte & ":" & zentralwertZeileDatenbanknamen).EntireRow.Hidden = True
    'Next tabellenblattZähler
    'On Error GoTo errHandler
    
    'Application.Goto Cells(zentralwertZeileDatenbeginn, 1), Scroll:=True  'Links oben in erste Datenzelle springen
    
    'Gitter ausblenden
    'ActiveWindow.DisplayGridlines = False
    
    'Alle bedingten Formatierungen, Datenüberprüfungen sowie feste Formeln frisch setzen
    'bedingteFormatierungenNeuSetzen
    'datenüberprüfungNeuSetzen
    'formelnNeuSetzen
    
    'Kontextmenü hinzufügen
    'kontextmenue_erweitern
    
    'Darstellung der Gruppierung ändern
    'With Sheets(zentralwertTabellenblattNameProduktverwaltung).Outline
    '    .AutomaticStyles = False
    '    .SummaryRow = xlAbove
    '    .SummaryColumn = xlRight
    'End With
    '
    'Application.Goto Cells(zentralwertZeileDatenbeginn, 1), Scroll:=True  'Links oben in erste Datenzelle springen
    
    
    'Backup erstellen
    'LIFE: diesen Antwort-Block wieder einkommentieren
    'If cModus = "Life" Then
    '    antwort = MsgBox(mehrsprachigkeitTextrückgabeAktuelleSprache(aktuellGewählteSprache, "backupErstellen01"), 4, mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "backupErstellen02"))
    '    If antwort = vbYes Then
    '        SaveBackup
    '    End If
    'End If

exitHandler:
    Application.EnableEvents = True
    'Application.CalculateFull
    Application.ScreenUpdating = True
    Application.Calculation = xlCalculationManual 'Automatische Formelberechnung durch Excel DEAKTIVIEREN
    
    Rem TBU, LIFE, den Startbildschirm wieder einkommentieren
    'If cModus = "Life" Then
    '    If zentralwertProgrammstart = True Then
    '        zentralwertProgrammstart = False
    '        Splashscreen.buttonAbbrechen.Visible = True 'Beenden-Button im Splashscreen bei Programmstart aktivieren
    '    End If
    'End If
    Exit Sub

errHandler:
    'MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
    MsgBox "Fehler in Standardeinstellungen_schnell: " & Err.Description, vbOKOnly
    GoTo exitHandler

End Sub


Public Sub toDosNachSprachwechsel_schnell()
  Rem 12.09.2023, verkürzte Variante der "toDosNachSprachwechsel"
  Rem   Arbeiten nach einem Sprachwechsel

    aktuelleFunktionsnummer = crc32HashErmitteln("toDosNachSprachwechsel")
    
    On Error GoTo errHandler
'Application.EnableCancelKey = xlDisabled

  Dim alterEnableEventsStatus As Boolean
  Dim alterScreenUpdateingStatus As Boolean

    aktuellGewählteSprache = gewählteSpracheZurückgeben()
    If aktuellGewählteSprache = 0 Then
        alterEnableEventsStatus = aktuellerEnableEventsStatus
        Application.EnableEvents = False
        Sheets(zentralwertTabellenblattNameProduktverwaltung).Range(seitenwählerZelle) = 1
        aktuellGewählteSprache = 1
        Sheets(zentralwertTabellenblattNameProduktverwaltung).Range(gewählteSpracheZelle) = aktuellGewählteSprache
        Application.EnableEvents = alterEnableEventsStatus
    End If
    
    mehrsprachigkeitTabellenNamenÄndern 'Tabellennamen ändern
    Application.CalculateFull
    
    spaltenDatenbanknamenProduktverwaltungInArray
    spaltenÜberschriftenProduktverwaltungInArray
    spaltenÜberschriftenKonfigurationInArray
    spaltenÜberschriftenBasisparameterInArray
    
'    kontextmenue_erweitern 'Auch die Kontextmenüs wegen Mehrsprachigkeit neu erstellen
    
    Cells(zentralwertZeileDatenbeginn, 1).Select
    
'    Application.CalculateFull

exitHandler:
    Application.EnableEvents = True 'Hier zur Sichereit EnableEvents immer aktivieren, falls zwischenzeitlich etwas schief gelaufen sein sollte
    Application.ScreenUpdating = True 'Ebenso zur Sicherheit das ScreenUpdating
    Exit Sub

errHandler:
    MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
    GoTo exitHandler
End Sub
