<?php

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\Config;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;


class UpdateShopProductPageConfigMigration extends AbstractMigration
{

    public function shouldRun(): bool
    {
        return isset($GLOBALS['TL_CONFIG']['ls_shop_defaultProductPage'])
            && !isset($GLOBALS['TL_CONFIG']['ls_shop_defaultProductPages']);
    }

    public function run(): MigrationResult
    {
        // Hole den Wert des alten Schlüssels aus der Konfiguration
        $oldValue = Config::get('ls_shop_defaultProductPage');

        if($oldValue){
            // Setze den neuen Schlüssel im Speicher und dann speichere ihn in der localconfig
            Config::set('ls_shop_defaultProductPages', $oldValue);
            Config::persist('ls_shop_defaultProductPages', $oldValue);

            // Setze den alten Schlüssel im Speicher auf null und lösche den alten aus der localconfig
            Config::set('ls_shop_defaultProductPage', null);
            Config::remove('ls_shop_defaultProductPage');
        }

        return $this->createResult(
            true,
            "Der Konfigurationsschlüssel 'ls_shop_defaultProductPage' wurde erfolgreich zu 'ls_shop_defaultProductPages' migriert."
        );
    }
}