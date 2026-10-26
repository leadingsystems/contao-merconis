<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\Database;
use Contao\FrontendUser;
use Contao\StringUtil;
use Contao\System;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Support\StoredSepaMandateFormatter;
use Merconis\Core\ls_shop_paymentModule_standard;

final class EInvoicingStandardPaymentModule extends ls_shop_paymentModule_standard
{
    public array $arrCurrentSettings = [];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $frontendMemberRecord = null;

    public function statusOkayToShowAdditionalDataForm(): bool
    {
        if ($this->usesStoredSepaMandate()) {
            return false;
        }

        return parent::statusOkayToShowAdditionalDataForm();
    }

    public function statusOkayToShowCustomUserInterface(): bool
    {
        if ($this->usesStoredSepaMandate()) {
            return true;
        }

        return parent::statusOkayToShowCustomUserInterface();
    }

    public function getFormIDForAdditionalData($formID)
    {
        if ($this->usesStoredSepaMandate()) {
            return 0;
        }

        return parent::getFormIDForAdditionalData($formID);
    }

    public function getCustomUserInterface()
    {
        if (!$this->usesStoredSepaMandate()) {
            return parent::getCustomUserInterface();
        }

        \Contao\Controller::loadLanguageFile('default');

        $memberRecord = $this->loadFrontendMemberRecord();

        $heading = $this->escapeHtml($this->translate(
            'einvoicing_sepa_mandate_heading',
            'Hinterlegtes SEPA-Mandat'
        ));
        $accountHolderLabel = $this->escapeHtml($this->translate(
            'einvoicing_sepa_mandate_account_holder_label',
            'Kontoinhaber'
        ));
        $ibanLabel = $this->escapeHtml($this->translate(
            'einvoicing_sepa_mandate_iban_label',
            'IBAN'
        ));
        $mandateReferenceLabel = $this->escapeHtml($this->translate(
            'einvoicing_sepa_mandate_reference_label',
            'Mandatsreferenz'
        ));
        $changeNotice = $this->escapeHtml($this->translate(
            'einvoicing_sepa_mandate_change_notice',
            'Wenn Sie ein anderes Konto verwenden möchten, wählen Sie bitte eine andere Zahlungsmethode oder klären Sie die Änderung außerhalb der Bestellung.'
        ));
        $fallbackValue = $this->translate(
            'einvoicing_sepa_mandate_value_unavailable',
            'nicht hinterlegt'
        );

        $accountHolder = $this->escapeHtml(
            StoredSepaMandateFormatter::resolveAccountHolder($memberRecord) ?? $fallbackValue
        );
        $maskedIban = $this->escapeHtml(
            StoredSepaMandateFormatter::maskIban(
                $memberRecord[EInvoicingSnapshotFields::MEMBER_DEBTOR_IBAN_FIELD] ?? null
            ) ?? $fallbackValue
        );
        $mandateReference = $this->escapeHtml(
            $this->optionalString(
                $memberRecord[EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD]
                ?? null
            ) ?? $fallbackValue
        );

        return '<div class="lsShopStoredSepaMandate">'
            . '<p class="lsShopStoredSepaMandate__headline">' . $heading . '</p>'
            . '<p class="lsShopStoredSepaMandate__row"><span class="lsShopStoredSepaMandate__label">' . $accountHolderLabel . ':</span> <span class="lsShopStoredSepaMandate__value">' . $accountHolder . '</span></p>'
            . '<p class="lsShopStoredSepaMandate__row"><span class="lsShopStoredSepaMandate__label">' . $ibanLabel . ':</span> <span class="lsShopStoredSepaMandate__value">' . $maskedIban . '</span></p>'
            . '<p class="lsShopStoredSepaMandate__row"><span class="lsShopStoredSepaMandate__label">' . $mandateReferenceLabel . ':</span> <span class="lsShopStoredSepaMandate__value">' . $mandateReference . '</span></p>'
            . '<p class="lsShopStoredSepaMandate__hint">' . $changeNotice . '</p>'
            . '</div>';
    }

    private function usesStoredSepaMandate(): bool
    {
        if (!$this->usesSepaDirectDebit()) {
            return false;
        }

        return StoredSepaMandateFormatter::hasStoredMandate($this->loadFrontendMemberRecord());
    }

    private function usesSepaDirectDebit(): bool
    {
        return EInvoicingCodeLists::normalizeCode(
            $this->arrCurrentSettings[EInvoicingSnapshotFields::PAYMENT_METHOD_MEANS_CODE_FIELD] ?? null
        ) === '59';
    }

    /**
     * @return array<string, mixed>
     */
    private function loadFrontendMemberRecord(): array
    {
        if ($this->frontendMemberRecord !== null) {
            return $this->frontendMemberRecord;
        }

        if (!System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
            $this->frontendMemberRecord = [];

            return $this->frontendMemberRecord;
        }

        $frontendUser = FrontendUser::getInstance();
        $memberId = (int) ($frontendUser->id ?? 0);

        if ($memberId <= 0) {
            $this->frontendMemberRecord = [];

            return $this->frontendMemberRecord;
        }

        $memberRecord = Database::getInstance()
            ->prepare('SELECT * FROM `tl_member` WHERE `id` = ?')
            ->limit(1)
            ->execute($memberId)
            ->row();

        $this->frontendMemberRecord = is_array($memberRecord) ? $memberRecord : [];

        return $this->frontendMemberRecord;
    }

    private function translate(string $key, string $fallback): string
    {
        return $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis'][$key] ?? $fallback;
    }

    private function escapeHtml(string $value): string
    {
        return StringUtil::specialchars($value);
    }

    private function optionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
