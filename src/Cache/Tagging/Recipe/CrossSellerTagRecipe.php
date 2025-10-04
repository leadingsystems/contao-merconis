<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

/**
 * CrossSellerTagRecipe
 *
 * Tags a cross-seller cache fragment by its id and current language.
 * Options:
 *  - id (int|string): required cross-seller id
 *  - vary_on (string[]|null): override default dimensions (default ['language'])
 */
final class CrossSellerTagRecipe implements TagRecipeInterface
{
    private $defaultDimensions = array('language');

    public function getTags(CachingContextInterface $ctx, array $options = array()): array
    {
        $id = isset($options['id']) ? (string) $options['id'] : '';
        $tags = new TagSet();
        if ($id !== '') {
            $tags->add('cross_seller_id', $id);
        } else {
            $tags->add('cross_seller', 'unknown');
        }

        $varyOn = (isset($options['vary_on']) && is_array($options['vary_on'])) ? $options['vary_on'] : $this->defaultDimensions;
        $tags->addMany($ctx->buildContextTags($varyOn));
        return $tags->toArray();
    }
}


