<?php
/**
 * API client for the Alt Text AI Cloudflare Worker.
 *
 * @package AltTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ATAI_API {

	/**
	 * Generate alt text for a single attachment.
	 *
	 * @param int $attachment_id WordPress attachment ID.
	 * @return array { success: bool, alt_text?: string, error?: string, usage?: array }
	 */
	public static function generate( int $attachment_id ): array {
		$license_key = get_option( 'atai_license_key', '' );
		if ( empty( $license_key ) ) {
			return [ 'success' => false, 'error' => 'No license key configured.' ];
		}

		// Get image file path
		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return [ 'success' => false, 'error' => 'Image file not found.' ];
		}

		// Use medium size if available to reduce payload
		$medium = image_get_intermediate_size( $attachment_id, 'medium' );
		if ( $medium ) {
			$upload_dir = wp_upload_dir();
			$medium_path = trailingslashit( $upload_dir['basedir'] ) . $medium['path'];
			if ( file_exists( $medium_path ) ) {
				$file_path = $medium_path;
			}
		}

		// Read and encode
		$image_data = file_get_contents( $file_path );
		if ( ! $image_data ) {
			return [ 'success' => false, 'error' => 'Could not read image file.' ];
		}

		$mime_type = mime_content_type( $file_path ) ?: 'image/jpeg';
		$base64    = 'data:' . $mime_type . ';base64,' . base64_encode( $image_data );

		// Build context
		$context = [
			'filename' => basename( $file_path ),
			'industry' => get_option( 'atai_industry', 'general' ),
			'location' => get_option( 'atai_location', '' ),
		];

		// Call API
		$response = wp_remote_post( ATAI_API_BASE . '/generate', [
			'timeout' => 30,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'license_key' => $license_key,
				'image'       => $base64,
				'context'     => $context,
			] ),
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'error' => $response->get_error_message() ];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 401 ) {
			return [ 'success' => false, 'error' => 'Invalid license key.' ];
		}
		if ( $code === 402 ) {
			return [ 'success' => false, 'error' => 'Monthly quota exceeded.', 'usage' => $body['usage'] ?? [] ];
		}
		if ( $code !== 200 || empty( $body['success'] ) ) {
			return [ 'success' => false, 'error' => $body['error'] ?? 'Unknown API error.' ];
		}

		return [
			'success'  => true,
			'alt_text' => $body['alt_text'],
			'usage'    => $body['usage'] ?? [],
		];
	}

	/**
	 * Fetch current usage stats.
	 *
	 * @return array { used, limit, tier, resets } or error array.
	 */
	public static function get_usage(): array {
		$license_key = get_option( 'atai_license_key', '' );
		if ( empty( $license_key ) ) {
			return [ 'error' => 'No license key configured.' ];
		}

		$url = add_query_arg( 'license_key', $license_key, ATAI_API_BASE . '/usage' );
		$response = wp_remote_get( $url, [ 'timeout' => 10 ] );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $body ?: [ 'error' => 'Invalid response.' ];
	}
}
