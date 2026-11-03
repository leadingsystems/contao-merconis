<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\PageModel;
use LeadingSystems\MerconisBundle\Sitemap\MainLanguagePageResolverInterface;
use LeadingSystems\MerconisBundle\Sitemap\SitemapPageCorrespondence;
use PHPUnit\Framework\TestCase;

final class SitemapPageCorrespondenceTest extends TestCase
{
    public function testMatchesAssignedPageForOwnLanguage(): void
    {
        $resolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $resolver->expects(self::never())->method('resolve');

        $correspondence = new SitemapPageCorrespondence($resolver);

        self::assertTrue(
            $correspondence->matches(
                $this->createPageModel(10, 'de', 'regular', 10),
                $this->createPageModel(10, 'de', 'regular', 10),
            )
        );
    }

    public function testMatchesForeignLanguagePageWithMatchingMainLanguagePage(): void
    {
        $resolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('resolve')
            ->with(20)
            ->willReturn(10);

        $correspondence = new SitemapPageCorrespondence($resolver);

        self::assertTrue(
            $correspondence->matches(
                $this->createPageModel(10, 'de', 'regular', 10),
                $this->createPageModel(20, 'en', 'regular', 20),
            )
        );
    }

    public function testRejectsWhenMainLanguagePageCannotBeResolved(): void
    {
        $resolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('resolve')
            ->with(20)
            ->willReturn(null);

        $correspondence = new SitemapPageCorrespondence($resolver);

        self::assertFalse(
            $correspondence->matches(
                $this->createPageModel(10, 'de', 'regular', 10),
                $this->createPageModel(20, 'en', 'regular', 20),
            )
        );
    }

    public function testRejectsWhenMainLanguagePageDiffersFromAssignedPage(): void
    {
        $resolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('resolve')
            ->with(20)
            ->willReturn(11);

        $correspondence = new SitemapPageCorrespondence($resolver);

        self::assertFalse(
            $correspondence->matches(
                $this->createPageModel(10, 'de', 'regular', 10),
                $this->createPageModel(20, 'en', 'regular', 20),
            )
        );
    }

    public function testRejectsLanguageRootFallbackAsCorrespondence(): void
    {
        $resolver = $this->createMock(MainLanguagePageResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('resolve')
            ->with(30)
            ->willReturn(30);

        $correspondence = new SitemapPageCorrespondence($resolver);

        self::assertFalse(
            $correspondence->matches(
                $this->createPageModel(10, 'de', 'regular', 10),
                $this->createPageModel(30, 'en', 'root', 30),
            )
        );
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
                    default => null,
                };
            }
        };
    }
}
