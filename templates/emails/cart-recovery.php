<?php
/**
 * Template email recupero carrello.
 *
 * Placeholder sostituiti da EmailScheduler: {{recovery_link}}, {{cart_total}}, {{shop_name}}
 *
 * @package FP\CartRecovery
 */

defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html__('Recupera il tuo carrello', 'fp-cartrecovery'); ?></title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f5f5;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
                <tr>
                    <td style="padding:32px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);text-align:center;">
                        <h1 style="margin:0;color:#fff;font-size:24px;font-weight:700;">
                            <?php echo esc_html__('Hai dimenticato qualcosa?', 'fp-cartrecovery'); ?>
                        </h1>
                        <p style="margin:12px 0 0;color:rgba(255,255,255,0.9);font-size:14px;">
                            <?php echo esc_html(sprintf(__('Ciao %s! Il tuo carrello da %s ti aspetta.', 'fp-cartrecovery'), '{{customer_name}}', '{{cart_total}}')); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;color:#1f2937;font-size:16px;line-height:1.6;">
                            <?php echo esc_html__('Hai aggiunto dei prodotti al carrello ma non hai completato l\'acquisto. Clicca il pulsante qui sotto per tornare al carrello e finalizzare l\'ordine.', 'fp-cartrecovery'); ?>
                        </p>
                        {{cart_items}}
                        <p style="margin:0 0 24px;text-align:center;">
                            <a href="{{recovery_link}}" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;text-decoration:none;font-weight:600;font-size:16px;border-radius:8px;">
                                <?php echo esc_html__('Riprendi l\'acquisto', 'fp-cartrecovery'); ?>
                            </a>
                        </p>
                        <p style="margin:0;color:#6b7280;font-size:12px;">
                            <?php echo esc_html__('Oppure copia questo link nel browser:', 'fp-cartrecovery'); ?>
                            <br>
                            <a href="{{recovery_link}}" style="color:#667eea;word-break:break-all;">{{recovery_link}}</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;">
                        <p style="margin:0;color:#9ca3af;font-size:12px;text-align:center;">
                            {{shop_name}}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
