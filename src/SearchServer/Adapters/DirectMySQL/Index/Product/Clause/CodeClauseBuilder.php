<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

final class CodeClauseBuilder
{
	/**
	 * @param array<int, array{text:string, exact?:bool, targets?:array<int,string>}> $terms
	 * @param callable():string $nextParameterName
	 * @param callable(string):string $createLikePattern
	 */
	public function build(array $terms, bool $debug, callable $nextParameterName, callable $createLikePattern, QueryBuilder $qb, array $targetMap = []): ClauseBuildResult
	{
		if (!count($terms)) {
			return new ClauseBuildResult(null, []);
		}

		/*
		 * targetMap example structure (keys are target identifiers present in $term['targets']):
		 * [
		 *   'code' => ['likeExpr' => "LOWER(product.lsShopProductCode)", 'eqExpr' => $normalizedCodeExpr, 'cfgPrefix' => 'ls_shop_dmysql_code'],
		 *   'mpn'  => ['likeExpr' => "LOWER(product.mpn)",            'eqExpr' => "LOWER(product.mpn)", 'cfgPrefix' => 'ls_shop_dmysql_mpn'],
		 *   'gtin' => ['likeExpr' => "product.gtin",                  'eqExpr' => "product.gtin",       'cfgPrefix' => 'ls_shop_dmysql_gtin'],
		 * ]
		 */
		if (!is_array($targetMap) || !count($targetMap)) {
			$targetMap = [
				'code' => ['likeExpr' => "LOWER(product.lsShopProductCode)", 'eqExpr' => "LOWER(product.lsShopProductCode)", 'cfgPrefix' => 'ls_shop_dmysql_code']
			];
		}

		$params = [];
		$paramTypes = [];
		$likePartsByTarget = [];
		$eqPartsByTarget = [];

		foreach ($terms as $term) {
			$text = isset($term['text']) ? (string) $term['text'] : '';
			$isExact = (bool) ($term['exact'] ?? false);
			$targets = isset($term['targets']) && is_array($term['targets']) ? $term['targets'] : ['code'];
			if ($text === '') { continue; }

			foreach ($targets as $t) {
				if (!isset($targetMap[$t])) { continue; }
				$likeExpr = (string) $targetMap[$t]['likeExpr'];
				$eqExpr = (string) $targetMap[$t]['eqExpr'];

				if ($isExact) {
					$p = $nextParameterName();
					$params[$p] = strtolower(trim($text));
					$paramTypes[$p] = ParameterType::STRING;
					$eqPartsByTarget[$t][] = sprintf('%s = :%s', $eqExpr, $p);
					continue;
				}
				$p = $nextParameterName();
				$params[$p] = $createLikePattern($text);
				$paramTypes[$p] = ParameterType::STRING;
				$likePartsByTarget[$t][] = sprintf("%s LIKE :%s ESCAPE '\\\\'", $likeExpr, $p);
			}
		}

		$hasAnyLike = false; foreach ($likePartsByTarget as $arr) { if (!empty($arr)) { $hasAnyLike = true; break; } }
		$hasAnyEq = false; foreach ($eqPartsByTarget as $arr) { if (!empty($arr)) { $hasAnyEq = true; break; } }
		if (!$hasAnyLike && !$hasAnyEq) {
			return new ClauseBuildResult(null, []);
		}

		$whereParts = [];
		$scoreAdditions = [];
		$debugSelects = [];

		// Build per-target like/eq where + scoring
		foreach ($targetMap as $t => $cfg) {
			$prefix = (string)($cfg['cfgPrefix'] ?? 'ls_shop_dmysql_code');

			if (!empty($likePartsByTarget[$t])) {
				$whereAll = '(' . implode(' AND ', $likePartsByTarget[$t]) . ')';
				$whereAny = '(' . implode(' OR ', $likePartsByTarget[$t]) . ')';
				$whereParts[] = $whereAny;
				$boostAll = (int) ($GLOBALS['TL_CONFIG'][$prefix . '_boost_allTerms'] ?? ($t === 'gtin' ? 110 : 100));
				$boostAny = (int) ($GLOBALS['TL_CONFIG'][$prefix . '_boost_anyTerm'] ?? ($t === 'gtin' ? 25 : 20));
				$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $whereAll, (string) $boostAll);
				$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $whereAny, (string) $boostAny);
				if ($debug) {
					$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_%s_like_all', $whereAll, (string) $boostAll, $t);
					$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_%s_like_any', $whereAny, (string) $boostAny, $t);
				}
			}

			if (!empty($eqPartsByTarget[$t])) {
				$eqAny = '(' . implode(' OR ', $eqPartsByTarget[$t]) . ')';
				$whereParts[] = $eqAny;
				$boostExact = (int) ($GLOBALS['TL_CONFIG'][$prefix . '_boost_exactTerm'] ?? ($t === 'gtin' ? 220 : 150));
				$scoreAdditions[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END', $eqAny, (string) $boostExact);
				if ($debug) {
					$debugSelects[] = sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_%s_eq_term', $eqAny, (string) $boostExact, $t);
				}
			}
		}

		$where = '(' . implode(' OR ', $whereParts) . ')';

		foreach ($debugSelects as $sel) {
			$qb->addSelect($sel);
		}

		return new ClauseBuildResult($where, $scoreAdditions, $params, $paramTypes);
	}
}


