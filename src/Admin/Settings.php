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
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            __('Versus Settings', 'versus'),
            __('Versus', 'versus'),
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
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable comparison', 'versus'); ?></th>
                            <td>
                                <label for="versus_enabled">
                                    <input
                                        type="checkbox"
                                        id="versus_enabled"
                                        name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                        value="1"
                                        <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                    />
                                    <?php esc_html_e('Show the compare button and enable the comparison table.', 'versus'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="versus_max_items"><?php esc_html_e('Maximum products', 'versus'); ?></label>
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
                                <p class="description"><?php esc_html_e('How many products a shopper can compare at once (2–6).', 'versus'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Placement & access', 'versus'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->checkboxRow('show_on_loop', __('Shop & archive loops', 'versus'), __('Show the compare button under each product in loops.', 'versus'), $settings);
                        $this->checkboxRow('show_on_single', __('Single product page', 'versus'), __('Show the compare button on the single product page.', 'versus'), $settings);
                        $this->checkboxRow('allow_guests', __('Allow guests', 'versus'), __('Let logged-out visitors build a comparison (stored per browser).', 'versus'), $settings);
                        $this->checkboxRow('show_in_account', __('Account menu', 'versus'), __('Add a "Compare" tab to the My Account menu for logged-in customers.', 'versus'), $settings);
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Comparison table', 'versus'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Choose which standard fields appear as rows. Rows that differ between products can be highlighted.', 'versus'); ?>
                </p>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->fieldCheckboxRow('price', __('Price', 'versus'), $fields);
                        $this->fieldCheckboxRow('sku', __('SKU', 'versus'), $fields);
                        $this->fieldCheckboxRow('availability', __('Availability', 'versus'), $fields);
                        $this->fieldCheckboxRow('description', __('Short description', 'versus'), $fields);
                        $this->checkboxRow('show_attributes', __('Product attributes', 'versus'), __('Add a row for each product attribute (colour, size, material, …).', 'versus'), $settings);
                        $this->checkboxRow('highlight_differences', __('Highlight differences', 'versus'), __('Visually highlight rows whose values differ between products.', 'versus'), $settings);
                        $this->checkboxRow('show_only_differences', __('Default to differences only', 'versus'), __('Tick the "show only differences" toggle by default.', 'versus'), $settings);
                        $this->checkboxRow('show_product_image', __('Product image', 'versus'), __('Show the product image in each column header.', 'versus'), $settings);
                        $this->checkboxRow('show_add_to_cart', __('Add to cart', 'versus'), __('Show an add-to-cart button in each column header.', 'versus'), $settings);
                        $this->checkboxRow('show_remove_button', __('Remove button', 'versus'), __('Show a remove button in each column header.', 'versus'), $settings);
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Labels &amp; text', 'versus'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Customise the front-end strings. Leave a field empty to use the default translation.', 'versus'); ?>
                </p>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->textRow('button_add_text', __('"Compare" button', 'versus'), __('Compare', 'versus'), $settings);
                        $this->textRow('button_remove_text', __('"Remove" button', 'versus'), __('Remove', 'versus'), $settings);
                        $this->textRow('compare_link_text', __('Compare link', 'versus'), __('View comparison', 'versus'), $settings);
                        $this->textRow('differences_toggle_text', __('Differences toggle', 'versus'), __('Show only differences', 'versus'), $settings);
                        $this->textRow('clear_text', __('Clear-all button', 'versus'), __('Clear all', 'versus'), $settings);
                        $this->textRow('empty_text', __('Empty comparison message', 'versus'), __('No products added to compare yet.', 'versus'), $settings);
                        ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a single boolean-setting checkbox row.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings): void
    {
        $id = 'versus_' . $key;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
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
    private function fieldCheckboxRow(string $key, string $label, array $fields): void
    {
        $id = 'versus_field_' . $key;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
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
                    echo esc_html(sprintf(__('Show the %s row.', 'versus'), $label));
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
    private function textRow(string $key, string $label, string $placeholder, array $settings): void
    {
        $id    = 'versus_' . $key;
        $value = isset($settings[$key]) && is_string($settings[$key]) ? $settings[$key] : '';
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
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
