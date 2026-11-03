<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use LeadingSystems\MerconisBundle\EventListener\SitemapListener;
use LeadingSystems\MerconisBundle\Sitemap\PageDetailsProviderInterface;
use LeadingSystems\MerconisBundle\Sitemap\SitemapLanguageTargetProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SitemapListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['merconis_globals']['arr_cache']['arr_languages'] = [
            'arr_allLanguages' => ['de' => 'de'],
            'str_fallbackLanguage' => 'de',
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['merconis_globals']['arr_cache']['arr_languages']);

        parent::tearDown();
    }

    public function testDoesNothingWhenProductUrlsAreHandledExternally(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('fetchAllAssociative');
        $connection->expects(self::never())->method('fetchFirstColumn');

        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider->expects(self::never())->method('getPageDetailsCached');

        $processor = $this->createMock(SitemapLanguageTargetProcessor::class);
        $processor->expects(self::never())->method('appendProductUrlsForAssignedPage');

        $listener = new SitemapListener($connection, $pageDetailsProvider, $processor, true);

        $listener($this->createSitemapEvent([10]));
    }

    public function testProcessesEligibleAssignedPagesWhenSitemapHandlingIsEnabled(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'pages' => serialize(['10']),
                    'alias_de' => 'produkt-de',
                ],
            ]);
        $connection
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn(['10']);

        $assignedPage = $this->createPageModel(10, 10);
        $pageDetailsProvider = $this->createMock(PageDetailsProviderInterface::class);
        $pageDetailsProvider
            ->expects(self::once())
            ->method('getPageDetailsCached')
            ->with(10)
            ->willReturn($assignedPage);

        $processor = $this->createMock(SitemapLanguageTargetProcessor::class);
        $processor
            ->expects(self::once())
            ->method('appendProductUrlsForAssignedPage')
            ->with(
                self::isInstanceOf(SitemapEvent::class),
                $assignedPage,
                ['pages' => serialize(['10']), 'alias_de' => 'produkt-de'],
            )
            ->willReturn(1);

        $listener = new SitemapListener($connection, $pageDetailsProvider, $processor, false);

        $listener($this->createSitemapEvent([10]));
    }

    private function createSitemapEvent(array $rootPageIds): SitemapEvent
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadXML('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        return new SitemapEvent($document, Request::create('/sitemap.xml'), $rootPageIds);
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
