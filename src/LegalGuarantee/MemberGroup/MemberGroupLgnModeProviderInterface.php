<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup;

interface MemberGroupLgnModeProviderInterface
{
    /**
     * Liefert den Wert `lsShopLgnMode` der maßgeblichen Mitgliedergruppe des aktuellen
     * Besuchers beziehungsweise Käufers. Ein nicht festgelegter Wert ist `''`.
     */
    public function getCurrentMemberGroupLgnMode(): string;
}
