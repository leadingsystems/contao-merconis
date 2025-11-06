<?php

namespace LeadingSystems\MerconisBundle\Services;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DownloadLinkHelper
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Diese Methode generiert den fertigen HTML-Link.
     * Sie kann von überall aufgerufen werden, wo man Zugriff auf den Service-Container hat.
     *
     * @param int $fileName filename of file in export folder.
     * @param string $label Der Text, der auf dem Button/Link stehen soll.
     * @param string $cssClass Optionale CSS-Klassen für den Link.
     *
     * @return string Der fertige HTML-Code für den <a>-Tag.
     */
    public function generateDownloadLinkHtml(string $fileName, string $label = 'Download', string $cssClass = 'button'): string
    {
        // Generiere die URL über den Routen-Namen aus deiner routing.yaml
        $url = $this->urlGenerator->generate('merconis.backend.downloadExportAction', ['fileName' => $fileName]);

        return sprintf(
            '<a href="%s" title="%s" class="%s">%s</a>',
            $url,
            htmlspecialchars($label),
            htmlspecialchars($cssClass),
            htmlspecialchars($label)
        );
    }
}