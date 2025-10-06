<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

final class FragmentGalleryTagRecipe extends AbstractContextVaryingRecipe
{
    protected array $defaultVaryOn = ['language'];

    protected function buildIdentityTags(array $entityParams, TagSet $tags): void
    {
        $version = isset($entityParams['v']) ? (string) $entityParams['v'] : '';
        $product = isset($entityParams['prod']) ? (string) $entityParams['prod'] : '';
        $isVariant = isset($entityParams['isVariant']) && (bool)$entityParams['isVariant'];
        $sort = isset($entityParams['sort']) ? (string) $entityParams['sort'] : '';
        $signature = $entityParams['sig'] ?? [];
        $mainSig = $entityParams['mis'] ?? null;
        $template = isset($entityParams['tpl']) ? (string) $entityParams['tpl'] : '';
        $overlays = $entityParams['ov'] ?? [];

        $tags->add('ns', 'gallery.fragment');
        if ($product !== '') {
            $tags->add('prod', $product);
        }
        $tags->add('isVariant', $isVariant);
        if ($sort !== '') {
            $tags->add('sort', $sort);
        }
        if (is_array($signature)) {
            $tags->add('sig', $signature);
        }
        if (is_array($mainSig) || $mainSig === null) {
            $tags->add('mis', $mainSig);
        }
        if ($template !== '') {
            $tags->add('tpl', $template);
        }
        if (is_array($overlays)) {
            $tags->add('ov', array_values($overlays));
        }
    }
}


