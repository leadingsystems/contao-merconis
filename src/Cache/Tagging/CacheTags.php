<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\Recipe\TagRecipeInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\Recipe\TagRecipeRegistry;

final class CacheTags
{
    /** @var CachingContextInterface */
    private $context;
    /** @var TagRecipeRegistry */
    private $registry;

    public function __construct(CachingContextInterface $context, TagRecipeRegistry $registry)
    {
        $this->context = $context;
        $this->registry = $registry;
    }

    /** Return tags for a named recipe with options; returns [] if recipe not found. */
    public function getTagsFor(string $recipeName, array $options = array()): array
    {
        /** @var TagRecipeInterface|null $recipe */
        $recipe = $this->registry->get($recipeName);
        if (!$recipe) {
            return array();
        }
        return $recipe->getTags($this->context, $options);
    }
}


