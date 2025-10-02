<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product;

use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\ProductSearch\SearchResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSearchInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;

class Search implements CommonInterface, IndexSearchInterface
{
    use AdapterCommonTrait;

    private Connection $connection;

    public function __construct(Client $client)
    {
        $this->connection = $client->getConnection();
    }

    public function initialize(): void
    {
        // No initialization required for direct MySQL queries.
    }

    public function search(Adapter &$productSearchAdapter, string $language, bool $activateFacets = true, bool $activateMatchEstimates = true, bool $removeImpossibleOptions = true): SearchResult
    {
        $criteria = $productSearchAdapter->getSearchCriteria();
        $productIds = $this->fetchProductIdsByCriteria($criteria);

        $searchResult = new SearchResult($productIds);
        $searchResult->setNumProductsUnfiltered(count($productIds));
        $searchResult->setNumProductsFiltered(count($productIds));

        return $searchResult;
    }

    private function fetchProductIdsByCriteria(array $criteria): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('DISTINCT product.id')
            ->from('tl_ls_shop_product', 'product')
            ->leftJoin('product', 'tl_ls_shop_product_page_map', 'map', 'map.pid = product.id');

        $parameters = [];

        if (isset($criteria['pages'])) {
            $pageIds = is_array($criteria['pages']) ? $criteria['pages'] : [$criteria['pages']];
            $pageIds = array_filter(array_map('intval', $pageIds));

            if (count($pageIds)) {
                $qb->andWhere('map.page_id IN (:pageIds)');
                $parameters['pageIds'] = $pageIds;
            } else {
                return [];
            }
        }

        if (isset($criteria['published'])) {
            $published = $criteria['published'];
            if ($published === '1' || $published === 1 || $published === true) {
                $qb->andWhere('product.published = 1');
            }
        }

        foreach ($parameters as $key => $value) {
            $qb->setParameter($key, $value, Connection::PARAM_INT_ARRAY);
        }

        $ids = $qb->executeQuery()->fetchFirstColumn();

        return array_map('intval', $ids);
    }
}


