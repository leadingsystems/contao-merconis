<?php

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Exception;

class FilterMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $needUpdate_tl_ls_shop_filter_fields = false;
        $needUpdate_tl_module = false;
        $schemaManager = $this->connection->createSchemaManager();

        if($schemaManager->tablesExist(['tl_ls_shop_filter_fields']))
        {
            $test = $this->connection->fetchNumeric("
                SELECT *
                FROM `tl_ls_shop_filter_fields` 
                WHERE templateToUse='template_formFilterField_new' 
                    OR templateToUseForPriceField='template_formPriceFilterField_new'
            ");

            if(is_array($test))
            {
                $needUpdate_tl_ls_shop_filter_fields = (bool)count($test);
            }
        }

        if($schemaManager->tablesExist(['tl_module']) && isset($schemaManager->listTableColumns('tl_module')['ls_shop_filterForm_template']))
        {
            $test2 = $this->connection->fetchNumeric("
                SELECT *
                FROM `tl_module`
                WHERE ls_shop_filterForm_template='template_filterForm_new' 
                   OR ls_shop_filterSummary_template='template_filterSummary_withAllFields'
            ");

            if(is_array($test2))
            {
                $needUpdate_tl_module = (bool)count($test2);
            }
        }

        //check if at least one of this two database tables need to be updatet
        return ($needUpdate_tl_ls_shop_filter_fields || $needUpdate_tl_module);
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();
        try
        {
            if($schemaManager->tablesExist('tl_ls_shop_filter_fields'))
            {
                $this->connection->update(
                    'tl_ls_shop_filter_fields',
                    ['templateToUse' => 'template_formFilterField_standard'],
                    ['templateToUse' => 'template_formFilterField_new']
                );

                $this->connection->update(
                    'tl_ls_shop_filter_fields',
                    ['templateToUseForPriceField' => 'template_formPriceFilterField_standard'],
                    ['templateToUseForPriceField' => 'template_formPriceFilterField_new']
                );
            }

            if($schemaManager->tablesExist('tl_module') && isset($schemaManager->listTableColumns('tl_module')['ls_shop_filterForm_template']))
            {
                $this->connection->update(
                    'tl_module',
                    ['ls_shop_filterForm_template' => 'template_filterForm_default'],
                    ['ls_shop_filterForm_template' => 'template_filterForm_new']
                );

                $this->connection->update(
                    'tl_module',
                    ['ls_shop_filterSummary_template' => 'template_filterSummary_default'],
                    ['ls_shop_filterSummary_template' => 'template_filterSummary_withAllFields']
                );
            }
        }
        catch(Exception $e)
        {
            return $this->createResult(
                false,
                $e
            );
        }

        return $this->createResult(true);
    }
}