<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup;

interface MemberGroupGllModeProviderInterface
{
    /**
     * Liefert den Wert `lsShopGllMode` der maßgeblichen Mitgliedergruppe des aktuellen
     * Besuchers beziehungsweise Käufers. Ein nicht festgelegter Wert ist `''`.
     */
    public function getCurrentMemberGroupGllMode(): string;
}
