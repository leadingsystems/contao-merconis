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
        $scoreExpression = null;

        if (count($fulltextComponents)) {
            [$whereConditions, $scoreExpression, $scoreParameters, $scoreParameterTypes] = $this->buildFulltextExpressions(
                $fulltextComponents,
                $language
            );

            foreach ($whereConditions as $condition) {
                $qb->andWhere($condition);
            }

            foreach ($scoreParameters as $name => $value) {
                $parameters[$name] = $value;
            }

            foreach ($scoreParameterTypes as $name => $type) {
                $parameterTypes[$name] = $type;
            }
        }

        if ($scoreExpression === null) {
            $scoreExpression = '0';
        }

        $qb->addSelect($scoreExpression . ' AS relevance');

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

        $rows = $qb->executeQuery()->fetchAllAssociative();

        if (!count($rows)) {
            return [];
        }

        return array_map(static fn (array $row) => (int) $row['id'], $rows);
    }

    /**
     * @return array{0:string[],1:string,2:array<string,string>,3:array<string,int>}
     */
    private function buildFulltextExpressions(array $components, string $language): array
    {
        $whereConditions = [];
        $scoreParts = [];
        $parameters = [];
        $parameterTypes = [];

        foreach ($components as $component) {
            $term = $component['text'];
            if ($term === '') {
                continue;
            }

            $fields = $component['fields'];
            if (!count($fields)) {
                $fields = $this->defaultFieldKeys;
            }

            $boost = $component['boost'];

            $termWhereFragments = [];

            $paramName = $this->nextParameterName();
            $pattern = $this->createLikePattern($term);
            $parameters[$paramName] = $pattern;
            $parameterTypes[$paramName] = ParameterType::STRING;

            foreach ($fields as $fieldKey) {
                $fieldConfig = $this->getFieldConfig($fieldKey);
                if ($fieldConfig === null) {
                    continue;
                }

                $columnExpression = $this->resolveColumnExpression($fieldConfig, $language);
                if ($columnExpression === null) {
                    continue;
                }

                $baseWeight = $fieldConfig['weight'] ?? 1.0;
                $weight = $baseWeight * $boost;

                $likeExpression = sprintf(
                    "LOWER(%s) LIKE :%s ESCAPE '\\\\'",
                    $columnExpression,
                    $paramName
                );

                $termWhereFragments[] = $likeExpression;

                $scoreParts[] = sprintf(
                    'CASE WHEN %s THEN %s ELSE 0 END',
                    $likeExpression,
                    $this->connection->quote((string) $weight)
                );
            }

            if (!count($termWhereFragments)) {
                continue;
            }

            $whereConditions[] = '(' . implode(' OR ', $termWhereFragments) . ')';
        }

        $scoreExpression = count($scoreParts) ? 'COALESCE(' . implode(' + ', $scoreParts) . ', 0)' : '0';

        return [$whereConditions, $scoreExpression, $parameters, $parameterTypes];
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

    private function applyInlineModifiers(array &$term, string $modifiersString): void
    {
        if (!preg_match_all('/\{([^:}]+):([^}]+)\}/', $modifiersString, $mods, PREG_SET_ORDER)) {
            return;
        }

        foreach ($mods as $mod) {
            $this->applyModifier($term, $mod[1], $mod[2]);
        }
    }

    private function applyModifierToken(array &$term, string $token): void
    {
        if (!preg_match('/^\{([^:}]+):([^}]+)\}$/', $token, $match)) {
            return;
        }

        $this->applyModifier($term, $match[1], $match[2]);
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


