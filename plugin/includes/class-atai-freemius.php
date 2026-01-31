<?php
/**
 * Freemius SDK integration scaffold.
 *
 * IMPORTANT: Danny must complete these manual steps:
 * 1. Download Freemius SDK from https://freemius.com/wordpress/sdk/
 * 2. Extract to: plugin/freemius/ directory
 * 3. Create a product on Freemius dashboard
 * 4. Replace the placeholder IDs below with real values
 * 5. Set up pricing plans (Starter/Pro/Agency) in Freemius dashboard
 *
 * @package AltTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ATAI_Freemius {

	private static ?self $instance = null;

	/** @var Freemius|null */
	private static $fs = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_freemius();
	}

	/**
	 * Initialise the Freemius SDK.
	 *
	 * Replace placeholder values with your actual Freemius product credentials.
	 */
	private function init_freemius(): void {
		$sdk_path = ATAI_PLUGIN_DIR . 'freemius/start.php';

		// Bail if SDK not installed yet
		if ( ! file_exists( $sdk_path ) ) {
			return;
		}

		require_once $sdk_path;

		try {
			self::$fs = fs_dynamic_init( [
				'id'                  => 'XXXXXX',           // TODO: Replace with Freemius product ID
				'slug'                => 'alt-text-ai',
				'type'                => 'plugin',
				'public_key'          => 'pk_XXXXXXXX',      // TODO: Replace with Freemius public key
				'is_premium'          => false,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'trial'               => [
					'days'    => 14,
					'is_require_payment' => false,
				],
				'menu'                => [
					'slug'   => 'alt-text-ai',
					'parent' => [
						'slug' => 'options-general.php',
					],
				],
			] );

			// Auto-populate license key when activated via Freemius
			self::$fs->add_action( 'after_account_connection', [ $this, 'sync_license_key' ] );
			self::$fs->add_action( 'after_license_change', [ $this, 'sync_license_key' ] );

		} catch ( Exception $e ) {
			// SDK not ready or misconfigured — fail silently
			error_log( 'Alt Text AI: Freemius init failed — ' . $e->getMessage() );
		}
	}

	/**
	 * Get the Freemius instance (if initialised).
	 */
	public static function get_fs(): ?Freemius {
		return self::$fs;
	}

	/**
	 * Sync the Freemius license key to our plugin option so the API
	 * client can use it without depending on the SDK at runtime.
	 */
	public function sync_license_key(): void {
		if ( ! self::$fs ) {
			return;
		}

		$license = self::$fs->_get_license();
		if ( $license && ! empty( $license->secret_key ) ) {
			update_option( 'atai_license_key', sanitize_text_field( $license->secret_key ) );
		}
	}

	/**
	 * Check if the current site has an active paid license.
	 */
	public static function is_paying(): bool {
		return self::$fs && self::$fs->is_paying();
	}

	/**
	 * Check if on a free trial.
	 */
	public static function is_trial(): bool {
		return self::$fs && self::$fs->is_trial();
	}
}
