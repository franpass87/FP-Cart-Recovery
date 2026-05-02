# Changelog

## [1.2.6] - 2026-05-02

### Added

- **Carrelli attivi** (sottomenu admin): tabella con aggiornamento automatico ogni 8 secondi tramite `GET /fp-cart-recovery/v1/active-carts` (finestra 5–60 minuti). Usa gli stessi record già salvati da `CartTracker` (nessuna modifica al flusso di tracciamento).
- Elenco REST con anteprima righe, totale formattato e azioni Copia link / Invia email / Elimina (stessi handler AJAX della dashboard).

### Changed

- Admin JS: handler delegati per copia link, invio reminder ed eliminazione carrello (compatibile con righe inserite dinamicamente dalla vista live).

## [1.2.5] - 2026-04-04

### Changed

- Eventi `cart_recovery` e `cart_recovery_email_sent`: aggiunti `cart_id` ed `event_id` per allineamento al catalogo FP Marketing Tracking Layer.

## [1.2.4] - 2026-03-24

### Changed

- Invio reminder via Brevo (`/v3/smtp/email`): prima del POST il payload passa da `fp_tracking_brevo_merge_transactional_tags()` se FP Marketing Tracking Layer espone la funzione (allineamento tag sito per log FP Mail SMTP / filtri API).

## [1.2.3] - 2026-03-24

### Changed

- Email reminder: dopo i filtri `fp_cartrecovery_email_*`, il corpo HTML frammento passa da **FP Mail SMTP** (`fp_fpmail_brand_html`) se attivo; template già documento completo (`<!DOCTYPE` / `<html>`) non vengono ri-avvolti. Vale sia per `wp_mail` sia per invio Brevo.

## [1.2.1] - 2025-03-23

### Changed

- **Estetica sezione Email** nelle Impostazioni: sezioni separate (Invio, Aspetto, Mittente, Template), pill per provider, anteprima gradiente colori, color picker nativo, placeholder in griglia, tab per 1ª/2ª/3ª email.

## [1.2.0] - 2025-03-23

### Added

- **Soglia minimo carrello** (`min_cart_value`): non inviare email se il totale è sotto la soglia
- **Pulizia automatica** carrelli abbandonati più vecchi di X giorni (`cleanup_after_days`)
- **Invio manuale** da dashboard: pulsante "Invia email" per singolo carrello
- **Link unsubscribe** nelle email (`{{unsubscribe_url}}`) con verifica token HMAC
- **Esclusione prodotti/categorie**: ID da escludere dal tracciamento
- **Frequenza cron** configurabile (15min, 30min, hourly, twicedaily, daily)
- **Export CSV** dalla dashboard
- **Statistiche avanzate**: tasso conversione, filtro temporale (7/30/90 giorni)
- **Terza email** reminder opzionale
- **REST API**: `GET /fp-cart-recovery/v1/stats?days=30`
- **Pagina Guida** nel menu con riepilogo placeholder e API
- Pagina destinazione per unsubscribe configurabile

### Changed

- Cron `ensure_scheduled` supporta intervalli personalizzati
- `AbandonedCartRepository::get_stats()` accetta parametro `days` per filtro temporale
- `get_paginated()` accetta parametro `days` per filtro lista

## [1.1.1] - 2025-03-23

### Added

- **Reply-To** configurabile per wp_mail (campo opzionale, visibile solo con provider WordPress)
- **Anteprima email**: pulsante che apre nuova finestra con HTML renderizzato e placeholder sostituiti
- Suggerimento wp_mail su plugin SMTP per deliverability
- Toggle visività campi wp_mail quando si cambia provider

## [1.1.0] - 2025-03-23

### Added

- **abandon_after_minutes**: filtra carrelli per invio email (min. inattività)
- **Scadenza link recovery**: `recovery_link_expiry_days` (0 = mai)
- **Eliminazione carrelli** dalla dashboard (pulsante con conferma)
- **Email di prova** nelle impostazioni
- Colonna **Reminder inviati** nella tabella carrelli
- Helper `ColorHelper::sanitize_hex()` centralizzato

### Changed

- Cron `ensure_scheduled` su `admin_init` (riduce carico frontend)
- Scheduling cron anche su attivazione plugin
- Fix binding copia link in admin.js

## [1.0.6] - 2025-03-23

### Added

- **Logo email**: URL configurabile (Media Library) mostrato nell'header
- **Colori branding**: primario e accent per header, bottone e link
- Placeholder `{{logo_html}}`, `{{primary_color}}`, `{{accent_color}}` per template custom

## [1.0.5] - 2025-03-23

### Added

- **Personalizzazione email**: oggetto e corpo distinti per 1ª e 2ª reminder
- Nuovi placeholder: `{{customer_name}}`, `{{cart_items}}`, `{{reminder_number}}`
- Campo **Email mittente** configurabile
- Sezione "Placeholder disponibili" in impostazioni con tabella di riferimento

## [1.0.4] - 2025-03-23

### Added

- Opzione **Invio email**: WordPress (wp_mail) o Brevo (API + evento FP Tracking).
- Con Brevo: usa impostazioni centralizzate da FP Marketing Tracking Layer; dopo l'invio emette `fp_tracking_event` (`cart_recovery_email_sent`) con value, currency, email, cart_id.
- Fallback automatico a wp_mail se Brevo non configurato o in errore.

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
