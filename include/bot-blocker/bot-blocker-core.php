<?php
/**
 * Bot Blocker - Logique de blocage des bots
 *
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Si ces fonctions existent déjà (ex: ancienne version d'eness-booster encore active
// sur le site), on ne redéclare rien pour éviter un fatal "Cannot redeclare function".
if (!function_exists('eness_limit_site_access')) {

/**
 * Récupère la liste des user agents bloqués depuis le fichier .htaccess
 *
 * @return array Liste des user agents bloqués
 */
function eness_get_blocked_user_agents_from_htaccess() {
    // Charge les fonctions WordPress nécessaires si elles n'existent pas
    if (!function_exists('extract_from_markers')) {
        require_once(ABSPATH . 'wp-admin/includes/misc.php');
    }

    $htaccess_path = ABSPATH . '.htaccess';

    if (!file_exists($htaccess_path)) {
        return array();
    }

    $marker_name = 'e-Ness Bot block';
    $marker_lines = extract_from_markers($htaccess_path, $marker_name);

    $blocked_user_agents = array();

    // Parcours des lignes récupérées et extraction des user agents
    foreach ($marker_lines as $line) {
        if (preg_match('/RewriteCond \%\{HTTP_USER_AGENT\} (.+?) \[NC(?:,OR)?\]/', $line, $matches)) {
            // Nettoyage des caractères d'échappement
            $blocked_user_agents[] = stripslashes($matches[1]);
        }
    }

    return $blocked_user_agents;
}

/**
 * Récupère la liste des IPs bloquées depuis le fichier .htaccess
 *
 * @return array Liste des IPs bloquées
 */
function eness_get_blocked_ips_from_htaccess() {
    // Charge les fonctions WordPress nécessaires si elles n'existent pas
    if (!function_exists('extract_from_markers')) {
        require_once(ABSPATH . 'wp-admin/includes/misc.php');
    }

    $htaccess_path = ABSPATH . '.htaccess';

    if (!file_exists($htaccess_path)) {
        return array();
    }

    $marker_name = 'e-Ness Bot block';
    $marker_lines = extract_from_markers($htaccess_path, $marker_name);

    $blocked_ips = array();

    foreach ($marker_lines as $line) {
        if (preg_match('/RewriteCond \%\{REMOTE_ADDR\} \^(.+?)\$(?: \[OR\])?/', $line, $matches)) {
            $blocked_ips[] = str_replace('\\', '', $matches[1]);
        }
    }

    return $blocked_ips;
}

/**
 * Met à jour le fichier .htaccess avec la liste des user agents et IPs à bloquer
 *
 * @param array $user_agents_to_block Liste des user agents à bloquer
 * @param array $ips_to_block Liste des IPs à bloquer
 * @return bool
 */
function eness_update_htaccess_with_blocks($user_agents_to_block, $ips_to_block) {
    // Charge les fonctions WordPress nécessaires si elles n'existent pas
    if (!function_exists('insert_with_markers')) {
        require_once(ABSPATH . 'wp-admin/includes/misc.php');
    }

    $htaccess_path = ABSPATH . '.htaccess';

    if (!file_exists($htaccess_path)) {
        return false;
    }

    $marker_name = 'e-Ness Bot block';

    // Filtre les user agents invalides (vides, null, etc.)
    $user_agents_to_block = array_filter($user_agents_to_block, function($ua) {
        if (empty($ua)) {
            return false;
        }
        if (!is_string($ua)) {
            return false;
        }
        if (trim($ua) === '') {
            return false;
        }
        return true;
    });

    // Filtre les IPs invalides
    $ips_to_block = array_filter($ips_to_block, function($ip) {
        return is_string($ip) && filter_var(trim($ip), FILTER_VALIDATE_IP);
    });

    if (empty($user_agents_to_block) && empty($ips_to_block)) {
        return insert_with_markers($htaccess_path, $marker_name, array());
    }

    $rules = array();
    $rules[] = "# Liste des user agents et IPs bloqués";
    $rules[] = "<IfModule mod_rewrite.c>";
    $rules[] = "    RewriteEngine On";

    $conditions = array();

    foreach ($user_agents_to_block as $user_agent) {
        $conditions[] = array(
            'condition' => "    RewriteCond %{HTTP_USER_AGENT} " . preg_quote($user_agent, '/'),
            'flags' => array('NC'),
        );
    }

    foreach ($ips_to_block as $ip) {
        $conditions[] = array(
            'condition' => "    RewriteCond %{REMOTE_ADDR} ^" . preg_quote(trim($ip), '/') . "$",
            'flags' => array(),
        );
    }

    $conditions_count = count($conditions);
    foreach ($conditions as $index => $condition) {
        $flags = $condition['flags'];

        if ($index < $conditions_count - 1) {
            $flags[] = 'OR';
        }

        $rules[] = $condition['condition'] . (!empty($flags) ? ' [' . implode(',', $flags) . ']' : '');
    }

    $rules[] = "    RewriteRule .* - [F,L]";
    $rules[] = "</IfModule>";

    return insert_with_markers($htaccess_path, $marker_name, $rules);
}

/**
 * Reconstruit le .htaccess à partir de l'état actuel des transients
 * Cette fonction est la source unique de vérité pour le blocage dans .htaccess
 *
 * @since 1.0.0
 * @return void
 */
function eness_update_htaccess_from_transients() {
    $settings = eness_bot_blocker_get_settings();
    $blocked_bots = array();
    $blocked_ips = array();

    // Parcourt tous les bots surveillés
    if (!empty($settings['monitored_bots']) && is_array($settings['monitored_bots'])) {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            $transient_name = $key . '_crawler_rate_limit';
            $count = get_transient($transient_name);

            // Si le blocage définitif est activé ou si le seuil est dépassé, le bot actif doit être bloqué
            if (!empty($bot['enabled']) && (!empty($bot['permanent_block']) || ($count !== false && $count >= $settings['limit_access']))) {
                $blocked_bots[] = $bot['user_agent'];
            }
        }
    }

    // Parcourt toutes les IPs surveillées
    if (!empty($settings['monitored_ips']) && is_array($settings['monitored_ips'])) {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            $transient_name = $key . '_ip_rate_limit';
            $count = get_transient($transient_name);

            // Si le blocage définitif est activé ou si le seuil est dépassé, l'IP active doit être bloquée
            if (!empty($ip['enabled']) && (!empty($ip['permanent_block']) || ($count !== false && $count >= $settings['limit_access']))) {
                $blocked_ips[] = $ip['address'];
            }
        }
    }

    // Met à jour le .htaccess avec la liste des bots et IPs à bloquer
    eness_update_htaccess_with_blocks($blocked_bots, $blocked_ips);
}

/**
 * Indique si la requête courante doit être ignorée par le compteur.
 *
 * @return bool
 */
function eness_bot_blocker_should_skip_request() {
    if (empty($_SERVER['REQUEST_URI'])) {
        return false;
    }

    $request_path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    return ($request_path === '/favicon.ico');
}

/**
 * Fonction principale de limitation d'accès au site
 * Surveille et bloque les bots qui accèdent trop fréquemment
 */
function eness_limit_site_access() {
    if (eness_bot_blocker_should_skip_request()) {
        return;
    }

    // Récupère les paramètres
    $settings = eness_bot_blocker_get_settings();

    // Si le bot blocker est désactivé, on ne fait rien
    if (empty($settings['enabled'])) {
        return;
    }

    $limit_seconds = intval($settings['limit_seconds']);
    $limit_access = intval($settings['limit_access']);
    $custom_message = sanitize_text_field($settings['custom_message']);

    // Récupère les bots actifs à surveiller
    $user_agents_to_check = eness_bot_blocker_get_active_bots();
    $ips_to_check = eness_bot_blocker_get_active_ips();
    $should_rebuild_htaccess = false;
    $should_block_request = false;

    // Vérifie si le header HTTP_USER_AGENT est défini
    if (!empty($user_agents_to_check) && isset($_SERVER['HTTP_USER_AGENT'])) {
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'];

        // Parcourt la liste des user agents à surveiller
        foreach ($user_agents_to_check as $user_agent => $key) {
            // Vérifie si le user agent courant correspond à l'un des user agents à surveiller
            if (stripos($current_user_agent, $user_agent) !== false) {
                if (!empty($settings['monitored_bots'][$key]['permanent_block'])) {
                    $should_rebuild_htaccess = true;
                    $should_block_request = true;
                    break;
                }

                $transient_name = $key . '_crawler_rate_limit';
                $old_count = get_transient($transient_name);

                // Calcul du nouveau nombre d'accès
                $nbr_acces = ($old_count === false) ? 1 : $old_count + 1;

                // Sauvegarde le nouveau compteur
                set_transient($transient_name, $nbr_acces, $limit_seconds);

                // Détection du changement de statut (bloqué <-> non bloqué)
                $was_blocked = ($old_count !== false && $old_count >= $limit_access);
                $is_blocked = ($nbr_acces >= $limit_access);

                // Reconstruction du .htaccess SEULEMENT si le statut de blocage a changé
                if ($was_blocked !== $is_blocked) {
                    $should_rebuild_htaccess = true;
                }

                // Si le nombre d'accès dépasse la limite autorisée, on bloque
                if ($is_blocked) {
                    $should_block_request = true;
                }

                break;
            }
        }
    }

    // Vérifie l'IP exacte de la requête
    if (!empty($ips_to_check) && isset($_SERVER['REMOTE_ADDR'])) {
        $current_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);

        foreach ($ips_to_check as $ip => $key) {
            if ($current_ip === $ip) {
                if (!empty($settings['monitored_ips'][$key]['permanent_block'])) {
                    $should_rebuild_htaccess = true;
                    $should_block_request = true;
                    break;
                }

                $transient_name = $key . '_ip_rate_limit';
                $old_count = get_transient($transient_name);

                // Calcul du nouveau nombre d'accès
                $nbr_acces = ($old_count === false) ? 1 : $old_count + 1;

                // Sauvegarde le nouveau compteur
                set_transient($transient_name, $nbr_acces, $limit_seconds);

                // Détection du changement de statut (bloqué <-> non bloqué)
                $was_blocked = ($old_count !== false && $old_count >= $limit_access);
                $is_blocked = ($nbr_acces >= $limit_access);

                // Reconstruction du .htaccess SEULEMENT si le statut de blocage a changé
                if ($was_blocked !== $is_blocked) {
                    $should_rebuild_htaccess = true;
                }

                // Si le nombre d'accès dépasse la limite autorisée, on bloque
                if ($is_blocked) {
                    $should_block_request = true;
                }

                break;
            }
        }
    }

    if ($should_rebuild_htaccess) {
        eness_update_htaccess_from_transients();
    }

    if ($should_block_request) {
        wp_die($custom_message);
    }
}

// Active la fonction de limitation d'accès
add_action('init', 'eness_limit_site_access');

/**
 * Débloque un user agent spécifique en supprimant son compteur
 * Le .htaccess sera automatiquement mis à jour lors de la prochaine reconstruction
 *
 * @param string $user_agent
 * @return void
 */
function eness_bot_blocker_unblock_user_agent($user_agent) {
    // Supprime le compteur associé à ce user agent
    $settings = eness_bot_blocker_get_settings();
    if (!empty($settings['monitored_bots'])) {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            if ($bot['user_agent'] === $user_agent) {
                $transient_name = $key . '_crawler_rate_limit';
                delete_transient($transient_name);
                break;
            }
        }
    }

    // Reconstruction du .htaccess à partir de l'état des transients
    eness_update_htaccess_from_transients();
}

/**
 * Débloque une IP spécifique en supprimant son compteur
 * Le .htaccess sera automatiquement mis à jour lors de la prochaine reconstruction
 *
 * @param string $ip_address
 * @return void
 */
function eness_bot_blocker_unblock_ip($ip_address) {
    // Supprime le compteur associé à cette IP
    $settings = eness_bot_blocker_get_settings();
    if (!empty($settings['monitored_ips'])) {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            if ($ip['address'] === $ip_address) {
                $transient_name = $key . '_ip_rate_limit';
                delete_transient($transient_name);
                break;
            }
        }
    }

    // Reconstruction du .htaccess à partir de l'état des transients
    eness_update_htaccess_from_transients();
}

/**
 * Débloque tous les user agents en supprimant tous les compteurs
 *
 * @return void
 */
function eness_bot_blocker_unblock_all() {
    $settings = eness_bot_blocker_get_settings();

    // Supprime tous les compteurs
    if (!empty($settings['monitored_bots'])) {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            $transient_name = $key . '_crawler_rate_limit';
            delete_transient($transient_name);
        }
    }

    if (!empty($settings['monitored_ips'])) {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            $transient_name = $key . '_ip_rate_limit';
            delete_transient($transient_name);
        }
    }

    // Reconstruction du .htaccess (vide puisque tous les transients sont supprimés)
    eness_update_htaccess_from_transients();
}

/**
 * Nettoie automatiquement le .htaccess des bots dont le transient a expiré
 * Cette fonction est appelée périodiquement par le cron WordPress
 *
 * @since 1.0.0
 * @return void
 */
function eness_bot_blocker_cleanup_expired_blocks() {
    // Reconstruction complète du .htaccess à partir de l'état des transients
    eness_update_htaccess_from_transients();
}

} // fin if (!function_exists('eness_limit_site_access'))

