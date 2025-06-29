<?php
/**
 * Pokemon class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\Front;

use FeverCodeChallenge\Plugin;

/**
 * Class Pokemon
 */
class Pokemon {

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
		$this->hooks();
	}

	/**
	 * Register class hooks.
	 */
	protected function hooks(): void {
		// Extend content for pokemon single page with custom fields.
		add_filter( 'the_content', array( $this, 'extend_pokemon_content' ) );

		// Enqueue scripts for handling the oldest dex entry button.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Extend the content of a single Pokemon post with custom fields.
	 *
	 * @param string $content The original content.
	 * @return string The modified content.
	 */
	public function extend_pokemon_content( string $content ): string {
		if ( ! is_singular( 'pokemon' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();

		// Retrieve and format Pokedex numbers.
		$raw_pokedex_entries = get_post_meta( $post_id, 'pokedex_numbers', true );
		$pokedex_map         = array();

		if ( is_array( $raw_pokedex_entries ) ) {
			foreach ( $raw_pokedex_entries as $entry ) {
				if ( isset( $entry['entry_number'], $entry['pokedex_name'] ) ) {
					$pokedex_map[ $entry['entry_number'] ] = $entry['pokedex_name'];
				}
			}
		}

		// Begin building custom content HTML.
		$custom_content = '<div class="pokemon-custom-fields">';

		$custom_fields = get_post_custom( $post_id );

		$newest_dex = '';

		foreach ( $custom_fields as $key => $values ) {
			if ( is_protected_meta( $key, 'pokemon' ) ) {
				continue;
			}

			// Skip fields we handle separately or don't need.
			if ( in_array( $key, array( 'pokedex_numbers', 'oldest_dex' ), true ) ) {
				continue;
			}

			$label         = ucwords( str_replace( '_', ' ', $key ) );
			$display_value = esc_html( implode( ', ', (array) $values ) );

			$custom_content .= sprintf(
				'<p><strong>%s:</strong> %s</p>',
				esc_html( $label ),
				$display_value
			);

			if ( 'newest_dex' === $key && isset( $values[0] ) && isset( $pokedex_map[ $values[0] ] ) ) {
				if ( 'newest_dex' === $key && isset( $values[0] ) && isset( $pokedex_map[ $values[0] ] ) ) {
					$custom_content .= sprintf(
						'<p><strong>%s:</strong> %s</p>',
						__( 'Newest Dex Entry', 'fever-code-challenge' ),
						esc_html( $pokedex_map[ $values[0] ] )
					);
				}

				$newest_dex = $values[0];
			}
		}

		// Add HTML button to get podex oldest dex entry.

		if ( ! empty( $newest_dex ) ) {
			$custom_content .= '<p class="pokemon-oldest-dex">';
			$custom_content .= sprintf(
				'<button type="button" class="button button-secondary pokemon-oldest-dex-button" data-id="%d">%s</button>',
				esc_attr( $newest_dex ),
				esc_html__( 'Get Oldest Dex Entry', 'fever-code-challenge' )
			);
			$custom_content .= '</p>';
		}

		$custom_content .= '</div>';

		return $content . $custom_content;
	}

	/**
	 * Enqueue scripts for the Pokemon single page.
	 */
	public function enqueue_scripts(): void {
		if ( is_singular( 'pokemon' ) && is_main_query() ) {
			wp_register_script(
				'fever_code_challenge-front-pokemon',
				$this->plugin->plugin_url() . '/dist/front-pokemon.js',
				array( 'wp-i18n' ),
				filemtime( $this->plugin->plugin_dir() . '/dist/front-pokemon.js' ),
				1
			);

			// Localize the script with action.
			wp_localize_script(
				'fever_code_challenge-front-pokemon',
				'feverCodeChallengeFrontPokemon',
				array(
					'rest_url' => rest_url( 'fever-code-challenge/v1/pokemon' ),
				)
			);

			// Enqueue the script.
			wp_enqueue_script( 'fever_code_challenge-front-pokemon' );
		}
	}
}
