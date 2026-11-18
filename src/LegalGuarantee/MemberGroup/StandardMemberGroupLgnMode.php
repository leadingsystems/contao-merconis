<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup;

use Closure;
use Merconis\Core\ls_shop_generalHelper;

/**
 * Bezug der Regeln R1 bis R4 (Spec 27, Abschnitt 4): der gespeicherte Wert `lsShopLgnMode`
 * der Standard-Benutzergruppe und die Entscheidung "LGN aus" für einen `lgnMode`-Wert.
 * Die Entscheidung kommt ohne Contao-Kontext aus; nur das Lesen des Gruppenwerts braucht ihn.
 */
final class StandardMemberGroupLgnMode
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
     * Liefert den gespeicherten Wert `lsShopLgnMode` der Gruppe aus
     * `$GLOBALS['TL_CONFIG']['ls_shop_standardGroup']`. Gelesen wird über
     * `getGroupSettings()`, nicht über `getGroupSettings4User()`; der Hook
     * `getGroupSettingsForUser` wirkt deshalb nicht. Fehlt die Standard-Benutzergruppe,
     * die Gruppe oder der Wert, ist das Ergebnis `''`; `NULL` wird `''`.
     */
    public function getStoredLgnMode(): string
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

        return trim((string) ($groupSettings['lsShopLgnMode'] ?? ''));
    }

    /**
     * Entscheidet "LGN aus" für einen `lgnMode`-Wert mit dem gespeicherten Wert der
     * Standard-Benutzergruppe.
     */
    public function isLgnOffForProduct(mixed $productLgnMode): bool
    {
        return $this->isLgnOff($this->getStoredLgnMode(), $productLgnMode);
    }

    /**
     * Tabelle aus Spec 27, Abschnitt 4: Gruppe `off` bei `inherit` oder `never`; jeder andere
     * Gruppenwert (`on`, `blocked`, leer) nur bei `never`.
     */
    public function isLgnOff(string $standardMemberGroupLgnMode, mixed $productLgnMode): bool
    {
        $normalizedProductLgnMode = $this->normalizeProductLgnMode($productLgnMode);

        if ('off' === trim($standardMemberGroupLgnMode)) {
            return in_array($normalizedProductLgnMode, ['inherit', 'never'], true);
        }

        return 'never' === $normalizedProductLgnMode;
    }

    /**
     * Wert, den R1 für `lgnMode` setzt: `always` bei Standard-Benutzergruppe `off`, sonst
     * `inherit`.
     */
    public function getLgnModeForEnabledGaran(string $standardMemberGroupLgnMode): string
    {
        return 'off' === trim($standardMemberGroupLgnMode) ? 'always' : 'inherit';
    }

    /**
     * Ein fehlender oder leerer Produktwert gilt als `inherit` (Spec 27, Abschnitt 10).
     */
    public function normalizeProductLgnMode(mixed $productLgnMode): string
    {
        $normalizedProductLgnMode = trim((string) $productLgnMode);

        return '' === $normalizedProductLgnMode ? 'inherit' : $normalizedProductLgnMode;
    }
}
