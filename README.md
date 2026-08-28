Leading Systems Contao Merconis bundle
=================================

Overview
--------
This bundle provides the MERCONIS shop system for Contao. It includes infrastructure for product rendering, image handling, and a standardized caching layer via the MerconisCache services.

Image processing cache
----------------------
MERCONIS computes product image galleries (main image + more images) from file system data and Contao file metadata. This can be expensive due to file I/O, metadata lookups, and sorting. A cross-request cache stores the processed gallery payload and an HTML fragment for the product details gallery to reduce latency and server load.

What is cached
- Gallery payload (value mode):
  - Keyed by: language, sort mode, overlays, list of image paths + their mtimes, optional main image + its mtime, cache version, and whether the main image is included.
  - Value: array with `images` (list of plain arrays with file/metadata) and optional `mainImage` entry.
- File metadata (value mode):
  - Keyed by: file path and language.
  - Value: Contao file metadata array (alt, title, link, caption). TTL: 6 hours.
- Product details gallery fragment (echo/capture):
  - Keyed by: language, product code, variant flag, sort mode, list of image paths + mtimes, main image + mtime, cache version.
  - Value: Rendered HTML fragment.

Runtime policies
- Enable/disable cache via backend.
- Per-entry TTL (hours) for gallery payload and fragment.
- Skip caching completely when random sorting is active (optional setting).
- Bypass read for backend users (optional); still warm after computation if enabled.
- Warm only in production (optional).
- Emit Server-Timing headers (optional) to expose hit/miss/bypass timings.
- In-request memoization keeps already-processed files for the current request.

MerconisCache services
----------------------
MERCONIS centralizes caching via two services:
- `LeadingSystems\MerconisBundle\Cache\MerconisCache`: low-level API for value computation with stampede protection and tag-based invalidation.
- `LeadingSystems\MerconisBundle\Cache\MerconisCacheHandler`: ergonomic helper with three usage modes and built-in compute locks.

Service registration (excerpt)
- Service IDs are public and autowired (see `Resources/config/services.yml`).
- Namespace prefix used: `merconis.custom.cache.`

Three usage modes
-----------------
The buffer exposes three ways to use the cache, matching typical rendering patterns.

1) Echo mode (streaming with fallback)
- Use when you want to immediately echo cached HTML if present, otherwise compute and echo while storing.
- Flow:
  - `$cacheHandle = $cacheHandler->create($ttlSeconds, $tags);`
  - `if ($cacheHandle->start()) { return; }` // cache hit already echoed
  - Output content into an output buffer
  - `$cacheHandle->finish();` // stores and echoes
- Guarantees:
  - Avoids duplicate echo.
  - Protects against stampedes (compute lock).

2) Capture mode (get or build string)
- Use when you need the string result for further handling.
- Flow:
  - `$cacheHandle = $cacheHandler->create($ttlSeconds, $tags);`
  - `$cached = $cacheHandle->startCapture();`
  - `if ($cached !== null) { return $cached; }`
  - Output content into an output buffer
  - `$html = $cacheHandle->finishCapture();` // returns and stores the string

3) Value mode (arbitrary data)
- Use for any non-echo value (arrays, DTOs, etc.).
- Flow:
  - `$cacheHandle = $cacheHandler->create($ttlSeconds, $tags);`
  - `list($hit, $value) = $cacheHandle->getValueOrStart();`
  - `if ($hit) { return $value; }`
  - Compute `$value`
  - `$cacheHandle->storeValue($value);`

Tags and keying
---------------
- Tags are simple associative arrays. They are normalized and hashed to form a deterministic element key.
- Use tags that express the inputs: language, product code, variant flag, image list signature (path + mtime), sort mode, and a `v` tag for version.
- Tag indices support invalidation by tags via `MerconisCache::invalidateCacheElementsByTags($tags)`.

Stampede protection
-------------------
- Buffer sessions internally use short-lived compute locks keyed by tags.
- On miss, a process either becomes the producer or waits briefly for a value to appear; escalates to producer if necessary.

Integration points
------------------
- `Resources/contao/classes/productImageGallery.php`
  - Gallery payload (value mode) and file metadata (value mode) now use `MerconisCacheHandler`.
- `Resources/contao/templates/template_productIncludes_imageOutput_01.html5`
  - HTML fragment caching switched to `MerconisCacheHandler` echo mode, with warming when configured.

Backend settings
----------------
All settings remain supported and effective:
- `ls_shop_galleryCache_enabled` (checkbox)
- `ls_shop_galleryCache_ttlHours` (text; hours)
- `ls_shop_galleryCache_includeMainImage` (checkbox)
- `ls_shop_galleryCache_skipRandomSort` (checkbox)
- `ls_shop_galleryCache_disableForBEUsers` (checkbox)
- `ls_shop_galleryCache_warmOnBE` (checkbox)
- `ls_shop_galleryCache_warmOnlyProd` (checkbox)
- `ls_shop_galleryCache_exposeServerTiming` (checkbox)
- `ls_shop_galleryCache_version` (text)

Examples
--------
Echo mode (HTML fragment):
```php
$cacheHandler = System::getContainer()->get(\LeadingSystems\MerconisBundle\Cache\MerconisCacheHandler::class);
$tags = ['ns' => 'gallery.fragment', 'v' => 'v1', 'lang' => $lang, 'prod' => $code, 'sig' => $sig];
$cacheHandle = $cacheHandler->create(6*3600, $tags);
if ($cacheHandle->start()) { return; }
// ... echo HTML into buffer ...
$cacheHandle->finish();
```

Capture mode:
```php
$cacheHandle = $cacheHandler->create(3600, $tags);
$cached = $cacheHandle->startCapture();
if ($cached !== null) { return $cached; }
// ... echo HTML into buffer ...
$html = $cacheHandle->finishCapture();
return $html;
```

Value mode (payload):
```php
$cacheHandle = $cacheHandler->create(6*3600, $tags);
list($hit, $payload) = $cacheHandle->getValueOrStart();
if ($hit) { return $payload; }
$payload = ['images' => $images, 'mainImage' => $mainImage];
$cacheHandle->storeValue($payload);
return $payload;
```

Invalidation
------------
- To invalidate a set of elements addressed by tags (e.g., for a given product or language):
```php
$cache = System::getContainer()->get(\LeadingSystems\MerconisBundle\Cache\MerconisCache::class);
$cache->invalidateCacheElementsByTags(['ns' => 'gallery.images', 'lang' => $lang, 'prod' => $code]);
```

Notes
-----
- Always include a `v` (version) tag to perform global invalidations without iterating large tag sets.
- Prefer including file mtimes in signatures to ensure automatic invalidation on asset updates.
- Random sort is intentionally excluded from caching (configurable) to avoid confusing reorders.