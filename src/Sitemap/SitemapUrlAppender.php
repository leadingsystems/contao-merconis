<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapUrlAppender
{
    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly SitemapPageScope $pageScope,
    ) {
    }

    public function appendProductUrl(SitemapEvent $event, PageModel $targetPage, string $productAlias): bool
    {
        if ('' === $productAlias || !$this->pageScope->contains($targetPage, $event)) {
            return false;
        }

        $productUrl = $this->contentUrlGenerator->generate(
            $targetPage,
            ['parameters' => '/product/'.$productAlias],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $event->addUrlToDefaultUrlSet($productUrl);

        return true;
    }
}
