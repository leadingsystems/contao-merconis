<?php

namespace LeadingSystems\MerconisBundle\Scheduler\Jobs;

use Contao\Database;
use Contao\StringUtil;
use LeadingSystems\MerconisCustomBundle\Scheduler\Traits\SchedulableTrait;

class BuildProductPageMap
{
    use SchedulableTrait;

    public function run(): void
    {
        $db = Database::getInstance();

        $db->execute(
            "
            CREATE TABLE IF NOT EXISTS `tl_ls_shop_product_page_map` (
              `pid` INT UNSIGNED NOT NULL,
              `page_id` INT UNSIGNED NOT NULL,
              PRIMARY KEY (`pid`, `page_id`),
              KEY `idx_page` (`page_id`),
              KEY `idx_pid` (`pid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            "
        );

        $db->execute("TRUNCATE `tl_ls_shop_product_page_map`");

        $products = $db->prepare("SELECT id, pages FROM tl_ls_shop_product")
            ->execute();

        $values = array();
        $params = array();
        $count = 0;
        $flush = function() use (&$values, &$params, &$count, $db) {
            if ($count <= 0) { return; }
            $sql = "INSERT IGNORE INTO tl_ls_shop_product_page_map (pid, page_id) VALUES " . implode(',', $values);
            $db->prepare($sql)->execute(...$params);
            $values = array();
            $params = array();
            $count = 0;
        };

        while ($products->next()) {
            $pid = (int) $products->id;
            $pages = StringUtil::deserialize($products->pages, true);
            if (!is_array($pages) || !count($pages)) {
                continue;
            }

            foreach ($pages as $pageId) {
                $pageId = (int) $pageId;
                if ($pageId <= 0) { continue; }

                $values[] = '(?, ?)';
                $params[] = $pid;
                $params[] = $pageId;
                $count++;

                if ($count >= 1000) { $flush(); }
            }
        }

        $flush();

        $this->executionResultMessage = 'Rebuilt tl_ls_shop_product_page_map successfully';
    }
}


