/**
 * FP Cart Recovery — Vista carrelli attivi (polling REST).
 */
(function () {
    'use strict';

    const cfg = window.fpCartRecoveryLive;
    if (!cfg || !cfg.restUrl) {
        return;
    }

    let pollTimer = null;
    let inFlight = false;

    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getMinutes() {
        const sel = document.getElementById('fpcartrecovery-live-window');
        const v = sel ? parseInt(sel.value, 10) : 15;
        return Number.isFinite(v) ? v : 15;
    }

    function setLoading(on) {
        const el = document.getElementById('fpcartrecovery-live-loading');
        if (el) {
            el.hidden = !on;
        }
    }

    function setLastMessage(text) {
        const el = document.getElementById('fpcartrecovery-live-last');
        if (el) {
            el.textContent = text;
        }
    }

    function buildRow(c) {
        const emailOrUser = c.email
            ? escapeHtml(c.email)
            : (c.user_label ? escapeHtml(c.user_label) : '—');
        const summary = c.item_summary ? escapeHtml(c.item_summary) : '—';
        const total = escapeHtml(c.formatted_total || '');
        const updated = escapeHtml(c.updated_human || '');
        const lines = typeof c.lines === 'number' ? String(c.lines) : '0';
        const reminders = String(c.reminder_sent ?? 0);

        let actions = '';
        if (c.recovery_url) {
            actions +=
                '<button type="button" class="fpcartrecovery-btn fpcartrecovery-btn-secondary fpcartrecovery-copy-link" data-url="' +
                escapeHtml(c.recovery_url) +
                '">' +
                escapeHtml(cfg.i18n.copyLink) +
                '</button>';
        }
        if (c.email) {
            actions +=
                '<button type="button" class="fpcartrecovery-btn fpcartrecovery-btn-secondary fpcartrecovery-send-reminder" data-id="' +
                escapeHtml(String(c.id)) +
                '">' +
                escapeHtml(cfg.i18n.sendEmail) +
                '</button>';
        }
        actions +=
            '<button type="button" class="fpcartrecovery-btn fpcartrecovery-btn-secondary fpcartrecovery-delete-cart" data-id="' +
            escapeHtml(String(c.id)) +
            '">' +
            escapeHtml(cfg.i18n.delete) +
            '</button>';

        return (
            '<tr class="status-abandoned">' +
            '<td>' + emailOrUser + '</td>' +
            '<td>' + lines + '</td>' +
            '<td class="fpcartrecovery-live-summary">' + summary + '</td>' +
            '<td>' + total + '</td>' +
            '<td>' + updated + '</td>' +
            '<td>' + reminders + '</td>' +
            '<td class="fpcartrecovery-actions-cell">' + actions + '</td>' +
            '</tr>'
        );
    }

    function render(carts) {
        const tbody = document.getElementById('fpcartrecovery-live-tbody');
        if (!tbody) {
            return;
        }
        if (!carts || !carts.length) {
            tbody.innerHTML =
                '<tr><td colspan="7" class="fpcartrecovery-live-empty">' +
                escapeHtml(cfg.i18n.empty) +
                '</td></tr>';
            return;
        }
        tbody.innerHTML = carts.map(buildRow).join('');
    }

    function fetchCarts() {
        if (inFlight) {
            return;
        }
        inFlight = true;
        setLoading(true);

        const minutes = getMinutes();
        const url =
            cfg.restUrl +
            (cfg.restUrl.indexOf('?') === -1 ? '?' : '&') +
            'minutes=' +
            encodeURIComponent(String(minutes)) +
            '&limit=40';

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': cfg.restNonce || '',
                Accept: 'application/json',
            },
        })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }
                return r.json();
            })
            .then(function (data) {
                render(data.carts || []);
                const refreshed = data.refreshed_at || '';
                setLastMessage(
                    refreshed ? cfg.i18n.lastSync + ' ' + refreshed : cfg.i18n.lastSyncNow
                );
            })
            .catch(function () {
                setLastMessage(cfg.i18n.error);
            })
            .finally(function () {
                inFlight = false;
                setLoading(false);
            });
    }

    function schedule() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        fetchCarts();
        pollTimer = setInterval(fetchCarts, cfg.pollMs || 8000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('fpcartrecovery-live-tbody');
        if (!tbody) {
            return;
        }
        schedule();
        const win = document.getElementById('fpcartrecovery-live-window');
        if (win) {
            win.addEventListener('change', function () {
                schedule();
            });
        }
    });
})();
