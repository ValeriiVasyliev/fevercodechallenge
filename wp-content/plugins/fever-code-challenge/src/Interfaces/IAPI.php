<?php
/**
 * Interface for general API interaction.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\Interfaces;

interface IAPI {

	/**
	 * Get a list of items from the API with optional pagination.
	 *
	 * @param int  $limit  Number of items to retrieve.
	 * @param int  $offset Offset for pagination.
	 * @param bool $force  Whether to force refresh from the remote API.
	 * @return array
	 */
	public function get_list( int $limit = 20, int $offset = 0, bool $force = false ): array;

	/**
	 * Get structured Pokemon data (merged + normalized).
	 *
	 * @param string $name_or_id Pokémon name or ID.
	 * @param bool   $force      Whether to force refresh from the API.
	 * @return array
	 */
	public function get_pokemon_data( string $name_or_id, bool $force = false ): array;

	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data.
	 * @return mixed Sanitized data.
	 */
	public function sanitize_data( $data );
}
