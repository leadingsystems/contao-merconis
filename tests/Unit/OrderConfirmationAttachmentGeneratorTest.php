<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranOrderSnapshotRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderConfirmationAttachmentGenerator;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderConfirmationAttachmentGeneratorTest extends TestCase
{
    public function testGenerateAttachmentsKeepsSuccessfulFilesWhenSingleGaranPdfFails(): void
    {
        $projectDir = $this->createTempProjectDir();
        $bundleProjectDir = $projectDir . '/vendor/leadingsystems/contao-merconis';
        $gllDirectory = $bundleProjectDir . '/src/Resources/public/legal-guarantee/gll/v1.0';
        $garanDirectory = $bundleProjectDir . '/src/Resources/legal-guarantee/garan/v1.0';
        mkdir($gllDirectory, 0777, true);
        mkdir($garanDirectory, 0777, true);
        file_put_contents($gllDirectory . '/legal-guarantee-notice-en.pdf', 'pdf');
        file_put_contents(
            $garanDirectory . '/eu-text-links.json',
            '{"url":"https://europa.example.test/garan"}'
        );

        $assetLocator = new OfficialGuaranteeAssetLocator(
            $bundleProjectDir,
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
                            $itemSnapshot['garanDurationMonths']
                        );
                    }
                },
            ]
        );

        $writer = new class implements GaranPdfAttachmentWriterInterface {
            public array $baseNames = [];
            /** @var list<array{svgMarkup: string, productNames: list<string>, euLinkText: string, euLinkUrl: string}> */
            public array $pages = [];

            public function write(array $pages, string $preferredBaseName): array
            {
                $this->baseNames[] = $preferredBaseName;
                $this->pages = $pages;

                return [
                    'relativePath' => 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/' . $preferredBaseName . '-hash.pdf',
                ];
            }
        };

        $translator = new class implements TranslatorInterface {
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return 'EU information about the manufacturer guarantee';
            }

            public function getLocale(): string
            {
                return 'en';
            }

            public function setLocale(string $locale): void
            {
            }
        };

        $generator = new OrderConfirmationAttachmentGenerator(
            $snapshotRenderer,
            $writer,
            $projectDir,
            new GaranLabelGrouping(),
            $translator,
        );

        $attachments = $generator->generateAttachments([
            'orderNr' => 'ORDER-77',
            'gllVersion' => 'v1.0',
            'gllLanguage' => 'en',
            'items' => [
                [
                    'productVariantID' => '10-100',
                    'garanVersion' => 'v1.0',
                    'garanBrand' => 'Alpha',
                    'garanModelIdentifier' => 'OK-1',
                    'garanDurationMonths' => '24',
                    'productTitle' => 'Fallback Product',
                    'variantTitle' => 'Fallback Variant',
                    'isVariant' => true,
                    'extendedInfo' => [
                        '_productTitle_customerLanguage' => 'Produkt Alpha',
                        '_title_customerLanguage' => 'Variante Blau',
                    ],
                ],
                [
                    'productVariantID' => '10-101',
                    'garanVersion' => 'v1.0',
                    'garanBrand' => 'Alpha',
                    'garanModelIdentifier' => 'OK-1',
                    'garanDurationMonths' => '24',
                    'productTitle' => 'Fallback Product',
                    'variantTitle' => 'Fallback Variant 2',
                    'isVariant' => true,
                    'extendedInfo' => [
                        '_productTitle_customerLanguage' => 'Produkt Alpha',
                        '_title_customerLanguage' => 'Variante Gruen',
                    ],
                ],
                [
                    'productVariantID' => '20-200',
                    'garanVersion' => 'v1.0',
                    'garanBrand' => 'Beta',
                    'garanModelIdentifier' => 'BROKEN',
                    'garanDurationMonths' => '60',
                    'productTitle' => 'Defekt',
                ],
            ],
        ]);

        self::assertSame(
            [
                'bundles/leadingsystemsmerconis/legal-guarantee/gll/v1.0/legal-guarantee-notice-en.pdf',
                'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/garan-labels-order-77-hash.pdf',
            ],
            $attachments['relativePaths']
        );
        self::assertSame(
            ['garan-labels-order-77'],
            $writer->baseNames
        );
        self::assertCount(1, $writer->pages);
        self::assertSame(
            ['Produkt Alpha (Variante Blau)', 'Produkt Alpha (Variante Gruen)'],
            $writer->pages[0]['productNames']
        );
        self::assertSame(
            'EU information about the manufacturer guarantee',
            $writer->pages[0]['euLinkText']
        );
        self::assertSame('https://europa.example.test/garan', $writer->pages[0]['euLinkUrl']);
    }

    private function createTempProjectDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/merconis-garan-' . uniqid('', true);

        mkdir($tempDir, 0777, true);

        return $tempDir;
    }
}
