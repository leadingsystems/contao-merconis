<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\Recipe\TagRecipeInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\Recipe\TagRecipeRegistry;

final class CacheTags
{
    private CachingContextInterface $context;
    private TagRecipeRegistry $registry;

    public function __construct(CachingContextInterface $context, TagRecipeRegistry $registry)
    {
        $this->context = $context;
        $this->registry = $registry;
    }

    /** Return tags for a named recipe with params and optional vary_on override; returns [] if recipe not found. */
    public function getTagsFor(string $recipeName, array $entityParams = [], ?array $varyOnOverride = null): array
    {
        /** @var TagRecipeInterface|null $recipe */
        $recipe = $this->registry->get($recipeName);
        if (!$recipe) {
            return [];
        }
        return $recipe->getTags($this->context, $entityParams, $varyOnOverride);
    }
}


