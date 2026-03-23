<?php

declare(strict_types=1);

namespace FP\CartRecovery\Admin;

/**
 * Pagina Guida FP Cart Recovery.
 */
final class HelpPage {

    public function render(): void {
        $dashboard_url = admin_url('admin.php?page=fp_cartrecovery_dashboard');
        $settings_url = admin_url('admin.php?page=' . AdminMenu::SETTINGS_SLUG);
        ?>
        <div class="wrap fpcartrecovery-admin-page">
            <h1 class="screen-reader-text"><?php echo esc_html__('FP Cart Recovery - Guida', 'fp-cartrecovery'); ?></h1>

            <div class="fpcartrecovery-page-header">
                <div class="fpcartrecovery-page-header-content">
                    <h2 class="fpcartrecovery-page-header-title" aria-hidden="true">
                        <span class="dashicons dashicons-book"></span>
                        <?php echo esc_html__('Guida', 'fp-cartrecovery'); ?>
                    </h2>
                    <p><?php echo esc_html__('Riepilogo funzionalità e placeholder.', 'fp-cartrecovery'); ?></p>
                </div>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="fpcartrecovery-btn fpcartrecovery-btn-secondary"><?php echo esc_html__('Dashboard', 'fp-cartrecovery'); ?></a>
                <a href="<?php echo esc_url($settings_url); ?>" class="fpcartrecovery-btn fpcartrecovery-btn-secondary" style="margin-left:8px;"><?php echo esc_html__('Impostazioni', 'fp-cartrecovery'); ?></a>
            </div>

            <div class="fpcartrecovery-card">
                <div class="fpcartrecovery-card-header">
                    <h2><?php echo esc_html__('Funzionalità', 'fp-cartrecovery'); ?></h2>
                </div>
                <div class="fpcartrecovery-card-body">
                    <ul style="line-height:1.8;">
                        <li><?php echo esc_html__('Tracciamento automatico carrelli WooCommerce (utenti loggati e guest)', 'fp-cartrecovery'); ?></li>
                        <li><?php echo esc_html__('Email di richiamo configurabili (1ª, 2ª, 3ª)', 'fp-cartrecovery'); ?></li>
                        <li><?php echo esc_html__('Link di recovery per ripristinare il carrello con un click', 'fp-cartrecovery'); ?></li>
                        <li><?php echo esc_html__('Soglia minimo carrello, esclusione prodotti/categorie', 'fp-cartrecovery'); ?></li>
                        <li><?php echo esc_html__('Link unsubscribe nelle email', 'fp-cartrecovery'); ?></li>
                        <li><?php echo esc_html__('Pulizia automatica carrelli vecchi', 'fp-cartrecovery'); ?></li>
                        <li><?php echo esc_html__('Integrazione FP Tracking e Brevo', 'fp-cartrecovery'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="fpcartrecovery-card">
                <div class="fpcartrecovery-card-header">
                    <h2><?php echo esc_html__('Placeholder email', 'fp-cartrecovery'); ?></h2>
                </div>
                <div class="fpcartrecovery-card-body">
                    <table class="widefat">
                        <thead>
                            <tr><th><?php echo esc_html__('Placeholder', 'fp-cartrecovery'); ?></th><th><?php echo esc_html__('Descrizione', 'fp-cartrecovery'); ?></th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>{{recovery_link}}</code></td><td><?php echo esc_html__('URL per ripristinare il carrello', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{cart_total}}</code></td><td><?php echo esc_html__('Totale formattato', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{shop_name}}</code></td><td><?php echo esc_html__('Nome del sito', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{customer_name}}</code></td><td><?php echo esc_html__('Nome utente o "Cliente"', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{cart_items}}</code></td><td><?php echo esc_html__('Lista HTML prodotti', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{reminder_number}}</code></td><td><?php echo esc_html__('1, 2 o 3', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{logo_html}}</code></td><td><?php echo esc_html__('Immagine logo', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{primary_color}}</code></td><td><?php echo esc_html__('Colore primario (#hex)', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{accent_color}}</code></td><td><?php echo esc_html__('Colore accent (#hex)', 'fp-cartrecovery'); ?></td></tr>
                            <tr><td><code>{{unsubscribe_url}}</code></td><td><?php echo esc_html__('Link disiscrizione', 'fp-cartrecovery'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="fpcartrecovery-card">
                <div class="fpcartrecovery-card-header">
                    <h2><?php echo esc_html__('REST API', 'fp-cartrecovery'); ?></h2>
                </div>
                <div class="fpcartrecovery-card-body">
                    <p><code>GET <?php echo esc_html(rest_url('fp-cart-recovery/v1/stats')); ?>?days=30</code></p>
                    <p><?php echo esc_html__('Richiede autenticazione e capability manage_options. Parametro days: 0, 7, 30, 90.', 'fp-cartrecovery'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}
