<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'atai_license_key' );
delete_option( 'atai_industry' );
delete_option( 'atai_location' );
