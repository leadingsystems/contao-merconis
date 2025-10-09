<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\ContaoCacheBundle\Cache\Tagging\TagSet;

final class GalleryImagesTagRecipe extends \LeadingSystems\ContaoCacheBundle\Cache\Tagging\Recipe\AbstractContextVaryingRecipe
{
    protected array $defaultVaryOn = ['language'];

    protected function buildIdentityTags(array $entityParams, TagSet $tags): void
    {
        $version = isset($entityParams['v']) ? (string) $entityParams['v'] : '';
        $sort = isset($entityParams['sort']) ? (string) $entityParams['sort'] : '';
        $overlays = $entityParams['ov'] ?? [];
        $signature = $entityParams['sig'] ?? [];
        $mainSig = $entityParams['mis'] ?? null;
        $includeMain = !isset($entityParams['incMain']) || (bool)$entityParams['incMain'];

        $tags->add('ns', 'gallery.images');
        if ($sort !== '') {
            $tags->add('sort', $sort);
        }
        if (is_array($overlays)) {
            $tags->add('ov', array_values($overlays));
        }
        if (is_array($signature)) {
            $tags->add('sig', $signature);
        }
        if (is_array($mainSig) || $mainSig === null) {
            $tags->add('mis', $mainSig);
        }
        $tags->add('incMain', $includeMain);
    }
}


