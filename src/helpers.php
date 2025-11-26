<?php

use Giantpeach\WpModernImages\Image;

if (!function_exists('gp_image')) {
    function gp_image(int $imageId, array $sizes, array $options = []): string
    {
        $image = new Image($imageId, $sizes, $options);
        return $image->render();
    }
}
