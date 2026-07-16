<?php
/**
 * e-Ness Blocker WordPress Plugin
 *
 * @package e-Ness Blocker
 *
 * Plugin Name: e-Ness Blocker
 * Description: Protège le site contre les bots et IPs malveillants en limitant leur fréquence d'accès
 * Plugin URI: https://www.eness.fr
 * Version: 1.0.0-rc.1
 * Author: e-Ness
 * Author URI: https://www.eness.fr
 * Text Domain: eness-blocker
 */

if (!defined('ENESS_BLOCKER')) {
   define('ENESS_BLOCKER', plugin_dir_path(__FILE__));
}

// Require plugin files
require_once 'framework/framework.php';

/**
 * Ajout de la fonctionnalité de blocage des bots malveillants
 *
 * @since 1.0.0
 *
 */
require_once 'include/bot-blocker/bot-blocker.php';

/**
 * Activation du plugin : planifie les crons
 *
 * @since 1.0.0
 */
function eness_blocker_activate() {
    // Planifie le cron de nettoyage toutes les 5 minutes
    if (!wp_next_scheduled('eness_bot_blocker_cleanup_cron')) {
        wp_schedule_event(time(), 'eness_bot_blocker_5min', 'eness_bot_blocker_cleanup_cron');
    }
}
register_activation_hook(__FILE__, 'eness_blocker_activate');

/**
 * Désactivation du plugin : nettoie les crons
 *
 * @since 1.0.0
 */
function eness_blocker_deactivate() {
    // Supprime le cron de nettoyage
    $timestamp = wp_next_scheduled('eness_bot_blocker_cleanup_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'eness_bot_blocker_cleanup_cron');
    }
}
register_deactivation_hook(__FILE__, 'eness_blocker_deactivate');

/**
 * Désinstallation du plugin : supprime les données persistantes
 *
 * @since 1.0.0
 */
function eness_blocker_uninstall() {
    require_once ENESS_BLOCKER . 'include/bot-blocker/bot-blocker-uninstall.php';
    eness_bot_blocker_uninstall();
}
register_uninstall_hook(__FILE__, 'eness_blocker_uninstall');
