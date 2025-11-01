<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

use Doctrine\DBAL\Query\QueryBuilder;

final class SortingApplier
{
	/**
	 * @param callable(array,bool,string): array $buildOrderBy returns [orderBys, ignored]
	 * @return array<int, string> ignored sort fields
	 */
	public function apply(QueryBuilder $qb, array $sortingCriteria, bool $hasRelevance, string $language, callable $buildOrderBy): array
	{
		[$orderBys, $ignored] = $buildOrderBy($sortingCriteria, $hasRelevance, $language);
		if (!empty($orderBys)) {
			$first = true;
			foreach ($orderBys as [$expr, $dir]) {
				if ($first) {
					$qb->orderBy($expr, $dir);
					$first = false;
				} else {
					$qb->addOrderBy($expr, $dir);
				}
			}
			$qb->addOrderBy('product.id', 'ASC');
		} else {
			if ($hasRelevance) {
				$qb->orderBy('relevance', 'DESC');
				$qb->addOrderBy('product.id', 'ASC');
			} else {
				$qb->orderBy('product.id', 'ASC');
			}
		}
		return $ignored;
	}
}


