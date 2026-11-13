<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\CoreBundle\Framework\ContaoFramework;
use LeadingSystems\MerconisBundle\Command\LegalGuaranteeOrderAttachmentsCommand;
use LeadingSystems\MerconisBundle\LegalGuarantee\GaranLabelGrouping;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeAssetLocator;
use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\GaranOrderSnapshotRendererInterface;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderConfirmationAttachmentGenerator;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderLabelSnapshotRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LegalGuaranteeOrderAttachmentsCommandTest extends TestCase
{
    public function testCommandOutputsAttachmentPathsLineByLine(): void
    {
        $framework = $this->createStub(ContaoFramework::class);
        $projectDir = $this->createTempProjectDir();
        $bundleProjectDir = $projectDir . '/vendor/leadingsystems/contao-merconis';
        $gllDirectory = $bundleProjectDir . '/src/Resources/public/legal-guarantee/gll/v1.0';
        $garanDirectory = $bundleProjectDir . '/src/Resources/legal-guarantee/garan/v1.0';
        mkdir($gllDirectory, 0777, true);
        mkdir($garanDirectory, 0777, true);
        file_put_contents($gllDirectory . '/legal-guarantee-notice-de.pdf', 'pdf');
        file_put_contents(
            $garanDirectory . '/eu-text-links.json',
            '{"url":"https://europa.example.test/garan"}'
        );

        $generator = new OrderConfirmationAttachmentGenerator(
            new OrderLabelSnapshotRenderer(
                new OfficialGuaranteeAssetLocator(
                    $bundleProjectDir,
                    new OfficialGuaranteeLanguageResolver(),
                ),
                [
                    new class implements GaranOrderSnapshotRendererInterface {
                        public function getVersion(): string
                        {
                            return 'v1.0';
                        }

                        public function render(array $itemSnapshot): string
                        {
                            return '<svg viewBox="0 0 10 10"></svg>';
                        }
                    },
                ]
            ),
            new class implements \LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface {
                public function write(array $pages, string $orderNumber, int $orderDateUnixTimestamp): array
                {
                    return [
                        'relativePath' => 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/garan.pdf',
                    ];
                }
            },
            new GaranLabelGrouping(),
            new class implements TranslatorInterface {
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
            },
            new NullLogger(),
        );

        $command = new class($framework, $generator) extends LegalGuaranteeOrderAttachmentsCommand {
            public function __construct(
                ContaoFramework $framework,
                OrderConfirmationAttachmentGenerator $generator
            )
            {
                parent::__construct($framework, $generator);
            }

            protected function loadOrder(int $orderId): ?array
            {
                if (4 !== $orderId) {
                    return null;
                }

                return [
                    'id' => 4,
                    'gllVersion' => 'v1.0',
                    'gllLanguage' => 'de',
                    'items' => [
                        [
                            'productVariantID' => '10-1',
                            'garanVersion' => 'v1.0',
                            'garanBrand' => 'Brand',
                            'garanModelIdentifier' => 'Model',
                            'garanDurationMonths' => '36',
                            'productTitle' => 'Product',
                        ],
                    ],
                ];
            }
        };

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['order-id' => '4']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(
            "bundles/leadingsystemsmerconis/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf\nfiles/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/garan.pdf\n",
            $tester->getDisplay()
        );
    }

    public function testCommandFailsForUnknownOrder(): void
    {
        $framework = $this->createStub(ContaoFramework::class);
        $generator = new OrderConfirmationAttachmentGenerator(
            new OrderLabelSnapshotRenderer(
                new OfficialGuaranteeAssetLocator(
                    $this->createTempProjectDir(),
                    new OfficialGuaranteeLanguageResolver(),
                ),
                []
            ),
            new class implements \LeadingSystems\MerconisBundle\LegalGuarantee\Garan\V1_0\GaranPdfAttachmentWriterInterface {
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

        $command = new class($framework, $generator) extends LegalGuaranteeOrderAttachmentsCommand {
            public function __construct(
                ContaoFramework $framework,
                OrderConfirmationAttachmentGenerator $generator
            )
            {
                parent::__construct($framework, $generator);
            }

            protected function loadOrder(int $orderId): ?array
            {
                return null;
            }
        };

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['order-id' => '999']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Order not found.', $tester->getDisplay());
    }

    private function createTempProjectDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/merconis-command-' . uniqid('', true);
        mkdir($tempDir, 0777, true);

        return $tempDir;
    }
}
