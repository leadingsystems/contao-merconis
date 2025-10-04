<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

final class ProductListTagRecipe implements TagRecipeInterface
{
    private $defaultDimensions = array('language', 'currency', 'shipping_country', 'user_group_ids', 'price_display_mode');

    public function getTags(CachingContextInterface $ctx, array $options = array()): array
    {
        $tags = new TagSet();
        $tags->add('entity:product_list');

        $categoryId = isset($options['category_id']) ? (int) $options['category_id'] : null;
        if ($categoryId) {
            $tags->add('entity:category:' . $categoryId);
        }

        $varyOn = (isset($options['vary_on']) && is_array($options['vary_on'])) ? $options['vary_on'] : $this->defaultDimensions;
        $tags->addMany($ctx->buildContextTags($varyOn));
        return $tags->toArray();
    }
}


