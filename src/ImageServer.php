<?php

namespace Giantpeach\WpModernImages;

class ImageServer
{
    private Cache $cache;

    public function __construct()
    {
        $this->cache = Cache::getInstance();
    }

    public function serve(string $imagePath): void
    {
        $filePath = $this->cache->getFilePath($imagePath);

        if ($filePath === '' || !file_exists($filePath)) {
            status_header(404);
            exit;
        }

        // Rejecting '..' in the requested path is not enough on its own: a
        // symlink inside the cache directory still resolves outside it, and no
        // amount of string inspection catches that. Resolve the path for real
        // and confirm where it landed before reading a byte of it.
        $realPath = realpath($filePath);
        $cacheRoot = realpath($this->cache->getCachePath());

        if ($realPath === false || $cacheRoot === false) {
            status_header(404);
            exit;
        }

        // realpath() returns backslash-separated paths on Windows, so fold
        // both sides to forward slashes before comparing prefixes.
        $realPath = str_replace('\\', '/', $realPath);
        $cacheRoot = rtrim(str_replace('\\', '/', $cacheRoot), '/') . '/';

        if (strpos($realPath, $cacheRoot) !== 0) {
            status_header(404);
            exit;
        }

        $filePath = $realPath;

        // Only ever serve the image types this plugin generates. Previously an
        // unrecognised type fell through to application/octet-stream and was
        // still sent, which turned any readable file into a download.
        $mimeType = $this->getMimeType($filePath);

        if ($mimeType === null) {
            status_header(404);
            exit;
        }

        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        $etag = md5($filePath . $lastModified);

        // Handle conditional requests
        if ($this->isNotModified($etag, $lastModified)) {
            status_header(304);
            exit;
        }

        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('ETag: "' . $etag . '"');
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

        readfile($filePath);
        exit;
    }

    /**
     * The file's MIME type, or null when it is not an image this plugin serves.
     */
    private function getMimeType(string $filePath): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        // Whitelist valid image types
        $validTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
        ];

        if (in_array($mimeType, $validTypes, true)) {
            return $mimeType;
        }

        return null;
    }

    private function isNotModified(string $etag, int $lastModified): bool
    {
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        if ($ifNoneMatch && trim($ifNoneMatch, '"') === $etag) {
            return true;
        }

        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return true;
        }

        return false;
    }
}
