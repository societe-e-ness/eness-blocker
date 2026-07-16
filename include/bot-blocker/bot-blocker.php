<?php
/**
 * Bot Blocker - Protection contre les bots malveillants
 *
 * Cette fonctionnalité surveille et bloque automatiquement les bots qui accèdent
 * trop fréquemment au site en les ajoutant dans le .htaccess
 *
 * @since 1.1.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Charge les fichiers du bot blocker
require_once __DIR__ . '/bot-blocker-settings.php';
require_once __DIR__ . '/bot-blocker-core.php';

/**
 * Ajoute un intervalle de 5 minutes pour le cron WordPress
 *
 * @since 1.2.4
 * @param array $schedules Intervalles existants
 * @return array Intervalles avec le nouvel intervalle ajouté
 */
function eness_bot_blocker_add_cron_interval($schedules) {
    $schedules['eness_bot_blocker_5min'] = array(
        'interval' => 300, // 5 minutes en secondes
        'display'  => __('Toutes les 5 minutes (Bot Blocker)', 'eness-blocker')
    );
    return $schedules;
}
add_filter('cron_schedules', 'eness_bot_blocker_add_cron_interval');

/**
 * S'assure que le cron de nettoyage est bien planifié
 * Vérifie à chaque init si le cron existe, sinon le planifie
 *
 * @since 1.2.4
 * @return void
 */
function eness_bot_blocker_ensure_cron_scheduled() {
    if (!wp_next_scheduled('eness_bot_blocker_cleanup_cron')) {
        wp_schedule_event(time(), 'eness_bot_blocker_5min', 'eness_bot_blocker_cleanup_cron');
    }
}
add_action('init', 'eness_bot_blocker_ensure_cron_scheduled');

// Migration automatique des paramètres lors des changements de defaults
add_action('init', 'eness_bot_blocker_auto_migrate_settings', 1);

// Nettoyage automatique des bots bloqués dont le transient a expiré (fonction dans bot-blocker-core.php)
add_action('eness_bot_blocker_cleanup_cron', 'eness_bot_blocker_cleanup_expired_blocks');

// Charge l'admin uniquement en back-office
if (is_admin()) {
    require_once __DIR__ . '/bot-blocker-admin.php';
}
