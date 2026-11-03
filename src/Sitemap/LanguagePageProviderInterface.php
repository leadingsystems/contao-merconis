<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Sitemap;

interface LanguagePageProviderInterface
{
    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function getLanguagePages(int $pageId): array;
}
