<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\Config;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;

final class InitializeEuCountryCodesMigration extends AbstractMigration
{
    private const DEFAULT_EU_COUNTRIES = 'de,be,bg,dk,ee,fi,fr,el,ie,it,hr,lv,lt,lu,mt,nl,at,pl,pt,ro,se,sk,si,es,cz,hu,cy,gr';

    public function shouldRun(): bool
    {
        return trim((string) Config::get('ls_shop_euCountrycodes')) === '';
    }

    public function run(): MigrationResult
    {
        Config::persist('ls_shop_euCountrycodes', self::DEFAULT_EU_COUNTRIES);

        return $this->createResult(
            true,
            'Initialized ls_shop_euCountrycodes with the default EU country list.'
        );
    }
}
