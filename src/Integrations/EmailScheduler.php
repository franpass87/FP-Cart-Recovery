<?php

declare(strict_types=1);

namespace FP\CartRecovery\Integrations;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Domain\Settings;
use FP\CartRecovery\Utils\ColorHelper;

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
        add_action('admin_init', [$this, 'ensure_scheduled']);
        add_action(self::CRON_HOOK, [$this, 'send_reminders']);
    }

    public function ensure_scheduled(): void {
        if (wp_next_scheduled(self::CRON_HOOK)) {
            return;
        }
        wp_schedule_event(time() + 60, 'hourly', self::CRON_HOOK);
    }

    public function send_reminders(): void {
        $abandon_min = max(0, (int) $this->settings->get('abandon_after_minutes', 30));
        $abandon_hours = $abandon_min / 60.0;

        $first_hours = max(1.0, max((float) $this->settings->get('first_reminder_hours', 2), $abandon_hours));
        $second_hours = max(1.0, max((float) $this->settings->get('second_reminder_hours', 24), $abandon_hours));

        $subject = $this->settings->get('email_subject') ?: __('Hai dimenticato qualcosa nel carrello', 'fp-cartrecovery');
        $body_template = $this->settings->get('email_body') ?: $this->get_default_body();

        $sent = 0;

        $subject_2 = $this->settings->get('email_subject_2') ?: $subject;
        $body_template_2 = $this->settings->get('email_body_2') ?: $body_template;

        $carts_first = $this->repository->find_abandoned_for_reminder((int) ceil($first_hours), 1);
        foreach ($carts_first as $cart) {
            if ($this->send_email($cart, $subject, $body_template, 1)) {
                $this->repository->increment_reminder_sent((int) $cart['id']);
                $sent++;
            }
        }

        $carts_second = $this->repository->find_abandoned_for_reminder((int) ceil($second_hours), 2);
        foreach ($carts_second as $cart) {
            if ($this->send_email($cart, $subject_2, $body_template_2, 2)) {
                $this->repository->increment_reminder_sent((int) $cart['id']);
                $sent++;
            }
        }

        if ($sent > 0 && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FP-Cart-Recovery] Sent ' . $sent . ' reminder emails');
        }
    }

    private function send_email(array $cart, string $subject, string $body_template, int $reminder_number = 1, bool $is_test = false): bool {
        $email = $cart['email'] ?? '';
        if ($email === '') {
            return false;
        }

        $recovery_url = RecoveryHandler::get_recovery_url($cart['recovery_token'] ?? '');
        $cart_total_formatted = wc_price((float) ($cart['cart_total'] ?? 0), ['currency' => $cart['currency'] ?? 'EUR']);
        $shop_name = get_bloginfo('name');
        $customer_name = $this->get_customer_name($cart);
        $cart_items_html = $this->build_cart_items_html($cart);

        $logo_url = esc_url_raw($this->settings->get('email_logo_url') ?: '');
        $logo_html = $logo_url !== ''
            ? '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($shop_name) . '" style="max-height:60px;width:auto;display:block;margin:0 auto 16px;" />'
            : '';
        $primary_color = ColorHelper::sanitize_hex($this->settings->get('email_primary_color') ?: '#667eea');
        $accent_color = ColorHelper::sanitize_hex($this->settings->get('email_accent_color') ?: '#764ba2');

        $placeholders = [
            '{{recovery_link}}',
            '{{cart_total}}',
            '{{shop_name}}',
            '{{customer_name}}',
            '{{cart_items}}',
            '{{reminder_number}}',
            '{{logo_html}}',
            '{{primary_color}}',
            '{{accent_color}}',
        ];
        $values = [
            $recovery_url,
            $cart_total_formatted,
            $shop_name,
            $customer_name,
            $cart_items_html,
            (string) $reminder_number,
            $logo_html,
            $primary_color,
            $accent_color,
        ];

        $body = str_replace($placeholders, $values, $body_template);
        $subject = str_replace($placeholders, $values, $subject);

        $from_name = $this->settings->get('from_name') ?: $shop_name;
        $from_email = $this->settings->get('from_email') ?: get_option('admin_email');

        $subject = apply_filters('fp_cartrecovery_email_subject', $subject, $cart);
        $body = apply_filters('fp_cartrecovery_email_body', $body, $cart);

        $provider = $this->settings->get('email_provider', 'wp');
        $success = $provider === 'brevo'
            ? $this->send_via_brevo($email, $subject, $body, $from_name, $from_email, $cart)
            : $this->send_via_wp($email, $subject, $body, $from_name, $from_email);

        if ($success && !$is_test && $provider === 'brevo' && defined('FP_TRACKING_VERSION')) {
            do_action('fp_tracking_event', 'cart_recovery_email_sent', [
                'value'    => (float) ($cart['cart_total'] ?? 0),
                'currency' => $cart['currency'] ?? 'EUR',
                'email'    => $email,
                'cart_id'  => (int) ($cart['id'] ?? 0),
            ]);
        }

        return $success;
    }

    /**
     * Invia email di prova (usa placeholder e provider configurato, senza evento tracking).
     */
    public function send_test_email(string $to, string $subject, string $body_template, array $cart): bool {
        $cart_with_email = array_merge($cart, ['email' => $to]);
        return $this->send_email($cart_with_email, $subject, $body_template, 1, true);
    }

    /**
     * Invia email via wp_mail con struttura completa.
     *
     * Headers: Content-Type HTML, From, Reply-To, charset UTF-8.
     * Filtro: fp_cartrecovery_wp_mail_headers
     */
    private function send_via_wp(string $to, string $subject, string $body, string $from_name, string $from_email): bool {
        $subject = str_replace(["\r", "\n"], '', $subject);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
        ];
        $headers = apply_filters('fp_cartrecovery_wp_mail_headers', $headers, $to, $subject);

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

    private function get_customer_name(array $cart): string {
        $user_id = (int) ($cart['user_id'] ?? 0);
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            if ($user) {
                $name = trim($user->first_name . ' ' . $user->last_name);
                if ($name !== '') {
                    return $name;
                }
                return $user->display_name ?: '';
            }
        }
        return __('Cliente', 'fp-cartrecovery');
    }

    /**
     * Costruisce HTML lista prodotti dal cart_content.
     */
    private function build_cart_items_html(array $cart): string {
        $content = $cart['cart_content'] ?? '';
        if ($content === '') {
            return '';
        }
        $items = json_decode($content, true);
        if (!is_array($items) || empty($items)) {
            return '';
        }

        $lines = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $product_id = absint($item['product_id'] ?? 0);
            $variation_id = absint($item['variation_id'] ?? 0);
            $quantity = max(1, absint($item['quantity'] ?? 1));
            $id = $variation_id > 0 ? $variation_id : $product_id;

            $product = $id > 0 ? wc_get_product($id) : null;
            $name = $product ? $product->get_name() : sprintf(__('Prodotto #%d', 'fp-cartrecovery'), $product_id);
            $lines[] = sprintf('<li>%s &times; %s</li>', esc_html((string) $quantity), esc_html($name));
        }

        return $lines === [] ? '' : '<ul style="margin:0 0 16px;padding-left:20px;">' . implode('', $lines) . '</ul>';
    }

    private function get_default_body(): string {
        ob_start();
        include FP_CARTRECOVERY_DIR . 'templates/emails/cart-recovery.php';
        return ob_get_clean() ?: '';
    }
}
