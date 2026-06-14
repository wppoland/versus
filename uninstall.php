<?php
/**
 * Uninstall cleanup for Versus.
 *
 * Runs only when the plugin is deleted from wp-admin. Removes the options and
 * the custom compare-items table this plugin created. Guarded by the WordPress
 * uninstall constant so it can never run in any other context.
 *
 * @package Versus
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('versus_settings');
delete_option('versus_db_version');

global $wpdb;

// Drop the plugin's own compare-items table. The name is built from the WP
// table prefix and a fixed, plugin-owned suffix; it is not user input.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query(
    $wpdb->prepare('DROP TABLE IF EXISTS %i', $wpdb->prefix . 'versus_compare_items')
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
