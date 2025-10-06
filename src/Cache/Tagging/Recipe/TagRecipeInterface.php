<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;

interface TagRecipeInterface
{
    public function getTags(CachingContextInterface $ctx, array $entityParams = array(), ?array $varyOnOverride = null): array;
}


