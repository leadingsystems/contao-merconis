<?php
declare(strict_types=1);

namespace Merconis\Core;

use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingDocumentWriter;
use LeadingSystems\MerconisBundle\EInvoicing\Service\OrderEInvoicingDocumentGenerator;

class dynamicAttachment_einvoice_01 extends Controller
{
    private const OUTPUT_DIRECTORY = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles';

    /**
     * @param array<string, mixed> $arrOrder
     * @param array<int|string, mixed> $flex_parameters
     */
    public function __construct(
        private array $arrOrder = [],
        private mixed $counterNr = null,
        private array $flex_parameters = [],
    ) {
        parent::__construct();
    }

    public function parse(): ?string
    {
        $orderId = (int) ($this->arrOrder['id'] ?? 0);
        $invoiceNumber = is_scalar($this->counterNr) ? trim((string) $this->counterNr) : '';

        if ($orderId <= 0) {
            $this->logError('Unable to generate dynamic e-invoice attachment because the order ID is missing.');
            return null;
        }

        if ($invoiceNumber === '') {
            $this->logError(sprintf(
                'Unable to generate dynamic e-invoice attachment for order "%s" because no invoice number was provided.',
                (string) ($this->arrOrder['orderNr'] ?? $orderId)
            ));
            return null;
        }

        try {
            $container = System::getContainer();
            /** @var OrderEInvoicingDocumentGenerator $documentGenerator */
            $documentGenerator = $container->get(OrderEInvoicingDocumentGenerator::class);
            /** @var EInvoicingDocumentWriter $documentWriter */
            $documentWriter = $container->get(EInvoicingDocumentWriter::class);

            $generatedDocument = $documentGenerator->generateForOrderId($orderId, $invoiceNumber);

            return $documentWriter->writePdf($generatedDocument, self::OUTPUT_DIRECTORY, true);
        } catch (InvoiceValidationException) {
            $this->logError(sprintf(
                'Unable to generate dynamic e-invoice attachment for order "%s" because the order data is incomplete for EN 16931 export.',
                (string) ($this->arrOrder['orderNr'] ?? $orderId)
            ));

            return null;
        } catch (\Throwable $throwable) {
            $this->logError(sprintf(
                'Unable to generate dynamic e-invoice attachment for order "%s": %s',
                (string) ($this->arrOrder['orderNr'] ?? $orderId),
                $throwable->getMessage()
            ));

            return null;
        }
    }

    private function logError(string $message): void
    {
        System::getContainer()->get('monolog.logger.contao')->error(
            'MERCONIS: ' . $message,
            ['contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_ERROR)]
        );
    }
}
