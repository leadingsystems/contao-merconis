<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Contao\Controller;
use Contao\Environment;
use Contao\Input;

final class FastFilterController
{
    public function __construct(
        private readonly FastFilterConfigurationRepository $configurationRepository,
        private readonly FastFilterSessionWriter $sessionWriter,
    ) {
    }

    /**
     * Verarbeitet ausschließlich das neue `fastFilterForm`.
     */
    public function processSentFilterSettings(): void
    {
        $this->sessionWriter->ensureSession();

        if (Input::post('FORM_SUBMIT') !== 'fastFilterForm') {
            return;
        }

        if (Input::post('resetFastFilter') || Input::post('resetFilter')) {
            $this->sessionWriter->reset();
            $this->redirectToCleanFilterUrl();
            return;
        }

        $this->sessionWriter->writeCriteria($this->normalizePostedCriteria());
        $this->redirectToCleanFilterUrl();
    }

    /**
     * @return array{attributes: array<int, list<int>>, producers: list<string>}
     */
    private function normalizePostedCriteria(): array
    {
        $criteria = [
            'attributes' => [],
            'producers' => [],
        ];

        foreach ($this->configurationRepository->getActiveFields() as $field) {
            $postedValue = Input::post('filterField_' . $field['id']);

            if ($field['dataSource'] === 'attribute') {
                $selectedValues = $this->normalizePostedList($postedValue, true);

                if ($selectedValues !== []) {
                    $criteria['attributes'][(int) $field['sourceAttribute']] = $selectedValues;
                }
            }

            if ($field['dataSource'] === 'producer') {
                $selectedProducers = $this->normalizePostedList($postedValue, false);

                if ($selectedProducers !== []) {
                    $criteria['producers'] = $selectedProducers;
                }
            }
        }

        return $criteria;
    }

    /**
     * @return list<int|string>
     */
    private function normalizePostedList(mixed $postedValue, bool $castToInteger): array
    {
        $postedValues = is_array($postedValue) ? $postedValue : [$postedValue];
        $normalizedValues = [];

        foreach ($postedValues as $postedItem) {
            if ($postedItem === null || $postedItem === '' || $postedItem === '--reset--' || $postedItem === '--checkall--') {
                continue;
            }

            $normalizedValues[] = $castToInteger ? (int) $postedItem : (string) $postedItem;
        }

        return array_values(array_unique($normalizedValues));
    }

    private function redirectToCleanFilterUrl(): void
    {
        $targetUrl = (string) Environment::get('request');
        $targetUrl = preg_replace('/(\?|&)cajaxCall=[^&]*&?/i', '$1', $targetUrl);
        $targetUrl = preg_replace('/\?$/', '', $targetUrl);
        $targetUrl = preg_replace('/(page_(?:crossSeller|standard).*?=)(.*?[0-9]*?)([^0-9]|&|$)/', '${1}1$3', $targetUrl);

        Controller::redirect($targetUrl);
    }
}
