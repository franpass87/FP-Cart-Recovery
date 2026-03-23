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
        $cart_total = wc_price((float) ($cart['cart_total'] ?? 0), ['currency' => $cart['currency'] ?? 'EUR']);
        $shop_name = get_bloginfo('name');

        $body = str_replace(
            ['{{recovery_link}}', '{{cart_total}}', '{{shop_name}}'],
            [$recovery_url, $cart_total, $shop_name],
            $body_template
        );

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $from_name = $this->settings->get('from_name') ?: $shop_name;
        $from_email = get_option('admin_email');
        $headers[] = sprintf('From: %s <%s>', $from_name, $from_email);

        $subject = apply_filters('fp_cartrecovery_email_subject', $subject, $cart);
        $body = apply_filters('fp_cartrecovery_email_body', $body, $cart);

        return wp_mail($email, $subject, $body, $headers);
    }

    private function get_default_body(): string {
        ob_start();
        include FP_CARTRECOVERY_DIR . 'templates/emails/cart-recovery.php';
        return ob_get_clean() ?: '';
    }
}
