<?php

declare(strict_types=1);

namespace FP\CartRecovery\Admin;

use FP\CartRecovery\Domain\Settings;
use FP\CartRecovery\Integrations\EmailScheduler;
use FP\CartRecovery\Utils\ColorHelper;

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

        $default_body_tpl = EmailScheduler::get_default_body_template();
        $disp_email_subject = trim((string) ($data['email_subject'] ?? '')) !== ''
            ? (string) $data['email_subject']
            : EmailScheduler::get_default_email_subject(1);
        $disp_email_body = (string) ($data['email_body'] ?? '') !== ''
            ? (string) $data['email_body']
            : $default_body_tpl;
        $disp_email_subject_2 = trim((string) ($data['email_subject_2'] ?? '')) !== ''
            ? (string) $data['email_subject_2']
            : $disp_email_subject;
        $disp_email_body_2 = (string) ($data['email_body_2'] ?? '') !== ''
            ? (string) $data['email_body_2']
            : $disp_email_body;
        $disp_email_subject_3 = trim((string) ($data['email_subject_3'] ?? '')) !== ''
            ? (string) $data['email_subject_3']
            : $disp_email_subject_2;
        $disp_email_body_3 = (string) ($data['email_body_3'] ?? '') !== ''
            ? (string) $data['email_body_3']
            : $disp_email_body_2;

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
                        <?php
                        $tracking_on = !empty($data['enabled']);
                        $emails_on = $tracking_on && !empty($data['emails_enabled']);
                        ?>
                        <span class="fpcartrecovery-badge <?php echo $tracking_on ? 'fpcartrecovery-badge-success' : 'fpcartrecovery-badge-neutral'; ?>">
                            <?php echo $tracking_on ? '&#10003; ' . esc_html__('Tracciamento attivo', 'fp-cartrecovery') : esc_html__('Disattivo', 'fp-cartrecovery'); ?>
                        </span>
                    </div>
                    <div class="fpcartrecovery-card-body">
                        <div class="fpcartrecovery-toggle-row">
                            <div class="fpcartrecovery-toggle-info">
                                <strong><?php echo esc_html__('Traccia carrelli abbandonati', 'fp-cartrecovery'); ?></strong>
                                <span><?php echo esc_html__('Salva carrelli in dashboard e statistiche; consente invio manuale e link di recovery.', 'fp-cartrecovery'); ?></span>
                            </div>
                            <label class="fpcartrecovery-toggle">
                                <input type="checkbox" name="enabled" value="1" id="fpcartrecovery-enabled-tracking" <?php checked($tracking_on); ?>>
                                <span class="fpcartrecovery-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="fpcartrecovery-toggle-row">
                            <div class="fpcartrecovery-toggle-info">
                                <strong><?php echo esc_html__('Email di richiamo automatiche', 'fp-cartrecovery'); ?></strong>
                                <span><?php echo esc_html__('Invio programmato (cron) delle reminder. Disattivalo per tracciare senza email automatiche.', 'fp-cartrecovery'); ?></span>
                            </div>
                            <label class="fpcartrecovery-toggle">
                                <input type="checkbox" name="emails_enabled" value="1" id="fpcartrecovery-enabled-emails" <?php checked($emails_on); ?> <?php disabled(!$tracking_on); ?>>
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
                                <label><?php echo esc_html__('Minuti per considerare abbandono', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="abandon_after_minutes" value="<?php echo esc_attr((string) ($data['abandon_after_minutes'] ?? 30)); ?>" min="0" max="1440" class="small-text">
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('0 = usa solo ore email. Es: 30 = non inviare a carrelli modificati da meno di 30 min.', 'fp-cartrecovery'); ?></span>
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Soglia minimo carrello (€)', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="min_cart_value" value="<?php echo esc_attr((string) ($data['min_cart_value'] ?? 0)); ?>" min="0" max="9999" step="0.01" class="small-text">
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('0 = nessuna. Non inviare email se totale carrello &lt; soglia.', 'fp-cartrecovery'); ?></span>
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Frequenza cron', 'fp-cartrecovery'); ?></label>
                                <select name="cron_interval">
                                    <option value="fp_cartrecovery_15min" <?php selected(($data['cron_interval'] ?? '') === 'fp_cartrecovery_15min'); ?>><?php echo esc_html__('Ogni 15 minuti', 'fp-cartrecovery'); ?></option>
                                    <option value="fp_cartrecovery_30min" <?php selected(($data['cron_interval'] ?? '') === 'fp_cartrecovery_30min'); ?>><?php echo esc_html__('Ogni 30 minuti', 'fp-cartrecovery'); ?></option>
                                    <option value="hourly" <?php selected(($data['cron_interval'] ?? 'hourly') === 'hourly'); ?>><?php echo esc_html__('Ogni ora', 'fp-cartrecovery'); ?></option>
                                    <option value="twicedaily" <?php selected(($data['cron_interval'] ?? '') === 'twicedaily'); ?>><?php echo esc_html__('Due volte al giorno', 'fp-cartrecovery'); ?></option>
                                    <option value="daily" <?php selected(($data['cron_interval'] ?? '') === 'daily'); ?>><?php echo esc_html__('Giornaliero', 'fp-cartrecovery'); ?></option>
                                </select>
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Pulizia carrelli (giorni)', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="cleanup_after_days" value="<?php echo esc_attr((string) ($data['cleanup_after_days'] ?? 90)); ?>" min="0" max="365" class="small-text">
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('0 = disattiva. Elimina carrelli abbandonati più vecchi.', 'fp-cartrecovery'); ?></span>
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Prima email (ore dopo abbandono)', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="first_reminder_hours" value="<?php echo esc_attr((string) ($data['first_reminder_hours'] ?? 2)); ?>" min="1" max="72" class="small-text">
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Seconda email (ore dopo abbandono)', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="second_reminder_hours" value="<?php echo esc_attr((string) ($data['second_reminder_hours'] ?? 24)); ?>" min="1" max="168" class="small-text">
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Terza email', 'fp-cartrecovery'); ?></label>
                                <label class="fpcartrecovery-inline-checkbox">
                                    <input type="checkbox" name="third_reminder_enabled" value="1" <?php checked(!empty($data['third_reminder_enabled'])); ?>>
                                    <?php echo esc_html__('Attiva', 'fp-cartrecovery'); ?>
                                </label>
                                <input type="number" name="third_reminder_hours" value="<?php echo esc_attr((string) ($data['third_reminder_hours'] ?? 72)); ?>" min="1" max="336" class="small-text" style="width:80px;">
                                <?php echo esc_html__('ore', 'fp-cartrecovery'); ?>
                            </div>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Scadenza link recovery (giorni)', 'fp-cartrecovery'); ?></label>
                                <input type="number" name="recovery_link_expiry_days" value="<?php echo esc_attr((string) ($data['recovery_link_expiry_days'] ?? 0)); ?>" min="0" max="365" class="small-text">
                                <span class="fpcartrecovery-hint"><?php echo esc_html__('0 = mai. Es: 7 = link valido 7 giorni dall\'ultima modifica carrello.', 'fp-cartrecovery'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fpcartrecovery-card fpcartrecovery-card-email">
                    <div class="fpcartrecovery-card-header">
                        <div class="fpcartrecovery-card-header-left">
                            <span class="dashicons dashicons-email-alt"></span>
                            <h2><?php echo esc_html__('Email di richiamo', 'fp-cartrecovery'); ?></h2>
                        </div>
                    </div>
                    <div class="fpcartrecovery-card-body">
                        <div class="fpcartrecovery-email-section fpcartrecovery-email-provider">
                            <h3 class="fpcartrecovery-email-section-title"><span class="dashicons dashicons-admin-generic"></span> <?php echo esc_html__('Invio', 'fp-cartrecovery'); ?></h3>
                            <div class="fpcartrecovery-radio-group fpcartrecovery-radio-pills">
                                <label class="fpcartrecovery-radio-pill">
                                    <input type="radio" name="email_provider" value="wp" <?php checked(($data['email_provider'] ?? 'wp') === 'wp'); ?>>
                                    <span class="fpcartrecovery-radio-pill-label"><?php echo esc_html__('WordPress (wp_mail)', 'fp-cartrecovery'); ?></span>
                                </label>
                                <label class="fpcartrecovery-radio-pill">
                                    <input type="radio" name="email_provider" value="brevo" <?php checked(($data['email_provider'] ?? '') === 'brevo'); ?>>
                                    <span class="fpcartrecovery-radio-pill-label"><?php echo esc_html__('Brevo (API)', 'fp-cartrecovery'); ?></span>
                                </label>
                            </div>
                            <span class="fpcartrecovery-hint"><?php echo esc_html__('Brevo usa le impostazioni di FP Marketing Tracking Layer.', 'fp-cartrecovery'); ?></span>
                            <div class="fpcartrecovery-wp-mail-notice fpcartrecovery-wp-mail-only"<?php echo ($data['email_provider'] ?? 'wp') !== 'wp' ? ' style="display:none"' : ''; ?>>
                                <span class="dashicons dashicons-info"></span>
                                <span><?php echo esc_html__('Per una deliverability migliore, considera un plugin SMTP (es. WP Mail SMTP, FluentSMTP).', 'fp-cartrecovery'); ?></span>
                            </div>
                        </div>

                        <div class="fpcartrecovery-email-section fpcartrecovery-email-aspetto">
                            <h3 class="fpcartrecovery-email-section-title"><span class="dashicons dashicons-art"></span> <?php echo esc_html__('Aspetto email', 'fp-cartrecovery'); ?></h3>
                            <div class="fpcartrecovery-branding-preview" style="--preview-primary:<?php echo esc_attr(\FP\CartRecovery\Utils\ColorHelper::sanitize_hex($data['email_primary_color'] ?? '#667eea')); ?>;--preview-accent:<?php echo esc_attr(\FP\CartRecovery\Utils\ColorHelper::sanitize_hex($data['email_accent_color'] ?? '#764ba2')); ?>">
                                <div class="fpcartrecovery-branding-preview-strip"></div>
                                <div class="fpcartrecovery-branding-fields">
                                    <div class="fpcartrecovery-field fpcartrecovery-logo-field">
                                        <label><?php echo esc_html__('Logo', 'fp-cartrecovery'); ?></label>
                                        <div class="fpcartrecovery-logo-picker">
                                            <?php $logo_url = esc_url_raw($data['email_logo_url'] ?? ''); ?>
                                            <?php if ($logo_url) : ?>
                                                <div class="fpcartrecovery-logo-preview"><img src="<?php echo esc_url($logo_url); ?>" alt="Logo" /></div>
                                            <?php endif; ?>
                                            <div class="fpcartrecovery-logo-input-wrap">
                                                <input type="url" id="fpcartrecovery-logo-url" name="email_logo_url" value="<?php echo esc_attr($logo_url); ?>" placeholder="https://..." class="regular-text">
                                                <button type="button" id="fpcartrecovery-logo-upload" class="fpcartrecovery-btn fpcartrecovery-btn-secondary"><?php echo esc_html__('Media', 'fp-cartrecovery'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fpcartrecovery-color-pair">
                                        <div class="fpcartrecovery-field">
                                            <label><?php echo esc_html__('Colore primario', 'fp-cartrecovery'); ?></label>
                                            <div class="fpcartrecovery-color-input">
                                                <input type="color" id="fpcartrecovery-primary-color-picker" value="<?php echo esc_attr(\FP\CartRecovery\Utils\ColorHelper::sanitize_hex($data['email_primary_color'] ?? '#667eea')); ?>" title="<?php echo esc_attr__('Scegli colore', 'fp-cartrecovery'); ?>">
                                                <input type="text" name="email_primary_color" id="fpcartrecovery-primary-color" value="<?php echo esc_attr($data['email_primary_color'] ?? '#667eea'); ?>" maxlength="7" placeholder="#667eea">
                                            </div>
                                        </div>
                                        <div class="fpcartrecovery-field">
                                            <label><?php echo esc_html__('Colore accent', 'fp-cartrecovery'); ?></label>
                                            <div class="fpcartrecovery-color-input">
                                                <input type="color" id="fpcartrecovery-accent-color-picker" value="<?php echo esc_attr(\FP\CartRecovery\Utils\ColorHelper::sanitize_hex($data['email_accent_color'] ?? '#764ba2')); ?>" title="<?php echo esc_attr__('Scegli colore', 'fp-cartrecovery'); ?>">
                                                <input type="text" name="email_accent_color" id="fpcartrecovery-accent-color" value="<?php echo esc_attr($data['email_accent_color'] ?? '#764ba2'); ?>" maxlength="7" placeholder="#764ba2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fpcartrecovery-email-section fpcartrecovery-email-mittente">
                            <h3 class="fpcartrecovery-email-section-title"><span class="dashicons dashicons-email"></span> <?php echo esc_html__('Mittente', 'fp-cartrecovery'); ?></h3>
                            <div class="fpcartrecovery-fields-grid fpcartrecovery-fields-inline">
                                <div class="fpcartrecovery-field">
                                    <label><?php echo esc_html__('Nome', 'fp-cartrecovery'); ?></label>
                                    <input type="text" name="from_name" value="<?php echo esc_attr($data['from_name'] ?? ''); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                                </div>
                                <div class="fpcartrecovery-field">
                                    <label><?php echo esc_html__('Email', 'fp-cartrecovery'); ?></label>
                                    <input type="email" name="from_email" value="<?php echo esc_attr($data['from_email'] ?? ''); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                                </div>
                                <div class="fpcartrecovery-field fpcartrecovery-wp-mail-only"<?php echo ($data['email_provider'] ?? 'wp') !== 'wp' ? ' style="display:none"' : ''; ?>>
                                    <label><?php echo esc_html__('Reply-To', 'fp-cartrecovery'); ?></label>
                                    <input type="email" name="reply_to_email" value="<?php echo esc_attr($data['reply_to_email'] ?? ''); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                                </div>
                            </div>
                        </div>

                        <details class="fpcartrecovery-placeholders-box">
                            <summary><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__('Placeholder disponibili', 'fp-cartrecovery'); ?></summary>
                            <div class="fpcartrecovery-placeholders-grid">
                                <div class="fpcartrecovery-placeholder-item"><code>{{recovery_link}}</code><span><?php echo esc_html__('URL recupero carrello', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{cart_total}}</code><span><?php echo esc_html__('Totale formattato', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{shop_name}}</code><span><?php echo esc_html__('Nome sito', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{customer_name}}</code><span><?php echo esc_html__('Nome utente', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{cart_items}}</code><span><?php echo esc_html__('Lista prodotti HTML', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{reminder_number}}</code><span><?php echo esc_html__('1, 2 o 3', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{unsubscribe_url}}</code><span><?php echo esc_html__('Link disiscrizione', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{logo_html}}</code><span><?php echo esc_html__('Logo', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{primary_color}}</code><span><?php echo esc_html__('Colore primario', 'fp-cartrecovery'); ?></span></div>
                                <div class="fpcartrecovery-placeholder-item"><code>{{accent_color}}</code><span><?php echo esc_html__('Colore accent', 'fp-cartrecovery'); ?></span></div>
                            </div>
                        </details>

                        <div class="fpcartrecovery-email-section fpcartrecovery-email-templates">
                            <h3 class="fpcartrecovery-email-section-title"><span class="dashicons dashicons-edit"></span> <?php echo esc_html__('Template email', 'fp-cartrecovery'); ?></h3>
                            <div class="fpcartrecovery-email-tabs">
                                <div class="fpcartrecovery-email-tab fpcartrecovery-email-tab-active">
                                    <h4><?php echo esc_html__('1ª email', 'fp-cartrecovery'); ?></h4>
                                    <div class="fpcartrecovery-field">
                                        <label><?php echo esc_html__('Oggetto', 'fp-cartrecovery'); ?></label>
                                        <input type="text" name="email_subject" value="<?php echo esc_attr($disp_email_subject); ?>" class="regular-text">
                                        <span class="fpcartrecovery-hint"><?php echo esc_html__('Testo predefinito del plugin; modifica e salva per personalizzare.', 'fp-cartrecovery'); ?></span>
                                    </div>
                                    <div class="fpcartrecovery-field">
                                        <label><?php echo esc_html__('Corpo (HTML)', 'fp-cartrecovery'); ?></label>
                                        <textarea name="email_body" rows="12" class="large-text code"><?php echo esc_textarea($disp_email_body); ?></textarea>
                                    </div>
                                </div>
                                <div class="fpcartrecovery-email-tab">
                                    <h4><?php echo esc_html__('2ª email', 'fp-cartrecovery'); ?></h4>
                                    <div class="fpcartrecovery-field">
                                        <label><?php echo esc_html__('Oggetto', 'fp-cartrecovery'); ?></label>
                                        <input type="text" name="email_subject_2" value="<?php echo esc_attr($disp_email_subject_2); ?>" class="regular-text">
                                        <span class="fpcartrecovery-hint"><?php echo esc_html__('Se salvi oggetto e corpo vuoti, verranno riusati quelli della 1ª.', 'fp-cartrecovery'); ?></span>
                                    </div>
                                    <div class="fpcartrecovery-field">
                                        <label><?php echo esc_html__('Corpo (HTML)', 'fp-cartrecovery'); ?></label>
                                        <textarea name="email_body_2" rows="12" class="large-text code"><?php echo esc_textarea($disp_email_body_2); ?></textarea>
                                    </div>
                                </div>
                                <div class="fpcartrecovery-email-tab fpcartrecovery-third-email"<?php echo empty($data['third_reminder_enabled']) ? ' style="display:none"' : ''; ?>>
                                    <h4><?php echo esc_html__('3ª email', 'fp-cartrecovery'); ?></h4>
                                    <div class="fpcartrecovery-field">
                                        <label><?php echo esc_html__('Oggetto', 'fp-cartrecovery'); ?></label>
                                        <input type="text" name="email_subject_3" value="<?php echo esc_attr($disp_email_subject_3); ?>" class="regular-text">
                                        <span class="fpcartrecovery-hint"><?php echo esc_html__('Se salvi vuoto, verranno riusati oggetto e corpo della 2ª (o della 1ª in cascata).', 'fp-cartrecovery'); ?></span>
                                    </div>
                                    <div class="fpcartrecovery-field">
                                        <label><?php echo esc_html__('Corpo (HTML)', 'fp-cartrecovery'); ?></label>
                                        <textarea name="email_body_3" rows="12" class="large-text code"><?php echo esc_textarea($disp_email_body_3); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fpcartrecovery-email-section fpcartrecovery-email-extras">
                            <h3 class="fpcartrecovery-email-section-title"><span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html__('Altro', 'fp-cartrecovery'); ?></h3>
                            <div class="fpcartrecovery-field">
                                <label><?php echo esc_html__('Pagina dopo disiscrizione', 'fp-cartrecovery'); ?></label>
                                <?php
                                wp_dropdown_pages([
                                    'name' => 'unsubscribe_page_id',
                                    'selected' => (int) ($data['unsubscribe_page_id'] ?? 0),
                                    'show_option_none' => __('— Homepage', 'fp-cartrecovery'),
                                    'option_none_value' => '0',
                                ]);
                                ?>
                            </div>
                        </div>

                        <div class="fpcartrecovery-fields-grid fpcartrecovery-exclude-section" style="margin-top:20px;padding-top:20px;border-top:1px solid var(--fpdms-border-light);">
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Escludi prodotti (ID)', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="exclude_product_ids" value="<?php echo esc_attr(is_array($data['exclude_product_ids'] ?? null) ? implode(', ', array_map('absint', $data['exclude_product_ids'])) : ($data['exclude_product_ids'] ?? '')); ?>" placeholder="123, 456">
                            </div>
                            <div class="fpcartrecovery-field fpcartrecovery-field-full">
                                <label><?php echo esc_html__('Escludi categorie (ID)', 'fp-cartrecovery'); ?></label>
                                <input type="text" name="exclude_category_ids" value="<?php echo esc_attr(is_array($data['exclude_category_ids'] ?? null) ? implode(', ', array_map('absint', $data['exclude_category_ids'])) : ($data['exclude_category_ids'] ?? '')); ?>" placeholder="5, 12">
                            </div>
                        </div>
                    </div>
                </div>

                <p>
                    <button type="submit" class="fpcartrecovery-btn fpcartrecovery-btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo esc_html__('Salva impostazioni', 'fp-cartrecovery'); ?>
                    </button>
                    <button type="button" id="fpcartrecovery-send-test-email" class="fpcartrecovery-btn fpcartrecovery-btn-secondary" style="margin-left:8px;">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php echo esc_html__('Invia email di prova', 'fp-cartrecovery'); ?>
                    </button>
                    <button type="button" id="fpcartrecovery-preview-email" class="fpcartrecovery-btn fpcartrecovery-btn-secondary" style="margin-left:8px;" title="<?php echo esc_attr__('Usa le impostazioni salvate. Salva prima per vedere le modifiche.', 'fp-cartrecovery'); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo esc_html__('Anteprima email', 'fp-cartrecovery'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    private function save(): void {
        $enabled = !empty($_POST['enabled']);
        $emails_enabled = $enabled && !empty($_POST['emails_enabled']);
        $track_guests = !empty($_POST['track_guests']);
        $email_provider = in_array($_POST['email_provider'] ?? '', ['wp', 'brevo'], true) ? $_POST['email_provider'] : 'wp';
        $abandon_min = max(0, min(1440, absint($_POST['abandon_after_minutes'] ?? 30)));
        $min_cart_value = max(0.0, (float) ($_POST['min_cart_value'] ?? 0));
        $cleanup_days = max(0, min(365, absint($_POST['cleanup_after_days'] ?? 90)));
        $cron_interval = in_array($_POST['cron_interval'] ?? '', ['fp_cartrecovery_15min', 'fp_cartrecovery_30min', 'hourly', 'twicedaily', 'daily'], true)
            ? $_POST['cron_interval'] : 'hourly';
        $first_hours = max(1, min(72, absint($_POST['first_reminder_hours'] ?? 2)));
        $second_hours = max(1, min(168, absint($_POST['second_reminder_hours'] ?? 24)));
        $third_enabled = !empty($_POST['third_reminder_enabled']);
        $third_hours = max(1, min(336, absint($_POST['third_reminder_hours'] ?? 72)));
        $expiry_days = max(0, min(365, absint($_POST['recovery_link_expiry_days'] ?? 0)));
        $unsubscribe_page_id = max(0, absint($_POST['unsubscribe_page_id'] ?? 0));
        $exclude_products = array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_POST['exclude_product_ids'] ?? '')))));
        $exclude_cats = array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_POST['exclude_category_ids'] ?? '')))));
        $email_subject = sanitize_text_field(wp_unslash($_POST['email_subject'] ?? ''));
        $email_body = wp_kses_post(wp_unslash($_POST['email_body'] ?? ''));
        $email_subject_2 = sanitize_text_field(wp_unslash($_POST['email_subject_2'] ?? ''));
        $email_body_2 = wp_kses_post(wp_unslash($_POST['email_body_2'] ?? ''));
        $email_subject_3 = sanitize_text_field(wp_unslash($_POST['email_subject_3'] ?? ''));
        $email_body_3 = wp_kses_post(wp_unslash($_POST['email_body_3'] ?? ''));
        $from_name = sanitize_text_field(wp_unslash($_POST['from_name'] ?? ''));
        $from_email = sanitize_email(wp_unslash($_POST['from_email'] ?? ''));
        $reply_to = sanitize_email(wp_unslash($_POST['reply_to_email'] ?? ''));
        $logo_url = esc_url_raw(wp_unslash($_POST['email_logo_url'] ?? ''));
        $primary_color = ColorHelper::sanitize_hex(sanitize_text_field(wp_unslash($_POST['email_primary_color'] ?? '#667eea')));
        $accent_color = ColorHelper::sanitize_hex(sanitize_text_field(wp_unslash($_POST['email_accent_color'] ?? '#764ba2')));

        $old_cron = $this->settings->get('cron_interval', 'hourly');
        $this->settings->save([
            'enabled'                  => $enabled,
            'emails_enabled'           => $emails_enabled,
            'track_guests'             => $track_guests,
            'email_provider'           => $email_provider,
            'abandon_after_minutes'    => $abandon_min,
            'min_cart_value'           => $min_cart_value,
            'cleanup_after_days'       => $cleanup_days,
            'cron_interval'            => $cron_interval,
            'third_reminder_enabled'   => $third_enabled,
            'third_reminder_hours'     => $third_hours,
            'first_reminder_hours'     => $first_hours,
            'second_reminder_hours'    => $second_hours,
            'recovery_link_expiry_days'=> $expiry_days,
            'unsubscribe_page_id'      => $unsubscribe_page_id,
            'exclude_product_ids'      => $exclude_products,
            'exclude_category_ids'     => $exclude_cats,
            'email_subject_3'          => $email_subject_3,
            'email_body_3'             => $email_body_3,
            'email_subject'         => $email_subject,
            'email_body'            => $email_body,
            'email_subject_2'       => $email_subject_2,
            'email_body_2'          => $email_body_2,
            'from_name'             => $from_name,
            'from_email'            => $from_email,
            'reply_to_email'        => $reply_to,
            'email_logo_url'        => $logo_url,
            'email_primary_color'   => $primary_color,
            'email_accent_color'    => $accent_color,
        ]);

        if ($cron_interval !== $old_cron) {
            wp_clear_scheduled_hook(\FP\CartRecovery\Integrations\EmailScheduler::CRON_HOOK);
        }
        if (!$emails_enabled) {
            wp_clear_scheduled_hook(\FP\CartRecovery\Integrations\EmailScheduler::CRON_HOOK);
        } elseif ($emails_enabled) {
            (new \FP\CartRecovery\Integrations\EmailScheduler($this->settings))->ensure_scheduled();
        }

        add_settings_error(
            'fp_cartrecovery',
            'saved',
            __('Impostazioni salvate.', 'fp-cartrecovery'),
            'success'
        );
    }
}
