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

    private const PER_PAGE = 20;

    public function __construct(
        private readonly Settings $settings
    ) {}

    public function render(): void {
        if (isset($_GET['export']) && $_GET['export'] === 'csv' && current_user_can('manage_options')) {
            $this->export_csv();
            return;
        }

        $repository = new AbandonedCartRepository();
        $stats_period = isset($_GET['stats_days']) ? absint($_GET['stats_days']) : 0;
        $stats = $repository->get_stats($stats_period);

        $page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
        $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $list_period = isset($_GET['list_days']) ? absint($_GET['list_days']) : 0;
        $result = $repository->get_paginated($page, self::PER_PAGE, $status_filter, $list_period);
        $items = $result['items'];
        $total = $result['total'];

        $settings_url = admin_url('admin.php?page=' . AdminMenu::SETTINGS_SLUG);
        $live_url = admin_url('admin.php?page=' . AdminMenu::LIVE_SLUG);
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

            <?php
            $dash_tracking = (bool) $this->settings->get('enabled', false);
            $dash_emails = $dash_tracking && (bool) $this->settings->get('emails_enabled', false);
            ?>
            <div class="fpcartrecovery-status-bar">
                <span class="fpcartrecovery-status-pill <?php echo $dash_tracking ? 'is-active' : 'is-missing'; ?>">
                    <span class="dot"></span>
                    <?php echo $dash_tracking ? esc_html__('Tracciamento attivo', 'fp-cartrecovery') : esc_html__('Tracciamento disattivo', 'fp-cartrecovery'); ?>
                </span>
                <span class="fpcartrecovery-status-pill <?php echo $dash_emails ? 'is-active' : 'is-missing'; ?>">
                    <span class="dot"></span>
                    <?php echo $dash_emails ? esc_html__('Email automatiche attive', 'fp-cartrecovery') : esc_html__('Email automatiche off', 'fp-cartrecovery'); ?>
                </span>
                <a href="<?php echo esc_url($settings_url); ?>" class="fpcartrecovery-status-pill">
                    <?php echo esc_html__('Impostazioni', 'fp-cartrecovery'); ?>
                </a>
                <a href="<?php echo esc_url($live_url); ?>" class="fpcartrecovery-status-pill">
                    <?php echo esc_html__('Carrelli attivi', 'fp-cartrecovery'); ?>
                </a>
            </div>

            <div class="fpcartrecovery-card">
                <div class="fpcartrecovery-card-header">
                    <div class="fpcartrecovery-card-header-left">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h2><?php echo esc_html__('Statistiche', 'fp-cartrecovery'); ?></h2>
                    </div>
                    <div class="fpcartrecovery-filter-links">
                        <?php
                        $stats_base = admin_url('admin.php?page=fp_cartrecovery_dashboard');
                        if ($status_filter) $stats_base = add_query_arg('status', $status_filter, $stats_base);
                        if ($list_period > 0) $stats_base = add_query_arg('list_days', $list_period, $stats_base);
                        ?>
                        <a href="<?php echo esc_url(add_query_arg('stats_days', 0, $stats_base)); ?>" class="<?php echo $stats_period === 0 ? 'current' : ''; ?>"><?php echo esc_html__('Tutti', 'fp-cartrecovery'); ?></a>
                        <a href="<?php echo esc_url(add_query_arg('stats_days', 7, $stats_base)); ?>" class="<?php echo $stats_period === 7 ? 'current' : ''; ?>">7 <?php echo esc_html__('giorni', 'fp-cartrecovery'); ?></a>
                        <a href="<?php echo esc_url(add_query_arg('stats_days', 30, $stats_base)); ?>" class="<?php echo $stats_period === 30 ? 'current' : ''; ?>">30 <?php echo esc_html__('giorni', 'fp-cartrecovery'); ?></a>
                        <a href="<?php echo esc_url(add_query_arg('stats_days', 90, $stats_base)); ?>" class="<?php echo $stats_period === 90 ? 'current' : ''; ?>">90 <?php echo esc_html__('giorni', 'fp-cartrecovery'); ?></a>
                    </div>
                </div>
                <div class="fpcartrecovery-card-body">
                    <?php
                    $total_abandoned_recovered = $stats['abandoned'] + $stats['recovered'];
                    $conversion_rate = $total_abandoned_recovered > 0 ? round(100 * $stats['recovered'] / $total_abandoned_recovered, 1) : 0;
                    ?>
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
                        <div class="fpcartrecovery-stat">
                            <span class="fpcartrecovery-stat-value"><?php echo esc_html((string) $conversion_rate); ?>%</span>
                            <span class="fpcartrecovery-stat-label"><?php echo esc_html__('Tasso conversione', 'fp-cartrecovery'); ?></span>
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
                        <?php
                        $base_list = admin_url('admin.php?page=fp_cartrecovery_dashboard');
                        $base_list .= $stats_period > 0 ? '&stats_days=' . $stats_period : '';
                        $base_list .= $list_period > 0 ? '&list_days=' . $list_period : '';
                        ?>
                        <a href="<?php echo esc_url($base_list); ?>" class="<?php echo $status_filter === '' ? 'current' : ''; ?>"><?php echo esc_html__('Tutti', 'fp-cartrecovery'); ?></a>
                        <a href="<?php echo esc_url(add_query_arg('status', 'abandoned', $base_list)); ?>" class="<?php echo $status_filter === 'abandoned' ? 'current' : ''; ?>"><?php echo esc_html__('Abbandonati', 'fp-cartrecovery'); ?></a>
                        <a href="<?php echo esc_url(add_query_arg('status', 'recovered', $base_list)); ?>" class="<?php echo $status_filter === 'recovered' ? 'current' : ''; ?>"><?php echo esc_html__('Recuperati', 'fp-cartrecovery'); ?></a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=fp_cartrecovery_dashboard&export=csv' . ($status_filter ? '&status=' . $status_filter : '') . ($list_period ? '&list_days=' . $list_period : ''))); ?>" class="fpcartrecovery-export-link">
                            <?php echo esc_html__('Esporta CSV', 'fp-cartrecovery'); ?>
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
                                    <th><?php echo esc_html__('Reminder', 'fp-cartrecovery'); ?></th>
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
                                        <td><?php echo esc_html((string) ($row['reminder_sent'] ?? 0)); ?></td>
                                        <td>
                                            <span class="fpcartrecovery-badge fpcartrecovery-badge-<?php echo $row['status'] === 'recovered' ? 'success' : 'warning'; ?>">
                                                <?php echo $row['status'] === 'recovered' ? esc_html__('Recuperato', 'fp-cartrecovery') : esc_html__('Abbandonato', 'fp-cartrecovery'); ?>
                                            </span>
                                        </td>
                                        <td class="fpcartrecovery-actions-cell">
                                            <?php if (($row['status'] ?? '') === 'abandoned' && !empty($row['recovery_token'])) : ?>
                                                <?php $recovery_url = RecoveryHandler::get_recovery_url($row['recovery_token']); ?>
                                                <button type="button" class="fpcartrecovery-btn fpcartrecovery-btn-secondary fpcartrecovery-copy-link" data-url="<?php echo esc_attr($recovery_url); ?>">
                                                    <?php echo esc_html__('Copia link', 'fp-cartrecovery'); ?>
                                                </button>
                                                <?php if (!empty($row['email'])) : ?>
                                                <button type="button" class="fpcartrecovery-btn fpcartrecovery-btn-secondary fpcartrecovery-send-reminder" data-id="<?php echo esc_attr((string) ($row['id'] ?? 0)); ?>">
                                                    <?php echo esc_html__('Invia email', 'fp-cartrecovery'); ?>
                                                </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <button type="button" class="fpcartrecovery-btn fpcartrecovery-btn-secondary fpcartrecovery-delete-cart" data-id="<?php echo esc_attr((string) ($row['id'] ?? 0)); ?>">
                                                <?php echo esc_html__('Elimina', 'fp-cartrecovery'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php
                        if ($total > self::PER_PAGE) {
                            $pagination = paginate_links([
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => (int) ceil($total / self::PER_PAGE),
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

    private function export_csv(): void {
        $repository = new AbandonedCartRepository();
        $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $days = isset($_GET['list_days']) ? absint($_GET['list_days']) : 0;

        $result = $repository->get_paginated(1, 10000, $status_filter, $days);
        $items = $result['items'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="fp-cart-recovery-' . gmdate('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            __('ID', 'fp-cartrecovery'),
            __('Email', 'fp-cartrecovery'),
            __('User ID', 'fp-cartrecovery'),
            __('Totale', 'fp-cartrecovery'),
            __('Valuta', 'fp-cartrecovery'),
            __('Stato', 'fp-cartrecovery'),
            __('Reminder inviati', 'fp-cartrecovery'),
            __('Data', 'fp-cartrecovery'),
        ]);

        foreach ($items as $row) {
            $email = $row['email'] ?? '';
            if (empty($email) && !empty($row['user_id'])) {
                $user = get_userdata((int) $row['user_id']);
                $email = $user ? $user->user_email : '';
            }
            fputcsv($out, [
                $row['id'] ?? '',
                $email,
                $row['user_id'] ?? '',
                $row['cart_total'] ?? '',
                $row['currency'] ?? '',
                $row['status'] ?? '',
                $row['reminder_sent'] ?? 0,
                $row['updated_at'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
}
