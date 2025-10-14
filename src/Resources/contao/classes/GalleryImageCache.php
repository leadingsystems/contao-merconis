<?php
declare(strict_types=1);

namespace Merconis\Core;

final class GalleryImageCache {

    public static function buildOverlaysHash(array $overlays): string {
        $vals = array_values($overlays);
        sort($vals);
        return md5(json_encode($vals));
    }

    public static function buildMultiSigHash(string $multiSig): string {
        return md5($multiSig);
    }

    public static function buildTags(string $language, string $sortMode, string $overlaysHash, string $mainSig, string $multiSigHash): array {
        return array(
            'ns' => 'merconis.gallery',
            'lang' => $language,
            'sort' => $sortMode,
            'ov' => $overlaysHash,
            'main' => $mainSig,
            'multi' => $multiSigHash
        );
    }

    public static function serializeImage(\stdClass $img): array {
        return array(
            'name' => (string) ($img->name ?? ''),
            'singleSRC' => (string) ($img->singleSRC ?? ''),
            'originalSRC' => $img->originalSRC ?? false,
            'arrOverlays' => (array) ($img->arrOverlays ?? array()),
            'alt' => (string) ($img->alt ?? ''),
            'title' => (string) ($img->title ?? ''),
            'imageUrl' => (string) ($img->imageUrl ?? ''),
            'caption' => (string) ($img->caption ?? ''),
            'mtime' => (int) ($img->mtime ?? 0),
            'randomSortingValue' => (string) ($img->randomSortingValue ?? ''),
        );
    }

    public static function serializeImages(array $images): array {
        $out = array();
        foreach ($images as $img) {
            if ($img instanceof \stdClass) {
                $out[] = self::serializeImage($img);
            }
        }
        return $out;
    }

    public static function hydrateImage(array $row): \stdClass {
        $o = new \stdClass();
        $o->name = (string) ($row['name'] ?? '');
        $o->singleSRC = (string) ($row['singleSRC'] ?? '');
        $o->originalSRC = $row['originalSRC'] ?? false;
        $o->arrOverlays = (array) ($row['arrOverlays'] ?? array());
        $o->alt = (string) ($row['alt'] ?? '');
        $o->title = (string) ($row['title'] ?? '');
        $o->imageUrl = (string) ($row['imageUrl'] ?? '');
        $o->caption = (string) ($row['caption'] ?? '');
        $o->mtime = (int) ($row['mtime'] ?? 0);
        $o->randomSortingValue = (string) ($row['randomSortingValue'] ?? '');
        return $o;
    }

    public static function hydrateImages(array $rows): array {
        $out = array();
        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = self::hydrateImage($row);
            }
        }
        return $out;
    }
}


