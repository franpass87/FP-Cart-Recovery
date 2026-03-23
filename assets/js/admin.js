/**
 * FP Cart Recovery — Admin JS
 */
(function ($) {
    'use strict';

    $(function () {
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
