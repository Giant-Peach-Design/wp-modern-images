<?php

namespace Giantpeach\WpModernImages;

class Cache
{
    private static ?Cache $instance = null;
    private string $cachePath;
    private string $cacheUrl;

    public static function getInstance(): Cache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->cachePath = $this->resolveCachePath();
        $this->cacheUrl = $this->resolveCacheUrl();
        $this->ensureCacheDirectoryExists();
    }

    private function resolveCachePath(): string
    {
        if (defined('WP_MODERN_IMAGES_CACHE_DIR')) {
            $path = WP_MODERN_IMAGES_CACHE_DIR;
        } else {
            $path = WP_CONTENT_DIR . '/cache/wp-modern-images';
        }

        return apply_filters('gp_modern_images_cache_path', $path);
    }

    private function resolveCacheUrl(): string
    {
        if (defined('WP_MODERN_IMAGES_CACHE_URL')) {
            $url = WP_MODERN_IMAGES_CACHE_URL;
        } else {
            $url = content_url('/cache/wp-modern-images');
        }

        return apply_filters('gp_modern_images_cache_url', $url);
    }

    private function ensureCacheDirectoryExists(): void
    {
        if (!file_exists($this->cachePath)) {
            wp_mkdir_p($this->cachePath);
        }
    }

    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    public function getCacheUrl(): string
    {
        return $this->cacheUrl;
    }

    public function getFileUrl(string $relativePath): string
    {
        return trailingslashit($this->cacheUrl) . ltrim($relativePath, '/');
    }

    public function getFilePath(string $relativePath): string
    {
        return trailingslashit($this->cachePath) . ltrim($relativePath, '/');
    }

    public function fileExists(string $relativePath): bool
    {
        return file_exists($this->getFilePath($relativePath));
    }
}
