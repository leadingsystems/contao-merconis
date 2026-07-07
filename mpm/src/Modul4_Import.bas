Attribute VB_Name = "Modul4_Import"
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

Public Sub zeilenLöschen()
'Löschen der aktuell markierten Zeilen und zuvor kopieren/einfügen ins Löschprotokoll

aktuelleFunktionsnummer = crc32HashErmitteln("zeilenLöschen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

Dim bereichLöschenZeilenString As String
Dim AnzahlZeilen As Long
Dim X As Long

If ActiveSheet.Name <> mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "ProductManagement") Then Exit Sub 'Raus, wenn nicht Tabellenblatt Produktmanager

bereichLöschenZeilenString = Selection.Address

Sheets(zentralwertTabellenblattNameLöschprotokoll).Select

'So viele Leerzeilen ganz oben einfügen, wie benötigt
For X = 1 To AnzahlZeilen + 3
    Cells(zentralwertZeileDatenbeginn, 1).EntireRow.Insert
Next X

Cells(zentralwertZeileDatenbeginn, 1) = Date & " / " & Time
Rows(zentralwertZeileDatenbeginn).Interior.Color = 255

Sheets(zentralwertTabellenblattNameProduktverwaltung).Select
Selection.Cut

Sheets(zentralwertTabellenblattNameLöschprotokoll).Select
Rows(zentralwertZeileDatenbeginn + 1).Select
Selection.Insert Shift:=xlDown

Sheets(zentralwertTabellenblattNameProduktverwaltung).Select
Selection.Delete Shift:=xlUp

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Public Sub variantenErstellen()
'Aufruf des VariantCreators

aktuelleFunktionsnummer = crc32HashErmitteln("variantenErstellen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim alterEnableEventsStatus As Boolean
Dim alterScreenUpdateingStatus As Boolean
alterEnableEventsStatus = aktuellerEnableEventsStatus
alterScreenUpdateingStatus = aktuellerScreenUpdatingStatus
Application.EnableEvents = False
Application.ScreenUpdating = False

If ActiveSheet.Name <> mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "ProductManagement") Then Exit Sub 'Raus, wenn nicht Tabellenblatt Produktmanager

'VariantCreator-Formular anzeigen
VariantCreator.Show

exitHandler:
Application.EnableEvents = alterEnableEventsStatus
Application.ScreenUpdating = alterScreenUpdateingStatus
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Sub importUpdateDurchführen()
'Produktdaten/Konfig aus einem anderen Produktmanager importieren

aktuelleFunktionsnummer = crc32HashErmitteln("importUpdateDurchführen")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

'Formular aufrufen
ImportUpdate.Show

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub



Sub sVertikaleUeberschriftenFormatieren(sName$, Optional bMergeSetzen As Boolean = True, Optional lZellLaenge&)
  Rem 06.09.2023, beim Import/Update Vorgang werden bestehende Daten (Produkte/Konfiguration) vorher gelöscht. Beim
  Rem Konfigurationsblatt gibt es aber vertikale Überschriften (Export, Bedienung etc.), die beim löschen dann abgeschnitten werden
  Rem Hier werden die darunter stehenden Zellen wieder verbunden (merge)
  Rem sName ist der Begriff, der in der Zell-Formel (Zeile 11) als zweiter Parameter angegeben ist
  Rem lZellLaenge   ist die Anzahl von Zellen (unterhalb der Header-Zeile 11) die gemerget werden müssen
  Rem bMergeSetzen  entweder true, wenn Merge gesetzt werden soll, oder false, wenn nicht
  Dim sUeberschrift$
  Dim lngSpalte&
  
    On Error GoTo errHandler

    Rem die eigentlichen Überschrift-Spalten haben gar keinen eigenen Spalten-Key (in Zeile 10) und außerdem findet man die Spalten
    Rem auch nicht, wenn man das Array "zentralwertKonfigurationÜberschriftenSpaltenBezeichnungenArray" durchsucht
    sUeberschrift = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, sName)
    lngSpalte = spaltenNummernKonfigurationZurückgeben(sUeberschrift)

    Rem die eigentliche Header-Spalte ist (in allen 4 Fällen) 2 links von der Zelle mit dem Namen
    lngSpalte = lngSpalte - 2
    If lngSpalte <= 0 Then
        GoTo exitHandler
    End If
  
    If ActiveSheet.Name <> zentralwertTabellenblattNameKonfiguration Then
        Sheets(zentralwertTabellenblattNameKonfiguration).Select
    End If
    
    Range(Cells(zentralwertZeileÜberschriften, lngSpalte), Cells(zentralwertZeileÜberschriften + lZellLaenge, lngSpalte)).Select
    Selection.MergeCells = bMergeSetzen
    
exitHandler:
    Exit Sub

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("sVertikaleUeberschriftenFormatieren")
    MsgBox "Error: " & aktuelleFunktionsnummer & " " & Err.Description, vbOKOnly
    GoTo exitHandler
End Sub
