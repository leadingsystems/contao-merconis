<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\ProductSearch\Facets;
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
        $criteria = $this->prepareCriteria($productSearchAdapter->getSearchCriteria());

        // Base criteria without attribute filters for keys-only facet discovery and unmatched counts
        $baseCriteria = $this->prepareBaseCriteria($criteria);

        // Get base candidates (respecting fulltext/pages/published etc.)
        // Note: baseCriteria still contains a possible producer filter; we will also compute a version without producers when needed.
        $baseCandidateIds = $this->fetchProductIdsByCriteria($baseCriteria, $language);

        // Fast path: no facets requested and no attribute filters → just return base IDs
        $attributeFilters = $this->normalizeAttributeFilters($criteria['attributes'] ?? []);
        $attributeFieldsPublished = $this->attributeFilterFieldsExist();
        $producerFieldPublished = $this->producerFilterFieldExists();
        if (!$activateFacets && empty($attributeFilters)) {
            $result = new SearchResult($baseCandidateIds);
            $total = count($baseCandidateIds);
            $result->setNumProductsUnfiltered($total);
            $result->setNumProductsFiltered($total);
            return $result;
        }

        // If there are no published attribute filter fields, skip attribute work but still handle producer facets if published
        if (!$attributeFieldsPublished) {
            $criteriaNoProd = $baseCriteria;
            unset($criteriaNoProd['producers']);
            $idsUnfilteredForProducers = $this->fetchProductIdsByCriteria($criteriaNoProd, $language);
            $idsFilteredForProducers = $baseCandidateIds; // may include producer filter

            if (!$activateFacets || !$producerFieldPublished) {
                $facets = $activateFacets ? new Facets([], [], []) : null;
                $result = new SearchResult($idsFilteredForProducers, $facets, false, 0, count($idsUnfilteredForProducers), count($idsFilteredForProducers));
                $result->setNumProductsUnfiltered(count($idsUnfilteredForProducers));
                $result->setNumProductsFiltered(count($idsFilteredForProducers));
                return $result;
            }

            if ($activateMatchEstimates) {
                $unfProd = $this->computeProducerCounts($idsUnfilteredForProducers);
                $filProd = $this->computeProducerCounts($idsFilteredForProducers);
                $unfilteredFacetMap = [];
                $filteredFacetMap = [];
                foreach ($unfProd as $producer => $cnt) {
                    $unfilteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => (int) $cnt];
                }
                foreach ($filProd as $producer => $cnt) {
                    $filteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => (int) $cnt];
                }
                $combined = [];
                foreach ($unfProd as $producer => $cnt) {
                    $combined[] = [
                        'producer' => (string) $producer,
                        'total_product_count' => (int) $cnt,
                        'filtered_product_count' => (int) ($filProd[$producer] ?? 0),
                        'is_available' => ((int) ($filProd[$producer] ?? 0)) > 0,
                        'is_filtered_out' => ((int) ($filProd[$producer] ?? 0)) === 0 && (int) $cnt > 0,
                        'is_invalid' => false
                    ];
                }
                $facets = new Facets($unfilteredFacetMap, $filteredFacetMap, $combined);
            } else {
                // keys only
                $unfProd = $this->computeProducerCounts($idsUnfilteredForProducers);
                $unfilteredFacetMap = [];
                $filteredFacetMap = [];
                foreach ($unfProd as $producer => $cnt) {
                    $unfilteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => 0];
                    $filteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => 0];
                }
                $combined = [];
                foreach ($unfProd as $producer => $cnt) {
                    $combined[] = [
                        'producer' => (string) $producer,
                        'total_product_count' => 0,
                        'filtered_product_count' => 0,
                        'is_available' => true,
                        'is_filtered_out' => false,
                        'is_invalid' => false
                    ];
                }
                $facets = new Facets($unfilteredFacetMap, $filteredFacetMap, $combined);
            }

            $hasUnmatched = !empty($criteria['producers']) && (count($idsFilteredForProducers) < count($idsUnfilteredForProducers));
            $numUnmatched = $hasUnmatched ? (count($idsUnfilteredForProducers) - count($idsFilteredForProducers)) : 0;
            $result = new SearchResult($idsFilteredForProducers, $facets, $hasUnmatched, $numUnmatched, count($idsUnfilteredForProducers), count($idsFilteredForProducers));
            $result->setNumProductsUnfiltered(count($idsUnfilteredForProducers));
            $result->setNumProductsFiltered(count($idsFilteredForProducers));
            return $result;
        }

        // Load attribute pairs for products and variants in the base candidate set (batched, O(1) lookups)
        $attributeData = $this->loadAttributeDataForProducts($baseCandidateIds);

        // Discover available attribute pairs (keys-only) over base candidates
        $availablePairs = $this->computeAvailablePairsKeysOnly($attributeData);

        // Dismiss invalid attribute filters (not available under current base criteria)
        [$effectiveFilters, $dismissedFilters] = $this->dismissInvalidAttributeFilters($attributeFilters, $availablePairs);

        // Apply ES-style attribute matching over base candidates (product-level OR single-variant match)
        $filteredIds = $this->applyAttributeFilters($baseCandidateIds, $attributeData, $effectiveFilters);

        // Build facets
        if (!$activateFacets) {
            $facets = new Facets([], [], []);
            $result = new SearchResult($filteredIds, $facets, false, 0, count($baseCandidateIds), count($filteredIds));
            $result->setNumProductsUnfiltered(count($baseCandidateIds));
            $result->setNumProductsFiltered(count($filteredIds));
            return $result;
        }

        if ($activateMatchEstimates) {
            // Unfiltered facet counts over base candidates
            $unfilteredFacetMap = $this->computeFacetCounts($baseCandidateIds, $attributeData);
            // Reuse unfiltered counts if no attribute filters are active
            $filteredFacetMap = empty($effectiveFilters)
                ? $unfilteredFacetMap
                : $this->computeFacetCounts($filteredIds, $attributeData);
            $combined = $this->combineFacetData($unfilteredFacetMap, $filteredFacetMap, $dismissedFilters);

            // Producer facets (only if producer field is published)
            if ($this->producerFilterFieldExists()) {
                $unfProd = $this->computeProducerCounts($baseCandidateIds);
                $filProd = $this->computeProducerCounts($filteredIds);
                foreach ($unfProd as $producer => $count) {
                    $filteredCount = (int) ($filProd[$producer] ?? 0);
                    $combined[] = [
                        'producer' => (string) $producer,
                        'total_product_count' => (int) $count,
                        'filtered_product_count' => (int) $filteredCount,
                        'is_available' => $filteredCount > 0,
                        'is_filtered_out' => $filteredCount === 0 && $count > 0,
                        'is_invalid' => false
                    ];
                }
                // Also expose raw producer maps inside unfiltered/filtered arrays for presenter
                foreach ($unfProd as $producer => $count) {
                    $unfilteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => (int) $count];
                }
                foreach ($filProd as $producer => $count) {
                    $filteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => (int) $count];
                }
            }
            $facetData = new Facets($unfilteredFacetMap, $filteredFacetMap, $combined);
            if ($removeImpossibleOptions) {
                $facetData = $this->removeImpossibleOptions($facetData);
            }
        } else {
            // Keys-only mode: use availablePairs for both sets and synthesize combined
            $unfilteredKeysOnly = $this->facetKeysOnlyFromAvailable($availablePairs);
            $filteredKeysOnly = $unfilteredKeysOnly;
            $combined = $this->combineFacetDataKeysOnly($unfilteredKeysOnly, $dismissedFilters);
            if ($this->producerFilterFieldExists()) {
                $unfProd = $this->computeProducerCounts($baseCandidateIds);
                foreach ($unfProd as $producer => $count) {
                    $combined[] = [
                        'producer' => (string) $producer,
                        'total_product_count' => 0,
                        'filtered_product_count' => 0,
                        'is_available' => true,
                        'is_filtered_out' => false,
                        'is_invalid' => false
                    ];
                }
                foreach ($unfProd as $producer => $count) {
                    $unfilteredKeysOnly[] = ['producer' => (string) $producer, 'product_count' => 0];
                    $filteredKeysOnly[] = ['producer' => (string) $producer, 'product_count' => 0];
                }
            }
            $facetData = new Facets($unfilteredKeysOnly, $filteredKeysOnly, $combined);
        }

        // Unmatched counts (attribute filters vs. base criteria)
        $hasUnmatched = !empty($effectiveFilters) && (count($filteredIds) < count($baseCandidateIds));
        $numUnmatched = $hasUnmatched ? (count($baseCandidateIds) - count($filteredIds)) : 0;

        $result = new SearchResult(
            $filteredIds,
            $facetData,
            $hasUnmatched,
            $numUnmatched,
            count($baseCandidateIds),
            count($filteredIds)
        );
        $result->setNumProductsUnfiltered(count($baseCandidateIds));
        $result->setNumProductsFiltered(count($filteredIds));
        return $result;
    }

    private function attributeFilterFieldsExist(): bool
    {
        try {
            $qb = $this->connection->createQueryBuilder();
            $qb->select('COUNT(*) AS cnt')
                ->from('tl_ls_shop_filter_fields')
                ->where("published = '1'")
                ->andWhere("dataSource = 'attribute'");
            $cnt = (int) ($qb->executeQuery()->fetchOne() ?? 0);
            return $cnt > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function producerFilterFieldExists(): bool
    {
        try {
            $qb = $this->connection->createQueryBuilder();
            $qb->select('COUNT(*) AS cnt')
                ->from('tl_ls_shop_filter_fields')
                ->where("published = '1'")
                ->andWhere("dataSource = 'producer'");
            return (int) ($qb->executeQuery()->fetchOne() ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function computeProducerCounts(array $productIds): array
    {
        if (empty($productIds)) { return []; }
        $ids = array_values(array_map('intval', $productIds));
        $batchSize = 1000;
        $counts = [];
        for ($offset = 0, $n = count($ids); $offset < $n; $offset += $batchSize) {
            $chunk = array_slice($ids, $offset, $batchSize);
            $qb = $this->connection->createQueryBuilder();
            $qb->select('LOWER(COALESCE(p.lsShopProductProducer, "")) AS producer', 'COUNT(p.id) AS cnt')
                ->from('tl_ls_shop_product', 'p')
                ->where($qb->expr()->in('p.id', ':ids'))
                ->groupBy('producer')
                ->setParameter('ids', $chunk, Connection::PARAM_INT_ARRAY);
            foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
                $producer = (string) ($row['producer'] ?? '');
                if ($producer === '') { continue; }
                $counts[$producer] = ($counts[$producer] ?? 0) + (int) $row['cnt'];
            }
        }
        return $counts;
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

        // Producer filter (exact match on product-level producer)
        if (!empty($criteria['producers']) && is_array($criteria['producers'])) {
            $producers = array_values(array_filter(array_map(function ($p) { return strtolower(trim((string) $p)); }, $criteria['producers']), function ($v) { return $v !== ''; }));
            if (count($producers)) {
                $paramName = $this->nextParameterName();
                $qb->andWhere('LOWER(product.lsShopProductProducer) IN (:' . $paramName . ')');
                $parameters[$paramName] = $producers;
                $parameterTypes[$paramName] = Connection::PARAM_STR_ARRAY;
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
            $booleanQuery = $this->buildBooleanFulltextShouldQueryString($descriptiveTerms);
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
                $codeBoostAllTerms = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_allTerms'] ?? 100);
                $scoreExpression = sprintf(
                    '(%s) + CASE WHEN %s THEN %s ELSE 0 END',
                    $scoreExpression,
                    $codeWhereAll,
                    (string) $codeBoostAllTerms
                );
                $codeBoostAnyTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_anyTerm'] ?? 20);
                $scoreExpression = sprintf(
                    '(%s) + CASE WHEN %s THEN %s ELSE 0 END',
                    $scoreExpression,
                    $codeWhereAny,
                    (string) $codeBoostAnyTerm
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
                    $codeBoostExactTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_exactTerm'] ?? 150);
                    $scoreExpression = sprintf(
                        '(%s) + CASE WHEN %s THEN %s ELSE 0 END',
                        $scoreExpression,
                        $codeEqualsAny,
                        (string) $codeBoostExactTerm
                    );
                }

				// 2) The entire reassembled query equals the product code exactly (highest boost)
				if ($normalizedFullQuery !== '') {
					$eqAllParam = $this->nextParameterName();
					$parameters[$eqAllParam] = $normalizedFullQuery;
					$parameterTypes[$eqAllParam] = ParameterType::STRING;
					$codeEqualsFullExpr = sprintf('%s = :%s', $normalizedCodeExpr, $eqAllParam);
                    $codeBoostExactFull = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_code_boost_exactFullQuery'] ?? 300);
                    $scoreExpression = sprintf(
                        '(%s) + CASE WHEN %s THEN %s ELSE 0 END',
                        $scoreExpression,
                        $codeEqualsFullExpr,
                        (string) $codeBoostExactFull
                    );
				}

                // Debug projection: code contribution components
                if ($debugScoringEnabled) {
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_like_all', $codeWhereAll, (string) $codeBoostAllTerms));
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_like_any', $codeWhereAny, (string) $codeBoostAnyTerm));
                    if (isset($codeEqualsAny)) {
                        $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_eq_term', $codeEqualsAny, (string) $codeBoostExactTerm));
                    }
					if (isset($codeEqualsFullExpr)) {
						$qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_code_eq_full', $codeEqualsFullExpr, (string) $codeBoostExactFull));
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
                $producerBoostAllTerms = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_allTerms'] ?? 60);
                $producerBoostAnyTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_anyTerm'] ?? 10);
                $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerWhereAll, (string) $producerBoostAllTerms);
                $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerWhereAny, (string) $producerBoostAnyTerm);

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
                    $producerBoostExactTerm = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_exactTerm'] ?? 80);
                    $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerEqualsAny, (string) $producerBoostExactTerm);
                }

                // Entire normalized query equals producer exactly
                if ($normalizedFullQuery !== '') {
                    $eqAllParam = $this->nextParameterName();
                    $parameters[$eqAllParam] = $normalizedFullQuery;
                    $parameterTypes[$eqAllParam] = ParameterType::STRING;
                    $producerEqualsFullExpr = sprintf('LOWER(product.lsShopProductProducer) = :%s', $eqAllParam);
                    $producerBoostExactFull = (int) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_producer_boost_exactFullQuery'] ?? 160);
                    $scoreExpression = sprintf('(%s) + CASE WHEN %s THEN %s ELSE 0 END', $scoreExpression, $producerEqualsFullExpr, (string) $producerBoostExactFull);
                }

                // Debug projection for producer components
                if ($debugScoringEnabled) {
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_like_all', $producerWhereAll, (string) $producerBoostAllTerms));
                    $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_like_any', $producerWhereAny, (string) $producerBoostAnyTerm));
                    if (isset($producerEqualsAny)) {
                        $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_eq_term', $producerEqualsAny, (string) $producerBoostExactTerm));
                    }
                    if (isset($producerEqualsFullExpr)) {
                        $qb->addSelect(sprintf('CASE WHEN %s THEN %s ELSE 0 END AS dbg_producer_eq_full', $producerEqualsFullExpr, (string) $producerBoostExactFull));
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

    private function prepareCriteria(array $criteria): array
    {
        return $criteria;
    }

    private function prepareBaseCriteria(array $criteria): array
    {
        $base = $criteria;
        unset($base['attributes']);
        return $base;
    }

    /**
     * Normalize incoming attribute filters into an array of [attribute_id=>int, value_id=>int].
     * Accepts both associative and loosely-typed entries.
     */
    private function normalizeAttributeFilters($raw): array
    {
        $filters = [];
        if (!is_array($raw)) {
            return $filters;
        }
        foreach ($raw as $f) {
            if (!is_array($f)) { continue; }
            $attr = isset($f['attribute_id']) ? (int) $f['attribute_id'] : (isset($f[0]) ? (int) $f[0] : 0);
            $val = isset($f['value_id']) ? (int) $f['value_id'] : (isset($f[1]) ? (int) $f[1] : 0);
            if ($attr > 0 && $val > 0) {
                $filters[] = ['attribute_id' => $attr, 'value_id' => $val];
            }
        }
        return $filters;
    }

    /**
     * Load attribute JSON for products and variants and precompute pairs.
     * For parity with ES, product-level pairs are empty when a product has variants.
     *
     * @return array<int, array{hasVariants:bool, productPairs:array<int,string>, variants:array<int, array<int,string>>}>
     */
    private function loadAttributeDataForProducts(array $productIds): array
    {
        if (empty($productIds)) { return []; }

        $ids = array_values(array_map('intval', $productIds));
        $byId = [];
        $productJsonById = [];

        // Initialize result structure for all ids to keep order stable
        foreach ($ids as $id) {
            $byId[$id] = [
                'hasVariants' => false,
                'productPairs' => [],
                'variants' => []
            ];
        }

        // Batch size to keep IN lists and memory reasonable
        $batchSize = 1000;

        // Load product-level JSON in batches
        for ($offset = 0, $n = count($ids); $offset < $n; $offset += $batchSize) {
            $chunk = array_slice($ids, $offset, $batchSize);
            $qp = $this->connection->createQueryBuilder();
            $qp->select('p.id', 'p.lsShopProductAttributesValues')
                ->from('tl_ls_shop_product', 'p')
                ->where($qp->expr()->in('p.id', ':ids'))
                ->setParameter('ids', $chunk, Connection::PARAM_INT_ARRAY);
            $rows = $qp->executeQuery()->fetchAllAssociative();
            foreach ($rows as $row) {
                $pid = (int) $row['id'];
                $productJsonById[$pid] = $row['lsShopProductAttributesValues'] ?? null;
                $byId[$pid]['productPairs'] = $this->pairsFromAttributesJson($productJsonById[$pid]);
            }
        }

        // Load published variants for those products in batches
        for ($offset = 0, $n = count($ids); $offset < $n; $offset += $batchSize) {
            $chunk = array_slice($ids, $offset, $batchSize);
            $qv = $this->connection->createQueryBuilder();
            $qv->select('v.id', 'v.pid', 'v.lsShopProductVariantAttributesValues')
                ->from('tl_ls_shop_variant', 'v')
                ->where($qv->expr()->in('v.pid', ':ids'))
                ->andWhere("v.published = '1'")
                ->orderBy('v.pid', 'ASC')
                ->setParameter('ids', $chunk, Connection::PARAM_INT_ARRAY);
            $rows = $qv->executeQuery()->fetchAllAssociative();
            foreach ($rows as $row) {
                $pid = (int) $row['pid'];
                if (!isset($byId[$pid])) { continue; }
                $byId[$pid]['hasVariants'] = true;
                $effectiveJson = $this->mergeVariantAndProductAttributesJson(
                    $row['lsShopProductVariantAttributesValues'] ?? null,
                    $productJsonById[$pid] ?? null
                );
                $byId[$pid]['variants'][(int) $row['id']] = $this->pairsFromAttributesJson($effectiveJson);
            }
        }

        // ES-style parity: clear product-level pairs if there are variants
        foreach ($byId as $pid => &$info) {
            if ($info['hasVariants']) {
                $info['productPairs'] = [];
            }
        }
        unset($info);

        return $byId;
    }

    // removed attributesJsonForProduct() – now we use an O(1) map

    /**
     * Convert JSON like [[attrId, valueId], ...] to set of pair keys 'attr:value'.
     *
     * @return array<int,string> list of pair keys
     */
    private function pairsFromAttributesJson(?string $json): array
    {
        $pairs = [];
        if ($json === null || $json === '') { return $pairs; }
        $data = json_decode($json, true);
        if (!is_array($data)) { return $pairs; }
        $seen = [];
        foreach ($data as $assign) {
            if (!is_array($assign) || !isset($assign[0], $assign[1])) { continue; }
            $key = ((int) $assign[0]) . ':' . ((int) $assign[1]);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $pairs[] = $key;
            }
        }
        return $pairs;
    }

    /**
     * Merge variant and product attribute JSON and deduplicate, like ES Sync does.
     */
    private function mergeVariantAndProductAttributesJson(?string $variantJson, ?string $productJson): ?string
    {
        if ($variantJson === null || $variantJson === '') { return $productJson; }
        if ($productJson === null || $productJson === '') { return $variantJson; }
        $variant = json_decode($variantJson, true);
        $product = json_decode($productJson, true);
        if (!is_array($variant)) { $variant = []; }
        if (!is_array($product)) { $product = []; }
        $merged = array_merge($variant, $product);
        $unique = [];
        $out = [];
        foreach ($merged as $assign) {
            if (!is_array($assign) || !isset($assign[0], $assign[1])) { continue; }
            $k = ((int) $assign[0]) . ':' . ((int) $assign[1]);
            if (!isset($unique[$k])) {
                $unique[$k] = true;
                $out[] = [(int) $assign[0], (int) $assign[1]];
            }
        }
        return json_encode($out);
    }

    /**
     * Compute keys-only available attribute pairs over base candidates.
     * Returns array of ['attribute_id'=>int,'value_id'=>int] entries.
     */
    private function computeAvailablePairsKeysOnly(array $attributeData): array
    {
        $keys = [];
        foreach ($attributeData as $pid => $info) {
            // Product-level pairs (only when no variants)
            if (!$info['hasVariants']) {
                foreach ($info['productPairs'] as $pairKey) {
                    $keys[$pairKey] = true;
                }
            }
            // Variant effective pairs
            foreach ($info['variants'] as $variantId => $variantPairs) {
                foreach ($variantPairs as $pairKey) {
                    $keys[$pairKey] = true;
                }
            }
        }
        $out = [];
        foreach (array_keys($keys) as $k) {
            [$a, $v] = array_map('intval', explode(':', $k, 2));
            $out[] = ['attribute_id' => $a, 'value_id' => $v];
        }
        return $out;
    }

    /**
     * Drop attribute filters not present in available pairs.
     *
     * @return array{0: array<int,array{attribute_id:int,value_id:int}>, 1: array<int,array{attribute_id:int,value_id:int}>}
     */
    private function dismissInvalidAttributeFilters(array $filters, array $availablePairs): array
    {
        if (empty($filters)) { return [$filters, []]; }
        $available = [];
        foreach ($availablePairs as $p) {
            $available[(int)$p['attribute_id'] . ':' . (int)$p['value_id']] = true;
        }
        $effective = [];
        $dismissed = [];
        foreach ($filters as $f) {
            $key = ((int)$f['attribute_id']) . ':' . ((int)$f['value_id']);
            if (isset($available[$key])) { $effective[] = $f; } else { $dismissed[] = $f; }
        }
        return [$effective, $dismissed];
    }

    /**
     * ES semantics: A product matches if (all pairs at product level) OR (exists a single variant where all pairs are present).
     */
    private function applyAttributeFilters(array $baseCandidateIds, array $attributeData, array $filters): array
    {
        if (empty($filters)) { return $baseCandidateIds; }
        // Convert filters to set of keys for quick contains checks
        $required = [];
        foreach ($filters as $f) { $required[((int)$f['attribute_id']) . ':' . ((int)$f['value_id'])] = true; }

        $result = [];
        foreach ($baseCandidateIds as $pid) {
            $info = $attributeData[$pid] ?? null;
            if ($info === null) { continue; }

            // Product-level path (only if pairs exist, typically when no variants)
            if (!empty($info['productPairs'])) {
                $set = array_fill_keys($info['productPairs'], true);
                $ok = true;
                foreach ($required as $rk => $_) { if (!isset($set[$rk])) { $ok = false; break; } }
                if ($ok) { $result[] = $pid; continue; }
            }

            // Variant-level path: any single variant that contains all required pairs
            $matchedByVariant = false;
            foreach ($info['variants'] as $variantPairs) {
                if (empty($variantPairs)) { continue; }
                $set = array_fill_keys($variantPairs, true);
                $ok = true;
                foreach ($required as $rk => $_) { if (!isset($set[$rk])) { $ok = false; break; } }
                if ($ok) { $matchedByVariant = true; break; }
            }
            if ($matchedByVariant) { $result[] = $pid; }
        }
        return $result;
    }

    /**
     * Compute facet counts: map 'attr:value' => ['attribute_id'=>int,'value_id'=>int,'product_count'=>int].
     * Counts products distinctly; product-level pairs contribute only if the product has no variants.
     */
    private function computeFacetCounts(array $productIds, array $attributeData): array
    {
        $pairToProductSet = [];
        foreach ($productIds as $pid) {
            $info = $attributeData[$pid] ?? null;
            if ($info === null) { continue; }
            if (!$info['hasVariants']) {
                foreach ($info['productPairs'] as $pairKey) {
                    if (!isset($pairToProductSet[$pairKey])) { $pairToProductSet[$pairKey] = []; }
                    $pairToProductSet[$pairKey][$pid] = true;
                }
            }
            foreach ($info['variants'] as $variantPairs) {
                foreach ($variantPairs as $pairKey) {
                    if (!isset($pairToProductSet[$pairKey])) { $pairToProductSet[$pairKey] = []; }
                    $pairToProductSet[$pairKey][$pid] = true;
                }
            }
        }

        $out = [];
        foreach ($pairToProductSet as $pairKey => $productSet) {
            [$a, $v] = array_map('intval', explode(':', $pairKey, 2));
            $out[$pairKey] = [
                'attribute_id' => $a,
                'value_id' => $v,
                'product_count' => count($productSet)
            ];
        }
        return $out;
    }

    private function combineFacetData(array $unfilteredFacetMap, array $filteredFacetMap, array $dismissedFilters = []): array
    {
        $combined = [];
        foreach ($unfilteredFacetMap as $pairKey => $entry) {
            $filteredCount = $filteredFacetMap[$pairKey]['product_count'] ?? 0;
            $combined[] = [
                'attribute_id' => $entry['attribute_id'],
                'value_id' => $entry['value_id'],
                'total_product_count' => $entry['product_count'],
                'filtered_product_count' => $filteredCount,
                'is_available' => $filteredCount > 0,
                'is_filtered_out' => $filteredCount === 0 && $entry['product_count'] > 0,
                'is_invalid' => false
            ];
        }
        foreach ($dismissedFilters as $f) {
            $combined[] = [
                'attribute_id' => (int) $f['attribute_id'],
                'value_id' => (int) $f['value_id'],
                'total_product_count' => 0,
                'filtered_product_count' => 0,
                'is_available' => false,
                'is_filtered_out' => false,
                'is_invalid' => true
            ];
        }
        return $combined;
    }

    private function combineFacetDataKeysOnly(array $unfilteredFacetsKeysOnly, array $dismissedFilters = []): array
    {
        $combined = [];
        foreach ($unfilteredFacetsKeysOnly as $facet) {
            $combined[] = [
                'attribute_id' => (int) $facet['attribute_id'],
                'value_id' => (int) $facet['value_id'],
                'total_product_count' => 0,
                'filtered_product_count' => 0,
                'is_available' => true,
                'is_filtered_out' => false,
                'is_invalid' => false
            ];
        }
        foreach ($dismissedFilters as $filter) {
            $combined[] = [
                'attribute_id' => (int) $filter['attribute_id'],
                'value_id' => (int) $filter['value_id'],
                'total_product_count' => 0,
                'filtered_product_count' => 0,
                'is_available' => false,
                'is_filtered_out' => false,
                'is_invalid' => true
            ];
        }
        return $combined;
    }

    private function removeImpossibleOptions(Facets $facetData): Facets
    {
        $combinedFacets = $facetData->getCombinedFacets();
        $allowed = [];
        foreach ($combinedFacets as $entry) {
            $attr = $entry['attribute_id'] ?? null;
            $val = $entry['value_id'] ?? null;
            $isInvalid = $entry['is_invalid'] ?? false;
            $filteredCount = $entry['filtered_product_count'] ?? 0;
            if ($attr === null || $val === null) { continue; }
            if ($isInvalid) { continue; }
            if ($filteredCount <= 0) { continue; }
            $allowed[$attr . ':' . $val] = true;
        }

        $unfiltered = $facetData->getUnfilteredFacets();
        $filtered = $facetData->getFilteredFacets();

        $unfiltered = array_filter($unfiltered, function ($entry, $key) use ($allowed) {
            $k = is_string($key) ? $key : (($entry['attribute_id'] ?? '') . ':' . ($entry['value_id'] ?? ''));
            return isset($allowed[$k]);
        }, ARRAY_FILTER_USE_BOTH);

        $filtered = array_filter($filtered, function ($entry, $key) use ($allowed) {
            $k = is_string($key) ? $key : (($entry['attribute_id'] ?? '') . ':' . ($entry['value_id'] ?? ''));
            return isset($allowed[$k]);
        }, ARRAY_FILTER_USE_BOTH);

        $combinedFiltered = array_values(array_filter($combinedFacets, function ($entry) use ($allowed) {
            $k = ($entry['attribute_id'] ?? '') . ':' . ($entry['value_id'] ?? '');
            return isset($allowed[$k]);
        }));

        return new Facets($unfiltered, $filtered, $combinedFiltered);
    }

    private function facetKeysOnlyFromAvailable(array $availablePairs): array
    {
        $out = [];
        foreach ($availablePairs as $p) {
            $out[] = [
                'attribute_id' => (int) $p['attribute_id'],
                'value_id' => (int) $p['value_id'],
                'product_count' => 0,
            ];
        }
        return $out;
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
            $t = str_replace(['+','-','~','<','>','(',')','"','\''], ' ', $t);
            $t = preg_replace('/\s+/', ' ', $t);
            $t = trim($t);
            if ($t === '') { continue; }
            $parts[] = '+' . $t . '*';
        }
        return implode(' ', $parts);
    }

    // Build a boolean-mode query string with optional terms: "term1* term2* ..."
    private function buildBooleanFulltextShouldQueryString(array $terms): string
    {
        $parts = [];
        foreach ($terms as $t) {
            $t = trim((string) $t);
            if ($t === '') { continue; }
            // Strip characters that have special boolean meaning to avoid user injection of operators
            $t = str_replace(['+','-','~','<','>','(',')','"','\''], ' ', $t);
            $t = preg_replace('/\s+/', ' ', $t);
            $t = trim($t);
            if ($t === '') { continue; }
            $parts[] = $t . '*';
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
            case 'title': return (float) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_weight_title'] ?? 5.0);
            case 'keywords': return (float) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_weight_keywords'] ?? 3.0);
            case 'shortDescription': return (float) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_weight_shortDescription'] ?? 2.0);
            case 'description': return (float) ($GLOBALS['TL_CONFIG']['ls_shop_dmysql_weight_description'] ?? 1.5);
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


