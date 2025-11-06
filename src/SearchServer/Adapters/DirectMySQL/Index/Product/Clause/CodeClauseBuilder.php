<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

final class CodeClauseBuilder
{
	/**
	 * @param array<int, array{text:string, exact?:bool}> $terms
	 * @param callable():string $nextParameterName
	 * @param callable(string):string $createLikePattern
	 */
	public function build(array $terms, string $normalizedFullQuery, string $normalizedCodeExpr, bool $debug, callable $nextParameterName, callable $createLikePattern, QueryBuilder $qb): ClauseBuildResult
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
				$eqParts[] = sprintf('%s = :%s', $normalizedCodeExpr, $p);
				continue;
			}
			$p = $nextParameterName();
			$params[$p] = $createLikePattern($text);
			$paramTypes[$p] = ParameterType::STRING;
			$likeParts[] = sprintf("LOWER(product.lsShopProductCode) LIKE :%s ESCAPE '\\\\'", $p);
		}

		if (!count($likeParts) && !count($eqParts)) {
			return new ClauseBuildResult(null, []);
		}

		$whereParts = [];
		$scoreAdditions = [];
		$debugSelects = [];

		if (count($likeParts)) {
			$codeWhereAll = '(' . implode(' AND ', $likeParts) . ')';
			$codeWhereAny = '(' . implode(' OR ', $likeParts) . ')';
			$whereParts[] = $codeWhereAny;
			$codeBoostAllTerms = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_allTerms'] ?? 100);
			$codeBoostAnyTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_anyTerm'] ?? 20);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $codeWhereAll, (string) $codeBoostAllTerms);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $codeWhereAny, (string) $codeBoostAnyTerm);
			if ($debug) {
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_like_all', $codeWhereAll, (string) $codeBoostAllTerms);
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_like_any', $codeWhereAny, (string) $codeBoostAnyTerm);
			}
		}

		if (count($eqParts)) {
			$codeEqualsAny = '(' . implode(' OR ', $eqParts) . ')';
			$whereParts[] = $codeEqualsAny;
			$codeBoostExactTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_exactTerm'] ?? 150);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $codeEqualsAny, (string) $codeBoostExactTerm);
			if ($debug) {
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_eq_term', $codeEqualsAny, (string) $codeBoostExactTerm);
			}
		}

		$where = '(' . implode(' OR ', $whereParts) . ')';

		if ($normalizedFullQuery !== '') {
			$p = $nextParameterName();
			$params[$p] = $normalizedFullQuery;
			$paramTypes[$p] = ParameterType::STRING;
			$codeEqualsFullExpr = sprintf('%s = :%s', $normalizedCodeExpr, $p);
			$codeBoostExactFull = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_exactFullQuery'] ?? 300);
			$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $codeEqualsFullExpr, (string) $codeBoostExactFull);
			if ($debug) {
				$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_eq_full', $codeEqualsFullExpr, (string) $codeBoostExactFull);
			}
		}

		if ($debug) {
			$qb->addSelect($normalizedCodeExpr . ' AS dbg_product_code_norm');
		}

		foreach ($debugSelects as $sel) {
			$qb->addSelect($sel);
		}

		return new ClauseBuildResult($where, $scoreAdditions, $params, $paramTypes);
	}
}


