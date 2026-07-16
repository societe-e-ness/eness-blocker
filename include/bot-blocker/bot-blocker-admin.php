<?php
/**
 * Bot Blocker - Interface d'administration
 *
 * @since 1.2.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute la page Bot Blocker au menu admin
 */
function eness_bot_blocker_add_admin_menu() {
    add_submenu_page(
        'menu_bo_plugin_eness',
        'Bot Blocker',
        'Bot Blocker',
        'manage_options',
        'eness-bot-blocker',
        'eness_bot_blocker_admin_page'
    );
}
add_action('admin_menu', 'eness_bot_blocker_add_admin_menu');

/**
 * Traite les actions AJAX et formulaires
 */
function eness_bot_blocker_handle_actions() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    // Traitement des actions POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eness_bot_blocker_action'])) {

        // Vérification du nonce
        if (!isset($_POST['eness_bot_blocker_nonce']) || !wp_verify_nonce($_POST['eness_bot_blocker_nonce'], 'eness_bot_blocker_action')) {
            wp_die('Action non autorisée');
        }

        $action = sanitize_text_field($_POST['eness_bot_blocker_action']);

        switch ($action) {
            case 'save_settings':
                eness_bot_blocker_save_settings_handler();
                break;

            case 'reset_settings':
                eness_bot_blocker_reset_settings();
                add_settings_error('eness_bot_blocker', 'settings_reset', 'Paramètres réinitialisés avec succès.', 'success');
                break;

            case 'clear_counters':
                $count = eness_bot_blocker_clear_all_counters();
                add_settings_error('eness_bot_blocker', 'counters_cleared', "Tous les compteurs ont été vidés ($count transients supprimés).", 'success');
                break;

            case 'unblock_all':
                eness_bot_blocker_unblock_all();
                add_settings_error('eness_bot_blocker', 'unblock_all', 'Tous les bots et IPs ont été débloqués.', 'success');
                break;

            case 'unblock_single':
                if (isset($_POST['user_agent'])) {
                    $user_agent = sanitize_text_field($_POST['user_agent']);
                    eness_bot_blocker_unblock_user_agent($user_agent);
                    add_settings_error('eness_bot_blocker', 'unblock_single', 'Bot débloqué avec succès.', 'success');
                }
                break;

            case 'unblock_ip':
                if (isset($_POST['ip_address'])) {
                    $ip_address = sanitize_text_field($_POST['ip_address']);
                    eness_bot_blocker_unblock_ip($ip_address);
                    add_settings_error('eness_bot_blocker', 'unblock_ip', 'IP débloquée avec succès.', 'success');
                }
                break;

            case 'add_bot':
                eness_bot_blocker_add_bot_handler();
                break;

            case 'delete_bot':
                eness_bot_blocker_delete_bot_handler();
                break;

            case 'add_ip':
                eness_bot_blocker_add_ip_handler();
                break;

            case 'delete_ip':
                eness_bot_blocker_delete_ip_handler();
                break;
        }

        set_transient('eness_bot_blocker_messages', get_settings_errors('eness_bot_blocker'), 30);

        wp_redirect(admin_url('admin.php?page=eness-bot-blocker'));
        exit;
    }
}
add_action('admin_init', 'eness_bot_blocker_handle_actions');

/**
 * Sauvegarde les paramètres généraux
 */
function eness_bot_blocker_save_settings_handler() {
    $settings = eness_bot_blocker_get_settings();

    // Configuration générale
    $settings['enabled'] = isset($_POST['enabled']) ? true : false;
    $settings['limit_seconds'] = isset($_POST['limit_seconds']) ? intval($_POST['limit_seconds']) : 3600;
    $settings['limit_access'] = isset($_POST['limit_access']) ? intval($_POST['limit_access']) : 10;
    $settings['custom_message'] = isset($_POST['custom_message']) ? sanitize_text_field($_POST['custom_message']) : '';

    // Mise à jour de l'état des bots
    if (isset($_POST['bots_enabled']) && is_array($_POST['bots_enabled'])) {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            $settings['monitored_bots'][$key]['enabled'] = in_array($key, $_POST['bots_enabled']);
        }
    } else {
        // Si aucun bot n'est coché, désactiver tous
        foreach ($settings['monitored_bots'] as $key => $bot) {
            $settings['monitored_bots'][$key]['enabled'] = false;
        }
    }

    // Mise à jour du blocage définitif des bots
    if (isset($_POST['bots_permanent_blocks']) && is_array($_POST['bots_permanent_blocks'])) {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            $settings['monitored_bots'][$key]['permanent_block'] = in_array($key, $_POST['bots_permanent_blocks']);
        }
    } else {
        foreach ($settings['monitored_bots'] as $key => $bot) {
            $settings['monitored_bots'][$key]['permanent_block'] = false;
        }
    }

    // Mise à jour de l'état des IPs
    if (isset($_POST['ips_enabled']) && is_array($_POST['ips_enabled'])) {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            $settings['monitored_ips'][$key]['enabled'] = in_array($key, $_POST['ips_enabled']);
        }
    } else {
        // Si aucune IP n'est cochée, désactiver toutes
        foreach ($settings['monitored_ips'] as $key => $ip) {
            $settings['monitored_ips'][$key]['enabled'] = false;
        }
    }

    // Mise à jour du blocage définitif des IPs
    if (isset($_POST['ips_permanent_blocks']) && is_array($_POST['ips_permanent_blocks'])) {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            $settings['monitored_ips'][$key]['permanent_block'] = in_array($key, $_POST['ips_permanent_blocks']);
        }
    } else {
        foreach ($settings['monitored_ips'] as $key => $ip) {
            $settings['monitored_ips'][$key]['permanent_block'] = false;
        }
    }

    eness_bot_blocker_save_settings($settings);
    eness_update_htaccess_from_transients();
    add_settings_error('eness_bot_blocker', 'settings_saved', 'Paramètres sauvegardés avec succès.', 'success');
}

/**
 * Ajoute un nouveau bot
 */
function eness_bot_blocker_add_bot_handler() {
    if (!isset($_POST['new_bot_name']) || !isset($_POST['new_bot_user_agent'])) {
        add_settings_error('eness_bot_blocker', 'add_bot_error', 'Données manquantes pour ajouter le bot.', 'error');
        return;
    }

    $settings = eness_bot_blocker_get_settings();

    $bot_name = sanitize_text_field($_POST['new_bot_name']);
    $user_agent = sanitize_text_field($_POST['new_bot_user_agent']);

    if (empty($bot_name) || empty($user_agent)) {
        add_settings_error('eness_bot_blocker', 'add_bot_error', 'Le nom et le user agent sont requis.', 'error');
        return;
    }

    // Génère une clé unique
    $key = sanitize_key(strtolower(str_replace(' ', '_', $bot_name)));
    $counter = 1;
    $original_key = $key;

    while (isset($settings['monitored_bots'][$key])) {
        $key = $original_key . '_' . $counter;
        $counter++;
    }

    $settings['monitored_bots'][$key] = array(
        'name' => $bot_name,
        'user_agent' => $user_agent,
        'enabled' => true,
        'permanent_block' => false,
    );

    eness_bot_blocker_save_settings($settings);
    add_settings_error('eness_bot_blocker', 'add_bot_success', 'Bot ajouté avec succès.', 'success');
}

/**
 * Supprime un bot
 */
function eness_bot_blocker_delete_bot_handler() {
    if (!isset($_POST['bot_key'])) {
        add_settings_error('eness_bot_blocker', 'delete_bot_error', 'Clé du bot manquante.', 'error');
        return;
    }

    $settings = eness_bot_blocker_get_settings();
    $bot_key = sanitize_text_field($_POST['bot_key']);

    if (isset($settings['monitored_bots'][$bot_key])) {
        delete_transient($bot_key . '_crawler_rate_limit');
        unset($settings['monitored_bots'][$bot_key]);
        eness_bot_blocker_save_settings($settings);
        eness_update_htaccess_from_transients();
        add_settings_error('eness_bot_blocker', 'delete_bot_success', 'Bot supprimé avec succès.', 'success');
    } else {
        add_settings_error('eness_bot_blocker', 'delete_bot_error', 'Bot introuvable.', 'error');
    }
}

/**
 * Ajoute une nouvelle IP
 */
function eness_bot_blocker_add_ip_handler() {
    if (!isset($_POST['new_ip_name']) || !isset($_POST['new_ip_address'])) {
        add_settings_error('eness_bot_blocker', 'add_ip_error', 'Données manquantes pour ajouter l\'IP.', 'error');
        return;
    }

    $settings = eness_bot_blocker_get_settings();

    $ip_name = sanitize_text_field($_POST['new_ip_name']);
    $ip_address = sanitize_text_field($_POST['new_ip_address']);

    if (empty($ip_name) || empty($ip_address)) {
        add_settings_error('eness_bot_blocker', 'add_ip_error', 'Le nom et l\'IP sont requis.', 'error');
        return;
    }

    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        add_settings_error('eness_bot_blocker', 'add_ip_error', 'L\'IP renseignée n\'est pas valide.', 'error');
        return;
    }

    // Génère une clé unique
    $key = sanitize_key(strtolower(str_replace(array(' ', '.', ':'), '_', $ip_name . '_' . $ip_address)));
    $counter = 1;
    $original_key = $key;

    while (isset($settings['monitored_ips'][$key])) {
        $key = $original_key . '_' . $counter;
        $counter++;
    }

    $settings['monitored_ips'][$key] = array(
        'name' => $ip_name,
        'address' => $ip_address,
        'enabled' => true,
        'permanent_block' => false,
    );

    eness_bot_blocker_save_settings($settings);
    add_settings_error('eness_bot_blocker', 'add_ip_success', 'IP ajoutée avec succès.', 'success');
}

/**
 * Supprime une IP
 */
function eness_bot_blocker_delete_ip_handler() {
    if (!isset($_POST['ip_key'])) {
        add_settings_error('eness_bot_blocker', 'delete_ip_error', 'Clé de l\'IP manquante.', 'error');
        return;
    }

    $settings = eness_bot_blocker_get_settings();
    $ip_key = sanitize_text_field($_POST['ip_key']);

    if (isset($settings['monitored_ips'][$ip_key])) {
        delete_transient($ip_key . '_ip_rate_limit');
        unset($settings['monitored_ips'][$ip_key]);
        eness_bot_blocker_save_settings($settings);
        eness_update_htaccess_from_transients();
        add_settings_error('eness_bot_blocker', 'delete_ip_success', 'IP supprimée avec succès.', 'success');
    } else {
        add_settings_error('eness_bot_blocker', 'delete_ip_error', 'IP introuvable.', 'error');
    }
}

/**
 * Affiche la page d'administration
 */
function eness_bot_blocker_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Vous n\'avez pas les permissions nécessaires.');
    }

    // Récupère les messages
    $messages = get_transient('eness_bot_blocker_messages');
    if ($messages) {
        delete_transient('eness_bot_blocker_messages');
        foreach ($messages as $message) {
            echo '<div class="notice notice-' . esc_attr($message['type']) . ' is-dismissible"><p>' . esc_html($message['message']) . '</p></div>';
        }
    }

    $settings = eness_bot_blocker_get_settings();
    $blocked_bots = eness_get_blocked_user_agents_from_htaccess();
    $blocked_ips = eness_get_blocked_ips_from_htaccess();
    $counters_stats = eness_bot_blocker_get_counters_stats();
    $ip_counters_stats = eness_bot_blocker_get_ip_counters_stats();

    ?>
    <div id="eness_panel" class="eness-bot-blocker">
        <div id="eness_panel_wrapper">
            <div id="eness_panel_header">
                <h1>Bot Blocker</h1>
                <p class="description">Protégez votre site contre les bots malveillants en limitant leur fréquence d'accès.</p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce'); ?>
                <input type="hidden" name="eness_bot_blocker_action" value="save_settings">

                <!-- Section 1 : Configuration générale -->
                <div class="eness-section">
                    <h2>Configuration générale</h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="enabled">Activer le Bot Blocker</label>
                            </th>
                            <td>
                                <label class="eness-switch">
                                    <input type="checkbox" id="enabled" name="enabled" value="1" <?php checked($settings['enabled'], true); ?>>
                                    <span class="eness-slider"></span>
                                </label>
                                <p class="description">Active ou désactive la protection contre les bots.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="limit_seconds">Durée de surveillance (secondes)</label>
                            </th>
                            <td>
                                <input type="number" id="limit_seconds" name="limit_seconds" value="<?php echo esc_attr($settings['limit_seconds']); ?>" min="60" step="60" class="regular-text">
                                <p class="description">Période pendant laquelle les accès sont comptabilisés (3600 = 1 heure).</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="limit_access">Nombre d'accès maximum</label>
                            </th>
                            <td>
                                <input type="number" id="limit_access" name="limit_access" value="<?php echo esc_attr($settings['limit_access']); ?>" min="1" class="regular-text">
                                <p class="description">Nombre d'accès autorisés avant blocage.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="custom_message">Message d'erreur personnalisé</label>
                            </th>
                            <td>
                                <input type="text" id="custom_message" name="custom_message" value="<?php echo esc_attr($settings['custom_message']); ?>" class="large-text">
                                <p class="description">Message affiché aux bots bloqués.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Section 2 : Gestion des bots et IPs -->
                <div class="eness-section">
                    <h2>Bots surveillés</h2>

                    <div class="eness-tabs">
                        <button type="button" class="eness-tab-button active" data-tab="user-agents">User agents</button>
                        <button type="button" class="eness-tab-button" data-tab="ips">IPs</button>
                    </div>

                    <div class="eness-tab-panel active" id="eness-tab-user-agents">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th width="5%">Actif</th>
                                    <th width="20%">Nom</th>
                                    <th width="45%">User Agent</th>
                                    <th width="15%">Blocage définitif</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($settings['monitored_bots'])): ?>
                                    <?php foreach ($settings['monitored_bots'] as $key => $bot): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="bots_enabled[]" value="<?php echo esc_attr($key); ?>" <?php checked($bot['enabled'], true); ?>>
                                            </td>
                                            <td><strong><?php echo esc_html($bot['name']); ?></strong></td>
                                            <td><code><?php echo esc_html($bot['user_agent']); ?></code></td>
                                            <td>
                                                <label class="eness-switch eness-switch-small">
                                                    <input type="checkbox" name="bots_permanent_blocks[]" value="<?php echo esc_attr($key); ?>" <?php checked(!empty($bot['permanent_block']), true); ?>>
                                                    <span class="eness-slider"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <button type="button" class="button button-small eness-delete-bot" data-bot-key="<?php echo esc_attr($key); ?>" data-bot-name="<?php echo esc_attr($bot['name']); ?>">
                                                    Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">Aucun bot configuré.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="eness-add-bot" style="margin-top: 20px;">
                            <h3>Ajouter un nouveau bot</h3>
                            <table class="form-table">
                                <tr>
                                    <th><label for="new_bot_name">Nom du bot</label></th>
                                    <td>
                                        <input type="text" id="new_bot_name" class="regular-text" placeholder="Ex: GoogleBot">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="new_bot_user_agent">User Agent</label></th>
                                    <td>
                                        <input type="text" id="new_bot_user_agent" class="large-text" placeholder="Ex: googlebot">
                                        <p class="description">Partie du user agent à détecter (insensible à la casse).</p>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" class="button button-secondary" id="eness-add-bot-btn">Ajouter le bot</button>
                        </div>
                    </div>

                    <div class="eness-tab-panel" id="eness-tab-ips">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th width="5%">Actif</th>
                                    <th width="20%">Nom</th>
                                    <th width="45%">IP</th>
                                    <th width="15%">Blocage définitif</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($settings['monitored_ips'])): ?>
                                    <?php foreach ($settings['monitored_ips'] as $key => $ip): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="ips_enabled[]" value="<?php echo esc_attr($key); ?>" <?php checked($ip['enabled'], true); ?>>
                                            </td>
                                            <td><strong><?php echo esc_html($ip['name']); ?></strong></td>
                                            <td><code><?php echo esc_html($ip['address']); ?></code></td>
                                            <td>
                                                <label class="eness-switch eness-switch-small">
                                                    <input type="checkbox" name="ips_permanent_blocks[]" value="<?php echo esc_attr($key); ?>" <?php checked(!empty($ip['permanent_block']), true); ?>>
                                                    <span class="eness-slider"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <button type="button" class="button button-small eness-delete-ip" data-ip-key="<?php echo esc_attr($key); ?>" data-ip-name="<?php echo esc_attr($ip['name']); ?>">
                                                    Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">Aucune IP configurée.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="eness-add-bot" style="margin-top: 20px;">
                            <h3>Ajouter une nouvelle IP</h3>
                            <table class="form-table">
                                <tr>
                                    <th><label for="new_ip_name">Nom de l'IP</label></th>
                                    <td>
                                        <input type="text" id="new_ip_name" class="regular-text" placeholder="Ex: IP suspecte">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="new_ip_address">IP</label></th>
                                    <td>
                                        <input type="text" id="new_ip_address" class="regular-text" placeholder="Ex: 192.0.2.10">
                                        <p class="description">IP exacte à surveiller.</p>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" class="button button-secondary" id="eness-add-ip-btn">Ajouter l'IP</button>
                        </div>
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">Enregistrer les paramètres</button>
                </p>
            </form>

            <!-- Section 3 : État et monitoring -->
            <div class="eness-section">
                <h2>État actuel et monitoring</h2>

                <div class="eness-monitoring-grid">
                    <!-- Bots bloqués -->
                    <div class="eness-monitor-box">
                        <h3>Éléments actuellement bloqués</h3>
                        <?php if (!empty($blocked_bots) || !empty($blocked_ips)): ?>
                            <ul class="eness-blocked-list">
                                <?php if (!empty($blocked_bots)): ?>
                                    <?php foreach ($blocked_bots as $blocked_bot): ?>
                                        <li>
                                            <span><strong>UA</strong> <code><?php echo esc_html($blocked_bot); ?></code></span>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce'); ?>
                                                <input type="hidden" name="eness_bot_blocker_action" value="unblock_single">
                                                <input type="hidden" name="user_agent" value="<?php echo esc_attr($blocked_bot); ?>">
                                                <button type="submit" class="button button-small">Débloquer</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (!empty($blocked_ips)): ?>
                                    <?php foreach ($blocked_ips as $blocked_ip): ?>
                                        <li>
                                            <span><strong>IP</strong> <code><?php echo esc_html($blocked_ip); ?></code></span>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce'); ?>
                                                <input type="hidden" name="eness_bot_blocker_action" value="unblock_ip">
                                                <input type="hidden" name="ip_address" value="<?php echo esc_attr($blocked_ip); ?>">
                                                <button type="submit" class="button button-small">Débloquer</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        <?php else: ?>
                            <p class="eness-empty">Aucun bot ou IP bloqué actuellement.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Statistiques des compteurs -->
                    <div class="eness-monitor-box">
                        <h3>Statistiques des compteurs</h3>
                        <?php if (!empty($counters_stats) || !empty($ip_counters_stats)): ?>
                            <div class="eness-stats-list">
                                <?php if (!empty($counters_stats)): ?>
                                    <?php foreach ($counters_stats as $stat): ?>
                                        <div class="eness-stat-item">
                                            <strong><?php echo esc_html($stat['name']); ?> <small>User agent</small></strong>
                                            <div class="eness-progress-bar">
                                                <div class="eness-progress-fill" style="width: <?php echo min(100, $stat['percentage']); ?>%;"></div>
                                            </div>
                                            <span class="eness-stat-count"><?php echo $stat['count']; ?> / <?php echo $stat['limit']; ?> accès</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (!empty($ip_counters_stats)): ?>
                                    <?php foreach ($ip_counters_stats as $stat): ?>
                                        <div class="eness-stat-item">
                                            <strong><?php echo esc_html($stat['name']); ?> <small><?php echo esc_html($stat['address']); ?></small></strong>
                                            <div class="eness-progress-bar">
                                                <div class="eness-progress-fill" style="width: <?php echo min(100, $stat['percentage']); ?>%;"></div>
                                            </div>
                                            <span class="eness-stat-count"><?php echo $stat['count']; ?> / <?php echo $stat['limit']; ?> accès</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="eness-empty">Aucune activité détectée récemment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Section 4 : Actions rapides -->
            <div class="eness-section">
                <h2>Actions rapides</h2>

                <div class="eness-quick-actions">
                    <form method="post" style="display: inline-block;">
                        <?php wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce'); ?>
                        <input type="hidden" name="eness_bot_blocker_action" value="clear_counters">
                        <button type="submit" class="button button-secondary" onclick="return confirm('Êtes-vous sûr de vouloir vider tous les compteurs ?');">
                            Vider tous les compteurs
                        </button>
                    </form>

                    <form method="post" style="display: inline-block;">
                        <?php wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce'); ?>
                        <input type="hidden" name="eness_bot_blocker_action" value="unblock_all">
                        <button type="submit" class="button button-secondary" onclick="return confirm('Êtes-vous sûr de vouloir débloquer tous les bots et IPs ?');">
                            Débloquer tous les bots et IPs
                        </button>
                    </form>

                    <form method="post" style="display: inline-block;">
                        <?php wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce'); ?>
                        <input type="hidden" name="eness_bot_blocker_action" value="reset_settings">
                        <button type="submit" class="button button-secondary" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser tous les paramètres ?');">
                            Réinitialiser les paramètres
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JS -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Navigation par onglets
        $('.eness-tab-button').on('click', function() {
            var tab = $(this).data('tab');

            $('.eness-tab-button').removeClass('active');
            $('.eness-tab-panel').removeClass('active');

            $(this).addClass('active');
            $('#eness-tab-' + tab).addClass('active');
        });

        // Ajout d'un bot
        $('#eness-add-bot-btn').on('click', function() {
            var name = $('#new_bot_name').val();
            var userAgent = $('#new_bot_user_agent').val();

            if (!name || !userAgent) {
                alert('Veuillez remplir tous les champs.');
                return;
            }

            var form = $('<form>', {
                method: 'post',
                action: ''
            });

            form.append('<?php echo wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce', true, false); ?>');
            form.append($('<input>', { type: 'hidden', name: 'eness_bot_blocker_action', value: 'add_bot' }));
            form.append($('<input>', { type: 'hidden', name: 'new_bot_name', value: name }));
            form.append($('<input>', { type: 'hidden', name: 'new_bot_user_agent', value: userAgent }));

            $('body').append(form);
            form.submit();
        });

        // Ajout d'une IP
        $('#eness-add-ip-btn').on('click', function() {
            var name = $('#new_ip_name').val();
            var ipAddress = $('#new_ip_address').val();

            if (!name || !ipAddress) {
                alert('Veuillez remplir tous les champs.');
                return;
            }

            var form = $('<form>', {
                method: 'post',
                action: ''
            });

            form.append('<?php echo wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce', true, false); ?>');
            form.append($('<input>', { type: 'hidden', name: 'eness_bot_blocker_action', value: 'add_ip' }));
            form.append($('<input>', { type: 'hidden', name: 'new_ip_name', value: name }));
            form.append($('<input>', { type: 'hidden', name: 'new_ip_address', value: ipAddress }));

            $('body').append(form);
            form.submit();
        });

        // Suppression d'un bot
        $('.eness-delete-bot').on('click', function() {
            var botKey = $(this).data('bot-key');
            var botName = $(this).data('bot-name');

            if (!confirm('Êtes-vous sûr de vouloir supprimer le bot "' + botName + '" ?')) {
                return;
            }

            var form = $('<form>', {
                method: 'post',
                action: ''
            });

            form.append('<?php echo wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce', true, false); ?>');
            form.append($('<input>', { type: 'hidden', name: 'eness_bot_blocker_action', value: 'delete_bot' }));
            form.append($('<input>', { type: 'hidden', name: 'bot_key', value: botKey }));

            $('body').append(form);
            form.submit();
        });

        // Suppression d'une IP
        $('.eness-delete-ip').on('click', function() {
            var ipKey = $(this).data('ip-key');
            var ipName = $(this).data('ip-name');

            if (!confirm('Êtes-vous sûr de vouloir supprimer l\'IP "' + ipName + '" ?')) {
                return;
            }

            var form = $('<form>', {
                method: 'post',
                action: ''
            });

            form.append('<?php echo wp_nonce_field('eness_bot_blocker_action', 'eness_bot_blocker_nonce', true, false); ?>');
            form.append($('<input>', { type: 'hidden', name: 'eness_bot_blocker_action', value: 'delete_ip' }));
            form.append($('<input>', { type: 'hidden', name: 'ip_key', value: ipKey }));

            $('body').append(form);
            form.submit();
        });
    });
    </script>
    <?php
}

/**
 * Charge les styles CSS pour la page d'administration
 */
function eness_bot_blocker_admin_styles($hook) {
    if ($hook !== 'plugin-e-ness_page_eness-bot-blocker') {
        return;
    }

    wp_enqueue_style('eness-bot-blocker-admin', plugins_url('../../assets/css/bot-blocker-admin.css', __FILE__), array(), '1.1.0');
}
add_action('admin_enqueue_scripts', 'eness_bot_blocker_admin_styles');

