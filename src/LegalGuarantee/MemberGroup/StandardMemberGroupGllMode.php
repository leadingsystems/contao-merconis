<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup;

use Closure;
use Merconis\Core\ls_shop_generalHelper;

/**
 * Bezug der Regeln R1 bis R4 (Spec 27, Abschnitt 4): der gespeicherte Wert `lsShopGllMode`
 * der Standard-Mitgliedergruppe und die Entscheidung "GLL aus" für einen `gllMode`-Wert.
 * Die Entscheidung kommt ohne Contao-Kontext aus; nur das Lesen des Gruppenwerts braucht ihn.
 */
final class StandardMemberGroupGllMode
{
    /**
     * @param Closure(int): mixed|null $groupSettingsReader Liest die Einstellungen der Gruppe
     *                                                      mit der übergebenen ID; ohne Angabe
     *                                                      `ls_shop_generalHelper::getGroupSettings()`
     */
    public function __construct(
        private readonly ?Closure $groupSettingsReader = null,
    ) {
    }

    /**
     * Liefert den gespeicherten Wert `lsShopGllMode` der Gruppe aus
     * `$GLOBALS['TL_CONFIG']['ls_shop_standardGroup']`. Gelesen wird über
     * `getGroupSettings()`, nicht über `getGroupSettings4User()`; der Hook
     * `getGroupSettingsForUser` wirkt deshalb nicht. Fehlt die Standard-Mitgliedergruppe,
     * die Gruppe oder der Wert, ist das Ergebnis `''`; `NULL` wird `''`.
     */
    public function getStoredGllMode(): string
    {
        $standardMemberGroupId = (int) ($GLOBALS['TL_CONFIG']['ls_shop_standardGroup'] ?? 0);

        if ($standardMemberGroupId <= 0) {
            return '';
        }

        $groupSettingsReader = $this->groupSettingsReader ?? ls_shop_generalHelper::getGroupSettings(...);
        $groupSettings = $groupSettingsReader($standardMemberGroupId);

        if (!is_array($groupSettings)) {
            return '';
        }

        return trim((string) ($groupSettings['lsShopGllMode'] ?? ''));
    }

    /**
     * Entscheidet "GLL aus" für einen `gllMode`-Wert mit dem gespeicherten Wert der
     * Standard-Mitgliedergruppe.
     */
    public function isGllOffForProduct(mixed $productGllMode): bool
    {
        return $this->isGllOff($this->getStoredGllMode(), $productGllMode);
    }

    /**
     * Tabelle aus Spec 27, Abschnitt 4: Gruppe `off` bei `inherit` oder `never`; jeder andere
     * Gruppenwert (`on`, `blocked`, leer) nur bei `never`.
     */
    public function isGllOff(string $standardMemberGroupGllMode, mixed $productGllMode): bool
    {
        $normalizedProductGllMode = $this->normalizeProductGllMode($productGllMode);

        if ('off' === trim($standardMemberGroupGllMode)) {
            return in_array($normalizedProductGllMode, ['inherit', 'never'], true);
        }

        return 'never' === $normalizedProductGllMode;
    }

    /**
     * Wert, den R1 für `gllMode` setzt: `always` bei Standard-Mitgliedergruppe `off`, sonst
     * `inherit`.
     */
    public function getGllModeForEnabledGaran(string $standardMemberGroupGllMode): string
    {
        return 'off' === trim($standardMemberGroupGllMode) ? 'always' : 'inherit';
    }

    /**
     * Ein fehlender oder leerer Produktwert gilt als `inherit` (Spec 27, Abschnitt 10).
     */
    public function normalizeProductGllMode(mixed $productGllMode): string
    {
        $normalizedProductGllMode = trim((string) $productGllMode);

        return '' === $normalizedProductGllMode ? 'inherit' : $normalizedProductGllMode;
    }
}
