<?php
/**
 * Plugin Name: Alt Text AI
 * Plugin URI:  https://alttextai.com
 * Description: Generate SEO-optimised alt text for images using AI. Bulk process your entire media library in clicks.
 * Version:     1.0.0
 * Author:      PhD Networks & Systems
 * Author URI:  https://phdnetworks.co.uk
 * License:     GPL-2.0-or-later
 * Text Domain: alt-text-ai
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ATAI_VERSION', '1.0.0' );
define( 'ATAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ATAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ATAI_API_BASE', 'https://api.alttextai.com' );

/* ------------------------------------------------------------------
 * Autoload includes
 * ----------------------------------------------------------------*/
require_once ATAI_PLUGIN_DIR . 'includes/class-atai-api.php';
require_once ATAI_PLUGIN_DIR . 'includes/class-atai-settings.php';
require_once ATAI_PLUGIN_DIR . 'includes/class-atai-media.php';
require_once ATAI_PLUGIN_DIR . 'includes/class-atai-bulk.php';
require_once ATAI_PLUGIN_DIR . 'includes/class-atai-ajax.php';
require_once ATAI_PLUGIN_DIR . 'includes/class-atai-freemius.php';

/* ------------------------------------------------------------------
 * Boot
 * ----------------------------------------------------------------*/
function atai_init() {
	ATAI_Settings::get_instance();
	ATAI_Media::get_instance();
	ATAI_Bulk::get_instance();
	ATAI_Ajax::get_instance();
	ATAI_Freemius::get_instance();
}
add_action( 'plugins_loaded', 'atai_init' );

/* ------------------------------------------------------------------
 * Activation / Deactivation
 * ----------------------------------------------------------------*/
register_activation_hook( __FILE__, function () {
	add_option( 'atai_license_key', '' );
	add_option( 'atai_industry', 'general' );
	add_option( 'atai_location', '' );
} );

register_deactivation_hook( __FILE__, function () {
	// Keep options on deactivation; clean up on uninstall only.
} );
