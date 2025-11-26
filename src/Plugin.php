<?php

namespace Giantpeach\WpModernImages;

class Plugin
{
    private static ?Plugin $instance = null;

    public static function init(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->loadHelpers();
    }

    private function loadHelpers(): void
    {
        require_once WP_MODERN_IMAGES_PATH . 'src/helpers.php';
    }
}
