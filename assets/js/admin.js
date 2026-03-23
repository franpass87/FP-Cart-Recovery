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
                    $('#fpcartrecovery-logo-url').val(attachment.url || '');
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
    });
})(jQuery);
