<?php
/**
 * Bulk action: "Generate Alt Text" in Media Library list view.
 *
 * @package AltTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ATAI_Bulk {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'bulk_actions-upload', [ $this, 'register_bulk_action' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'handle_bulk_action' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'bulk_admin_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_bulk_assets' ] );
	}

	/**
	 * Add our action to the bulk actions dropdown.
	 */
	public function register_bulk_action( array $actions ): array {
		$actions['atai_generate_bulk'] = __( 'Generate Alt Text (AI)', 'alt-text-ai' );
		return $actions;
	}

	/**
	 * Handle the bulk action — redirects to a processing page.
	 */
	public function handle_bulk_action( string $redirect_to, string $action, array $post_ids ): string {
		if ( 'atai_generate_bulk' !== $action ) {
			return $redirect_to;
		}

		// Filter to images only
		$image_ids = array_filter( $post_ids, function ( $id ) {
			return wp_attachment_is_image( $id );
		} );

		if ( empty( $image_ids ) ) {
			return add_query_arg( 'atai_bulk_error', 'no_images', $redirect_to );
		}

		// Store IDs in a transient for the AJAX processor
		$batch_id = wp_generate_password( 12, false );
		set_transient( 'atai_bulk_' . $batch_id, $image_ids, HOUR_IN_SECONDS );

		return add_query_arg( [
			'atai_bulk_process' => $batch_id,
			'atai_bulk_count'   => count( $image_ids ),
		], admin_url( 'upload.php' ) );
	}

	/**
	 * Show the bulk processing UI or result notices.
	 */
	public function bulk_admin_notice(): void {
		// Error: no images selected
		if ( isset( $_GET['atai_bulk_error'] ) && $_GET['atai_bulk_error'] === 'no_images' ) {
			echo '<div class="notice notice-warning is-dismissible"><p>';
			esc_html_e( 'No images were selected. Alt text can only be generated for image attachments.', 'alt-text-ai' );
			echo '</p></div>';
			return;
		}

		// Bulk processing modal
		if ( ! empty( $_GET['atai_bulk_process'] ) ) {
			$batch_id = sanitize_text_field( $_GET['atai_bulk_process'] );
			$count    = intval( $_GET['atai_bulk_count'] ?? 0 );
			?>
			<div id="atai-bulk-modal" class="notice notice-info">
				<h3><?php esc_html_e( 'Alt Text AI — Bulk Processing', 'alt-text-ai' ); ?></h3>
				<div id="atai-bulk-progress">
					<p id="atai-bulk-status"><?php printf( esc_html__( 'Processing 0/%d…', 'alt-text-ai' ), $count ); ?></p>
					<div class="atai-progress-bar" style="background:#e5e5e5;border-radius:4px;height:20px;overflow:hidden;max-width:500px;">
						<div id="atai-bulk-bar" style="background:#2271b1;height:100%;width:0%;transition:width .3s;"></div>
					</div>
					<p id="atai-bulk-log" style="margin-top:10px;font-size:12px;color:#666;"></p>
				</div>
				<div id="atai-bulk-complete" style="display:none;">
					<p><strong id="atai-bulk-summary"></strong></p>
				</div>
			</div>
			<script>
				window.ataiBulkBatchId = '<?php echo esc_js( $batch_id ); ?>';
				window.ataiBulkCount = <?php echo intval( $count ); ?>;
			</script>
			<?php
		}
	}

	/**
	 * Enqueue bulk processing JS.
	 */
	public function enqueue_bulk_assets( string $hook ): void {
		if ( 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'atai-bulk',
			ATAI_PLUGIN_URL . 'admin/js/bulk.js',
			[ 'jquery' ],
			ATAI_VERSION,
			true
		);

		wp_localize_script( 'atai-bulk', 'ataiBulk', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'atai_bulk' ),
		] );
	}
}
