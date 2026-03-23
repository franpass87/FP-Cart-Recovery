<?php

declare(strict_types=1);

namespace FP\CartRecovery\Core;

/**
 * Migrazioni database per FP Cart Recovery.
 *
 * Crea e aggiorna la tabella dei carrelli abbandonati.
 */
final class Migrations {

    private const DB_VERSION = '2025-03-23';
    private const OPTION_KEY = 'fp_cartrecovery_db_version';

    public static function run(): void {
        if (get_option(self::OPTION_KEY) === self::DB_VERSION) {
            return;
        }

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $wpdb->prefix . 'fp_cartrecovery_carts';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_key VARCHAR(100) NOT NULL DEFAULT '',
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            email VARCHAR(255) NOT NULL DEFAULT '',
            cart_content LONGTEXT NOT NULL,
            cart_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
            recovery_token VARCHAR(64) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'abandoned',
            reminder_sent TINYINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_key (session_key),
            KEY user_id (user_id),
            KEY recovery_token (recovery_token),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset};";

        dbDelta($sql);
        update_option(self::OPTION_KEY, self::DB_VERSION);
    }
}
