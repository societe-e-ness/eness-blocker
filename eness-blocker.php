<?php
/**
 * e-Ness Blocker WordPress Plugin
 *
 * @package e-Ness Blocker
 *
 * Plugin Name: e-Ness Blocker
 * Description: Protège le site contre les bots et IPs malveillants en limitant leur fréquence d'accès
 * Plugin URI: https://www.eness.fr
 * Version: 1.0.1
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
 * Retourne les informations d'un e-Ness Booster actif incompatible, s'il existe.
 *
 * @since 1.0.0
 * @return array|false
 */
function eness_blocker_get_incompatible_booster() {
    static $incompatible_booster = null;

    if ($incompatible_booster !== null) {
        return $incompatible_booster;
    }

    if (!function_exists('get_plugins') || !function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = get_plugins();
    $incompatible_booster = false;

    foreach ($plugins as $plugin_file => $plugin_data) {
        $is_active = is_plugin_active($plugin_file) || (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network($plugin_file));

        if (!$is_active) {
            continue;
        }

        $normalized_plugin_file = strtolower(str_replace('\\', '/', $plugin_file));
        $text_domain = isset($plugin_data['TextDomain']) ? strtolower($plugin_data['TextDomain']) : '';
        $name = isset($plugin_data['Name']) ? $plugin_data['Name'] : '';
        $is_booster = strpos($normalized_plugin_file, 'eness-booster') !== false
            || $text_domain === 'eness-booster'
            || preg_match('/\be-?ness\s+booster\b/i', $name);

        if (!$is_booster) {
            continue;
        }

        $version = isset($plugin_data['Version']) ? trim((string) $plugin_data['Version']) : '';
        $is_compatible = $version !== ''
            && (
                version_compare($version, '1.7.0', '>=')
                || preg_match('/^1\.7\.0[-_.\s]?rc(?:[-_.\s]?\d+)?$/i', $version)
            );

        if (!$is_compatible) {
            $incompatible_booster = array(
                'file' => $plugin_file,
                'name' => isset($plugin_data['Name']) ? $plugin_data['Name'] : 'e-Ness Booster',
                'version' => $version,
            );
            break;
        }
    }

    return $incompatible_booster;
}

/**
 * Indique si le module Bot Blocker peut être chargé.
 *
 * @since 1.0.0
 * @return bool
 */
function eness_blocker_should_load_bot_blocker() {
    return !eness_blocker_get_incompatible_booster();
}

/**
 * Affiche une notice lorsque le Bot Blocker n'est pas chargé à cause d'e-Ness Booster.
 *
 * @since 1.0.0
 * @return void
 */
function eness_blocker_incompatible_booster_notice() {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    $booster = eness_blocker_get_incompatible_booster();

    if (!$booster) {
        return;
    }

    $version = $booster['version'] !== '' ? $booster['version'] : 'inconnue';

    echo '<div class="notice notice-warning"><p>';
    echo '<strong>e-Ness Blocker :</strong> le Bot Blocker n\'est pas chargé car ';
    echo esc_html($booster['name']) . ' ' . esc_html($version);
    echo ' contient encore cette fonctionnalité. Mettez e-Ness Booster à jour en version 1.7.0 ou supérieure, ou en 1.7.0-rc.x, pour réactiver automatiquement le Bot Blocker d\'e-Ness Blocker.';
    echo '</p></div>';
}

/**
 * Ajout de la fonctionnalité de blocage des bots malveillants
 *
 * @since 1.0.0
 *
 */
if (eness_blocker_should_load_bot_blocker()) {
    require_once 'include/bot-blocker/bot-blocker.php';
} else {
    add_action('admin_notices', 'eness_blocker_incompatible_booster_notice');
    add_action('network_admin_notices', 'eness_blocker_incompatible_booster_notice');
}

/**
 * Activation du plugin : planifie les crons
 *
 * @since 1.0.0
 */
function eness_blocker_activate() {
    if (!eness_blocker_should_load_bot_blocker()) {
        return;
    }

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
