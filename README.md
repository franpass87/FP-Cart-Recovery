# FP Cart Recovery

Plugin WordPress per il **recupero carrelli abbandonati** WooCommerce in stile FP.

## Funzionalità

- **Tracciamento**: salva automaticamente i carrelli con prodotti (utenti loggati e guest), incluse esperienze e gift voucher FP Experiences
- **Email di richiamo**: invio automatico di reminder dopo 2h e 24h (configurabili)
- **Link di recovery**: URL univoco per ripristinare il carrello con un click
- **Dashboard admin**: statistiche e elenco carrelli abbandonati/recuperati
- **Integrazione FP Tracking**: eventi `cart_abandoned` (al salvataggio carrello) e `cart_recovery` (al recupero)

## Requisiti

- WordPress 6.0+
- PHP 8.0+
- WooCommerce attivo

## Installazione

1. Carica la cartella del plugin in `wp-content/plugins/`
2. Esegui `composer install` nella cartella del plugin
3. Attiva da **Plugin** nel pannello WordPress

## Utilizzo

1. Vai in **FP Cart Recovery** nel menu admin
2. Clicca **Impostazioni** e attiva il recupero
3. Configura tempistiche (prima/seconda email) e template email
4. I carrelli verranno tracciati automaticamente

## Hook e filtri

| Hook / Filtro | Descrizione |
|---------------|-------------|
| `fp_cartrecovery_cart_abandoned` | Eseguito quando un carrello viene salvato come abbandonato |
| `fp_cartrecovery_cart_recovered` | Eseguito quando un carrello viene recuperato tramite link |
| `fp_tracking_event` (cart_abandoned) | Emesso con value, currency, items (GA4) quando il carrello viene salvato come abbandonato |
| `fp_tracking_event` (cart_recovery) | Emesso con value, currency quando il carrello viene recuperato tramite link |
| `fp_tracking_event` (cart_recovery_email_sent) | Emesso con value, currency, email, cart_id quando viene inviata email tramite Brevo |
| `fp_cartrecovery_email_subject` | Filtra l'oggetto email |
| `fp_cartrecovery_email_body` | Filtra il corpo email |

## Placeholder email

- `{{recovery_link}}` — URL per ripristinare il carrello
- `{{cart_total}}` — Totale formattato (es. €29,90)
- `{{shop_name}}` — Nome del sito
- `{{customer_name}}` — Nome utente o "Cliente"
- `{{cart_items}}` — Lista HTML prodotti nel carrello
- `{{reminder_number}}` — 1 o 2 (prima/seconda email)
- `{{logo_html}}` — Immagine logo (se configurata)
- `{{primary_color}}` — Colore primario (es. #667eea)
- `{{accent_color}}` — Colore accent (es. #764ba2)

## Tempistiche e scadenza

- **Minuti abbandono**: minimo inattività prima di considerare il carrello abbandonato per le email
- **Scadenza link**: giorni di validità del link recovery (0 = mai)

## Personalizzazione email

- **1ª e 2ª email** separabili: oggetto e corpo personalizzabili per ogni reminder
- **Mittente**: nome e email configurabili
- **Logo**: URL immagine nell'header (Media Library)
- **Colori**: primario e accent per header, bottone e link
- Template default include `{{customer_name}}`, `{{cart_items}}`, logo e colori configurabili

## Autore

**Francesco Passeri**
- Sito: [francescopasseri.com](https://francescopasseri.com)
- Email: [info@francescopasseri.com](mailto:info@francescopasseri.com)
- GitHub: [github.com/franpass87](https://github.com/franpass87)
