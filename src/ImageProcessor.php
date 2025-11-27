<?php

namespace Giantpeach\WpModernImages;

use League\Glide\ServerFactory;
use League\Glide\Server;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem;

class ImageProcessor
{
    private static ?ImageProcessor $instance = null;
    private ?Server $server = null;
    private Cache $cache;
    private string $uploadsPath;
    private string $uploadsUrl;

    public static function getInstance(): ImageProcessor
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->cache = Cache::getInstance();
        $uploadDir = wp_upload_dir();
        $this->uploadsPath = $uploadDir['basedir'];
        $this->uploadsUrl = $uploadDir['baseurl'];
    }

    private function getServer(): Server
    {
        if ($this->server === null) {
            $this->server = ServerFactory::create([
                'source' => new Filesystem(new LocalFilesystemAdapter($this->uploadsPath)),
                'cache' => new Filesystem(new LocalFilesystemAdapter($this->cache->getCachePath())),
                'driver' => extension_loaded('imagick') ? 'imagick' : 'gd',
            ]);
        }

        return $this->server;
    }

    public function process(int $attachmentId, array $params, string $format): ?string
    {
        $filePath = get_attached_file($attachmentId);

        if (!$filePath || !file_exists($filePath)) {
            return null;
        }

        $relativePath = str_replace($this->uploadsPath . '/', '', $filePath);

        $glideParams = [
            'w' => $params['w'],
            'h' => $params['h'],
            'fit' => $this->mapFitMode($params['fit'] ?? 'cover'),
            'fm' => $format,
            'q' => $params['q'] ?? 80,
        ];

        try {
            $cachedPath = $this->getServer()->makeImage($relativePath, $glideParams);
            return $this->cache->getFileUrl($cachedPath);
        } catch (\Exception $e) {
            error_log('WP Modern Images: Failed to process image - ' . $e->getMessage());
            return null;
        }
    }

    public function getOriginalFormat(int $attachmentId): string
    {
        $mimeType = get_post_mime_type($attachmentId);

        $formatMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $formatMap[$mimeType] ?? 'jpg';
    }

    public function getMimeType(string $format): string
    {
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        return $mimeMap[$format] ?? 'image/jpeg';
    }

    private function mapFitMode(string $fit): string
    {
        $fitMap = [
            'cover' => 'crop',
            'contain' => 'max',
            'fill' => 'stretch',
            'crop' => 'crop',
        ];

        return $fitMap[$fit] ?? 'crop';
    }
}
