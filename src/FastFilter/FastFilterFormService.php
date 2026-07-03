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
            'activeCount' => $this->countActiveCriteria($criteria, $availableOptions),
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
        $type = ((string) ($field['filterFormFieldType'] ?? 'checkbox')) === 'radio' ? 'radio' : 'checkbox';
        $filterDisplayMode = (string) ($field['filterDisplayMode'] ?? 'showMoreLess');

        if ($type === 'radio' && $filterDisplayMode === 'sliderDirect') {
            $filterDisplayMode = 'showMoreLess';
        }

        return [
            'id' => (int) $field['id'],
            'alias' => (string) ($field['alias'] ?? ''),
            'label' => (string) ($field['title'] ?? ''),
            'type' => $type,
            'cssClass' => (string) ($field['classForFilterFormField'] ?? ''),
            'filterDisplayMode' => $filterDisplayMode,
            'numItemsInReducedMode' => (int) ($field['numItemsInReducedMode'] ?? 0),
            'rangeSliderDecimalSeparator' => (string) ($field['rangeSliderDecimalSeparator'] ?? 'dot'),
            'rangeSliderThousandSeparator' => (string) ($field['rangeSliderThousandSeparator'] ?? 'none'),
            'rangeSliderMinOptionCount' => (int) ($field['rangeSliderMinOptionCount'] ?? 10),
            'rangeSliderInitialOptionCount' => (int) ($field['rangeSliderInitialOptionCount'] ?? 10),
            'rangeSliderInitialPosition' => (string) ($field['rangeSliderInitialPosition'] ?? 'bottom'),
            'rangeSliderAutoOptionVisibility' => (string) ($field['rangeSliderAutoOptionVisibility'] ?? 'show'),
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
     * @param array<string, mixed> $availableOptions
     */
    private function countActiveCriteria(array $criteria, array $availableOptions): int
    {
        $activeCount = 0;
        $availableAttributes = is_array($availableOptions['attributes'] ?? null) ? $availableOptions['attributes'] : [];
        $attributeCriteria = is_array($criteria['attributes'] ?? null) ? $criteria['attributes'] : [];

        foreach ($attributeCriteria as $attributeId => $selectedValues) {
            $availableValueIds = array_keys($availableAttributes[(int) $attributeId] ?? []);

            if (!is_array($selectedValues) || $availableValueIds === []) {
                continue;
            }

            $selectedValueIds = array_values(array_unique(array_map('intval', $selectedValues)));

            if (array_intersect($selectedValueIds, array_map('intval', $availableValueIds)) !== []) {
                $activeCount++;
            }
        }

        $selectedProducers = is_array($criteria['producers'] ?? null) ? array_map('strval', $criteria['producers']) : [];
        $availableProducers = is_array($availableOptions['producers'] ?? null) ? array_map('strval', $availableOptions['producers']) : [];

        if (array_intersect($selectedProducers, $availableProducers) !== []) {
            $activeCount++;
        }

        return $activeCount;
    }
}
