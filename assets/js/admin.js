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
            const url = $(this).data('url');
            if (!url) return;

            navigator.clipboard.writeText(url).then(function () {
                const $btn = $(this);
                const orig = $btn.text();
                $btn.text(fpCartRecoveryConfig?.i18n?.copied || 'Copiato!');
                setTimeout(function () {
                    $btn.text(orig);
                }, 2000);
            }.bind(this)).catch(function () {
                const $input = $('<input>').val(url).appendTo('body').select();
                document.execCommand('copy');
                $input.remove();
            });
        });
    });
})(jQuery);
