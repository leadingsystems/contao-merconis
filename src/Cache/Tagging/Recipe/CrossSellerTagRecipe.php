<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\Context\CachingContextInterface;
use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

/**
 * CrossSellerTagRecipe
 *
 * Tags a cross-seller cache fragment by its id and current language.
 * Params:
 *  - id (int|string): required cross-seller id
 */
final class CrossSellerTagRecipe extends AbstractContextVaryingRecipe
{
    protected array $defaultVaryOn = ['language'];

    protected function buildIdentityTags(array $entityParams, TagSet $tags): void
    {
        $id = isset($entityParams['id']) ? (string) $entityParams['id'] : '';
        if ($id !== '') {
            $tags->add('cross_seller_id', $id);
        } else {
            $tags->add('cross_seller_id', 'unknown');
        }
    }
}


