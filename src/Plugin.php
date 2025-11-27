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
        $this->registerHooks();
    }

    private function loadHelpers(): void
    {
        require_once WP_MODERN_IMAGES_PATH . 'src/helpers.php';
    }

    private function registerHooks(): void
    {
        add_action('init', [$this, 'addRewriteRules']);
        add_filter('query_vars', [$this, 'addQueryVars']);
        add_action('template_redirect', [$this, 'handleImageRequest']);
        add_filter('redirect_canonical', [$this, 'preventTrailingSlashRedirect'], 10, 2);
        add_action('acorn/init', [$this, 'registerBladeDirective']);
    }

    public function addRewriteRules(): void
    {
        add_rewrite_rule(
            '^img/(.+)$',
            'index.php?gp_image_path=$matches[1]',
            'top'
        );
    }

    public function addQueryVars(array $vars): array
    {
        $vars[] = 'gp_image_path';
        return $vars;
    }

    public function handleImageRequest(): void
    {
        $imagePath = get_query_var('gp_image_path');

        if (!$imagePath) {
            return;
        }

        $server = new ImageServer();
        $server->serve($imagePath);
    }

    public function preventTrailingSlashRedirect($redirect_url, $requested_url)
    {
        if (get_query_var('gp_image_path')) {
            return false;
        }

        return $redirect_url;
    }

    public function registerBladeDirective(): void
    {
        \Roots\Acorn\Application::getInstance()
            ->make('blade.compiler')
            ->directive('gpImage', function ($expression) {
                return "<?php echo gp_image({$expression}); ?>";
            });
    }

    public static function activate(): void
    {
        $instance = self::init();
        $instance->addRewriteRules();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
