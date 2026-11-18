<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

use Contao\DataContainer;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup\StandardMemberGroupLgnMode;

final class LegalGuaranteeBackendCallbacks
{
    /**
     * Hinweise über automatische Änderungen je Produkt-ID; ausgegeben erst im
     * `onsubmit_callback`, also nur nach erfolgreichem Speichern.
     *
     * @var array<int, list<string>>
     */
    private static array $pendingProductInfoMessages = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly ProductGuaranteeConfigurationValidator $validator = new ProductGuaranteeConfigurationValidator(),
        private readonly ProductGuaranteeValidationHelper $validationHelper = new ProductGuaranteeValidationHelper(),
        private readonly StandardMemberGroupLgnMode $standardMemberGroupLgnMode = new StandardMemberGroupLgnMode(),
    ) {
    }

    /**
     * `save_callback` an `lgnMode` (Spec 27, Abschnitt 4).
     *
     * R4: Weicht der abgeschickte Wert vom gespeicherten ab, gälte danach "LGN aus" und
     * stehen Varianten auf `override`, wird das Speichern abgelehnt.
     * R1: Ist der abgeschickte Wert gleich dem gespeicherten, gilt "LGN aus" und ist
     * `enableGaran` an, wird `lgnMode` auf den Wert nach R1 gesetzt. Ändert der Nutzer
     * `lgnMode`, greift stattdessen R2 im `onbeforesubmit_callback`.
     * `lgnModeOptions` enthält je Wert das Paar aus Beschriftung und Erläuterung für den
     * Hilfe-Assistenten; der Hinweis nennt nur die Beschriftung.
     *
     * @throws \Exception Bei einer Ablehnung nach R4
     */
    public function handleProductLgnModeSave(mixed $value, DataContainer $dataContainer): string
    {
        $productId = $this->getRecordId($dataContainer);
        $productData = $this->fetchProductData($productId);
        $standardMemberGroupLgnMode = $this->standardMemberGroupLgnMode->getStoredLgnMode();
        $storedLgnMode = $this->standardMemberGroupLgnMode->normalizeProductLgnMode($productData['lgnMode'] ?? null);
        $submittedLgnMode = $this->standardMemberGroupLgnMode->normalizeProductLgnMode($value);
        $isLgnOffForSubmittedLgnMode = $this->standardMemberGroupLgnMode->isLgnOff(
            $standardMemberGroupLgnMode,
            $submittedLgnMode
        );

        if ($submittedLgnMode !== $storedLgnMode) {
            if (!$isLgnOffForSubmittedLgnMode) {
                return $submittedLgnMode;
            }

            $overrideVariantIdentifiers = $this->validator->getOverrideVariantIdentifiers(
                $this->fetchRelatedVariants($productId)
            );

            if ($overrideVariantIdentifiers !== []) {
                throw new \Exception(sprintf(
                    $GLOBALS['TL_LANG']['tl_ls_shop_product']['legalGuaranteeMessages']['lgnModeRejectedByOwnVariantDetails'],
                    implode(', ', $overrideVariantIdentifiers)
                ));
            }

            return $submittedLgnMode;
        }

        if (!$isLgnOffForSubmittedLgnMode || $this->getEffectiveProductEnableGaran($productData, $dataContainer) !== '1') {
            return $submittedLgnMode;
        }

        $lgnModeForEnabledGaran = $this->standardMemberGroupLgnMode->getLgnModeForEnabledGaran($standardMemberGroupLgnMode);

        if ($productId !== null) {
            $this->queueProductInfoMessage($productId, sprintf(
                $GLOBALS['TL_LANG']['tl_ls_shop_product']['legalGuaranteeMessages']['lgnModeChangedForManufacturerGuarantee'],
                $GLOBALS['TL_LANG']['tl_ls_shop_product']['lgnModeOptions'][$lgnModeForEnabledGaran][0]
            ));
        }

        return $lgnModeForEnabledGaran;
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
     * `onbeforesubmit_callback` des Produkts, R2 (Spec 27, Abschnitt 4): Enthält `$values`
     * einen geänderten Wert für `lgnMode`, gilt dafür "LGN aus" und ist `enableGaran` an,
     * wird `enableGaran` ausgeschaltet. Dauer, Marke und Modell bleiben. Beim Umschalten
     * über `toggleSubpalette` enthält `$values` nur `enableGaran`.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    public function handleProductBeforeSubmit(array $values, DataContainer $dataContainer): array
    {
        $productId = $this->getRecordId($dataContainer);

        if ($productId === null || !array_key_exists('lgnMode', $values)) {
            return $values;
        }

        if (!$this->standardMemberGroupLgnMode->isLgnOffForProduct($values['lgnMode'])) {
            return $values;
        }

        $productData = $this->fetchProductData($productId);
        $effectiveEnableGaran = $this->normalizeCheckboxValue($values['enableGaran'] ?? ($productData['enableGaran'] ?? null));

        if ($effectiveEnableGaran !== '1') {
            return $values;
        }

        $values['enableGaran'] = '';
        $this->queueProductInfoMessage(
            $productId,
            $GLOBALS['TL_LANG']['tl_ls_shop_product']['legalGuaranteeMessages']['manufacturerGuaranteeDisabledByLgnMode']
        );

        return $values;
    }

    public function addProductSubmitInfoMessages(DataContainer $dataContainer): void
    {
        $productId = $this->getRecordId($dataContainer);

        if ($productId === null || !isset(self::$pendingProductInfoMessages[$productId])) {
            return;
        }

        foreach (array_unique(self::$pendingProductInfoMessages[$productId]) as $messageText) {
            Message::addInfo($messageText);
        }

        unset(self::$pendingProductInfoMessages[$productId]);
    }

    /**
     * `onload_callback` des Produkts: Hinweise beim Öffnen der Maske, wenn für den
     * gespeicherten Datensatz "LGN aus" gilt und `enableGaran` an ist (Ursache und Weg)
     * beziehungsweise Varianten auf `override` stehen (die Varianten).
     */
    public function addProductEditInfoMessage(DataContainer $dataContainer): void
    {
        if (!$this->isOpeningEditMask('tl_ls_shop_product')) {
            return;
        }

        $productId = $this->getRecordId($dataContainer);
        $productData = $this->fetchProductData($productId);
        $productLgnMode = $this->standardMemberGroupLgnMode->normalizeProductLgnMode($productData['lgnMode'] ?? null);

        if (!$this->standardMemberGroupLgnMode->isLgnOffForProduct($productLgnMode)) {
            return;
        }

        if ($this->normalizeCheckboxValue($productData['enableGaran'] ?? null) === '1') {
            Message::addInfo(
                'never' === $productLgnMode
                    ? $GLOBALS['TL_LANG']['tl_ls_shop_product']['legalGuaranteeMessages']['openHintProductNeverShown']
                    : $GLOBALS['TL_LANG']['tl_ls_shop_product']['legalGuaranteeMessages']['openHintStandardGroupOnlyAlwaysShow']
            );
        }

        $overrideVariantIdentifiers = $this->validator->getOverrideVariantIdentifiers(
            $this->fetchRelatedVariants($productId)
        );

        if ($overrideVariantIdentifiers !== []) {
            Message::addInfo(sprintf(
                $GLOBALS['TL_LANG']['tl_ls_shop_product']['legalGuaranteeMessages']['openHintVariantsWithOwnDetails'],
                implode(', ', $overrideVariantIdentifiers)
            ));
        }
    }

    /**
     * `save_callback` an `garanMode` der Variante, R3 (Spec 27, Abschnitt 4), über
     * `ProductGuaranteeConfigurationValidator::validateVariant()` mit dem gespeicherten
     * Elternprodukt.
     *
     * @throws \Exception Bei einer Ablehnung
     */
    public function validateVariantGaranMode(mixed $value, DataContainer $dataContainer): string
    {
        $variantData = $this->fetchVariantData($this->getRecordId($dataContainer));
        $variantData['garanMode'] = $value;

        $validationResult = $this->validator->validateVariant(
            $variantData,
            $this->fetchParentProductData($variantData)
        );

        $garanModeMessages = $this->validationHelper->getMessagesForField($validationResult->getMessages(), 'garanMode');
        $firstGaranModeMessage = reset($garanModeMessages);

        if (
            is_array($firstGaranModeMessage)
            && $firstGaranModeMessage['code'] === ProductGuaranteeConfigurationValidator::MESSAGE_GARAN_OVERRIDE_LGN_REQUIRED
        ) {
            throw new \Exception(
                $GLOBALS['TL_LANG']['tl_ls_shop_variant']['legalGuaranteeMessages']['ownDetailsRejectedWithoutLegalGuarantee']
            );
        }

        $this->validationHelper->throwValidationExceptionForField(
            $garanModeMessages,
            'garanMode',
            'tl_ls_shop_variant'
        );

        return (string) ($validationResult->getNormalizedData()['garanMode'] ?? 'inherit');
    }

    /**
     * `onload_callback` der Variante: Hinweis beim Öffnen der Maske, wenn die Variante
     * gespeichert auf `override` steht und für das Elternprodukt "LGN aus" gilt.
     */
    public function addVariantEditInfoMessage(DataContainer $dataContainer): void
    {
        if (!$this->isOpeningEditMask('tl_ls_shop_variant')) {
            return;
        }

        $variantData = $this->fetchVariantData($this->getRecordId($dataContainer));

        if ('override' !== trim((string) ($variantData['garanMode'] ?? ''))) {
            return;
        }

        $parentProductData = $this->fetchParentProductData($variantData);

        if (!$this->standardMemberGroupLgnMode->isLgnOffForProduct($parentProductData['lgnMode'] ?? null)) {
            return;
        }

        Message::addInfo(
            $GLOBALS['TL_LANG']['tl_ls_shop_variant']['legalGuaranteeMessages']['openHintParentProductWithoutLegalGuarantee']
        );
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
     * Der `onload_callback` läuft vor den `save_callback`s und vor AJAX-Aktionen wie
     * `toggleSubpalette`; dort wäre der gelesene Stand nur ein Zwischenstand.
     */
    private function isOpeningEditMask(string $table): bool
    {
        return Input::get('act') === 'edit'
            && Input::post('FORM_SUBMIT') !== $table
            && !Environment::get('isAjaxRequest');
    }

    /**
     * `enableGaran` aus dem Request, wenn das Feld im Formular steht, sonst gespeichert.
     *
     * @param array<string, mixed> $productData
     */
    private function getEffectiveProductEnableGaran(array $productData, DataContainer $dataContainer): string
    {
        $postedEnableGaranValue = $this->getPostedValue('enableGaran', $dataContainer);

        return $this->normalizeCheckboxValue($postedEnableGaranValue ?? ($productData['enableGaran'] ?? null));
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
            'SELECT lgnMode, enableGaran, guaranteeDurationMonths, guaranteeBrand, guaranteeModelIdentifier, lsShopProductProducer
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

    /**
     * In "Mehrere bearbeiten" trägt der Feldname im Request das Suffix `_<id>`.
     */
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

    private function queueProductInfoMessage(int $productId, string $messageText): void
    {
        self::$pendingProductInfoMessages[$productId] ??= [];
        self::$pendingProductInfoMessages[$productId][] = $messageText;
    }
}
