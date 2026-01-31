<?php
/**
 * Settings page: Settings → Alt Text AI.
 *
 * @package AltTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ATAI_Settings {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/* -------------------------------------------------------------- */
	/*  Menu                                                          */
	/* -------------------------------------------------------------- */

	public function add_menu(): void {
		add_options_page(
			__( 'Alt Text AI', 'alt-text-ai' ),
			__( 'Alt Text AI', 'alt-text-ai' ),
			'manage_options',
			'alt-text-ai',
			[ $this, 'render_page' ]
		);
	}

	/* -------------------------------------------------------------- */
	/*  Register Settings                                             */
	/* -------------------------------------------------------------- */

	public function register_settings(): void {
		register_setting( 'atai_settings', 'atai_license_key', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );
		register_setting( 'atai_settings', 'atai_industry', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'general',
		] );
		register_setting( 'atai_settings', 'atai_location', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		add_settings_section( 'atai_main', '', '__return_false', 'alt-text-ai' );

		add_settings_field( 'atai_license_key', __( 'License Key', 'alt-text-ai' ), [ $this, 'field_license' ], 'alt-text-ai', 'atai_main' );
		add_settings_field( 'atai_industry', __( 'Industry', 'alt-text-ai' ), [ $this, 'field_industry' ], 'alt-text-ai', 'atai_main' );
		add_settings_field( 'atai_location', __( 'Location', 'alt-text-ai' ), [ $this, 'field_location' ], 'alt-text-ai', 'atai_main' );
	}

	/* -------------------------------------------------------------- */
	/*  Field Renderers                                               */
	/* -------------------------------------------------------------- */

	public function field_license(): void {
		$val = esc_attr( get_option( 'atai_license_key', '' ) );
		echo '<input type="text" name="atai_license_key" value="' . $val . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Your license key is auto-populated when you activate via Freemius.', 'alt-text-ai' ) . '</p>';
	}

	public function field_industry(): void {
		$current = get_option( 'atai_industry', 'general' );
		$options = [
			'general'    => 'General',
			'roofing'    => 'Roofing',
			'electrical' => 'Electrical',
			'plumbing'   => 'Plumbing',
			'dental'     => 'Dental',
			'automotive' => 'Automotive',
			'restaurant' => 'Restaurant',
			'real-estate'=> 'Real Estate',
			'landscaping'=> 'Landscaping',
			'legal'      => 'Legal',
			'medical'    => 'Medical',
			'retail'     => 'Retail',
			'construction'=> 'Construction',
			'cleaning'   => 'Cleaning',
			'hvac'       => 'HVAC',
		];
		echo '<select name="atai_industry">';
		foreach ( $options as $val => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $val ),
				selected( $current, $val, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Helps the AI include industry-relevant context in alt text.', 'alt-text-ai' ) . '</p>';
	}

	public function field_location(): void {
		$val = esc_attr( get_option( 'atai_location', '' ) );
		echo '<input type="text" name="atai_location" value="' . $val . '" class="regular-text" placeholder="e.g. Leeds, West Yorkshire" />';
		echo '<p class="description">' . esc_html__( 'Optional. Adds local SEO context to generated alt text.', 'alt-text-ai' ) . '</p>';
	}

	/* -------------------------------------------------------------- */
	/*  Page Renderer                                                 */
	/* -------------------------------------------------------------- */

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Alt Text AI Settings', 'alt-text-ai' ); ?></h1>

			<?php $this->render_usage_box(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'atai_settings' );
				do_settings_sections( 'alt-text-ai' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the usage stats box at the top of settings.
	 */
	private function render_usage_box(): void {
		$usage = ATAI_API::get_usage();
		if ( isset( $usage['error'] ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( $usage['error'] ) . '</p></div>';
			return;
		}
		$used  = intval( $usage['used'] ?? 0 );
		$limit = intval( $usage['limit'] ?? 0 );
		$tier  = esc_html( ucfirst( $usage['tier'] ?? 'unknown' ) );
		$pct   = $limit > 0 ? round( ( $used / $limit ) * 100 ) : 0;
		?>
		<div class="atai-usage-box" style="background:#fff;border:1px solid #ccd0d4;border-left:4px solid #2271b1;padding:16px 20px;margin:20px 0;max-width:600px;">
			<h3 style="margin:0 0 8px;"><?php echo esc_html( $tier ); ?> Plan</h3>
			<p style="margin:0 0 8px;font-size:14px;">
				<strong><?php echo esc_html( $used ); ?></strong> / <?php echo esc_html( $limit ); ?> images used this month
			</p>
			<div style="background:#e5e5e5;border-radius:4px;height:16px;overflow:hidden;">
				<div style="background:#2271b1;height:100%;width:<?php echo esc_attr( $pct ); ?>%;transition:width .3s;"></div>
			</div>
			<?php if ( ! empty( $usage['resets'] ) ) : ?>
				<p style="margin:8px 0 0;color:#666;font-size:12px;">
					Resets: <?php echo esc_html( date( 'j M Y', strtotime( $usage['resets'] ) ) ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
