<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\PageModel;
use LeadingSystems\MerconisBundle\Sitemap\SitemapPageEligibility;
use PHPUnit\Framework\TestCase;

final class SitemapPageEligibilityTest extends TestCase
{
    private const CURRENT_TIMESTAMP = 1_700_000_000;

    public function testAcceptsRegularPublishedSearchableSitemapPageWithinPublicationWindow(): void
    {
        $eligibility = new SitemapPageEligibility();

        self::assertTrue(
            $eligibility->isEligible(
                $this->createPageModel(
                    type: 'regular',
                    published: '1',
                    start: '',
                    stop: '',
                    noSearch: '',
                    sitemap: '',
                ),
                self::CURRENT_TIMESTAMP,
            )
        );
    }

    public function testRejectsLanguageRootPage(): void
    {
        $this->assertRejectedPageType('root');
    }

    public function testRejectsInternalRedirectPage(): void
    {
        $this->assertRejectedPageType('forward');
    }

    public function testRejectsExternalRedirectPage(): void
    {
        $this->assertRejectedPageType('redirect');
    }

    public function testRejectsUnknownCustomPageType(): void
    {
        $this->assertRejectedPageType('custom_product_page');
    }

    public function testRejectsUnpublishedPage(): void
    {
        $eligibility = new SitemapPageEligibility();

        self::assertFalse(
            $eligibility->isEligible(
                $this->createPageModel(
                    type: 'regular',
                    published: '',
                    start: '',
                    stop: '',
                    noSearch: '',
                    sitemap: '',
                ),
                self::CURRENT_TIMESTAMP,
            )
        );
    }

    public function testRejectsPageThatIsNotYetActive(): void
    {
        $eligibility = new SitemapPageEligibility();

        self::assertFalse(
            $eligibility->isEligible(
                $this->createPageModel(
                    type: 'regular',
                    published: '1',
                    start: (string) self::CURRENT_TIMESTAMP,
                    stop: '',
                    noSearch: '',
                    sitemap: '',
                ),
                self::CURRENT_TIMESTAMP,
            )
        );
    }

    public function testRejectsExpiredPage(): void
    {
        $eligibility = new SitemapPageEligibility();

        self::assertFalse(
            $eligibility->isEligible(
                $this->createPageModel(
                    type: 'regular',
                    published: '1',
                    start: '',
                    stop: (string) self::CURRENT_TIMESTAMP,
                    noSearch: '',
                    sitemap: '',
                ),
                self::CURRENT_TIMESTAMP,
            )
        );
    }

    public function testRejectsPageExcludedFromSearch(): void
    {
        $eligibility = new SitemapPageEligibility();

        self::assertFalse(
            $eligibility->isEligible(
                $this->createPageModel(
                    type: 'regular',
                    published: '1',
                    start: '',
                    stop: '',
                    noSearch: '1',
                    sitemap: '',
                ),
                self::CURRENT_TIMESTAMP,
            )
        );
    }

    public function testRejectsPageExcludedFromSitemap(): void
    {
        $eligibility = new SitemapPageEligibility();

        self::assertFalse(
            $eligibility->isEligible(
                $this->createPageModel(
                    type: 'regular',
                    published: '1',
                    start: '',
                    stop: '',
                    noSearch: '',
                    sitemap: 'map_never',
                ),
                self::CURRENT_TIMESTAMP,
            )
        );
    }

    private function assertRejectedPageType(string $pageType): void
    {
        $eligibility = new SitemapPageEligibility();

        self::assertFalse(
            $eligibility->isEligible(
                $this->createPageModel(
                    type: $pageType,
                    published: '1',
                    start: '',
                    stop: '',
                    noSearch: '',
                    sitemap: '',
                ),
                self::CURRENT_TIMESTAMP,
            )
        );
    }

    private function createPageModel(
        string $type,
        string $published,
        string $start,
        string $stop,
        string $noSearch,
        string $sitemap,
    ): PageModel {
        return new class($type, $published, $start, $stop, $noSearch, $sitemap) extends PageModel {
            public function __construct(
                private readonly string $type,
                private readonly string $published,
                private readonly string $start,
                private readonly string $stop,
                private readonly string $noSearch,
                private readonly string $sitemap,
            ) {
            }

            public function __get($key)
            {
                return match ($key) {
                    'type' => $this->type,
                    'published' => $this->published,
                    'start' => $this->start,
                    'stop' => $this->stop,
                    'noSearch' => $this->noSearch,
                    'sitemap' => $this->sitemap,
                    default => null,
                };
            }
        };
    }
}
