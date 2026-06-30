<?php

declare(strict_types=1);

namespace Versus\Admin;

defined('ABSPATH') || exit;

use Versus\Contract\HasHooks;

/**
 * Admin settings page registered as a top-level "Versus" menu.
 *
 * Stores settings in the `versus_settings` option (array): the master toggle,
 * where the compare button appears, guest access + per-list cap, and which
 * standard fields / product attributes render in the comparison table. All
 * output is escaped; all input is sanitised on save.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'versus_settings';
    private const PAGE   = 'versus-settings';

    /** @var list<string> */
    private const FIELD_KEYS = ['price', 'sku', 'availability', 'description'];

    /** Monotonic counter so each help tooltip gets a unique DOM id. */
    private int $tipSeq = 0;

    /**
     * Editable front-end label keys. Each is a plain-text string stored in the
     * settings option and consumed by the compare engine / templates.
     *
     * @var list<string>
     */
    private const LABEL_KEYS = [
        'button_add_text',
        'button_remove_text',
        'compare_link_text',
        'differences_toggle_text',
        'clear_text',
        'empty_text',
    ];

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue the settings-screen styles/script (real files — Plugin-Check
     * clean) only on the Versus settings page.
     */
    public function enqueueAssets(string $hook): void
    {
        if ('toplevel_page_' . self::PAGE !== $hook) {
            return;
        }

        $plugin = \Versus\Plugin::instance();

        wp_enqueue_style(
            'versus-admin',
            $plugin->url('assets/css/admin.css'),
            [],
            \Versus\VERSION,
        );

        wp_enqueue_script(
            'versus-admin',
            $plugin->url('assets/js/admin.js'),
            [],
            \Versus\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            __('Versus Settings', 'plogins-versus'),
            __('Versus', 'plogins-versus'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
            'dashicons-columns',
            58,
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        // The menu uses manage_woocommerce; align the options.php save capability
        // so shop managers (not just admins with manage_options) can save.
        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->settings();
        $fields   = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];
        ?>
        <div class="wrap versus-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <p class="versus-settings__intro">
                <?php
                echo wp_kses(
                    __('<strong>Versus</strong> lets shoppers line products up side by side and pick with confidence. Turn the comparison on, choose where the <em>Compare</em> button appears, and decide which details fill the comparison table. Hover or focus the <span aria-hidden="true">?</span> next to any option for a quick explanation.', 'plogins-versus'),
                    ['strong' => [], 'em' => [], 'span' => ['aria-hidden' => true]],
                );
                ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <h2><?php esc_html_e('General', 'plogins-versus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Enable comparison', 'plogins-versus'); ?>
                                <?php $this->helpTip(__('The master switch. When off, no compare buttons, account tab, or table are shown anywhere on the storefront, your other settings here are kept for when you turn it back on.', 'plogins-versus')); ?>
                            </th>
                            <td>
                                <label for="versus_enabled">
                                    <input
                                        type="checkbox"
                                        id="versus_enabled"
                                        name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                        value="1"
                                        <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                    />
                                    <?php esc_html_e('Show the compare button and enable the comparison table.', 'plogins-versus'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="versus_max_items"><?php esc_html_e('Maximum products', 'plogins-versus'); ?></label>
                                <?php $this->helpTip(__('The cap on how many products fit in one comparison. When a shopper adds one more than this, the oldest item drops off automatically so the table never gets unreadably wide. Two to six works best on most themes.', 'plogins-versus')); ?>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    id="versus_max_items"
                                    name="<?php echo esc_attr(self::OPTION); ?>[max_items]"
                                    value="<?php echo esc_attr((string) ($settings['max_items'] ?? 4)); ?>"
                                    min="2"
                                    max="6"
                                    class="small-text"
                                />
                                <p class="description"><?php esc_html_e('How many products a shopper can compare at once (2–6).', 'plogins-versus'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Placement & access', 'plogins-versus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->checkboxRow('show_on_loop', __('Shop & archive loops', 'plogins-versus'), __('Show the compare button under each product in loops.', 'plogins-versus'), $settings, __('Adds a Compare button beneath every product on the shop, category, tag, and search results pages, so shoppers can build a comparison while they browse.', 'plogins-versus'));
                        $this->checkboxRow('show_on_single', __('Single product page', 'plogins-versus'), __('Show the compare button on the single product page.', 'plogins-versus'), $settings, __('Adds a Compare button to each individual product page, near the add-to-cart area.', 'plogins-versus'));
                        $this->checkboxRow('allow_guests', __('Allow guests', 'plogins-versus'), __('Let logged-out visitors build a comparison (stored per browser).', 'plogins-versus'), $settings, __('When on, visitors who are not signed in can still compare; their selection is remembered in their own browser for six months. When off, clicking Compare sends them to log in first.', 'plogins-versus'));
                        $this->checkboxRow('show_in_account', __('Account menu', 'plogins-versus'), __('Add a "Compare" tab to the My Account menu for logged-in customers.', 'plogins-versus'), $settings, __('Adds a Compare tab inside My Account so signed-in customers can return to their saved comparison at any time. If you change this, save and then re-save Permalinks under Settings → Permalinks once.', 'plogins-versus'));
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Comparison table', 'plogins-versus'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Choose which standard fields appear as rows. Rows that differ between products can be highlighted.', 'plogins-versus'); ?>
                </p>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->fieldCheckboxRow('price', __('Price', 'plogins-versus'), $fields, __('Adds a Price row showing each product’s current price (including any sale price).', 'plogins-versus'));
                        $this->fieldCheckboxRow('sku', __('SKU', 'plogins-versus'), $fields, __('Adds a SKU row, useful for shops where customers reference part or model numbers.', 'plogins-versus'));
                        $this->fieldCheckboxRow('availability', __('Availability', 'plogins-versus'), $fields, __('Adds a stock-status row (In stock, Out of stock, On backorder) so shoppers can rule out unavailable options.', 'plogins-versus'));
                        $this->fieldCheckboxRow('description', __('Short description', 'plogins-versus'), $fields, __('Adds a row with each product’s short description for an at-a-glance summary.', 'plogins-versus'));
                        $this->checkboxRow('show_attributes', __('Product attributes', 'plogins-versus'), __('Add a row for each product attribute (colour, size, material, …).', 'plogins-versus'), $settings, __('Adds one row per product attribute (such as colour, size, or material). Only attributes that at least one of the compared products defines are shown.', 'plogins-versus'));
                        $this->checkboxRow('highlight_differences', __('Highlight differences', 'plogins-versus'), __('Visually highlight rows whose values differ between products.', 'plogins-versus'), $settings, __('Tints any row where the products’ values are not identical, so the things that set them apart jump out immediately.', 'plogins-versus'));
                        $this->checkboxRow('show_only_differences', __('Default to differences only', 'plogins-versus'), __('Tick the "show only differences" toggle by default.', 'plogins-versus'), $settings, __('Pre-ticks the “Show only differences” toggle on the comparison page, hiding rows that are the same. Shoppers can still untick it to see everything.', 'plogins-versus'));
                        $this->checkboxRow('show_product_image', __('Product image', 'plogins-versus'), __('Show the product image in each column header.', 'plogins-versus'), $settings, __('Shows each product’s thumbnail at the top of its column. Space is reserved for the image so the table never jumps as pictures load.', 'plogins-versus'));
                        $this->checkboxRow('show_add_to_cart', __('Add to cart', 'plogins-versus'), __('Show an add-to-cart button in each column header.', 'plogins-versus'), $settings, __('Adds an Add to cart button under each product so shoppers can buy straight from the comparison once they have decided.', 'plogins-versus'));
                        $this->checkboxRow('show_remove_button', __('Remove button', 'plogins-versus'), __('Show a remove button in each column header.', 'plogins-versus'), $settings, __('Lets shoppers drop a single product from the comparison without clearing the whole list.', 'plogins-versus'));
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Labels &amp; text', 'plogins-versus'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Customise the front-end strings. Leave a field empty to use the default translation.', 'plogins-versus'); ?>
                </p>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->textRow('button_add_text', __('"Compare" button', 'plogins-versus'), __('Compare', 'plogins-versus'), $settings, __('The wording on the button that adds a product to the comparison.', 'plogins-versus'));
                        $this->textRow('button_remove_text', __('"Remove" button', 'plogins-versus'), __('Remove', 'plogins-versus'), $settings, __('The wording shown once a product has been added, clicking it takes the product back out.', 'plogins-versus'));
                        $this->textRow('compare_link_text', __('Compare link', 'plogins-versus'), __('View comparison', 'plogins-versus'), $settings, __('The link, shown next to the button, that opens the full comparison table.', 'plogins-versus'));
                        $this->textRow('differences_toggle_text', __('Differences toggle', 'plogins-versus'), __('Show only differences', 'plogins-versus'), $settings, __('The label for the checkbox on the comparison page that hides identical rows.', 'plogins-versus'));
                        $this->textRow('clear_text', __('Clear-all button', 'plogins-versus'), __('Clear all', 'plogins-versus'), $settings, __('The button that empties the entire comparison in one click.', 'plogins-versus'));
                        $this->textRow('empty_text', __('Empty comparison message', 'plogins-versus'), __('No products added to compare yet.', 'plogins-versus'), $settings, __('The friendly message shown on the comparison page before any products have been added.', 'plogins-versus'));
                        ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a small, accessible "?" help affordance: a focusable button whose
     * tooltip is wired via aria-describedby. Reachable on hover/focus (CSS) and
     * toggleable by keyboard/click (admin.js); fully usable without JS.
     */
    private function helpTip(string $text): void
    {
        $tipId = 'versus-tip-' . (++$this->tipSeq);
        ?>
        <span class="versus-help">
            <button
                type="button"
                class="versus-help__toggle"
                aria-describedby="<?php echo esc_attr($tipId); ?>"
                aria-label="<?php esc_attr_e('More information', 'plogins-versus'); ?>"
            >?</button>
            <span class="versus-help__tip" id="<?php echo esc_attr($tipId); ?>" role="tooltip">
                <?php echo esc_html($text); ?>
            </span>
        </span>
        <?php
    }

    /**
     * Render a single boolean-setting checkbox row.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings, string $tip = ''): void
    {
        $id = 'versus_' . $key;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
                <?php if ('' !== $tip) { $this->helpTip($tip); } ?>
            </th>
            <td>
                <label for="<?php echo esc_attr($id); ?>">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($id); ?>"
                        name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                        value="1"
                        <?php checked((bool) ($settings[$key] ?? false), true); ?>
                    />
                    <?php echo esc_html($help); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Render a checkbox for one of the `fields[...]` comparison columns.
     *
     * @param array<string, mixed> $fields
     */
    private function fieldCheckboxRow(string $key, string $label, array $fields, string $tip = ''): void
    {
        $id = 'versus_field_' . $key;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
                <?php if ('' !== $tip) { $this->helpTip($tip); } ?>
            </th>
            <td>
                <label for="<?php echo esc_attr($id); ?>">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($id); ?>"
                        name="<?php echo esc_attr(self::OPTION); ?>[fields][<?php echo esc_attr($key); ?>]"
                        value="1"
                        <?php checked((bool) ($fields[$key] ?? false), true); ?>
                    />
                    <?php
                    /* translators: %s: comparison field label. */
                    echo esc_html(sprintf(__('Show the %s row.', 'plogins-versus'), $label));
                    ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Render a single text-input row for an editable front-end label.
     *
     * @param array<string, mixed> $settings
     */
    private function textRow(string $key, string $label, string $placeholder, array $settings, string $tip = ''): void
    {
        $id    = 'versus_' . $key;
        $value = isset($settings[$key]) && is_string($settings[$key]) ? $settings[$key] : '';
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
                <?php if ('' !== $tip) { $this->helpTip($tip); } ?>
            </th>
            <td>
                <input
                    type="text"
                    id="<?php echo esc_attr($id); ?>"
                    name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                    value="<?php echo esc_attr($value); ?>"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    class="regular-text"
                />
            </td>
        </tr>
        <?php
    }

    /**
     * Sanitises the submitted settings before save, preserving defaults for any
     * field not on the form.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $defaults = $this->settings();

        $maxItems = isset($raw['max_items']) ? (int) $raw['max_items'] : 4;
        $maxItems = max(2, min(6, $maxItems));

        $rawFields = is_array($raw['fields'] ?? null) ? $raw['fields'] : [];
        $fields    = [];

        foreach (self::FIELD_KEYS as $key) {
            $fields[$key] = ! empty($rawFields[$key]);
        }

        $labels = [];

        foreach (self::LABEL_KEYS as $key) {
            $value = isset($raw[$key]) && is_string($raw[$key])
                ? sanitize_text_field(wp_unslash($raw[$key]))
                : '';

            // Empty falls back to the packaged default (and its translation).
            if ($value !== '') {
                $labels[$key] = $value;
            } elseif (isset($defaults[$key]) && is_string($defaults[$key])) {
                $labels[$key] = $defaults[$key];
            }
        }

        return array_merge($defaults, $labels, [
            'enabled'               => ! empty($raw['enabled']),
            'max_items'             => $maxItems,
            'show_on_loop'          => ! empty($raw['show_on_loop']),
            'show_on_single'        => ! empty($raw['show_on_single']),
            'allow_guests'          => ! empty($raw['allow_guests']),
            'show_in_account'       => ! empty($raw['show_in_account']),
            'show_attributes'       => ! empty($raw['show_attributes']),
            'highlight_differences' => ! empty($raw['highlight_differences']),
            'show_only_differences' => ! empty($raw['show_only_differences']),
            'show_product_image'    => ! empty($raw['show_product_image']),
            'show_add_to_cart'      => ! empty($raw['show_add_to_cart']),
            'show_remove_button'    => ! empty($raw['show_remove_button']),
            'fields'                => $fields,
        ]);
    }

    /**
     * Stored settings merged over packaged defaults.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require VERSUS_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }
}
