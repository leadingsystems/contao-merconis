<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\FastFilter;

use Contao\Database;
use Merconis\Core\ls_shop_languageHelper;

class FastFilterConfigurationRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveFields(): array
    {
        $fields = [];
        $fieldResult = Database::getInstance()->prepare("
            SELECT      *
            FROM        `tl_ls_shop_filter_fields`
            WHERE       `published` = '1'
                AND     `dataSource` IN ('attribute', 'producer')
            ORDER BY    `priority` DESC
        ")->execute();

        while ($fieldResult->next()) {
            $field = $fieldResult->row();
            $field['id'] = (int) $field['id'];
            $field['sourceAttribute'] = (int) ($field['sourceAttribute'] ?? 0);
            $field['title'] = $this->getFieldTitle($field['id']);
            $field['fieldValues'] = $this->getConfiguredFieldValues($field);
            $fields[$field['id']] = $field;
        }

        return $fields;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAttributeValues(int $attributeId): array
    {
        $values = [];
        $valueResult = Database::getInstance()->prepare("
            SELECT      `id`, `pid`, `sorting`, `alias`, `classForFilterFormField`, `importantFieldValue`, `numericValue`
            FROM        `tl_ls_shop_attribute_values`
            WHERE       `pid` = ?
            ORDER BY    `sorting` ASC
        ")->execute($attributeId);

        while ($valueResult->next()) {
            $valueId = (int) $valueResult->id;
            $values[$valueId] = [
                'id' => $valueId,
                'label' => $this->getAttributeValueTitle($valueId),
                'alias' => (string) $valueResult->alias,
                'class' => (string) $valueResult->classForFilterFormField,
                'important' => (bool) $valueResult->importantFieldValue,
                'numericValue' => $valueResult->numericValue,
                'sorting' => (int) $valueResult->sorting,
            ];
        }

        return $values;
    }

    private function getFieldTitle(int $fieldId): string
    {
        return (string) ls_shop_languageHelper::getMultiLanguage($fieldId, 'tl_ls_shop_filter_fields', ['title'], [$this->getLanguage()]);
    }

    private function getAttributeValueTitle(int $valueId): string
    {
        return (string) ls_shop_languageHelper::getMultiLanguage($valueId, 'tl_ls_shop_attribute_values', ['title'], [$this->getLanguage()]);
    }

    private function getLanguage(): string
    {
        global $objPage;

        return ($objPage->language ?? null) ?: ls_shop_languageHelper::getFallbackLanguage();
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return array<int, array<string, mixed>>
     */
    private function getConfiguredFieldValues(array $field): array
    {
        if ($field['dataSource'] !== 'producer') {
            return [];
        }

        $fieldValues = [];
        $valueResult = Database::getInstance()->prepare("
            SELECT      *
            FROM        `tl_ls_shop_filter_field_values`
            WHERE       `pid` = ?
            ORDER BY    `sorting` ASC
        ")->execute($field['id']);

        while ($valueResult->next()) {
            $fieldValues[] = $valueResult->row();
        }

        return $fieldValues;
    }
}
