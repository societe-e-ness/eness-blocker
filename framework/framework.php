<?php

/**
 * Class Plugin_eness_framework
 */

if (!class_exists('Plugin_eness_framework')) {
	class Plugin_eness_framework {
		function __construct() {
			if (!function_exists('createTopMenuBackend')) {
				add_action( 'admin_menu', array( $this, 'createTopMenuBackend' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'declare_css' ) );
			}
        }

        function declare_css() {
            wp_enqueue_style( 'plugin-eness-backend', plugins_url( 'style/framework.css', __FILE__ ) );
        }

		function createTopMenuBackend() {
			add_menu_page(
				'Récapitulatif des plugins',
				'Plugin e-Ness',
				'manage_options',
				'menu_bo_plugin_eness',
				array( $this, 'createPageRecapBackend_callback' ),
                plugins_url('../assets/icon/logo-eness-blanc.svg', __FILE__),
			);
			add_submenu_page(
				'menu_bo_plugin_eness',
				'Récapitulatif des plugins',
				'Overview',
				'manage_options',
				'menu_bo_plugin_eness',
				array( $this, 'createPageRecapBackend_callback' )
			);
		}

		function createPageRecapBackend_callback() {
			?> <div id="eness_panel">
				<div id="eness_panel_wrapper">
					<div id="eness_panel_header">
						<h2>Liste des plugins e-Ness installés</h2>
					</div>
					<div id="eness_tabs">
						<div class="eness_panel_content">
						<?php
							$list_plugins = get_plugins();
							foreach ($list_plugins as $nom => $info) {
								if ($info['Author'] == "e-Ness") { ?>
									<div class="plugin_overview">
										<div class="image">
										</div>
										<div class="info">
											<span class="nom"><?php echo $info['Name']; ?></span>
											<span class="version">Version: <?php echo $info['Version']; ?></span>
											<span class="description"><?php echo $info['Description']; ?></span>
										</div>
										<div class="activation">

										</div>
									</div>
								<?php }
							}
						?>
						</div>
					</div>
				</div>
			</div> <?php
		}
	}

	new Plugin_eness_framework();
}
