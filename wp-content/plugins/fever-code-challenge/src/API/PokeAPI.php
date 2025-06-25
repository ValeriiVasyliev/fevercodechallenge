<?php
/**
 * PokeAPI class to interact with the PokéAPI.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\API;

use Exception;
use FeverCodeChallenge\Interfaces\IAPI;

class PokeAPI implements IAPI {

	private const TRANSIENT_EXPIRATION    = 600;
	private const TRANSIENT_LIST          = 'fever_pokeapi_list';
	private const TRANSIENT_DETAIL_PREFIX = 'fever_pokeapi_detail_';
	private const BASE_ENDPOINT           = 'https://pokeapi.co/api/v2/';

	/**
	 * Get a list of Pokémon (names + URLs).
	 *
	 * @param bool $force Force refresh.
	 * @return array
	 */
	public function get_list( bool $force = false ): array {
		$url = self::BASE_ENDPOINT . 'pokemon?limit=20';
		return $this->fetch_and_cache( $url, self::TRANSIENT_LIST, $force );
	}

	/**
	 * Get detailed info about a single Pokémon.
	 *
	 * @param string $name_or_id Pokémon name or ID.
	 * @param bool   $force      Force refresh.
	 * @return array
	 */
	public function get_details( string $name_or_id, bool $force = false ): array {
		$url           = self::BASE_ENDPOINT . 'pokemon/' . strtolower( $name_or_id );
		$transient_key = self::TRANSIENT_DETAIL_PREFIX . strtolower( $name_or_id );
		return $this->fetch_and_cache( $url, $transient_key, $force );
	}

	/**
	 * Default method to retrieve items (points to get_list).
	 *
	 * @param bool $force Force refresh.
	 * @return array
	 */
	public function get_items( bool $force = false ): array {
		return $this->get_list( $force );
	}

	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data to sanitize.
	 * @return mixed
	 */
	public function sanitize_data( $data ) {
		if ( ! is_array( $data ) ) {
			return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
		}

		$filtered = [];
		foreach ( $data as $key => $value ) {
			$filtered[ $this->sanitize_data( $key ) ] = $this->sanitize_data( $value );
		}

		return $filtered;
	}

	/**
	 * Fetch and cache data from a given API URL.
	 *
	 * @param string $url           API endpoint.
	 * @param string $transient_key Cache key.
	 * @param bool   $force         Force refresh from API.
	 * @return array
	 */
	private function fetch_and_cache( string $url, string $transient_key, bool $force = false ): array {
		$cached = get_transient( $transient_key );

		if ( false === $cached || $force ) {
			try {
				$response = wp_remote_get( $url );

				if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
					$data = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );
					$data = $this->sanitize_data( $data );
					set_transient( $transient_key, $data, self::TRANSIENT_EXPIRATION );
					return $data;
				}
			} catch ( Exception $ex ) {
				// Optionally log error
				return [];
			}
		}

		return $cached;
	}
}
