<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

final class PageConstraintBuilder
{
    /**
     * Applies page mapping constraints and returns whether GROUP BY is required.
     *
     * @param mixed $pagesCriteria
     * @return array{needsGroupBy: bool, params: array<string, mixed>, types: array<string, int>, noMatch: bool}
     */
    public function apply(QueryBuilder $qb, $pagesCriteria): array
	{
		$params = [];
		$types = [];
		$needsGroupBy = false;

        if (!isset($pagesCriteria)) {
            return ['needsGroupBy' => false, 'params' => $params, 'types' => $types, 'noMatch' => false];
		}

		$needsGroupBy = true;
		$pageIds = is_array($pagesCriteria) ? $pagesCriteria : [$pagesCriteria];
		$pageIds = array_filter(array_map('intval', $pageIds));

        if (!count($pageIds)) {
            return ['needsGroupBy' => true, 'params' => $params, 'types' => $types, 'noMatch' => true];
		}

		$qb->leftJoin('product', 'tl_ls_shop_product_page_map', 'map', 'map.pid = product.id');
		$qb->andWhere('map.page_id IN (:pageIds)');
		$params['pageIds'] = $pageIds;
		$types['pageIds'] = ArrayParameterType::INTEGER;

        return ['needsGroupBy' => $needsGroupBy, 'params' => $params, 'types' => $types, 'noMatch' => false];
	}
}


