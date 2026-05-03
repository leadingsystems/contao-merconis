<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

final class FastFilterFormService
{
    public function __construct(
        private readonly FastFilterConfigurationRepository $configurationRepository,
        private readonly FastFilterSessionWriter $sessionWriter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewModel(): array
    {
        $this->sessionWriter->ensureSession();

        $criteria = $_SESSION['lsShop']['fastFilter']['criteria'] ?? ['attributes' => [], 'producers' => []];
        $availableOptions = $_SESSION['lsShop']['fastFilter']['availableOptions'] ?? ['attributes' => [], 'producers' => []];
        $matchEstimates = $_SESSION['lsShop']['fastFilter']['matchEstimates'] ?? ['attributes' => [], 'producers' => []];
        $fields = [];

        foreach ($this->configurationRepository->getActiveFields() as $field) {
            if ($field['dataSource'] === 'attribute') {
                $viewField = $this->buildAttributeField($field, $criteria, $availableOptions, $matchEstimates);
            } elseif ($field['dataSource'] === 'producer') {
                $viewField = $this->buildProducerField($field, $criteria, $availableOptions, $matchEstimates);
            } else {
                continue;
            }

            if ($viewField !== null) {
                $fields[] = $viewField;
            }
        }

        return [
            'fields' => $fields,
            'activeCount' => $this->countActiveCriteria($criteria),
            'hasOptions' => $fields !== [],
        ];
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $availableOptions
     * @param array<string, mixed> $matchEstimates
     *
     * @return array<string, mixed>|null
     */
    private function buildAttributeField(array $field, array $criteria, array $availableOptions, array $matchEstimates): ?array
    {
        $attributeId = (int) $field['sourceAttribute'];
        $availableValueIds = array_keys($availableOptions['attributes'][$attributeId] ?? []);

        if ($availableValueIds === []) {
            return null;
        }

        if (!empty($field['disableFilterIfOnlyOneValue']) && count($availableValueIds) <= 1) {
            return null;
        }

        $selectedValues = array_map('intval', $criteria['attributes'][$attributeId] ?? []);
        $options = [];

        foreach ($this->configurationRepository->getAttributeValues($attributeId) as $valueId => $value) {
            if (!in_array($valueId, $availableValueIds, true)) {
                continue;
            }

            $matchEstimate = (int) ($matchEstimates['attributes'][$attributeId][$valueId]['products'] ?? 0);
            $options[] = [
                'value' => $valueId,
                'label' => $value['label'],
                'cssClass' => $value['class'],
                'important' => $value['important'],
                'checked' => in_array($valueId, $selectedValues, true),
                'numericValue' => $availableOptions['attributes'][$attributeId][$valueId]['numericValue'] ?? $value['numericValue'],
                'matchEstimate' => $matchEstimate,
                'hasMatches' => $matchEstimate > 0,
            ];
        }

        return $this->createBaseField($field, $options) + [
            'source' => 'attribute',
            'sourceAttribute' => $attributeId,
            'selectedValues' => $selectedValues,
            'filterMode' => 'or',
        ];
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $availableOptions
     * @param array<string, mixed> $matchEstimates
     *
     * @return array<string, mixed>|null
     */
    private function buildProducerField(array $field, array $criteria, array $availableOptions, array $matchEstimates): ?array
    {
        $availableProducers = $availableOptions['producers'] ?? [];

        if (!is_array($availableProducers) || count($availableProducers) <= 1) {
            return null;
        }

        $selectedProducers = array_map('strval', $criteria['producers'] ?? []);
        $configuredValues = $this->mapConfiguredProducerValues($field['fieldValues'] ?? []);
        $options = [];

        foreach ($availableProducers as $producer) {
            $producer = (string) $producer;
            $configuredValue = $configuredValues[$producer] ?? [];
            $matchEstimate = (int) ($matchEstimates['producers'][$producer]['products'] ?? 0);
            $options[] = [
                'value' => $producer,
                'label' => $producer,
                'cssClass' => (string) ($configuredValue['classForFilterFormField'] ?? ''),
                'important' => !empty($configuredValue['importantFieldValue']),
                'checked' => in_array($producer, $selectedProducers, true),
                'numericValue' => null,
                'matchEstimate' => $matchEstimate,
                'hasMatches' => $matchEstimate > 0,
            ];
        }

        return $this->createBaseField($field, $options) + [
            'source' => 'producer',
            'sourceAttribute' => null,
            'selectedValues' => $selectedProducers,
            'filterMode' => null,
        ];
    }

    /**
     * @param array<string, mixed>        $field
     * @param list<array<string, mixed>>  $options
     *
     * @return array<string, mixed>
     */
    private function createBaseField(array $field, array $options): array
    {
        return [
            'id' => (int) $field['id'],
            'alias' => (string) ($field['alias'] ?? ''),
            'label' => (string) ($field['title'] ?? ''),
            'type' => ((string) ($field['filterFormFieldType'] ?? 'checkbox')) === 'radio' ? 'radio' : 'checkbox',
            'cssClass' => (string) ($field['classForFilterFormField'] ?? ''),
            'numItemsInReducedMode' => (int) ($field['numItemsInReducedMode'] ?? 0),
            'isActive' => $this->fieldHasSelectedOption($options),
            'options' => $options,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $configuredValues
     *
     * @return array<string, array<string, mixed>>
     */
    private function mapConfiguredProducerValues(array $configuredValues): array
    {
        $mappedValues = [];

        foreach ($configuredValues as $configuredValue) {
            $mappedValues[(string) $configuredValue['filterValue']] = $configuredValue;
        }

        return $mappedValues;
    }

    /**
     * @param list<array<string, mixed>> $options
     */
    private function fieldHasSelectedOption(array $options): bool
    {
        foreach ($options as $option) {
            if (!empty($option['checked'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function countActiveCriteria(array $criteria): int
    {
        $activeCount = 0;

        foreach ($criteria['attributes'] ?? [] as $selectedValues) {
            if (is_array($selectedValues) && $selectedValues !== []) {
                $activeCount++;
            }
        }

        if (($criteria['producers'] ?? []) !== []) {
            $activeCount++;
        }

        return $activeCount;
    }
}
