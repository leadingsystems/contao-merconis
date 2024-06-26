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

    public function getName(): string
    {
        return "Filter Migration";
    }

    public function shouldRun(): bool
    {
        $stmt = $this->connection->fetchNumeric("
            SELECT *
            FROM `tl_ls_shop_filter_fields` 
            WHERE templateToUse='template_formFilterField_new' 
                OR templateToUse='template_formPriceFilterField_new'
        ");

        $needUpdate_tl_ls_shop_filter_fields = false;
        if(is_array($stmt)){
            $needUpdate_tl_ls_shop_filter_fields = (bool)count($stmt);
        }

        $stmt2 = $this->connection->fetchNumeric("
            SELECT *
            FROM `tl_module`
            WHERE ls_shop_filterForm_template='template_filterForm_new' 
               OR ls_shop_filterSummary_template='template_filterSummary_withAllFields'
        ");

        $needUpdate_tl_module = false;
        if(is_array($stmt2)){

            $needUpdate_tl_module = (bool)count($stmt2);
        }

        //check if at least one of this two database tables need to be updatet
        return ($needUpdate_tl_ls_shop_filter_fields || $needUpdate_tl_module);
    }

    public function run(): MigrationResult
    {
        try{

             $this->connection->executeStatement("
                    UPDATE
                        tl_ls_shop_filter_fieldsxx
                    SET
                        templateToUse = 'template_formFilterField_standard'
                    WHERE 
                        templateToUse = 'template_formFilterField_new'
            ");

            $this->connection->executeStatement("
                    UPDATE
                        tl_ls_shop_filter_fields
                    SET
                        templateToUse = 'template_formPriceFilterField_standard'
                    WHERE
                        templateToUse = 'template_formPriceFilterField_new'
            ");

            $this->connection->executeStatement("
                    UPDATE
                        tl_module 
                    SET
                        ls_shop_filterForm_template = 'template_filterForm_default'
                    WHERE
                        ls_shop_filterForm_template = 'template_filterForm_new'
            ");

            $this->connection->executeStatement("
                    UPDATE
                        tl_module 
                    SET
                        ls_shop_filterSummary_template = 'template_filterSummary_default'
                    WHERE
                        ls_shop_filterSummary_template = 'template_filterSummary_withAllFields'
            ");

        }catch(Exception $e){
            return $this->createResult(
                false,
                $e
            );
        }

        return $this->createResult(
            true,
            'Fields updatet'
        );
    }
}