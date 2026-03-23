<?php

declare(strict_types=1);

namespace FP\CartRecovery\Admin;

use FP\CartRecovery\Domain\AbandonedCartRepository;
use FP\CartRecovery\Domain\Settings;
use FP\CartRecovery\Integrations\RecoveryHandler;

/**
 * Pagina Dashboard: elenco carrelli abbandonati e statistiche.
 */
final class DashboardPage {

    public function __construct(
        private readonly Settings $settings
    ) {}

    public function render(): void {
        $repository = new AbandonedCartRepository();
        $stats = $repository->get_stats();

        $page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $result = $repository->get_paginated($page, 20, $status_filter);
        $items = $result['items'];
        $total = $result['total'];

        $settings_url = admin_url('admin.php?page=' . AdminMenu::SETTINGS_SLUG);
        ?>
        <div class="wrap fpcartrecovery-admin-page">
            <h1 class="screen-reader-text"><?php echo esc_html__('FP Cart Recovery', 'fp-cartrecovery'); ?></h1>

            <div class="fpcartrecovery-page-header">
                <div class="fpcartrecovery-page-header-content">
                    <h2 class="fpcartrecovery-page-header-title" aria-hidden="true">
                        <span class="dashicons dashicons-cart"></span>
                        <?php echo esc_html__('FP Cart Recovery', 'fp-cartrecovery'); ?>
                    </h2>
                    <p><?php echo esc_html__('Carrelli abbandonati e recuperati.', 'fp-cartrecovery'); ?></p>
                </div>
                <span class="fpcartrecovery-page-header-badge">v<?php echo esc_html(FP_CARTRECOVERY_VERSION); ?></span>
            </div>

            <div class="fpcartrecovery-status-bar">
                <span class="fpcartrecovery-status-pill <?php echo $this->settings->get('enabled') ? 'is-active' : 'is-missing'; ?>">
                    <span class="dot"></span>
                    <?php echo $this->settings->get('enabled') ? esc_html__('Recupero attivo', 'fp-cartrecovery') : esc_html__('Attiva nelle impostazioni', 'fp-cartrecovery'); ?>
                </span>
                <a href="<?php echo esc_url($settings_url); ?>" class="fpcartrecovery-status-pill">
                    <?php echo esc_html__('Impostazioni', 'fp-cartrecovery'); ?>
                </a>
            </div>

            <div class="fpcartrecovery-card">
                <div class="fpcartrecovery-card-header">
                    <div class="fpcartrecovery-card-header-left">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h2><?php echo esc_html__('Statistiche', 'fp-cartrecovery'); ?></h2>
                    </div>
                </div>
                <div class="fpcartrecovery-card-body">
                    <div class="fpcartrecovery-stats-grid">
                        <div class="fpcartrecovery-stat">
                            <span class="fpcartrecovery-stat-value"><?php echo esc_html((string) $stats['abandoned']); ?></span>
                            <span class="fpcartrecovery-stat-label"><?php echo esc_html__('Carrelli abbandonati', 'fp-cartrecovery'); ?></span>
                        </div>
                        <div class="fpcartrecovery-stat">
                            <span class="fpcartrecovery-stat-value"><?php echo esc_html((string) $stats['recovered']); ?></span>
                            <span class="fpcartrecovery-stat-label"><?php echo esc_html__('Recuperati', 'fp-cartrecovery'); ?></span>
                        </div>
                        <div class="fpcartrecovery-stat">
                            <span class="fpcartrecovery-stat-value"><?php echo wc_price($stats['recovered_value']); ?></span>
                            <span class="fpcartrecovery-stat-label"><?php echo esc_html__('Valore recuperato', 'fp-cartrecovery'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fpcartrecovery-card">
                <div class="fpcartrecovery-card-header">
                    <div class="fpcartrecovery-card-header-left">
                        <span class="dashicons dashicons-list-view"></span>
                        <h2><?php echo esc_html__('Carrelli', 'fp-cartrecovery'); ?></h2>
                    </div>
                    <div class="fpcartrecovery-filter-links">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fp_cartrecovery_dashboard')); ?>" class="<?php echo $status_filter === '' ? 'current' : ''; ?>">
                            <?php echo esc_html__('Tutti', 'fp-cartrecovery'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fp_cartrecovery_dashboard&status=abandoned')); ?>" class="<?php echo $status_filter === 'abandoned' ? 'current' : ''; ?>">
                            <?php echo esc_html__('Abbandonati', 'fp-cartrecovery'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fp_cartrecovery_dashboard&status=recovered')); ?>" class="<?php echo $status_filter === 'recovered' ? 'current' : ''; ?>">
                            <?php echo esc_html__('Recuperati', 'fp-cartrecovery'); ?>
                        </a>
                    </div>
                </div>
                <div class="fpcartrecovery-card-body">
                    <?php if (empty($items)) : ?>
                        <p class="description"><?php echo esc_html__('Nessun carrello trovato.', 'fp-cartrecovery'); ?></p>
                    <?php else : ?>
                        <table class="fpcartrecovery-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Email / Utente', 'fp-cartrecovery'); ?></th>
                                    <th><?php echo esc_html__('Totale', 'fp-cartrecovery'); ?></th>
                                    <th><?php echo esc_html__('Data', 'fp-cartrecovery'); ?></th>
                                    <th><?php echo esc_html__('Stato', 'fp-cartrecovery'); ?></th>
                                    <th><?php echo esc_html__('Azioni', 'fp-cartrecovery'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $row) : ?>
                                    <tr class="status-<?php echo esc_attr($row['status'] ?? 'abandoned'); ?>">
                                        <td>
                                            <?php
                                            if (!empty($row['email'])) {
                                                echo esc_html($row['email']);
                                            } elseif (!empty($row['user_id'])) {
                                                $user = get_userdata((int) $row['user_id']);
                                                echo $user ? esc_html($user->display_name . ' (#' . $row['user_id'] . ')') : '—';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo wc_price((float) ($row['cart_total'] ?? 0), ['currency' => $row['currency'] ?? 'EUR']); ?></td>
                                        <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($row['updated_at'] ?? 'now'))); ?></td>
                                        <td>
                                            <span class="fpcartrecovery-badge fpcartrecovery-badge-<?php echo $row['status'] === 'recovered' ? 'success' : 'warning'; ?>">
                                                <?php echo $row['status'] === 'recovered' ? esc_html__('Recuperato', 'fp-cartrecovery') : esc_html__('Abbandonato', 'fp-cartrecovery'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (($row['status'] ?? '') === 'abandoned' && !empty($row['recovery_token'])) : ?>
                                                <?php $recovery_url = RecoveryHandler::get_recovery_url($row['recovery_token']); ?>
                                                <button type="button" class="fpcartrecovery-btn fpcartrecovery-btn-secondary fpcartrecovery-copy-link" data-url="<?php echo esc_attr($recovery_url); ?>">
                                                    <?php echo esc_html__('Copia link', 'fp-cartrecovery'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php
                        if ($total > 20) {
                            $pagination = paginate_links([
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => ceil($total / 20),
                                'current'   => $page,
                            ]);
                            if ($pagination) {
                                echo '<div class="fpcartrecovery-pagination">' . $pagination . '</div>';
                            }
                        }
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
