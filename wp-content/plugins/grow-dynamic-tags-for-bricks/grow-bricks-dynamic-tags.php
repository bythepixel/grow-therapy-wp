<?php
/**
 * Plugin Name: Grow • Bricks Dynamic Tags
 * Description: Custom Bricks dynamic tags: {gt_ctx:...} (host page context) and {gt_field:...} (meta fetcher with nested tag rendering & formatters).
 * Version:     1.2.1
 * Author:      Grow
 * Requires PHP: 7.4
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Boot after theme so Bricks is available
add_action('after_setup_theme', static function (): void {
    // Check if Bricks is available
    if (!defined('BRICKS_VERSION') && !function_exists('bricks_render_dynamic_data') && !did_action('bricks/init')) {
        add_action('admin_notices', static function (): void {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            echo '<div class="notice notice-error"><p><strong>Grow • Bricks Dynamic Tags</strong> requires the Bricks theme to be active.</p></div>';
        });
        return;
    }

    // Autoload classes
    $base = __DIR__ . '/src';
    require_once $base . '/plugin.php';
    require_once $base . '/rendering/content-renderer.php';
    require_once $base . '/rendering/finalizer.php';
    require_once $base . '/tags/gt-field-tag.php';
    require_once $base . '/tags/gt-ctx-tag.php';
    require_once $base . '/support/parser.php';
    require_once $base . '/support/context.php';

    \Grow\BricksTags\Plugin::init();
});
