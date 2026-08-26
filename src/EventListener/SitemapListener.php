<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use LeadingSystems\MerconisBundle\Sitemap\PageDetailsProviderInterface;
use LeadingSystems\MerconisBundle\Sitemap\SitemapLanguageTargetProcessor;
use Merconis\Core\ls_shop_languageHelper;

/*
 * Diese Funktion wird beim Aufbauen des Suchindex aufgerufen und ergänzt das übergebene Array der in den Index aufzunehmenden Seiten/URLs
 * um die ebenfalls aufzunehmenden Produkt-Seiten/-URLs.
 */
class SitemapListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly PageDetailsProviderInterface $pageDetailsProvider,
        private readonly SitemapLanguageTargetProcessor $languageTargetProcessor,
        private readonly bool $productUrlsHandledExternally = false,
    ) {
    }

    public function __invoke(SitemapEvent $event): void
    {
        if ($this->productUrlsHandledExternally) {
            return;
        }

        $languageKeys = array_values(ls_shop_languageHelper::getAllLanguages());

        foreach ($this->fetchProductRows($languageKeys) as $productRow) {
            $assignedPageIds = $this->normalizeAssignedPageIds($productRow['pages'] ?? null);

            if ([] === $assignedPageIds) {
                continue;
            }

            foreach ($this->findEligibleAssignedPageIds($assignedPageIds) as $assignedPageId) {
                $assignedPage = $this->pageDetailsProvider->getPageDetailsCached($assignedPageId);

                if (!$assignedPage instanceof PageModel) {
                    continue;
                }

                $this->languageTargetProcessor->appendProductUrlsForAssignedPage(
                    $event,
                    $assignedPage,
                    $productRow,
                );
            }
        }
    }

    /**
     * @param array<int, string> $languageKeys
     *
     * @return list<array<string, mixed>>
     */
    private function fetchProductRows(array $languageKeys): array
    {
        $columnList = $this->buildProductColumnList($languageKeys);

        return $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT %s FROM `tl_ls_shop_product` WHERE `published` = 1 ORDER BY `id` ASC LIMIT 10000',
                $columnList
            )
        );
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
     *
     * @return list<int>
     */
    private function normalizeAssignedPageIds(mixed $serializedPageSelection): array
    {
        return array_values(
            array_filter(
                array_map('intval', StringUtil::deserialize($serializedPageSelection, true)),
                static fn (int $pageId): bool => $pageId > 0
            )
        );
    }

    /**
     * @param list<int> $assignedPageIds
     *
     * @return list<int>
     */
    private function findEligibleAssignedPageIds(array $assignedPageIds): array
    {
        if ([] === $assignedPageIds) {
            return [];
        }

        $time = time();
        $eligiblePageIds = $this->connection->fetchFirstColumn(
            "SELECT `id`
             FROM `tl_page`
             WHERE `id` IN (?)
               AND `type` = ?
               AND (`start` = '' OR `start` < ?)
               AND (`stop` = '' OR `stop` > ?)
               AND `published` = 1
               AND `noSearch` != 1
               AND `sitemap` != ?
             ORDER BY `id` ASC",
            [$assignedPageIds, 'regular', $time, $time, 'map_never'],
            [
                ArrayParameterType::INTEGER,
                ParameterType::STRING,
                ParameterType::INTEGER,
                ParameterType::INTEGER,
                ParameterType::STRING,
            ]
        );

        return array_values(array_map('intval', $eligiblePageIds));
    }
}
