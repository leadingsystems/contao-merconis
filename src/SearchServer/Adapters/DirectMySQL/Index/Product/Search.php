<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\ProductSearch\SearchResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSearchInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;
use Psr\Log\LoggerInterface;

class Search implements CommonInterface, IndexSearchInterface
{
    use AdapterCommonTrait;

    private Connection $connection;
    private int $parameterCounter = 0;
    private ?array $productTableColumns = null;
    private LoggerInterface $logger;
    private string $projectDir;
    private string $environment;

    /**
     * Canonical field configuration keyed by lower-case identifiers.
     *
     * @var array<string, array{column:string, weight:float, languageAware:bool}>
     */
    private array $fieldConfigurations = [
        'title' => ['column' => 'title', 'weight' => 5.0, 'languageAware' => true],
        'keywords' => ['column' => 'keywords', 'weight' => 3.0, 'languageAware' => true],
        'shortdescription' => ['column' => 'shortDescription', 'weight' => 2.0, 'languageAware' => true],
        'description' => ['column' => 'description', 'weight' => 1.5, 'languageAware' => true],
        'lsshopproductcode' => ['column' => 'lsShopProductCode', 'weight' => 4.0, 'languageAware' => false],
        'lsshopproductproducer' => ['column' => 'lsShopProductProducer', 'weight' => 2.0, 'languageAware' => false],
    ];

    /**
     * Maps alternative modifier names to canonical keys from $fieldConfigurations.
     *
     * @var array<string, string>
     */
    private array $fieldAliases = [
        'producer' => 'lsshopproductproducer',
        'manufacturer' => 'lsshopproductproducer',
        'code' => 'lsshopproductcode',
        'productcode' => 'lsshopproductcode',
        'title' => 'title',
        'keywords' => 'keywords',
        'shortdescription' => 'shortdescription',
        'description' => 'description',
    ];

    /**
     * Default fields used when no field modifier is provided.
     *
     * @var string[]
     */
    private array $defaultFieldKeys = [
        'title',
        'keywords',
        'shortdescription',
        'description',
        'lsshopproductcode',
        'lsshopproductproducer',
    ];

    public function __construct(Client $client, LoggerInterface $logger, string $projectDir, string $environment)
    {
        $this->connection = $client->getConnection();
        $this->logger = $logger;
        $this->projectDir = $projectDir;
        $this->environment = $environment;
    }

    public function initialize(): void
    {
        // No initialization required for direct MySQL queries.
    }

    public function search(Adapter &$productSearchAdapter, string $language, bool $activateFacets = true, bool $activateMatchEstimates = true, bool $removeImpossibleOptions = true): SearchResult
    {
        $criteria = $productSearchAdapter->getSearchCriteria();
        $productIds = $this->fetchProductIdsByCriteria($criteria, $language);

        $searchResult = new SearchResult($productIds);
        $total = count($productIds);
        $searchResult->setNumProductsUnfiltered($total);
        $searchResult->setNumProductsFiltered($total);

        return $searchResult;
    }

    private function fetchProductIdsByCriteria(array $criteria, string $language): array
    {
        $this->parameterCounter = 0;
        $debugScoringEnabled = $this->isDebugScoringEnabled($criteria);

        $qb = $this->connection->createQueryBuilder();
        $qb->select('product.id')
            ->from('tl_ls_shop_product', 'product');

        $needsPageJoin = false;

        $parameters = [];
        $parameterTypes = [];

        if (isset($criteria['pages'])) {
            $needsPageJoin = true;
            $pageIds = is_array($criteria['pages']) ? $criteria['pages'] : [$criteria['pages']];
            $pageIds = array_filter(array_map('intval', $pageIds));

            if (count($pageIds)) {
                $qb->leftJoin('product', 'tl_ls_shop_product_page_map', 'map', 'map.pid = product.id');
                $qb->andWhere('map.page_id IN (:pageIds)');
                $qb->setParameter('pageIds', $pageIds, Connection::PARAM_INT_ARRAY);
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

        $fulltext = $criteria['fulltext'] ?? '';
        $fulltextComponents = $this->parseFulltextCriteria((string) $fulltext);
        // Reassemble normalized query text (order preserved, modifiers removed)
        $reassembledParts = [];
        foreach ($fulltextComponents as $comp) {
            $t = trim((string) ($comp['text'] ?? ''));
            if ($t !== '') { $reassembledParts[] = $t; }
        }
        $normalizedFullQuery = strtolower(implode(' ', $reassembledParts));

        // Build FULLTEXT boolean-mode search across descriptive fields and LIKE-based code/producer search.
        $descriptiveTerms = [];
        $codeTerms = [];
        $producerTerms = [];

        if (count($fulltextComponents)) {
            foreach ($fulltextComponents as $component) {
                $term = trim((string) ($component['text'] ?? ''));
                if ($term === '') { continue; }

                $fields = $component['fields'] ?? [];
                $includeInDescriptive = !count($fields);
                $includeInCode = !count($fields);
                $includeInProducer = !count($fields);

                foreach ($fields as $fieldKeyRaw) {
                    $canonical = $this->normalizeFieldKey($fieldKeyRaw);
                    if ($canonical === null) { continue; }
                    if (in_array($canonical, ['title','keywords','shortdescription','description'], true)) {
                        $includeInDescriptive = true;
                    }
                    if ($canonical === 'lsshopproductproducer') { $includeInProducer = true; }
                    if ($canonical === 'lsshopproductcode') {
                        $includeInCode = true;
                    }
                }

                if ($includeInDescriptive) { $descriptiveTerms[] = $term; }
                if ($includeInCode) { $codeTerms[] = $term; }
                if ($includeInProducer) { $producerTerms[] = $term; }
            }
        }

        $scoreExpression = '0';

        // Descriptive FULLTEXT: boolean mode with all terms required
        $fulltextWhere = null;
        $fulltextParamName = null;
        $descriptiveColumns = $this->getDescriptiveColumnsForLanguage($language);
        if (count($descriptiveTerms) && count($descriptiveColumns)) {
            $booleanQuery = $this->buildBooleanFulltextQueryString($descriptiveTerms);
            if ($booleanQuery !== null && $booleanQuery !== '') {
                $fulltextParamName = $this->nextParameterName();
                $parameters[$fulltextParamName] = $booleanQuery;
                $parameterTypes[$fulltextParamName] = ParameterType::STRING;

                // WHERE: At least one of the descriptive columns must match all terms
                // Use single MATCH per column and OR them so a product can match in any field
                $columnMatches = [];
                foreach ($descriptiveColumns as $col) {
                    $columnMatches[] = sprintf(
                        "MATCH(%s) AGAINST (:%s IN BOOLEAN MODE)",
                        $col,
                        $fulltextParamName
                    );
                }
                if (count($columnMatches)) {
                    $fulltextWhere = '(' . implode(' OR ', $columnMatches) . ')';
                }

                // Relevance: weighted sum of column-specific matches
                $scoreParts = [];
                foreach ($descriptiveColumns as $col) {
                    $weight = $this->getWeightForBaseColumn($col);
					$weightLiteral = sprintf('%.15g', $weight);
					$scoreParts[] = sprintf(
						"%s * COALESCE(MATCH(%s) AGAINST (:%s IN BOOLEAN MODE), 0)",
						$weightLiteral,
						$col,
						$fulltextParamName
					);

                    // Debug projection: raw and weighted matches per column
                    if ($debugScoringEnabled) {
                        $colAlias = $this->toDebugAlias($col);
                        $qb->addSelect(sprintf(
                            "COALESCE(MATCH(%s) AGAINST (:%s IN BOOLEAN MODE), 0) AS dbg_m_%s",
                            $col,
                            $fulltextParamName,
                            $colAlias
                        ));
                        $qb->addSelect(sprintf(
                            "%s * COALESCE(MATCH(%s) AGAINST (:%s IN BOOLEAN MODE), 0) AS dbg_w_%s",
                            $weightLiteral,
                            $col,
                            $fulltextParamName,
                            $colAlias
                        ));
                    }
                }
				if (count($scoreParts)) {
					$scoreExpression = '(' . implode(' + ', $scoreParts) . ')';
				}
            }
        }

		// Code LIKEs: build ANY-term and ALL-terms conditions
        $codeWhere = null;
        if (count($codeTerms)) {
            $likeParts = [];
            foreach ($codeTerms as $codeTerm) {
                $paramName = $this->nextParameterName();
                $parameters[$paramName] = $this->createLikePattern($codeTerm);
                $parameterTypes[$paramName] = ParameterType::STRING;
                $likeParts[] = sprintf("LOWER(product.lsShopProductCode) LIKE :%s ESCAPE '\\\\'", $paramName);
            }
            if (count($likeParts)) {
				$codeWhereAll = '(' . implode(' AND ', $likeParts) . ')';
				$codeWhereAny = '(' . implode(' OR ', $likeParts) . ')';
				// Use ANY-term match for inclusion in WHERE
				$codeWhere = $codeWhereAny;
				// Relevance boosts: strong boost for ALL-terms match, smaller for ANY-term match
				$scoreExpression = sprintf(
					'(%s) + CASE WHEN %s THEN %s ELSE 0 END',
					$scoreExpression,
					$codeWhereAll,
					'100'
				);
				$scoreExpression = sprintf(
					'(%s) + CASE WHEN %s THEN %s ELSE 0 END',
					$scoreExpression,
					$codeWhereAny,
					'20'
				);

				// Additional boosts for exact code matches
				// 1) Any single term equals the product code exactly (case-insensitive)
				$eqParts = [];
				$normalizedCodeExpr = $this->buildNormalizedProductCodeExpr();
				foreach ($codeTerms as $codeTerm) {
					$eqParam = $this->nextParameterName();
					$parameters[$eqParam] = strtolower(trim((string) $codeTerm));
					$parameterTypes[$eqParam] = ParameterType::STRING;
					$eqParts[] = sprintf('%s = :%s', $normalizedCodeExpr, $eqParam);
				}
                if (count($eqParts)) {
                    $codeEqualsAny = '(' . implode(' OR ', $eqParts) . ')';
                    $scoreExpression = sprintf(
                        '(%s) + CASE WHEN %s THEN %s ELSE 0 END',
                        $scoreExpression,
                        $codeEqualsAny,
                        '150'
                    );
                }

				// 2) The entire reassembled query equals the product code exactly (highest boost)
				if ($normalizedFullQuery !== '') {
					$eqAllParam = $this->nextParameterName();
					$parameters[$eqAllParam] = $normalizedFullQuery;
					$parameterTypes[$eqAllParam] = ParameterType::STRING;
					$codeEqualsFullExpr = sprintf('%s = :%s', $normalizedCodeExpr, $eqAllParam);
					$scoreExpression = sprintf(
						'(%s) + CASE WHEN %s THEN %s ELSE 0 END',
						$scoreExpression,
						$codeEqualsFullExpr,
						'300'
					);
				}

                // Debug projection: code contribution components
                if ($debugScoringEnabled) {
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_like_all', $codeWhereAll, '100'));
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_like_any', $codeWhereAny, '20'));
                    if (isset($codeEqualsAny)) {
                        $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_eq_term', $codeEqualsAny, '150'));
                    }
					if (isset($codeEqualsFullExpr)) {
						$qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_eq_full', $codeEqualsFullExpr, '300'));
					}
					// Also expose normalized code for clarity
					$qb->addSelect($normalizedCodeExpr . ' AS dbg_product_code_norm');
                }
            }
        }

        // Producer LIKEs: use regular index with LIKE and moderate boosts
        $producerWhere = null;
        if (count($producerTerms)) {
            $likeParts = [];
            foreach ($producerTerms as $producerTerm) {
                $paramName = $this->nextParameterName();
                $parameters[$paramName] = $this->createLikePattern($producerTerm);
                $parameterTypes[$paramName] = ParameterType::STRING;
                $likeParts[] = sprintf("LOWER(product.lsShopProductProducer) LIKE :%s ESCAPE '\\\\'", $paramName);
            }
            if (count($likeParts)) {
                $producerWhereAll = '(' . implode(' AND ', $likeParts) . ')';
                $producerWhereAny = '(' . implode(' OR ', $likeParts) . ')';
                // Use ANY-term match for inclusion in WHERE
                $producerWhere = $producerWhereAny;
                // Relevance boosts: moderate for ALL-terms, smaller for ANY-term
                $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerWhereAll, '60');
                $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerWhereAny, '10');

                // Additional boosts for exact producer matches (case-insensitive)
                $eqParts = [];
                foreach ($producerTerms as $producerTerm) {
                    $eqParam = $this->nextParameterName();
                    $parameters[$eqParam] = strtolower(trim((string) $producerTerm));
                    $parameterTypes[$eqParam] = ParameterType::STRING;
                    $eqParts[] = sprintf('LOWER(product.lsShopProductProducer) = :%s', $eqParam);
                }
                if (count($eqParts)) {
                    $producerEqualsAny = '(' . implode(' OR ', $eqParts) . ')';
                    $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerEqualsAny, '80');
                }

                // Entire normalized query equals producer exactly
                if ($normalizedFullQuery !== '') {
                    $eqAllParam = $this->nextParameterName();
                    $parameters[$eqAllParam] = $normalizedFullQuery;
                    $parameterTypes[$eqAllParam] = ParameterType::STRING;
                    $producerEqualsFullExpr = sprintf('LOWER(product.lsShopProductProducer) = :%s', $eqAllParam);
                    $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerEqualsFullExpr, '160');
                }

                // Debug projection for producer components
                if ($debugScoringEnabled) {
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_like_all', $producerWhereAll, '60'));
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_like_any', $producerWhereAny, '10'));
                    if (isset($producerEqualsAny)) {
                        $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_eq_term', $producerEqualsAny, '80'));
                    }
                    if (isset($producerEqualsFullExpr)) {
                        $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_eq_full', $producerEqualsFullExpr, '160'));
                    }
                }
            }
        }

        if ($fulltextWhere !== null || $codeWhere !== null || $producerWhere !== null) {
            $ors = [];
            if ($fulltextWhere !== null) { $ors[] = $fulltextWhere; }
            if ($codeWhere !== null) { $ors[] = $codeWhere; }
            if ($producerWhere !== null) { $ors[] = $producerWhere; }
            $qb->andWhere('(' . implode(' OR ', $ors) . ')');
        }

		$qb->addSelect($scoreExpression . ' AS relevance');

		// Debug identifiers: include product code and resolved title for easier identification
		if ($debugScoringEnabled) {
			$qb->addSelect('product.lsShopProductCode AS dbg_product_code');
			$titleExpr = $this->resolveColumnExpression($this->fieldConfigurations['title'], $language);
			if ($titleExpr !== null) {
				$qb->addSelect($titleExpr . ' AS dbg_product_title');
			}
		}

        if ($needsPageJoin) {
            $qb->groupBy('product.id');
        }

        if ($scoreExpression !== '0') {
            $qb->orderBy('relevance', 'DESC');
            $qb->addOrderBy('product.id', 'ASC');
        } else {
            $qb->orderBy('product.id', 'ASC');
        }

        foreach ($parameters as $name => $value) {
            $type = $parameterTypes[$name] ?? ParameterType::STRING;
            $qb->setParameter($name, $value, $type);
        }

        $this->logDebugInformation($criteria, $fulltextComponents, $qb);
        if ($debugScoringEnabled) {
            $this->logScoreBatchHeader($criteria, $language, $qb);
        }

        $rows = $qb->executeQuery()->fetchAllAssociative();

        if (!count($rows)) {
            return [];
        }

        if ($debugScoringEnabled && count($rows)) {
            $this->logScoreBreakdown($rows);
        }

        return array_map(static fn (array $row) => (int) $row['id'], $rows);
    }

    private function isDebugScoringEnabled(array $criteria): bool
    {
        $global = (bool) ($GLOBALS['TL_CONFIG']['ls_shop_debugSearchScoring'] ?? false);
        $byCriteria = (bool) ($criteria['debugScores'] ?? false);
        return $global || $byCriteria;
    }

    private function toDebugAlias(string $qualifiedColumn): string
    {
        // Strip table alias if present and replace dots with underscores
        $alias = $qualifiedColumn;
        if (strpos($alias, '.') !== false) {
            $alias = substr($alias, strrpos($alias, '.') + 1);
        }
        return str_replace('.', '_', $alias);
    }

    private function logScoreBatchHeader(array $criteria, string $language, \Doctrine\DBAL\Query\QueryBuilder $qb): void
    {
        try {
            $payload = [
                'ts' => gmdate('c'),
                'language' => $language,
                'criteria' => $criteria,
                'sql' => $qb->getSQL(),
                'parameters' => $qb->getParameters(),
            ];
            $logFile = $this->resolveScoreLogFilePath();
            $dir = \dirname($logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            @file_put_contents($logFile, json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
    }

    private function logScoreBreakdown(array $rows): void
    {
        try {
            $logFile = $this->resolveScoreLogFilePath();
            $dir = \dirname($logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            foreach ($rows as $row) {
                $components = [];
                $sum = 0.0;
                foreach ($row as $key => $value) {
                    if (strpos($key, 'dbg_') === 0) {
                        $components[$key] = is_numeric($value) ? (float) $value : $value;
                        if (strpos($key, 'dbg_m_') !== 0 && is_numeric($value)) {
                            $sum += (float) $value; // sum weighted + code components
                        }
                    }
                }
                $payload = [
                    'ts' => gmdate('c'),
                    'id' => isset($row['id']) ? (int) $row['id'] : null,
                    'relevance' => isset($row['relevance']) ? (float) $row['relevance'] : null,
                    'productCode' => $row['dbg_product_code'] ?? null,
                    'productTitle' => $row['dbg_product_title'] ?? null,
                    'components' => $components,
                    'componentsSum' => $sum,
                ];
                @file_put_contents($logFile, json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            }
        } catch (\Throwable $e) {
        }
    }

    private function resolveScoreLogFilePath(): string
    {
        $logDir = rtrim($this->projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs';
        $date = date('Y-m-d');
        return $logDir . DIRECTORY_SEPARATOR . 'merconis-search-scores-' . $date . '.log';
    }

    private function buildNormalizedProductCodeExpr(): string
    {
        $delimiter = (string) ($GLOBALS['TL_CONFIG']['ls_shop_productCodeDelimiter'] ?? '');
        $delimiter = trim($delimiter);
        if ($delimiter === '') {
            return 'LOWER(product.lsShopProductCode)';
        }
        $delimSql = $this->connection->quote($delimiter);
        return sprintf(
            'LOWER(CASE WHEN LOCATE(%1$s, product.lsShopProductCode) > 0 THEN SUBSTRING(product.lsShopProductCode, LOCATE(%1$s, product.lsShopProductCode) + CHAR_LENGTH(%1$s)) ELSE product.lsShopProductCode END)',
            $delimSql
        );
    }

    // Build a boolean-mode query string that requires all terms: "+term1* +term2* ..."
    private function buildBooleanFulltextQueryString(array $terms): string
    {
        $parts = [];
        foreach ($terms as $t) {
            $t = trim((string) $t);
            if ($t === '') { continue; }
            // Strip characters that have special boolean meaning to avoid user injection of operators
            $t = str_replace(['+','-','~','<','>','(',')','"',"'"], ' ', $t);
            $t = preg_replace('/\s+/', ' ', $t);
            $t = trim($t);
            if ($t === '') { continue; }
            $parts[] = '+' . $t . '*';
        }
        return implode(' ', $parts);
    }

    // Return descriptive columns (language-specific preferred) qualified with table alias that exist in the schema
    private function getDescriptiveColumnsForLanguage(string $language): array
    {
        $bases = ['title', 'keywords', 'shortDescription', 'description'];
        $existing = [];
        foreach ($bases as $base) {
            $langSpecific = $base . '_' . $language;
            if ($this->columnExists($langSpecific)) {
                $existing[] = 'product.' . $langSpecific;
                continue;
            }
            if ($this->columnExists($base)) { $existing[] = 'product.' . $base; }
        }
        return $existing;
    }

    private function getWeightForBaseColumn(string $qualifiedColumn): float
    {
        // Extract base column name
        $base = $qualifiedColumn;
        if (strpos($qualifiedColumn, '.') !== false) {
            $base = substr($qualifiedColumn, strrpos($qualifiedColumn, '.') + 1);
        }
        // Normalize possible language suffix (e.g., title_de -> title)
        $rootBase = $base;
        $underscorePos = strpos($base, '_');
        if ($underscorePos !== false) {
            $candidate = substr($base, 0, $underscorePos);
            if (in_array($candidate, ['title', 'keywords', 'shortDescription', 'description'], true)) {
                $rootBase = $candidate;
            }
        }

        switch ($rootBase) {
            case 'title': return 5.0;
            case 'keywords': return 3.0;
            case 'shortDescription': return 2.0;
            case 'description': return 1.5;
            default: return 1.0;
        }
    }

    private function parseFulltextCriteria(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        // Manual tokenizer keeps quoted phrases intact and supports escapes
        $rawTokens = $this->tokenizeFulltextRaw($raw);

        // Debug: log tokenization result to help diagnose empty parsing
        try {
            $this->logger->notice('DirectMySQL parse debug', [
                'raw' => $raw,
                'rawTokens' => $rawTokens,
            ]);
        } catch (\Throwable $e) {}
        try {
            $debugLine = json_encode(['ts' => gmdate('c'), 'parse_debug' => ['raw' => $raw, 'rawTokens' => $rawTokens]], JSON_UNESCAPED_SLASHES);
            @file_put_contents($this->resolveFallbackLogFilePath(), $debugLine . "\n", FILE_APPEND);
        } catch (\Throwable $e) {}

        $terms = [];
        foreach ($rawTokens as $token) {
            $currentTerm = $token;
            $attachedModifiers = '';
            if (preg_match('/^(?<term>[^{}]+)(?<mods>(\{[^}]+\})+)$/', $token, $termWithMods)) {
                $currentTerm = $termWithMods['term'];
                $attachedModifiers = $termWithMods['mods'];
            }

            $currentTerm = trim($currentTerm, "\"' ");
            if ($currentTerm === '') {
                continue;
            }

            $terms[] = [
                'text' => $currentTerm,
                'boost' => 1.0,
                'fields' => [],
            ];

            if ($attachedModifiers !== '') {
                $this->applyInlineModifiers($terms[count($terms) - 1], $attachedModifiers);
            }
        }

        // Apply standalone {field:..}/{boost:..} tokens to the previous term
        $previousIndex = null;
        foreach ($rawTokens as $token) {
            if ($token === '' || $token[0] !== '{' || substr($token, -1) !== '}') {
                $previousIndex = array_key_last($terms);
                continue;
            }
            if ($previousIndex === null) {
                continue;
            }
            $this->applyModifierToken($terms[$previousIndex], $token);
        }

        return $terms;
    }

    private function tokenizeFulltextRaw(string $raw): array
    {
        $tokens = [];
        $buffer = '';
        $inQuote = '';
        $escape = false;
        $len = strlen($raw);
        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];
            if ($escape) { $buffer .= $ch; $escape = false; continue; }
            if ($ch === '\\') { $escape = true; continue; }
            if ($inQuote !== '') {
                if ($ch === $inQuote) { $inQuote = ''; } else { $buffer .= $ch; }
                continue;
            }
            if ($ch === '"' || $ch === "'") { $inQuote = $ch; continue; }
            if (ctype_space($ch)) { if ($buffer !== '') { $tokens[] = $buffer; $buffer=''; } continue; }
            $buffer .= $ch;
        }
        if ($buffer !== '') { $tokens[] = $buffer; }
        return $tokens;
    }

    /**
     * Extract all modifier tuples from a string.
     * Returns an array of matches where each item is ['{name:value}', 'name', 'value'].
     */
    private function extractModifiers(string $input): array
    {
        if (!preg_match_all('/\{([^:}]+):([^}]+)\}/', $input, $mods, PREG_SET_ORDER)) {
            return [];
        }
        return $mods;
    }

    private function applyInlineModifiers(array &$term, string $modifiersString): void
    {
        $mods = $this->extractModifiers($modifiersString);
        foreach ($mods as $mod) {
            $this->applyModifier($term, $mod[1], $mod[2]);
        }
    }

    private function applyModifierToken(array &$term, string $token): void
    {
        // Accept one or more concatenated standalone modifiers as long as the token
        // consists solely of valid modifier blocks without any other characters.
        $mods = $this->extractModifiers($token);
        if (!count($mods)) {
            return;
        }
        $concatenated = '';
        foreach ($mods as $m) {
            $concatenated .= $m[0];
        }
        if ($concatenated !== $token) {
            // Token contains characters outside of {name:value} blocks → ignore
            return;
        }
        foreach ($mods as $m) {
            $this->applyModifier($term, $m[1], $m[2]);
        }
    }

    private function applyModifier(array &$term, string $name, string $value): void
    {
        $normalizedName = strtolower(trim($name));
        $value = trim($value);

        if ($normalizedName === 'boost') {
            $term['boost'] = max(0.1, (float) $value);
            return;
        }

        if ($normalizedName === 'field') {
            $fields = preg_split('/[|,]/', $value);
            $resolved = [];
            foreach ($fields as $field) {
                $fieldKey = $this->normalizeFieldKey($field);
                if ($fieldKey !== null) {
                    $resolved[] = $fieldKey;
                }
            }
            if (count($resolved)) {
                $term['fields'] = $resolved;
            }
        }
    }

    private function normalizeFieldKey(string $field): ?string
    {
        $key = strtolower(trim($field));
        if ($key === '') {
            return null;
        }

        if (isset($this->fieldAliases[$key])) {
            return $this->fieldAliases[$key];
        }

        if (isset($this->fieldConfigurations[$key])) {
            return $key;
        }

        return null;
    }

    private function getFieldConfig(string $fieldKey): ?array
    {
        $canonical = $this->normalizeFieldKey($fieldKey);
        if ($canonical === null) {
            return null;
        }

        return $this->fieldConfigurations[$canonical] ?? null;
    }

    private function resolveColumnExpression(array $config, string $language): ?string
    {
        $baseColumn = $config['column'];
        if ($config['languageAware']) {
            $languageSpecific = $baseColumn . '_' . $language;
            if ($this->columnExists($languageSpecific)) {
                return 'product.' . $languageSpecific;
            }
        }

        if ($this->columnExists($baseColumn)) {
            return 'product.' . $baseColumn;
        }

        return null;
    }

    private function columnExists(string $columnName): bool
    {
        if ($this->productTableColumns === null) {
            $schemaManager = $this->connection->createSchemaManager();
            $this->productTableColumns = [];
            foreach ($schemaManager->listTableColumns('tl_ls_shop_product') as $column) {
                $this->productTableColumns[] = strtolower($column->getName());
            }
        }

        return in_array(strtolower($columnName), $this->productTableColumns, true);
    }

    private function createLikePattern(string $term): string
    {
        $term = trim($term);
        $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $term = str_replace(['*', '?'], ['%', '_'], $term);

        return '%' . strtolower($term) . '%';
    }

    private function nextParameterName(): string
    {
        $this->parameterCounter++;
        return 'ftTerm' . $this->parameterCounter;
    }

    private function logDebugInformation(array $criteria, array $fulltextComponents, \Doctrine\DBAL\Query\QueryBuilder $qb): void
    {
        $isDebugEnabled = (bool) ($GLOBALS['TL_CONFIG']['ls_shop_debugSearch'] ?? false);
        if (!$isDebugEnabled) {
            return;
        }

        $payload = [
            'ts' => gmdate('c'),
            'criteria' => $criteria,
            'parsed_fulltext_terms' => $fulltextComponents,
            'sql' => $qb->getSQL(),
            'parameters' => $qb->getParameters(),
        ];

        try {
            $this->logger->notice('DirectMySQL search diagnostic', $payload);
        } catch (\Throwable $e) {
            // Prevent logging issues from breaking searches
        }

        // File-based fallback: write a line to var/logs/merconis-search-debug-YYYY-MM-DD.log
        try {
            $logFile = $this->resolveFallbackLogFilePath();
            $dir = \dirname($logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            @file_put_contents($logFile, json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (\Throwable $e) {
            // Swallow to avoid impacting requests
        }
    }

    private function resolveFallbackLogFilePath(): string
    {
        $logDir = rtrim($this->projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs';
        $date = date('Y-m-d');
        return $logDir . DIRECTORY_SEPARATOR . 'merconis-search-debug-' . $date . '.log';
    }
}


