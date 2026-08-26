<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use LeadingSystems\MerconisBundle\Sitemap\SitemapUrlAppender;
use Merconis\Core\ls_shop_languageHelper;

/*
 * Diese Funktion wird beim Aufbauen des Suchindex aufgerufen und ergänzt das übergebene Array der in den Index aufzunehmenden Seiten/URLs
 * um die ebenfalls aufzunehmenden Produkt-Seiten/-URLs.
 */
class SitemapListener
{
    public function __construct(
        private readonly SitemapUrlAppender $sitemapUrlAppender,
        private readonly bool $productUrlsHandledExternally = false,
    ) {
    }

    public function __invoke(SitemapEvent $event): void
    {
        if ($this->productUrlsHandledExternally) {
            return;
        }

        $languageKeys = ls_shop_languageHelper::getAllLanguages();
        $productRows = Database::getInstance()
            ->prepare(
                sprintf(
                    'SELECT `pages`, %s FROM `tl_ls_shop_product` WHERE `published` = 1',
                    $this->buildProductColumnList($languageKeys)
                )
            )
            ->limit(10000)
            ->execute();

        $pageController = System::getContainer()->get('contao_helper.controller.page_controller');

        while ($productRows->next()) {
            $assignedPageIds = array_values(
                array_filter(
                    array_map('intval', StringUtil::deserialize($productRows->pages, true)),
                    static fn (int $pageId): bool => $pageId > 0
                )
            );

            if ([] === $assignedPageIds) {
                continue;
            }

            $eligiblePages = $this->findEligiblePagesForProduct($assignedPageIds);

            while ($eligiblePages->next()) {
                foreach (ls_shop_languageHelper::getLanguagePages((int) $eligiblePages->id) as $languagePageInfo) {
                    $languagePageId = (int) ($languagePageInfo['id'] ?? 0);

                    if ($languagePageId <= 0) {
                        continue;
                    }

                    $targetPage = $pageController->getPageDetailsCached($languagePageId);

                    if (!$targetPage instanceof PageModel) {
                        continue;
                    }

                    $aliasColumn = 'alias_'.$targetPage->language;
                    $languageAlias = (string) ($productRows->{$aliasColumn} ?? '');

                    if ('' === $languageAlias) {
                        continue;
                    }

                    $this->sitemapUrlAppender->appendProductUrl($event, $targetPage, $languageAlias);
                }
            }
        }
    }

    /**
     * @param array<int, string> $languageKeys
     */
    private function buildProductColumnList(array $languageKeys): string
    {
        $columns = ['`pages`'];

        foreach ($languageKeys as $languageKey) {
            $columns[] = sprintf('`alias_%s`', $languageKey);
        }

        return implode(', ', $columns);
    }

    /**
     * @param list<int> $assignedPageIds
     */
    private function findEligiblePagesForProduct(array $assignedPageIds): object
    {
        $time = time();
        $pagePlaceholders = implode(' OR ', array_fill(0, count($assignedPageIds), '`id` = ?'));

        return Database::getInstance()
            ->prepare(
                "SELECT `id`
                 FROM `tl_page`
                 WHERE ($pagePlaceholders)
                   AND (`start` = '' OR `start` < ?)
                   AND (`stop` = '' OR `stop` > ?)
                   AND `published` = 1
                   AND `noSearch` != 1
                   AND `sitemap` != 'map_never'"
            )
            ->execute(...[...$assignedPageIds, $time, $time]);
    }
}
