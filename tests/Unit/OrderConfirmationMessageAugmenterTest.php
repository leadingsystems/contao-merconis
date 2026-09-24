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
    public function testEnhancePayloadAppendsLinksWithoutHiddenFormatFlags(): void
    {
        $augmenter = new OrderConfirmationMessageAugmenter($this->createAttachmentGenerator());

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
        self::assertStringContainsString('https://europa.eu/youreurope/garantien', $payload['bodyRawtext']);
        self::assertContains(
            OrderConfirmationMessageAugmenter::DYNAMIC_ATTACHMENT_PATH,
            $payload['dynamicAttachmentPaths']
        );
    }

    private function createAttachmentGenerator(): OrderConfirmationAttachmentGenerator
    {
        $projectDir = dirname(__DIR__, 2);
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
                        return 'svg';
                    }
                },
            ]
        );
        $writer = new class implements GaranPdfAttachmentWriterInterface {
            public function write(string $svgMarkup, string $preferredBaseName): array
            {
                return [
                    'relativePath' => 'var/tmp/merconis/legal-guarantee/' . $preferredBaseName . '.pdf',
                    'cleanupAfterSend' => true,
                ];
            }
        };

        return new OrderConfirmationAttachmentGenerator(
            $snapshotRenderer,
            $writer,
            $projectDir,
        );
    }
}
