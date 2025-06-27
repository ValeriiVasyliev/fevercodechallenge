<?php
/**
 * PokeAPI class to interact with the PokéAPI.
 *
 * @package FeverCodeChallenge
 */

namespace FeverCodeChallenge\API;

use Exception;
use FeverCodeChallenge\Interfaces\IAPI;

/**
 * Class PokeAPI
 *
 * Handles interaction with the PokéAPI including fetching and caching.
 */
class PokeAPI implements IAPI {

	private const TRANSIENT_EXPIRATION    = 600;
	private const TRANSIENT_LIST          = 'fever_pokeapi_list';
	private const TRANSIENT_DETAIL_PREFIX = 'fever_pokeapi_detail_';
	private const BASE_ENDPOINT           = 'https://pokeapi.co/api/v2/';

	/**
	 * Get a list of Pokémon (names + URLs) with optional pagination.
	 *
	 * @param int  $limit  Number of Pokémon to retrieve.
	 * @param int  $offset Offset for pagination.
	 * @param bool $force  Whether to force refresh the cache.
	 * @return array
	 */
	public function get_list( int $limit = 20, int $offset = 0, bool $force = false ): array {
		$url = add_query_arg(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			),
			self::BASE_ENDPOINT . 'pokemon'
		);

		$cache_key = self::TRANSIENT_LIST . "_{$limit}_{$offset}";

		return $this->fetch_and_cache( $url, $cache_key, $force );
	}

	/**
	 * Get structured Pokemon data (merged + normalized).
	 *
	 * @param string $name_or_id Pokémon name or ID.
	 * @param bool   $force      Whether to force refresh the cache.
	 * @return array
	 */
	public function get_pokemon_data( string $name_or_id, bool $force = false ): array {
		$details = $this->get_details( $name_or_id, $force );
		$species = $this->get_species( $name_or_id, $force );

		if ( empty( $details ) || empty( $species ) ) {
			return array();
		}

		// Image.
		$image_url = $details['sprites']['other']['official-artwork']['front_default'] ?? '';

		// Types.
		$types          = array_map( fn( $t ) => $t['type']['name'] ?? '', $details['types'] ?? array() );
		$primary_type   = $types[0] ?? '';
		$secondary_type = $types[1] ?? '';

		// Description.
		$description = '';
		foreach ( $species['flavor_text_entries'] ?? array() as $entry ) {
			if ( 'en' === $entry['language']['name'] ) { // Yoda condition applied.
				$description = $entry['flavor_text'];
				break;
			}
		}

		// Pokedex numbers.
		$pokedex_numbers = array();
		foreach ( $species['pokedex_numbers'] ?? array() as $dex_info ) {
			if ( ! empty( $dex_info['entry_number'] ) && ! empty( $dex_info['pokedex']['name'] ) ) {
				$pokedex_numbers[] = array(
					'entry_number' => $dex_info['entry_number'],
					'pokedex_name' => $dex_info['pokedex']['name'],
				);
			}
		}

		usort(
			$pokedex_numbers,
			fn( $a, $b ) => strcmp( $a['pokedex_name'], $b['pokedex_name'] )
		);

		$oldest_dex = $pokedex_numbers[0]['entry_number'] ?? null;
		$newest_dex = $pokedex_numbers[ count( $pokedex_numbers ) - 1 ]['entry_number'] ?? null;

		return array(
			'name'            => $details['name'] ?? '',
			'image_url'       => $image_url,
			'weight'          => $details['weight'] ?? 0,
			'primary_type'    => $primary_type,
			'secondary_type'  => $secondary_type,
			'description'     => $description,
			'pokedex_numbers' => $pokedex_numbers,
			'oldest_dex'      => $oldest_dex,
			'newest_dex'      => $newest_dex,
		);
	}

	/**
	 * Get detailed info about a single Pokémon.
	 *
	 * @param string $name_or_id Pokémon name or ID.
	 * @param bool   $force      Force refresh.
	 * @return array
	 */
	private function get_details( string $name_or_id, bool $force = false ): array {
		$url           = self::BASE_ENDPOINT . 'pokemon/' . strtolower( $name_or_id );
		$transient_key = self::TRANSIENT_DETAIL_PREFIX . strtolower( $name_or_id );

		return $this->fetch_and_cache( $url, $transient_key, $force );
	}

	/**
	 * Get a list of Pokémon species.
	 *
	 * @param string $name_or_id Species name or ID.
	 * @param bool   $force      Force refresh.
	 * @return array
	 */
	private function get_species( string $name_or_id, bool $force = false ): array {
		$url           = self::BASE_ENDPOINT . 'pokemon-species/' . strtolower( $name_or_id );
		$transient_key = self::TRANSIENT_DETAIL_PREFIX . 'species_' . strtolower( $name_or_id );

		return $this->fetch_and_cache( $url, $transient_key, $force );
	}

	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data to sanitize.
	 * @return mixed
	 */
	public function sanitize_data( $data ) {
		if ( is_array( $data ) ) {
			$filtered = array();

			foreach ( $data as $key => $value ) {
				$sanitized_key              = is_string( $key ) ? $this->sanitize_data( $key ) : $key;
				$filtered[ $sanitized_key ] = $this->sanitize_data( $value );
			}

			return $filtered;
		}

		// Only sanitize strings.
		if ( is_string( $data ) ) {
			return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
		}

		return $data;
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

		if ( false === $cached || true === $force ) {
			try {
				$response = wp_remote_get( $url );

				if (
					! is_wp_error( $response ) &&
					200 === wp_remote_retrieve_response_code( $response )
				) {
					$data = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );

					if ( is_array( $data ) ) {
						$data = $this->sanitize_data( $data );
						set_transient( $transient_key, $data, self::TRANSIENT_EXPIRATION );
						return $data;
					}
				}
			} catch ( Exception $ex ) {
				// Log the error.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
                    // phpcs:disable WordPress.PHP.DevelopmentFunctions
					error_log( 'PokéAPI error: ' . $ex->getMessage() );
                    // phpcs:enable
				}
			}

			// Return empty array if request failed or data was invalid.
			return array();
		}

		// Ensure cached value is array.
		return is_array( $cached ) ? $cached : array();
	}
}
