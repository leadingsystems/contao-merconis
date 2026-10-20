<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\Config;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use LeadingSystems\MerconisBundle\EInvoicing\Support\EInvoicingCodeLists;

final class InitializeEuCountryCodesMigration extends AbstractMigration
{
    public function shouldRun(): bool
    {
        return trim((string) Config::get('ls_shop_euCountrycodes')) === '';
    }

    public function run(): MigrationResult
    {
        $defaultEuCountries = implode(',', EInvoicingCodeLists::DEFAULT_EU_COUNTRIES);

        Config::set('ls_shop_euCountrycodes', $defaultEuCountries);
        Config::persist('ls_shop_euCountrycodes', $defaultEuCountries);

        return $this->createResult(
            true,
            'Initialized ls_shop_euCountrycodes with the default EU country list.'
        );
    }
}
