<?php

declare(strict_types=1);

/**
 * Pulizia alla disinstallazione di FP Cart Recovery.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

$table = $wpdb->prefix . 'fp_cartrecovery_carts';
$wpdb->query("DROP TABLE IF EXISTS {$table}");

delete_option('fp_cartrecovery_settings');
delete_option('fp_cartrecovery_db_version');
wp_clear_scheduled_hook('fp_cartrecovery_send_reminders');
