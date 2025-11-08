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
	 * @param array<int, array{text:string, boost?:float}>|null $perTermBoosts optional per-term boosts; when null or all boosts == 1.0, this feature is a no-op
	 */
	public function build(array $terms, array $descriptiveColumns, string $booleanQuery, bool $debug, callable $nextParameterName, callable $getWeightForColumn, QueryBuilder $qb, ?array $perTermBoosts = null): ClauseBuildResult
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

		// Optional per-term boosted contributions (no-op unless any boost != 1.0)
		$hasBoosted = false;
		$boostedTerms = [];
		if (is_array($perTermBoosts)) {
			foreach ($perTermBoosts as $t) {
				$text = isset($t['text']) ? (string) $t['text'] : '';
				if ($text === '') { continue; }
				$boost = isset($t['boost']) ? (float) $t['boost'] : 1.0;
				if (!is_finite($boost)) { $boost = 1.0; }
				if ($boost < 0.1) { $boost = 0.1; }
				if ($boost > 100.0) { $boost = 100.0; }
				if ($boost !== 1.0) {
					$hasBoosted = true;
				}
				$boostedTerms[] = ['text' => $text, 'boost' => $boost];
			}
		}

		if ($hasBoosted) {
			// Limit to first 4 boosted terms, skip stopwords and too-short tokens
			$countAdded = 0;
			foreach ($boostedTerms as $bt) {
				if ($countAdded >= 4) { break; }
				$txt = trim($bt['text']);
				if ($this->isTooShortOrStopword($txt)) { continue; }
				$countAdded++;
				// Build a boolean single-term string similar to buildBooleanFulltextShouldQueryString
				$single = $this->toBooleanSingleTerm($txt);
				$p = $nextParameterName();
				$params[$p] = $single;
				$paramTypes[$p] = ParameterType::STRING;
				foreach ($descriptiveColumns as $col) {
					$weight = (float) $getWeightForColumn($col);
					// effective delta factor: weight * (boost - 1)
					$delta = $weight * ((float) $bt['boost'] - 1.0);
					if ($delta == 0.0) { continue; }
					$deltaLiteral = sprintf('%.15g', $delta);
					$expr = sprintf('%s * COALESCE(MATCH(%s) AGAINST (:%s IN BOOLEAN MODE), 0)', $deltaLiteral, $col, $p);
					$scoreAdditions[] = $expr;
					if ($debug) {
						$colAlias = $this->toDebugAlias($col);
						$debugSelects[] = sprintf('%s AS dbg_bt_%s', $expr, $colAlias);
					}
				}
			}
		}

		// Attach debug projections
		foreach ($debugSelects as $sel) {
			$qb->addSelect($sel);
		}

		return new ClauseBuildResult($where, $scoreAdditions, $params, $paramTypes);
	}

	private function isTooShortOrStopword(string $t): bool
	{
		$norm = strtolower(trim($t));
		if ($norm === '' || strlen($norm) < 3) { return true; }
		$stop = [
			// English
			'the','and','or','for','with','not','but','you','are','was','were','this','that','these','those','from','into','onto','over','under','of','in','on','to','by','as','at','be','is','am','it',
			// German
			'der','die','das','und','oder','nicht','mit','ohne','für','zum','zur','von','im','in','auf','an','dem','den','des','ein','eine','einer','eines','ist','sind','war','waren','dies','diese','dieser'
		];
		return in_array($norm, $stop, true);
	}

	private function toBooleanSingleTerm(string $t): string
	{
		// Strip boolean operators to avoid injection, then append wildcard
		$term = str_replace(['+','-','~','<','>','(',')','"','\''], ' ', $t);
		$term = preg_replace('/\s+/', ' ', $term);
		$term = trim((string) $term);
		if ($term === '') { return ''; }
		return $term . '*';
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


