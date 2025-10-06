<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;

interface TagRecipeInterface
{
    public function getTags(CachingContextInterface $ctx, array $entityParams = [], ?array $varyOnOverride = null): array;
}


