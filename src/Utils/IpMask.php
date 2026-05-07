<?php

declare(strict_types=1);

namespace FP\CartRecovery\Utils;

/**
 * Anonimizza l'indirizzo IP per etichette in admin (GDPR-friendly).
 */
final class IpMask {

    /**
     * Restituisce un IP mascherato (es. ultimo ottetto su IPv4) o stringa vuota.
     */
    public static function from_request(): string {
        if (empty($_SERVER['REMOTE_ADDR']) || !is_string($_SERVER['REMOTE_ADDR'])) {
            return '';
        }
        $raw = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));

        return self::anonymize($raw);
    }

    /**
     * @param string $ip Indirizzo IP (non validato in ingresso oltre a trim).
     */
    public static function anonymize(string $ip): string {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }

        if (function_exists('wp_privacy_anonymize_ip')) {
            return (string) wp_privacy_anonymize_ip($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.•••.' . $parts[3];
            }
        }

        return '••••';
    }
}
