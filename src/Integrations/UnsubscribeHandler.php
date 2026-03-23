<?php

declare(strict_types=1);

namespace FP\CartRecovery\Integrations;

use FP\CartRecovery\Domain\Settings;

/**
 * Gestisce l'unsubscribe dalle email di recupero carrello.
 *
 * Link nelle email: {{unsubscribe_url}}
 * Verifica token HMAC per sicurezza.
 */
final class UnsubscribeHandler {

    private const OPTION_UNSUBSCRIBED = 'fp_cartrecovery_unsubscribed_emails';
    private const QUERY_ARG = 'fp_cart_unsubscribe';

    public function __construct(
        private readonly Settings $settings
    ) {}

    public function register(): void {
        add_action('template_redirect', [$this, 'handle_unsubscribe'], 1);
    }

    /**
     * Intercetta richiesta unsubscribe e aggiunge email alla lista.
     */
    public function handle_unsubscribe(): void {
        if (!isset($_GET[self::QUERY_ARG]) || $_GET[self::QUERY_ARG] !== '1') {
            return;
        }

        $email_b64 = isset($_GET['e']) ? sanitize_text_field(wp_unslash($_GET['e'])) : '';
        $token = isset($_GET['t']) ? sanitize_text_field(wp_unslash($_GET['t'])) : '';

        if ($email_b64 === '' || $token === '') {
            $this->redirect_with_notice(__('Link di disiscrizione non valido.', 'fp-cartrecovery'), 'error');
        }

        $email = base64_decode($email_b64, true);
        if ($email === false || !is_email($email)) {
            $this->redirect_with_notice(__('Link di disiscrizione non valido.', 'fp-cartrecovery'), 'error');
        }

        $expected = hash_hmac('sha256', $email, wp_salt('auth'));
        if (!hash_equals($expected, $token)) {
            $this->redirect_with_notice(__('Link di disiscrizione non valido o scaduto.', 'fp-cartrecovery'), 'error');
        }

        $this->add_unsubscribed($email);

        if (is_user_logged_in()) {
            wc_add_notice(__('Sei stato disiscritto dalle email di recupero carrello.', 'fp-cartrecovery'), 'success');
        }

        $page_id = (int) $this->settings->get('unsubscribe_page_id', 0);
        $redirect = $page_id > 0 ? get_permalink($page_id) : home_url('/');
        wp_safe_redirect(remove_query_arg([self::QUERY_ARG, 'e', 't'], $redirect ?: home_url('/')));
        exit;
    }

    /**
     * Genera URL unsubscribe per un'email.
     */
    public function get_unsubscribe_url(string $email): string {
        if (!is_email($email)) {
            return '';
        }
        $page_id = (int) $this->settings->get('unsubscribe_page_id', 0);
        $base = $page_id > 0 ? get_permalink($page_id) : home_url('/');
        if (!$base) {
            $base = home_url('/');
        }
        return add_query_arg([
            self::QUERY_ARG => '1',
            'e' => base64_encode($email),
            't' => hash_hmac('sha256', $email, wp_salt('auth')),
        ], $base);
    }

    /**
     * Verifica se un'email è disiscritta.
     */
    public function is_unsubscribed(string $email): bool {
        $list = get_option(self::OPTION_UNSUBSCRIBED, []);
        $list = is_array($list) ? $list : [];
        return in_array(strtolower($email), array_map('strtolower', $list), true);
    }

    /**
     * Aggiunge email alla lista disiscritti.
     */
    private function add_unsubscribed(string $email): void {
        $list = get_option(self::OPTION_UNSUBSCRIBED, []);
        $list = is_array($list) ? $list : [];
        $email_lower = strtolower($email);
        if (!in_array($email_lower, array_map('strtolower', $list), true)) {
            $list[] = $email;
            update_option(self::OPTION_UNSUBSCRIBED, $list);
        }
    }

    private function redirect_with_notice(string $message, string $type): void {
        if (function_exists('wc_add_notice')) {
            wc_add_notice($message, $type);
        }
        $url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
        wp_safe_redirect(remove_query_arg([self::QUERY_ARG, 'e', 't'], $url ?: home_url('/')));
        exit;
    }
}
