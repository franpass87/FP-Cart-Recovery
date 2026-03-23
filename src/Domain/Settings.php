<?php

declare(strict_types=1);

namespace FP\CartRecovery\Domain;

/**
 * Gestione impostazioni FP Cart Recovery.
 */
final class Settings {

    private const OPTION_KEY = 'fp_cartrecovery_settings';
    private const DEFAULTS = [
        'enabled'               => false,
        'abandon_after_minutes' => 30,
        'first_reminder_hours'  => 2,
        'second_reminder_hours' => 24,
        'track_guests'          => true,
        'email_provider'        => 'wp',
        'email_subject'         => '',
        'email_body'            => '',
        'from_name'             => '',
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
