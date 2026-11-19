<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class LegalGuaranteeOrderConfirmationAttachmentWiringTest extends TestCase
{
    private const SERVICES_PATH = __DIR__ . '/../../src/Resources/config/services.yml';
    private const ORDER_MESSAGES_PATH = __DIR__ . '/../../src/Resources/contao/classes/ls_shop_orderMessages.php';
    private const LEGACY_DYNAMIC_ATTACHMENT_PATH = __DIR__ . '/../../src/Resources/contao/classes/dynamicAttachment_legalGuaranteeLabels_01.php';
    private const MIGRATION_DOCUMENT_PATH = __DIR__ . '/../../pcf-migrations/001-order-confirmation-legal-guarantee-attachments.md';
    private const MIGRATION_GENERATOR_PATH = __DIR__ . '/../../pcf-migrations/001-order-confirmation-legal-guarantee-attachments/dynamicAttachment_legalGuaranteeLabels_01.php';

    public function testServicesRegisterConsoleCommandAndNoLongerRegisterAugmenter(): void
    {
        $servicesContents = (string) file_get_contents(self::SERVICES_PATH);

        self::assertStringContainsString(
            "LeadingSystems\\MerconisBundle\\Command\\LegalGuaranteeOrderAttachmentsCommand:\n    tags:\n      - { name: 'console.command', command: 'merconis:legal-guarantee:order-attachments' }",
            $servicesContents
        );
        self::assertStringNotContainsString('OrderConfirmationMessageAugmenter', $servicesContents);
    }

    public function testOrderMessagesNoLongerUsesAugmenterOrCleanupMechanism(): void
    {
        $orderMessagesContents = (string) file_get_contents(self::ORDER_MESSAGES_PATH);

        self::assertStringNotContainsString('OrderConfirmationMessageAugmenter', $orderMessagesContents);
        self::assertStringNotContainsString('cleanupAttachmentFiles', $orderMessagesContents);
        self::assertStringNotContainsString('getCleanupFilePaths', $orderMessagesContents);
        self::assertStringContainsString("getParameter('contao.web_dir')", $orderMessagesContents);
    }

    public function testDynamicAttachmentGeneratorMovedToManualMigration(): void
    {
        self::assertFileDoesNotExist(self::LEGACY_DYNAMIC_ATTACHMENT_PATH);
        self::assertFileExists(self::MIGRATION_DOCUMENT_PATH);
        self::assertFileExists(self::MIGRATION_GENERATOR_PATH);
    }
}
