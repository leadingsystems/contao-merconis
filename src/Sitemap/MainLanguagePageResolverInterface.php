<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

interface MainLanguagePageResolverInterface
{
    public function resolve(int $pageId): ?int;
}
