<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfTemplateRenderer;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranOrderSnapshotRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranV1OrderSnapshotRenderer;
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

    public function testRenderGaranLabelUsesOrderDecimalSeparatorForPdfReconstruction(): void
    {
        $assetLocator = $this->createAssetLocator();
        $renderer = new OrderLabelSnapshotRenderer(
            $assetLocator,
            [
                new GaranV1OrderSnapshotRenderer(
                    new GaranPdfTemplateRenderer($assetLocator, 'v1.0')
                ),
                new class implements GaranOrderSnapshotRendererInterface {
                    public function getVersion(): string
                    {
                        return 'v1.1';
                    }

                    public function render(array $itemSnapshot): string
                    {
                        return 'fake-v1.1:' . $itemSnapshot['garanBrand'];
                    }
                },
            ]
        );

        $renderedLabel = $renderer->renderGaranLabel(
            [
                'garanVersion' => 'v1.0',
                'garanBrand' => 'Brand',
                'garanModelIdentifier' => 'Model',
                'garanDurationYears' => '2.5',
            ],
            '.'
        );

        self::assertStringContainsString('>2.5<', $renderedLabel);
        self::assertStringNotContainsString('>2,5<', $renderedLabel);
        self::assertStringNotContainsString('fake-v1.1', $renderedLabel);
    }

    public function testRenderGaranLabelUsesStoredVersionDirectoryForSecondRegisteredRenderer(): void
    {
        $projectDir = $this->createTempProjectDirWithSecondGaranPdfVersion();
        $assetLocator = new OfficialGuaranteeAssetLocator(
            $projectDir,
            new OfficialGuaranteeLanguageResolver(),
        );
        $renderer = new OrderLabelSnapshotRenderer(
            $assetLocator,
            [
                new GaranV1OrderSnapshotRenderer(
                    new GaranPdfTemplateRenderer($assetLocator, 'v1.0')
                ),
                new GaranV1OrderSnapshotRenderer(
                    new GaranPdfTemplateRenderer($assetLocator, 'v1.1')
                ),
            ]
        );

        $renderedV10 = $renderer->renderGaranLabel([
            'garanVersion' => 'v1.0',
            'garanBrand' => 'Brand',
            'garanModelIdentifier' => 'Model',
            'garanDurationYears' => '2.5',
        ]);
        $renderedV11 = $renderer->renderGaranLabel([
            'garanVersion' => 'v1.1',
            'garanBrand' => 'Brand',
            'garanModelIdentifier' => 'Model',
            'garanDurationYears' => '2.5',
        ]);

        self::assertStringNotContainsString('data-version="v1.1"', $renderedV10);
        self::assertStringContainsString('data-version="v1.1"', $renderedV11);
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }

    private function createTempProjectDirWithSecondGaranPdfVersion(): string
    {
        $projectDir = sys_get_temp_dir() . '/merconis-order-label-' . uniqid('', true);
        $baseDirectory = $projectDir . '/src/Resources/public/legal-guarantee/garan/';

        mkdir($baseDirectory . 'v1.0', 0777, true);
        mkdir($baseDirectory . 'v1.1', 0777, true);

        $v10Contents = (string) file_get_contents(
            dirname(__DIR__, 2) . '/src/Resources/public/legal-guarantee/garan/v1.0/garan-label-colour-pdf.svg'
        );

        file_put_contents($baseDirectory . 'v1.0/garan-label-colour-pdf.svg', $v10Contents);
        file_put_contents(
            $baseDirectory . 'v1.1/garan-label-colour-pdf.svg',
            str_replace('<svg ', '<svg data-version="v1.1" ', $v10Contents)
        );

        return $projectDir;
    }
}
