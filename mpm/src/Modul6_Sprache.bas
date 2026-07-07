Attribute VB_Name = "Modul6_Sprache"
Option Explicit

'#############################################################################################################
'#Copyright by Leading Systems, Waiblingen, Germany. Usage allowed only with MERCONIS!
'#Not allowed: Code modification and standalone distribution (without MERCONIS).
'#############################################################################################################

'Public Sub mehrsprachigkeitBegriffsdefinitionenInArray()
''Hier werden alle mehrsprachigen Begriffe in einem  Array aufgeführt
'
'aktuelleFunktionsnummer = crc32HashErmitteln("mehrsprachigkeitBegriffsdefinitionenInArray")
'On Error GoTo errHandler
'Application.EnableCancelKey = xlDisabled
'
'Dim sprachzähler As Long
'Dim mehrsprachigkeitEnglisch As Long
'Dim mehrsprachigkeitDeutsch As Long
'Dim mehrsprachigkeitExport As Long
'
'sprachzähler = 0
'mehrsprachigkeitEnglisch = 1
'mehrsprachigkeitDeutsch = 2
'mehrsprachigkeitExport = 99
'
''Spaltenüberschriften
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Sort number"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Sortiernummer"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Ignore"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Importsperre"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Delete"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Löschen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Publish"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Veröffentlichen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Typ"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Group No."
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Gruppen-Nr."
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Product code"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Artikel-Nr."
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Parent product code"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Übergeordnete Artikel-Nr."
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Language"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Sprache"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Name"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Bezeichnung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Alias"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Alias"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 01"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 01"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 01"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 01"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 02"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 02"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 02"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 02"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 03"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 03"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 03"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 03"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 04"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 04"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 04"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 04"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 05"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 05"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 05"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 05"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 06"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 06"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 06"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 06"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 07"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 07"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 07"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 07"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 08"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 08"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 08"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 08"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 09"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 09"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 09"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 09"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 10"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 10"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 10"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 10"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 11"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 11"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 11"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 11"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 12"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 12"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 12"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 12"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 13"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 13"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 13"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 13"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 14"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 14"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 14"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 14"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 15"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 15"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 15"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 15"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 16"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 16"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 16"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 16"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 17"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 17"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 17"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 17"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 18"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 18"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 18"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 18"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 19"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 19"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 19"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 19"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property 20"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmal 20"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value 20"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägung 20"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Description"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Beschreibung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Short description"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Kurzbeschreibung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Category"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Kategorie"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Producer"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Hersteller"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Use group prices"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Gruppenpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Mitgliedergruppen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Use group prices"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Gruppenpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Use group prices"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Gruppenpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Use group prices"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Gruppenpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Use group prices"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Gruppenpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Use group prices"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Gruppenpreise anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Mitgliedergruppen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Mitgliedergruppen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Mitgliedergruppen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Mitgliedergruppen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Mitgliedergruppen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Use scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Staffelpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Use scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Staffelpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Use scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Staffelpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Use scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Staffelpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Use scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Staffelpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Use scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Staffelpreis anwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Scale price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Art der Staffelpreisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Scale price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Art der Staffelpreisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Scale price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Art der Staffelpreisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Scale price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Art der Staffelpreisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Scale price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Art der Staffelpreisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Scale price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Art der Staffelpreisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Quantity detection method"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Methode zur Mengenermittlung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Quantity detection method"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Methode zur Mengenermittlung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Quantity detection method"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Methode zur Mengenermittlung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Quantity detection method"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Methode zur Mengenermittlung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Quantity detection method"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Methode zur Mengenermittlung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Quantity detection method"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Methode zur Mengenermittlung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Always separate different configurations"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Unterschiedliche Konfigurationen stets trennen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Always separate different configurations"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Unterschiedliche Konfigurationen stets trennen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Always separate different configurations"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Unterschiedliche Konfigurationen stets trennen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Always separate different configurations"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Unterschiedliche Konfigurationen stets trennen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Always separate different configurations"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Unterschiedliche Konfigurationen stets trennen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Always separate different configurations"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Unterschiedliche Konfigurationen stets trennen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Scale price keyword"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Staffelpreis-Schlüsselwort"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Scale price keyword"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Staffelpreis-Schlüsselwort"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Scale price keyword"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Staffelpreis-Schlüsselwort"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Scale price keyword"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Staffelpreis-Schlüsselwort"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Scale price keyword"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Staffelpreis-Schlüsselwort"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Scale price keyword"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Staffelpreis-Schlüsselwort"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Staffelpreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Staffelpreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Staffelpreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Staffelpreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Staffelpreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Staffelpreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Old price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Alter Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Old price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Alter Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Old price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Alter Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Old price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Alter Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Old price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Alter Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Old price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Alter Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Old price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Alter Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Old price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Alter Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Old price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Alter Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Old price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Alter Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Old price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Alter Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Old price: Price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Alter Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Old price: Use"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Alter Preis: Verwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G1] Old price: Use"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G1] Alter Preis: Verwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G2] Old price: Use"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G2] Alter Preis: Verwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G3] Old price: Use"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G3] Alter Preis: Verwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G4] Old price: Use"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G4] Alter Preis: Verwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "[G5] Old price: Use"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "[G5] Alter Preis: Verwenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Mitgliedergruppen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Tax class"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Steuersatz"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Weight"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Gewicht"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Weight type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Art der Gewichtsangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Unit"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Mengeneinheit"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Quantity comparison unit"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Einheit für Mengenvergleichspreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Quantity comparison divisor"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Teiler zur Berechnung des Mengenvergleichspreises"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Quantity decimals"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Nachkommastellen für die Menge"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "New"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Neuheit"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "On sale"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Sonderpreis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Keywords"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schlüsselwörter"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Image"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Hauptbild"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "More images"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Weitere Bilder"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Change stock"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Lagerbest.-Änd."
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Settings for stock and delivery time"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Einstellungen zu Lagerbestand und Lieferzeit"
'sprachzähler = sprachzähler + 1
'
'
''TBU, 26.05.2023,
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Preordering Allowed"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Vorbestellungen erlaubt"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Settings for stock and delivery time in preorder Phase"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Einstellungen zu Lagerbestand und Lieferzeit in Vorbestellungsphase"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Available from"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Verfügbar ab"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Override availability settings of parent product"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Verfügbarkeits-Einstellungen des übergeordneten Produkts überschreiben"
'sprachzähler = sprachzähler + 1
'
'
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Recommended products"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Empfohlene Produkte"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Configurator"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Konfigurator"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Template"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Template für Produktdarstellung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent1"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent1"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent2"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent2"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent3"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent3"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent4"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent4"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent5"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent5"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent6"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent6"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent7"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent7"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent8"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent8"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent9"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent9"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent10"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent10"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent1 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent1 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent2 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent2 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent3 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent3 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent4 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent4 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent5 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent5 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent6 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent6 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent7 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent7 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent8 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent8 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent9 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent9 sprachunabhängig"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "flexContent10 language independent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "flexContent10 sprachunabhängig"
'
''Produkttypen-Kombinationen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "/Product/Product (foreign language)/Variant/Variant (foreign language)/"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "/Produkt/Produkt (Fremdsprache)/Variante/Variante (Fremdsprache)/"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "/Product/"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "/Produkt/"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "/Variant/"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "/Variante/"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "/Product/Variant/"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "/Produkt/Variante/"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "/Product (foreign language)/Variant/Variant (foreign language)/"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "/Produkt (Fremdsprache)/Variante/Variante (Fremdsprache)/"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "/Product (foreign language)/Variant (foreign language)/"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "/Produkt (Fremdsprache)/Variante (Fremdsprache)/"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "/Product/Product (foreign language)/"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "/Produkt/Produkt (Fremdsprache)/"
'
'
'
''Produkttypen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Product"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Produkt"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "product"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Variant"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Variante"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "variant"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Product (foreign language)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Produkt (Fremdsprache)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "productLanguage"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Variant (foreign language)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Variante (Fremdsprache)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "variantLanguage"
'
'
'
''Preistypen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Fixed price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Anpassung mit fixem Preis"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "fixed"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Percent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Prozentuale Anpassung"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "percentaged"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Independent price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Eigenständiger Preis"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "independent"
'
'
''Staffelpreistypen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Fixed scale price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Feste Preisangabe"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "scalePriceStandalone"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Percentaged adjustment"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Prozentuale Anpassung"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "scalePricePercentaged"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Adjustment with a fixed value"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Anpassung mit festem Wert"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "scalePriceFixedAdjustment"
'
'
''Staffelpreis Mengenvergleichsmethoden
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Separated by products, variants and configurations"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Produkte, Varianten und Konfigurationen getrennt"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "separatedVariantsAndConfigurations"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Separated by products and variants"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Produkte und Varianten getrennt"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "separatedVariants"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Separated by products"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Produkte getrennt"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "separatedProducts"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Summarized by scale price keyword"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Zusammengefasst nach Staffelpreis-Schlüsselwort"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "separatedScalePriceKeywords"
'
'
''Gewichtstypen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Fixed weight"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Anpassung mit fixem Gewicht"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "fixed"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Percent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Prozentuale Anpassung"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "percentaged"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Independent weight"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Eigenständiges Gewicht"
'mehrsprachigkeitBegriffe(mehrsprachigkeitExport, sprachzähler) = "independent"
'
'
'
''Überschriften
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Your products"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Ihre Produkte"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Your configuration"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Konfiguration"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Basic parameters"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Basisparameter"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Deletion log"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "MERCONIS PRODUCT MANAGER: Löschprotokoll"
'
'
''Tabellenblattbezeichnungen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "ProductManagement"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Produktverwaltung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Variants"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Varianten"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Configuration"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Konfiguration"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "BasicParameters"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Basisparameter"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "DeletionLog"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Löschprotokoll"
'
'
''Programmsprachen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Languages"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Sprachen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "English"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "English"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Deutsch"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Deutsch"
'
'
''Sonstiges
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Go to page"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Gehe zu Seite"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Sheet names"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Tabellenblattnamen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Action"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Aktion"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "File"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Datei"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Ready!"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Fertig!"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Question"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Frage"
'
'
''Kontextmenü
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Delete row(s)!"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Zeile(n) löschen!"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Create variants"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Varianten erstellen!"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Variants visible"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Varianten anzeigen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Variants hidden"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Varianten ausblenden"
'
''Aktionswähler
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Delete >>marked for deletion<<"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Löschen >>zu löschende<<"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Delete >>ignored products<<"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Löschen >>mit Importsperre<<"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Parent product code >>full auto mode<<"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Übergeordnete Art.-Nr. >>vollautom. ermitteln<<"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Parent product code >>semi auto mode (run now)<<"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Übergeordnete Art.-Nr. >>halbautom. (jetzt)<<"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Export >>as CSV file<<"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Exportieren >>als CSV-Datei<<"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Export marked rows >>as CSV file<<"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Markierte Zeilen exportieren >>als CSV-Datei<<"
'
'
''Konfig: Grundeinstellungen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Property name"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmalsbezeichnung"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Value name"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Ausprägungsbezeichnung"
'
'
''Konfig: CSV-Export-Einstellungen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Export"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Export"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Content transformation"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Umwandlung des Inhaltes"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Content transformation on/off"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Umwandlung des Inhaltes an/aus"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Transformation: Font size"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Umwandlung: Schriftgröße"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Size in Product Manager"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Größe im Produktmanager"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Size in export file"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Größe in der Exportdatei"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Font style: Bold"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schriftformat: Fett"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Font style: Italic"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schriftformat: Kursiv"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Font style: Underline"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schriftformat: Unterstrichen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Font style: Color"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schriftformat: Farbe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Font style: Size"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schriftformat: Größe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Font style: Line break"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schriftformat: Zeilenumbruch"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Method: Letters (slower)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Methode: Buchstaben (langsamer)"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Method: Words (faster)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Methode: Wörter (schneller)"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Deactivate transformation"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Umwandlung deaktivieren"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Font style: Standard size"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Schriftformat: Standardgröße"
'
''Konfig: Usability
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Usability"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Bedienung"
'
''Konfig: VariantCreator
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "VariantCreator"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "VariantCreator"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "VariantCreator Settings"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "VariantCreator Einstellungen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "VariantCreator Setting Values"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "VariantCreator Einstellungswerte"
'
''Konfig: Eigene Vorlagen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Own (text-)templates"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Eigene (Text-)vorlagen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Name of (Text-)template"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Name der (Text-)Vorlage"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Text of (Text-)template"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Text der (Text-)Vorlage"
'
''Basisparameter: Grundeinstellungen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Actions01"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Aktionen01"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Info"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Info"
'
'
''Aktionen
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Auto row height"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Automatische Zeilenhöhe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Create backup"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Backup erstellen"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Import/Update"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Import/Update"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Hide/show variants"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Varianten ein-/ausblenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Hide/show header"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Kopfbereich ein-/ausblenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Hide/show properties and values"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merkmale & Ausprägungen ein-/ausblenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Hide/show scale prices"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Staffelpreise ein-/ausblenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Hide/show member groups (0 to 5)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Mitgliedergruppen ein-/ausblenden (0 bis 5)"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Hide/show FlexContent"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "FlexContent ein-/ausblenden"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Select categories/member groups"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Kategorien/Mitgliedergruppen auswählen"
'
'
''VariantCreator
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "PROPERTIES"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "MERKMALE"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "VALUES"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "AUSPRÄGUNGEN"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Load/save settings"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Einstellungen laden/sichern"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Keep"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Merken"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Languages incl.?"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Inkl. Fremdsprachen?"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Code from main product"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Artikel-Nr. aus Hauptartikel"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Prefix/suffix for Code"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Artikel-Nr.-Zusatz"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Prefix start"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Präfix-Start"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Suffix start"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Suffix-Start"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Sep."
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Sep."
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Price: price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Old price"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Alter Preis"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Old price: price type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Alter Preis: Art der Preisangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Weight"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Gewicht"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Weight type"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Art der Gewichtsangabe"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Change stock"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Lagerb.-Änd."
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Delivery time"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Lieferzeit"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Main Image"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Hauptbild"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "More images"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Weitere Bilder"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "LOAD"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "LADEN"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "SAVE"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "SICHERN"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "DELETE"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "LÖSCHEN"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "CREATE"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "ERSTELLEN"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "CLOSE"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "SCHLIESSEN"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "MC"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "HP"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Formula"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Formel"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Separate diff. configs"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Untersch. Konfigs trennen"
'
'
''Konfig: VariantCreator
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "AUTOSAVE" 'nicht umbenennen
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "AUTOSAVE" 'nicht umbenennen
'
'
''Import/Update
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "SOURCE"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "QUELLE"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "IMPORT"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "IMPORT"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "CLOSE"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "BEENDEN"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Importing configuration"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Importiere Konfiguration"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Importing structure"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Importiere Struktur"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Importing column"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Importiere Spalte"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Source file"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Quelldatei"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "MPM version"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "MPM-Version"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Number of products"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Anzahl Produkte"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Number of variants"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Anzahl Varianten"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Number of language entries"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Anzahl Fremdspracheinträge"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Current step of import"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Aktueller Importschritt"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Import result/errors"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Importresultat/Fehler"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Configuration"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Konfiguration"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Structure of products"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Produktstruktur"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Columns (configuration)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Spalten (Konfiguration)"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Columns (products)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Spalten (Produkte)"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Missing columns (configuration)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Fehlende Spalten (Konfiguration)"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Missing columns (products)"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Fehlende Spalten (Produkte)"
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Number of imported products"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Anzahl der importierten Produkte"
'
''Sonstiges
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "Please enable macros!"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "Bitte Makros aktivieren!"
'
''Infotext
'sprachzähler = sprachzähler + 1
'mehrsprachigkeitBegriffe(mehrsprachigkeitEnglisch, sprachzähler) = "MERCONIS PRODUCT MANAGER - Version " & zentralwertEPMVersion & vbCr & "Copyright: Leading Systems GmbH, Korb, Deutschland" & vbCr & "http://www.merconis.com"
'mehrsprachigkeitBegriffe(mehrsprachigkeitDeutsch, sprachzähler) = "MERCONIS PRODUCT MANAGER - Version " & zentralwertEPMVersion & vbCr & "Copyright: Leading Systems GmbH, Korb, Deutschland" & vbCr & "http://www.merconis.com"
'
'exitHandler:
'Exit Sub
'
'errHandler:
'MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
'GoTo exitHandler
'
'End Sub

Public Function mehrsprachigkeitTextrückgabeAktuelleSprache(sprache, text)
'Hier werden alle mehrsprachigen Texte (also nicht nur Begriffe) zurückgegeben

aktuelleFunktionsnummer = crc32HashErmitteln("mehrsprachigkeitTextrückgabeAktuelleSprache")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

Dim mehrsprachigkeitEnglisch As Long
Dim mehrsprachigkeitDeutsch As Long
Dim TextInEnglisch As String
Dim TextInDeutsch As String

mehrsprachigkeitEnglisch = 1
mehrsprachigkeitDeutsch = 2

text = LCase(text)

Select Case text
'Splashscreen
Case "infotext"
    TextInEnglisch = "MERCONIS PRODUCT MANAGER - Version " & zentralwertEPMVersion & vbLf & vbLf & "Copyright: Leading Systems GmbH, Korb, Germany" & vbLf & vbLf & "Website: http://www.merconis.com" & vbLf & "Forum: https://www.merconisforum.com"
    TextInDeutsch = "MERCONIS PRODUCT MANAGER - Version " & zentralwertEPMVersion & vbLf & vbLf & "Copyright: Leading Systems GmbH, Korb, Deutschland" & vbLf & vbLf & "Website: http://www.merconis.com" & vbLf & "Forum: https://www.merconisforum.com"

'Bitte warten
Case "wait"
    TextInEnglisch = "Please wait..."
    TextInDeutsch = "Bitte warten..."

'Backup erstellen
Case "backuperstellen01"
    TextInEnglisch = "Would you like to make a backup of your MPM first?"
    TextInDeutsch = "Es wird empfohlen, ein Backup zu erstellen? Möchten Sie eines erstellen?"
Case "backuperstellen02"
    TextInEnglisch = "Make backup"
    TextInDeutsch = "Backup erstellen"
Case "backuperstellt"
    TextInEnglisch = "Backup created"
    TextInDeutsch = "Backup erstellt"

'#####Dies sind Texte des MPM. Bitte diejenigen Zeilen übersetzen, in denen "ÜBERSETZEN!" steht.
'#####Die vereinzelten Befehle, wie "vbCr", bitte stehen lassen. Immer nur das, was jeweils zwischen den doppelten Anführungsstrichen stehen, übersetzen.

'Löschen von markierten Zeilen
Case "loeschen01"
    TextInEnglisch = "Are you really sure that you want to definitely delete all entries marked for deletion (delete line) in the product manager, too?" & vbCr & vbCr & "If you delete products here, they will NOT also be deleted automatically in the online shop! You must first carry out an import in the online shop including the products marked for deletion! After that you can also delete the products in the product manager."
    TextInDeutsch = "Sind Sie wirklich sicher, dass Sie alle zum Löschen markierten Einträge, auch hier in der Produktverwaltung endgültig löschen (Zeile entfernen) möchten?" & vbCr & vbCr & "Wenn Sie die Produkte hier löschen, werden diese dadurch NICHT automatisch auch im Online-Shop gelöscht! Sie müssen im Online-Shop zuvor einen Import vornehmen - inkl. der für die Löschung markierten Produkte! Danach können Sie diese auch hier in der Produktverwaltung löschen."
Case "loeschen02"
    TextInEnglisch = "Really delete?"
    TextInDeutsch = "Wirklich löschen?"
Case "loeschen03"
    TextInEnglisch = "Are you really sure that you want to definitely delete all entries blocked for import (delete line) in the product manager, too?"
    TextInDeutsch = "Sind Sie wirklich sicher, dass Sie alle für den Import gesperrten Einträge, auch hier in der Produktverwaltung endgültig löschen (Zeile entfernen) möchten?"
Case "bittewarten"
    TextInEnglisch = "Please wait"
    TextInDeutsch = "Bitte warten"
Case "bittewartenloeschen"
    TextInEnglisch = "Please wait. Deleting..."
    TextInDeutsch = "Bitte warten. Lösche..."
Case "bittewartengelöscht"
    TextInEnglisch = "Deleted: "
    TextInDeutsch = "Gelöscht: "
Case "gelöschtezeilen"
    TextInEnglisch = "Deleted rows: "
    TextInDeutsch = "Gelöschte Zeilen: "

'Sonstiges
Case "änderungenverboten"
    TextInEnglisch = "Caution: Changes are not permitted here!"
    TextInDeutsch = "Achtung: Sie dürfen hier keine Änderungen vornehmen!"
Case "achtungformelnüberschreiben"
    TextInEnglisch = "Caution! You have tried to overwrite one or several cells containing formulas."
    TextInDeutsch = "Achtung: Sie wollten eine oder mehrere Zellen überschreiben, die Formeln enthalten."
Case "funktionnichtunterstützt"
    TextInEnglisch = "This function is not available in this (older) Excel version!"
    TextInDeutsch = "Diese Funktion steht Ihnen in dieser (älteren) Excel-Version nicht zur verfügung!"
Case "fehleraufgetretenneuinitialisierung"
    TextInEnglisch = "A fault has occurred (that could be warded off). The Excel product manager will be re-initialized. Changes will not be lost."
    TextInDeutsch = "Es ist ein Fehler aufgetreten (der abgefangen wurde). Der Excel-Produktmanager wird neu initialisiert. Änderungen gehen nicht verloren."
Case "error"
    TextInEnglisch = "An error has occured! Error code: "
    TextInDeutsch = "Ein Fehler ist aufgetreten! Fehler Nummer: "

'Import
Case "importfrage01"
    Rem 05.09.2023, gewünschte Textänderung
    'TextInEnglisch = "Are you sure that you want to import the data from the selected project manager?" & vbLf & vbLf & "Please note that the current configuration data will also be overwritten by the imported data." & vbLf & vbLf & "We strongly recommend you to carry out a backup first!"
    'TextInDeutsch = "Sind Sie sicher, dass Sie die Daten aus dem gewählten Projektmanager importieren möchten?" & vbLf & vbLf & "Bitte beachten Sie, dass ebenfalls die aktuellen Konfigurationsdaten durch die importierten überschrieben werden." & vbLf & vbLf & "Es wird dringend empfohlen, zuvor ein Backup zu erstellen!"
    TextInEnglisch = "Are you sure that you want to import the data from the selected project manager?" & _
        vbLf & vbLf & _
        "ATTENTION!" & _
        vbLf & _
        "Please note that all data, both the product data and the current configuration data, will be overwritten by the imported data." & _
        vbLf & vbLf & _
        "We strongly recommend you to carry out a backup first!"
    TextInDeutsch = "Sind Sie sicher, dass Sie die Daten aus dem gewählten Projektmanager importieren möchten?" & _
        vbLf & vbLf & _
        "ACHTUNG!" & _
        vbLf & _
        "Bitte beachten Sie, dass sämtliche Daten sowohl die Produktdaten als auch die aktuellen Konfigurationsdaten durch die importierten überschrieben werden." & _
        vbLf & vbLf & _
        "Es wird dringend empfohlen, zuvor ein Backup zu erstellen!"
Case "importok01"
    TextInEnglisch = "Import completed." & vbLf & vbLf & "You can find the imported products starting in line "
    TextInDeutsch = "Die Übernahme ist abgeschlossen." & vbLf & vbLf & "In der Produktverwaltung stehen nun die übertragenen Produkte ab Zeile "
Case "importok02"
    TextInEnglisch = "The configuration data have also been imported." & vbLf & vbLf & "Please carry out spot checks of the imported data to be on the safe side."
    TextInDeutsch = "Ebenso wurden die Konfigurationen übertragen." & vbLf & vbLf & "Prüfen Sie zur Sicherheit stichprobenartig die übernommenen Daten."
Case "importfehler"
    TextInEnglisch = "Import completed with FAULTS." & vbLf & vbLf & "Please check the import information as well as the imported data."
    TextInDeutsch = "Die Übernahme wurde mit FEHLERN abgeschlossen." & vbLf & vbLf & "Bitte prüfen Sie die Übernahmeinfos sowie die übernommenen Daten."
Case "importkeineausgewählt"
    TextInEnglisch = "You have not selected a (valid) product manager file as a source for the import yet!"
    TextInDeutsch = "Sie haben noch keine (gültige) Produktmanager-Datei als Quelle für den Import ausgewählt!"
Case "importkeinedaten"
    TextInEnglisch = "No data have been imported!"
    TextInDeutsch = "Es wurden keine Daten übernommen!"
Case "importmanuell"
    TextInEnglisch = "This version is not compatible!" & vbLf & vbLf & "Please import the data manually."
    TextInDeutsch = "Diese Version ist nicht kompatibel!" & vbLf & vbLf & "Übertragen Sie bitte die Daten manuell."

'CSV-Export
Case "csvverarbeitetezeilen"
    TextInEnglisch = "Processed rows: "
    TextInDeutsch = "Verarbeitete Zeilen: "
Case "csvexportiertezeilen"
    TextInEnglisch = "Number of exported rows: "
    TextInDeutsch = "Anzahl exportierter Zeilen: "
Case "csvexportierterdateiname"
    TextInEnglisch = "File name: "
    TextInDeutsch = "Dateiname: "
Case "csvexportfehler"
    TextInEnglisch = "Fault during export! Please check!"
    TextInDeutsch = "Während des Exports ist ein Fehler aufgetreten! Bitte prüfen!"
Case "csvparentcodeskorrekt01"
    TextInEnglisch = "Parent product codes"
    TextInDeutsch = "Übergeordnete Artikelnummern"
Case "csvparentcodeskorrekt02"
    TextInEnglisch = "You are currently generating the 'Parent product codes' in a semi-automatic manner (via the action selector), if required." & vbLf & vbLf & "Are the paramount product codes correct?" & vbLf & vbLf & "If you are not sure, then say “No” and simply carry out the respective action in the action selector for semi-automatic generation once again before the export to be on the safe side."
    TextInDeutsch = "Aktuell erstellen Sie die 'übergeordneten Artikelnummern' bei Bedarf halbautomatisch (über den Aktionswähler)." & vbLf & vbLf & "Sind die übergeordneten Artikelnummern korrekt?" & vbLf & vbLf & "Wenn Sie sich nicht sicher sind, sagen Sie 'Nein' und führen die entsprechende Aktion im Aktionswähler für die halbautomatische Erstellung sicherheitshalber vor dem Export einfach nochmals aus."
Case "csvlagerbestandsänderungenkorrekt01"
    TextInEnglisch = "There are ### changes in stock!"
    TextInDeutsch = "Es sind ### Lagerbestandsänderungen hinterlegt!"
Case "csvlagerbestandsänderungenkorrekt02"
    TextInEnglisch = "The MPM contains ### information on the number of goods in stock and will make corresponding changes in the online data." & vbLf & vbLf & "Do you wish to continue?"
    TextInDeutsch = "Der MPM enthält ### Informationen über Lagerbestandsveränderungen, was zu entsprechenden Lagerbestandsveränderungen Ihrer Online-Shop-Daten führen wird." & vbLf & vbLf & "Möchten Sie mit dem Export fortfahren?"
Case "csvlagerbestandsänderungenkorrekt03"
    TextInEnglisch = "There are changes in stock!"
    TextInDeutsch = "Es sind Lagerbestandsänderungen hinterlegt!"
Case "csvlagerbestandsänderungenkorrekt04"
    TextInEnglisch = "The MPM contains information on the number of goods in stock and will make corresponding changes in the online data." & vbLf & vbLf & "Do you wish to continue?"
    TextInDeutsch = "Der MPM enthält Informationen über Lagerbestandsveränderungen, was zu entsprechenden Lagerbestandsveränderungen Ihrer Online-Shop-Daten führen wird." & vbLf & vbLf & "Möchten Sie mit dem Export fortfahren?"

'Modus/Aktion 'übergeordnete Artikelnummer'
Case "parentcodefullautomode01"
    TextInEnglisch = "Are you sure?"
    TextInDeutsch = "Sind Sie sicher?"
Case "parentcodefullautomode02"
    TextInEnglisch = "This mode means that the MPM will calculate the 'Parent product codes' in a fully-automatic manner by means of complex formulas." & vbLf & vbLf & "Please note that Excel might become very slow due to this if you have a lot of products! As an alternative, you can always use the semi-automatic mode via the action selector, if required, no matter how many products you have. The semi-automatic mode will store the parent product codes directly (without formulas)!"
    TextInDeutsch = "Dieser Modus bedeutet, dass der MPM die 'übergeordneten Artikelnummern' vollautomatisch mittels (komplexer) Formeln ermitteln wird." & vbLf & vbLf & "Bitte beachten Sie, dass bei vielen Produkten Excel aufgrund der komplexen Matrix-Formeln dadurch sehr langsam werden kann! Alternativ können Sie bei Bedarf auch jederzeit den halbautomatischen Modus über den Aktionswähler verwenden - ganz gleich, wie viele Produkte Sie haben, der die übergeordneten Artikelnummern dann direkt (ohne Formeln) hinterlegt!"

'Konfig
Case "konfigfragelöschen"
    TextInEnglisch = "Are you really sure that you want to delete this entry?"
    TextInDeutsch = "Sind Sie wirklich sicher, dass Sie diesen Eintrag löschen möchten?"
Case "konfigsyncrospaltefehlt"
    TextInEnglisch = "The column below could not be found in the product manager! Therefore, the change cannot be carried out. Please check and update by pressing STRG+H, if required!"
    TextInDeutsch = "Die untenstehende Spalte konnte in der Produktverwaltung nicht gefunden werden! Daher kann die Änderung nicht durchgeführt werden.  Bitte prüfen und ggf. mittels STRG+H aktualisieren!"
Case "konfigsyncroübernommen"
    TextInEnglisch = "The changes were applied in the product manager!"
    TextInDeutsch = "Die Änderungen wurden in die Produktverwaltung übernommen!"
Case "konfigsyncronichtübernommen01"
    TextInEnglisch = "NO changes were carried out in the product manager! If the below-mentioned old value was used in the product manager, it will still be stated there!"
    TextInDeutsch = "Es wurden KEINE Änderungen in der Produktverwaltung durchgeführt! Falls in der Produktverwaltung der untenstehende alte Wert verwendet wurde, ist dieser nach wie vor dort aufgeführt!"
Case "konfigsyncronichtübernommen02"
    TextInEnglisch = "The changes could not or not completely be applied in the product manager! Please check and update by pressing STRG+H, if required!"
    TextInDeutsch = "Die Änderungen konnten nicht oder nicht vollständig in die Produktverwaltung übernommen werden! Bitte prüfen und ggf. mittels STRG+H aktualisieren!"

'VariantCreatorHelp
Case "variantcreatorhelp01"
    TextInEnglisch = "In fields with a checkbox 'Formula', you can use the following place holders:" & vbLf & vbLf
    TextInDeutsch = "In den Feldern, die über eine Checkbox 'Formel' verfügen, können Sie die nachfolgenden Platzhalter verwenden:" & vbLf & vbLf
Case "variantcreatorhelp02"
    TextInEnglisch = "  = Product code of the main product" & vbLf
    TextInDeutsch = "  = Artikelnummer des Hauptproduktes" & vbLf
Case "variantcreatorhelp03"
    TextInEnglisch = "  = Product designation of the main product" & vbLf
    TextInDeutsch = "  = Artikelbezeichnung des Hauptproduktes" & vbLf
Case "variantcreatorhelp04"
    TextInEnglisch = "  = Current prefix counter" & vbLf
    TextInDeutsch = "  = Aktueller Präfixzähler" & vbLf
Case "variantcreatorhelp05"
    TextInEnglisch = "  = Current suffix counter" & vbLf
    TextInDeutsch = "  = Aktueller Suffixzähler" & vbLf
Case "variantcreatorhelp06"
    TextInEnglisch = "  = Current separator" & vbLf & vbLf
    TextInDeutsch = "  = Aktuelles Trennzeichen" & vbLf & vbLf
Case "variantcreatorhelp07"
    TextInEnglisch = "If you activate the checkbox 'Formula', the text entered by you will be interpreted as a formula. This enables you to have the content of these fields for the defined variants defined automatically by the VariantCreator in a very flexible manner via conventional Excel formulas!" & vbLf & vbLf
    TextInDeutsch = "Wenn Sie die Checkbox 'Formel' aktivieren, wird der von Ihnen eingegebene Text als Formel interpretiert. So können Sie für die erstellten Varianten den Inhalt dieser Felder sehr flexibel und mittels herkömmtlicher Excel-Formeln automatisch vom VariantCreator erstellen lassen!" & vbLf & vbLf
Case "variantcreatorhelp08"
    TextInEnglisch = "Please check out the example included by default which you can easily load and change via the upper selection list of the Variant Creator." & vbLf
    TextInDeutsch = "Wir verweisen an dieser Stelle auf das standardmäßig enthaltene Beispiel, das Sie einfach über die obere Auswahlliste des VariantCreator laden und abändern können." & vbLf
Case "variantcreatorhelp09"
    TextInEnglisch = "    Double-click to select a file."
    TextInDeutsch = "    Hier können Sie den Doppelklick nutzen, um eine Datei auszuwählen."

'VariantCreator
Case "variantcreatorfeldfehlt"
    TextInEnglisch = "Missing field: "
    TextInDeutsch = "Das folgendes Feld ist nicht eingegeben: "
Case "variantcreatorabbruchinzeile"
    TextInEnglisch = "### variants have been created for #### products. Canceled in row: "
    TextInDeutsch = "Es wurden für ### Produkte Varianten erstellt. Abbruch in Zeile: ####"
Case "variantcreatorfehlerpassiert"
    TextInEnglisch = "An error has occurred while creating your variants."
    TextInDeutsch = "Es ist ein Fehler während der Variantenerstellung passiert."
Case "variantcreatorfehlerpassiertvariantenprüfen"
    TextInEnglisch = "An error has occurred while creating your variants. Please check the variants."
    TextInDeutsch = "Während der Erstellung ist ein Fehler passiert! Bitte prüfen Sie die erstellten Varianten."
Case "variantcreatorvariantenerstellt"
    TextInEnglisch = "#anzahlNeuerVarianten# variants have been created."
    TextInDeutsch = "Es wurde(n) #anzahlNeuerVarianten# Variante(n) erstellt."
Case "variantcreatorvariantenerstelltzusätzlichsprache"
    TextInEnglisch = " Plus #anzahlNeuerVariantenFremdsprache# foreign language entries."
    TextInDeutsch = " Zusätzlich wurden insg. #anzahlNeuerVariantenFremdsprache# Fremdspracheinträge zu den Varianten erstellt."
Case "variantcreatorfremdsprachenhinzugefügt"
    TextInEnglisch = "#anzahlNeuerVariantenFremdsprache# foreign languages have been added."
    TextInDeutsch = "Es wurde(n) #anzahlNeuerVariantenFremdsprache# Fremdsprachen zur ausgewählten Variante hinzugefügt."
Case "variantcreatorkeinevariantenerstellt"
    TextInEnglisch = "No variants have been created!"
    TextInDeutsch = "Es wurden KEINE Varianten erstellt!"
Case "variantcreatorzuwenigmerkmaleausgewählt"
    TextInEnglisch = "You have only selected #anzahlGewählteMerkmale# properties!"
    TextInDeutsch = "Sie haben nur #anzahlGewählteMerkmale# Merkmal(e) ausgewählt!"
Case "variantcreatorzuvielemerkmaleausgewählt"
    TextInEnglisch = "You have selected more than 8 properties!"
    TextInDeutsch = "Sie haben mehr als 8 Merkmale ausgewählt!"
Case "variantcreatorkeinezeilemarkiert"
    TextInEnglisch = "No row selected!"
    TextInDeutsch = "Sie haben keine Zeile markiert!"
Case "variantcreatorfalscherprodukttyp"
    TextInEnglisch = "Wrong product type!"
    TextInDeutsch = "Für diesen Produkttyp können Sie keine Varianten erstellen!"
Case "variantcreatorfalschezeichenfolge"
    TextInEnglisch = "Name has invalid characters."
    TextInDeutsch = "Die Zeichenfolge ist im Namen nicht erlaubt."
Case "variantcreatorkeinnameeingegeben"
    TextInEnglisch = "You have not entered a name!"
    TextInDeutsch = "Sie haben keinen Namen eingegeben!"

End Select

Select Case sprache
Case 1 'Englisch
    mehrsprachigkeitTextrückgabeAktuelleSprache = TextInEnglisch
Case 2 'Deutsch
    mehrsprachigkeitTextrückgabeAktuelleSprache = TextInDeutsch
Case Else 'Fallback
    mehrsprachigkeitTextrückgabeAktuelleSprache = TextInEnglisch
End Select

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler
End Function



'Public Function mehrsprachigkeitBegriffsrückgabe(sprache, begriff)
''Hier werden alle mehrsprachigen Begriffe zurückgegeben (case sensitive!)
'
'aktuelleFunktionsnummer = crc32HashErmitteln("mehrsprachigkeitBegriffsrückgabe")
'On Error GoTo errHandler
'Application.EnableCancelKey = xlDisabled
'
'Dim X As Long
'
'
'
'For X = 1 To 5000
'    If mehrsprachigkeitBegriffe(1, X) = begriff Then
'        mehrsprachigkeitBegriffsrückgabe = mehrsprachigkeitBegriffe(sprache, X)
'        Exit Function
'    End If
'Next
'
'exitHandler:
'Exit Function
'
'errHandler:
''MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
'GoTo exitHandler
'
'End Function



Public Function mehrsprachigkeitBegriffsrückgabe(lSprache&, sBegriff$) As String
  Rem 31.05.2023, TBU, Hier werden alle mehrsprachigen Begriffe zurückgegeben (case sensitive!)
  Dim sSprache$
  
    On Error GoTo errHandler
    
    Application.EnableCancelKey = xlDisabled
    
'TODO Es ist nicht nötig über den Parameter lSprache die gewünschte Sprache immer durchzureichen. Es reicht sie hier drin auszulesen. Da die
'Änderung aber noch zu kritisch ist wird das erst erledigt wenn wir einen vollständigen Test garantieren können

    'If lSprache = 0 Then lSprache = gewählteSpracheZurückgeben()
    

    If lSprache = 1 Then
        Rem bei englischer Sprache entspricht der englische Key dem englischen Text
        mehrsprachigkeitBegriffsrückgabe = sBegriff
        
        Rem Annahme: die englischen Keys sind auch genauso wie die englischen Texte  (als nicht "pleasewait"  <=> "Please Wait!"
        Rem Eine Überprüfung würde erfordern ca. 300 Begriffe im Code zu vergleichen.
        Rem Daher reicht es bei Sprache=englisch(1) einfach nur den Sprach-Begriffs-Key zurückzugeben
        
    Else
        Rem nicht-englisch
        
        sSprache = CStr(lSprache)
        
        Rem In der kontextmenue_erweitern werden Sprach-IDs verwendet für die es noch keine Sprachdaten gibt
        Rem In der Originalfunktion wird das gelöst indem das Array einfach durchsucht wird und für jeden Sprachkey sind null-Strings vorhanden
        Rem Da man lt. https://msdn.microsoft.com/en-us/library/aa261347(v=vs.60).aspx
        Rem nicht auf das vorhandensein von Keys zugreifen kann müssen wir hier mit on error resume next arbeiten
        'If fCollectionKeyExists(col_mehrsprachigBegriffe, sSprache) Then
        On Error Resume Next
        mehrsprachigkeitBegriffsrückgabe = col_mehrsprachigBegriffe(sSprache)(sBegriff)
        On Error GoTo errHandler
        'End If
        
    End If

exitHandler:
    Exit Function

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("mehrsprachigkeitBegriffsrückgabe-collection")
    MsgBox "Error: " & aktuelleFunktionsnummer & " " & Err.Description, vbOKOnly
    GoTo exitHandler

End Function


'Private Function fCollectionKeyExists(col_Source As Collection, sKey$) As Boolean
'  Rem 02.06.2023, TBU, prüft, ob in der übergebenen Collection der übergebene sKey enthalten ist
'  Dim var As Variant
'
'    'On Error GoTo errHandler
'
'    On Error Resume Next
'    var = col_Source(sKey)
'    fCollectionKeyExists = (Err.Number = 0)
'    Err.Clear
'
'
'exitHandler:
'    Exit Function
'
'errHandler:
'    aktuelleFunktionsnummer = crc32HashErmitteln("fCollectionKeyExists")
'    MsgBox "Error: " & aktuelleFunktionsnummer & " " & Err.Description, vbOKOnly
'    GoTo exitHandler
'
'End Function









Public Function gewählteSpracheZurückgeben()
'Gewählte Sprache zurückgeben

aktuelleFunktionsnummer = crc32HashErmitteln("gewählteSpracheZurückgeben")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled
Dim gewählteSprache As Long

If gewählteSpracheZelle = "" Then gewählteSpracheZelle = "$B$6" 'Workaround zur Fehlervermeidung bei Programmstart
gewählteSprache = ThisWorkbook.Sheets(1).Range(gewählteSpracheZelle) 'hier direkt die Nummer des Tabellenblattes verwenden
If gewählteSprache = 0 Then gewählteSprache = 1 'Workaround zur Fehlervermeidung bei Programmstart

Select Case gewählteSprache
Case 1
    gewählteSpracheZurückgeben = 1
Case 2
    gewählteSpracheZurückgeben = 2
End Select

exitHandler:
Exit Function

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Function


Public Sub mehrsprachigkeitTabellenNamenÄndern()
'Tabellennamen ändern

aktuelleFunktionsnummer = crc32HashErmitteln("mehrsprachigkeitTabellenNamenÄndern")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

    zentralwertTabellenblattNameProduktverwaltung = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "ProductManagement")
    zentralwertTabellenblattNameKonfiguration = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "Configuration")
    zentralwertTabellenblattNameBasisparameter = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "BasicParameters")
    zentralwertTabellenblattNameLöschprotokoll = mehrsprachigkeitBegriffsrückgabe(aktuellGewählteSprache, "DeletionLog")
    ThisWorkbook.Sheets(1).Name = zentralwertTabellenblattNameProduktverwaltung
    ThisWorkbook.Sheets(2).Name = zentralwertTabellenblattNameKonfiguration
    ThisWorkbook.Sheets(3).Name = zentralwertTabellenblattNameBasisparameter
    ThisWorkbook.Sheets(4).Name = zentralwertTabellenblattNameLöschprotokoll

exitHandler:
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub


Public Sub toDosNachSprachwechsel()
'Arbeiten nach einem Sprachwechsel

aktuelleFunktionsnummer = crc32HashErmitteln("toDosNachSprachwechsel")
On Error GoTo errHandler
Application.EnableCancelKey = xlDisabled

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

kontextmenue_erweitern 'Auch die Kontextmenüs wegen Mehrsprachigkeit neu erstellen

Cells(zentralwertZeileDatenbeginn, 1).Select

Application.CalculateFull

exitHandler:
Application.EnableEvents = True 'Hier zur Sichereit EnableEvents immer aktivieren, falls zwischenzeitlich etwas schief gelaufen sein sollte
Application.ScreenUpdating = True 'Ebenso zur Sicherheit das ScreenUpdating
Exit Sub

errHandler:
MsgBox "Error: " & aktuelleFunktionsnummer, vbOKOnly
GoTo exitHandler

End Sub

Function FarbeHex_In_Color(ByVal Wert As String) As Long
Dim R, G, B As Long
Dim X As Long
'Wert As String
Wert = Replace$(Wert, "#", "")
R = CDec("&H" & Mid$(Wert, 1, 2))
G = CDec("&H" & Mid$(Wert, 3, 2))
B = CDec("&H" & Mid$(Wert, 5, 2))

X = R + (B * 65536 + G * 256)

FarbeHex_In_Color = X


End Function


Public Sub sFuelleCollection()
  Rem TBU, 01.06.2023, Alle Übersetzungstexte in eine Collection eintragen
  Dim col_deutsch As Collection
  Dim col_export As Collection

    On Error GoTo errHandler
  
    If col_mehrsprachigBegriffe Is Nothing Then
        Set col_mehrsprachigBegriffe = New Collection
    End If
  
  
    Rem deutsche Begriffe zu englischem Key
    Set col_deutsch = New Collection
    
    With col_deutsch
    
    .Add "Sortiernummer", "Sort number"
    .Add "Importsperre", "Ignore"
    .Add "Löschen", "Delete"
    .Add "Veröffentlichen", "Publish"
    .Add "Typ", "Type"
    .Add "Gruppen-Nr.", "Group No."
    .Add "Artikel-Nr.", "Product code"
    .Add "Übergeordnete Artikel-Nr.", "Parent product code"
    .Add "Sprache", "Language"
    .Add "Bezeichnung", "Name"
    .Add "Alias", "Alias"
    .Add "Merkmal", "Property"
    .Add "Ausprägung", "Value"
    .Add "Merkmal 01", "Property 01"
    .Add "Ausprägung 01", "Value 01"
    .Add "Merkmal 02", "Property 02"
    .Add "Ausprägung 02", "Value 02"
    .Add "Merkmal 03", "Property 03"
    .Add "Ausprägung 03", "Value 03"
    .Add "Merkmal 04", "Property 04"
    .Add "Ausprägung 04", "Value 04"
    .Add "Merkmal 05", "Property 05"
    .Add "Ausprägung 05", "Value 05"
    .Add "Merkmal 06", "Property 06"
    .Add "Ausprägung 06", "Value 06"
    .Add "Merkmal 07", "Property 07"
    .Add "Ausprägung 07", "Value 07"
    .Add "Merkmal 08", "Property 08"
    .Add "Ausprägung 08", "Value 08"
    .Add "Merkmal 09", "Property 09"
    .Add "Ausprägung 09", "Value 09"
    .Add "Merkmal 10", "Property 10"
    .Add "Ausprägung 10", "Value 10"
    .Add "Merkmal 11", "Property 11"
    .Add "Ausprägung 11", "Value 11"
    .Add "Merkmal 12", "Property 12"
    .Add "Ausprägung 12", "Value 12"
    .Add "Merkmal 13", "Property 13"
    .Add "Ausprägung 13", "Value 13"
    .Add "Merkmal 14", "Property 14"
    .Add "Ausprägung 14", "Value 14"
    .Add "Merkmal 15", "Property 15"
    .Add "Ausprägung 15", "Value 15"
    .Add "Merkmal 16", "Property 16"
    .Add "Ausprägung 16", "Value 16"
    .Add "Merkmal 17", "Property 17"
    .Add "Ausprägung 17", "Value 17"
    .Add "Merkmal 18", "Property 18"
    .Add "Ausprägung 18", "Value 18"
    .Add "Merkmal 19", "Property 19"
    .Add "Ausprägung 19", "Value 19"
    .Add "Merkmal 20", "Property 20"
    .Add "Ausprägung 20", "Value 20"
    
    Rem 21.05.2024, Unterstützung 100 Merkmale & Ausprägungen
    .Add "Merkmal 21", "Property 21"
    .Add "Ausprägung 21", "Value 21"
    .Add "Merkmal 22", "Property 22"
    .Add "Ausprägung 22", "Value 22"
    .Add "Merkmal 23", "Property 23"
    .Add "Ausprägung 23", "Value 23"
    .Add "Merkmal 24", "Property 24"
    .Add "Ausprägung 24", "Value 24"
    .Add "Merkmal 25", "Property 25"
    .Add "Ausprägung 25", "Value 25"
    .Add "Merkmal 26", "Property 26"
    .Add "Ausprägung 26", "Value 26"
    .Add "Merkmal 27", "Property 27"
    .Add "Ausprägung 27", "Value 27"
    .Add "Merkmal 28", "Property 28"
    .Add "Ausprägung 28", "Value 28"
    .Add "Merkmal 29", "Property 29"
    .Add "Ausprägung 29", "Value 29"
    .Add "Merkmal 30", "Property 30"
    .Add "Ausprägung 30", "Value 30"
    .Add "Merkmal 31", "Property 31"
    .Add "Ausprägung 31", "Value 31"
    .Add "Merkmal 32", "Property 32"
    .Add "Ausprägung 32", "Value 32"
    .Add "Merkmal 33", "Property 33"
    .Add "Ausprägung 33", "Value 33"
    .Add "Merkmal 34", "Property 34"
    .Add "Ausprägung 34", "Value 34"
    .Add "Merkmal 35", "Property 35"
    .Add "Ausprägung 35", "Value 35"
    .Add "Merkmal 36", "Property 36"
    .Add "Ausprägung 36", "Value 36"
    .Add "Merkmal 37", "Property 37"
    .Add "Ausprägung 37", "Value 37"
    .Add "Merkmal 38", "Property 38"
    .Add "Ausprägung 38", "Value 38"
    .Add "Merkmal 39", "Property 39"
    .Add "Ausprägung 39", "Value 39"
    .Add "Merkmal 40", "Property 40"
    .Add "Ausprägung 40", "Value 40"
    .Add "Merkmal 41", "Property 41"
    .Add "Ausprägung 41", "Value 41"
    .Add "Merkmal 42", "Property 42"
    .Add "Ausprägung 42", "Value 42"
    .Add "Merkmal 43", "Property 43"
    .Add "Ausprägung 43", "Value 43"
    .Add "Merkmal 44", "Property 44"
    .Add "Ausprägung 44", "Value 44"
    .Add "Merkmal 45", "Property 45"
    .Add "Ausprägung 45", "Value 45"
    .Add "Merkmal 46", "Property 46"
    .Add "Ausprägung 46", "Value 46"
    .Add "Merkmal 47", "Property 47"
    .Add "Ausprägung 47", "Value 47"
    .Add "Merkmal 48", "Property 48"
    .Add "Ausprägung 48", "Value 48"
    .Add "Merkmal 49", "Property 49"
    .Add "Ausprägung 49", "Value 49"
    .Add "Merkmal 50", "Property 50"
    .Add "Ausprägung 50", "Value 50"
    .Add "Merkmal 51", "Property 51"
    .Add "Ausprägung 51", "Value 51"
    .Add "Merkmal 52", "Property 52"
    .Add "Ausprägung 52", "Value 52"
    .Add "Merkmal 53", "Property 53"
    .Add "Ausprägung 53", "Value 53"
    .Add "Merkmal 54", "Property 54"
    .Add "Ausprägung 54", "Value 54"
    .Add "Merkmal 55", "Property 55"
    .Add "Ausprägung 55", "Value 55"
    .Add "Merkmal 56", "Property 56"
    .Add "Ausprägung 56", "Value 56"
    .Add "Merkmal 57", "Property 57"
    .Add "Ausprägung 57", "Value 57"
    .Add "Merkmal 58", "Property 58"
    .Add "Ausprägung 58", "Value 58"
    .Add "Merkmal 59", "Property 59"
    .Add "Ausprägung 59", "Value 59"
    .Add "Merkmal 60", "Property 60"
    .Add "Ausprägung 60", "Value 60"
    .Add "Merkmal 61", "Property 61"
    .Add "Ausprägung 61", "Value 61"
    .Add "Merkmal 62", "Property 62"
    .Add "Ausprägung 62", "Value 62"
    .Add "Merkmal 63", "Property 63"
    .Add "Ausprägung 63", "Value 63"
    .Add "Merkmal 64", "Property 64"
    .Add "Ausprägung 64", "Value 64"
    .Add "Merkmal 65", "Property 65"
    .Add "Ausprägung 65", "Value 65"
    .Add "Merkmal 66", "Property 66"
    .Add "Ausprägung 66", "Value 66"
    .Add "Merkmal 67", "Property 67"
    .Add "Ausprägung 67", "Value 67"
    .Add "Merkmal 68", "Property 68"
    .Add "Ausprägung 68", "Value 68"
    .Add "Merkmal 69", "Property 69"
    .Add "Ausprägung 69", "Value 69"
    .Add "Merkmal 70", "Property 70"
    .Add "Ausprägung 70", "Value 70"
    .Add "Merkmal 71", "Property 71"
    .Add "Ausprägung 71", "Value 71"
    .Add "Merkmal 72", "Property 72"
    .Add "Ausprägung 72", "Value 72"
    .Add "Merkmal 73", "Property 73"
    .Add "Ausprägung 73", "Value 73"
    .Add "Merkmal 74", "Property 74"
    .Add "Ausprägung 74", "Value 74"
    .Add "Merkmal 75", "Property 75"
    .Add "Ausprägung 75", "Value 75"
    .Add "Merkmal 76", "Property 76"
    .Add "Ausprägung 76", "Value 76"
    .Add "Merkmal 77", "Property 77"
    .Add "Ausprägung 77", "Value 77"
    .Add "Merkmal 78", "Property 78"
    .Add "Ausprägung 78", "Value 78"
    .Add "Merkmal 79", "Property 79"
    .Add "Ausprägung 79", "Value 79"
    .Add "Merkmal 80", "Property 80"
    .Add "Ausprägung 80", "Value 80"
    .Add "Merkmal 81", "Property 81"
    .Add "Ausprägung 81", "Value 81"
    .Add "Merkmal 82", "Property 82"
    .Add "Ausprägung 82", "Value 82"
    .Add "Merkmal 83", "Property 83"
    .Add "Ausprägung 83", "Value 83"
    .Add "Merkmal 84", "Property 84"
    .Add "Ausprägung 84", "Value 84"
    .Add "Merkmal 85", "Property 85"
    .Add "Ausprägung 85", "Value 85"
    .Add "Merkmal 86", "Property 86"
    .Add "Ausprägung 86", "Value 86"
    .Add "Merkmal 87", "Property 87"
    .Add "Ausprägung 87", "Value 87"
    .Add "Merkmal 88", "Property 88"
    .Add "Ausprägung 88", "Value 88"
    .Add "Merkmal 89", "Property 89"
    .Add "Ausprägung 89", "Value 89"
    .Add "Merkmal 90", "Property 90"
    .Add "Ausprägung 90", "Value 90"
    .Add "Merkmal 91", "Property 91"
    .Add "Ausprägung 91", "Value 91"
    .Add "Merkmal 92", "Property 92"
    .Add "Ausprägung 92", "Value 92"
    .Add "Merkmal 93", "Property 93"
    .Add "Ausprägung 93", "Value 93"
    .Add "Merkmal 94", "Property 94"
    .Add "Ausprägung 94", "Value 94"
    .Add "Merkmal 95", "Property 95"
    .Add "Ausprägung 95", "Value 95"
    .Add "Merkmal 96", "Property 96"
    .Add "Ausprägung 96", "Value 96"
    .Add "Merkmal 97", "Property 97"
    .Add "Ausprägung 97", "Value 97"
    .Add "Merkmal 98", "Property 98"
    .Add "Ausprägung 98", "Value 98"
    .Add "Merkmal 99", "Property 99"
    .Add "Ausprägung 99", "Value 99"
    .Add "Merkmal 100", "Property 100"
    .Add "Ausprägung 100", "Value 100"
    
    
    .Add "Beschreibung", "Description"
    .Add "Kurzbeschreibung", "Short description"
    .Add "Kategorie", "Category"
    .Add "Hersteller", "Producer"
    .Add "Gruppenpreis anwenden", "Use group prices"
    .Add "Mitgliedergruppen", "Member groups"
    .Add "[G1] Gruppenpreis anwenden", "[G1] Use group prices"
    .Add "[G2] Gruppenpreis anwenden", "[G2] Use group prices"
    .Add "[G3] Gruppenpreis anwenden", "[G3] Use group prices"
    .Add "[G4] Gruppenpreis anwenden", "[G4] Use group prices"
    .Add "[G5] Gruppenpreise anwenden", "[G5] Use group prices"
    .Add "[G1] Mitgliedergruppen", "[G1] Member groups"
    .Add "[G2] Mitgliedergruppen", "[G2] Member groups"
    .Add "[G3] Mitgliedergruppen", "[G3] Member groups"
    .Add "[G4] Mitgliedergruppen", "[G4] Member groups"
    .Add "[G5] Mitgliedergruppen", "[G5] Member groups"
    .Add "Preis", "Price"
    .Add "[G1] Preis", "[G1] Price"
    .Add "[G2] Preis", "[G2] Price"
    .Add "[G3] Preis", "[G3] Price"
    .Add "[G4] Preis", "[G4] Price"
    .Add "[G5] Preis", "[G5] Price"
    .Add "Preis: Art der Preisangabe", "Price: Price type"
    .Add "[G1] Preis: Art der Preisangabe", "[G1] Price: Price type"
    .Add "[G2] Preis: Art der Preisangabe", "[G2] Price: Price type"
    .Add "[G3] Preis: Art der Preisangabe", "[G3] Price: Price type"
    .Add "[G4] Preis: Art der Preisangabe", "[G4] Price: Price type"
    .Add "[G5] Preis: Art der Preisangabe", "[G5] Price: Price type"
    .Add "Staffelpreis anwenden", "Use scale price"
    .Add "[G1] Staffelpreis anwenden", "[G1] Use scale price"
    .Add "[G2] Staffelpreis anwenden", "[G2] Use scale price"
    .Add "[G3] Staffelpreis anwenden", "[G3] Use scale price"
    .Add "[G4] Staffelpreis anwenden", "[G4] Use scale price"
    .Add "[G5] Staffelpreis anwenden", "[G5] Use scale price"
    .Add "Art der Staffelpreisangabe", "Scale price type"
    .Add "[G1] Art der Staffelpreisangabe", "[G1] Scale price type"
    .Add "[G2] Art der Staffelpreisangabe", "[G2] Scale price type"
    .Add "[G3] Art der Staffelpreisangabe", "[G3] Scale price type"
    .Add "[G4] Art der Staffelpreisangabe", "[G4] Scale price type"
    .Add "[G5] Art der Staffelpreisangabe", "[G5] Scale price type"
    .Add "Methode zur Mengenermittlung", "Quantity detection method"
    .Add "[G1] Methode zur Mengenermittlung", "[G1] Quantity detection method"
    .Add "[G2] Methode zur Mengenermittlung", "[G2] Quantity detection method"
    .Add "[G3] Methode zur Mengenermittlung", "[G3] Quantity detection method"
    .Add "[G4] Methode zur Mengenermittlung", "[G4] Quantity detection method"
    .Add "[G5] Methode zur Mengenermittlung", "[G5] Quantity detection method"
    .Add "Unterschiedliche Konfigurationen stets trennen", "Always separate different configurations"
    .Add "[G1] Unterschiedliche Konfigurationen stets trennen", "[G1] Always separate different configurations"
    .Add "[G2] Unterschiedliche Konfigurationen stets trennen", "[G2] Always separate different configurations"
    .Add "[G3] Unterschiedliche Konfigurationen stets trennen", "[G3] Always separate different configurations"
    .Add "[G4] Unterschiedliche Konfigurationen stets trennen", "[G4] Always separate different configurations"
    .Add "[G5] Unterschiedliche Konfigurationen stets trennen", "[G5] Always separate different configurations"
    .Add "Staffelpreis-Schlüsselwort", "Scale price keyword"
    .Add "[G1] Staffelpreis-Schlüsselwort", "[G1] Scale price keyword"
    .Add "[G2] Staffelpreis-Schlüsselwort", "[G2] Scale price keyword"
    .Add "[G3] Staffelpreis-Schlüsselwort", "[G3] Scale price keyword"
    .Add "[G4] Staffelpreis-Schlüsselwort", "[G4] Scale price keyword"
    .Add "[G5] Staffelpreis-Schlüsselwort", "[G5] Scale price keyword"
    .Add "Staffelpreis", "Scale price"
    .Add "[G1] Staffelpreis", "[G1] Scale price"
    .Add "[G2] Staffelpreis", "[G2] Scale price"
    .Add "[G3] Staffelpreis", "[G3] Scale price"
    .Add "[G4] Staffelpreis", "[G4] Scale price"
    .Add "[G5] Staffelpreis", "[G5] Scale price"
    .Add "Alter Preis", "Old price"
    .Add "[G1] Alter Preis", "[G1] Old price"
    .Add "[G2] Alter Preis", "[G2] Old price"
    .Add "[G3] Alter Preis", "[G3] Old price"
    .Add "[G4] Alter Preis", "[G4] Old price"
    .Add "[G5] Alter Preis", "[G5] Old price"
    .Add "Alter Preis: Art der Preisangabe", "Old price: Price type"
    .Add "[G1] Alter Preis: Art der Preisangabe", "[G1] Old price: Price type"
    .Add "[G2] Alter Preis: Art der Preisangabe", "[G2] Old price: Price type"
    .Add "[G3] Alter Preis: Art der Preisangabe", "[G3] Old price: Price type"
    .Add "[G4] Alter Preis: Art der Preisangabe", "[G4] Old price: Price type"
    .Add "[G5] Alter Preis: Art der Preisangabe", "[G5] Old price: Price type"
    .Add "Alter Preis: Verwenden", "Old price: Use"
    .Add "[G1] Alter Preis: Verwenden", "[G1] Old price: Use"
    .Add "[G2] Alter Preis: Verwenden", "[G2] Old price: Use"
    .Add "[G3] Alter Preis: Verwenden", "[G3] Old price: Use"
    .Add "[G4] Alter Preis: Verwenden", "[G4] Old price: Use"
    .Add "[G5] Alter Preis: Verwenden", "[G5] Old price: Use"
    
    '.Add "Mitgliedergruppen", "Member groups"       'DOPPELT
    .Add "Steuersatz", "Tax class"
    .Add "Gewicht", "Weight"
    .Add "Art der Gewichtsangabe", "Weight type"
    .Add "Mengeneinheit", "Unit"
    .Add "Einheit für Mengenvergleichspreis", "Quantity comparison unit"
    .Add "Teiler zur Berechnung des Mengenvergleichspreises", "Quantity comparison divisor"
    .Add "Nachkommastellen für die Menge", "Quantity decimals"
    .Add "Neuheit", "New"
    .Add "Sonderpreis", "On sale"
    .Add "Schlüsselwörter", "Keywords"
    .Add "Hauptbild", "Image"
    .Add "Weitere Bilder", "More images"
    .Add "Lagerbest.-Änd.", "Change stock"
    .Add "Einstellungen zu Lagerbestand und Lieferzeit", "Settings for stock and delivery time"
    .Add "Vorbestellungen erlaubt", "Preordering Allowed"
    .Add "Einstellungen zu Lagerbestand und Lieferzeit in Vorbestellungsphase", "Settings for stock and delivery time in preorder Phase"
    .Add "Verfügbar ab", "Available from"
    .Add "Verfügbarkeits-Einstellungen des übergeordneten Produkts überschreiben", "Override availability settings of parent product"
    .Add "Empfohlene Produkte", "Recommended products"
    .Add "Konfigurator", "Configurator"
    .Add "Template für Produktdarstellung", "Template"
    .Add "flexContent1", "flexContent1"
    .Add "flexContent2", "flexContent2"
    .Add "flexContent3", "flexContent3"
    .Add "flexContent4", "flexContent4"
    .Add "flexContent5", "flexContent5"
    .Add "flexContent6", "flexContent6"
    .Add "flexContent7", "flexContent7"
    .Add "flexContent8", "flexContent8"
    .Add "flexContent9", "flexContent9"
    .Add "flexContent10", "flexContent10"
    .Add "flexContent1 sprachunabhängig", "flexContent1 language independent"
    .Add "flexContent2 sprachunabhängig", "flexContent2 language independent"
    .Add "flexContent3 sprachunabhängig", "flexContent3 language independent"
    .Add "flexContent4 sprachunabhängig", "flexContent4 language independent"
    .Add "flexContent5 sprachunabhängig", "flexContent5 language independent"
    .Add "flexContent6 sprachunabhängig", "flexContent6 language independent"
    .Add "flexContent7 sprachunabhängig", "flexContent7 language independent"
    .Add "flexContent8 sprachunabhängig", "flexContent8 language independent"
    .Add "flexContent9 sprachunabhängig", "flexContent9 language independent"
    .Add "flexContent10 sprachunabhängig", "flexContent10 language independent"
    
    'Produkttypen-Kombinationen
    .Add "/Produkt/Produkt (Fremdsprache)/Variante/Variante (Fremdsprache)/", "/Product/Product (foreign language)/Variant/Variant (foreign language)/"
    .Add "/Produkt/", "/Product/"
    .Add "/Variante/", "/Variant/"
    .Add "/Produkt/Variante/", "/Product/Variant/"
    .Add "/Produkt (Fremdsprache)/Variante/Variante (Fremdsprache)/", "/Product (foreign language)/Variant/Variant (foreign language)/"
    .Add "/Produkt (Fremdsprache)/Variante (Fremdsprache)/", "/Product (foreign language)/Variant (foreign language)/"
    .Add "/Produkt/Produkt (Fremdsprache)/", "/Product/Product (foreign language)/"
    
    
    'Produkttypen
    .Add "Produkt", "Product"
    .Add "Variante", "Variant"
    .Add "Produkt (Fremdsprache)", "Product (foreign language)"
    .Add "Variante (Fremdsprache)", "Variant (foreign language)"
    
    
    'Preistypen
    .Add "Anpassung mit fixem Preis", "Fixed price"
    .Add "Prozentuale Anpassung", "Percent"
    .Add "Eigenständiger Preis", "Independent price"
    
    
    'Staffelpreistypen
    .Add "Feste Preisangabe", "Fixed scale price"
    .Add "Prozentuale Anpassung", "Percentaged adjustment"
    .Add "Anpassung mit festem Wert", "Adjustment with a fixed value"
    
    
    'Staffelpreis Mengenvergleichsmethoden
    .Add "Produkte, Varianten und Konfigurationen getrennt", "Separated by products, variants and configurations"
    .Add "Produkte und Varianten getrennt", "Separated by products and variants"
    .Add "Produkte getrennt", "Separated by products"
    .Add "Zusammengefasst nach Staffelpreis-Schlüsselwort", "Summarized by scale price keyword"
    
    
    'Gewichtstypen
    .Add "Anpassung mit fixem Gewicht", "Fixed weight"

    '.Add "Prozentuale Anpassung", "Percent"                         'DOPPELT

    .Add "Eigenständiges Gewicht", "Independent weight"


    'Überschriften
    .Add "MERCONIS PRODUCT MANAGER: Ihre Produkte", "MERCONIS PRODUCT MANAGER: Your products"
    .Add "MERCONIS PRODUCT MANAGER: Konfiguration", "MERCONIS PRODUCT MANAGER: Your configuration"
    .Add "MERCONIS PRODUCT MANAGER: Basisparameter", "MERCONIS PRODUCT MANAGER: Basic parameters"
    .Add "Basisparameter", "Basic parameters"
    .Add "MERCONIS PRODUCT MANAGER: Löschprotokoll", "MERCONIS PRODUCT MANAGER: Deletion log"
    .Add "Löschprotokoll", "Deletion log"


    'Tabellenblattbezeichnungen
    .Add "Produktverwaltung", "ProductManagement"
    .Add "Varianten", "Variants"
    .Add "Konfiguration", "Configuration"
    .Add "Basisparameter", "BasicParameters"
    .Add "Löschprotokoll", "DeletionLog"

    'Programmsprachen
    .Add "Sprachen", "Languages"
    .Add "English", "English"
    .Add "Deutsch", "Deutsch"

    'Sonstiges
    .Add "Gehe zu Seite", "Go to page"
    .Add "Tabellenblattnamen", "Sheet names"
    .Add "Aktion", "Action"
    .Add "Datei", "File"
    .Add "Fertig!", "Ready!"
    .Add "Frage", "Question"

    'Kontextmenü
    .Add "Zeile(n) löschen!", "Delete row(s)!"
    .Add "Varianten erstellen!", "Create variants"
    .Add "Varianten anzeigen", "Variants visible"
    .Add "Varianten ausblenden", "Variants hidden"

    'Aktionswähler
    .Add "Löschen >>zu löschende<<", "Delete >>marked for deletion<<"
    .Add "Löschen >>mit Importsperre<<", "Delete >>ignored products<<"
    .Add "Übergeordnete Art.-Nr. >>vollautom. ermitteln<<", "Parent product code >>full auto mode<<"
    .Add "Übergeordnete Art.-Nr. >>halbautom. (jetzt)<<", "Parent product code >>semi auto mode (run now)<<"
    .Add "Exportieren >>als CSV-Datei<<", "Export >>as CSV file<<"
    .Add "Markierte Zeilen exportieren >>als CSV-Datei<<", "Export marked rows >>as CSV file<<"

    'Konfig: Grundeinstellungen
    .Add "Merkmalsbezeichnung", "Property name"
    .Add "Ausprägungsbezeichnung", "Value name"

    'Konfig: CSV-Export-Einstellungen
    .Add "Export", "Export"
    .Add "Umwandlung des Inhaltes", "Content transformation"
    .Add "Umwandlung des Inhaltes an/aus", "Content transformation on/off"
    .Add "Umwandlung: Schriftgröße", "Transformation: Font size"
    .Add "Größe im Produktmanager", "Size in Product Manager"
    .Add "Größe in der Exportdatei", "Size in export file"
    .Add "Schriftformat: Fett", "Font style: Bold"
    .Add "Schriftformat: Kursiv", "Font style: Italic"
    .Add "Schriftformat: Unterstrichen", "Font style: Underline"
    .Add "Schriftformat: Farbe", "Font style: Color"
    .Add "Schriftformat: Größe", "Font style: Size"
    .Add "Schriftformat: Zeilenumbruch", "Font style: Line break"
    .Add "Methode: Buchstaben (langsamer)", "Method: Letters (slower)"
    .Add "Methode: Wörter (schneller)", "Method: Words (faster)"
    .Add "Umwandlung deaktivieren", "Deactivate transformation"
    .Add "Schriftformat: Standardgröße", "Font style: Standard size"

    'Konfig: Usability
    .Add "Bedienung", "Usability"

    'Konfig: VariantCreator
    .Add "VariantCreator", "VariantCreator"
    .Add "VariantCreator Einstellungen", "VariantCreator Settings"
    .Add "VariantCreator Einstellungswerte", "VariantCreator Setting Values"

    'Konfig: Eigene Vorlagen
    .Add "Eigene (Text-)vorlagen", "Own (text-)templates"
    .Add "Name der (Text-)Vorlage", "Name of (Text-)template"
    .Add "Text der (Text-)Vorlage", "Text of (Text-)template"

    'Basisparameter: Grundeinstellungen
    .Add "Aktionen01", "Actions01"
    .Add "Info", "Info"

    'Aktionen
    .Add "Automatische Zeilenhöhe", "Auto row height"
    .Add "Backup erstellen", "Create backup"
    .Add "Import/Update", "Import/Update"
    .Add "Varianten ein-/ausblenden", "Hide/show variants"
    .Add "Kopfbereich ein-/ausblenden", "Hide/show header"
    .Add "Merkmale & Ausprägungen ein-/ausblenden", "Hide/show properties and values"
    .Add "Staffelpreise ein-/ausblenden", "Hide/show scale prices"
    .Add "Mitgliedergruppen ein-/ausblenden (0 bis 5)", "Hide/show member groups (0 to 5)"
    .Add "FlexContent ein-/ausblenden", "Hide/show FlexContent"
    .Add "Kategorien/Mitgliedergruppen auswählen", "Select categories/member groups"

    'VariantCreator
    .Add "MERKMALE", "PROPERTIES"
    .Add "AUSPRÄGUNGEN", "VALUES"
    .Add "Einstellungen laden/sichern", "Load/save settings"
    .Add "Merken", "Keep"
    .Add "Inkl. Fremdsprachen?", "Languages incl.?"
    .Add "Artikel-Nr. aus Hauptartikel", "Code from main product"
    .Add "Artikel-Nr.-Zusatz", "Prefix/suffix for Code"
    .Add "Präfix-Start", "Prefix start"
    .Add "Suffix-Start", "Suffix start"
    .Add "Sep.", "Sep."

    '.Add "Preis","Price"                   'DOPPELT
    '.Add "Preis: Art der Preisangabe", "Price: price type"          'DOPPELT
    '.Add "Alter Preis", "Old price"                         'DOPPELT
    '.Add "Alter Preis: Art der Preisangabe", "Old price: price type"            'DOPPELT
    '.Add "Gewicht", "Weight"                    'DOPPELT
    '.Add "Art der Gewichtsangabe", "Weight type"         'DOPPELT
    '.Add "Lagerb.-Änd.", "Change stock"                  'DOPPELT


    .Add "Lieferzeit", "Delivery time"
    .Add "Hauptbild", "Main Image"
    
    '.Add "Weitere Bilder", "More images"             'DOPPELT
    
    .Add "LADEN", "LOAD"
    .Add "SICHERN", "SAVE"

    '.Add "LÖSCHEN", "DELETE"                 'DOPPELT

    .Add "ERSTELLEN", "CREATE"
    .Add "SCHLIESSEN", "CLOSE"
    .Add "HP", "MC"
    .Add "Formel", "Formula"
    .Add "Untersch. Konfigs trennen", "Separate diff. configs"

    'Konfig: VariantCreator
    .Add "AUTOSAVE", "AUTOSAVE"

    'Import/Update
    .Add "QUELLE", "SOURCE"
    .Add "IMPORT", "IMPORT"

    '.Add "BEENDEN", "CLOSE"                      'DOPPELT

    .Add "Importiere Konfiguration", "Importing configuration"
    .Add "Importiere Struktur", "Importing structure"
    .Add "Importiere Spalte", "Importing column"
    .Add "Quelldatei", "Source file"
    .Add "MPM-Version", "MPM version"
    .Add "Anzahl Produkte", "Number of products"
    .Add "Anzahl Varianten", "Number of variants"
    .Add "Anzahl Fremdspracheinträge", "Number of language entries"
    .Add "Aktueller Importschritt", "Current step of import"
    .Add "Importresultat/Fehler", "Import result/errors"

    '.Add "Konfiguration", "Configuration"                               'DOPPELT

    .Add "Produktstruktur", "Structure of products"
    .Add "Spalten (Konfiguration)", "Columns (configuration)"
    .Add "Spalten (Produkte)", "Columns (products)"
    .Add "Fehlende Spalten (Konfiguration)", "Missing columns (configuration)"
    .Add "Fehlende Spalten (Produkte)", "Missing columns (products)"
    .Add "Anzahl der importierten Produkte", "Number of imported products"
    
    'Sonstiges
    .Add "Bitte Makros aktivieren!", "Please enable macros!"
            
    'Infotext
    .Add "MERCONIS PRODUCT MANAGER - Version " & zentralwertEPMVersion & vbCr & "Copyright: Leading Systems GmbH, Korb, Deutschland" & vbCr & "http://www.merconis.com", "MERCONIS PRODUCT MANAGER - Version " & zentralwertEPMVersion & vbCr & "Copyright: Leading Systems GmbH, Korb, Deutschland" & vbCr & "http://www.merconis.com"
    
    End With
    
    col_mehrsprachigBegriffe.Add col_deutsch, "2"
    


    Rem 04.09.2023, die Sprache 99 ist für Exporte vorgesehen
    Set col_export = New Collection
    
    With col_export
    
    .Add "product", "Product"
    .Add "variant", "Variant"
    .Add "productLanguage", "Product (foreign language)"
    .Add "variantLanguage", "Variant (foreign language)"
    .Add "fixed", "Fixed price"
    .Add "percentaged", "Percent"
    .Add "independent", "Independent price"
    .Add "scalePriceStandalone", "Fixed scale price"
    .Add "scalePricePercentaged", "Percentaged adjustment"
    .Add "scalePriceFixedAdjustment", "Adjustment with a fixed value"
    .Add "separatedVariantsAndConfigurations", "Separated by products, variants and configurations"
    .Add "separatedVariants", "Separated by products and variants"
    .Add "separatedProducts", "Separated by products"
    .Add "separatedScalePriceKeywords", "Summarized by scale price keyword"
    .Add "fixed", "Fixed weight"
    '.Add "percentaged", "Percent"                                  'Doppelt aus Gewichtstypen
    .Add "independent", "Independent weight"

    Rem weitere Texte
    .Add "Export Konfiguration", "Export Configuration"

    End With
    
    col_mehrsprachigBegriffe.Add col_export, "99"


exitHandler:
    Exit Sub

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("kontextmenue_loeschen")
    MsgBox "Error: " & aktuelleFunktionsnummer & " " & Err.Description, vbOKOnly
    GoTo exitHandler

End Sub





Function fSpaltenKeyZuBezeichner$(ByVal strSpalte$, Optional lngNummer&, Optional strWorksheet$)
  Rem 04.09.2023, TBU, zum übergebenen Spaltenkey (die die ausgeblendet in Zeile 10 stehen) wird der aktuelle Bezeichner
  Rem   aus Zeile 11 geholt und zurückgegeben.
  Rem 12.09.2023, neben dem Bezeichner wird in der neuen byref Variable lngNummer die Spaltennummer zurückgegeben. Desweiteren
  Rem   werden weitere Worksheets (Konfiguration) durchsucht, falls das Feld nicht gefunden wurde. Dazu kann optional das
  Rem   Arbeitsblatt in strWorksheet übergeben werden
  Dim lngSpalte&

    On Error GoTo errHandler

    If strWorksheet = "" Or strWorksheet = zentralwertTabellenblattNameProduktverwaltung Then
        Rem alle durchprobieren
        lngNummer = spaltenNummernProduktverwaltungDatenbanknamenZurückgeben(strSpalte)
        If lngNummer <> 0 Then
            fSpaltenKeyZuBezeichner = Worksheets(zentralwertTabellenblattNameProduktverwaltung).Cells(zentralwertZeileÜberschriften, lngNummer)
            GoTo exitHandler
        End If

        Rem bei Konfiguration gibt es kein Array anhand dessen man zum Key die Bezeichnung und Nummer kriegt
        Rem Dort muss der Sprach-Parameter übergeben werden, der in den Zell-Funktionsaufrufen steht
        fSpaltenKeyZuBezeichner = fKonfiguration_SpaltenKeyZuBezeichnerNr(strSpalte, lngNummer)
        If lngNummer <> 0 Then
            GoTo exitHandler
        End If
    
    ElseIf strWorksheet = zentralwertTabellenblattNameKonfiguration Then
    
        Rem bei Konfiguration gibt es kein Array anhand dessen man zum Key die Bezeichnung und Nummer kriegt
        Rem Dort muss der Sprach-Parameter übergeben werden, der in den Zell-Funktionsaufrufen steht
        fSpaltenKeyZuBezeichner = fKonfiguration_SpaltenKeyZuBezeichnerNr(strSpalte, lngNummer)
'TODO: noch die Daten für Worksheet Basisparameter einbauen
    End If


exitHandler:
    Exit Function

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("fSpaltenKeyZuBezeichner")
    MsgBox "Error: " & aktuelleFunktionsnummer & " " & Err.Description, vbOKOnly
    GoTo exitHandler
End Function



Function fKonfiguration_SpaltenKeyZuBezeichnerNr$(strSpaltenKey$, Optional lngNummer&)
  Rem 12.09.2023, zum übergebenen SpaltenKey (Zeile 10) (Nur für Sheet "Konfiguration") wird der Sprach-Bezeichner und
  Rem   in der byref lngNummer die Spaltennummer zurückgegeben
  Dim lngMaxSpalte&
  Dim lngSpalte&
  Dim arr_Result()
  Static col_KonfigurationSpalten As Collection

    On Error GoTo errHandler
    
    If col_KonfigurationSpalten Is Nothing Then
        Rem Collection erstellen

        With Worksheets(zentralwertTabellenblattNameKonfiguration)
        
        lngMaxSpalte = .UsedRange.Columns.Count
        
        Set col_KonfigurationSpalten = New Collection
        
        For lngSpalte = 1 To lngMaxSpalte
            
            If .Cells(zentralwertZeileDatenbanknamen, lngSpalte) <> "" Then                'keine leeren Zellen
            
                Rem Zum Spaltenkey speichern wir die Spaltennummer und den Sprach-Bezeichner (in einem Array)
                col_KonfigurationSpalten.Add _
                    Array(lngSpalte, CStr(.Cells(zentralwertZeileÜberschriften, lngSpalte))), _
                    .Cells(zentralwertZeileDatenbanknamen, lngSpalte)
            End If
        
        Next
        
        End With
        
    End If
    
    If Not fCollectionHasKey(col_KonfigurationSpalten, strSpaltenKey) Then
        GoTo exitHandler
    End If
    
    arr_Result = col_KonfigurationSpalten(strSpaltenKey)
    lngNummer = arr_Result(0)
    
    If lngNummer <> 0 Then
        fKonfiguration_SpaltenKeyZuBezeichnerNr = arr_Result(1)
    End If

exitHandler:
    Exit Function

errHandler:
    aktuelleFunktionsnummer = crc32HashErmitteln("fKonfiguration_SpaltenKeyZuBezeichnerNr")
    MsgBox "Error: " & aktuelleFunktionsnummer & " " & Err.Description, vbOKOnly
    GoTo exitHandler
End Function


Function fCollectionHasKey(col_Source As Collection, ByVal strKey$) As Boolean
  Rem 12.09.2023, prüft, ob in der übergebenen Collection der strKey enthalten ist und liefert dementsprechend ein true/false zurück
  Dim var As Variant
    
    On Error Resume Next
    var = col_Source(strKey)
    fCollectionHasKey = (Err.Number = 0)
    Err.Clear

End Function



'Sub sTest()
'
'Dim lSpalte&
'Dim sName$
'
'standardeinstellungen_schnell
'
'    'sName = fKonfiguration_SpaltenKeyZuBezeichnerNr("variantCreatorSettings")
'    'sName = fKonfiguration_SpaltenKeyZuBezeichnerNr("variantCreatorSettings", lSpalte)
'
'
'
'
'    sName = fSpaltenKeyZuBezeichner("publish")
'    sName = fSpaltenKeyZuBezeichner("publish", lSpalte)
'
'    'sName = fSpaltenKeyZuBezeichner("taxclass", lSpalte)
'    sName = fSpaltenKeyZuBezeichner("autoRowHeight", lSpalte)
'
'
'    sName = fSpaltenKeyZuBezeichner("autoRowHeight", lSpalte, zentralwertTabellenblattNameKonfiguration)
'
'End Sub
