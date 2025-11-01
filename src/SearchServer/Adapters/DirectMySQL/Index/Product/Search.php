<?php

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use LeadingSystems\MerconisBundle\ProductSearch\Adapter;
use LeadingSystems\MerconisBundle\ProductSearch\Facets;
use LeadingSystems\MerconisBundle\ProductSearch\SearchResult;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\CommonInterface;
use LeadingSystems\MerconisBundle\SearchServer\AdapterInterfaces\IndexSearchInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Client;
use LeadingSystems\MerconisBundle\SearchServer\Traits\AdapterCommonTrait;
use Psr\Log\LoggerInterface;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\ClauseBuildResult;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\DescriptiveFulltextClauseBuilder;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\CodeClauseBuilder;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\ProducerClauseBuilder;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\PageConstraintBuilder;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\PublishedConstraintBuilder;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\ProducerExactFilterApplier;
use LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause\SortingApplier;

class Search implements CommonInterface, IndexSearchInterface
{
    use AdapterCommonTrait;

    private Connection $connection;
    private int $parameterCounter = 0;
    private ?array $productTableColumns = null;
    private LoggerInterface $logger;
    private string $projectDir;
    private string $environment;
    private array $dmysql_sortingInput = [];
    private array $dmysql_fixedSortingInput = [];

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
		$this->dmysql_sortingInput = $productSearchAdapter->getSortingCriteria();
		$this->dmysql_fixedSortingInput = $productSearchAdapter->getFixedSorting();

		$baseCriteria = $this->prepareBaseCriteria($criteria);
		$baseCandidateIds = $this->resolveBaseCandidates($baseCriteria, $language);

		$attributeFilters = $this->normalizeAttributeFilters($criteria['attributes'] ?? []);
		$ctx = [
			'language' => $language,
			'mode' => $this->determineFacetMode($activateFacets, $activateMatchEstimates),
			'removeImpossible' => $removeImpossibleOptions,
			'attributeFieldsPublished' => $this->attributeFilterFieldsExist(),
			'producerFieldPublished' => $this->producerFilterFieldExists(),
			'attributeFilters' => $attributeFilters,
			'hasAttributeFilters' => !empty($attributeFilters),
			'hasProducerFilters' => !empty($criteria['producers'] ?? [])
		];

		// Early exit: no facets and no attribute filters → just base ids with optional price sort
		if ($this->shouldReturnEarlyNoFacetsNoAttr($ctx)) {
			$ids = $this->applyPhpSorts($baseCandidateIds);
			$result = new SearchResult($ids);
			$total = count($baseCandidateIds);
			$result->setNumProductsUnfiltered($total);
			$result->setNumProductsFiltered($total);
			return $result;
		}

		// Producers-only path when attribute fields are not published
		if (!$ctx['attributeFieldsPublished']) {
			return $this->handleProducersOnlyFlow($baseCriteria, $baseCandidateIds, $ctx);
		}

		// Attribute pipeline
		$attributeData = $this->loadAttributeDataForProducts($baseCandidateIds);
		$availablePairs = $this->computeAvailablePairsKeysOnly($attributeData);
		[$effectiveFilters, $dismissedFilters] = $this->dismissInvalidAttributeFilters($attributeFilters, $availablePairs);
		$filteredIds = $this->applyAttributeFilters($baseCandidateIds, $attributeData, $effectiveFilters);

		$facetData = $this->buildFacetData(
			$attributeData,
			$baseCandidateIds,
			$filteredIds,
			$ctx,
			$effectiveFilters,
			$dismissedFilters,
			$availablePairs
		);

		$ids = $this->applyPhpSorts($filteredIds);
		return $this->finalizeResult($ids, $baseCandidateIds, $facetData, !empty($effectiveFilters));
    }



	private function resolveBaseCandidates(array $baseCriteria, string $language): array
	{
		return $this->fetchProductIdsByCriteria($baseCriteria, $language);
	}

	private function determineFacetMode(bool $activateFacets, bool $activateMatchEstimates): string
	{
		if (!$activateFacets) { return 'Disabled'; }
		return $activateMatchEstimates ? 'Counts' : 'KeysOnly';
	}

	private function shouldReturnEarlyNoFacetsNoAttr(array $ctx): bool
	{
		return $ctx['mode'] === 'Disabled' && !$ctx['hasAttributeFilters'];
	}

	private function handleProducersOnlyFlow(array $baseCriteria, array $baseIds, array $ctx): SearchResult
	{
		$criteriaNoProd = $baseCriteria;
		unset($criteriaNoProd['producers']);
		$idsUnfilteredForProducers = $this->resolveBaseCandidates($criteriaNoProd, $ctx['language']);
		$idsFilteredForProducers = $baseIds;

		$facets = null;
		if ($ctx['mode'] !== 'Disabled' && $ctx['producerFieldPublished']) {
			$facets = $this->buildProducerFacetData($idsUnfilteredForProducers, $idsFilteredForProducers, $ctx['mode']);
		} elseif ($ctx['mode'] !== 'Disabled' && !$ctx['producerFieldPublished']) {
			$facets = new Facets([], [], []);
		}

		$ids = $this->applyPhpSorts($idsFilteredForProducers);
		return $this->finalizeResultWithCustomUnfilteredBase(
			$ids,
			$idsUnfilteredForProducers,
			$idsFilteredForProducers,
			$facets,
			$ctx['hasProducerFilters']
		);
	}

	private function buildFacetData(
		array $attributeData,
		array $baseIds,
		array $filteredIds,
		array $ctx,
		array $effectiveFilters,
		array $dismissedFilters,
		array $availablePairs
	): Facets {
		if ($ctx['mode'] === 'Disabled') {
			return new Facets([], [], []);
		}

		if ($ctx['mode'] === 'KeysOnly') {
			$unfilteredKeysOnly = $this->facetKeysOnlyFromAvailable($availablePairs);
			$filteredKeysOnly = $unfilteredKeysOnly;
			$combined = $this->combineFacetDataKeysOnly($unfilteredKeysOnly, $dismissedFilters);

			if ($ctx['producerFieldPublished']) {
				[$prodUnf, $prodFil, $prodCombined] = $this->buildProducerFacetDataArraysKeysOnly($baseIds);
				$unfilteredKeysOnly = array_merge($unfilteredKeysOnly, $prodUnf);
				$filteredKeysOnly = array_merge($filteredKeysOnly, $prodFil);
				$combined = array_merge($combined, $prodCombined);
			}

			return new Facets($unfilteredKeysOnly, $filteredKeysOnly, $combined);
		}

		// Counts mode
		$unfilteredFacetMap = $this->computeFacetCounts($baseIds, $attributeData);
		$filteredFacetMap = empty($effectiveFilters) ? $unfilteredFacetMap : $this->computeFacetCounts($filteredIds, $attributeData);
		$combined = $this->combineFacetData($unfilteredFacetMap, $filteredFacetMap, $dismissedFilters);

		if ($ctx['producerFieldPublished']) {
			[$prodUnf, $prodFil, $prodCombined] = $this->buildProducerFacetDataArraysCounts($baseIds, $filteredIds);
			foreach ($prodCombined as $row) { $combined[] = $row; }
			foreach ($prodUnf as $row) { $unfilteredFacetMap[] = $row; }
			foreach ($prodFil as $row) { $filteredFacetMap[] = $row; }
		}

		$facetData = new Facets($unfilteredFacetMap, $filteredFacetMap, $combined);
		return $ctx['removeImpossible'] ? $this->removeImpossibleOptions($facetData) : $facetData;
	}

	private function buildProducerFacetData(array $idsUnfiltered, array $idsFiltered, string $mode): Facets
	{
		if ($mode === 'KeysOnly') {
			[$unf, $fil, $combined] = $this->buildProducerFacetDataArraysKeysOnly($idsUnfiltered);
			return new Facets($unf, $fil, $combined);
		}
		[$unf, $fil, $combined] = $this->buildProducerFacetDataArraysCounts($idsUnfiltered, $idsFiltered);
		return new Facets($unf, $fil, $combined);
	}

	private function buildProducerFacetDataArraysCounts(array $idsUnfiltered, array $idsFiltered): array
	{
		$unfProd = $this->computeProducerCounts($idsUnfiltered);
		$filProd = $this->computeProducerCounts($idsFiltered);
		$unfilteredFacetMap = [];
		$filteredFacetMap = [];
		$combined = [];
		foreach ($unfProd as $producer => $cnt) {
			$unfilteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => (int) $cnt];
		}
		foreach ($filProd as $producer => $cnt) {
			$filteredFacetMap[] = ['producer' => (string) $producer, 'product_count' => (int) $cnt];
		}
		foreach ($unfProd as $producer => $cnt) {
			$filteredCount = (int) ($filProd[$producer] ?? 0);
			$combined[] = [
				'producer' => (string) $producer,
				'total_product_count' => (int) $cnt,
				'filtered_product_count' => $filteredCount,
				'is_available' => $filteredCount > 0,
				'is_filtered_out' => $filteredCount === 0 && $cnt > 0,
				'is_invalid' => false
			];
		}
		return [$unfilteredFacetMap, $filteredFacetMap, $combined];
	}

	private function buildProducerFacetDataArraysKeysOnly(array $idsUnfiltered): array
	{
		$unfProd = $this->computeProducerCounts($idsUnfiltered);
		$unfiltered = [];
		$filtered = [];
		$combined = [];
		foreach ($unfProd as $producer => $cnt) {
			$unfiltered[] = ['producer' => (string) $producer, 'product_count' => 0];
			$filtered[] = ['producer' => (string) $producer, 'product_count' => 0];
			$combined[] = [
				'producer' => (string) $producer,
				'total_product_count' => 0,
				'filtered_product_count' => 0,
				'is_available' => true,
				'is_filtered_out' => false,
				'is_invalid' => false
			];
		}
		return [$unfiltered, $filtered, $combined];
	}

	private function maybePriceSort(array $ids): array
	{
		$dir = $this->resolvePriceSortDirection($this->dmysql_sortingInput);
		return $dir ? $this->sortIdsByPrice($ids, $dir) : $ids;
	}

    private function maybeFixedSort(array $ids): array
    {
        if (empty($this->dmysql_fixedSortingInput)) { return $ids; }
        return $this->applyFixedSorting($ids, $this->dmysql_fixedSortingInput);
    }

    private function applyPhpSorts(array $ids): array
    {
        $ids = $this->maybePriceSort($ids);
        $ids = $this->maybeFixedSort($ids);
        return $ids;
    }

	private function finalizeResult(array $ids, array $baseIds, ?Facets $facets, bool $hasFilters): SearchResult
	{
		[$hasUnmatched, $numUnmatched] = $this->computeUnmatched($baseIds, $ids, $hasFilters);
		$result = new SearchResult($ids, $facets, $hasUnmatched, $numUnmatched, count($baseIds), count($ids));
		$result->setNumProductsUnfiltered(count($baseIds));
		$result->setNumProductsFiltered(count($ids));
		return $result;
	}

	private function finalizeResultWithCustomUnfilteredBase(array $ids, array $unfilteredBase, array $filteredBase, ?Facets $facets, bool $hasFilters): SearchResult
	{
		[$hasUnmatched, $numUnmatched] = $this->computeUnmatched($unfilteredBase, $filteredBase, $hasFilters);
		$result = new SearchResult($ids, $facets, $hasUnmatched, $numUnmatched, count($unfilteredBase), count($filteredBase));
		$result->setNumProductsUnfiltered(count($unfilteredBase));
		$result->setNumProductsFiltered(count($filteredBase));
		return $result;
	}

	private function computeUnmatched(array $unfilteredIds, array $filteredIds, bool $hasFilters): array
	{
		if (!$hasFilters) { return [false, 0]; }
		$hasUnmatched = count($filteredIds) < count($unfilteredIds);
		return [$hasUnmatched, $hasUnmatched ? (count($unfilteredIds) - count($filteredIds)) : 0];
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
                ->setParameter('ids', $chunk, ArrayParameterType::INTEGER);
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

		$parameters = [];
		$parameterTypes = [];

		// Direct ID filter (curated restriction)
		if (array_key_exists('id', $criteria)) {
			$idsRaw = $criteria['id'];
			if (!is_array($idsRaw)) { $idsRaw = [$idsRaw]; }
			$ids = array_values(array_unique(array_filter(array_map('intval', $idsRaw), fn($v) => $v > 0)));
			if (!count($ids)) { return []; }
			$qb->andWhere($qb->expr()->in('product.id', ':idsExplicit'));
			$parameters['idsExplicit'] = $ids;
			$parameterTypes['idsExplicit'] = ArrayParameterType::INTEGER;
		}

		// Constraints: pages, producers (exact), published
		$pageBuilder = new PageConstraintBuilder();
		$pageResult = $pageBuilder->apply($qb, $criteria['pages'] ?? null);
		if (($criteria['pages'] ?? null) !== null && $pageResult['noMatch'] === true) {
			return [];
		}
		$needsGroupBy = $pageResult['needsGroupBy'] === true;
		$parameters = array_merge($parameters, $pageResult['params']);
		$parameterTypes = array_merge($parameterTypes, $pageResult['types']);

		$producerExact = new ProducerExactFilterApplier();
		$producerExactRes = $producerExact->apply($qb, $criteria['producers'] ?? null);
		$parameters = array_merge($parameters, $producerExactRes['params']);
		$parameterTypes = array_merge($parameterTypes, $producerExactRes['types']);

		$publishedBuilder = new PublishedConstraintBuilder();
		$publishedWhere = $publishedBuilder->build($criteria['published'] ?? null);
		if ($publishedWhere !== null) {
			$qb->andWhere($publishedWhere);
		}

		// Fulltext parsing and term partitioning
		$fulltext = $criteria['fulltext'] ?? '';
		$fulltextComponents = $this->parseFulltextCriteria((string) $fulltext);
		$reassembledParts = [];
		foreach ($fulltextComponents as $comp) {
			$t = trim((string) ($comp['text'] ?? ''));
			if ($t !== '') { $reassembledParts[] = $t; }
		}
		$normalizedFullQuery = strtolower(implode(' ', $reassembledParts));

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
					if (in_array($canonical, ['title','keywords','shortdescription','description'], true)) { $includeInDescriptive = true; }
					if ($canonical === 'lsshopproductproducer') { $includeInProducer = true; }
					if ($canonical === 'lsshopproductcode') { $includeInCode = true; }
				}
				if ($includeInDescriptive) { $descriptiveTerms[] = $term; }
				if ($includeInCode) { $codeTerms[] = $term; }
				if ($includeInProducer) { $producerTerms[] = $term; }
			}
		}

		$whereParts = [];
		$scoreExpressionParts = [];

		// Descriptive fulltext clause
		$descriptiveColumns = $this->getDescriptiveColumnsForLanguage($language);
		if (count($descriptiveTerms) && count($descriptiveColumns)) {
			$booleanQuery = $this->buildBooleanFulltextShouldQueryString($descriptiveTerms);
			$descBuilder = new DescriptiveFulltextClauseBuilder();
			$descRes = $descBuilder->build(
				$descriptiveTerms,
				$descriptiveColumns,
				$booleanQuery,
				$debugScoringEnabled,
				fn() => $this->nextParameterName(),
				fn(string $col) => $this->getWeightForBaseColumn($col),
				$qb
			);
			if ($descRes->getWhereSql() !== null) { $whereParts[] = $descRes->getWhereSql(); }
			$scoreExpressionParts = array_merge($scoreExpressionParts, $descRes->getScoreAdditions());
			$parameters = array_merge($parameters, $descRes->getParams());
			$parameterTypes = array_merge($parameterTypes, $descRes->getParamTypes());
		}

		// Code clause
		if (count($codeTerms)) {
			$codeBuilder = new CodeClauseBuilder();
			$normalizedCodeExpr = $this->buildNormalizedProductCodeExpr();
			$codeRes = $codeBuilder->build(
				$codeTerms,
				$normalizedFullQuery,
				$normalizedCodeExpr,
				$debugScoringEnabled,
				fn() => $this->nextParameterName(),
				fn(string $t) => $this->createLikePattern($t),
				$qb
			);
			if ($codeRes->getWhereSql() !== null) { $whereParts[] = $codeRes->getWhereSql(); }
			$scoreExpressionParts = array_merge($scoreExpressionParts, $codeRes->getScoreAdditions());
			$parameters = array_merge($parameters, $codeRes->getParams());
			$parameterTypes = array_merge($parameterTypes, $codeRes->getParamTypes());
		}

		// Producer clause
		if (count($producerTerms)) {
			$producerBuilder = new ProducerClauseBuilder();
			$producerRes = $producerBuilder->build(
				$producerTerms,
				$normalizedFullQuery,
				$debugScoringEnabled,
				fn() => $this->nextParameterName(),
				fn(string $t) => $this->createLikePattern($t),
				$qb
			);
			if ($producerRes->getWhereSql() !== null) { $whereParts[] = $producerRes->getWhereSql(); }
			$scoreExpressionParts = array_merge($scoreExpressionParts, $producerRes->getScoreAdditions());
			$parameters = array_merge($parameters, $producerRes->getParams());
			$parameterTypes = array_merge($parameterTypes, $producerRes->getParamTypes());
		}

		if (count($whereParts)) {
			$qb->andWhere('(' . implode(' OR ', $whereParts) . ')');
		}

		$scoreExpression = '0';
		if (count($scoreExpressionParts)) {
			$scoreExpression = '(' . implode(' + ', $scoreExpressionParts) . ')';
		}
		$qb->addSelect($scoreExpression . ' AS relevance');

		if ($debugScoringEnabled) {
			$qb->addSelect('product.lsShopProductCode AS dbg_product_code');
			$titleExpr = $this->resolveColumnExpression($this->fieldConfigurations['title'], $language);
			if ($titleExpr !== null) { $qb->addSelect($titleExpr . ' AS dbg_product_title'); }
		}

		if ($needsGroupBy) {
			$qb->groupBy('product.id');
		}

		// Sorting
		$sortingCriteria = $this->dmysql_sortingInput;
		$sortingApplier = new SortingApplier();
		$ignoredSorts = $sortingApplier->apply(
			$qb,
			$sortingCriteria,
			($scoreExpression !== '0'),
			$language,
			fn(array $crit, bool $hasScore, string $lang) => $this->buildSqlOrderByFromSorting($crit, $hasScore, $lang)
		);

		if (!empty($ignoredSorts) && !empty($GLOBALS['TL_CONFIG']['ls_shop_debugSearch'])) {
			try {
				$this->logger->notice('DirectMySQL sorting: ignored unsupported fields', [ 'ignored' => $ignoredSorts, 'received' => $sortingCriteria ]);
			} catch (\Throwable $e) {}
			try {
				$payload = json_encode(['ts' => gmdate('c'), 'event' => 'sorting_ignored_fields', 'ignored' => $ignoredSorts, 'received' => $sortingCriteria], JSON_UNESCAPED_SLASHES);
				@file_put_contents($this->resolveFallbackLogFilePath(), $payload."\n", FILE_APPEND);
			} catch (\Throwable $e) {}
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
		if (!count($rows)) { return []; }
		if ($debugScoringEnabled && count($rows)) { $this->logScoreBreakdown($rows); }
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
                ->setParameter('ids', $chunk, ArrayParameterType::INTEGER);
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
                ->setParameter('ids', $chunk, ArrayParameterType::INTEGER);
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

    /**
     * Detect whether sorting by price is requested and return direction 'ASC'|'DESC' or null.
     */
    private function resolvePriceSortDirection(array $sortingCriteria): ?string
    {
        if (!is_array($sortingCriteria) || !count($sortingCriteria)) { return null; }
        foreach ($sortingCriteria as $rule) {
            $field = strtolower(trim((string)($rule['field'] ?? '')));
            $dir = strtoupper(trim((string)($rule['direction'] ?? 'ASC')));
            if ($field === 'lsshopproductprice' || $field === 'price') {
                return $dir === 'DESC' ? 'DESC' : 'ASC';
            }
        }
        return null;
    }

	/**
	 * Compute product display prices for given product IDs, considering group overrides if enabled.
	 * Uses the cheapest published variant per product (respecting variant price type) when available,
	 * otherwise falls back to the product's own price.
	 * Returns map: productId => numeric display price.
	 */
	private function computeDisplayPricesForProducts(array $productIds): array
	{
		$displayPriceByProductId = [];
		if (empty($productIds)) { return $displayPriceByProductId; }

		$considerGroupPrices = !empty($GLOBALS['TL_CONFIG']['ls_shop_considerGroupPricesInFilterAndSorting']);
		$groupId = null;
		if ($considerGroupPrices) {
			try {
				$groupSettings = \Merconis\Core\ls_shop_generalHelper::getGroupSettings4User();
				$groupId = $groupSettings['id'] ?? null;
			} catch (\Throwable $e) {
				$groupId = null;
			}
		}

		$batchSize = 1000;
		// Pass 1: compute minimal display price from published variants per product
		for ($offset = 0, $n = count($productIds); $offset < $n; $offset += $batchSize) {
			$chunk = array_slice($productIds, $offset, $batchSize);
			$qv = $this->connection->createQueryBuilder();
			$variantSelects = [
				'v.id AS variant_id',
				'v.pid AS product_id',
				'v.lsShopVariantPrice',
				'v.lsShopVariantPriceType',
				'v.lsShopVariantCode',
				'p.lsShopProductPrice',
				'p.lsShopProductSteuersatz',
				'p.lsShopProductCode'
			];
			if ($considerGroupPrices) {
				for ($i = 1; $i <= 5; $i++) {
					$variantSelects[] = 'v.useGroupPrices_' . $i;
					$variantSelects[] = 'v.priceForGroups_' . $i;
					$variantSelects[] = 'v.lsShopVariantPrice_' . $i;
					$variantSelects[] = 'v.lsShopVariantPriceType_' . $i;
				}
				for ($i = 1; $i <= 5; $i++) {
					$variantSelects[] = 'p.useGroupPrices_' . $i;
					$variantSelects[] = 'p.priceForGroups_' . $i;
					$variantSelects[] = 'p.lsShopProductPrice_' . $i;
				}
			}
			$qv->select(...$variantSelects)
				->from('tl_ls_shop_variant', 'v')
				->innerJoin('v', 'tl_ls_shop_product', 'p', 'p.id = v.pid')
				->where($qv->expr()->in('v.pid', ':ids'))
				->andWhere("v.published = '1'")
				->orderBy('v.pid', 'ASC')
				->setParameter('ids', array_map('intval', $chunk), ArrayParameterType::INTEGER);

			$variantRows = $qv->executeQuery()->fetchAllAssociative();
			foreach ($variantRows as $row) {
				$pid = (int) ($row['product_id'] ?? 0);
				if (!$pid) { continue; }

				$productBasePrice = $row['lsShopProductPrice'] ?? null;
				if ($considerGroupPrices && $groupId) {
					try {
						$productStructuredGroupPrices = \Merconis\Core\ls_shop_generalHelper::getStructuredGroupPrices($row, 'product');
						if (isset($productStructuredGroupPrices[$groupId]['lsShopProductPrice'])) {
							$productBasePrice = $productStructuredGroupPrices[$groupId]['lsShopProductPrice'];
						}
					} catch (\Throwable $e) {}
				}

				$variantPrice = $row['lsShopVariantPrice'] ?? null;
				$variantPriceType = $row['lsShopVariantPriceType'] ?? null;
				if ($considerGroupPrices && $groupId) {
					try {
						$variantStructuredGroupPrices = \Merconis\Core\ls_shop_generalHelper::getStructuredGroupPrices($row, 'variant');
						if (isset($variantStructuredGroupPrices[$groupId])) {
							if (array_key_exists('lsShopVariantPrice', $variantStructuredGroupPrices[$groupId])) {
								$variantPrice = $variantStructuredGroupPrices[$groupId]['lsShopVariantPrice'];
							}
							if (array_key_exists('lsShopVariantPriceType', $variantStructuredGroupPrices[$groupId])) {
								$variantPriceType = $variantStructuredGroupPrices[$groupId]['lsShopVariantPriceType'];
							}
						}
					} catch (\Throwable $e) {}
				}

				if ($variantPrice === null) { continue; }

				try {
					$effectiveVariantBasePrice = \Merconis\Core\ls_shop_generalHelper::ls_calculateVariantPriceRegardingPriceType(
						$variantPriceType,
						$productBasePrice,
						$variantPrice
					);
				} catch (\Throwable $e) {
					$effectiveVariantBasePrice = $variantPrice;
				}

				try {
					$display = \Merconis\Core\ls_shop_generalHelper::getDisplayPrice(
						$effectiveVariantBasePrice,
						$row['lsShopProductSteuersatz'] ?? 0,
						true,
						$row['lsShopProductCode'] ?? null,
						$row['lsShopVariantCode'] ?? null
					);
					if (is_numeric($display)) {
						$numericDisplay = (float) $display;
						if (!isset($displayPriceByProductId[$pid]) || $numericDisplay < $displayPriceByProductId[$pid]) {
							$displayPriceByProductId[$pid] = $numericDisplay;
						}
					}
				} catch (\Throwable $e) {}
			}
		}

		// Pass 2: fallback to product base price for products without published variants or failed computations
		$missingProductIds = array_values(array_diff(array_map('intval', $productIds), array_keys($displayPriceByProductId)));
		if (!empty($missingProductIds)) {
			for ($offset = 0, $n = count($missingProductIds); $offset < $n; $offset += $batchSize) {
				$chunk = array_slice($missingProductIds, $offset, $batchSize);
				$qp = $this->connection->createQueryBuilder();
				$productSelects = [
					'p.id',
					'p.lsShopProductPrice',
					'p.lsShopProductSteuersatz',
					'p.lsShopProductCode'
				];
				if ($considerGroupPrices) {
					for ($i = 1; $i <= 5; $i++) {
						$productSelects[] = 'p.useGroupPrices_' . $i;
						$productSelects[] = 'p.priceForGroups_' . $i;
						$productSelects[] = 'p.lsShopProductPrice_' . $i;
					}
				}
				$qp->select(...$productSelects)
					->from('tl_ls_shop_product', 'p')
					->where($qp->expr()->in('p.id', ':ids'))
					->setParameter('ids', $chunk, ArrayParameterType::INTEGER);

				$productRows = $qp->executeQuery()->fetchAllAssociative();
				foreach ($productRows as $row) {
					$pid = (int) $row['id'];
					$price = $row['lsShopProductPrice'] ?? null;
					if ($considerGroupPrices && $groupId) {
						try {
							$structured = \Merconis\Core\ls_shop_generalHelper::getStructuredGroupPrices($row, 'product');
							if (isset($structured[$groupId]) && isset($structured[$groupId]['lsShopProductPrice'])) {
								$price = $structured[$groupId]['lsShopProductPrice'];
							}
						} catch (\Throwable $e) {}
					}
					if ($price === null) { continue; }
					try {
						$display = \Merconis\Core\ls_shop_generalHelper::getDisplayPrice(
							$price,
							$row['lsShopProductSteuersatz'] ?? 0,
							true,
							$row['lsShopProductCode'] ?? null
						);
						if (is_numeric($display)) {
							$displayPriceByProductId[$pid] = (float) $display;
						}
					} catch (\Throwable $e) {}
				}
			}
		}

		return $displayPriceByProductId;
	}

    /**
     * Helper: sort a list of product IDs by computed display price.
     */
    private function sortIdsByPrice(array $ids, string $direction): array
    {
        if (empty($ids)) { return $ids; }
        try {
            $priceById = $this->computeDisplayPricesForProducts($ids);
        } catch (\Throwable $e) {
            return $ids; // fail-safe: keep original order
        }
        $indexed = [];
        foreach ($ids as $idx => $pid) {
            $indexed[] = ['id' => (int) $pid, 'price' => $priceById[$pid] ?? null, 'idx' => $idx];
        }
        $dir = ($direction === 'DESC') ? 'DESC' : 'ASC';
        usort($indexed, function ($a, $b) use ($dir) {
            $pa = $a['price'];
            $pb = $b['price'];
            $aNull = ($pa === null);
            $bNull = ($pb === null);
            if ($aNull && $bNull) { return $a['idx'] <=> $b['idx']; }
            if ($aNull) { return 1; }
            if ($bNull) { return -1; }
            if ($pa == $pb) { return $a['idx'] <=> $b['idx']; }
            $cmp = ($pa <=> $pb);
            return $dir === 'DESC' ? -$cmp : $cmp;
        });
        return array_map(static function ($row) { return $row['id']; }, $indexed);
    }

    private function applyFixedSorting(array $ids, array $fixed): array
    {
        if (empty($fixed) || empty($ids)) { return $ids; }
        $present = [];
        foreach ($ids as $id) { $present[(int)$id] = true; }
        $out = [];
        foreach ($fixed as $id) {
            $iid = (int) $id;
            if (isset($present[$iid])) { $out[] = $iid; }
        }
        return $out;
    }

    /**
     * Build SQL ORDER BY parts from adapter-provided sorting rules.
     * Only DB-orderable fields are considered; others are returned in $ignored for optional debug logging.
     *
     * @param array $sortingCriteria [ [field=>string, direction=>ASC|DESC], ... ]
     * @param bool $hasScore whether a meaningful relevance score is available
     * @param string $language current search language
     * @return array{0: array<int, array{0:string,1:string}>, 1: array<int, string>} [orderBys, ignoredFields]
     */
    private function buildSqlOrderByFromSorting(array $sortingCriteria, bool $hasScore, string $language): array
    {
        $orderBys = [];
        $ignored = [];

        if (!is_array($sortingCriteria) || !count($sortingCriteria)) {
            return [$orderBys, $ignored];
        }

        foreach ($sortingCriteria as $rule) {
            $fieldRaw = isset($rule['field']) ? (string) $rule['field'] : '';
            $dirRaw = isset($rule['direction']) ? (string) $rule['direction'] : 'ASC';
            $field = strtolower(trim($fieldRaw));
            $dir = strtoupper(trim($dirRaw)) === 'DESC' ? 'DESC' : 'ASC';

            // Normalize aliases for sort fields
            switch ($field) {
                case 'priority':
                case 'relevance':
                    if ($hasScore) {
                        $orderBys[] = ['relevance', $dir];
                    } else {
                        $ignored[] = $fieldRaw;
                    }
                    break;

                case 'id':
                    $orderBys[] = ['product.id', $dir];
                    break;

                case 'title':
                    $titleExpr = $this->resolveColumnExpression($this->fieldConfigurations['title'], $language);
                    if ($titleExpr !== null) {
                        $orderBys[] = [$titleExpr, $dir];
                    } else {
                        $ignored[] = $fieldRaw;
                    }
                    break;

                case 'code':
                case 'lsshopproductcode':
                case 'lsshopproductcode_sortdir': // tolerate raw tokens
                    $orderBys[] = ['product.lsShopProductCode', $dir];
                    break;

                case 'producer':
                case 'lsshopproductproducer':
                    $orderBys[] = ['product.lsShopProductProducer', $dir];
                    break;

                case 'weight':
                case 'lsshopproductweight':
                    $orderBys[] = ['product.lsShopProductWeight', $dir];
                    break;

                case 'sorting':
                    $orderBys[] = ['product.sorting', $dir];
                    break;

                default:
                    // Unsupported in DB-only sorting phase (e.g., price, flex_contents, attribute label)
                    $ignored[] = $fieldRaw;
                    break;
            }
        }

        // Ensure uniqueness and preserve order (avoid duplicate id/order entries)
        if (!empty($orderBys)) {
            $seen = [];
            $unique = [];
            foreach ($orderBys as [$expr, $dir]) {
                $key = strtolower($expr) . '|' . $dir;
                if (isset($seen[$key])) { continue; }
                $seen[$key] = true;
                $unique[] = [$expr, $dir];
            }
            $orderBys = $unique;
        }

        return [$orderBys, $ignored];
    }
}


