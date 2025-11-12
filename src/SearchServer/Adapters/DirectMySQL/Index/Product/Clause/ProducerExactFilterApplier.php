<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

final class ProducerExactFilterApplier
{
	/**
	 * Applies exact producer filter using IN (...) list (case-insensitive).
	 * Returns additional params and types.
	 *
	 * @param mixed $producersCriteria
	 * @return array{params: array<string, mixed>, types: array<string, int>}
	 */
	public function apply(QueryBuilder $qb, $producersCriteria): array
	{
		$params = [];
		$types = [];

		if (empty($producersCriteria) || !is_array($producersCriteria)) {
			return ['params' => $params, 'types' => $types];
		}

		$producers = array_values(array_filter(array_map(function ($p) { return strtolower(trim((string) $p)); }, $producersCriteria), function ($v) { return $v !== ''; }));
		if (!count($producers)) {
			return ['params' => $params, 'types' => $types];
		}

		$qb->andWhere('LOWER(product.lsShopProductProducer) IN (:dmysql_producers)');
		$params['dmysql_producers'] = $producers;
		$types['dmysql_producers'] = ArrayParameterType::STRING;

		return ['params' => $params, 'types' => $types];
	}
}


