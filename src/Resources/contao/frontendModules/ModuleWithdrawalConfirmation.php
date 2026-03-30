<?php
declare(strict_types=1);

namespace Merconis\Core;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Database;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use LeadingSystems\MerconisBundle\Helpers\WithdrawalConfirmationTokenProcessor;

class ModuleWithdrawalConfirmation extends Module
{
    protected $strTemplate = 'mod_ls_shop_withdrawal_confirmation';

    public function generate()
    {
        if (System::getContainer()->get('merconis.routing.scope')->isBackend()) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### MERCONIS Widerrufsbestätigung ###';

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $tokenValue = (string) Input::get('wrt');
        $tokenProcessor = new WithdrawalConfirmationTokenProcessor();
        $secret = (string) System::getContainer()->getParameter('kernel.secret');

        $validationResult = $tokenProcessor->validateToken(
            $tokenValue,
            $secret,
            time(),
            3600
        );

        if (($validationResult['status'] ?? '') !== WithdrawalConfirmationTokenProcessor::STATUS_SUCCESS) {
            $this->redirectToScreenA();
        }

        $withdrawalPrimaryKey = (int) ($validationResult['primaryKey'] ?? 0);
        $withdrawalRecord = $this->findWithdrawalRecordByPrimaryKey($withdrawalPrimaryKey);

        if ($withdrawalRecord === null) {
            $this->redirectToScreenA();
        }

        $this->Template = new FrontendTemplate($this->strTemplate);
        $this->Template->withdrawalIdHeading = sprintf(
            (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_confirmation_id_heading'],
            StringUtil::specialchars((string) ($withdrawalRecord['withdrawalId'] ?? ''))
        );
        $this->Template->withdrawalId = (string) ($withdrawalRecord['withdrawalId'] ?? '');
        $this->Template->email = (string) ($withdrawalRecord['email'] ?? '');
        $this->Template->confirmationText = $this->resolveConfirmationText($withdrawalRecord);
    }

    private function redirectToScreenA(): void
    {
        $withdrawalPage = ls_shop_languageHelper::getLanguagePage('ls_shop_withdrawalPages');
        if (!is_string($withdrawalPage) || $withdrawalPage === '') {
            $withdrawalPage = ls_shop_generalHelper::getUrl(false, ['wrt']);
        }

        Controller::redirect($withdrawalPage);
    }

    /**
     * @return ?array<string, mixed>
     */
    private function findWithdrawalRecordByPrimaryKey(int $withdrawalPrimaryKey): ?array
    {
        if ($withdrawalPrimaryKey <= 0) {
            return null;
        }

        $objResult = Database::getInstance()
            ->prepare(
                "SELECT `id`, `withdrawalId`, `email`
                 FROM `tl_ls_shop_withdrawal`
                 WHERE `id` = ?"
            )
            ->limit(1)
            ->execute($withdrawalPrimaryKey);

        if (!$objResult->numRows) {
            return null;
        }

        return $objResult->row();
    }

    /**
     * @param array<string, mixed> $withdrawalRecord
     */
    private function resolveConfirmationText(array $withdrawalRecord): string
    {
        $configuredConfirmationText = trim((string) ($this->ls_shop_withdrawalConfirmationText ?? ''));
        if ($configuredConfirmationText !== '') {
            return StringUtil::decodeEntities($configuredConfirmationText);
        }

        $fallbackText = sprintf(
            (string) $GLOBALS['TL_LANG']['MSC']['ls_contao-merconis']['withdrawal_confirmation_text'],
            StringUtil::specialchars((string) ($withdrawalRecord['withdrawalId'] ?? '')),
            StringUtil::specialchars((string) ($withdrawalRecord['email'] ?? ''))
        );

        return nl2br($fallbackText);
    }
}
