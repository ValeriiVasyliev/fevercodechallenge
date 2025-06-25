<?php
/**
 * API class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge;

use Exception;

/**
 * Class API
 */
class API {
	/**
	 * Transient expiration time in seconds.
	 */
	private const TRANSIENT_EXPIRATION = 600; // 10 minutes

	private const TRANSIENT = 'fever_code_challenge_items';

	private const END_POINT_REMOTE = 'https://pokeapi.co/api/v2/';

	/**
	 * Get items form remote server.
	 *
	 * @param boolean $force The param for forcing data from API.
	 * @return array
	 *
	 * @throws \JsonException JsonException.
	 */
	public function get_items( $force = false ): array {

		$items = get_transient( self::TRANSIENT );

		if ( false === $items || true === $force ) {
			try {
				$response = wp_remote_get( self::END_POINT_REMOTE );
				if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
					$items = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );
					if ( json_last_error() === JSON_ERROR_NONE ) {
						$items = $this->sanitize_data( $items );
						set_transient( self::TRANSIENT, $items, self::TRANSIENT_EXPIRATION );
					}
				} else {
					return false;
				}
                // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( Exception $ex ) {
				// Handle Exception.
			}
		}

		return $items;
	}

	/**
	 * Recursive function to sanitize data in an array.
	 *
	 * @param array|string $data Data to sanitize.
	 * @return array|string
	 */
	public function sanitize_data( $data ) {
		if ( ! is_array( $data ) ) {
			// Use sanitize_text_field for strings, but decode and encode to preserve encoded characters.
			return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
		}

		$filtered = [];
		foreach ( $data as $key => $value ) {
			// Recursively sanitize keys and values
			$filtered[ $this->sanitize_data( $key ) ] = $this->sanitize_data( $value );
		}

		return $filtered;
	}
}

