# Changelog

## [1.0.0] - 2025-03-23

### Added

- Tracciamento carrelli WooCommerce (utenti loggati e guest)
- Salvataggio carrelli abbandonati in tabella `wp_fp_cartrecovery_carts`
- Cattura email guest al checkout (form e AJAX update)
- Cron orario per invio email di richiamo (prima dopo 2h, seconda dopo 24h)
- Link di recovery con token univoco per ripristinare il carrello
- Dashboard admin con statistiche e elenco carrelli
- Pagina Impostazioni (attiva/disattiva, tempistiche, template email)
- Template email HTML con design FP
- Integrazione `fp_tracking_event` su recupero carrello
- Marcatura automatica come "recovered" al completamento ordine
