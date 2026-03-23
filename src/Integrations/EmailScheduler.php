<?php

declare(strict_types=1);

namespace FP\CartRecovery\Integrations;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Domain\Settings;

/**
 * Pianifica e invia le email di reminder per carrelli abbandonati.
 */
final class EmailScheduler {

    public const CRON_HOOK = 'fp_cartrecovery_send_reminders';

    public function __construct(
        private readonly Settings $settings,
        private readonly AbandonedCartRepository $repository = new AbandonedCartRepository()
    ) {}

    public function register(): void {
        add_action('init', [$this, 'ensure_scheduled']);
        add_action(self::CRON_HOOK, [$this, 'send_reminders']);
    }

    public function ensure_scheduled(): void {
        if (wp_next_scheduled(self::CRON_HOOK)) {
            return;
        }
        wp_schedule_event(time() + 60, 'hourly', self::CRON_HOOK);
    }

    public function send_reminders(): void {
        $first_hours = max(1, (int) $this->settings->get('first_reminder_hours', 2));
        $second_hours = max(1, (int) $this->settings->get('second_reminder_hours', 24));

        $subject = $this->settings->get('email_subject') ?: __('Hai dimenticato qualcosa nel carrello', 'fp-cartrecovery');
        $body_template = $this->settings->get('email_body') ?: $this->get_default_body();

        $sent = 0;

        $carts_first = $this->repository->find_abandoned_for_reminder($first_hours, 1);
        foreach ($carts_first as $cart) {
            if ($this->send_email($cart, $subject, $body_template)) {
                $this->repository->increment_reminder_sent((int) $cart['id']);
                $sent++;
            }
        }

        $carts_second = $this->repository->find_abandoned_for_reminder($second_hours, 2);
        foreach ($carts_second as $cart) {
            if ($this->send_email($cart, $subject, $body_template)) {
                $this->repository->increment_reminder_sent((int) $cart['id']);
                $sent++;
            }
        }

        if ($sent > 0 && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FP-Cart-Recovery] Sent ' . $sent . ' reminder emails');
        }
    }

    private function send_email(array $cart, string $subject, string $body_template): bool {
        $email = $cart['email'] ?? '';
        if ($email === '') {
            return false;
        }

        $recovery_url = RecoveryHandler::get_recovery_url($cart['recovery_token'] ?? '');
        $cart_total_formatted = wc_price((float) ($cart['cart_total'] ?? 0), ['currency' => $cart['currency'] ?? 'EUR']);
        $shop_name = get_bloginfo('name');

        $body = str_replace(
            ['{{recovery_link}}', '{{cart_total}}', '{{shop_name}}'],
            [$recovery_url, $cart_total_formatted, $shop_name],
            $body_template
        );

        $from_name = $this->settings->get('from_name') ?: $shop_name;
        $from_email = get_option('admin_email');

        $subject = apply_filters('fp_cartrecovery_email_subject', $subject, $cart);
        $body = apply_filters('fp_cartrecovery_email_body', $body, $cart);

        $provider = $this->settings->get('email_provider', 'wp');
        $success = $provider === 'brevo'
            ? $this->send_via_brevo($email, $subject, $body, $from_name, $from_email, $cart)
            : $this->send_via_wp($email, $subject, $body, $from_name, $from_email);

        if ($success && $provider === 'brevo' && defined('FP_TRACKING_VERSION')) {
            do_action('fp_tracking_event', 'cart_recovery_email_sent', [
                'value'    => (float) ($cart['cart_total'] ?? 0),
                'currency' => $cart['currency'] ?? 'EUR',
                'email'    => $email,
                'cart_id'  => (int) ($cart['id'] ?? 0),
            ]);
        }

        return $success;
    }

    private function send_via_wp(string $to, string $subject, string $body, string $from_name, string $from_email): bool {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email),
        ];
        return wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Invia email via API Brevo (usa impostazioni centralizzate da FP Tracking).
     */
    private function send_via_brevo(string $to, string $subject, string $body, string $from_name, string $from_email, array $cart): bool {
        if (!function_exists('fp_tracking_get_brevo_settings')) {
            return $this->send_via_wp($to, $subject, $body, $from_name, $from_email);
        }

        $brevo = fp_tracking_get_brevo_settings();
        if (empty($brevo['api_key']) || !$brevo['enabled']) {
            return $this->send_via_wp($to, $subject, $body, $from_name, $from_email);
        }

        $payload = [
            'sender'      => [
                'name'  => $from_name,
                'email' => $from_email,
            ],
            'to'          => [['email' => $to]],
            'subject'     => str_replace(["\r", "\n"], '', $subject),
            'htmlContent' => $body,
        ];

        $response = wp_remote_post(
            'https://api.brevo.com/v3/smtp/email',
            [
                'headers' => [
                    'accept'       => 'application/json',
                    'api-key'      => $brevo['api_key'],
                    'content-type' => 'application/json',
                ],
                'body'    => wp_json_encode($payload),
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FP-Cart-Recovery] Brevo API error: ' . $response->get_error_message());
            }
            return $this->send_via_wp($to, $subject, $body, $from_name, $from_email);
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 201) {
            $body_res = wp_remote_retrieve_body($response);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FP-Cart-Recovery] Brevo API HTTP ' . $code . ': ' . $body_res);
            }
            return false;
        }

        return true;
    }

    private function get_default_body(): string {
        ob_start();
        include FP_CARTRECOVERY_DIR . 'templates/emails/cart-recovery.php';
        return ob_get_clean() ?: '';
    }
}
