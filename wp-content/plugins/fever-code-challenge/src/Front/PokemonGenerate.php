<?php
/**
 * PokemonGenerate class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\Front;

use FeverCodeChallenge\Plugin;

/**
 * Class PokemonGenerate
 */
class PokemonGenerate {
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
		add_action( 'init', [ $this, 'register_rewrite_rule' ] );
		add_filter( 'query_vars', [ $this, 'add_query_var' ] );
		add_action( 'template_redirect', [ $this, 'maybe_generate_pokemon' ] );
	}

	/**
	 * Register the /generate rewrite rule.
	 */
	public function register_rewrite_rule(): void {
		add_rewrite_rule( '^generate/?$', 'index.php?generate_pokemon=1', 'top' );
	}

	/**
	 * Add 'generate_pokemon' to the list of query vars.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'generate_pokemon';
		return $vars;
	}

	/**
	 * Handle the /generate route logic.
	 */
	public function maybe_generate_pokemon(): void {
		if ( get_query_var( 'generate_pokemon' ) ) {

			if ( FEVER_CODE_CHALLENGE_DEBUG ) {
				wp_register_script(
					'fever_code_challenge-front-pokemon-generate',
					$this->plugin->plugin_url() . '/assets/js/front-pokemon-generate.js',
					[ 'wp-i18n' ],
					filemtime( $this->plugin->plugin_dir() . '/assets/js/front-pokemon-generate.js' ),
					true
				);
			} else {
				wp_register_script(
					'fever_code_challenge-front-pokemon-generate',
					$this->plugin->plugin_url() . '/dist/front-pokemon-generate.js',
					[ 'wp-i18n' ],
					filemtime( $this->plugin->plugin_dir() . '/dist/front-pokemon-generate.js', ),
					true
				);
			}

			// Localize the script with nonce and action.
			wp_localize_script(
				'fever_code_challenge-front-pokemon-generate',
				'feverCodeChallengeFrontPokemonGenerate',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'create_new_pokemon' ),
				]
			);

			wp_enqueue_script( 'fever_code_challenge-front-pokemon-generate' );

			$template = locate_template( 'fever-code-challenge/generate-pokemon.php' );
			if ( ! $template ) {
				$template = $this->plugin->plugin_dir() . '/templates/generate-pokemon.php';
			}
			include $template;

			exit();
		}
	}
}
