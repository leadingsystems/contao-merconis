<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

use LeadingSystems\MerconisBundle\Cache\Tagging\TagSet;

final class GalleryFileMetaTagRecipe extends AbstractContextVaryingRecipe
{
    protected array $defaultVaryOn = ['language'];

    protected function buildIdentityTags(array $entityParams, TagSet $tags): void
    {
        $file = isset($entityParams['file']) ? (string) $entityParams['file'] : '';
        $version = isset($entityParams['v']) ? (string) $entityParams['v'] : '';

        $tags->add('ns', 'gallery.filemeta');
        $tags->add('file', $file !== '' ? $file : 'unknown');
    }
}


