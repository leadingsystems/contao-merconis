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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;
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
            public array $orderDates = [];
            public array $orderNumbers = [];
            /** @var list<array<string, mixed>> */
            public array $pages = [];

            public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array
            {
                $this->orderNumbers[] = $orderNumber;
                $this->orderDates[] = $orderDateUnixTimestamp;
                $this->pages = $pages;

                return [
                    'relativePath' => 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/2026/11/12/garan-labels-ORDER-77.pdf',
                ];
            }
        };

        $translator = new class implements TranslatorInterface {
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                if ($id === 'MSC.ls_shop.legalGuarantee.garanLabelUnavailableNotice') {
                    return 'The manufacturer\'s guarantee label for these items could not be generated for technical reasons. Please contact the retailer.';
                }

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

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::logicalAnd(
                    self::stringContains('ORDER-77'),
                    self::stringContains('ART-200')
                ),
                self::anything()
            );

        $generator = new OrderConfirmationAttachmentGenerator(
            $snapshotRenderer,
            $writer,
            new GaranLabelGrouping(),
            $translator,
            $logger,
        );

        $attachments = $generator->generateAttachments([
            'orderNr' => 'ORDER-77',
            'orderDateUnixTimestamp' => 1_794_441_600,
            'gllVersion' => 'v1.0',
            'gllLanguage' => 'en',
            'items' => [
                [
                    'productVariantID' => '10-100',
                    'artNr' => 'ART-100',
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
                    'artNr' => 'ART-101',
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
                    'artNr' => 'ART-200',
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
                'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/2026/11/12/garan-labels-ORDER-77.pdf',
            ],
            $attachments['relativePaths']
        );
        self::assertSame(
            ['ORDER-77'],
            $writer->orderNumbers
        );
        self::assertSame([1_794_441_600], $writer->orderDates);
        self::assertCount(2, $writer->pages);
        self::assertSame(
            ['Produkt Alpha (Variante Blau) - ART-100', 'Produkt Alpha (Variante Gruen) - ART-101'],
            $writer->pages[0]['productNames']
        );
        self::assertSame('label', $writer->pages[0]['type']);
        self::assertSame(
            'EU information about the manufacturer guarantee',
            $writer->pages[0]['euLinkText']
        );
        self::assertSame('https://europa.example.test/garan', $writer->pages[0]['euLinkUrl']);
        self::assertSame(['ART-100', 'ART-101'], $writer->pages[0]['articleNumbers']);
        self::assertSame('notice', $writer->pages[1]['type']);
        self::assertSame(['Defekt - ART-200'], $writer->pages[1]['productNames']);
        self::assertSame(['ART-200'], $writer->pages[1]['articleNumbers']);
        self::assertStringContainsString('could not be generated', $writer->pages[1]['noticeText']);
    }

    public function testGenerateAttachmentsBuildsPublicGllPathFromResolvedPdfLanguage(): void
    {
        $projectDir = $this->createTempProjectDir();
        $bundleProjectDir = $projectDir . '/vendor/leadingsystems/contao-merconis';
        $gllDirectory = $bundleProjectDir . '/src/Resources/public/legal-guarantee/gll/v1.0';
        mkdir($gllDirectory, 0777, true);
        file_put_contents($gllDirectory . '/legal-guarantee-notice-de.pdf', 'pdf');
        file_put_contents($gllDirectory . '/legal-guarantee-notice-en.pdf', 'pdf');

        $snapshotRenderer = new OrderLabelSnapshotRenderer(
            new OfficialGuaranteeAssetLocator(
                $bundleProjectDir,
                new OfficialGuaranteeLanguageResolver(),
            ),
            []
        );

        $generator = new OrderConfirmationAttachmentGenerator(
            $snapshotRenderer,
            new class implements GaranPdfAttachmentWriterInterface {
                public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array
                {
                    return ['relativePath' => 'unused.pdf'];
                }
            },
            new GaranLabelGrouping(),
            new class implements TranslatorInterface {
                public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
                {
                    return 'unused';
                }

                public function getLocale(): string
                {
                    return 'en';
                }

                public function setLocale(string $locale): void
                {
                }
            },
            new NullLogger(),
        );

        self::assertSame(
            ['bundles/leadingsystemsmerconis/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf'],
            $generator->generateAttachments([
                'gllVersion' => 'v1.0',
                'gllLanguage' => 'de_AT',
                'items' => [],
            ])['relativePaths']
        );
        self::assertSame(
            ['bundles/leadingsystemsmerconis/legal-guarantee/gll/v1.0/legal-guarantee-notice-en.pdf'],
            $generator->generateAttachments([
                'gllVersion' => 'v1.0',
                'gllLanguage' => 'zz_ZZ',
                'items' => [],
            ])['relativePaths']
        );
    }

    public function testGenerateAttachmentsSkipsGllWhenNoPdfExistsForResolvedLanguage(): void
    {
        $projectDir = $this->createTempProjectDir();
        $bundleProjectDir = $projectDir . '/vendor/leadingsystems/contao-merconis';
        mkdir($bundleProjectDir . '/src/Resources/public/legal-guarantee/gll/v1.0', 0777, true);

        $generator = new OrderConfirmationAttachmentGenerator(
            new OrderLabelSnapshotRenderer(
                new OfficialGuaranteeAssetLocator(
                    $bundleProjectDir,
                    new OfficialGuaranteeLanguageResolver(),
                ),
                []
            ),
            new class implements GaranPdfAttachmentWriterInterface {
                public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array
                {
                    return ['relativePath' => 'unused.pdf'];
                }
            },
            new GaranLabelGrouping(),
            new class implements TranslatorInterface {
                public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
                {
                    return 'unused';
                }

                public function getLocale(): string
                {
                    return 'en';
                }

                public function setLocale(string $locale): void
                {
                }
            },
            new NullLogger(),
        );

        self::assertSame(
            [],
            $generator->generateAttachments([
                'gllVersion' => 'v1.0',
                'gllLanguage' => 'de_AT',
                'items' => [],
            ])['relativePaths']
        );
    }

    public function testBuildOrderProductNameAppendsVariantAndArticleNumber(): void
    {
        $method = new ReflectionMethod(OrderConfirmationAttachmentGenerator::class, 'buildOrderProductName');
        $method->setAccessible(true);

        self::assertSame(
            'Produkt Alpha (Variante Blau) - ART-100',
            $method->invoke($this->createGenerator(), [
                'artNr' => 'ART-100',
                'extendedInfo' => [
                    '_productTitle_customerLanguage' => 'Produkt Alpha',
                    '_title_customerLanguage' => 'Variante Blau',
                ],
                'isVariant' => true,
                'productTitle' => 'Fallback Product',
                'variantTitle' => 'Fallback Variant',
            ])
        );
    }

    public function testBuildOrderProductNameLeavesArticleSeparatorOutWhenArticleNumberIsEmpty(): void
    {
        $method = new ReflectionMethod(OrderConfirmationAttachmentGenerator::class, 'buildOrderProductName');
        $method->setAccessible(true);

        self::assertSame(
            'Produkt Alpha',
            $method->invoke($this->createGenerator(), [
                'artNr' => '',
                'extendedInfo' => [
                    '_productTitle_customerLanguage' => 'Produkt Alpha',
                ],
                'isVariant' => false,
                'productTitle' => 'Fallback Product',
            ])
        );
    }

    public function testGenerateAttachmentsKeepsTwoProductLinesWhenArticleNumbersMatch(): void
    {
        $writer = new class implements GaranPdfAttachmentWriterInterface {
            /** @var list<array<string, mixed>> */
            public array $pages = [];

            public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array
            {
                $this->pages = $pages;

                return ['relativePath' => 'unused.pdf'];
            }
        };

        $generator = $this->createGenerator($writer);
        $generator->generateAttachments([
            'orderNr' => 'ORDER-88',
            'orderDateUnixTimestamp' => 1_794_441_600,
            'items' => [
                [
                    'artNr' => 'ART-100',
                    'garanBrand' => 'Alpha',
                    'garanDurationMonths' => '24',
                    'garanModelIdentifier' => 'OK-1',
                    'garanVersion' => 'v1.0',
                    'productID' => '10',
                    'productTitle' => 'Produkt Alpha',
                ],
                [
                    'artNr' => 'ART-100',
                    'garanBrand' => 'Alpha',
                    'garanDurationMonths' => '24',
                    'garanModelIdentifier' => 'OK-1',
                    'garanVersion' => 'v1.0',
                    'productID' => '20',
                    'productTitle' => 'Produkt Beta',
                ],
            ],
        ]);

        self::assertCount(2, $writer->pages);
        self::assertSame(['Produkt Alpha - ART-100'], $writer->pages[0]['productNames']);
        self::assertSame(['Produkt Beta - ART-100'], $writer->pages[1]['productNames']);
    }

    private function createTempProjectDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/merconis-garan-' . uniqid('', true);

        mkdir($tempDir, 0777, true);

        return $tempDir;
    }

    private function createGenerator(?GaranPdfAttachmentWriterInterface $writer = null): OrderConfirmationAttachmentGenerator
    {
        return new OrderConfirmationAttachmentGenerator(
            new OrderLabelSnapshotRenderer(
                new OfficialGuaranteeAssetLocator(
                    $this->createTempProjectDir(),
                    new OfficialGuaranteeLanguageResolver(),
                ),
                []
            ),
            $writer ?? new class implements GaranPdfAttachmentWriterInterface {
                public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array
                {
                    return ['relativePath' => 'unused.pdf'];
                }
            },
            new GaranLabelGrouping(),
            new class implements TranslatorInterface {
                public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
                {
                    return 'unused';
                }

                public function getLocale(): string
                {
                    return 'en';
                }

                public function setLocale(string $locale): void
                {
                }
            },
            new NullLogger(),
        );
    }
}
