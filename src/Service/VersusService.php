<?php

declare(strict_types=1);

namespace Versus\Service;

defined('ABSPATH') || exit;

use Versus\Contract\HasHooks;
use Versus\Repository\CompareRepository;
use WPPoland\StorefrontKit\Compare\CompareEngine;

/**
 * Thin adapter over the storefront-kit {@see CompareEngine}.
 *
 * Injects this plugin's text-domain ('versus'), option prefix ('versus_'),
 * asset URLs and labels into the namespace-neutral engine, and supplies the
 * closures the engine needs: one to render the packaged loop/single buttons,
 * one to render the comparison table, and a field resolver for any non-standard
 * column. All compare orchestration (endpoint, nonce, enqueue, AJAX, guest
 * cookie, difference highlighting) lives in the kit; this class only supplies
 * localisation, option storage, asset paths and the table/button markup.
 */
final class VersusService implements HasHooks
{
    private const OPTION = 'versus_settings';

    private ?CompareEngine $engine = null;

    public function __construct(
        private readonly CompareRepository $repository,
    ) {
        // The engine ships with storefront-kit >= 1.4.0. When present, wire it
        // with this plugin's text-domain / option prefix / asset URLs. Otherwise
        // leave the service inert (see registerHooks()).
        if (! class_exists(CompareEngine::class)) {
            return;
        }

        $this->engine = new CompareEngine(
            repository: $this->repository,
            ajaxAction: 'versus_compare_toggle',
            clearAjaxAction: 'versus_compare_clear',
            nonceAction: 'versus_compare',
            scriptObjectName: 'versusCompare',
            assetHandle: 'versus',
            styleUrl: \Versus\Plugin::instance()->url('assets/css/compare.css'),
            scriptUrl: \Versus\Plugin::instance()->url('assets/js/compare.js'),
            version: \Versus\VERSION,
            endpoint: 'versus-compare',
            guestCookie: 'versus_compare_session',
            loopButtonTemplate: 'compare-button',
            singleButtonTemplate: 'compare-button',
            tableTemplate: 'compare-table',
            comparisonFields: $this->comparisonFields(),
            labels: [
                'add'            => __('Compare', 'plogins-versus'),
                'remove'         => __('Remove', 'plogins-versus'),
                'account'        => __('Compare', 'plogins-versus'),
                'feature'        => __('Feature', 'plogins-versus'),
                'login_required' => __('Please log in to compare products.', 'plogins-versus'),
                'not_found'      => __('Product not found.', 'plogins-versus'),
                'clear_error'    => __('Could not clear the comparison.', 'plogins-versus'),
                'limit_notice'   => __('You can compare up to {limit} products. The oldest item was removed.', 'plogins-versus'),
            ],
            isEnabled: fn (): bool => $this->isEnabled(),
            settings: fn (): array => $this->settings(),
            renderTemplate: function (string $template, array $context): void {
                $this->renderTemplate($template, $context);
            },
            renderTable: function (string $template, array $context): string {
                ob_start();
                $this->renderTemplate($template, $context);

                return (string) ob_get_clean();
            },
            fieldResolver: fn (\WC_Product $product, string $key): array => ['-', '-'],
        );
    }

    public function registerHooks(): void
    {
        if ($this->engine instanceof CompareEngine) {
            $this->engine->registerHooks();

            // The kit localises window.versusCompare on wp_enqueue_scripts@10
            // but does not include a generic failure string; compare.js reads
            // `config.errorText` to announce network/HTTP errors to assistive
            // tech. Merge it in after the kit has enqueued (priority 20) without
            // touching the kit.
            add_action('wp_enqueue_scripts', [$this, 'localiseErrorText'], 20);

            return;
        }

        // TODO: storefront-kit < 1.4.0 has no CompareEngine. Bump the
        // `wppoland/storefront-kit` constraint (composer update) to enable the
        // comparison. No hooks are registered until the engine is present.
    }

    /**
     * Add a translatable failure message to the already-localised
     * `versusCompare` object so compare.js can announce errors. No-op unless the
     * kit actually enqueued the script for this request.
     */
    public function localiseErrorText(): void
    {
        if (! wp_script_is('versus', 'enqueued')) {
            return;
        }

        $errorText = __('Something went wrong. Please try again.', 'plogins-versus');

        wp_add_inline_script(
            'versus',
            'window.versusCompare = Object.assign(window.versusCompare || {}, ' . wp_json_encode(['errorText' => $errorText]) . ');',
            'before',
        );
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    /**
     * Ordered map of field key => row label for the standard fields the merchant
     * has enabled. Resolved by the engine itself (price/sku/availability/
     * description).
     *
     * @return array<string, string>
     */
    private function comparisonFields(): array
    {
        $labels = [
            'price'        => __('Price', 'plogins-versus'),
            'sku'          => __('SKU', 'plogins-versus'),
            'availability' => __('Availability', 'plogins-versus'),
            'description'  => __('Description', 'plogins-versus'),
        ];

        $enabled = $this->settings()['fields'] ?? [];
        $enabled = is_array($enabled) ? $enabled : [];

        $fields = [];

        foreach ($labels as $key => $label) {
            if (! empty($enabled[$key])) {
                $fields[$key] = $label;
            }
        }

        return $fields;
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

    /**
     * @param array<string, mixed> $context
     */
    private function renderTemplate(string $template, array $context): void
    {
        $file = VERSUS_DIR . 'templates/' . $template . '.php';

        if (! is_readable($file)) {
            return;
        }

        extract($context, EXTR_SKIP);
        require $file;
    }
}
