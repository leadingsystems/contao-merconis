<?php

declare(strict_types=1);

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Merconis\Core\ls_shop_languageHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

$projectDir = dirname(__DIR__, 5);
$autoloadPath = $projectDir.'/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, "Project autoload file not found.\n");
    exit(1);
}

require_once $autoloadPath;

try {
    $outputPath = requireOutputPath($argv);
    exportSitemapState($projectDir, $outputPath);
    fwrite(STDOUT, "Exported sitemap state to {$outputPath}\n");
    exit(0);
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage()."\n");
    exit(1);
}

function requireOutputPath(array $argv): string
{
    if (count($argv) !== 2) {
        throw new InvalidArgumentException('Expected exactly one output path argument.');
    }

    $outputPath = $argv[1];

    if ('' === $outputPath) {
        throw new InvalidArgumentException('Output path must not be empty.');
    }

    if (is_dir($outputPath)) {
        throw new InvalidArgumentException('Output path must point to a file, not a directory.');
    }

    $parentDirectory = dirname($outputPath);

    if (!is_dir($parentDirectory)) {
        throw new InvalidArgumentException('Output directory does not exist.');
    }

    if (!is_writable($parentDirectory)) {
        throw new InvalidArgumentException('Output directory is not writable.');
    }

    return $outputPath;
}

function exportSitemapState(string $projectDir, string $outputPath): void
{
    $_SERVER['DISABLE_HTTP_CACHE'] = '1';

    $bootstrapRequest = createSitemapRequest('m51-c53.ddev.site');
    $httpKernel = ContaoKernel::fromRequest($projectDir, $bootstrapRequest);

    if (!$httpKernel instanceof ContaoKernel) {
        throw new RuntimeException('ContaoKernel bootstrap did not return a ContaoKernel instance.');
    }

    $httpKernel->boot();

    try {
        $container = $httpKernel->getContainer();
        System::setContainer($container);
        $container->get('contao.framework')->initialize();

        /** @var PageFinder $pageFinder */
        $pageFinder = $container->get('contao.routing.page_finder');
        /** @var ContentUrlGenerator $contentUrlGenerator */
        $contentUrlGenerator = $container->get('contao.routing.content_url_generator');
        /** @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');
        /** @var RouterInterface $router */
        $router = $container->get('router');
        /** @var Connection $databaseConnection */
        $databaseConnection = $container->get('database_connection');
        $pageController = $container->get('contao_helper.controller.page_controller');

        $languageKeys = array_values(ls_shop_languageHelper::getAllLanguages());
        sort($languageKeys);

        $products = collectProducts($databaseConnection, $pageController, $languageKeys);
        $hosts = [
            'm51-c53.ddev.site',
            'm51-c53-b.ddev.site',
            'm51-c53-c.ddev.site',
        ];

        $hostState = [];

        foreach ($hosts as $host) {
            $request = createSitemapRequest($host);
            $request->attributes->set('_scope', 'frontend');

            $requestStack->push($request);

            try {
                $requestContext = (new RequestContext())->fromRequest($request);
                $router->setContext($requestContext);
                $contentUrlGenerator->setContext($requestContext);
                $contentUrlGenerator->reset();

                $rootPages = $pageFinder->findRootPagesForHost($host);
                $rootPageIds = array_map(
                    static fn (PageModel $rootPage): int => (int) $rootPage->id,
                    $rootPages
                );
                sort($rootPageIds);

                $expectedUrls = [];
                $scopedPages = [];

                foreach ($products as &$productState) {
                    foreach ($productState['pageAssignments'] as &$pageAssignment) {
                        foreach ($pageAssignment['languageTargets'] as &$languageTarget) {
                            $inScope = in_array($languageTarget['pageId'], $rootPageIds, true)
                                || in_array($languageTarget['rootId'], $rootPageIds, true);
                            $shouldBeIncluded = $languageTarget['isCorrespondence']
                                && $languageTarget['eligibility']['eligible']
                                && '' !== $languageTarget['productAlias']
                                && $inScope;

                            $languageTarget['hosts'][$host] = [
                                'inScope' => $inScope,
                                'expectedUrl' => null,
                                'included' => $shouldBeIncluded,
                            ];

                            if (!$shouldBeIncluded) {
                                continue;
                            }

                            /** @var PageModel $targetPageModel */
                            $targetPageModel = $languageTarget['pageModel'];
                            $expectedUrl = $contentUrlGenerator->generate(
                                $targetPageModel,
                                ['parameters' => '/product/'.$languageTarget['productAlias']],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            );

                            $languageTarget['hosts'][$host]['expectedUrl'] = $expectedUrl;
                            $expectedUrls[$expectedUrl] = true;

                            $scopedPages[$languageTarget['pageId']] = [
                                'pageId' => $languageTarget['pageId'],
                                'rootId' => $languageTarget['rootId'],
                                'language' => $languageTarget['language'],
                                'pageType' => $languageTarget['pageType'],
                                'pageAlias' => $languageTarget['pageAlias'],
                                'productAlias' => $languageTarget['productAlias'],
                                'sourceAssignedPageId' => $pageAssignment['assignedPageId'],
                                'isCorrespondence' => $languageTarget['isCorrespondence'],
                                'correspondenceMainLanguagePageId' => $languageTarget['correspondenceMainLanguagePageId'],
                            ];
                        }
                    }
                }
                unset($productState, $pageAssignment, $languageTarget);

                ksort($expectedUrls);
                ksort($scopedPages);

                $hostState[$host] = [
                    'rootPages' => normalizeRootPages($rootPages),
                    'rootPageIds' => $rootPageIds,
                    'scopedLanguageTargetPages' => array_values($scopedPages),
                    'expectedProductUrls' => array_keys($expectedUrls),
                ];
            } finally {
                $requestStack->pop();
            }
        }

        $normalizedProducts = normalizeProducts($products);
        $result = [
            'generatedAt' => gmdate('c'),
            'hosts' => $hostState,
            'languages' => $languageKeys,
            'products' => $normalizedProducts,
        ];

        $json = json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        if (false === file_put_contents($outputPath, $json."\n")) {
            throw new RuntimeException('Failed to write output file.');
        }
    } finally {
        $httpKernel->shutdown();
    }
}

/**
 * @param object $pageController
 * @param list<string> $languageKeys
 *
 * @return list<array<string, mixed>>
 */
function collectProducts(Connection $databaseConnection, object $pageController, array $languageKeys): array
{
    $productColumns = array_map(
        static fn (string $languageKey): string => sprintf('`alias_%s`', $languageKey),
        $languageKeys
    );

    $selectColumns = implode(', ', array_merge(['`id`', '`pages`'], $productColumns));
    $productRows = $databaseConnection->fetchAllAssociative(
        "SELECT {$selectColumns} FROM `tl_ls_shop_product` WHERE `published` = 1 ORDER BY `id` ASC"
    );

    $productStates = [];
    $currentTimestamp = time();

    foreach ($productRows as $productRow) {
        $assignedPageIds = array_values(
            array_filter(
                array_map('intval', deserializePages($productRow['pages'] ?? null)),
                static fn (int $pageId): bool => $pageId > 0
            )
        );

        sort($assignedPageIds);

        $pageAssignments = [];

        foreach ($assignedPageIds as $assignedPageId) {
            $pageRecord = fetchPageRecord($databaseConnection, $assignedPageId);

            if (null === $pageRecord) {
                $pageAssignments[] = [
                    'assignedPageId' => $assignedPageId,
                    'selectionFlags' => [
                        'exists' => false,
                        'published' => false,
                        'withinPublicationWindow' => false,
                        'searchable' => false,
                        'sitemapIncluded' => false,
                        'eligibleForSitemap' => false,
                    ],
                    'languageTargets' => [],
                ];

                continue;
            }

            $selectionFlags = buildSelectionFlags($pageRecord, $currentTimestamp);
            $languageTargets = [];

            if ($selectionFlags['eligibleForSitemap']) {
                foreach (ls_shop_languageHelper::getLanguagePages($assignedPageId) as $languagePageInfo) {
                    $languagePageId = (int) ($languagePageInfo['id'] ?? 0);

                    if ($languagePageId <= 0) {
                        continue;
                    }

                    $targetPage = $pageController->getPageDetailsCached($languagePageId);

                    if (!$targetPage instanceof PageModel) {
                        continue;
                    }

                    $correspondenceMainLanguagePageId = $languagePageId === $assignedPageId
                        ? $assignedPageId
                        : normalizeMainLanguagePageId(
                            ls_shop_languageHelper::getMainlanguagePageIDForPageID($languagePageId)
                        );
                    $eligibility = buildEligibilitySnapshot($targetPage, $currentTimestamp);
                    $language = (string) $targetPage->language;
                    $productAlias = (string) ($productRow['alias_'.$language] ?? '');

                    $languageTargets[] = [
                        'pageId' => (int) $targetPage->id,
                        'rootId' => (int) $targetPage->rootId,
                        'language' => $language,
                        'pageType' => (string) $targetPage->type,
                        'pageAlias' => (string) $targetPage->alias,
                        'productAlias' => $productAlias,
                        'isOwnLanguageAssignment' => $languagePageId === $assignedPageId,
                        'correspondenceMainLanguagePageId' => $correspondenceMainLanguagePageId,
                        'isCorrespondence' => $languagePageId === $assignedPageId
                            || $correspondenceMainLanguagePageId === $assignedPageId,
                        'eligibility' => $eligibility,
                        'pageModel' => $targetPage,
                        'hosts' => [],
                    ];
                }

                usort(
                    $languageTargets,
                    static fn (array $left, array $right): int => [$left['language'], $left['pageId']]
                        <=> [$right['language'], $right['pageId']]
                );
            }

            $pageAssignments[] = [
                'assignedPageId' => $assignedPageId,
                'selectionFlags' => $selectionFlags,
                'languageTargets' => $languageTargets,
            ];
        }

        $aliases = [];

        foreach ($languageKeys as $languageKey) {
            $aliases[$languageKey] = (string) ($productRow['alias_'.$languageKey] ?? '');
        }

        $productStates[] = [
            'productId' => (int) $productRow['id'],
            'assignedPageIds' => $assignedPageIds,
            'aliases' => $aliases,
            'pageAssignments' => $pageAssignments,
        ];
    }

    return $productStates;
}

/**
 * @return list<int|string>
 */
function deserializePages(mixed $serializedPages): array
{
    if (!is_string($serializedPages) || '' === $serializedPages) {
        return [];
    }

    $value = @unserialize($serializedPages, ['allowed_classes' => false]);

    if (is_array($value)) {
        return $value;
    }

    return [];
}

/**
 * @return array<string, mixed>|null
 */
function fetchPageRecord(Connection $databaseConnection, int $pageId): ?array
{
    $pageRecord = $databaseConnection->fetchAssociative(
        'SELECT `id`, `alias`, `type`, `published`, `start`, `stop`, `noSearch`, `sitemap` FROM `tl_page` WHERE `id` = ?',
        [$pageId]
    );

    return false === $pageRecord ? null : $pageRecord;
}

/**
 * @param array<string, mixed> $pageRecord
 *
 * @return array<string, bool>
 */
function buildSelectionFlags(array $pageRecord, int $currentTimestamp): array
{
    $pageTypeIsRegular = 'regular' === (string) ($pageRecord['type'] ?? '');
    $published = '1' === (string) ($pageRecord['published'] ?? '');
    $withinPublicationWindow = isWithinPublicationWindow(
        (string) ($pageRecord['start'] ?? ''),
        (string) ($pageRecord['stop'] ?? ''),
        $currentTimestamp
    );
    $searchable = '1' !== (string) ($pageRecord['noSearch'] ?? '');
    $sitemapIncluded = 'map_never' !== (string) ($pageRecord['sitemap'] ?? '');

    return [
        'exists' => true,
        'pageTypeIsRegular' => $pageTypeIsRegular,
        'published' => $published,
        'withinPublicationWindow' => $withinPublicationWindow,
        'searchable' => $searchable,
        'sitemapIncluded' => $sitemapIncluded,
        'eligibleForSitemap' => $pageTypeIsRegular && $published && $withinPublicationWindow && $searchable && $sitemapIncluded,
    ];
}

/**
 * @return array<string, bool>
 */
function buildEligibilitySnapshot(PageModel $pageModel, int $currentTimestamp): array
{
    $pageTypeIsRegular = 'regular' === (string) $pageModel->type;
    $published = '1' === (string) $pageModel->published;
    $withinPublicationWindow = isWithinPublicationWindow(
        (string) $pageModel->start,
        (string) $pageModel->stop,
        $currentTimestamp
    );
    $searchable = '1' !== (string) $pageModel->noSearch;
    $sitemapIncluded = 'map_never' !== (string) $pageModel->sitemap;

    return [
        'pageTypeIsRegular' => $pageTypeIsRegular,
        'published' => $published,
        'withinPublicationWindow' => $withinPublicationWindow,
        'searchable' => $searchable,
        'sitemapIncluded' => $sitemapIncluded,
        'eligible' => $pageTypeIsRegular && $published && $withinPublicationWindow && $searchable && $sitemapIncluded,
    ];
}

function isWithinPublicationWindow(string $start, string $stop, int $currentTimestamp): bool
{
    return ('' === $start || (int) $start < $currentTimestamp)
        && ('' === $stop || (int) $stop > $currentTimestamp);
}

function normalizeMainLanguagePageId(mixed $pageId): ?int
{
    $normalizedPageId = (int) $pageId;

    return $normalizedPageId > 0 ? $normalizedPageId : null;
}

/**
 * @param list<PageModel> $rootPages
 *
 * @return list<array<string, mixed>>
 */
function normalizeRootPages(array $rootPages): array
{
    $normalizedRootPages = array_map(
        static fn (PageModel $rootPage): array => [
            'id' => (int) $rootPage->id,
            'rootId' => (int) $rootPage->rootId,
            'language' => (string) $rootPage->language,
            'dns' => (string) $rootPage->dns,
            'domain' => (string) $rootPage->domain,
            'fallback' => '1' === (string) $rootPage->fallback,
        ],
        $rootPages
    );

    usort(
        $normalizedRootPages,
        static fn (array $left, array $right): int => [$left['id'], $left['language']]
            <=> [$right['id'], $right['language']]
    );

    return $normalizedRootPages;
}

/**
 * @param list<array<string, mixed>> $products
 *
 * @return list<array<string, mixed>>
 */
function normalizeProducts(array $products): array
{
    foreach ($products as &$product) {
        foreach ($product['pageAssignments'] as &$pageAssignment) {
            foreach ($pageAssignment['languageTargets'] as &$languageTarget) {
                unset($languageTarget['pageModel']);
                ksort($languageTarget['hosts']);
            }
            unset($languageTarget);
        }
        unset($pageAssignment);
    }
    unset($product);

    return $products;
}

function createSitemapRequest(string $host): Request
{
    return Request::create(
        "https://{$host}/sitemap.xml",
        'GET',
        [],
        [],
        [],
        [
            'HTTP_HOST' => $host,
            'HTTPS' => 'on',
            'REQUEST_URI' => '/sitemap.xml',
            'SERVER_PORT' => '443',
        ]
    );
}
