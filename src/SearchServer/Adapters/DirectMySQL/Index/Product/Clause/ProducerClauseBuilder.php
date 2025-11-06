<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

final class ProducerClauseBuilder
{
	/**
	 * @param array<int, array{text:string, exact?:bool}> $terms
	 * @param callable():string $nextParameterName
	 * @param callable(string):string $createLikePattern
	 */
	public function build(array $terms, string $normalizedFullQuery, bool $debug, callable $nextParameterName, callable $createLikePattern, QueryBuilder $qb): ClauseBuildResult
	{
		if (!count($terms)) {
			return new ClauseBuildResult(null, []);
		}

		$params = [];
		$paramTypes = [];
		$likeParts = [];
		$eqParts = [];
		foreach ($terms as $term) {
			$text = isset($term['text']) ? (string) $term['text'] : '';
			$isExact = (bool) ($term['exact'] ?? false);
			if ($text === '') { continue; }
			if ($isExact) {
				$p = $nextParameterName();
				$params[$p] = strtolower(trim($text));
				$paramTypes[$p] = ParameterType::STRING;
				$eqParts[] = sprintf('LOWER(product.lsShopProductProducer) = :%s', $p);
				continue;
			}
			$p = $nextParameterName();
			$params[$p] = $createLikePattern($text);
			$paramTypes[$p] = ParameterType::STRING;
			$likeParts[] = sprintf("LOWER(product.lsShopProductProducer) LIKE :%s ESCAPE '\\\\'", $p);
		}

		if (!count($likeParts) && !count($eqParts)) {
			return new ClauseBuildResult(null, []);
		}

		$whereParts = [];

		$scoreAdditions = [];
		$debugSelects = [];

		if (count($likeParts)) {
			$producerWhereAll = '(' . implode(' AND ', $likeParts) . ')';
			$producerWhereAny = '(' . implode(' OR ', $likeParts) . ')';
			$whereParts[] = $producerWhereAny;
			$producerBoostAllTerms = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_allTerms'] ?? 60);
			$producerBoostAnyTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_anyTerm'] ?? 10);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $producerWhereAll, (string) $producerBoostAllTerms);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $producerWhereAny, (string) $producerBoostAnyTerm);
			if ($debug) {
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_like_all', $producerWhereAll, (string) $producerBoostAllTerms);
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_like_any', $producerWhereAny, (string) $producerBoostAnyTerm);
			}
		}

		if (count($eqParts)) {
			$producerEqualsAny = '(' . implode(' OR ', $eqParts) . ')';
			$whereParts[] = $producerEqualsAny;
			$producerBoostExactTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_exactTerm'] ?? 80);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $producerEqualsAny, (string) $producerBoostExactTerm);
			if ($debug) {
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_eq_term', $producerEqualsAny, (string) $producerBoostExactTerm);
			}
		}

		if ($normalizedFullQuery !== '') {
			$p = $nextParameterName();
			$params[$p] = $normalizedFullQuery;
			$paramTypes[$p] = ParameterType::STRING;
			$producerEqualsFullExpr = sprintf('LOWER(product.lsShopProductProducer) = :%s', $p);
			$producerBoostExactFull = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_exactFullQuery'] ?? 160);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $producerEqualsFullExpr, (string) $producerBoostExactFull);
			if ($debug) {
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_eq_full', $producerEqualsFullExpr, (string) $producerBoostExactFull);
			}
		}

		$where = '(' . implode(' OR ', $whereParts) . ')';

		foreach ($debugSelects as $sel) {
			$qb->addSelect($sel);
		}

		return new ClauseBuildResult($where, $scoreAdditions, $params, $paramTypes);
	}
}


