<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

abstract class AbstractContextVaryingRecipe implements TagRecipeInterface
{
    /** @var string[] */
    protected array $defaultVaryOn = array('');

    final public function getTags(CachingContextInterface $ctx, array $entityParams = array(), ?array $varyOnOverride = null): array
    {
        $tags = new TagSet();

        $this->buildIdentityTags($entityParams, $tags);

        $varyOn = is_array($varyOnOverride) ? $varyOnOverride : $this->defaultVaryOn;
        $tags->addMany($ctx->buildContextTags($varyOn));

        return $tags->toArray();
    }

    /**
     * Implementations must add identity tags only.
     */
    abstract protected function buildIdentityTags(array $entityParams, TagSet $tags): void;
}


