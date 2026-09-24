<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranOrderSnapshotRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotRenderer;
use PHPUnit\Framework\TestCase;

final class OrderLabelSnapshotRendererTest extends TestCase
{
    public function testRenderGllNoticeSvgUsesStoredSnapshotVersionAndLanguage(): void
    {
        $assetLocator = $this->createAssetLocator();
        $renderer = new OrderLabelSnapshotRenderer($assetLocator, []);

        $svg = $renderer->renderGllNoticeSvg([
            'gllVersion' => OfficialGuaranteeAssetLocator::GLL_VERSION,
            'gllLanguage' => 'de',
        ]);
        $pdfPath = $renderer->resolveGllPdfPath([
            'gllVersion' => OfficialGuaranteeAssetLocator::GLL_VERSION,
            'gllLanguage' => 'de',
        ]);

        self::assertSame(
            (string) file_get_contents($assetLocator->getGllSvgPathByLanguage('de')),
            $svg
        );
        self::assertStringEndsWith(
            '/src/Resources/public/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf',
            (string) $pdfPath
        );
    }

    public function testRenderGaranLabelDispatchesToStoredVersionRenderer(): void
    {
        $renderer = new OrderLabelSnapshotRenderer(
            $this->createAssetLocator(),
            [
                new class implements GaranOrderSnapshotRendererInterface {
                    public function getVersion(): string
                    {
                        return 'v1.0';
                    }

                    public function render(array $itemSnapshot): string
                    {
                        return 'v1:' . $itemSnapshot['garanBrand'];
                    }
                },
                new class implements GaranOrderSnapshotRendererInterface {
                    public function getVersion(): string
                    {
                        return 'v2.0';
                    }

                    public function render(array $itemSnapshot): string
                    {
                        return 'v2:' . $itemSnapshot['garanBrand'];
                    }
                },
            ]
        );

        $renderedLabel = $renderer->renderGaranLabel([
            'garanVersion' => 'v1.0',
            'garanBrand' => 'Altbestand',
        ]);

        self::assertSame('v1:Altbestand', $renderedLabel);
    }

    public function testRenderedSnapshotRemainsStableAfterLaterSourceChanges(): void
    {
        $renderer = new OrderLabelSnapshotRenderer(
            $this->createAssetLocator(),
            [
                new class implements GaranOrderSnapshotRendererInterface {
                    public function getVersion(): string
                    {
                        return 'v1.0';
                    }

                    public function render(array $itemSnapshot): string
                    {
                        return sprintf(
                            '%s|%s|%s',
                            $itemSnapshot['garanBrand'],
                            $itemSnapshot['garanModelIdentifier'],
                            $itemSnapshot['garanDurationYears']
                        );
                    }
                },
            ]
        );

        $itemSnapshot = [
            'garanVersion' => 'v1.0',
            'garanBrand' => 'Initial Brand',
            'garanModelIdentifier' => 'Initial Model',
            'garanDurationYears' => '2.0',
        ];

        $renderedBeforeChange = $renderer->renderGaranLabel($itemSnapshot);

        $itemSnapshot['garanBrand'] = 'Changed Brand';
        $itemSnapshot['garanModelIdentifier'] = 'Changed Model';
        $itemSnapshot['garanDurationYears'] = '9.0';

        self::assertSame('Initial Brand|Initial Model|2.0', $renderedBeforeChange);
        self::assertSame('Changed Brand|Changed Model|9.0', $renderer->renderGaranLabel($itemSnapshot));
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }
}
