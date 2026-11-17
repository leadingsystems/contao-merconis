<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup;

use Merconis\Core\ls_shop_generalHelper;

final class CurrentMemberGroupGllModeProvider implements MemberGroupGllModeProviderInterface
{
    /**
     * Liest die Gruppeneinstellungen über `getGroupSettings4User()`, damit der Hook
     * `getGroupSettingsForUser` auf die Ausgabe wirkt. Fehlt die Gruppe oder der Wert,
     * ist das Ergebnis `''`; `NULL` wird `''`.
     */
    public function getCurrentMemberGroupGllMode(): string
    {
        $groupSettings = ls_shop_generalHelper::getGroupSettings4User();

        if (!is_array($groupSettings)) {
            return '';
        }

        return trim((string) ($groupSettings['lsShopGllMode'] ?? ''));
    }
}
