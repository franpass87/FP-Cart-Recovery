<?php
/**
 * Smoke test FP Cart Recovery
 *
 * Eseguire: php tests/smoke-test.php (dalla root del plugin)
 *
 * Verifica: classi caricabili, ColorHelper, template, CRON_HOOK.
 * Per test in contesto WordPress: wp eval-file tests/smoke-test.php
 */
declare(strict_types=1);

$plugin_dir = dirname(__DIR__);
$autoload = $plugin_dir . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    echo "FAIL: vendor/autoload.php non trovato\n";
    exit(1);
}

require_once $autoload;

// Simula costanti WP se non in contesto WP
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}
if (!defined('FP_CARTRECOVERY_DIR')) {
    define('FP_CARTRECOVERY_DIR', $plugin_dir . '/');
}

echo "=== FP Cart Recovery Smoke Test ===\n\n";

$errors = 0;

// 1. Classi caricabili
$classes = [
    'FP\\CartRecovery\\Core\\Plugin',
    'FP\\CartRecovery\\Core\\Migrations',
    'FP\\CartRecovery\\Domain\\Settings',
    'FP\\CartRecovery\\Domain\\AbandonedCartRepository',
    'FP\\CartRecovery\\Integrations\\CartTracker',
    'FP\\CartRecovery\\Integrations\\EmailScheduler',
    'FP\\CartRecovery\\Integrations\\RecoveryHandler',
    'FP\\CartRecovery\\Integrations\\UnsubscribeHandler',
    'FP\\CartRecovery\\Integrations\\CleanupCron',
    'FP\\CartRecovery\\Admin\\AdminAjax',
    'FP\\CartRecovery\\Admin\\AdminMenu',
    'FP\\CartRecovery\\Admin\\SettingsPage',
    'FP\\CartRecovery\\Admin\\DashboardPage',
    'FP\\CartRecovery\\Admin\\HelpPage',
    'FP\\CartRecovery\\Rest\\StatsController',
    'FP\\CartRecovery\\Utils\\ColorHelper',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "OK  Class exists: $class\n";
    } else {
        echo "FAIL Class missing: $class\n";
        $errors++;
    }
}

// 2. ColorHelper
if (class_exists('FP\\CartRecovery\\Utils\\ColorHelper')) {
    $c = \FP\CartRecovery\Utils\ColorHelper::sanitize_hex('#667eea');
    $c2 = \FP\CartRecovery\Utils\ColorHelper::sanitize_hex('invalid');
    if ($c === '#667eea' && $c2 === '#667eea') {
        echo "OK  ColorHelper::sanitize_hex\n";
    } else {
        echo "FAIL ColorHelper output\n";
        $errors++;
    }
}

// 3. Template esistente
$template = $plugin_dir . '/templates/emails/cart-recovery.php';
if (file_exists($template)) {
    echo "OK  Template email esiste\n";
} else {
    echo "FAIL Template mancante\n";
    $errors++;
}

// 4. EmailScheduler + CleanupCron constants
$hook = \FP\CartRecovery\Integrations\EmailScheduler::CRON_HOOK;
if ($hook === 'fp_cartrecovery_send_reminders') {
    echo "OK  EmailScheduler::CRON_HOOK\n";
} else {
    echo "FAIL CRON_HOOK value\n";
    $errors++;
}
$cleanup = \FP\CartRecovery\Integrations\CleanupCron::CRON_HOOK;
if ($cleanup === 'fp_cartrecovery_cleanup_old_carts') {
    echo "OK  CleanupCron::CRON_HOOK\n";
} else {
    echo "FAIL CleanupCron::CRON_HOOK\n";
    $errors++;
}

echo "\n--- Risultato: " . ($errors === 0 ? "PASS" : "FAIL ($errors errori)") . " ---\n";
exit($errors > 0 ? 1 : 0);
