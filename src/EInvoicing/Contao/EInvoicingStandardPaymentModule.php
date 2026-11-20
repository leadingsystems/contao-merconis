<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Contao;

use Contao\Database;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\System;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingSnapshotFields;
use LeadingSystems\MerconisBundle\EInvoicing\Support\StoredSepaMandateFormatter;
use Merconis\Core\ls_shop_paymentModule_standard;

final class EInvoicingStandardPaymentModule extends ls_shop_paymentModule_standard
{
    public const STORED_SEPA_MANDATE_TEMPLATE = 'template_einvoicingStoredSepaMandate_default';

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

        $storedSepaMandateTemplate = new FrontendTemplate(self::STORED_SEPA_MANDATE_TEMPLATE);
        $storedSepaMandateTemplate->headline = $this->translate('einvoicing_sepa_mandate_heading');
        $storedSepaMandateTemplate->accountHolderLabel = $this->translate('einvoicing_sepa_mandate_account_holder_label');
        $storedSepaMandateTemplate->ibanLabel = $this->translate('einvoicing_sepa_mandate_iban_label');
        $storedSepaMandateTemplate->mandateReferenceLabel = $this->translate('einvoicing_sepa_mandate_reference_label');
        $storedSepaMandateTemplate->changeNotice = $this->translate('einvoicing_sepa_mandate_change_notice');
        $storedSepaMandateTemplate->valueUnavailableText = $this->translate('einvoicing_sepa_mandate_value_unavailable');
        $storedSepaMandateTemplate->accountHolder = StoredSepaMandateFormatter::resolveAccountHolder($memberRecord);
        $storedSepaMandateTemplate->maskedIban = StoredSepaMandateFormatter::maskIban(
            $memberRecord[EInvoicingSnapshotFields::MEMBER_DEBTOR_IBAN_FIELD] ?? null
        );
        $storedSepaMandateTemplate->mandateReference = $this->optionalString(
            $memberRecord[EInvoicingSnapshotFields::MEMBER_MANDATE_REFERENCE_FIELD] ?? null
        );

        return $storedSepaMandateTemplate->parse();
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
        ) === EInvoicingCodeLists::SEPA_DIRECT_DEBIT_CODE;
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

    private function translate(string $key): string
    {
        return $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis'][$key];
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
