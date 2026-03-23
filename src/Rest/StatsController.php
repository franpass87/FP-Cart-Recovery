<?php

declare(strict_types=1);

namespace FP\CartRecovery\Rest;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API per statistiche FP Cart Recovery.
 *
 * Endpoint: GET /fp-cart-recovery/v1/stats
 * Parametri: days (0|7|30|90)
 */
final class StatsController {

    public function __construct(
        private readonly AbandonedCartRepository $repository = new AbandonedCartRepository()
    ) {}

    public function register(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('fp-cart-recovery/v1', '/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_stats'],
            'permission_callback' => fn () => current_user_can('manage_options'),
            'args'                => [
                'days' => [
                    'default'           => 0,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn ($v) => in_array((int) $v, [0, 7, 30, 90], true),
                ],
            ],
        ]);
    }

    public function get_stats(WP_REST_Request $request): WP_REST_Response {
        $days = (int) $request->get_param('days');
        $stats = $this->repository->get_stats($days);

        $total = $stats['abandoned'] + $stats['recovered'];
        $conversion_rate = $total > 0 ? round(100 * $stats['recovered'] / $total, 1) : 0.0;

        return new WP_REST_Response([
            'abandoned'       => $stats['abandoned'],
            'recovered'       => $stats['recovered'],
            'recovered_value' => (float) $stats['recovered_value'],
            'conversion_rate' => $conversion_rate,
            'days'            => $days,
        ], 200);
    }
}
