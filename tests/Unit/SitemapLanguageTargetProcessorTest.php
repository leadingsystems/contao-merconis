<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use LeadingSystems\MerconisBundle\Sitemap\LanguagePageProviderInterface;
use LeadingSystems\MerconisBundle\Sitemap\MainLanguagePageResolverInterface;
use LeadingSystems\MerconisBundle\Sitemap\PageDetailsProviderInterface;
use LeadingSystems\MerconisBundle\Sitemap\SitemapLanguageTargetProcessor;
use LeadingSystems\MerconisBundle\Sitemap\SitemapPageCorrespondence;
use LeadingSystems\MerconisBundle\Sitemap\SitemapPageEligibility;
use LeadingSystems\MerconisBundle\Sitemap\SitemapPageScope;
use LeadingSystems\MerconisBundle\Sitemap\SitemapUrlAppender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapLanguageTargetProcessorTest extends TestCase
{
    public function testOwnEligibleCorrespondenceAddsUrl(): void
    {
        $assignedPage = $this->createPageModel(10, 'de', 'regular', 10);
        $targetPage = $this->createPageModel(10, 'de', 'regular', 10);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [10]);

        $languagePageProvider = $this->createMock(LanguagePageProviderInterface::class);
        $languagePageProvider
            ->expects(self::once())
            ->method('getLanguagePages')
            ->with(10)
            ->willReturn(['de' => ['id' => 10]]);

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider
            ->expects(self::once())
            ->method('getPageDetailsCached')
            ->with(10)
            ->willReturn($targetPage);

        $mainLanguagePageResolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $mainLanguagePageResolver->expects(self::never())->method('resolve');

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                $targetPage,
                ['parameters' => '/product/produkt-de'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://m51-c53.ddev.site/de/product/produkt-de');

        $processor = $this->createProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            $mainLanguagePageResolver,
            $contentUrlGenerator,
        );

        self::assertSame(
            1,
            $processor->appendProductUrlsForAssignedPage(
                $event,
                $assignedPage,
                ['alias_de' => 'produkt-de'],
            )
        );
        self::assertSame(
            ['https://m51-c53.ddev.site/de/product/produkt-de'],
            $this->extractLocValues($event->getDocument())
        );
    }

    public function testForeignEligibleCorrespondenceAddsUrlInOwnScope(): void
    {
        $assignedPage = $this->createPageModel(10, 'de', 'regular', 10);
        $targetPage = $this->createPageModel(20, 'en', 'regular', 20);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [20]);

        $languagePageProvider = $this->createMock(LanguagePageProviderInterface::class);
        $languagePageProvider
            ->expects(self::once())
            ->method('getLanguagePages')
            ->with(10)
            ->willReturn(['en' => ['id' => 20]]);

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider
            ->expects(self::once())
            ->method('getPageDetailsCached')
            ->with(20)
            ->willReturn($targetPage);

        $mainLanguagePageResolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $mainLanguagePageResolver
            ->expects(self::once())
            ->method('resolve')
            ->with(20)
            ->willReturn(10);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                $targetPage,
                ['parameters' => '/product/product-en'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://m51-c53-b.ddev.site/en/product/product-en');

        $processor = $this->createProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            $mainLanguagePageResolver,
            $contentUrlGenerator,
        );

        self::assertSame(
            1,
            $processor->appendProductUrlsForAssignedPage(
                $event,
                $assignedPage,
                ['alias_en' => 'product-en'],
            )
        );
        self::assertSame(
            ['https://m51-c53-b.ddev.site/en/product/product-en'],
            $this->extractLocValues($event->getDocument())
        );
    }

    public function testMissingCorrespondenceDoesNotAddUrl(): void
    {
        $assignedPage = $this->createPageModel(10, 'de', 'regular', 10);
        $targetPage = $this->createPageModel(20, 'en', 'regular', 20);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [20]);

        $languagePageProvider = $this->createMock(LanguagePageProviderInterface::class);
        $languagePageProvider->method('getLanguagePages')->willReturn(['en' => ['id' => 20]]);

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider->method('getPageDetailsCached')->willReturn($targetPage);

        $mainLanguagePageResolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $mainLanguagePageResolver->method('resolve')->willReturn(null);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator->expects(self::never())->method('generate');

        $processor = $this->createProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            $mainLanguagePageResolver,
            $contentUrlGenerator,
        );

        self::assertSame(
            0,
            $processor->appendProductUrlsForAssignedPage(
                $event,
                $assignedPage,
                ['alias_en' => 'product-en'],
            )
        );
        self::assertSame([], $this->extractLocValues($event->getDocument()));
    }

    public function testIneligibleTargetDoesNotAddUrl(): void
    {
        $assignedPage = $this->createPageModel(10, 'de', 'regular', 10);
        $targetPage = $this->createPageModel(20, 'en', 'root', 20);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [20]);

        $languagePageProvider = $this->createMock(LanguagePageProviderInterface::class);
        $languagePageProvider->method('getLanguagePages')->willReturn(['en' => ['id' => 20]]);

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider->method('getPageDetailsCached')->willReturn($targetPage);

        $mainLanguagePageResolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $mainLanguagePageResolver->method('resolve')->willReturn(10);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator->expects(self::never())->method('generate');

        $processor = $this->createProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            $mainLanguagePageResolver,
            $contentUrlGenerator,
        );

        self::assertSame(
            0,
            $processor->appendProductUrlsForAssignedPage(
                $event,
                $assignedPage,
                ['alias_en' => 'product-en'],
            )
        );
        self::assertSame([], $this->extractLocValues($event->getDocument()));
    }

    public function testEmptyAliasDoesNotAddUrl(): void
    {
        $assignedPage = $this->createPageModel(10, 'de', 'regular', 10);
        $targetPage = $this->createPageModel(20, 'en', 'regular', 20);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [20]);

        $languagePageProvider = $this->createMock(LanguagePageProviderInterface::class);
        $languagePageProvider->method('getLanguagePages')->willReturn(['en' => ['id' => 20]]);

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider->method('getPageDetailsCached')->willReturn($targetPage);

        $mainLanguagePageResolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $mainLanguagePageResolver->method('resolve')->willReturn(10);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator->expects(self::never())->method('generate');

        $processor = $this->createProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            $mainLanguagePageResolver,
            $contentUrlGenerator,
        );

        self::assertSame(
            0,
            $processor->appendProductUrlsForAssignedPage(
                $event,
                $assignedPage,
                ['alias_en' => ''],
            )
        );
        self::assertSame([], $this->extractLocValues($event->getDocument()));
    }

    public function testLanguageRootFallbackDoesNotReachUrlGeneration(): void
    {
        $assignedPage = $this->createPageModel(10, 'de', 'regular', 10);
        $targetPage = $this->createPageModel(20, 'en', 'root', 20);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [20]);

        $languagePageProvider = $this->createMock(LanguagePageProviderInterface::class);
        $languagePageProvider->method('getLanguagePages')->willReturn(['en' => ['id' => 20]]);

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider->method('getPageDetailsCached')->willReturn($targetPage);

        $mainLanguagePageResolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $mainLanguagePageResolver
            ->expects(self::once())
            ->method('resolve')
            ->with(20)
            ->willReturn(20);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator->expects(self::never())->method('generate');

        $processor = $this->createProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            $mainLanguagePageResolver,
            $contentUrlGenerator,
        );

        self::assertSame(
            0,
            $processor->appendProductUrlsForAssignedPage(
                $event,
                $assignedPage,
                ['alias_en' => 'product-en'],
            )
        );
        self::assertSame([], $this->extractLocValues($event->getDocument()));
    }

    public function testValidForeignCorrespondenceOutsideScopeDoesNotAddUrl(): void
    {
        $assignedPage = $this->createPageModel(10, 'de', 'regular', 10);
        $targetPage = $this->createPageModel(20, 'en', 'regular', 20);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [10]);

        $languagePageProvider = $this->createMock(LanguagePageProviderInterface::class);
        $languagePageProvider->method('getLanguagePages')->willReturn(['en' => ['id' => 20]]);

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider->method('getPageDetailsCached')->willReturn($targetPage);

        $mainLanguagePageResolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $mainLanguagePageResolver->method('resolve')->willReturn(10);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator->expects(self::never())->method('generate');

        $processor = $this->createProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            $mainLanguagePageResolver,
            $contentUrlGenerator,
        );

        self::assertSame(
            0,
            $processor->appendProductUrlsForAssignedPage(
                $event,
                $assignedPage,
                ['alias_en' => 'product-en'],
            )
        );
        self::assertSame([], $this->extractLocValues($event->getDocument()));
    }

    private function createProcessor(
        LanguagePageProviderInterface $languagePageProvider,
        PageDetailsProviderInterface $pageDetailsProvider,
        MainLanguagePageResolverInterface $mainLanguagePageResolver,
        ContentUrlGenerator $contentUrlGenerator,
    ): SitemapLanguageTargetProcessor {
        return new SitemapLanguageTargetProcessor(
            $languagePageProvider,
            $pageDetailsProvider,
            new SitemapPageCorrespondence($mainLanguagePageResolver),
            new SitemapPageEligibility(),
            new SitemapUrlAppender($contentUrlGenerator, new SitemapPageScope()),
        );
    }

    private function createSitemapDocument(): \DOMDocument
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadXML(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'
        );

        return $document;
    }

    /**
     * @return list<string>
     */
    private function extractLocValues(\DOMDocument $document): array
    {
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $locNodes = $xpath->query('//sm:url/sm:loc');
        $locValues = [];

        if (false === $locNodes) {
            return $locValues;
        }

        foreach ($locNodes as $locNode) {
            $locValues[] = $locNode->textContent;
        }

        return $locValues;
    }

    private function createPageModel(
        int $pageId,
        string $language,
        string $type,
        int $rootPageId,
    ): PageModel {
        return new class($pageId, $language, $type, $rootPageId) extends PageModel {
            public function __construct(
                private readonly int $pageId,
                private readonly string $language,
                private readonly string $type,
                private readonly int $rootPageId,
            ) {
            }

            public function __get($key)
            {
                return match ($key) {
                    'id' => $this->pageId,
                    'language' => $this->language,
                    'type' => $this->type,
                    'rootId' => $this->rootPageId,
                    'published' => '1',
                    'start' => '',
                    'stop' => '',
                    'noSearch' => '',
                    'sitemap' => '',
                    default => null,
                };
            }
        };
    }
}
