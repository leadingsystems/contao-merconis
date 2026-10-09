<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\Order;

use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use RuntimeException;

final class OrderLabelSnapshotRenderer
{
    /**
     * @var array<string, GaranOrderSnapshotRendererInterface>
     */
    private array $garanRenderersByVersion = [];

    /**
     * @param iterable<GaranOrderSnapshotRendererInterface> $garanRenderers
     */
    public function __construct(
        private readonly OfficialGuaranteeAssetLocator $assetLocator,
        iterable $garanRenderers,
    ) {
        foreach ($garanRenderers as $renderer) {
            $this->garanRenderersByVersion[$renderer->getVersion()] = $renderer;
        }
    }

    /**
     * @param array<string, mixed> $orderSnapshot
     */
    public function renderGllNoticeSvg(array $orderSnapshot): string
    {
        $version = trim((string) ($orderSnapshot['gllVersion'] ?? ''));
        $language = trim((string) ($orderSnapshot['gllLanguage'] ?? ''));

        if ('' === $version || '' === $language) {
            return '';
        }

        $svgPath = $this->assetLocator->getGllSvgPathByLanguage($language, $version);
        $svgContents = file_get_contents($svgPath);

        if (false === $svgContents) {
            throw new RuntimeException(sprintf('GLL snapshot asset could not be read: %s', $svgPath));
        }

        return $svgContents;
    }

    /**
     * @param array<string, mixed> $orderSnapshot
     */
    public function resolveGllPdfPath(array $orderSnapshot): ?string
    {
        $version = trim((string) ($orderSnapshot['gllVersion'] ?? ''));
        $language = trim((string) ($orderSnapshot['gllLanguage'] ?? ''));

        if ('' === $version || '' === $language) {
            return null;
        }

        return $this->assetLocator->getGllPdfPathByLanguage($language, $version);
    }

    /**
     * @param array<string, mixed> $itemSnapshot
     */
    public function renderGaranLabel(array $itemSnapshot): string
    {
        $version = trim((string) ($itemSnapshot['garanVersion'] ?? ''));

        if ('' === $version) {
            return '';
        }

        $renderer = $this->garanRenderersByVersion[$version] ?? null;

        if (null === $renderer) {
            throw new RuntimeException(sprintf('No GARAN snapshot renderer registered for version "%s".', $version));
        }

        return $renderer->render($itemSnapshot);
    }
}
