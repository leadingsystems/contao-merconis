<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

final class OrderEInvoicingDocumentGeneratorSmokeTest extends TestCase
{
    public function testGenerateForOrderNumberUsesPersistedOrderWithShippingCosts(): void
    {
        $container = $this->bootHostProjectContainer();
        $connection = $container->get('database_connection');

        self::assertInstanceOf(Connection::class, $connection);

        $orderNumber = $connection->fetchOne(
            'SELECT orderNr FROM tl_ls_shop_orders'
            . ' WHERE shippingMethod_amount > 0'
            . ' AND paymentMethod_einvoicingPaymentMeansCode <> ?'
            . ' AND shippingMethod_einvoicingTaxCategory <> ?'
            . ' ORDER BY id DESC LIMIT 1',
            ['', '']
        );

        if (!is_string($orderNumber) || trim($orderNumber) === '') {
            self::markTestSkipped(
                'Keine persistierte Bestellung mit Versandkosten und neuen E-Invoicing-Snapshot-Feldern gefunden.'
            );
        }

        $documentGenerator = $container->get(OrderEInvoicingDocumentGenerator::class);

        self::assertInstanceOf(OrderEInvoicingDocumentGenerator::class, $documentGenerator);

        $generatedDocument = $documentGenerator->generateForOrderNumber(
            trim($orderNumber),
            trim($orderNumber) . '-SMOKE',
            new DateTimeImmutable('2026-10-21')
        );

        $document = new DOMDocument();
        $document->loadXML($generatedDocument->xmlContent);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');
        $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
        $xpath->registerNamespace('udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100');

        self::assertSame(
            trim($orderNumber) . '-SMOKE',
            $xpath->evaluate('string(/rsm:CrossIndustryInvoice/rsm:ExchangedDocument/ram:ID)')
        );
        self::assertGreaterThan(
            0.0,
            (float) $xpath->evaluate(
                'count(//ram:SpecifiedTradeAllowanceCharge[ram:ChargeIndicator/udt:Indicator="true"])'
            )
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

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer();
        $container->get('contao.framework')->initialize();

        return $container;
    }
}
