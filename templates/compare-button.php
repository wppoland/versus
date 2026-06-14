<?php
/**
 * Compare trigger button (used for both loop and single product placements).
 *
 * Rendered by the storefront-kit CompareEngine on
 * `woocommerce_after_shop_loop_item` and `woocommerce_single_product_summary`.
 *
 * @var \WC_Product          $product
 * @var array<string, mixed> $settings
 * @var array{product_id: int, in_compare: bool, label: string, count: int, compare_url: string} $button
 *
 * @package Versus/Templates
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the template include scope.

declare(strict_types=1);

defined('ABSPATH') || exit;

$compare_link_text = (string) ($settings['compare_link_text'] ?? '');
if ('' === $compare_link_text) {
    $compare_link_text = __('View comparison', 'versus');
}
?>
<div class="versus-compare">
    <button
        type="button"
        class="button versus-compare-button<?php echo $button['in_compare'] ? ' is-active' : ''; ?>"
        data-versus-compare-button
        data-product-id="<?php echo esc_attr((string) $button['product_id']); ?>"
        aria-pressed="<?php echo $button['in_compare'] ? 'true' : 'false'; ?>"
    >
        <?php echo esc_html($button['label']); ?>
    </button>
    <a class="versus-compare-link" href="<?php echo esc_url($button['compare_url']); ?>">
        <?php echo esc_html($compare_link_text); ?>
    </a>
</div>
