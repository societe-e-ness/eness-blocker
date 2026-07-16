<?php
/**
 * Bot Blocker - Nettoyage à la désinstallation
 *
 * @since 1.5.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Supprime les données persistantes du bot blocker.
 *
 * @return void
 */
function eness_bot_blocker_uninstall() {
    global $wpdb;

    delete_option('eness_bot_blocker_settings');
    delete_option('eness_bot_blocker_default_settings');
    delete_transient('eness_bot_blocker_messages');

    $wpdb->query(
        "DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_%_crawler_rate_limit'
        OR option_name LIKE '_transient_timeout_%_crawler_rate_limit'
        OR option_name LIKE '_transient_%_ip_rate_limit'
        OR option_name LIKE '_transient_timeout_%_ip_rate_limit'"
    );

    if (!function_exists('insert_with_markers')) {
        require_once(ABSPATH . 'wp-admin/includes/misc.php');
    }

    $htaccess_path = ABSPATH . '.htaccess';

    if (file_exists($htaccess_path)) {
        insert_with_markers($htaccess_path, 'e-Ness Bot block', array());
    }
}
