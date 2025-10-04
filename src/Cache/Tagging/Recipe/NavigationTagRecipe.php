<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

final class NavigationTagRecipe implements TagRecipeInterface
{
    private $defaultDimensions = array('language');

    public function getTags(CachingContextInterface $ctx, array $options = array()): array
    {
        $tags = new TagSet();
        $tags->add('entity:navigation');

        $rootId = isset($options['root_id']) ? (int) $options['root_id'] : null;
        if ($rootId) {
            $tags->add('entity:root:' . $rootId);
        }

        $varyOn = (isset($options['vary_on']) && is_array($options['vary_on'])) ? $options['vary_on'] : $this->defaultDimensions;
        $tags->addMany($ctx->buildContextTags($varyOn));
        return $tags->toArray();
    }
}


