<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranOrderSnapshotRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderConfirmationAttachmentGenerator;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OrderConfirmationAttachmentGeneratorTest extends TestCase
{
    public function testGenerateAttachmentsKeepsSuccessfulFilesWhenSingleGaranPdfFails(): void
    {
        $projectDir = $this->createTempProjectDir();
        $gllDirectory = $projectDir . '/src/Resources/public/legal-guarantee/gll/v1.0';
        mkdir($gllDirectory, 0777, true);
        file_put_contents($gllDirectory . '/legal-guarantee-notice-en.pdf', 'pdf');

        $assetLocator = new OfficialGuaranteeAssetLocator(
            $projectDir,
            new OfficialGuaranteeLanguageResolver(),
        );
        $snapshotRenderer = new OrderLabelSnapshotRenderer(
            $assetLocator,
            [
                new class implements GaranOrderSnapshotRendererInterface {
                    public function getVersion(): string
                    {
                        return 'v1.0';
                    }

                    public function render(array $itemSnapshot): string
                    {
                        if (($itemSnapshot['garanModelIdentifier'] ?? '') === 'BROKEN') {
                            throw new RuntimeException('broken item');
                        }

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

        $writer = new class implements GaranPdfAttachmentWriterInterface {
            public array $baseNames = [];

            public function write(string $svgMarkup, string $preferredBaseName): array
            {
                $this->baseNames[] = $preferredBaseName;

                return [
                    'relativePath' => 'var/tmp/merconis/legal-guarantee/' . $preferredBaseName . '.pdf',
                    'cleanupAfterSend' => true,
                ];
            }
        };

        $generator = new OrderConfirmationAttachmentGenerator(
            $snapshotRenderer,
            $writer,
            $projectDir,
        );

        $attachments = $generator->generateAttachments([
            'orderNr' => 'ORDER-77',
            'gllVersion' => 'v1.0',
            'gllLanguage' => 'en',
            'items' => [
                [
                    'garanVersion' => 'v1.0',
                    'garanBrand' => 'Alpha',
                    'garanModelIdentifier' => 'OK-1',
                    'garanDurationYears' => '2',
                    'productTitle' => 'Fallback Product',
                    'variantTitle' => 'Fallback Variant',
                    'isVariant' => true,
                    'extendedInfo' => [
                        '_productTitle_customerLanguage' => 'Produkt Alpha',
                        '_title_customerLanguage' => 'Variante Blau',
                    ],
                ],
                [
                    'garanVersion' => 'v1.0',
                    'garanBrand' => 'Alpha',
                    'garanModelIdentifier' => 'OK-1',
                    'garanDurationYears' => '2',
                    'productTitle' => 'Fallback Product',
                    'variantTitle' => 'Fallback Variant',
                    'isVariant' => true,
                    'extendedInfo' => [
                        '_productTitle_customerLanguage' => 'Produkt Alpha',
                        '_title_customerLanguage' => 'Variante Blau',
                    ],
                ],
                [
                    'garanVersion' => 'v1.0',
                    'garanBrand' => 'Beta',
                    'garanModelIdentifier' => 'BROKEN',
                    'garanDurationYears' => '5',
                    'productTitle' => 'Defekt',
                ],
            ],
        ]);

        self::assertSame(
            [
                'src/Resources/public/legal-guarantee/gll/v1.0/legal-guarantee-notice-en.pdf',
                'var/tmp/merconis/legal-guarantee/garan-label-order-77-produkt-alpha-variante-blau.pdf',
            ],
            $attachments['relativePaths']
        );
        self::assertSame(
            ['var/tmp/merconis/legal-guarantee/garan-label-order-77-produkt-alpha-variante-blau.pdf'],
            $attachments['cleanupRelativePaths']
        );
        self::assertSame(
            ['garan-label-order-77-produkt-alpha-variante-blau'],
            $writer->baseNames
        );
    }

    private function createTempProjectDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/merconis-garan-' . uniqid('', true);

        mkdir($tempDir, 0777, true);

        return $tempDir;
    }
}
