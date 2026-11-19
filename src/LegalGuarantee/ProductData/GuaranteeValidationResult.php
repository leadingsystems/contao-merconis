<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

final class GuaranteeValidationResult
{
    /**
     * @param array<string, mixed> $normalizedData
     * @param array<string, mixed> $effectiveData
     * @param list<array{code: string, parameters: array<int, string>}> $messages
     */
    public function __construct(
        private readonly array $normalizedData,
        private readonly array $effectiveData,
        private readonly array $messages,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getNormalizedData(): array
    {
        return $this->normalizedData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEffectiveData(): array
    {
        return $this->effectiveData;
    }

    /**
     * @return list<array{code: string, parameters: array<int, string>}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
