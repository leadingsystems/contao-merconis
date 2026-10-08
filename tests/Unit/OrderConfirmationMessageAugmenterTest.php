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
        $augmenter = $this->createAugmenter();

        $payload = $augmenter->enhancePayload(
            [
                'bodyHTML' => '<html><body><p>Ausgang</p><footer>Fußbereich</footer></body></html>',
                'bodyRawtext' => 'Ausgang',
                'dynamicAttachmentPaths' => [],
            ],
            ['sendWhen' => 'asOrderConfirmation'],
            $this->createOrderWithGllAndGaran(),
            'de'
        );

        self::assertStringContainsString('EU-Information zur gesetzlichen Gewährleistung', $payload['bodyHTML']);
        self::assertStringContainsString('EU-Information zur Herstellergarantie', $payload['bodyHTML']);
        self::assertStringContainsString('Kaffeemaschine (Schwarz)', $payload['bodyHTML']);
        self::assertStringContainsString('<footer>Fußbereich</footer>', $payload['bodyHTML']);
        self::assertStringContainsString(
            $this->createAssetLocator()->getGllEuUrlByLanguage('de', 'v1.0'),
            $payload['bodyRawtext']
        );
        self::assertStringContainsString(
            'https://europa.eu/youreurope/commercial-guarantee-durability/index.htm',
            $payload['bodyRawtext']
        );
        self::assertGreaterThan(
            strpos($payload['bodyHTML'], '<footer>Fußbereich</footer>'),
            strpos($payload['bodyHTML'], 'EU-Information zur gesetzlichen Gewährleistung')
        );
        self::assertLessThan(
            stripos($payload['bodyHTML'], '</body>'),
            strpos($payload['bodyHTML'], 'EU-Information zur gesetzlichen Gewährleistung')
        );
        self::assertContains(
            OrderConfirmationMessageAugmenter::DYNAMIC_ATTACHMENT_PATH,
            $payload['dynamicAttachmentPaths']
        );
    }

    public function testEnhancePayloadInsertsHtmlSectionBeforeClosingHtmlTagWhenBodyTagIsMissing(): void
    {
        $augmenter = $this->createAugmenter();

        $payload = $augmenter->enhancePayload(
            [
                'bodyHTML' => '<div>Ausgang</div></html>',
                'bodyRawtext' => 'Ausgang',
                'dynamicAttachmentPaths' => [],
            ],
            ['sendWhen' => 'asOrderConfirmation'],
            $this->createOrderWithGllOnly(),
            'de'
        );

        self::assertLessThan(
            stripos($payload['bodyHTML'], '</html>'),
            strpos($payload['bodyHTML'], 'EU-Information zur gesetzlichen Gewährleistung')
        );
    }

    public function testEnhancePayloadAppendsHtmlSectionToFragmentWithoutClosingTags(): void
    {
        $augmenter = $this->createAugmenter();

        $payload = $augmenter->enhancePayload(
            [
                'bodyHTML' => '<div>Ausgang</div>',
                'bodyRawtext' => 'Ausgang',
                'dynamicAttachmentPaths' => [],
            ],
            ['sendWhen' => 'asOrderConfirmation'],
            $this->createOrderWithGllOnly(),
            'de'
        );

        self::assertStringEndsWith(
            $this->createExpectedGllHtmlSection(),
            $payload['bodyHTML']
        );
    }

    private function createAugmenter(): OrderConfirmationMessageAugmenter
    {
        return new OrderConfirmationMessageAugmenter(
            $this->createAttachmentGenerator(),
            $this->createAssetLocator()
        );
    }

    private function createExpectedGllHtmlSection(): string
    {
        return sprintf(
            '<p><a href="%s" target="_blank" rel="noreferrer noopener">%s</a></p>',
            htmlspecialchars(
                $this->createAssetLocator()->getGllEuUrlByLanguage('de', 'v1.0'),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ),
            htmlspecialchars(
                $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuarantee']['gllEuLinkText'],
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createOrderWithGllOnly(): array
    {
        return [
            'gllVersion' => 'v1.0',
            'gllLanguage' => 'de',
            'items' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createOrderWithGllAndGaran(): array
    {
        return [
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
        ];
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
