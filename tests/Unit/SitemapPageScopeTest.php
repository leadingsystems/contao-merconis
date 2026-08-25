<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;
use LeadingSystems\MerconisBundle\Sitemap\SitemapPageScope;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SitemapPageScopeTest extends TestCase
{
    public function testContainsAcceptsRootPageItself(): void
    {
        $scope = new SitemapPageScope();
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [15]);
        $pageModel = $this->createPageModel(15, 0);

        self::assertTrue($scope->contains($pageModel, $event));
    }

    public function testContainsAcceptsPageBelowAllowedRootPage(): void
    {
        $scope = new SitemapPageScope();
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [22]);
        $pageModel = $this->createPageModel(27, 22);

        self::assertTrue($scope->contains($pageModel, $event));
    }

    public function testContainsRejectsPageOutsideAllowedRootPages(): void
    {
        $scope = new SitemapPageScope();
        $event = new SitemapEvent($this->createSitemapDocument(), Request::create('/sitemap.xml'), [5, 6]);
        $pageModel = $this->createPageModel(27, 22);

        self::assertFalse($scope->contains($pageModel, $event));
    }

    private function createSitemapDocument(): \DOMDocument
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadXML(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'
        );

        return $document;
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
