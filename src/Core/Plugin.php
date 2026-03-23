<?php

declare(strict_types=1);

namespace FP\CartRecovery\Core;

use FP\CartRecovery\Admin\AdminMenu;
use FP\CartRecovery\Domain\Settings;
use FP\CartRecovery\Integrations\CartTracker;
use FP\CartRecovery\Integrations\EmailScheduler;
use FP\CartRecovery\Integrations\RecoveryHandler;

/**
 * Bootstrap del plugin FP Cart Recovery.
 *
 * @see https://github.com/franpass87/FP-Cart-Recovery
 */
final class Plugin {

    private static ?self $instance = null;

    private Settings $settings;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->settings = new Settings();
    }

    public function init(): void {
        $this->check_requirements();
        $this->run_migrations();
        $this->register_hooks();
    }

    private function check_requirements(): void {
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            add_action('admin_notices', fn () => $this->render_requirement_notice(
                __('FP Cart Recovery richiede PHP 8.0 o superiore.', 'fp-cartrecovery')
            ));
            return;
        }

        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', fn () => $this->render_requirement_notice(
                __('FP Cart Recovery richiede WooCommerce attivo.', 'fp-cartrecovery')
            ));
            return;
        }
    }

    private function render_requirement_notice(string $message): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html($message)
        );
    }

    private function run_migrations(): void {
        Migrations::run();
    }

    private function register_hooks(): void {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_filter('admin_body_class', [$this, 'add_admin_body_class']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        if (!class_exists('WooCommerce')) {
            return;
        }

        $cart_tracker = new CartTracker($this->settings);
        $cart_tracker->register();

        $recovery_handler = new RecoveryHandler($this->settings);
        $recovery_handler->register();

        if ($this->settings->get('enabled', false)) {
            $email_scheduler = new EmailScheduler($this->settings);
            $email_scheduler->register();
        }

        $admin_menu = new AdminMenu($this->settings);
        $admin_menu->register();

        $admin_ajax = new \FP\CartRecovery\Admin\AdminAjax($this->settings);
        $admin_ajax->register();
    }

    public function register_admin_menu(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        add_menu_page(
            __('FP Cart Recovery', 'fp-cartrecovery'),
            __('FP Cart Recovery', 'fp-cartrecovery'),
            'manage_options',
            'fp_cartrecovery_dashboard',
            [$this, 'render_dashboard'],
            'dashicons-cart',
            '56.12'
        );
    }

    /**
     * Callback per la pagina principale (Dashboard).
     */
    public function render_dashboard(): void {
        $page = new \FP\CartRecovery\Admin\DashboardPage($this->settings);
        $page->render();
    }

    public function add_admin_body_class(string $classes): string {
        $screen = get_current_screen();
        if (!$screen) {
            return $classes;
        }
        $screen_id = $screen->id ?? '';
        if (str_contains($screen_id, 'fp_cartrecovery') && !str_contains($classes, 'fpcartrecovery-admin-shell')) {
            $classes .= ' fpcartrecovery-admin-shell';
        }
        return $classes;
    }

    /**
     * Enqueue CSS/JS admin.
     *
     * @param string $hook Pagina admin corrente.
     */
    public function enqueue_admin_assets(string $hook): void {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $is_our_page = str_contains($hook, 'fp_cartrecovery')
            || in_array($page, ['fp_cartrecovery_dashboard', 'fp_cartrecovery_settings'], true);

        if (!$is_our_page) {
            return;
        }

        if ($page === 'fp_cartrecovery_settings') {
            wp_enqueue_media();
        }

        wp_enqueue_style(
            'fp-cartrecovery-admin',
            FP_CARTRECOVERY_URL . 'assets/css/admin.css',
            [],
            FP_CARTRECOVERY_VERSION
        );

        wp_enqueue_script(
            'fp-cartrecovery-admin',
            FP_CARTRECOVERY_URL . 'assets/js/admin.js',
            ['jquery'],
            FP_CARTRECOVERY_VERSION,
            true
        );

        wp_localize_script('fp-cartrecovery-admin', 'fpCartRecoveryConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fp_cartrecovery_admin'),
            'i18n'    => [
                'confirmDelete' => __('Eliminare questo carrello abbandonato?', 'fp-cartrecovery'),
                'copied'        => __('Link copiato negli appunti.', 'fp-cartrecovery'),
                'deleted'       => __('Carrello eliminato.', 'fp-cartrecovery'),
                'testEmailSent' => __('Email di prova inviata.', 'fp-cartrecovery'),
                'selectLogo'    => __('Seleziona logo', 'fp-cartrecovery'),
                'useImage'      => __('Usa questa immagine', 'fp-cartrecovery'),
            ],
        ]);
    }

    public function get_settings(): Settings {
        return $this->settings;
    }
}
