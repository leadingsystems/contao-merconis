<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

use Contao\DataContainer;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Doctrine\DBAL\Connection;

final class LegalGuaranteeBackendCallbacks
{
    /**
     * @var array<int, list<string>>
     */
    private static array $pendingProductInfoMessages = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly ProductGuaranteeConfigurationValidator $validator = new ProductGuaranteeConfigurationValidator(),
        private readonly ProductGuaranteeValidationHelper $validationHelper = new ProductGuaranteeValidationHelper(),
    ) {
    }

    public function validateProductEnableGll(mixed $value, DataContainer $dataContainer): string
    {
        $productId = $this->getRecordId($dataContainer);
        $productData = $this->fetchProductData($productId);
        $storedEnableGll = $this->normalizeCheckboxValue($productData['enableGll'] ?? null);
        $storedEnableGaran = $this->normalizeCheckboxValue($productData['enableGaran'] ?? null);
        $normalizedEnableGll = $this->normalizeCheckboxValue($value);
        $productData['enableGll'] = $normalizedEnableGll;
        $postedEnableGaranValue = $this->getPostedValue('enableGaran', $dataContainer);

        if ($postedEnableGaranValue !== null) {
            $productData['enableGaran'] = $postedEnableGaranValue;
        }

        $effectiveEnableGaran = $postedEnableGaranValue !== null
            ? $this->normalizeCheckboxValue($postedEnableGaranValue)
            : $storedEnableGaran;

        if (
            $storedEnableGll === ''
            && $normalizedEnableGll === ''
            && $effectiveEnableGaran === '1'
        ) {
            if ($productId !== null) {
                $this->queueProductInfoMessage($productId, 'gll_enabled_by_garan');
            }

            return '1';
        }

        $validationResult = $this->validator->validateProduct($productData, $this->fetchRelatedVariants($productId));

        $this->validationHelper->throwValidationExceptionForField(
            $validationResult->getMessages(),
            'enableGll',
            'tl_ls_shop_product'
        );

        return $normalizedEnableGll;
    }

    public function validateProductEnableGaran(mixed $value, DataContainer $dataContainer): string
    {
        $productData = $this->fetchProductData($this->getRecordId($dataContainer));
        $postedEnableGllValue = $this->getPostedValue('enableGll', $dataContainer);

        if ($postedEnableGllValue !== null) {
            $productData['enableGll'] = $postedEnableGllValue;
        }

        $productData['enableGaran'] = $value;

        $validationResult = $this->validator->validateProduct($productData);

        $this->validationHelper->throwValidationExceptionForField(
            $validationResult->getMessages(),
            'enableGaran',
            'tl_ls_shop_product'
        );

        return (string) ($validationResult->getNormalizedData()['enableGaran'] ?? '');
    }

    public function validateProductGuaranteeDurationMonths(mixed $value, DataContainer $dataContainer): ?string
    {
        return $this->validateGuaranteeDurationMonths($value, 'tl_ls_shop_product');
    }

    public function loadProductGuaranteeBrand(mixed $value, DataContainer $dataContainer): mixed
    {
        if ('' !== trim((string) $value)) {
            return $value;
        }

        $productData = $this->fetchProductData($this->getRecordId($dataContainer));

        return $this->validator->getInitialGuaranteeBrand((string) ($productData['lsShopProductProducer'] ?? ''));
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    public function handleProductBeforeSubmit(array $values, DataContainer $dataContainer): array
    {
        $productId = $this->getRecordId($dataContainer);

        if ($productId === null || !array_key_exists('enableGll', $values)) {
            return $values;
        }

        $productData = $this->fetchProductData($productId);
        $storedEnableGll = $this->normalizeCheckboxValue($productData['enableGll'] ?? null);
        $submittedEnableGll = $this->normalizeCheckboxValue($values['enableGll'] ?? null);

        if ($storedEnableGll !== '1' || $submittedEnableGll !== '') {
            return $values;
        }

        $effectiveEnableGaran = $this->normalizeCheckboxValue($values['enableGaran'] ?? ($productData['enableGaran'] ?? null));

        if ($effectiveEnableGaran !== '1') {
            return $values;
        }

        $values['enableGaran'] = '';
        $this->queueProductInfoMessage($productId, 'garan_disabled_by_gll');

        return $values;
    }

    public function addProductSubmitInfoMessages(DataContainer $dataContainer): void
    {
        $productId = $this->getRecordId($dataContainer);

        if ($productId === null || !isset(self::$pendingProductInfoMessages[$productId])) {
            return;
        }

        foreach (array_unique(self::$pendingProductInfoMessages[$productId]) as $messageKey) {
            $messageText = $GLOBALS['TL_LANG']['tl_ls_shop_product'][$messageKey] ?? null;

            if (!is_string($messageText) || $messageText === '') {
                continue;
            }

            Message::addInfo($messageText);
        }

        unset(self::$pendingProductInfoMessages[$productId]);
    }

    public function addProductEditInfoMessage(DataContainer $dataContainer): void
    {
        // Der `onload_callback` läuft vor den `save_callback`s und vor AJAX-Aktionen wie
        // `toggleSubpalette`; dort wäre der gelesene Stand nur ein Zwischenstand.
        if (
            Input::get('act') !== 'edit'
            || Input::post('FORM_SUBMIT') === 'tl_ls_shop_product'
            || Environment::get('isAjaxRequest')
        ) {
            return;
        }

        $productData = $this->fetchProductData($this->getRecordId($dataContainer));

        if (
            $this->normalizeCheckboxValue($productData['enableGaran'] ?? null) !== '1'
            || $this->normalizeCheckboxValue($productData['enableGll'] ?? null) === '1'
        ) {
            return;
        }

        $messageText = $GLOBALS['TL_LANG']['tl_ls_shop_product']['garan_not_output_without_gll'] ?? null;

        if (is_string($messageText) && $messageText !== '') {
            Message::addInfo($messageText);
        }
    }

    public function validateVariantGaranMode(mixed $value, DataContainer $dataContainer): string
    {
        $variantData = $this->fetchVariantData($this->getRecordId($dataContainer));
        $variantData['garanMode'] = $value;

        $validationResult = $this->validator->validateVariant(
            $variantData,
            $this->fetchParentProductData($variantData)
        );

        $this->validationHelper->throwValidationExceptionForField(
            $validationResult->getMessages(),
            'garanMode',
            'tl_ls_shop_variant'
        );

        return (string) ($validationResult->getNormalizedData()['garanMode'] ?? 'inherit');
    }

    public function validateVariantGuaranteeDurationMonths(mixed $value, DataContainer $dataContainer): ?string
    {
        return $this->validateGuaranteeDurationMonths($value, 'tl_ls_shop_variant');
    }

    public function loadVariantGuaranteeBrand(mixed $value, DataContainer $dataContainer): mixed
    {
        if ('' !== trim((string) $value)) {
            return $value;
        }

        $variantData = $this->fetchVariantData($this->getRecordId($dataContainer));
        $parentProductData = $this->fetchParentProductData($variantData);
        $parentGuaranteeBrand = trim((string) ($parentProductData['guaranteeBrand'] ?? ''));

        if ('' !== $parentGuaranteeBrand) {
            return $parentGuaranteeBrand;
        }

        return $this->validator->getInitialGuaranteeBrand((string) ($parentProductData['lsShopProductProducer'] ?? ''));
    }

    private function validateGuaranteeDurationMonths(mixed $value, string $languageScope): ?string
    {
        $durationResult = $this->validationHelper->normalizeDurationInput($value);

        $this->validationHelper->throwValidationExceptionForField(
            $durationResult['messages'],
            'guaranteeDurationMonths',
            $languageScope
        );

        return $durationResult['storedValue'];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchProductData(?int $productId): array
    {
        if ($productId === null) {
            return [];
        }

        $productData = $this->connection->fetchAssociative(
            'SELECT enableGll, enableGaran, guaranteeDurationMonths, guaranteeBrand, guaranteeModelIdentifier, lsShopProductProducer
             FROM tl_ls_shop_product
             WHERE id = ?',
            [$productId]
        );

        return is_array($productData) ? $productData : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchVariantData(?int $variantId): array
    {
        if ($variantId === null) {
            return [];
        }

        $variantData = $this->connection->fetchAssociative(
            'SELECT id, pid, garanMode, guaranteeDurationMonths, guaranteeBrand, guaranteeModelIdentifier
             FROM tl_ls_shop_variant
             WHERE id = ?',
            [$variantId]
        );

        return is_array($variantData) ? $variantData : [];
    }

    /**
     * @param array<string, mixed> $variantData
     *
     * @return array<string, mixed>
     */
    private function fetchParentProductData(array $variantData): array
    {
        $parentProductId = isset($variantData['pid']) ? (int) $variantData['pid'] : null;

        return $this->fetchProductData($parentProductId ?: null);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRelatedVariants(?int $productId): array
    {
        if ($productId === null) {
            return [];
        }

        $relatedVariants = $this->connection->fetchAllAssociative(
            'SELECT id, title, lsShopVariantCode, garanMode
             FROM tl_ls_shop_variant
             WHERE pid = ?
             ORDER BY id ASC',
            [$productId]
        );

        return array_values(array_filter($relatedVariants, 'is_array'));
    }

    private function getPostedValue(string $fieldName, DataContainer $dataContainer): mixed
    {
        $postedValue = Input::post($fieldName);

        if ($postedValue !== null) {
            return $postedValue;
        }

        $recordId = $this->getRecordId($dataContainer);

        if ($recordId === null) {
            return null;
        }

        return Input::post($fieldName . '_' . $recordId);
    }

    private function getRecordId(DataContainer $dataContainer): ?int
    {
        $recordId = (int) ($dataContainer->id ?? 0);

        if ($recordId > 0) {
            return $recordId;
        }

        $activeRecord = $dataContainer->activeRecord ?? null;

        if (is_object($activeRecord) && isset($activeRecord->id) && (int) $activeRecord->id > 0) {
            return (int) $activeRecord->id;
        }

        return null;
    }

    private function normalizeCheckboxValue(mixed $value): string
    {
        return $this->validationHelper->isTruthy($value) ? '1' : '';
    }

    private function queueProductInfoMessage(int $productId, string $messageKey): void
    {
        self::$pendingProductInfoMessages[$productId] ??= [];
        self::$pendingProductInfoMessages[$productId][] = $messageKey;
    }
}
