<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\Config;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;

/**
 * Migriert den bisherigen Konfigurationswert `ls_shop_serial` nach `merconis_licenseKey`.
 *
 * Wichtig: Der neue Wert akzeptiert Dual-Format (signierter License Key oder Legacy-Seriennummer)
 */
final class MigrateShopSerialToMerconisLicenseKeyMigration extends AbstractMigration
{
    public function shouldRun(): bool
    {
        return isset($GLOBALS['TL_CONFIG']['ls_shop_serial'])
            && (string) ($GLOBALS['TL_CONFIG']['ls_shop_serial'] ?? '') !== ''
            && (!isset($GLOBALS['TL_CONFIG']['merconis_licenseKey']) || (string) ($GLOBALS['TL_CONFIG']['merconis_licenseKey'] ?? '') === '');
    }

    public function run(): MigrationResult
    {
        $oldValue = (string) Config::get('ls_shop_serial');

        if (trim($oldValue) !== '') {
            Config::set('merconis_licenseKey', $oldValue);
            Config::persist('merconis_licenseKey', $oldValue);
        }

        return $this->createResult(
            true,
            "Der Konfigurationsschlüssel 'ls_shop_serial' wurde nach 'merconis_licenseKey' übernommen."
        );
    }
}

