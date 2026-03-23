# Changelog

## [1.0.3] - 2025-03-23

### Changed

- **Centralizzazione**: il recupero carrelli ora include anche FP Experiences (esperienze e gift voucher). Email, tracking e recovery link gestiti tutti da FP Cart Recovery.
- Ripristino carrello: supporto per cart_item_data custom (fp_exp_tickets, fp_exp_slot_id, gift_voucher, ecc.) per ripristinare correttamente i carrelli FP Experiences.

## [1.0.2] - 2025-03-23

### Added

- Invio evento `cart_abandoned` a FP Tracking (`fp_tracking_event`) quando un carrello viene salvato come abbandonato (value, currency, items GA4).

## [1.0.1] - 2025-03-23

### Fixed

- (Sostituito in 1.0.3) Esclusione FP Experiences dalle email — ora centralizzato, vedi 1.0.3.

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
