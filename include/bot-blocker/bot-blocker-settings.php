<?php
/**
 * Bot Blocker - Gestion des options et settings
 *
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupère les paramètres par défaut du bot blocker
 *
 * @return array
 */
function eness_bot_blocker_get_default_settings() {
    return array(
        'enabled' => true,
        'limit_seconds' => 3600,
        'limit_access' => 10,
        'custom_message' => 'Vous accédez trop fréquemment au site. Veuillez réessayer plus tard.',
        'monitored_ips' => array(
            'ip_34.65.123.32' => array(
                'name' => 'IP suspect 1',
                'address' => '34.65.123.32',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_34.65.83.163' => array(
                'name' => 'IP suspect 2',
                'address' => '34.65.83.163',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_195_178_110_64' => array(
                'name' => 'IP demande de reset password en masse 1',
                'address' => '195.178.110.64',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_195_178_110_242' => array(
                'name' => 'IP demande de reset password en masse 2',
                'address' => '195.178.110.242',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_45_148_10_21' => array(
                'name' => 'IP demande de reset password en masse 3',
                'address' => '45.148.10.21',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_167_172_90_170' => array(
                'name' => 'IP force brute 1',
                'address' => '167.172.90.170',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_165_245_183_237' => array(
                'name' => 'IP force brute 2',
                'address' => '165.245.183.237',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_146_235_16_244' => array(
                'name' => 'IP force brute 3',
                'address' => '146.235.16.244',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_161_118_222_149' => array(
                'name' => 'IP force brute 4',
                'address' => '161.118.222.149',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_62_60_130_239' => array(
                'name' => 'IP force brute 5',
                'address' => '62.60.130.239',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_161_118_221_125' => array(
                'name' => 'IP force brute 6',
                'address' => '161.118.221.125',
                'enabled' => true,
                'permanent_block' => true,
            ),
            'ip_188_166_234_16' => array(
                'name' => 'IP force brute 7',
                'address' => '188.166.234.16',
                'enabled' => true,
                'permanent_block' => true,
            ),
        ),
        'monitored_bots' => array(
            'facebookexternalhit' => array(
                'name' => 'Facebook External Hit',
                'user_agent' => 'facebookexternalhit',
                'enabled' => true,
            ),
            'facebook_docs' => array(
                'name' => 'Facebook Crawler',
                'user_agent' => 'facebook.com/docs/sharing/webmasters/crawler',
                'enabled' => true,
            ),
            'babbar' => array(
                'name' => 'Babbar',
                'user_agent' => 'babbar.tech/crawler',
                'enabled' => true,
            ),
            'dotbot' => array(
                'name' => 'DotBot',
                'user_agent' => 'opensiteexplorer.org/dotbot',
                'enabled' => true,
            ),
            'claudebot' => array(
                'name' => 'ClaudeBot',
                'user_agent' => 'claudebot',
                'enabled' => true,
            ),
            'serpstatbot' => array(
                'name' => 'SerpstatBot',
                'user_agent' => 'serpstatbot',
                'enabled' => true,
            ),
            'ahrefsbot' => array(
                'name' => 'AhrefsBot',
                'user_agent' => 'AhrefsBot',
                'enabled' => true,
            ),
            'yandexbot' => array(
                'name' => 'YandexBot',
                'user_agent' => 'yandex.com/bots',
                'enabled' => true,
            ),
            'zoominfobot' => array(
                'name' => 'ZoominfoBot',
                'user_agent' => 'ZoominfoBot',
                'enabled' => true,
            ),
            'petalbot' => array(
                'name' => 'PetalBot',
                'user_agent' => 'petalbot',
                'enabled' => true,
            ),
            'bingbot' => array(
                'name' => 'BingBot',
                'user_agent' => 'bingbot',
                'enabled' => true,
            ),
            'amazonbot' => array(
                'name' => 'AmazonBot',
                'user_agent' => 'amazonbot',
                'enabled' => true,
            ),
            'headlesschrome' => array(
                'name' => 'HeadlessChrome',
                'user_agent' => 'HeadlessChrome',
                'enabled' => true,
            ),
            'dnbcrawler' => array(
                'name' => 'DnB Crawler',
                'user_agent' => 'DnBCrawler-Analytics',
                'enabled' => true,
            ),
            'geedo' => array(
                'name' => 'Geedo',
                'user_agent' => 'geedo',
                'enabled' => true,
            ),
            'semrush' => array(
                'name' => 'SEMrush',
                'user_agent' => 'semrush.com/bot',
                'enabled' => true,
            ),
            'baiduspider' => array(
                'name' => 'Baiduspider',
                'user_agent' => 'Baiduspider-render/2.0',
                'enabled' => true,
            ),
            'mj12bot' => array(
                'name' => 'MJ12bot',
                'user_agent' => 'MJ12bot',
                'enabled' => true,
            ),
        ),
    );
}

/**
 * Retourne les anciens defaults pour le bootstrap initial du système de migration
 * Cette fonction ne sert QUE lors de la première migration
 * Une fois eness_bot_blocker_default_settings créé en BDD, elle n'est plus utilisée
 *
 * @since 1.2.4
 * @return array
 */
function eness_bot_blocker_get_legacy_defaults() {
    // On part des defaults actuels
    $legacy = eness_bot_blocker_get_default_settings();

    // On override les valeurs qui ont changé avec leurs ANCIENNES valeurs
    $legacy['limit_seconds'] = 7200; // Ancienne valeur avant migration (était 7200, maintenant 3600)
    $legacy['monitored_ips'] = array(); // Ancienne valeur avant ajout du blocage par IP

    // Si d'autres valeurs ont changé historiquement, les ajouter ici
    // $legacy['autre_setting'] = 'ancienne_valeur';

    return $legacy;
}

/**
 * Fusionne les nouvelles entrées par défaut d'une collection sans écraser les réglages utilisateur.
 *
 * @param array $current_settings Réglages actuels
 * @param array $stored_defaults Defaults connus avant mise à jour
 * @param array $code_defaults Defaults du code courant
 * @param string $collection_key Clé de collection à fusionner
 * @return array Réglages mis à jour et état de modification
 */
function eness_bot_blocker_merge_default_collection($current_settings, $stored_defaults, $code_defaults, $collection_key) {
    $needs_update = false;

    if (empty($code_defaults[$collection_key]) || !is_array($code_defaults[$collection_key])) {
        return array($current_settings, $needs_update);
    }

    if (empty($current_settings[$collection_key]) || !is_array($current_settings[$collection_key])) {
        $current_settings[$collection_key] = array();
    }

    $stored_collection = array();
    if (!empty($stored_defaults[$collection_key]) && is_array($stored_defaults[$collection_key])) {
        $stored_collection = $stored_defaults[$collection_key];
    }

    foreach ($code_defaults[$collection_key] as $item_key => $default_item) {
        // Nouvelle entrée par défaut : on l'ajoute aux installations existantes.
        if (!array_key_exists($item_key, $current_settings[$collection_key]) && !array_key_exists($item_key, $stored_collection)) {
            $current_settings[$collection_key][$item_key] = $default_item;
            $needs_update = true;
            continue;
        }

        // Entrée existante : on ajoute uniquement les nouveaux champs, sans écraser les valeurs utilisateur.
        if (isset($current_settings[$collection_key][$item_key]) && is_array($current_settings[$collection_key][$item_key]) && is_array($default_item)) {
            foreach ($default_item as $field_key => $field_value) {
                if (!array_key_exists($field_key, $current_settings[$collection_key][$item_key])) {
                    $current_settings[$collection_key][$item_key][$field_key] = $field_value;
                    $needs_update = true;
                }
            }
        }
    }

    return array($current_settings, $needs_update);
}

/**
 * Migration automatique des paramètres lors des changements de valeurs par défaut
 * Cette fonction compare les defaults stockés en BDD avec les defaults du code
 * et migre automatiquement les valeurs qui correspondent aux anciens defaults
 *
 * @since 1.2.4
 * @return void
 */
function eness_bot_blocker_auto_migrate_settings() {
    $code_defaults = eness_bot_blocker_get_default_settings();
    $stored_defaults = get_option('eness_bot_blocker_default_settings', false);

    // Bootstrap : première exécution, eness_bot_blocker_default_settings n'existe pas
    if ($stored_defaults === false) {
        // On utilise les anciens defaults connus pour le bootstrap
        $stored_defaults = eness_bot_blocker_get_legacy_defaults();
    }

    $current_settings = get_option('eness_bot_blocker_settings', false);

    // Cas 1 : Pas de settings utilisateur, on met juste à jour les defaults stockés
    if ($current_settings === false) {
        update_option('eness_bot_blocker_default_settings', $code_defaults);
        return;
    }

    // Cas 2 & 3 : Settings utilisateur existent, on vérifie setting par setting
    $needs_update = false;

    if ($stored_defaults !== $code_defaults) {
        foreach ($code_defaults as $key => $new_default_value) {
            // Les collections sont fusionnées entrée par entrée plus bas.
            if (in_array($key, array('monitored_bots', 'monitored_ips'), true)) {
                continue;
            }

            // Nouveau setting de premier niveau : on l'ajoute sans toucher au reste.
            if (!array_key_exists($key, $current_settings)) {
                $current_settings[$key] = $new_default_value;
                $needs_update = true;
                continue;
            }

            // Si ce setting a un ancien default stocké
            if (!isset($stored_defaults[$key])) {
                continue; // Nouveau setting déjà traité
            }

            $old_default_value = $stored_defaults[$key];

            // Si le default a changé dans le code
            if ($old_default_value !== $new_default_value) {
                // Cas 2 : Si l'utilisateur avait l'ancien default, on migre
                if (isset($current_settings[$key]) && $current_settings[$key] === $old_default_value) {
                    $current_settings[$key] = $new_default_value;
                    $needs_update = true;
                }
                // Cas 3 : Sinon l'utilisateur a une valeur custom, on ne touche pas
            }
        }
    }

    list($current_settings, $bots_merged) = eness_bot_blocker_merge_default_collection($current_settings, $stored_defaults, $code_defaults, 'monitored_bots');
    list($current_settings, $ips_merged) = eness_bot_blocker_merge_default_collection($current_settings, $stored_defaults, $code_defaults, 'monitored_ips');
    $needs_update = $needs_update || $bots_merged || $ips_merged;

    // Sauvegarde des settings utilisateur si nécessaire
    if ($needs_update) {
        update_option('eness_bot_blocker_settings', $current_settings);
    }

    // MAJ des defaults stockés en BDD
    update_option('eness_bot_blocker_default_settings', $code_defaults);
}

/**
 * Récupère les paramètres du bot blocker
 *
 * @return array
 */
function eness_bot_blocker_get_settings() {
    $defaults = eness_bot_blocker_get_default_settings();
    $settings = get_option('eness_bot_blocker_settings', $defaults);

    // Fusion avec les valeurs par défaut pour gérer les nouvelles options
    return wp_parse_args($settings, $defaults);
}

/**
 * Sauvegarde les paramètres du bot blocker
 *
 * @param array $settings
 * @return bool
 */
function eness_bot_blocker_save_settings($settings) {
    return update_option('eness_bot_blocker_settings', $settings);
}

/**
 * Réinitialise les paramètres aux valeurs par défaut
 *
 * @return bool
 */
function eness_bot_blocker_reset_settings() {
    return update_option('eness_bot_blocker_settings', eness_bot_blocker_get_default_settings());
}

/**
 * Récupère les bots surveillés et activés
 *
 * @return array
 */
function eness_bot_blocker_get_active_bots() {
    $settings = eness_bot_blocker_get_settings();
    $active_bots = array();

    if (!empty($settings['monitored_bots']) && is_array($settings['monitored_bots'])) {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            if (!empty($bot['enabled']) && !empty($bot['user_agent'])) {
                $active_bots[$bot['user_agent']] = $key;
            }
        }
    }

    return $active_bots;
}

/**
 * Récupère les IPs surveillées et activées
 *
 * @return array
 */
function eness_bot_blocker_get_active_ips() {
    $settings = eness_bot_blocker_get_settings();
    $active_ips = array();

    if (!empty($settings['monitored_ips']) && is_array($settings['monitored_ips'])) {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            if (!empty($ip['enabled']) && !empty($ip['address'])) {
                $active_ips[$ip['address']] = $key;
            }
        }
    }

    return $active_ips;
}

/**
 * Vide tous les compteurs (transients) des bots
 *
 * @return int Nombre de transients supprimés
 */
function eness_bot_blocker_clear_all_counters() {
    global $wpdb;

    $count = $wpdb->query(
        "DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_%_crawler_rate_limit'
        OR option_name LIKE '_transient_timeout_%_crawler_rate_limit'
        OR option_name LIKE '_transient_%_ip_rate_limit'
        OR option_name LIKE '_transient_timeout_%_ip_rate_limit'"
    );

    return $count;
}

/**
 * Récupère les statistiques des compteurs actuels
 *
 * @return array
 */
function eness_bot_blocker_get_counters_stats() {
    $settings = eness_bot_blocker_get_settings();
    $stats = array();

    if (!empty($settings['monitored_bots']) && is_array($settings['monitored_bots'])) {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            $transient_name = $key . '_crawler_rate_limit';
            $count = get_transient($transient_name);

            if ($count !== false) {
                $stats[$key] = array(
                    'name' => $bot['name'],
                    'count' => intval($count),
                    'limit' => $settings['limit_access'],
                    'percentage' => ($count / $settings['limit_access']) * 100,
                );
            }
        }
    }

    return $stats;
}

/**
 * Récupère les statistiques des compteurs IP actuels
 *
 * @return array
 */
function eness_bot_blocker_get_ip_counters_stats() {
    $settings = eness_bot_blocker_get_settings();
    $stats = array();

    if (!empty($settings['monitored_ips']) && is_array($settings['monitored_ips'])) {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            $transient_name = $key . '_ip_rate_limit';
            $count = get_transient($transient_name);

            if ($count !== false) {
                $stats[$key] = array(
                    'name' => $ip['name'],
                    'address' => $ip['address'],
                    'count' => intval($count),
                    'limit' => $settings['limit_access'],
                    'percentage' => ($count / $settings['limit_access']) * 100,
                );
            }
        }
    }

    return $stats;
}

