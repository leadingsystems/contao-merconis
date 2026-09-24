# Aufbereitung der GARAN-PDF-Vorlage

Quelle der technischen Aufbereitung ist die amtliche Datei
`garan-label-colour.svg`. Die unveränderte Frontend- und
Inline-SVG-Vorlage bleibt weiterhin `garan-label-colour.svg`.
Diese Datei dokumentiert nur die technische Differenz der
zusätzlichen PDF-Vorlage `garan-label-colour-pdf.svg`.

## Verbindlicher Laufzeitpfad

- PDF-Vorlage: `garan/v1.0/garan-label-colour-pdf.svg`
- Unveränderte Amtsvorlage: `garan/v1.0/garan-label-colour.svg`
- Gewählte PDF-Lib im Produktivpfad: `mpdf/mpdf`
- Laufzeitumgebung: PHP im `ddev`-Container, keine Host-Binaries

## Reproduzierbarer Lösungsweg

1. Die amtliche SVG wurde in eine zweite Datei kopiert und nie
   direkt überschrieben.
2. Der QR-Code wurde aus `clipPath`-Masken in normale sichtbare
   SVG-Geometrie überführt. Die zuvor geclippten Rechteckflächen
   liefern dabei weiterhin die Original-Füllfarben.
3. Der globale `style`-Block mit `.cls-*`-Klassen wurde aufgelöst.
   Die benötigten Angaben wurden als Inline-Attribute an die
   einzelnen SVG-Elemente geschrieben.
4. Kollidierende IDs und nicht mehr benötigte Metadaten wurden
   entfernt, insbesondere `clipPath`-IDs, Layer-IDs, `data-name`
   sowie ungenutzte Namespace-Angaben.
5. Die drei injizierbaren Klartextfelder blieben als `text`
   erhalten und tragen `data-field="brand"`, `data-field="model"`
   und `data-field="duration"`.
6. Inhalt und Layout des DVO-Labels wurden nicht neu gezeichnet
   und nicht fachlich verändert. Unverändert bleiben insbesondere
   `viewBox`, Wortmarke, Flagge, Piktogramme, Fußzeile, Farben und
   QR-Ziel.

## Laufzeitstrategie für die PDF-Erzeugung

1. Die vorbereitete SVG wird zur Laufzeit geladen.
2. Snapshot-Werte werden nur in die drei `data-field`-Textfelder
   injiziert.
3. Die fertige SVG wird Base64-kodiert als `data:image/svg+xml`
   in ein `img` eingebettet.
4. `mPDF` rendert dieses `img` ohne Host-Binaries und ohne
   PNG-Zwischenschritt zu einem PDF.
5. Das Seitenformat der Produktiv-Ausgabe ist `95mm x 100mm` bei
   null Seitenrand.

## Bekannte und bewusst akzeptierte Grenzen

- Inter wird im Produktivpfad nicht zusätzlich als eingebettete
  PDF-Schrift nachgerüstet.
- Pixelgenaue DVO-Schrift ist für diese Stage nicht erforderlich.
- Es gibt keinen Fallback auf `rsvg-convert`, Inkscape, Imagick
  oder andere Host-Werkzeuge.
