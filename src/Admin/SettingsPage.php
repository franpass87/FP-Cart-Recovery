<?php

declare(strict_types=1);

namespace FP\CartRecovery\Admin;

use FP\CartRecovery\Domain\Settings;

/**
 * Pagina Impostazioni FP Cart Recovery.
 */
final class SettingsPage {

    public function __construct(
        private readonly Settings $settings
    ) {}

    public function render(): void {
        if (isset($_POST['fp_cartrecovery_save']) && check_admin_referer('fp_cartrecovery_save_settings', 'fp_cartrecovery_nonce')) {
            if (current_user_can('manage_options')) {
                $this->save();
            }
        }

        settings_errors('fp_cartrecovery');
        $data = $this->settings->all();
        $dashboard_url = admin_url('admin.php?page=fp_cartrecovery_dashboard');
        ?>
        <div class="wrap fpcartrecovery-admin-page">
            <h1 class="screen-reader-text"><?php echo esc_html__('FP Cart Recovery - Impostazioni', 'fp-cartrecovery'); ?></h1>

            <div class="fpcartrecovery-page-header">
                <div class="fpcartrecovery-page-header-content">
                    <h2 class="fpcartrecovery-page-header-title" aria-hidden="true">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php echo esc_html__('Impostazioni', 'fp-cartrecovery'); ?>
                    </h2>
                    <p><?php echo esc_html__('Configura il recupero carrelli abbandonati.', 'fp-cartrecovery'); ?></p>
                </div>
                <a href="<?php echo esc_url($dashboard_url); ?>" class="fpcartrecovery-btn fpcartrecovery-btn-secondary">
                    <?php echo esc_html__('Dashboard', 'fp-cartrecovery'); ?>
                </a>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('fp_cartrecovery_save_settings', 'fp_cartrecovery_nonce'); ?>
                <input type="hidden" name="fp_cartrecovery_save" value="1">

                <div class="fpcartrecovery-card">
                    <div class="fpcartrecovery-card-header">
                        <div class="fpcartrecovery-card-header-left">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <h2><?php echo esc_html__('Funzionalità', 'fp-cartrecovery'); ?></h2>
                        </div>
                        <span class="fpcartrecovery-badge <?php echo !empty($data['enabled']) ? 'fpcartrecovery-badge-success' : 'fpcartrecovery-badge-neutral'; ?>">
                            <?php echo !empty($data['enabled']) ? '&#10003; ' . esc_html__('Attivo', 'fp-cartrecovery') : esc_html__('Disattivo', 'fp-cartrecovery'); ?>
                        </span>
                    </div>
                    <div class="fpcartrecovery-card-body">
                        <div class="fpcartrecovery-toggle-row">
                            <div class="fpcartrecovery-toggle-info">
                                <strong><?php echo esc_html__('Attiva recupero carrelli', 'fp-cartrecovery'); ?></strong>
                                <span><?php echo esc_html__('Traccia carrelli abbandonati e invia email di richiamo.', 'fp-cartrecovery'); ?></span>
                            </div>
                            <label class="fpcartrecovery-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($data['enabled'])); ?>>
                                <span class="fpcartrecovery-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="fpcartrecovery-toggle-row">
                            <div class="fpcartrecovery-toggle-info">
                                <strong><?php echo esc_html__('Traccia utenti guest', 'fp-cartrecovery'); ?></strong>
                                <span><?php echo esc_html__('Salva anche i carrelli di chi non è loggato (email catturata al checkout).', 'fp-cartrecovery'); ?></span>
                            </div>
                            <label class="fpcartrecovery-toggle">
                                <input type="checkbox" name="track_guests" value="1" <?php checked(!empty($data['track_guests'])); ?>>
                                <span class="fpcartrecovery-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="fpcartrecovery-card">
                    <div class="fpcartrecovery-card-header">
                        <div class="fpcartrecovery-card-header-left">
                            <span class="dashicons dashicons-clock"></span>
                            <h2><?php echo esc_html__('Tempistiche', 'fp-cartrecovery'); ?></h2>
                        </div>
                    </div>
                    <div class="fpcartrecovery-card-body">
                        <div class="fpcartrecovery-fields-grid">
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Prima email (ore dopo abbandono)', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="first_reminder_hours" value="<?php echo esc_attr((string) ($data['first_reminder_hours'] ?? 2)); ?>" min="1" max="72" class="small-text">
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Seconda email (ore dopo abbandono)', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="second_reminder_hours" value="<?php echo esc_attr((string) ($data['second_reminder_hours'] ?? 24)); ?>" min="1" max="168" class="small-text">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fpcartrecovery-card">
                    <div class="fpcartrecovery-card-header">
                        <div class="fpcartrecovery-card-header-left">
                            <span class="dashicons dashicons-email"></span>
                            <h2><?php echo esc_html__('Email di richiamo', 'fp-cartrecovery'); ?></h2>
                        </div>
                    </div>
                    <div class="fpcartrecovery-card-body">
                        <div class="fpcartrecovery-field fpcartrecovery-field-full" style="margin-bottom:16px">
                            <label><?php echo esc_html__('Invio email', 'fp-cartrecovery'); ?></label>
                            <div class="fpcartrecovery-radio-group">
                                <label>
                                    <input type="radio" name="email_provider" value="wp" <?php checked(($data['email_provider'] ?? 'wp') === 'wp'); ?>>
                                    <?php echo esc_html__('WordPress (wp_mail)', 'fp-cartrecovery'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="email_provider" value="brevo" <?php checked(($data['email_provider'] ?? '') === 'brevo'); ?>>
                                    <?php echo esc_html__('Brevo (API + evento FP Tracking)', 'fp-cartrecovery'); ?>
                                </label>
                            </div>
                            <span class="fpcartrecovery-hint"><?php echo esc_html__('Brevo usa le impostazioni di FP Marketing Tracking Layer. Con Brevo viene emesso l\'evento cart_recovery_email_sent a fp_tracking_event.', 'fp-cartrecovery'); ?></span>
                        </div>
                        <details class="fpcartrecovery-placeholders" style="margin-bottom:16px">
                            <summary><?php echo esc_html__('Placeholder disponibili', 'fp-cartrecovery'); ?></summary>
                            <table class="widefat" style="margin-top:8px">
                                <tbody>
                                    <tr><td><code>{{recovery_link}}</code></td><td><?php echo esc_html__('URL per ripristinare il carrello', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{cart_total}}</code></td><td><?php echo esc_html__('Totale formattato (es. €29,90)', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{shop_name}}</code></td><td><?php echo esc_html__('Nome del sito', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{customer_name}}</code></td><td><?php echo esc_html__('Nome utente o "Cliente"', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{cart_items}}</code></td><td><?php echo esc_html__('Lista HTML prodotti nel carrello', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{reminder_number}}</code></td><td><?php echo esc_html__('1 o 2 (prima/seconda email)', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{logo_html}}</code></td><td><?php echo esc_html__('Immagine logo (se configurata)', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{primary_color}}</code></td><td><?php echo esc_html__('Colore primario (es. #667eea)', 'fp-cartrecovery'); ?></td></tr>
                                    <tr><td><code>{{accent_color}}</code></td><td><?php echo esc_html__('Colore accent (es. #764ba2)', 'fp-cartrecovery'); ?></td></tr>
                                </tbody>
                            </table>
                        </details>
                        <div class="fpcartrecovery-fields-grid">
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Oggetto 1ª email', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="email_subject" value="<?php echo esc_attr($data['email_subject'] ?? ''); ?>" class="large-text" placeholder="<?php echo esc_attr__('Hai dimenticato qualcosa nel carrello', 'fp-cartrecovery'); ?>">
                            </div>
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Corpo 1ª email (HTML, vuoto = template default)', 'fp-cartrecovery'); ?></label>
                                <textarea name="email_body" rows="6" class="large-text"><?php echo esc_textarea($data['email_body'] ?? ''); ?></textarea>
                            </div>
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Oggetto 2ª email', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="email_subject_2" value="<?php echo esc_attr($data['email_subject_2'] ?? ''); ?>" class="large-text" placeholder="<?php echo esc_attr__('Il tuo carrello ti aspetta ancora', 'fp-cartrecovery'); ?>">
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('Vuoto = uguale alla 1ª', 'fp-cartrecovery'); ?></span>
                            </div>
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Corpo 2ª email', 'fp-cartrecovery'); ?></label>
                                <textarea name="email_body_2" rows="6" class="large-text"><?php echo esc_textarea($data['email_body_2'] ?? ''); ?></textarea>
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('Vuoto = uguale alla 1ª', 'fp-cartrecovery'); ?></span>
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Nome mittente', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="from_name" value="<?php echo esc_attr($data['from_name'] ?? ''); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Email mittente', 'fp-cartrecovery'); ?></label>
                                <input type="email" name="from_email" value="<?php echo esc_attr($data['from_email'] ?? ''); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('Vuoto = email admin', 'fp-cartrecovery'); ?></span>
                            </div>
                        </div>
                        <div class="fpcartrecovery-fields-grid" style="margin-top:20px;padding-top:20px;border-top:1px solid #e5e7eb;">
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Logo email', 'fp-cartrecovery'); ?></label>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="url" id="fpcartrecovery-logo-url" name="email_logo_url" value="<?php echo esc_attr($data['email_logo_url'] ?? ''); ?>" class="large-text" placeholder="https://...">
                                    <button type="button" id="fpcartrecovery-logo-upload" class="fpcartrecovery-btn fpcartrecovery-btn-secondary" style="white-space:nowrap;">
                                        <?php echo esc_html__('Seleziona da Media', 'fp-cartrecovery'); ?>
                                    </button>
                                </div>
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('Logo mostrato nell\'header delle email (max altezza 60px). Vuoto = nessun logo.', 'fp-cartrecovery'); ?></span>
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Colore primario', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="email_primary_color" value="<?php echo esc_attr($data['email_primary_color'] ?? '#667eea'); ?>" class="small-text" placeholder="#667eea" maxlength="7" style="width:100px;">
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Colore accent', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="email_accent_color" value="<?php echo esc_attr($data['email_accent_color'] ?? '#764ba2'); ?>" class="small-text" placeholder="#764ba2" maxlength="7" style="width:100px;">
                            </div>
                        </div>
                    </div>
                </div>

                <p>
                    <button type="submit" class="fpcartrecovery-btn fpcartrecovery-btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo esc_html__('Salva impostazioni', 'fp-cartrecovery'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    private function save(): void {
        $enabled = !empty($_POST['enabled']);
        $track_guests = !empty($_POST['track_guests']);
        $email_provider = in_array($_POST['email_provider'] ?? '', ['wp', 'brevo'], true) ? $_POST['email_provider'] : 'wp';
        $first_hours = max(1, min(72, absint($_POST['first_reminder_hours'] ?? 2)));
        $second_hours = max(1, min(168, absint($_POST['second_reminder_hours'] ?? 24)));
        $email_subject = sanitize_text_field(wp_unslash($_POST['email_subject'] ?? ''));
        $email_body = wp_kses_post(wp_unslash($_POST['email_body'] ?? ''));
        $email_subject_2 = sanitize_text_field(wp_unslash($_POST['email_subject_2'] ?? ''));
        $email_body_2 = wp_kses_post(wp_unslash($_POST['email_body_2'] ?? ''));
        $from_name = sanitize_text_field(wp_unslash($_POST['from_name'] ?? ''));
        $from_email = sanitize_email(wp_unslash($_POST['from_email'] ?? ''));
        $logo_url = esc_url_raw(wp_unslash($_POST['email_logo_url'] ?? ''));
        $primary_color = $this->sanitize_hex_color(sanitize_text_field(wp_unslash($_POST['email_primary_color'] ?? '#667eea')));
        $accent_color = $this->sanitize_hex_color(sanitize_text_field(wp_unslash($_POST['email_accent_color'] ?? '#764ba2')));

        $this->settings->save([
            'enabled'               => $enabled,
            'track_guests'          => $track_guests,
            'email_provider'        => $email_provider,
            'first_reminder_hours'  => $first_hours,
            'second_reminder_hours' => $second_hours,
            'email_subject'         => $email_subject,
            'email_body'            => $email_body,
            'email_subject_2'       => $email_subject_2,
            'email_body_2'          => $email_body_2,
            'from_name'             => $from_name,
            'from_email'            => $from_email,
            'email_logo_url'        => $logo_url,
            'email_primary_color'   => $primary_color,
            'email_accent_color'    => $accent_color,
        ]);

        add_settings_error(
            'fp_cartrecovery',
            'saved',
            __('Impostazioni salvate.', 'fp-cartrecovery'),
            'success'
        );
    }

    private function sanitize_hex_color(string $color): string {
        $color = trim($color);
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
            return $color;
        }
        return '#667eea';
    }
}
