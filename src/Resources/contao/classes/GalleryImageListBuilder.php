<?php
declare(strict_types=1);

namespace Merconis\Core;

use Contao\File;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\System;
use Contao\StringUtil;

/**
 * Builds a product's image lists in a single pass.
 * - Produces a main image (if available) and a list of more images
 * - Applies sorting to more images
 * - Moves video covers to the end of the more-images list
 * - Resolves metadata based on current page language
 *
 * Notes:
 * - Uses stdClass image objects compatible with productImageGallery expectations
 */
final class GalleryImageListBuilder {

    /** @var array<int, string> */
    private array $arrImgSuffixes = array('jpg', 'jpeg', 'JPG', 'JPEG', 'gif', 'GIF', 'png', 'PNG');

    private int $sortingRandomizer;

    public function __construct(int $sortingRandomizer) {
        $this->sortingRandomizer = $sortingRandomizer;
    }

    /**
     * @param array<int, string> $multiSRC
     * @param array<int, string> $arrOverlays
     * @return array{mainImage: ?\stdClass, moreImages: array<int, \stdClass>}
     */
    public function build(?string $mainImageSRC, array $multiSRC, array $arrOverlays, string $sortMode): array {
        /** @var PageModel $objPage */
        global $objPage;

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        $images = array();

        foreach ($multiSRC as $file) {
            $img = $this->processSingleImage($file, $arrOverlays, $projectDir, $objPage);
            if ($img) {
                $images[] = $img;
            }
        }

        // Sort more images (main image handled separately and never sorted here)
        $images = $this->sortMoreImages($images, $sortMode);

        // Move videos to end
        $images = $this->moveVideosToEnd($images);

        // Determine main image
        $mainImage = null;
        if ($mainImageSRC) {
            $mainImage = $this->processSingleImage($mainImageSRC, $arrOverlays, $projectDir, $objPage);
        }

        // Fallback main: if not provided or failed to build, take first more image if any
        if (!$mainImage && !empty($images)) {
            $mainImage = $images[0];
        }

        return array(
            'mainImage' => $mainImage ?: null,
            'moreImages' => $images
        );
    }

    /**
     * @param array<int, \stdClass> $images
     * @return array<int, \stdClass>
     */
    private function sortMoreImages(array $images, string $sortMode): array {
        switch ($sortMode) {
            default:
            case 'name_asc':
                usort($images, function($a, $b) { return strnatcasecmp($a->name, $b->name); });
                break;
            case 'name_desc':
                usort($images, function($a, $b) { return -strnatcasecmp($a->name, $b->name); });
                break;
            case 'date_asc':
                usort($images, function($a, $b) {
                    if ($a->mtime === $b->mtime) { return 0; }
                    return ($a->mtime < $b->mtime) ? -1 : 1;
                });
                break;
            case 'date_desc':
                usort($images, function($a, $b) {
                    if ($a->mtime === $b->mtime) { return 0; }
                    return ($a->mtime < $b->mtime) ? 1 : -1;
                });
                break;
            case 'random':
                usort($images, function($a, $b) {
                    return strcmp($a->randomSortingValue, $b->randomSortingValue);
                });
                break;
            case 'none':
                break;
        }
        return $images;
    }

    /**
     * @param array<int, \stdClass> $images
     * @return array<int, \stdClass>
     */
    private function moveVideosToEnd(array $images): array {
        $nonVideos = array();
        $videos = array();
        foreach ($images as $img) {
            if ($img->originalSRC !== false && $img->originalSRC !== null && $img->originalSRC !== '') {
                $videos[] = $img;
            } else {
                $nonVideos[] = $img;
            }
        }
        return array_merge($nonVideos, $videos);
    }

    private function processSingleImage(string $file, array $arrOverlays, string $projectDir, ?PageModel $objPage): ?\stdClass {
        // Skip cover still if matching video exists
        if (preg_match('/_cover/siU', $file)) {
            $parts = explode('_cover.', $file);
            if (!preg_match('/\.mp4/siU', $file) && is_file($projectDir . '/' . $parts[0] . '.mp4')) {
                return null;
            }
        }

        if (!is_file($projectDir . '/' . $file)) {
            return null;
        }

        $originalSRC = false;
        $localOverlays = $arrOverlays;

        $objFile = new File($file, true);
        if (!$objFile->isGdImage) {
            // treat as video; find cover
            $objFile = $this->getVideoCover($file);
            if (!in_array('isVideo', $localOverlays)) {
                $localOverlays[] = 'isVideo';
            }
            $originalSRC = $file;
        }

        $objFileModel = FilesModel::findMultipleByPaths(array($originalSRC ? $originalSRC : $file));
        $arrMeta = array();
        if (is_object($objFileModel)) {
            $objFileModel->first();
            if ($objPage) {
                $arrMeta = $this->getMetaData($objFileModel->meta, $objPage->language);
            }
        }

        if ($objFile->isGdImage) {
            $img = new \stdClass();
            $img->name = $objFile->basename;
            $img->originalSRC = $originalSRC;
            $img->arrOverlays = $localOverlays;
            $img->singleSRC = $file;
            $img->alt = $arrMeta['alt'] ?? '';
            $img->title = $arrMeta['title'] ?? '';
            $img->imageUrl = $arrMeta['link'] ?? '';
            $img->caption = $arrMeta['caption'] ?? '';
            $img->mtime = $objFile->mtime;
            $img->randomSortingValue = md5($objFile->basename . $this->sortingRandomizer);
            return $img;
        }

        return null;
    }

    private function getVideoCover(string &$filename): File {
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $coverFile = false;

        $coverFilename = preg_replace('(\..*$)', '_cover', $filename);
        foreach ($this->arrImgSuffixes as $suffix) {
            $coverFilename2 = $coverFilename . '.' . $suffix;
            if (is_file($projectDir . '/' . $coverFilename2)) {
                $coverFile = $coverFilename2;
                break;
            }
        }

        if (!$coverFile) {
            $coverFile = ls_shop_generalHelper::getSystemImage('videoDummyCover');
        }

        $coverFile = $coverFile ? $coverFile : $filename;
        $filename = $coverFile;
        return new File($coverFile, true);
    }

    private function getMetaData($serialized, string $language): array {
        if (!$serialized) {
            return array();
        }
        $arrData = StringUtil::deserialize($serialized);
        if (!is_array($arrData)) {
            return array();
        }
        if (isset($arrData[$language])) {
            return $arrData[$language];
        }
        if (isset($arrData['en'])) {
            return $arrData['en'];
        }
        return array();
    }
}


