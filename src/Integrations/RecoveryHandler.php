<?php

declare(strict_types=1);

namespace FP\CartRecovery\Integrations;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Domain\Settings;

/**
 * Gestisce il ripristino del carrello tramite link con token.
 */
final class RecoveryHandler {

    private const QUERY_ARG = 'fp_cart_recovery';

    public function __construct(
        private readonly ?Settings $settings = null
    ) {}

    public function register(): void {
        add_action('template_redirect', [$this, 'handle_recovery'], 5);
    }

    /**
     * Intercetta la richiesta con token recovery e ripristina il carrello.
     */
    public function handle_recovery(): void {
        $token = isset($_GET[self::QUERY_ARG]) ? sanitize_text_field(wp_unslash($_GET[self::QUERY_ARG])) : '';
        if ($token === '') {
            return;
        }

        if (!function_exists('WC') || !WC()->session) {
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        $repository = new AbandonedCartRepository();
        $cart_record = $repository->find_by_token($token);
        if (!$cart_record) {
            wc_add_notice(__('Link di recupero non valido o già utilizzato.', 'fp-cartrecovery'), 'error');
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        $expiry_days = (int) ($this->settings?->get('recovery_link_expiry_days') ?? 0);
        if ($expiry_days > 0) {
            $updated = strtotime($cart_record['updated_at'] ?? 'now');
            if ($updated && (time() - $updated) > ($expiry_days * DAY_IN_SECONDS)) {
                wc_add_notice(__('Questo link di recupero è scaduto.', 'fp-cartrecovery'), 'error');
                wp_safe_redirect(wc_get_cart_url());
                exit;
            }
        }

        $cart_content = json_decode($cart_record['cart_content'] ?? '[]', true);
        if (!is_array($cart_content) || empty($cart_content)) {
            wc_add_notice(__('Carrello non recuperabile.', 'fp-cartrecovery'), 'error');
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        WC()->cart->empty_cart(false);

        $reserved_keys = ['product_id', 'variation_id', 'quantity', 'variation', 'data'];
        foreach ($cart_content as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            $product_id = absint($item['product_id'] ?? 0);
            $quantity = max(1, absint($item['quantity'] ?? 1));
            $variation_id = absint($item['variation_id'] ?? 0);
            $variation = [];

            if ($variation_id > 0 && !empty($item['variation']) && is_array($item['variation'])) {
                $variation = array_map('sanitize_text_field', $item['variation']);
            }

            $cart_item_data = [];
            foreach ($item as $k => $v) {
                if (in_array($k, $reserved_keys, true) || $v === null) {
                    continue;
                }
                $cart_item_data[$k] = $this->sanitize_cart_item_value($v);
            }

            $product = $variation_id > 0 ? wc_get_product($variation_id) : wc_get_product($product_id);
            if (!$product || !$product->exists()) {
                continue;
            }

            WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);
        }

        $repository->mark_recovered((int) $cart_record['id']);

        do_action('fp_cartrecovery_cart_recovered', (int) $cart_record['id'], $cart_record);

        if (defined('FP_TRACKING_VERSION')) {
            $cid = (int) ($cart_record['id'] ?? 0);
            do_action('fp_tracking_event', 'cart_recovery', [
                'value'     => (float) ($cart_record['cart_total'] ?? 0),
                'currency'  => $cart_record['currency'] ?? 'EUR',
                'cart_id'   => $cid,
                'event_id'  => 'fp_cartrecovery_recover_' . $cid . '_' . time(),
            ]);
        }

        wc_add_notice(__('Carrello ripristinato con successo!', 'fp-cartrecovery'), 'success');

        wp_safe_redirect(remove_query_arg(self::QUERY_ARG, wc_get_cart_url()));
        exit;
    }

    /**
     * Sanitizza un valore per cart_item_data (preserva int, float, array).
     *
     * @param mixed $v
     * @return mixed
     */
    private function sanitize_cart_item_value(mixed $v): mixed {
        if (is_array($v)) {
            return array_map([$this, 'sanitize_cart_item_value'], $v);
        }
        if (is_int($v) || is_float($v)) {
            return $v;
        }
        if (is_numeric($v)) {
            return strpos((string) $v, '.') !== false ? (float) $v : (int) $v;
        }
        return sanitize_text_field((string) $v);
    }

    /**
     * Genera l'URL di recovery per un token.
     */
    public static function get_recovery_url(string $token): string {
        return add_query_arg(self::QUERY_ARG, $token, wc_get_cart_url());
    }
}
