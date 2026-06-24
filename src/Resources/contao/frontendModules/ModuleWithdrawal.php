<?php
declare(strict_types=1);

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalConfirmationTokenProcessor;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenAProcessor;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenBProcessor;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalScreenCProcessor;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalTestmodeProcessor;

class ModuleWithdrawal extends Module
{
    private const SCREEN_A = 'screenA';
    private const SCREEN_B = 'screenB';
    private const SCREEN_C = 'screenC';
    private const RATE_LIMIT_SCOPE_SCREEN_A_LOOKUP = 'screenA_lookup';
    private const RATE_LIMIT_SCOPE_SCREEN_C_IP = 'screenC_ip';
    private const RATE_LIMIT_SCOPE_SCREEN_C_MAIL = 'screenC_mail';
    private const RATE_LIMIT_WINDOW_SECONDS_SCREEN_A = 3600;
    private const RATE_LIMIT_WINDOW_SECONDS_SCREEN_C_IP = 3600;
    private const RATE_LIMIT_WINDOW_SECONDS_SCREEN_C_MAIL = 86400;
    private const RATE_LIMIT_MAX_HITS_SCREEN_A = 20;
    private const RATE_LIMIT_MAX_HITS_SCREEN_C_IP = 5;
    private const RATE_LIMIT_MAX_HITS_SCREEN_C_MAIL = 3;
    private const SESSION_KEY_FAILED_ATTEMPTS = 'lsShop.withdrawal.screenAFailedAttempts';
    private const FORM_SUBMIT_SCREEN_A = 'ls_shop_withdrawal_screenA';
    private const FORM_SUBMIT_SCREEN_B = 'ls_shop_withdrawal_screenB';
    private const FORM_SUBMIT_SCREEN_C = 'ls_shop_withdrawal_screenC';
    private const HONEYPOT_FIELD_NAME = 'additional_info';
    protected $strTemplate = 'mod_ls_shop_withdrawal';

    public function generate()
    {
        if ((string) Input::post('isAjax') === '1' && (string) Input::post('action') === 'updateWithdrawalQuantity') {
            echo $this->buildScreenBQuantitySnippet();
            exit;
        }

        if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### MERCONIS Widerruf ###';

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $this->Template->activeScreen = $this->resolveActiveScreen();
        $this->Template->screenAData = [];
        $this->Template->screenBData = [];
        $this->Template->screenCData = [];

        if ($this->Template->activeScreen === self::SCREEN_A) {
            $this->Template->screenAData = $this->buildScreenAData();
            return;
        }

        if ($this->Template->activeScreen === self::SCREEN_B) {
            $this->Template->screenBData = $this->buildScreenBData();
            return;
        }

        if ($this->Template->activeScreen === self::SCREEN_C) {
            $this->Template->screenCData = $this->buildScreenCData();
        }
    }

    private function resolveActiveScreen(): string
    {
        if ((string) Input::get('fallback') === '1') {
            return self::SCREEN_C;
        }

        if ((new WithdrawalTestmodeProcessor())->hasTestmodeParameter((string) Input::get('testmode'))) {
            return self::SCREEN_B;
        }

        if ((string) Input::get('wid') !== '') {
            return self::SCREEN_B;
        }

        return self::SCREEN_A;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildScreenAData(): array
    {
        $failedAttempts = $this->getFailedAttemptsFromSession();
        $fallbackUrl = $this->buildFallbackUrl();
        $inputIdentifier = (string) Input::post('withdrawalIdentifier');
        $errorMessage = '';

        if ((string) Input::post('FORM_SUBMIT') === self::FORM_SUBMIT_SCREEN_A) {
            $processor = new WithdrawalScreenAProcessor();
            $clientIpHash = $this->createClientIpHash();
            $bucket = $this->createTimeBucketForWindow(self::RATE_LIMIT_WINDOW_SECONDS_SCREEN_A);
            $rateLimitHitCount = $this->getRateLimitHitCount($clientIpHash, $bucket);

            $result = $processor->process(
                $inputIdentifier,
                (string) Input::post(self::HONEYPOT_FIELD_NAME),
                $failedAttempts,
                $rateLimitHitCount,
                self::RATE_LIMIT_MAX_HITS_SCREEN_A,
                function (string $normalizedIdentifier): ?string {
                    return $this->findCanonicalWithdrawalIdentifier($normalizedIdentifier);
                }
            );

            $failedAttempts = $result['failedAttempts'];
            $this->storeFailedAttemptsInSession($failedAttempts);

            if ($result['status'] === WithdrawalScreenAProcessor::STATUS_SUCCESS) {
                $this->increaseRateLimitHitCount($clientIpHash, $bucket);
                $this->cleanupRateLimitBuckets($bucket);
                $this->redirectToScreenB((string) $result['canonicalIdentifier']);
            }

            if ($result['status'] === WithdrawalScreenAProcessor::STATUS_NOT_FOUND) {
                $this->increaseRateLimitHitCount($clientIpHash, $bucket);
                $this->cleanupRateLimitBuckets($bucket);
                $errorMessage = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_error_not_found'];
            } elseif ($result['status'] === WithdrawalScreenAProcessor::STATUS_EMPTY) {
                $errorMessage = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_error_empty_identifier'];
            } elseif ($result['status'] === WithdrawalScreenAProcessor::STATUS_RATE_LIMITED) {
                $errorMessage = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_error_rate_limited'];
            }
        }

        return [
            'activeScreen' => $this->resolveActiveScreen(),
            'formSubmitName' => self::FORM_SUBMIT_SCREEN_A,
            'formAction' => ls_shop_generalHelper::getUrl(false, ['wid', 'fallback', 'testmode']),
            'identifierFieldName' => 'withdrawalIdentifier',
            'identifierValue' => $inputIdentifier,
            'honeypotFieldName' => self::HONEYPOT_FIELD_NAME,
            'headline' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_heading'],
            'labelIdentifier' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_identifier_label'],
            'placeholderIdentifier' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_identifier_placeholder'],
            'submitLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_submit'],
            'fallbackLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_entry_fallback_link_label'],
            'fallbackUrl' => $fallbackUrl,
            'fallbackHighlighted' => $failedAttempts >= 3,
            'errorMessage' => $errorMessage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildScreenBData(): array
    {
        $arrOrder = $this->resolveOrderForScreenBRequest();

        if ($arrOrder === null) {
            $this->redirectToScreenA();
        }

        $indexedOrderItems = $this->indexOrderItemsById($arrOrder['items'] ?? []);
        $withdrawnQuantities = [];
        $selectedItemIds = [];
        $changedItemIds = [];

        foreach ($indexedOrderItems as $orderItemId => $orderItem) {
            $withdrawnQuantities[$orderItemId] = $this->toFloat($orderItem['quantity'] ?? 0);
        }

        $nameValue = trim((string) (($arrOrder['firstname'] ?? '') . ' ' . ($arrOrder['lastname'] ?? '')));
        $emailValue = trim((string) ($arrOrder['customerData']['personalData']['email'] ?? ''));
        $errorMessages = [];

        if ((string) Input::post('FORM_SUBMIT') === self::FORM_SUBMIT_SCREEN_B) {
            $selectedItemIds = $this->parseSelectedOrderItemIds();
            $submittedQuantities = $this->parseSubmittedQuantities();
            $nameValue = trim((string) Input::post('withdrawalName'));
            $emailValue = trim((string) Input::post('withdrawalEmail'));

            foreach ($submittedQuantities as $orderItemId => $submittedQuantity) {
                if (array_key_exists($orderItemId, $withdrawnQuantities)) {
                    $withdrawnQuantities[$orderItemId] = $submittedQuantity;
                }
            }

            $errorMessages = $this->validateScreenBForm(
                $selectedItemIds,
                $withdrawnQuantities,
                $indexedOrderItems,
                $nameValue,
                $emailValue
            );

            if (count($errorMessages) === 0) {
                $this->persistWithdrawalFromScreenB(
                    $arrOrder,
                    $selectedItemIds,
                    $withdrawnQuantities,
                    $nameValue,
                    $emailValue
                );
            }
        }

        $processor = new WithdrawalScreenBProcessor();
        $templateItems = [];
        foreach ($indexedOrderItems as $orderItemId => $orderItem) {
            $salesUnitSize = $processor->getSalesUnitSize($orderItem);
            $quantityDecimals = $this->toQuantityDecimals($orderItem['quantityDecimals'] ?? 0);
            $orderedQuantity = $processor->getOrderedDisplayQuantity($orderItem);
            $withdrawnQuantity = $withdrawnQuantities[$orderItemId] ?? $orderedQuantity;
            $quantityChanged = abs($withdrawnQuantity - $orderedQuantity) > 0.0001;
            $productName = $this->resolveOrderItemCustomerLanguageValue(
                $orderItem,
                ['_productTitle_customerLanguage'],
                'productTitle'
            );
            $variantTitleKeys = !empty($orderItem['isVariant'])
                ? ['_variantTitle_customerLanguage', '_title_customerLanguage']
                : ['_variantTitle_customerLanguage'];
            $variantTitle = $this->resolveOrderItemCustomerLanguageValue(
                $orderItem,
                $variantTitleKeys,
                'variantTitle'
            );
            $quantityUnit = $this->resolveOrderItemCustomerLanguageValue(
                $orderItem,
                $salesUnitSize > 0 ? ['_salesUnit_customerLanguage'] : ['_quantityUnit_customerLanguage'],
                $salesUnitSize > 0 ? 'salesUnit' : 'quantityUnit'
            );
            $unitPriceQuantityUnit = $this->resolveOrderItemCustomerLanguageValue(
                $orderItem,
                $salesUnitSize > 0 ? ['_displayQuantityUnit_customerLanguage'] : ['_quantityUnit_customerLanguage'],
                $salesUnitSize > 0 ? 'displayQuantityUnit' : 'quantityUnit'
            );

            $configReferenceNumber = $processor->resolveConfiguratorReferenceNumber($orderItem);
            if ($configReferenceNumber === '') {
                $configReferenceNumber = $processor->resolveCustomizerReferenceNumber($orderItem);
            }

            if ($quantityChanged) {
                $changedItemIds[] = $orderItemId;
            }

            $templateItems[] = [
                'id' => $orderItemId,
                'productName' => $productName,
                'variantTitle' => $variantTitle,
                'productNumber' => (string) ($orderItem['artNr'] ?? ''),
                'unitPrice' => $this->formatUnitPriceDisplay(
                    $orderItem['price'] ?? 0,
                    $unitPriceQuantityUnit
                ),
                'orderedQuantity' => $orderedQuantity,
                'orderedQuantityDisplay' => $this->formatQuantityForInput($orderedQuantity),
                'quantityUnit' => $quantityUnit,
                'quantityDecimals' => $quantityDecimals,
                'salesUnitSize' => $salesUnitSize,
                'minimumQuantity' => $processor->getDisplayStepValue($salesUnitSize, $quantityDecimals),
                'inputMode' => strpos($processor->getDisplayStepValue($salesUnitSize, $quantityDecimals), '.') !== false
                    ? 'decimal'
                    : 'numeric',
                'selected' => in_array($orderItemId, $selectedItemIds, true),
                'withdrawnQuantity' => $this->formatQuantityForInput($withdrawnQuantity),
                'quantityDisplay' => $this->renderQuantityDisplay($withdrawnQuantity, $orderedQuantity, $quantityUnit),
                'quantityChanged' => $quantityChanged,
                'configReferenceNumber' => $configReferenceNumber,
            ];
        }

        return [
            'formSubmitName' => self::FORM_SUBMIT_SCREEN_B,
            'formAction' => ls_shop_generalHelper::getUrl(false, ['fallback']),
            'wid' => (string) ($arrOrder['withdrawalIdentifier'] ?? ''),
            'headline' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_heading'],
            'selectAllLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_select_all'],
            'selectItemLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_select_item'],
            'changeQuantityLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_change_quantity'],
            'invalidQuantityMessage' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_invalid_quantity_inline'],
            'noItemsSelectedMessage' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_no_items'],
            'nameLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_name_label'],
            'emailLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_email_label'],
            'submitLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_submit'],
            'errorMessages' => $errorMessages,
            'items' => $templateItems,
            'nameValue' => $nameValue,
            'emailValue' => $emailValue,
            'changedItemIds' => $changedItemIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildScreenCData(): array
    {
        $nameValue = trim((string) Input::post('withdrawalName'));
        $emailValue = trim((string) Input::post('withdrawalEmail'));
        $freetextValue = trim((string) Input::post('withdrawalFreetext'));
        $errorMessages = [];

        if ((string) Input::post('FORM_SUBMIT') === self::FORM_SUBMIT_SCREEN_C) {
            $processor = new WithdrawalScreenCProcessor();

            if ($processor->isHoneypotTriggered((string) Input::post(self::HONEYPOT_FIELD_NAME))) {
                return $this->createScreenCDataResponse($nameValue, $emailValue, $freetextValue, []);
            }

            $clientIpHash = $this->createClientIpHash();
            $ipTimeBucket = $this->createTimeBucketForWindow(self::RATE_LIMIT_WINDOW_SECONDS_SCREEN_C_IP);
            $ipHitCount = $this->getRateLimitHitCountByScope(self::RATE_LIMIT_SCOPE_SCREEN_C_IP, $clientIpHash, $ipTimeBucket);

            $recipientEmailHash = $this->createRecipientEmailHash($emailValue);
            $recipientTimeBucket = $this->createTimeBucketForWindow(self::RATE_LIMIT_WINDOW_SECONDS_SCREEN_C_MAIL);
            $recipientHitCount = $recipientEmailHash === ''
                ? 0
                : $this->getRateLimitHitCountByScope(
                    self::RATE_LIMIT_SCOPE_SCREEN_C_MAIL,
                    $recipientEmailHash,
                    $recipientTimeBucket
                );

            if ($processor->hasExceededAnyRateLimit(
                $ipHitCount,
                self::RATE_LIMIT_MAX_HITS_SCREEN_C_IP,
                $recipientHitCount,
                self::RATE_LIMIT_MAX_HITS_SCREEN_C_MAIL
            )) {
                $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_fallback_error_rate_limited'];
                $this->logScreenCRateLimitExceeded($clientIpHash, $recipientEmailHash);
            } else {
                $errorMessages = $this->validateScreenCForm($nameValue, $emailValue, $freetextValue);

                if (count($errorMessages) === 0) {
                    $this->increaseRateLimitHitCountByScope(
                        self::RATE_LIMIT_SCOPE_SCREEN_C_IP,
                        $clientIpHash,
                        $ipTimeBucket
                    );

                    if ($recipientEmailHash !== '') {
                        $this->increaseRateLimitHitCountByScope(
                            self::RATE_LIMIT_SCOPE_SCREEN_C_MAIL,
                            $recipientEmailHash,
                            $recipientTimeBucket
                        );
                    }

                    $this->cleanupRateLimitBucketsByScope(
                        self::RATE_LIMIT_SCOPE_SCREEN_C_IP,
                        $ipTimeBucket - 72
                    );
                    $this->cleanupRateLimitBucketsByScope(
                        self::RATE_LIMIT_SCOPE_SCREEN_C_MAIL,
                        $recipientTimeBucket - 14
                    );

                    $this->persistWithdrawalFromScreenC(
                        $nameValue,
                        $emailValue,
                        $freetextValue
                    );
                }
            }
        }

        return $this->createScreenCDataResponse($nameValue, $emailValue, $freetextValue, $errorMessages);
    }

    /**
     * @param array<int, string> $errorMessages
     * @return array<string, mixed>
     */
    private function createScreenCDataResponse(
        string $nameValue,
        string $emailValue,
        string $freetextValue,
        array $errorMessages
    ): array {
        return [
            'formSubmitName' => self::FORM_SUBMIT_SCREEN_C,
            'formAction' => ls_shop_generalHelper::getUrl(false, ['wid', 'testmode']),
            'honeypotFieldName' => self::HONEYPOT_FIELD_NAME,
            'headline' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_fallback_heading'],
            'nameLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_fallback_name_label'],
            'emailLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_fallback_email_label'],
            'freetextLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_fallback_freetext_label'],
            'submitLabel' => (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_fallback_submit'],
            'errorMessages' => array_values(array_unique($errorMessages)),
            'nameValue' => $nameValue,
            'emailValue' => $emailValue,
            'freetextValue' => $freetextValue,
        ];
    }

    private function buildScreenBQuantitySnippet(): string
    {
        $arrOrder = $this->resolveOrderForScreenBRequest((string) Input::post('wid'));

        if ($arrOrder === null) {
            return '';
        }

        $indexedOrderItems = $this->indexOrderItemsById($arrOrder['items'] ?? []);
        $orderItemId = (int) Input::post('itemId');
        if (!isset($indexedOrderItems[$orderItemId])) {
            return '';
        }

        $orderItem = $indexedOrderItems[$orderItemId];
        $processor = new WithdrawalScreenBProcessor();
        $quantityDecimals = $this->toQuantityDecimals($orderItem['quantityDecimals'] ?? 0);
        $salesUnitSize = $processor->getSalesUnitSize($orderItem);
        $orderedQuantity = $processor->getOrderedDisplayQuantity($orderItem);
        $withdrawnQuantity = $this->toFloat(Input::post('quantity'));
        $quantityUnit = $this->resolveOrderItemCustomerLanguageValue(
            $orderItem,
            $salesUnitSize > 0 ? ['_salesUnit_customerLanguage'] : ['_quantityUnit_customerLanguage'],
            $salesUnitSize > 0 ? 'salesUnit' : 'quantityUnit'
        );

        if (!$processor->isValidWithdrawnQuantity(
            $withdrawnQuantity,
            $orderedQuantity,
            $processor->getDisplayMinimumQuantity($salesUnitSize, $quantityDecimals),
            $quantityDecimals,
            $salesUnitSize
        )) {
            $withdrawnQuantity = $orderedQuantity;
        }

        return $this->renderQuantityDisplay(
            $withdrawnQuantity,
            $orderedQuantity,
            $quantityUnit
        );
    }

    private function createClientIpHash(): string
    {
        $clientIp = trim((string) Environment::get('ip'));

        if ($clientIp === '') {
            $clientIp = '0.0.0.0';
        }

        return hash('sha256', $clientIp);
    }

    private function createTimeBucketForWindow(int $windowInSeconds): int
    {
        if ($windowInSeconds <= 0) {
            $windowInSeconds = 1;
        }

        return (int) floor(time() / $windowInSeconds);
    }

    private function getRateLimitHitCount(string $identifierHash, int $timeBucket): int
    {
        return $this->getRateLimitHitCountByScope(
            self::RATE_LIMIT_SCOPE_SCREEN_A_LOOKUP,
            $identifierHash,
            $timeBucket
        );
    }

    private function getRateLimitHitCountByScope(string $scope, string $identifierHash, int $timeBucket): int
    {
        $objResult = Database::getInstance()
            ->prepare(
                "SELECT `hitCount`
                 FROM `tl_ls_shop_withdrawal_rate_limit`
                 WHERE `scope` = ?
                   AND `identifierHash` = ?
                   AND `timeBucket` = ?"
            )
            ->limit(1)
            ->execute($scope, $identifierHash, $timeBucket);

        if (!$objResult->numRows) {
            return 0;
        }

        return max(0, (int) $objResult->hitCount);
    }

    private function increaseRateLimitHitCount(string $identifierHash, int $timeBucket): void
    {
        $this->increaseRateLimitHitCountByScope(
            self::RATE_LIMIT_SCOPE_SCREEN_A_LOOKUP,
            $identifierHash,
            $timeBucket
        );
    }

    private function increaseRateLimitHitCountByScope(string $scope, string $identifierHash, int $timeBucket): void
    {
        $currentTimestamp = time();

        Database::getInstance()
            ->prepare(
                "INSERT INTO `tl_ls_shop_withdrawal_rate_limit`
                    (`tstamp`, `scope`, `identifierHash`, `timeBucket`, `hitCount`)
                 VALUES (?, ?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE
                    `hitCount` = `hitCount` + 1,
                    `tstamp` = ?"
            )
            ->execute(
                $currentTimestamp,
                $scope,
                $identifierHash,
                $timeBucket,
                $currentTimestamp
            );
    }

    private function cleanupRateLimitBuckets(int $currentBucket): void
    {
        $this->cleanupRateLimitBucketsByScope(
            self::RATE_LIMIT_SCOPE_SCREEN_A_LOOKUP,
            $currentBucket - 48
        );
    }

    private function cleanupRateLimitBucketsByScope(string $scope, int $cutoffBucket): void
    {
        Database::getInstance()
            ->prepare(
                "DELETE FROM `tl_ls_shop_withdrawal_rate_limit`
                 WHERE `scope` = ?
                   AND `timeBucket` < ?"
            )
            ->execute($scope, $cutoffBucket);
    }

    private function createRecipientEmailHash(string $emailAddress): string
    {
        $normalizedEmailAddress = trim(strtolower($emailAddress));
        if ($normalizedEmailAddress === '') {
            return '';
        }

        return hash('sha256', $normalizedEmailAddress);
    }

    private function findCanonicalWithdrawalIdentifier(string $normalizedIdentifier): ?string
    {
        if ($normalizedIdentifier === '') {
            return null;
        }

        $objResult = Database::getInstance()
            ->prepare(
                "SELECT `withdrawalIdentifier`
                 FROM `tl_ls_shop_orders`
                 WHERE REPLACE(UPPER(`withdrawalIdentifier`), '-', '') = ?"
            )
            ->limit(1)
            ->execute($normalizedIdentifier);

        if (!$objResult->numRows) {
            return null;
        }

        return (string) $objResult->withdrawalIdentifier;
    }

    private function redirectToScreenB(string $canonicalIdentifier): void
    {
        $withdrawalPage = ls_shop_languageHelper::getLanguagePage('ls_shop_withdrawalPages');

        if (!is_string($withdrawalPage) || $withdrawalPage === '') {
            $withdrawalPage = ls_shop_generalHelper::getUrl(false, ['wid', 'fallback']);
        }

        $separator = str_contains($withdrawalPage, '?') ? '&' : '?';
        $redirectUrl = $withdrawalPage . $separator . 'wid=' . rawurlencode($canonicalIdentifier);

        Controller::redirect($redirectUrl);
    }

    private function buildFallbackUrl(): string
    {
        $withdrawalPage = ls_shop_languageHelper::getLanguagePage('ls_shop_withdrawalPages');

        if (!is_string($withdrawalPage) || $withdrawalPage === '') {
            $withdrawalPage = ls_shop_generalHelper::getUrl(false, ['wid', 'fallback', 'testmode']);
        }

        $separator = str_contains($withdrawalPage, '?') ? '&' : '?';

        return $withdrawalPage . $separator . 'fallback=1';
    }

    private function redirectToScreenA(): void
    {
        Controller::redirect(ls_shop_generalHelper::getUrl(false, ['wid', 'fallback', 'testmode']));
    }

    /**
     * @param array<string, mixed> $arrOrder
     * @param array<int> $selectedItemIds
     * @param array<int, float> $withdrawnQuantities
     */
    private function persistWithdrawalFromScreenB(
        array $arrOrder,
        array $selectedItemIds,
        array $withdrawnQuantities,
        string $name,
        string $email
    ): void {
        if ($this->isCurrentRequestInTestmode()) {
            $this->redirectToConfirmation(
                WithdrawalTestmodeProcessor::PLACEHOLDER_WITHDRAWAL_ID,
                trim((string) Input::get('testmode'))
            );
            return;
        }

        $withdrawalId = (new ls_shop_checkout())->generateWithdrawalId();
        $withdrawalTimestamp = time();

        $processor = new WithdrawalScreenBProcessor();
        $parentSnapshot = $processor->buildParentSnapshot(
            $arrOrder,
            $withdrawalId,
            $name,
            $email,
            $withdrawalTimestamp
        );

        if (!$processor->hasCompleteParentSnapshot($parentSnapshot)) {
            return;
        }

        $objInsertedParent = Database::getInstance()
            ->prepare(
                "INSERT INTO `tl_ls_shop_withdrawal`
                    (`tstamp`, `withdrawalId`, `withdrawalTimestamp`, `name`, `email`, `orderReference`,
                     `snapshotOrderNr`, `snapshotOrderDate`, `snapshotBillingAddress`,
                     `snapshotShippingAddress`, `snapshotPaymentMethod`,
                     `snapshotPaymentMethod_customerLanguage`, `snapshotShippingMethod`,
                     `snapshotShippingMethod_customerLanguage`, `freetext`, `scenario`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )
            ->execute(
                $parentSnapshot['tstamp'],
                $parentSnapshot['withdrawalId'],
                $parentSnapshot['withdrawalTimestamp'],
                $parentSnapshot['name'],
                $parentSnapshot['email'],
                $parentSnapshot['orderReference'],
                $parentSnapshot['snapshotOrderNr'],
                $parentSnapshot['snapshotOrderDate'],
                $parentSnapshot['snapshotBillingAddress'],
                $parentSnapshot['snapshotShippingAddress'],
                $parentSnapshot['snapshotPaymentMethod'],
                $parentSnapshot['snapshotPaymentMethod_customerLanguage'],
                $parentSnapshot['snapshotShippingMethod'],
                $parentSnapshot['snapshotShippingMethod_customerLanguage'],
                $parentSnapshot['freetext'],
                $parentSnapshot['scenario']
            );

        $withdrawalDbId = (int) $objInsertedParent->insertId;
        $indexedOrderItems = $this->indexOrderItemsById($arrOrder['items'] ?? []);
        $childSnapshotsForMail = [];

        foreach ($selectedItemIds as $orderItemId) {
            if (!isset($indexedOrderItems[$orderItemId])) {
                continue;
            }

            $orderItem = $indexedOrderItems[$orderItemId];
            $internalWithdrawnQuantity = $processor->convertDisplayQuantityToInternalQuantity(
                $withdrawnQuantities[$orderItemId] ?? 0.0,
                $processor->getSalesUnitSize($orderItem)
            );
            $childSnapshot = $processor->buildChildSnapshot(
                $orderItem,
                $internalWithdrawnQuantity,
                $withdrawalTimestamp
            );

            Database::getInstance()
                ->prepare(
                    "INSERT INTO `tl_ls_shop_withdrawal_items`
                        (`pid`, `tstamp`, `orderItemReference`, `snapshotProductName`,
                         `snapshotProductName_customerLanguage`, `snapshotVariantTitle`,
                         `snapshotVariantTitle_customerLanguage`, `snapshotProductNumber`,
                         `snapshotUnitPrice`, `snapshotUnitPrice_customerLanguage`,
                         `snapshotQuantityUnit`, `snapshotQuantityUnit_customerLanguage`,
                         `snapshotOrderedQuantity`, `snapshotQuantityDecimals`, `snapshotSalesUnitSize`,
                         `snapshotConfiguratorReferenceNumber`, `snapshotCustomizerReferenceNumber`,
                         `withdrawnQuantity`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                )
                ->execute(
                    $withdrawalDbId,
                    $childSnapshot['tstamp'],
                    $childSnapshot['orderItemReference'],
                    $childSnapshot['snapshotProductName'],
                    $childSnapshot['snapshotProductName_customerLanguage'],
                    $childSnapshot['snapshotVariantTitle'],
                    $childSnapshot['snapshotVariantTitle_customerLanguage'],
                    $childSnapshot['snapshotProductNumber'],
                    $childSnapshot['snapshotUnitPrice'],
                    $childSnapshot['snapshotUnitPrice_customerLanguage'],
                    $childSnapshot['snapshotQuantityUnit'],
                    $childSnapshot['snapshotQuantityUnit_customerLanguage'],
                    $childSnapshot['snapshotOrderedQuantity'],
                    $childSnapshot['snapshotQuantityDecimals'],
                    $childSnapshot['snapshotSalesUnitSize'],
                    $childSnapshot['snapshotConfiguratorReferenceNumber'],
                    $childSnapshot['snapshotCustomizerReferenceNumber'],
                    $childSnapshot['withdrawnQuantity']
                );

            $childSnapshot['pid'] = $withdrawalDbId;
            $childSnapshotsForMail[] = $childSnapshot;
        }

        $arrWithdrawalForMail = $parentSnapshot;
        $arrWithdrawalForMail['id'] = $withdrawalDbId;
        $arrWithdrawalForMail['items'] = $childSnapshotsForMail;

        $this->sendWithdrawalMessages($arrOrder, $arrWithdrawalForMail);
        $this->redirectToConfirmation($withdrawalDbId);
    }

    private function persistWithdrawalFromScreenC(string $name, string $email, string $freetext): void
    {
        $withdrawalId = (new ls_shop_checkout())->generateWithdrawalId();
        $withdrawalTimestamp = time();

        $objInsertedParent = Database::getInstance()
            ->prepare(
                "INSERT INTO `tl_ls_shop_withdrawal`
                    (`tstamp`, `withdrawalId`, `withdrawalTimestamp`, `name`, `email`, `orderReference`,
                     `snapshotOrderNr`, `snapshotOrderDate`, `snapshotBillingAddress`,
                     `snapshotShippingAddress`, `snapshotPaymentMethod`, `snapshotShippingMethod`,
                     `freetext`, `scenario`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )
            ->execute(
                $withdrawalTimestamp,
                $withdrawalId,
                $withdrawalTimestamp,
                $name,
                $email,
                0,
                '',
                '',
                '',
                '',
                '',
                '',
                $freetext,
                '2'
            );

        $withdrawalDbId = (int) $objInsertedParent->insertId;
        $arrWithdrawalForMail = [
            'id' => $withdrawalDbId,
            'tstamp' => $withdrawalTimestamp,
            'withdrawalId' => $withdrawalId,
            'withdrawalTimestamp' => $withdrawalTimestamp,
            'name' => $name,
            'email' => $email,
            'orderReference' => 0,
            'snapshotOrderNr' => '',
            'snapshotOrderDate' => '',
            'snapshotBillingAddress' => '',
            'snapshotShippingAddress' => '',
            'snapshotPaymentMethod' => '',
            'snapshotShippingMethod' => '',
            'freetext' => $freetext,
            'scenario' => '2',
            'items' => [],
        ];

        $this->sendWithdrawalMessages(null, $arrWithdrawalForMail);
        $this->redirectToConfirmation($withdrawalDbId);
    }

    /**
     * @param array<string, mixed> $arrOrder
     * @param array<string, mixed> $arrWithdrawal
     */
    private function sendWithdrawalMessages(?array $arrOrder, array $arrWithdrawal): void
    {
        $arrMailLanguages = $this->determineWithdrawalMailLanguages($arrOrder);
        $customerLanguage = $arrMailLanguages['customerLanguage'];
        $fallbackLanguage = $arrMailLanguages['fallbackLanguage'];
        $orderId = isset($arrOrder['id']) ? (int) $arrOrder['id'] : null;

        try {
            (new ls_shop_orderMessages(
                $orderId,
                'asWithdrawalConfirmation',
                'sendWhen',
                $customerLanguage,
                true,
                null,
                null,
                $arrWithdrawal
            ))->sendMessages();
        } catch (\Throwable $throwable) {
            $this->logWithdrawalMailError('customer', (string) ($arrWithdrawal['withdrawalId'] ?? 'n/a'), $throwable->getMessage());
        }

        try {
            (new ls_shop_orderMessages(
                $orderId,
                'asWithdrawalNotice',
                'sendWhen',
                $fallbackLanguage,
                true,
                null,
                null,
                $arrWithdrawal
            ))->sendMessages();
        } catch (\Throwable $throwable) {
            $this->logWithdrawalMailError('merchant', (string) ($arrWithdrawal['withdrawalId'] ?? 'n/a'), $throwable->getMessage());
        }
    }

    /**
     * @param array<string, mixed>|null $arrOrder
     * @return array{customerLanguage: string, fallbackLanguage: string}
     */
    private function determineWithdrawalMailLanguages(?array $arrOrder): array
    {
        $fallbackLanguage = (string) ls_shop_languageHelper::getFallbackLanguage();
        if ($fallbackLanguage === '') {
            $fallbackLanguage = 'en';
        }

        $customerLanguage = (string) ($arrOrder['customerLanguage'] ?? $GLOBALS['TL_LANGUAGE'] ?? $fallbackLanguage);
        if ($customerLanguage === '') {
            $customerLanguage = $fallbackLanguage;
        }

        return [
            'customerLanguage' => $customerLanguage,
            'fallbackLanguage' => $fallbackLanguage,
        ];
    }

    private function logWithdrawalMailError(string $recipientType, string $withdrawalId, string $message): void
    {
        System::getContainer()->get('monolog.logger.contao')->info(
            'MERCONIS: withdrawal mail could not be sent (' . $recipientType . ', withdrawal ' . $withdrawalId . '): ' . $message,
            ['contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_MESSAGES)]
        );
    }

    private function redirectToConfirmation(string|int $withdrawalReference, string $testmodeOrderIdentificationHash = ''): void
    {
        $confirmationPage = ls_shop_languageHelper::getLanguagePage('ls_shop_withdrawalConfirmationPages');
        if (!is_string($confirmationPage) || $confirmationPage === '') {
            $confirmationPage = ls_shop_generalHelper::getUrl(false, ['fallback', 'testmode']);
        }

        $separator = str_contains($confirmationPage, '?') ? '&' : '?';
        $signedReference = $this->createSignedWithdrawalReference($withdrawalReference);
        $redirectUrl = $confirmationPage . $separator . 'wrt=' . rawurlencode($signedReference);

        if ($testmodeOrderIdentificationHash !== '') {
            $redirectUrl .= '&testmode=' . rawurlencode($testmodeOrderIdentificationHash);
        }

        Controller::redirect($redirectUrl);
    }

    private function createSignedWithdrawalReference(string|int $withdrawalReference): string
    {
        $issuedAt = time();
        $secret = (string) System::getContainer()->getParameter('kernel.secret');

        return (new WithdrawalConfirmationTokenProcessor())
            ->createToken($withdrawalReference, $issuedAt, $secret);
    }

    /**
     * @return ?array<string, mixed>
     */
    private function resolveOrderForScreenBRequest(?string $submittedWithdrawalIdentifier = null): ?array
    {
        $testmodeProcessor = new WithdrawalTestmodeProcessor();
        $testmodeOrderIdentificationHash = (string) Input::get('testmode');

        if ($testmodeProcessor->hasTestmodeParameter($testmodeOrderIdentificationHash)) {
            return $testmodeProcessor->resolveOrder(
                $testmodeOrderIdentificationHash,
                fn (string $orderIdentificationHash): ?array => $this->resolveOrderByTestmodeIdentifier($orderIdentificationHash)
            );
        }

        $withdrawalIdentifier = trim((string) ($submittedWithdrawalIdentifier ?? ''));
        if ($withdrawalIdentifier === '') {
            $withdrawalIdentifier = trim((string) Input::get('wid'));
        }

        return $this->resolveOrderByWithdrawalIdentifier($withdrawalIdentifier);
    }

    private function isCurrentRequestInTestmode(): bool
    {
        return (new WithdrawalTestmodeProcessor())->hasTestmodeParameter((string) Input::get('testmode'));
    }

    /**
     * @return ?array<string, mixed>
     */
    private function resolveOrderByTestmodeIdentifier(string $orderIdentificationHash): ?array
    {
        $arrOrder = ls_shop_generalHelper::getOrder(trim($orderIdentificationHash), 'orderIdentificationHash');

        if (!is_array($arrOrder) || !count($arrOrder)) {
            return null;
        }

        return $arrOrder;
    }

    /**
     * @return ?array<string, mixed>
     */
    private function resolveOrderByWithdrawalIdentifier(string $identifier): ?array
    {
        $normalizedIdentifier = $this->normalizeWithdrawalIdentifier($identifier);
        if ($normalizedIdentifier === '') {
            return null;
        }

        $objResult = Database::getInstance()
            ->prepare(
                "SELECT `id`, `withdrawalIdentifier`
                 FROM `tl_ls_shop_orders`
                 WHERE REPLACE(UPPER(`withdrawalIdentifier`), '-', '') = ?"
            )
            ->limit(1)
            ->execute($normalizedIdentifier);

        if (!$objResult->numRows) {
            return null;
        }

        $orderId = (int) $objResult->id;
        $arrOrder = ls_shop_generalHelper::getOrder($orderId);

        if (!is_array($arrOrder)) {
            return null;
        }

        $arrOrder['id'] = $orderId;
        $arrOrder['withdrawalIdentifier'] = (string) $objResult->withdrawalIdentifier;

        return $arrOrder;
    }

    private function normalizeWithdrawalIdentifier(string $identifier): string
    {
        return strtoupper(str_replace('-', '', trim($identifier)));
    }

    /**
     * @param array<int, mixed> $orderItems
     * @return array<int, array<string, mixed>>
     */
    private function indexOrderItemsById(array $orderItems): array
    {
        $indexedItems = [];
        $fallbackId = 1;

        foreach ($orderItems as $orderItem) {
            if (!is_array($orderItem)) {
                continue;
            }

            $orderItemId = (int) ($orderItem['id'] ?? 0);
            if ($orderItemId <= 0) {
                $orderItemId = $fallbackId;
            }

            $indexedItems[$orderItemId] = $orderItem;
            $fallbackId++;
        }

        return $indexedItems;
    }

    /**
     * @return array<int>
     */
    private function parseSelectedOrderItemIds(): array
    {
        $selectedItems = Input::post('selectedItems');
        if (!is_array($selectedItems)) {
            return [];
        }

        $selectedItemIds = [];
        foreach ($selectedItems as $orderItemId => $selectedValue) {
            if ((string) $selectedValue !== '1') {
                continue;
            }

            $selectedItemIds[] = (int) $orderItemId;
        }

        return $selectedItemIds;
    }

    /**
     * @return array<int, float>
     */
    private function parseSubmittedQuantities(): array
    {
        $quantities = Input::post('withdrawalQuantity');
        if (!is_array($quantities)) {
            return [];
        }

        $result = [];
        foreach ($quantities as $orderItemId => $quantityValue) {
            $result[(int) $orderItemId] = $this->toFloat($quantityValue);
        }

        return $result;
    }

    /**
     * @param array<int> $selectedItemIds
     * @param array<int, float> $withdrawnQuantities
     * @param array<int, array<string, mixed>> $indexedOrderItems
     * @return array<int, string>
     */
    private function validateScreenBForm(
        array $selectedItemIds,
        array $withdrawnQuantities,
        array $indexedOrderItems,
        string $name,
        string $email
    ): array {
        $errorMessages = [];
        $processor = new WithdrawalScreenBProcessor();

        if (count($selectedItemIds) === 0) {
            $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_no_items'];
        }

        foreach ($selectedItemIds as $selectedItemId) {
            if (!isset($indexedOrderItems[$selectedItemId])) {
                $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_invalid_quantity'];
                continue;
            }

            $orderedQuantity = $this->toFloat($indexedOrderItems[$selectedItemId]['quantity'] ?? 0);
            $salesUnitSize = $processor->getSalesUnitSize($indexedOrderItems[$selectedItemId]);
            $orderedQuantity = $processor->getOrderedDisplayQuantity($indexedOrderItems[$selectedItemId]);
            $withdrawnQuantity = $withdrawnQuantities[$selectedItemId] ?? 0.0;
            $quantityDecimals = $this->toQuantityDecimals($indexedOrderItems[$selectedItemId]['quantityDecimals'] ?? 0);

            if (!$processor->isValidWithdrawnQuantity(
                $withdrawnQuantity,
                $orderedQuantity,
                $processor->getDisplayMinimumQuantity($salesUnitSize, $quantityDecimals),
                $quantityDecimals,
                $salesUnitSize
            )) {
                $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_invalid_quantity'];
            }
        }

        if ($name === '') {
            $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_name_required'];
        }

        if (!Validator::isEmail($email)) {
            $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_email_invalid'];
        }

        return array_values(array_unique($errorMessages));
    }

    /**
     * @return array<int, string>
     */
    private function validateScreenCForm(string $name, string $email, string $freetext): array
    {
        $errorMessages = [];

        if ($name === '') {
            $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_name_required'];
        }

        if (!Validator::isEmail($email)) {
            $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_form_error_email_invalid'];
        }

        if ($freetext === '') {
            $errorMessages[] = (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_fallback_error_freetext_required'];
        }

        return array_values(array_unique($errorMessages));
    }

    private function logScreenCRateLimitExceeded(string $clientIpHash, string $recipientEmailHash): void
    {
        System::getContainer()->get('monolog.logger.contao')->info(
            'MERCONIS: withdrawal screen C rate limit exceeded (ip hash: '
            . $clientIpHash
            . ', recipient hash: '
            . ($recipientEmailHash !== '' ? $recipientEmailHash : 'n/a')
            . ')',
            ['contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_MESSAGES)]
        );
    }

    private function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $normalizedValue = str_replace(',', '.', trim($value));
        if ($normalizedValue === '' || !is_numeric($normalizedValue)) {
            return 0.0;
        }

        return (float) $normalizedValue;
    }

    private function toQuantityDecimals(mixed $value): int
    {
        return max(0, (int) $value);
    }

    private function formatQuantityForInput(float $quantity): string
    {
        $formatted = number_format($quantity, 4, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    private function formatUnitPriceDisplay(mixed $priceValue, string $quantityUnit): string
    {
        $formattedPrice = ls_shop_generalHelper::outputPrice($this->toFloat($priceValue));
        if ($quantityUnit === '') {
            return $formattedPrice;
        }

        return $formattedPrice . '/' . $quantityUnit;
    }

    /**
     * @param array<string, mixed> $orderItem
     * @param array<int, string> $customerLanguageKeys
     */
    private function resolveOrderItemCustomerLanguageValue(
        array $orderItem,
        array $customerLanguageKeys,
        string $fallbackKey
    ): string {
        $extendedInfo = is_array($orderItem['extendedInfo'] ?? null) ? $orderItem['extendedInfo'] : [];

        return $this->resolveScalarValueWithFallback($extendedInfo, $customerLanguageKeys, $fallbackKey, $orderItem);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<int, string> $preferredKeys
     * @param array<string, mixed>|null $fallbackValues
     */
    private function resolveScalarValueWithFallback(
        array $values,
        array $preferredKeys,
        string $fallbackKey,
        ?array $fallbackValues = null
    ): string {
        foreach ($preferredKeys as $preferredKey) {
            if (!array_key_exists($preferredKey, $values) || $values[$preferredKey] === null) {
                continue;
            }

            return (string) $values[$preferredKey];
        }

        $fallbackValues ??= $values;
        if (!array_key_exists($fallbackKey, $fallbackValues) || $fallbackValues[$fallbackKey] === null) {
            return '';
        }

        return (string) $fallbackValues[$fallbackKey];
    }

    private function renderQuantityDisplay(float $withdrawnQuantity, float $orderedQuantity, string $quantityUnit): string
    {
        $orderedQuantityOutput = $this->formatQuantityForInput($orderedQuantity);
        $withdrawnQuantityOutput = $this->formatQuantityForInput($withdrawnQuantity);
        $quantityUnitSuffix = $quantityUnit !== '' ? ' ' . $quantityUnit : '';

        $isPartialWithdrawal = abs($withdrawnQuantity - $orderedQuantity) > 0.0001;
        if (!$isPartialWithdrawal) {
            return StringUtil::specialchars($orderedQuantityOutput . $quantityUnitSuffix);
        }

        $partialQuantityLabel = sprintf(
            (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_items_quantity_partial'],
            $withdrawnQuantityOutput,
            $orderedQuantityOutput
        );

        return StringUtil::specialchars($partialQuantityLabel . $quantityUnitSuffix);
    }

    private function getFailedAttemptsFromSession(): int
    {
        $failedAttempts = $this->readNestedSessionValue(self::SESSION_KEY_FAILED_ATTEMPTS);

        return is_int($failedAttempts) ? max(0, $failedAttempts) : 0;
    }

    private function storeFailedAttemptsInSession(int $failedAttempts): void
    {
        $this->writeNestedSessionValue(self::SESSION_KEY_FAILED_ATTEMPTS, max(0, $failedAttempts));
    }

    /**
     * @return mixed
     */
    private function readNestedSessionValue(string $path)
    {
        $pathParts = explode('.', $path);
        $sessionValue = $_SESSION;

        foreach ($pathParts as $pathPart) {
            if (!is_array($sessionValue) || !array_key_exists($pathPart, $sessionValue)) {
                return null;
            }

            $sessionValue = $sessionValue[$pathPart];
        }

        return $sessionValue;
    }

    /**
     * @param mixed $value
     */
    private function writeNestedSessionValue(string $path, $value): void
    {
        $pathParts = explode('.', $path);
        $sessionReference = &$_SESSION;

        foreach ($pathParts as $pathPart) {
            if (!isset($sessionReference[$pathPart]) || !is_array($sessionReference[$pathPart])) {
                $sessionReference[$pathPart] = [];
            }

            $sessionReference = &$sessionReference[$pathPart];
        }

        $sessionReference = $value;
    }
}
