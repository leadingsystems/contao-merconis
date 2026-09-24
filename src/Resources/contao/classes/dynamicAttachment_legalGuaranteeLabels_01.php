<?php

declare(strict_types=1);

namespace Merconis\Core;

use Contao\System;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderConfirmationAttachmentGenerator;

class dynamicAttachment_legalGuaranteeLabels_01
{
    /**
     * @var array<string, mixed>
     */
    private array $arrOrder = [];

    /**
     * @var list<string>
     */
    private array $cleanupFilePaths = [];

    /**
     * @param array<string, mixed> $arrOrder
     */
    public function __construct($arrOrder = [], $messageCounterNr = null, $arrFlexParameters = [])
    {
        $this->arrOrder = is_array($arrOrder) ? $arrOrder : [];
    }

    /**
     * @return list<string>
     */
    public function parse(): array
    {
        try {
            $generatedAttachments = System::getContainer()
                ->get(OrderConfirmationAttachmentGenerator::class)
                ->generateAttachments($this->arrOrder);
        } catch (\Throwable) {
            $this->cleanupFilePaths = [];

            return [];
        }

        $this->cleanupFilePaths = $generatedAttachments['cleanupRelativePaths'];

        return $generatedAttachments['relativePaths'];
    }

    /**
     * @return list<string>
     */
    public function getCleanupFilePaths(): array
    {
        return $this->cleanupFilePaths;
    }
}
