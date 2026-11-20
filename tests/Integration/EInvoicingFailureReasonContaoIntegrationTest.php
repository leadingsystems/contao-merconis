<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\System;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingGenerationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReasonFormatter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingFailureReporter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prüft die Sprachauflösung über den echten Contao-Sprachdatei-Lader und
 * den Code für eine nicht vorhandene Bestell-ID gegen die Datenbank des
 * Testsystems.
 */
final class EInvoicingFailureReasonContaoIntegrationTest extends TestCase
{
    private const NON_EXISTING_ORDER_ID = 2147483647;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->bootHostProjectContainer();
    }

    public function testResolvesDescriptionsThroughContaoInBothLanguagesAndRestoresTheLoadedLanguage(): void
    {
        System::loadLanguageFile('default', 'de', true);
        $languageStringsBeforeResolution = $GLOBALS['TL_LANG'];
        $formatter = new EInvoicingFailureReasonFormatter($this->container->get('contao.framework'));
        $failureReason = new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_91, ['paymentMeansCode' => '59']);

        self::assertSame(
            '[EINVOICING_MISSING_BT_91] Payment means code 59 requires the buyer IBAN (BT-91).',
            $formatter->format($failureReason, 'en')
        );
        self::assertSame($languageStringsBeforeResolution, $GLOBALS['TL_LANG']);

        self::assertSame(
            '[EINVOICING_MISSING_BT_91] Für den Payment Means Code 59 ist die IBAN des Käufers (BT-91) erforderlich.',
            $formatter->format($failureReason, 'de')
        );
        self::assertSame($languageStringsBeforeResolution, $GLOBALS['TL_LANG']);
    }

    public function testUnknownOrderIdIsReportedWithCodeAndResolvedByTheReporterService(): void
    {
        $documentGenerator = $this->container->get(OrderEInvoicingDocumentGenerator::class);
        self::assertInstanceOf(OrderEInvoicingDocumentGenerator::class, $documentGenerator);

        try {
            $documentGenerator->buildSemanticInvoiceForOrderId(self::NON_EXISTING_ORDER_ID, 'RE-INTEGRATION');
            self::fail('Expected an EInvoicingGenerationException.');
        } catch (EInvoicingGenerationException $generationException) {
            self::assertSame(EInvoicingErrorCode::ORDER_ID_NOT_FOUND, $generationException->failureReasons[0]->code);
            self::assertSame(['orderId' => (string) self::NON_EXISTING_ORDER_ID], $generationException->failureReasons[0]->parameters);
        }

        $failureReporter = $this->container->get(EInvoicingFailureReporter::class);
        self::assertInstanceOf(EInvoicingFailureReporter::class, $failureReporter);

        self::assertSame(
            ['[EINVOICING_ORDER_ID_NOT_FOUND] No order with the ID ' . self::NON_EXISTING_ORDER_ID . ' was found.'],
            $failureReporter->formatFailureReasons($generationException)
        );
    }

    private function bootHostProjectContainer(): ContainerInterface
    {
        $hostProjectPath = dirname(__DIR__, 5);
        $cacheKernel = ContaoKernel::fromRequest($hostProjectPath, Request::create('/'));
        $kernelProperty = new \ReflectionProperty($cacheKernel, 'kernel');
        $kernel = $kernelProperty->getValue($cacheKernel);

        if (!is_object($kernel) || !method_exists($kernel, 'boot') || !method_exists($kernel, 'getContainer')) {
            throw new \RuntimeException('Contao-Kernel der Host-Anwendung konnte nicht initialisiert werden.');
        }

        $kernel->boot();
        $container = $kernel->getContainer();
        $container->get('contao.framework')->initialize();

        return $container;
    }
}
