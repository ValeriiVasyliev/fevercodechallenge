<?php
/**
 * Interface for general API interaction.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\Interfaces;

interface IAPI {

	/**
	 * Get a list of items from the API.
	 *
	 * @param bool $force Force refresh from remote API.
	 * @return array
	 */
	public function get_list( bool $force = false ): array;

	/**
	 * Get details about a specific item (e.g. Pokémon).
	 *
	 * @param string $name_or_id The item name or ID.
	 * @param bool   $force      Force refresh from remote API.
	 * @return array
	 */
	public function get_details( string $name_or_id, bool $force = false ): array;

	/**
	 * Default method to retrieve items (could be mapped to get_list).
	 *
	 * @param bool $force Force refresh from remote API.
	 * @return array
	 */
	public function get_items( bool $force = false ): array;

	/**
	 * Recursively sanitize data.
	 *
	 * @param mixed $data Raw data.
	 * @return mixed Sanitized data.
	 */
	public function sanitize_data( $data );
}
