<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\System;
use LeadingSystems\MerconisBundle\EInvoicing\Contao\EInvoicingOrderMessageHook;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingFailureReporter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingMessageTypeContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prüft gegen den Container der Host-Anwendung, dass der Hook, den der
 * Merconis-Nachrichtenversand über `System::importStatic()` bezieht, und der
 * Fehler-Reporter denselben Nachrichtentyp-Kontext verwenden. Es werden
 * weder E-Mails versendet noch Logeinträge geschrieben.
 */
final class EInvoicingMessageTypeContextContaoIntegrationTest extends TestCase
{
    private const ORDER_ID = 2147483646;

    private const INVOICE_MESSAGE_TYPE_ID = 4;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->bootHostProjectContainer();
    }

    public function testImportStaticDeliversTheContainerInstanceOfTheHook(): void
    {
        $hook = System::importStatic(EInvoicingOrderMessageHook::class, null, true);

        self::assertSame($this->container->get(EInvoicingOrderMessageHook::class), $hook);
    }

    public function testContextStoredByTheHookArrivesAtTheReporterService(): void
    {
        $hook = System::importStatic(EInvoicingOrderMessageHook::class, null, true);
        $hook->beforeSendingOrderMessage(
            ['pid' => (string) self::INVOICE_MESSAGE_TYPE_ID],
            ['id' => (string) self::ORDER_ID]
        );

        $messageTypeContext = $this->container->get(EInvoicingMessageTypeContext::class);
        self::assertInstanceOf(EInvoicingMessageTypeContext::class, $messageTypeContext);
        self::assertSame(self::INVOICE_MESSAGE_TYPE_ID, $messageTypeContext->getMessageTypeIdForOrder(self::ORDER_ID));

        $failureReporter = $this->container->get(EInvoicingFailureReporter::class);
        self::assertSame(
            $messageTypeContext,
            (new \ReflectionProperty(EInvoicingFailureReporter::class, 'messageTypeContext'))->getValue($failureReporter)
        );

        $resolveMessageType = new \ReflectionMethod(EInvoicingFailureReporter::class, 'resolveMessageType');

        self::assertSame(
            ['id' => self::INVOICE_MESSAGE_TYPE_ID, 'title' => 'Rechnung'],
            $resolveMessageType->invoke($failureReporter, self::ORDER_ID, 'de')
        );
        self::assertSame(
            ['id' => self::INVOICE_MESSAGE_TYPE_ID, 'title' => 'Invoice'],
            $resolveMessageType->invoke($failureReporter, self::ORDER_ID, 'en')
        );
        self::assertNull($resolveMessageType->invoke($failureReporter, self::ORDER_ID - 1, 'de'));
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

        // `ContaoFramework` setzt den Container nur bei der ersten Initialisierung im
        // Prozess; weitere Kernel-Boots anderer Tests ändern `System::getContainer()` nicht.
        System::setContainer($container);

        return $container;
    }
}
