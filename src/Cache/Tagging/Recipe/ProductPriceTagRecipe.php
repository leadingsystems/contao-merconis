<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

final class ProductPriceTagRecipe implements TagRecipeInterface
{
    private $defaultDimensions = array('currency', 'shipping_country', 'user_group_ids', 'price_display_mode');

    public function getTags(CachingContextInterface $ctx, array $options = array()): array
    {
        $productId = isset($options['product_id']) ? (int) $options['product_id'] : 0;
        $tags = new TagSet();
        if ($productId > 0) {
            $tags->add('entity:product:' . $productId);
        } else {
            $tags->add('entity:product');
        }

        $varyOn = (isset($options['vary_on']) && is_array($options['vary_on'])) ? $options['vary_on'] : $this->defaultDimensions;
        $tags->addMany($ctx->buildContextTags($varyOn));
        return $tags->toArray();
    }
}


