<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranOrderSnapshotRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderConfirmationAttachmentGenerator;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderConfirmationMessageAugmenter;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotRenderer;
use PHPUnit\Framework\TestCase;

final class OrderConfirmationMessageAugmenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee'] = [
            'gllEuLinkText' => 'EU-Information zur gesetzlichen Gewährleistung',
            'garanBlockHeadline' => 'Herstellergarantie für folgende Artikel:',
            'garanEuLinkText' => 'EU-Information zur Herstellergarantie',
        ];
    }

    public function testEnhancePayloadAppendsLinksWithoutHiddenFormatFlags(): void
    {
        $augmenter = new OrderConfirmationMessageAugmenter(
            $this->createAttachmentGenerator(),
            $this->createAssetLocator()
        );

        $payload = $augmenter->enhancePayload(
            [
                'bodyHTML' => '<p>Ausgang</p>',
                'bodyRawtext' => 'Ausgang',
                'dynamicAttachmentPaths' => [],
            ],
            ['sendWhen' => 'asOrderConfirmation'],
            [
                'gllVersion' => 'v1.0',
                'gllLanguage' => 'de',
                'items' => [
                    [
                        'garanVersion' => 'v1.0',
                        'productTitle' => 'Kaffeemaschine',
                        'variantTitle' => 'Schwarz',
                        'isVariant' => true,
                        'extendedInfo' => [
                            '_productTitle_customerLanguage' => 'Kaffeemaschine',
                            '_title_customerLanguage' => 'Schwarz',
                        ],
                    ],
                ],
            ],
            'de'
        );

        self::assertStringContainsString('EU-Information zur gesetzlichen Gewährleistung', $payload['bodyHTML']);
        self::assertStringContainsString('EU-Information zur Herstellergarantie', $payload['bodyHTML']);
        self::assertStringContainsString('Kaffeemaschine (Schwarz)', $payload['bodyHTML']);
        self::assertStringContainsString('index_de.htm', $payload['bodyRawtext']);
        self::assertStringContainsString(
            'https://europa.eu/youreurope/commercial-guarantee-durability/index.htm',
            $payload['bodyRawtext']
        );
        self::assertContains(
            OrderConfirmationMessageAugmenter::DYNAMIC_ATTACHMENT_PATH,
            $payload['dynamicAttachmentPaths']
        );
    }

    private function createAttachmentGenerator(): OrderConfirmationAttachmentGenerator
    {
        $projectDir = dirname(__DIR__, 2);
        $assetLocator = $this->createAssetLocator();
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
                        return 'svg';
                    }
                },
            ]
        );
        $writer = new class implements GaranPdfAttachmentWriterInterface {
            public function write(string $svgMarkup, string $preferredBaseName): array
            {
                return [
                    'relativePath' => 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/' . $preferredBaseName . '.pdf',
                    'cleanupAfterSend' => false,
                ];
            }
        };

        return new OrderConfirmationAttachmentGenerator(
            $snapshotRenderer,
            $writer,
            $projectDir,
        );
    }

    private function createAssetLocator(): OfficialGuaranteeAssetLocator
    {
        return new OfficialGuaranteeAssetLocator(
            dirname(__DIR__, 2),
            new OfficialGuaranteeLanguageResolver(),
        );
    }
}
