<?php
/**
 * Plugin Name:       FP Cart Recovery
 * Plugin URI:        https://github.com/franpass87/FP-Cart-Recovery
 * Description:       Cart recovery per WooCommerce: traccia carrelli abbandonati, invia email di richiamo e link per ripristinare il carrello.
 * Version:           1.2.6
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Francesco Passeri
 * Author URI:        https://francescopasseri.com
 * License:           Proprietary
 * Text Domain:       fp-cartrecovery
 * Domain Path:       /languages
 * GitHub Plugin URI: franpass87/FP-Cart-Recovery
 * Primary Branch:    main
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('FP_CARTRECOVERY_VERSION', '1.2.6');
define('FP_CARTRECOVERY_FILE', __FILE__);
define('FP_CARTRECOVERY_DIR', plugin_dir_path(__FILE__));
define('FP_CARTRECOVERY_URL', plugin_dir_url(__FILE__));

if (!file_exists(FP_CARTRECOVERY_DIR . 'vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p><strong>FP Cart Recovery:</strong> ';
        echo esc_html__('Esegui `composer install` nella cartella del plugin oppure carica la cartella vendor.', 'fp-cartrecovery');
        echo '</p></div>';
    });
    return;
}
require_once FP_CARTRECOVERY_DIR . 'vendor/autoload.php';

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('fp-cartrecovery', false, dirname(plugin_basename(__FILE__)) . '/languages');
    \FP\CartRecovery\Core\Plugin::instance()->init();
});

register_activation_hook(__FILE__, static function (): void {
    if (class_exists('FP\CartRecovery\Core\Migrations')) {
        \FP\CartRecovery\Core\Migrations::run();
    }
    if (!wp_next_scheduled(\FP\CartRecovery\Integrations\EmailScheduler::CRON_HOOK)) {
        wp_schedule_event(time() + 60, 'hourly', \FP\CartRecovery\Integrations\EmailScheduler::CRON_HOOK);
    }
    if (!wp_next_scheduled(\FP\CartRecovery\Integrations\CleanupCron::CRON_HOOK)) {
        wp_schedule_event(time() + 120, 'daily', \FP\CartRecovery\Integrations\CleanupCron::CRON_HOOK);
    }
});

register_deactivation_hook(__FILE__, static function (): void {
    wp_clear_scheduled_hook('fp_cartrecovery_send_reminders');
    wp_clear_scheduled_hook('fp_cartrecovery_cleanup_old_carts');
});
