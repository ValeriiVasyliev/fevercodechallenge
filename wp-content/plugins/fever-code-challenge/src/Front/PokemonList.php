<?php
/**
 * PokemonList class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\Front;

use FeverCodeChallenge\Plugin;

/**
 * Class PokemonList
 */
class PokemonList {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize the class.
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register class hooks.
	 */
	protected function register_hooks(): void {
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_display_pokemon_list_page' ) );
	}

	/**
	 * Add 'pokemon_list' to the list of query vars.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'pokemon_list';
		return $vars;
	}

	/**
	 * Conditionally display the Pokemon list page.
	 */
	public function maybe_display_pokemon_list_page(): void {
		if ( get_query_var( 'pokemon_list' ) ) {

			wp_register_script(
				'fever-pokemon-list',
				$this->plugin->plugin_url() . '/dist/front-pokemon-list.js',
				array( 'wp-i18n' ),
				filemtime( $this->plugin->plugin_dir() . '/dist/front-pokemon-list.js' ),
				true
			);

			// Localize the script with action.
			wp_localize_script(
				'fever-pokemon-list',
				'feverPokemonList',
				array(
					'rest_url' => rest_url( 'fever-code-challenge/v1/pokemon' ),
					'per_page' => 6, // Number of Pokémon per page.
				)
			);

			// Enqueue the script.
			wp_enqueue_script( 'fever-pokemon-list' );

			// Get the selected type from the query var.
			$unique_types = $this->get_unique_type_meta_values();

			$template = locate_template( 'fever-code-challenge/pokemon-list.php' );
			if ( ! $template ) {
				$template = $this->plugin->plugin_dir() . '/templates/pokemon-list.php';
			}

			include $template;
			exit();
		}
	}

	/**
	 * Get unique values for 'primary_type' and 'secondary_type' from published 'pokemon' posts.
	 *
	 * @return string[] Array of unique type values.
	 */
	protected function get_unique_type_meta_values(): array {
		global $wpdb;

		$meta_keys = array( 'primary_type', 'secondary_type' );
		$post_type = 'pokemon';

		$query = $wpdb->prepare(
			"
            SELECT DISTINCT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE (pm.meta_key = %s OR pm.meta_key = %s)
              AND p.post_type = %s
              AND p.post_status = 'publish'
              AND pm.meta_value != ''
            ORDER BY pm.meta_value ASC
            ",
			$meta_keys[0],
			$meta_keys[1],
			$post_type
		);

		return $wpdb->get_col( $query );
	}
}
