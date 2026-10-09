<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\EInvoicing\Model\GeneratedZugferdDocument;
use Merconis\Core\ls_shop_generalHelper;
use RuntimeException;

class OrderEInvoicingDocumentGenerator
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly SemanticInvoiceBuilder $semanticInvoiceBuilder,
        private readonly ZugferdDocumentGenerator $documentGenerator,
    ) {
    }

    public function generateForOrderNumber(
        string $orderNumber,
        ?string $invoiceNumber = null,
        ?DateTimeImmutable $invoiceDate = null,
        ?string $visibleHtml = null,
    ): GeneratedZugferdDocument {
        $normalizedOrderNumber = trim($orderNumber);

        if ($normalizedOrderNumber === '') {
            throw new RuntimeException('Order number must not be empty.');
        }

        $orderId = $this->findOrderIdByOrderNumber($normalizedOrderNumber);

        if ($orderId === null) {
            throw new RuntimeException(sprintf('Order "%s" was not found.', $normalizedOrderNumber));
        }

        return $this->generateForOrderId(
            $orderId,
            $invoiceNumber ?? $normalizedOrderNumber,
            $invoiceDate,
            $visibleHtml
        );
    }

    public function generateForOrderId(
        int $orderId,
        string $invoiceNumber,
        ?DateTimeImmutable $invoiceDate = null,
        ?string $visibleHtml = null,
    ): GeneratedZugferdDocument {
        $normalizedInvoiceNumber = trim($invoiceNumber);

        if ($normalizedInvoiceNumber === '') {
            throw new RuntimeException('Invoice number must not be empty.');
        }

        $semanticInvoice = $this->buildSemanticInvoiceForOrderId(
            $orderId,
            $normalizedInvoiceNumber,
            $invoiceDate
        );

        return $this->documentGenerator->generate($semanticInvoice, $visibleHtml);
    }

    public function buildSemanticInvoiceForOrderId(
        int $orderId,
        string $invoiceNumber,
        ?DateTimeImmutable $invoiceDate = null,
    ): \LeadingSystems\MerconisBundle\EInvoicing\Model\SemanticInvoice {
        $normalizedInvoiceNumber = trim($invoiceNumber);

        if ($normalizedInvoiceNumber === '') {
            throw new RuntimeException('Invoice number must not be empty.');
        }

        $this->framework->initialize();

        $orderData = ls_shop_generalHelper::getOrder($orderId, 'id', true);

        if (!is_array($orderData) || $orderData === []) {
            throw new RuntimeException(sprintf('Order with ID %d was not found.', $orderId));
        }

        $paymentMethodId = (int) ($orderData['paymentMethod_id'] ?? 0);
        $shippingMethodId = (int) ($orderData['shippingMethod_id'] ?? 0);
        $memberGroupId = (int) ($orderData['memberGroupInfo_id'] ?? 0);
        $taxRateIds = $this->collectTaxRateIds($orderData);

        return $this->semanticInvoiceBuilder->build(
            $orderData,
            $this->loadShopSettings(),
            $this->loadRecordById('tl_ls_shop_payment_methods', $paymentMethodId),
            $this->loadRecordById('tl_ls_shop_shipping_methods', $shippingMethodId),
            $this->loadTaxRatesByIds($taxRateIds),
            $memberGroupId > 0 ? $this->loadRecordById('tl_member_group', $memberGroupId) : null,
            $normalizedInvoiceNumber,
            $invoiceDate ?? new DateTimeImmutable('now')
        );
    }

    private function findOrderIdByOrderNumber(string $orderNumber): ?int
    {
        $orderId = $this->connection->fetchOne(
            'SELECT id FROM tl_ls_shop_orders WHERE orderNr = ?',
            [$orderNumber]
        );

        if ($orderId === false || $orderId === null) {
            return null;
        }

        return (int) $orderId;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadShopSettings(): array
    {
        $config = $this->framework->getAdapter(Config::class);

        return [
            'ls_shop_currencyCode' => $config->get('ls_shop_currencyCode'),
            'ls_shop_priceType' => $config->get('ls_shop_priceType'),
            'ls_shop_country' => $config->get('ls_shop_country'),
            'ls_shop_ownVATID' => $config->get('ls_shop_ownVATID'),
            'ls_shop_ownEmailAddress' => $config->get('ls_shop_ownEmailAddress'),
            'ls_shop_einvoicingSellerName' => $config->get('ls_shop_einvoicingSellerName'),
            'ls_shop_einvoicingSellerStreet' => $config->get('ls_shop_einvoicingSellerStreet'),
            'ls_shop_einvoicingSellerPostalCode' => $config->get('ls_shop_einvoicingSellerPostalCode'),
            'ls_shop_einvoicingSellerCity' => $config->get('ls_shop_einvoicingSellerCity'),
            'ls_shop_einvoicingSellerTaxNumber' => $config->get('ls_shop_einvoicingSellerTaxNumber'),
            'ls_shop_einvoicingSellerElectronicAddress' => $config->get('ls_shop_einvoicingSellerElectronicAddress'),
            'ls_shop_einvoicingSellerElectronicAddressScheme' => $config->get('ls_shop_einvoicingSellerElectronicAddressScheme'),
            'ls_shop_einvoicingSellerIban' => $config->get('ls_shop_einvoicingSellerIban'),
            'ls_shop_einvoicingSellerBic' => $config->get('ls_shop_einvoicingSellerBic'),
            'ls_shop_einvoicingPaymentTermsDays' => $config->get('ls_shop_einvoicingPaymentTermsDays'),
            'ls_shop_einvoicingPaymentTermsNote' => $config->get('ls_shop_einvoicingPaymentTermsNote'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadRecordById(string $table, int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        $record = $this->connection->fetchAssociative(
            sprintf('SELECT * FROM %s WHERE id = ?', $table),
            [$id]
        );

        return is_array($record) ? $record : [];
    }

    /**
     * @param list<int> $taxRateIds
     *
     * @return array<int|string, array<string, mixed>>
     */
    private function loadTaxRatesByIds(array $taxRateIds): array
    {
        if ($taxRateIds === []) {
            return [];
        }

        $rows = $this->connection->executeQuery(
            'SELECT * FROM tl_ls_shop_steuersaetze WHERE id IN (?)',
            [$taxRateIds],
            [ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $taxRatesById = [];

        foreach ($rows as $row) {
            $taxRatesById[(string) $row['id']] = $row;
        }

        return $taxRatesById;
    }

    /**
     * @param array<string, mixed> $orderData
     *
     * @return list<int>
     */
    private function collectTaxRateIds(array $orderData): array
    {
        $taxRateIds = [];
        $items = is_array($orderData['items'] ?? null) ? $orderData['items'] : [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $taxClassId = (int) ($item['taxClass'] ?? 0);

            if ($taxClassId > 0) {
                $taxRateIds[$taxClassId] = $taxClassId;
            }
        }

        foreach ([$orderData['paymentMethod_amountTaxedWith'] ?? null, $orderData['shippingMethod_amountTaxedWith'] ?? null] as $taxedWith) {
            if (!is_array($taxedWith)) {
                continue;
            }

            foreach (array_keys($taxedWith) as $taxClassId) {
                $normalizedTaxClassId = (int) $taxClassId;

                if ($normalizedTaxClassId > 0) {
                    $taxRateIds[$normalizedTaxClassId] = $normalizedTaxClassId;
                }
            }
        }

        return array_values($taxRateIds);
    }
}
