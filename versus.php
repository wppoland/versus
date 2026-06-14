<?php
/**
 * Plugin Name:       Versus - Product Compare for WooCommerce
 * Plugin URI:        https://plogins.com/versus/
 * Description:        Fast, accessible product comparison for WooCommerce - compare table with difference highlighting, guest + customer lists, no jQuery
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            WPPoland
 * Author URI:        https://plogins.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       versus
 * Domain Path:       /languages
 * WC requires at least: 8.0
 *
 * @package Versus
 */

declare(strict_types=1);

namespace Versus;

defined('ABSPATH') || exit;

const VERSION     = '0.2.0';
const PLUGIN_FILE = __FILE__;

define('VERSUS_DIR', plugin_dir_path(__FILE__));
define('VERSUS_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

// HPOS + cart/checkout blocks compatibility.
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Load translations from /languages on init (translators may ship local .mo
// files; wp.org language packs load automatically). Hooked on init per the
// WordPress 6.7 just-in-time loading guidance.
add_action('init', static function (): void {
    load_plugin_textdomain('versus', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Versus requires WooCommerce to be active.', 'versus');
            echo '</p></div>';
        });
        return;
    }

    Plugin::instance()->boot();
    do_action('versus/booted', Plugin::instance());
});
