<?php
/**
 * Plugin Name: WP Modern Images
 * Plugin URI: https://github.com/giantpeach/wp-modern-images
 * Description: Improved developer experience for working with WordPress media library images
 * Version: 1.0.0
 * Author: Giant Peach
 * Author URI: https://giantpeach.agency
 * Text Domain: wp-modern-images
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_MODERN_IMAGES_VERSION', '1.0.0');
define('WP_MODERN_IMAGES_URL', plugin_dir_url(__FILE__));
define('WP_MODERN_IMAGES_PATH', plugin_dir_path(__FILE__));

// Load Composer autoloader
$autoloader = WP_MODERN_IMAGES_PATH . 'vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Load plugin
add_action('plugins_loaded', function () {
    \Giantpeach\WpModernImages\Plugin::init();
});
