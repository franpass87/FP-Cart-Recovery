<?php

declare(strict_types=1);

namespace FP\CartRecovery\Integrations;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Domain\Settings;

/**
 * Traccia i carrelli WooCommerce e li salva come abbandonati.
 */
final class CartTracker {

    public function __construct(
        private readonly Settings $settings,
        private readonly AbandonedCartRepository $repository = new AbandonedCartRepository()
    ) {}

    public function register(): void {
        if (!$this->settings->get('enabled', false)) {
            return;
        }

        add_action('woocommerce_add_to_cart', [$this, 'on_cart_updated'], 20);
        add_action('woocommerce_cart_item_removed', [$this, 'on_cart_updated'], 20);
        add_action('woocommerce_cart_item_restored', [$this, 'on_cart_updated'], 20);
        add_action('woocommerce_cart_item_set_quantity', [$this, 'on_cart_updated'], 20);
        add_action('shutdown', [$this, 'maybe_save_cart_on_shutdown'], 5);

        add_action('woocommerce_checkout_process', [$this, 'capture_guest_email'], 5);
        add_action('woocommerce_checkout_update_order_review', [$this, 'capture_guest_email_from_post'], 10);
        add_action('woocommerce_order_status_completed', [$this, 'mark_cart_recovered_on_order'], 10, 2);
    }

    /**
     * Segna il carrello come modificato per salvataggio su shutdown.
     */
    public function on_cart_updated(): void {
        if (!function_exists('WC') || !WC()->cart) {
            return;
        }
        set_transient('fp_cartrecovery_pending_save', 1, 120);
    }

    /**
     * Salva il carrello su shutdown (debounced).
     */
    public function maybe_save_cart_on_shutdown(): void {
        if (!get_transient('fp_cartrecovery_pending_save')) {
            return;
        }
        delete_transient('fp_cartrecovery_pending_save');

        if (!function_exists('WC') || !WC()->cart || !WC()->session) {
            return;
        }

        $cart = WC()->cart;
        if ($cart->is_empty()) {
            return;
        }

        $session_key = WC()->session->get_customer_id();
        $user_id = get_current_user_id();
        $track_guests = $this->settings->get('track_guests', true);

        if ($user_id === 0 && !$track_guests) {
            return;
        }

        $email = '';
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            $email = $user ? $user->user_email : '';
        }

        $cart_for_session = $this->get_cart_for_storage();
        if (empty($cart_for_session)) {
            return;
        }

        $cart_total = (float) $cart->get_total('edit');
        $currency = get_woocommerce_currency();

        $this->repository->upsert([
            'session_key'   => $session_key,
            'user_id'       => $user_id,
            'email'         => $email,
            'cart_content'  => wp_json_encode($cart_for_session),
            'cart_total'    => $cart_total,
            'currency'      => $currency,
            'status'        => 'abandoned',
        ]);

        do_action('fp_cartrecovery_cart_abandoned', $session_key, $user_id, $cart_total);

        if (defined('FP_TRACKING_VERSION') && !$this->cart_has_fp_experiences($cart_for_session)) {
            $items = $this->build_ga4_items_from_cart();
            do_action('fp_tracking_event', 'cart_abandoned', [
                'value'    => $cart_total,
                'currency' => $currency,
                'items'    => $items,
                'event_id' => 'fp_cartrecovery_' . $session_key . '_' . time(),
            ]);
        }
    }

    /**
     * Verifica se il carrello contiene prodotti FP Experiences (non emettere tracking).
     *
     * @param array<string, array<string, mixed>> $cart_items
     */
    private function cart_has_fp_experiences(array $cart_items): bool {
        if (!defined('FP_EXP_VERSION')) {
            return false;
        }
        $exp_product_id = (int) get_option('fp_exp_wc_product_id', 0);
        $gift_product_id = (int) get_option('fp_exp_gift_product_id', 0);

        foreach ($cart_items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $product_id = absint($item['product_id'] ?? 0);
            $variation_id = absint($item['variation_id'] ?? 0);
            $check_id = $variation_id > 0 ? $variation_id : $product_id;

            if ($check_id > 0 && ($check_id === $exp_product_id || $check_id === $gift_product_id)) {
                return true;
            }
            if (!empty($item['fp_exp_tickets']) || !empty($item['fp_exp_is_gift']) || !empty($item['gift_voucher']) || !empty($item['_fp_experience_id'])) {
                return true;
            }
            if ($product_id > 0 && 'yes' === get_post_meta($product_id, '_fp_exp_is_gift_product', true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Costruisce array items in formato GA4 per fp_tracking_event.
     *
     * @return array<int, array<string, mixed>>
     */
    private function build_ga4_items_from_cart(): array {
        if (!function_exists('WC') || !WC()->cart) {
            return [];
        }
        $items = [];
        foreach (WC()->cart->get_cart() as $item) {
            $product = $item['data'] ?? null;
            if (!$product instanceof \WC_Product) {
                continue;
            }
            $product_id = $product->get_id();
            $categories = get_the_terms($product_id, 'product_cat');
            $primary_cat = '';
            if (is_array($categories) && !empty($categories)) {
                $primary = reset($categories);
                $primary_cat = $primary instanceof \WP_Term ? $primary->name : '';
            }
            $items[] = [
                'item_id'       => (string) $product_id,
                'item_name'     => $product->get_name(),
                'price'         => (float) $product->get_price(),
                'quantity'      => (int) ($item['quantity'] ?? 1),
                'item_category' => $primary_cat,
                'item_brand'    => '',
            ];
        }
        return $items;
    }

    /**
     * Marca il carrello abbandonato come recovered quando l'ordine è completato.
     */
    public function mark_cart_recovered_on_order(int $order_id, ?\WC_Order $order = null): void {
        $order = $order ?? wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        $email = $order->get_billing_email();

        $repository = new AbandonedCartRepository();
        if ($user_id > 0) {
            $existing = $repository->find_by_session_or_user('', $user_id);
        } else {
            $existing = $email !== '' ? $repository->find_abandoned_by_email($email) : null;
        }
        if ($existing) {
            $repository->mark_recovered((int) $existing['id']);
        }
    }

    /**
     * Cattura email guest al checkout (submit o update order review).
     */
    public function capture_guest_email(): void {
        $this->do_capture_guest_email();
    }

    /**
     * Cattura email da AJAX update_order_review (guest inizia a compilare il checkout).
     */
    public function capture_guest_email_from_post(): void {
        $this->do_capture_guest_email();
    }

    private function do_capture_guest_email(): void {
        if (get_current_user_id() > 0) {
            return;
        }

        $email = isset($_POST['billing_email']) ? sanitize_email(wp_unslash($_POST['billing_email'])) : '';
        if ($email === '') {
            return;
        }

        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        $session_key = WC()->session->get_customer_id();
        if ($session_key === '') {
            return;
        }

        $this->repository->capture_email_for_session($session_key, $email);
    }

    /**
     * Ottiene il carrello nel formato WC session (senza oggetto product).
     *
     * @return array<string, array<string, mixed>>
     */
    private function get_cart_for_storage(): array {
        if (!function_exists('WC') || !WC()->cart) {
            return [];
        }

        $cart_data = [];
        foreach (WC()->cart->get_cart() as $key => $item) {
            $cart_data[$key] = $item;
            unset($cart_data[$key]['data']);
        }

        return $cart_data;
    }
}
