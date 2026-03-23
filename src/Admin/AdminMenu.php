<?php

declare(strict_types=1);

namespace FP\CartRecovery\Admin;

use FP\CartRecovery\Domain\Settings;

/**
 * Registra le voci di menu admin per FP Cart Recovery.
 */
final class AdminMenu {

    public const SETTINGS_SLUG = 'fp_cartrecovery_settings';

    public function __construct(
        private readonly Settings $settings
    ) {}

    public function register(): void {
        add_action('admin_menu', [$this, 'add_submenus'], 20);
    }

    public const HELP_SLUG = 'fp_cartrecovery_help';

    public function add_submenus(): void {
        add_submenu_page(
            'fp_cartrecovery_dashboard',
            __('Impostazioni', 'fp-cartrecovery'),
            __('Impostazioni', 'fp-cartrecovery'),
            'manage_options',
            self::SETTINGS_SLUG,
            [$this, 'render_settings']
        );
        add_submenu_page(
            'fp_cartrecovery_dashboard',
            __('Guida', 'fp-cartrecovery'),
            __('Guida', 'fp-cartrecovery'),
            'manage_options',
            self::HELP_SLUG,
            [$this, 'render_help']
        );
    }

    public function render_help(): void {
        $page = new HelpPage();
        $page->render();
    }

    public function render_settings(): void {
        $page = new SettingsPage($this->settings);
        $page->render();
    }
}
