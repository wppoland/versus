<?php
/**
 * Comparison table (account page / compare endpoint).
 *
 * Rendered by the storefront-kit CompareEngine via the injected renderTable
 * closure. Reserved space: the table wrapper scrolls horizontally so adding
 * columns never reflows surrounding content.
 *
 * @var list<\WC_Product>    $products
 * @var list<array{key: string, label: string, values: array<int, string>, text_values: array<int, string>}> $rows
 * @var array<string, bool>  $differences
 * @var array<string, mixed> $settings
 * @var string               $feature_label
 *
 * @package Versus/Templates
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the template include scope.

declare(strict_types=1);

defined('ABSPATH') || exit;

$highlight     = (bool) ($settings['highlight_differences'] ?? true);
$only_diff     = (bool) ($settings['show_only_differences'] ?? false);
$show_image    = (bool) ($settings['show_product_image'] ?? true);
$show_cart     = (bool) ($settings['show_add_to_cart'] ?? true);
$show_remove   = (bool) ($settings['show_remove_button'] ?? true);
$remove_label  = (string) ($settings['button_remove_text'] ?? '');
$clear_text    = (string) ($settings['clear_text'] ?? '');
$empty_text    = (string) ($settings['empty_text'] ?? '');
$toggle_text   = (string) ($settings['differences_toggle_text'] ?? '');

$remove_label  = '' !== $remove_label ? $remove_label : __('Remove', 'versus');
$clear_text    = '' !== $clear_text ? $clear_text : __('Clear all', 'versus');
$empty_text    = '' !== $empty_text ? $empty_text : __('No products added to compare yet.', 'versus');
$toggle_text   = '' !== $toggle_text ? $toggle_text : __('Show only differences', 'versus');
?>
<div class="versus-compare-account">
    <div class="versus-compare-account__header">
        <h2><?php echo esc_html($feature_label !== '' ? $feature_label : __('Compare products', 'versus')); ?></h2>

        <?php if ($products !== []) : ?>
            <div class="versus-compare-actions">
                <label class="versus-compare-toggle">
                    <input type="checkbox" data-versus-compare-differences <?php checked($only_diff); ?> />
                    <span><?php echo esc_html($toggle_text); ?></span>
                </label>

                <button type="button" class="button" data-versus-compare-clear>
                    <?php echo esc_html($clear_text); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($products === []) : ?>
        <p><?php echo esc_html($empty_text); ?></p>
    <?php else : ?>
        <div class="versus-compare-table-wrapper">
            <table class="shop_table shop_table_responsive versus-compare-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html($feature_label); ?></th>
                        <?php foreach ($products as $product) : ?>
                            <th class="versus-compare-product">
                                <a href="<?php echo esc_url(get_permalink($product->get_id()) ?: ''); ?>">
                                    <?php if ($show_image) : ?>
                                        <?php echo $product->get_image('woocommerce_thumbnail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce returns escaped <img> markup. ?>
                                    <?php endif; ?>
                                    <span class="versus-compare-product__name"><?php echo esc_html($product->get_name()); ?></span>
                                </a>
                                <div class="versus-compare-product__actions">
                                    <?php if ($show_cart && $product->is_purchasable() && $product->is_in_stock()) : ?>
                                        <a
                                            href="<?php echo esc_url($product->add_to_cart_url()); ?>"
                                            data-quantity="1"
                                            class="button add_to_cart_button<?php echo $product->supports('ajax_add_to_cart') ? ' ajax_add_to_cart' : ''; ?>"
                                            data-product_id="<?php echo esc_attr((string) $product->get_id()); ?>"
                                            data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
                                            aria-label="<?php echo esc_attr(wp_strip_all_tags($product->add_to_cart_description())); ?>"
                                            rel="nofollow"
                                        >
                                            <?php echo esc_html($product->add_to_cart_text()); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($show_remove) : ?>
                                        <button
                                            type="button"
                                            class="button versus-compare-button is-active"
                                            data-versus-compare-button
                                            data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>"
                                            aria-pressed="true"
                                        >
                                            <?php echo esc_html($remove_label); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <?php $is_different = $differences[$row['key']] ?? false; ?>
                        <tr
                            data-different="<?php echo $is_different ? '1' : '0'; ?>"
                            class="<?php echo esc_attr($highlight && $is_different ? 'is-different' : ''); ?>"
                        >
                            <th><?php echo esc_html($row['label']); ?></th>
                            <?php foreach ($row['values'] as $value) : ?>
                                <td><?php echo $value !== '-' ? wp_kses_post($value) : esc_html($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
