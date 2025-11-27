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

        if (!file_exists($filePath)) {
            status_header(404);
            exit;
        }

        $mimeType = $this->getMimeType($filePath);
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

    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
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
