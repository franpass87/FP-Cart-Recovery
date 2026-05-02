<?php

declare(strict_types=1);

namespace FP\CartRecovery\Rest;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Integrations\RecoveryHandler;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API: carrelli abbandonati aggiornati di recente (polling admin, quasi live).
 *
 * Endpoint: GET /fp-cart-recovery/v1/active-carts
 */
final class ActiveCartsController {

    public function __construct(
        private readonly AbandonedCartRepository $repository = new AbandonedCartRepository()
    ) {}

    public function register(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('fp-cart-recovery/v1', '/active-carts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_active_carts'],
            'permission_callback' => static fn (): bool => current_user_can('manage_options'),
            'args'                => [
                'minutes' => [
                    'default'           => 15,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => static fn ($v): bool => (int) $v >= 1 && (int) $v <= 120,
                ],
                'limit'   => [
                    'default'           => 40,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => static fn ($v): bool => (int) $v >= 1 && (int) $v <= 100,
                ],
            ],
        ]);
    }

    public function get_active_carts(WP_REST_Request $request): WP_REST_Response {
        $minutes = (int) $request->get_param('minutes');
        $limit = (int) $request->get_param('limit');

        $rows = $this->repository->find_active_abandoned($minutes, $limit);
        $carts = [];

        foreach ($rows as $row) {
            $summary = $this->summarize_cart_content((string) ($row['cart_content'] ?? ''));
            $user_id = (int) ($row['user_id'] ?? 0);
            $user_label = '';
            if ($user_id > 0) {
                $user = get_userdata($user_id);
                if ($user instanceof \WP_User) {
                    $user_label = sprintf(
                        /* translators: 1: display name, 2: user ID */
                        __('%1$s (#%2$d)', 'fp-cartrecovery'),
                        $user->display_name,
                        $user_id
                    );
                }
            }

            $currency = (string) ($row['currency'] ?? 'EUR');
            $total = (float) ($row['cart_total'] ?? 0);
            $formatted = function_exists('wc_price')
                ? wp_strip_all_tags(wc_price($total, ['currency' => $currency]))
                : (string) $total;

            $token = (string) ($row['recovery_token'] ?? '');
            $carts[] = [
                'id'              => (int) ($row['id'] ?? 0),
                'email'           => (string) ($row['email'] ?? ''),
                'user_label'      => $user_label,
                'cart_total'      => $total,
                'currency'        => $currency,
                'formatted_total' => $formatted,
                'updated_at'      => (string) ($row['updated_at'] ?? ''),
                'updated_human'   => $this->format_updated_human((string) ($row['updated_at'] ?? '')),
                'reminder_sent'   => (int) ($row['reminder_sent'] ?? 0),
                'lines'           => $summary['lines'],
                'item_summary'    => $summary['summary'],
                'recovery_url'    => $token !== '' ? RecoveryHandler::get_recovery_url($token) : '',
            ];
        }

        return new WP_REST_Response([
            'carts'         => $carts,
            'refreshed_at'  => current_time('mysql'),
            'window_minutes'=> $minutes,
        ], 200);
    }

    /**
     * @return array{lines: int, summary: string}
     */
    private function summarize_cart_content(string $json): array {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['lines' => 0, 'summary' => ''];
        }

        $lines = count($data);
        $names = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            $variation_id = (int) ($item['variation_id'] ?? 0);
            $product_id = (int) ($item['product_id'] ?? 0);
            $title_id = $variation_id > 0 ? $variation_id : $product_id;
            if ($title_id > 0) {
                $title = get_the_title($title_id);
                if ($title !== '') {
                    $names[] = $title;
                }
            }
            if (count($names) >= 4) {
                break;
            }
        }

        $summary = implode(', ', $names);
        if ($lines > count($names)) {
            $summary .= ($summary !== '' ? ' ' : '') . sprintf(
                /* translators: %d: number of additional line items */
                __('(+%d)', 'fp-cartrecovery'),
                max(0, $lines - count($names))
            );
        }

        return ['lines' => $lines, 'summary' => trim($summary)];
    }

    private function format_updated_human(string $mysql): string {
        if ($mysql === '') {
            return '';
        }
        $ts = mysql2date('U', $mysql);
        if (!$ts) {
            return $mysql;
        }

        return sprintf(
            /* translators: %s: relative time phrase from human_time_diff(), e.g. "5 minutes". */
            __('%s ago', 'fp-cartrecovery'),
            human_time_diff((int) $ts, time())
        );
    }
}
