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

		$custom_fields  = get_post_custom();
		$custom_content = '<div class="pokemon-custom-fields">';

		foreach ( $custom_fields as $key => $values ) {
			if ( is_protected_meta( $key, 'pokemon' ) ) {
				continue;
			}

			$label = ucwords( str_replace( '_', ' ', $key ) );

			if ( 'pokedex_numbers' === $key ) {
				$pokedex_numbers = maybe_unserialize( $values[0] ?? '' );

				if ( is_array( $pokedex_numbers ) ) {
					$custom_content .= '<p><strong>' . esc_html( $label ) . ':</strong> ';

					$pokedex_items = array_map(
						function ( $entry ) {
							return sprintf(
								'<span class="pokedex-number">%s: %s</span>',
								esc_html( $entry['entry_number'] ?? '' ),
								esc_html( $entry['pokedex_name'] ?? '' )
							);
						},
						$pokedex_numbers
					);

					$custom_content .= implode( ', ', $pokedex_items ) . '</p>';
					continue;
				}
			}

			// Default output for other fields.
			$custom_content .= sprintf(
				'<p><strong>%s:</strong> %s</p>',
				esc_html( $label ),
				esc_html( implode( ', ', (array) $values ) )
			);
		}

		$custom_content .= '</div>';
		return $content . $custom_content;
	}
}
