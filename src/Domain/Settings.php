<?php

declare(strict_types=1);

namespace FP\CartRecovery\Domain;

/**
 * Gestione impostazioni FP Cart Recovery.
 */
final class Settings {

    private const OPTION_KEY = 'fp_cartrecovery_settings';
    private const DEFAULTS = [
        'enabled'                  => false,
        'abandon_after_minutes'    => 30,
        'first_reminder_hours'     => 2,
        'second_reminder_hours'    => 24,
        'third_reminder_hours'     => 72,
        'third_reminder_enabled'   => false,
        'track_guests'             => true,
        'min_cart_value'           => 0.0,
        'cleanup_after_days'       => 90,
        'cron_interval'            => 'hourly',
        'exclude_product_ids'      => [],
        'exclude_category_ids'     => [],
        'email_provider'           => 'wp',
        'email_subject'            => '',
        'email_body'               => '',
        'email_subject_2'          => '',
        'email_body_2'             => '',
        'email_subject_3'          => '',
        'email_body_3'             => '',
        'from_name'                => '',
        'from_email'               => '',
        'reply_to_email'           => '',
        'email_logo_url'           => '',
        'email_primary_color'      => '#667eea',
        'email_accent_color'       => '#764ba2',
        'recovery_link_expiry_days'=> 0,
        'unsubscribe_page_id'      => 0,
    ];

    private array $data;

    public function __construct() {
        $saved = get_option(self::OPTION_KEY, []);
        $saved = is_array($saved) ? $saved : [];
        $this->data = array_merge(self::DEFAULTS, $saved);
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): void {
        $allowed = array_keys(self::DEFAULTS);
        $data = array_intersect_key($data, array_flip($allowed));
        $this->data = array_merge($this->data, $data);
        update_option(self::OPTION_KEY, $this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array {
        return $this->data;
    }
}
