<?php

declare(strict_types=1);

namespace FP\CartRecovery\Admin;

use FP\CartRecovery\Domain\Settings;

/**
 * Pagina admin: carrelli aggiornati di recente con polling REST (quasi live).
 */
final class LiveCartsPage {

    public function __construct(
        private readonly Settings $settings
    ) {}

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permessi insufficienti.', 'fp-cartrecovery'));
        }

        $dashboard_url = admin_url('admin.php?page=fp_cartrecovery_dashboard');
        $settings_url = admin_url('admin.php?page=' . AdminMenu::SETTINGS_SLUG);
        $enabled = (bool) $this->settings->get('enabled', false);
        ?>
        <div class="wrap fpcartrecovery-admin-page fpcartrecovery-live-page">
            <h1 class="screen-reader-text"><?php echo esc_html__('Carrelli attivi', 'fp-cartrecovery'); ?></h1>

            <div class="fpcartrecovery-page-header">
                <div class="fpcartrecovery-page-header-content">
                    <h2 class="fpcartrecovery-page-header-title" aria-hidden="true">
                        <span class="dashicons dashicons-controls-repeat" aria-hidden="true"></span>
                        <?php echo esc_html__('Carrelli attivi', 'fp-cartrecovery'); ?>
                    </h2>
                    <p><?php echo esc_html__('Carrelli abbandonati aggiornati negli ultimi minuti (aggiornamento automatico ogni pochi secondi).', 'fp-cartrecovery'); ?></p>
                </div>
                <span class="fpcartrecovery-page-header-badge">v<?php echo esc_html(FP_CARTRECOVERY_VERSION); ?></span>
            </div>

            <div class="fpcartrecovery-status-bar">
                <span class="fpcartrecovery-status-pill <?php echo $enabled ? 'is-active' : 'is-missing'; ?>">
                    <span class="dot"></span>
                    <?php echo $enabled ? esc_html__('Tracciamento attivo', 'fp-cartrecovery') : esc_html__('Tracciamento disattivato', 'fp-cartrecovery'); ?>
                </span>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="fpcartrecovery-status-pill"><?php echo esc_html__('Dashboard', 'fp-cartrecovery'); ?></a>
                <a href="<?php echo esc_url($settings_url); ?>" class="fpcartrecovery-status-pill"><?php echo esc_html__('Impostazioni', 'fp-cartrecovery'); ?></a>
            </div>

            <?php if (!$enabled) : ?>
                <div class="notice notice-warning">
                    <p><?php echo esc_html__('Attiva il recupero nelle impostazioni: senza tracciamento i carrelli non vengono salvati e questa vista resterà vuota.', 'fp-cartrecovery'); ?></p>
                </div>
            <?php endif; ?>

            <div class="fpcartrecovery-card">
                <div class="fpcartrecovery-card-header">
                    <div class="fpcartrecovery-card-header-left">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        <h2><?php echo esc_html__('Sessioni recenti', 'fp-cartrecovery'); ?></h2>
                    </div>
                    <div class="fpcartrecovery-live-meta">
                        <label for="fpcartrecovery-live-window" class="screen-reader-text"><?php echo esc_html__('Finestra temporale (minuti)', 'fp-cartrecovery'); ?></label>
                        <select id="fpcartrecovery-live-window" class="fpcartrecovery-live-window">
                            <option value="5">5 <?php echo esc_html__('min', 'fp-cartrecovery'); ?></option>
                            <option value="15" selected>15 <?php echo esc_html__('min', 'fp-cartrecovery'); ?></option>
                            <option value="30">30 <?php echo esc_html__('min', 'fp-cartrecovery'); ?></option>
                            <option value="60">60 <?php echo esc_html__('min', 'fp-cartrecovery'); ?></option>
                        </select>
                        <span class="fpcartrecovery-live-last" id="fpcartrecovery-live-last" aria-live="polite"></span>
                    </div>
                </div>
                <div class="fpcartrecovery-card-body">
                    <p class="description fpcartrecovery-live-hint"><?php echo esc_html__('I dati riflettono il salvataggio lato server dopo le azioni sul carrello (stesso flusso della dashboard).', 'fp-cartrecovery'); ?></p>
                    <div class="fpcartrecovery-live-loading" id="fpcartrecovery-live-loading" hidden><?php echo esc_html__('Aggiornamento…', 'fp-cartrecovery'); ?></div>
                    <table class="fpcartrecovery-table fpcartrecovery-live-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Email / Utente', 'fp-cartrecovery'); ?></th>
                                <th><?php echo esc_html__('Righe', 'fp-cartrecovery'); ?></th>
                                <th><?php echo esc_html__('Anteprima', 'fp-cartrecovery'); ?></th>
                                <th><?php echo esc_html__('Totale', 'fp-cartrecovery'); ?></th>
                                <th><?php echo esc_html__('Ultimo aggiornamento', 'fp-cartrecovery'); ?></th>
                                <th><?php echo esc_html__('Reminder', 'fp-cartrecovery'); ?></th>
                                <th><?php echo esc_html__('Azioni', 'fp-cartrecovery'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="fpcartrecovery-live-tbody">
                            <tr class="fpcartrecovery-live-placeholder">
                                <td colspan="7"><?php echo esc_html__('Caricamento…', 'fp-cartrecovery'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
