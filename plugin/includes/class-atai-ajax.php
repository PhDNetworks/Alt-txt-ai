<?php
/**
 * AJAX handlers for single and bulk alt text generation.
 *
 * @package AltTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ATAI_Ajax {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_atai_generate_single', [ $this, 'generate_single' ] );
		add_action( 'wp_ajax_atai_bulk_next', [ $this, 'bulk_next' ] );
	}

	/**
	 * AJAX: Generate alt text for a single attachment.
	 */
	public function generate_single(): void {
		check_ajax_referer( 'atai_generate', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}

		$attachment_id = intval( $_POST['attachment_id'] ?? 0 );
		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => 'Invalid attachment.' ] );
		}

		$result = ATAI_API::generate( $attachment_id );

		if ( ! $result['success'] ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}

		// Save alt text
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $result['alt_text'] ) );

		wp_send_json_success( [
			'alt_text' => $result['alt_text'],
			'usage'    => $result['usage'],
		] );
	}

	/**
	 * AJAX: Process the next image in a bulk batch.
	 *
	 * Called sequentially by the JS — one image at a time.
	 */
	public function bulk_next(): void {
		check_ajax_referer( 'atai_bulk', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}

		$batch_id = sanitize_text_field( $_POST['batch_id'] ?? '' );
		$index    = intval( $_POST['index'] ?? 0 );

		$image_ids = get_transient( 'atai_bulk_' . $batch_id );
		if ( ! $image_ids || ! is_array( $image_ids ) ) {
			wp_send_json_error( [ 'message' => 'Batch expired or not found.' ] );
		}

		if ( $index >= count( $image_ids ) ) {
			// All done — clean up transient
			delete_transient( 'atai_bulk_' . $batch_id );
			wp_send_json_success( [ 'done' => true ] );
		}

		$attachment_id = intval( $image_ids[ $index ] );
		$result = ATAI_API::generate( $attachment_id );

		if ( ! $result['success'] ) {
			wp_send_json_success( [
				'done'          => false,
				'index'         => $index,
				'attachment_id' => $attachment_id,
				'error'         => $result['error'],
				'usage'         => $result['usage'] ?? [],
				'title'         => get_the_title( $attachment_id ),
			] );
			return;
		}

		// Save alt text
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $result['alt_text'] ) );

		wp_send_json_success( [
			'done'          => false,
			'index'         => $index,
			'attachment_id' => $attachment_id,
			'alt_text'      => $result['alt_text'],
			'usage'         => $result['usage'],
			'title'         => get_the_title( $attachment_id ),
		] );
	}
}
