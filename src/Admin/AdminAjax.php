<?php

declare(strict_types=1);

namespace FP\CartRecovery\Admin;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Domain\Settings;
use FP\CartRecovery\Integrations\EmailScheduler;

/**
 * Handler AJAX admin: elimina carrello, invia email di prova.
 */
final class AdminAjax {

    public function __construct(
        private readonly Settings $settings
    ) {}

    public function register(): void {
        add_action('wp_ajax_fp_cartrecovery_delete_cart', [$this, 'delete_cart']);
        add_action('wp_ajax_fp_cartrecovery_send_test_email', [$this, 'send_test_email']);
        add_action('wp_ajax_fp_cartrecovery_preview_email', [$this, 'preview_email']);
    }

    public function delete_cart(): void {
        check_ajax_referer('fp_cartrecovery_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'fp-cartrecovery')]);
        }

        $id = absint($_POST['id'] ?? 0);
        if ($id === 0) {
            wp_send_json_error(['message' => __('ID non valido.', 'fp-cartrecovery')]);
        }

        $repository = new AbandonedCartRepository();
        if ($repository->delete($id)) {
            wp_send_json_success();
        }
        wp_send_json_error(['message' => __('Eliminazione fallita.', 'fp-cartrecovery')]);
    }

    public function send_test_email(): void {
        check_ajax_referer('fp_cartrecovery_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'fp-cartrecovery')]);
        }

        $user = wp_get_current_user();
        if (!$user->exists() || empty($user->user_email)) {
            wp_send_json_error(['message' => __('Email non disponibile per l\'utente corrente.', 'fp-cartrecovery')]);
        }

        $scheduler = new EmailScheduler($this->settings);
        $cart = $this->build_fake_cart_for_test($user);
        $subject = $this->settings->get('email_subject') ?: __('Hai dimenticato qualcosa nel carrello', 'fp-cartrecovery');
        $body = $this->settings->get('email_body') ?: $this->get_default_body_for_test();

        $success = $scheduler->send_test_email($user->user_email, $subject, $body, $cart);
        if ($success) {
            wp_send_json_success(['message' => __('Email di prova inviata a ', 'fp-cartrecovery') . $user->user_email]);
        }
        wp_send_json_error(['message' => __('Invio fallito. Controlla la configurazione SMTP.', 'fp-cartrecovery')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function build_fake_cart_for_test(\WP_User $user): array {
        return [
            'id'             => 0,
            'recovery_token' => 'test_' . bin2hex(random_bytes(8)),
            'cart_total'     => 99.90,
            'currency'       => get_woocommerce_currency(),
            'email'          => $user->user_email,
            'user_id'        => $user->ID,
            'cart_content'   => '[]',
        ];
    }

    private function get_default_body_for_test(): string {
        ob_start();
        include FP_CARTRECOVERY_DIR . 'templates/emails/cart-recovery.php';
        return ob_get_clean() ?: '';
    }

    public function preview_email(): void {
        check_ajax_referer('fp_cartrecovery_admin', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'fp-cartrecovery')]);
        }

        $user = wp_get_current_user();
        $cart = $this->build_fake_cart_for_test($user);
        $scheduler = new EmailScheduler($this->settings);
        $html = $scheduler->get_preview_html($cart);

        wp_send_json_success(['html' => $html]);
    }
}
