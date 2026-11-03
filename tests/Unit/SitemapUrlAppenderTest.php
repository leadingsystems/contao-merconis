<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use LeadingSystems\MerconisBundle\Sitemap\SitemapPageScope;
use LeadingSystems\MerconisBundle\Sitemap\SitemapUrlAppender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapUrlAppenderTest extends TestCase
{
    public function testAppendProductUrlAddsAbsoluteUrlForPageBelowAllowedRoot(): void
    {
        $targetPage = $this->createPageModel(33, 10);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [10]);
        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                $targetPage,
                ['parameters' => '/product/produkt-de'],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('https://example.test/de/produkt/produkt-de');

        $appender = new SitemapUrlAppender($contentUrlGenerator, new SitemapPageScope());

        self::assertTrue($appender->appendProductUrl($event, $targetPage, 'produkt-de'));
        self::assertSame(
            ['https://example.test/de/produkt/produkt-de'],
            $this->extractLocValues($event->getDocument())
        );
    }

    public function testAppendProductUrlAcceptsRootPageItself(): void
    {
        $targetPage = $this->createPageModel(10, 0);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [10]);
        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturn('https://example.test/de/produkt/root-alias');

        $appender = new SitemapUrlAppender($contentUrlGenerator, new SitemapPageScope());

        self::assertTrue($appender->appendProductUrl($event, $targetPage, 'root-alias'));
        self::assertCount(1, $this->extractLocValues($event->getDocument()));
    }

    public function testAppendProductUrlSkipsPageOutsideAllowedRootPages(): void
    {
        $targetPage = $this->createPageModel(33, 20);
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [10]);
        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects(self::never())
            ->method('generate');

        $appender = new SitemapUrlAppender($contentUrlGenerator, new SitemapPageScope());

        self::assertFalse($appender->appendProductUrl($event, $targetPage, 'produkt-de'));
        self::assertSame([], $this->extractLocValues($event->getDocument()));
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

    private function createPageModel(int $pageId, int $rootPageId): PageModel
    {
        return new class($pageId, $rootPageId) extends PageModel {
            public function __construct(
                private readonly int $pageId,
                private readonly int $rootPageId,
            ) {
            }

            public function __get($key)
            {
                return match ($key) {
                    'id' => $this->pageId,
                    'rootId' => $this->rootPageId,
                    default => null,
                };
            }
        };
    }
}
