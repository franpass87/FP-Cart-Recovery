<?php

declare(strict_types=1);

namespace FP\CartRecovery\Integrations;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Domain\Settings;

/**
 * Cron per pulizia automatica carrelli abbandonati vecchi.
 */
final class CleanupCron {

    public const CRON_HOOK = 'fp_cartrecovery_cleanup_old_carts';

    public function __construct(
        private readonly Settings $settings,
        private readonly AbandonedCartRepository $repository = new AbandonedCartRepository()
    ) {}

    public function register(): void {
        add_action('admin_init', [$this, 'ensure_scheduled']);
        add_action(self::CRON_HOOK, [$this, 'run_cleanup']);
    }

    public function ensure_scheduled(): void {
        if (wp_next_scheduled(self::CRON_HOOK)) {
            return;
        }
        wp_schedule_event(time() + 120, 'daily', self::CRON_HOOK);
    }

    /**
     * Elimina carrelli abbandonati più vecchi di X giorni.
     */
    public function run_cleanup(): int {
        $days = (int) $this->settings->get('cleanup_after_days', 90);
        if ($days < 1) {
            return 0;
        }
        return $this->repository->delete_older_than_days($days);
    }
}
