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
                        <p class="description"><?php echo esc_html__('Placeholder: {{recovery_link}}, {{cart_total}}, {{shop_name}}', 'fp-cartrecovery'); ?></p>
                        <div class="fpcartrecovery-fields-grid">
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Oggetto email', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="email_subject" value="<?php echo esc_attr($data['email_subject'] ?? ''); ?>" class="large-text" placeholder="<?php echo esc_attr__('Hai dimenticato qualcosa nel carrello', 'fp-cartrecovery'); ?>">
                            </div>
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Nome mittente', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="from_name" value="<?php echo esc_attr($data['from_name'] ?? ''); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            </div>
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Corpo email (HTML, vuoto = template default)', 'fp-cartrecovery'); ?></label>
                                <textarea name="email_body" rows="8" class="large-text"><?php echo esc_textarea($data['email_body'] ?? ''); ?></textarea>
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
        $first_hours = max(1, min(72, absint($_POST['first_reminder_hours'] ?? 2)));
        $second_hours = max(1, min(168, absint($_POST['second_reminder_hours'] ?? 24)));
        $email_subject = sanitize_text_field(wp_unslash($_POST['email_subject'] ?? ''));
        $email_body = wp_kses_post(wp_unslash($_POST['email_body'] ?? ''));
        $from_name = sanitize_text_field(wp_unslash($_POST['from_name'] ?? ''));

        $this->settings->save([
            'enabled'               => $enabled,
            'track_guests'          => $track_guests,
            'first_reminder_hours'  => $first_hours,
            'second_reminder_hours' => $second_hours,
            'email_subject'         => $email_subject,
            'email_body'            => $email_body,
            'from_name'             => $from_name,
        ]);

        add_settings_error(
            'fp_cartrecovery',
            'saved',
            __('Impostazioni salvate.', 'fp-cartrecovery'),
            'success'
        );
    }
}
