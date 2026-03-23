/**
 * FP Cart Recovery — Admin JS
 */
(function ($) {
    'use strict';

    $(function () {
        // Logo upload (solo su pagina Impostazioni, richiede wp.media)
        if ($('#fpcartrecovery-logo-upload').length && typeof wp !== 'undefined' && wp.media) {
            let mediaFrame;
            $('#fpcartrecovery-logo-upload').on('click', function () {
                if (mediaFrame) {
                    mediaFrame.open();
                    return;
                }
                mediaFrame = wp.media({
                    title: fpCartRecoveryConfig?.i18n?.selectLogo || 'Seleziona logo',
                    button: { text: fpCartRecoveryConfig?.i18n?.useImage || 'Usa questa immagine' },
                    library: { type: 'image' },
                    multiple: false
                });
                mediaFrame.on('select', function () {
                    const attachment = mediaFrame.state().get('selection').first().toJSON();
                    const url = attachment.url || '';
                    $('#fpcartrecovery-logo-url').val(url);
                    let $prev = $('.fpcartrecovery-logo-preview');
                    if (url) {
                        if (!$prev.length) $prev = $('<div class="fpcartrecovery-logo-preview"></div>').prependTo('.fpcartrecovery-logo-picker');
                        $prev.html('<img src="' + url + '" alt="Logo" />').show();
                    } else if ($prev.length) $prev.hide();
                });
                mediaFrame.open();
            });
        }

        $('.fpcartrecovery-copy-link').on('click', function () {
            const $btn = $(this);
            const url = $btn.data('url');
            if (!url) return;

            navigator.clipboard.writeText(url).then(function () {
                const orig = $btn.text();
                $btn.text(fpCartRecoveryConfig?.i18n?.copied || 'Copiato!');
                setTimeout(function () { $btn.text(orig); }, 2000);
            }).catch(function () {
                const $input = $('<input>').val(url).appendTo('body').select();
                document.execCommand('copy');
                $input.remove();
            });
        });

        $('.fpcartrecovery-send-reminder').on('click', function () {
            const $btn = $(this);
            const id = $btn.data('id');
            if (!id) return;
            $btn.prop('disabled', true);
            $.post(fpCartRecoveryConfig.ajaxUrl, {
                action: 'fp_cartrecovery_send_reminder',
                nonce: fpCartRecoveryConfig.nonce,
                id: id
            }).done(function (r) {
                if (r.success) {
                    alert(r.data?.message || 'Email inviata.');
                    location.reload();
                } else {
                    alert(r.data?.message || 'Errore invio.');
                }
            }).fail(function () {
                alert('Errore di connessione.');
            }).always(function () { $btn.prop('disabled', false); });
        });

        $('.fpcartrecovery-delete-cart').on('click', function () {
            const $btn = $(this);
            const id = $btn.data('id');
            if (!id || !confirm(fpCartRecoveryConfig?.i18n?.confirmDelete || 'Eliminare?')) return;

            $btn.prop('disabled', true);
            $.post(fpCartRecoveryConfig.ajaxUrl, {
                action: 'fp_cartrecovery_delete_cart',
                nonce: fpCartRecoveryConfig.nonce,
                id: id
            }).done(function (r) {
                if (r.success) {
                    $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                }
            }).always(function () { $btn.prop('disabled', false); });
        });

        $('#fpcartrecovery-send-test-email').on('click', function () {
            const $btn = $(this);
            $btn.prop('disabled', true);
            $.post(fpCartRecoveryConfig.ajaxUrl, {
                action: 'fp_cartrecovery_send_test_email',
                nonce: fpCartRecoveryConfig.nonce
            }).done(function (r) {
                if (r.success) {
                    alert(fpCartRecoveryConfig?.i18n?.testEmailSent || 'Email inviata!');
                } else {
                    alert(r.data?.message || 'Errore invio.');
                }
            }).fail(function () {
                alert('Errore di connessione.');
            }).always(function () { $btn.prop('disabled', false); });
        });

        // Toggle campi wp_mail quando cambia provider
        $('input[name="email_provider"]').on('change', function () {
            const isWp = $(this).val() === 'wp';
            $('.fpcartrecovery-wp-mail-only').toggle(isWp);
        });

        // Toggle campi 3ª email
        $('input[name="third_reminder_enabled"]').on('change', function () {
            $('.fpcartrecovery-third-email').toggle($(this).is(':checked'));
        });

        // Sincronizza color picker con input hex e anteprima
        function syncColorPicker(pickerId, textId, varName) {
            const $picker = $('#' + pickerId);
            const $text = $('#' + textId);
            const $preview = $('.fpcartrecovery-branding-preview');
            if (!$picker.length || !$text.length) return;
            function updatePreview(val) {
                if ($preview.length && val) $preview.css(varName, val);
            }
            $picker.on('input', function () {
                const v = $(this).val();
                $text.val(v);
                updatePreview(v);
            });
            $text.on('input', function () {
                const v = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(v)) {
                    $picker.val(v);
                    updatePreview(v);
                }
            });
        }
        syncColorPicker('fpcartrecovery-primary-color-picker', 'fpcartrecovery-primary-color', '--preview-primary');
        syncColorPicker('fpcartrecovery-accent-color-picker', 'fpcartrecovery-accent-color', '--preview-accent');

        // Anteprima email
        $('#fpcartrecovery-preview-email').on('click', function () {
            const $btn = $(this);
            $btn.prop('disabled', true);
            $.post(fpCartRecoveryConfig.ajaxUrl, {
                action: 'fp_cartrecovery_preview_email',
                nonce: fpCartRecoveryConfig.nonce
            }).done(function (r) {
                if (r.success && r.data && r.data.html) {
                    const w = window.open('', '_blank', 'width=680,height=700,scrollbars=yes');
                    w.document.write('<html><head><meta charset="UTF-8"><title>' + (fpCartRecoveryConfig?.i18n?.previewEmail || 'Anteprima') + '</title></head><body style="margin:0;padding:16px;background:#f5f5f5;">' + r.data.html + '</body></html>');
                    w.document.close();
                } else {
                    alert(r.data?.message || 'Errore anteprima.');
                }
            }).fail(function () {
                alert('Errore di connessione.');
            }).always(function () { $btn.prop('disabled', false); });
        });
    });
})(jQuery);
