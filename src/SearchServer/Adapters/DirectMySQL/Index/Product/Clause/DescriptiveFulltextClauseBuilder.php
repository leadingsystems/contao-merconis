<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

final class DescriptiveFulltextClauseBuilder
{
	/**
	 * @param array<int, string> $descriptiveColumns qualified column names
	 * @param callable():string $nextParameterName
	 * @param callable(string):float $getWeightForColumn receives qualified column, returns weight
	 */
	public function build(array $terms, array $descriptiveColumns, string $booleanQuery, bool $debug, callable $nextParameterName, callable $getWeightForColumn, QueryBuilder $qb): ClauseBuildResult
	{
		if (!count($terms) || !count($descriptiveColumns) || $booleanQuery === '') {
			return new ClauseBuildResult(null, []);
		}

		$paramName = $nextParameterName();
		$params = [$paramName => $booleanQuery];
		$paramTypes = [$paramName => ParameterType::STRING];

		$matches = [];
		$scoreParts = [];
		$debugSelects = [];
		foreach ($descriptiveColumns as $col) {
			$matches[] = sprintf('MATCH(%s) AGAINST (:%s IN BOOLEAN MODE)', $col, $paramName);
			$weight = (float) $getWeightForColumn($col);
			$weightLiteral = sprintf('%.15g', $weight);
			$scoreParts[] = sprintf('%s * COALESCE(MATCH(%s) AGAINST (:%s IN BOOLEAN MODE), 0)', $weightLiteral, $col, $paramName);
			if ($debug) {
				$colAlias = $this->toDebugAlias($col);
				$debugSelects[] = sprintf('COALESCE(MATCH(%s) AGAINST (:%s IN BOOLEAN MODE), 0) AS dbg_m_%s', $col, $paramName, $colAlias);
				$debugSelects[] = sprintf('%s * COALESCE(MATCH(%s) AGAINST (:%s IN BOOLEAN MODE), 0) AS dbg_w_%s', $weightLiteral, $col, $paramName, $colAlias);
			}
		}

		$where = count($matches) ? '(' . implode(' OR ', $matches) . ')' : null;
		$scoreAdditions = [];
		if (count($scoreParts)) {
			$scoreAdditions[] = '(' . implode(' + ', $scoreParts) . ')';
		}

		// Attach debug projections
		foreach ($debugSelects as $sel) {
			$qb->addSelect($sel);
		}

		return new ClauseBuildResult($where, $scoreAdditions, $params, $paramTypes);
	}

	private function toDebugAlias(string $qualifiedColumn): string
	{
		$base = $qualifiedColumn;
		$pos = strrpos($base, '.');
		if ($pos !== false) {
			$base = substr($base, $pos + 1);
		}
		return strtolower(str_replace(['"', '\''], '', $base));
	}
}


