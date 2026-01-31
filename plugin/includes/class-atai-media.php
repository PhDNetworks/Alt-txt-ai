<?php
/**
 * Single-image "Generate with AI" button in the media modal.
 *
 * @package AltTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ATAI_Media {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		// Add button via attachment fields filter
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_generate_button' ], 10, 2 );
	}

	/**
	 * Enqueue admin JS/CSS on media screens.
	 */
	public function enqueue_assets( string $hook ): void {
		// Load on media library, post editor, and upload screens
		if ( ! in_array( $hook, [ 'upload.php', 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'atai-admin',
			ATAI_PLUGIN_URL . 'admin/css/admin.css',
			[],
			ATAI_VERSION
		);

		wp_enqueue_script(
			'atai-media',
			ATAI_PLUGIN_URL . 'admin/js/media.js',
			[ 'jquery' ],
			ATAI_VERSION,
			true
		);

		wp_localize_script( 'atai-media', 'atai', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'atai_generate' ),
		] );
	}

	/**
	 * Add "Generate with AI" button to attachment details fields.
	 */
	public function add_generate_button( array $fields, WP_Post $post ): array {
		if ( ! wp_attachment_is_image( $post->ID ) ) {
			return $fields;
		}

		$fields['atai_generate'] = [
			'label' => '',
			'input' => 'html',
			'html'  => sprintf(
				'<button type="button" class="button atai-generate-btn" data-attachment-id="%d">%s</button>
				 <span class="atai-status"></span>',
				esc_attr( $post->ID ),
				esc_html__( '✨ Generate Alt Text with AI', 'alt-text-ai' )
			),
		];

		return $fields;
	}
}
