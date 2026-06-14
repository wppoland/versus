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

// Translations: no manual load_plugin_textdomain() call. WordPress 4.6+ loads
// translations for wp.org-hosted plugins automatically (just-in-time) from the
// plugin slug, and the bundled languages/versus.pot lets translators get
// started. The `Domain Path: /languages` header points WP at the local files.
add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Versus requires WooCommerce to be active.', 'versus');
            echo '</p></div>';
        });
        return;
    }

    // Boot on init:0 (not synchronously in plugins_loaded) so services that call
    // __()/translation functions don't run before the `init` hook — loading a text
    // domain earlier triggers WordPress 6.7+'s _load_textdomain_just_in_time
    // notice. Plugin::boot() fires the `versus/booted` action once it has
    // registered its services, so PRO companions can hook there reliably.
    add_action('init', static function (): void {
        Plugin::instance()->boot();
    }, 0);
});
