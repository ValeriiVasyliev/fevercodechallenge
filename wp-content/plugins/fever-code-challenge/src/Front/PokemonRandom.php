<?php
/**
 * PokemonRandom class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge\Front;

use FeverCodeChallenge\Plugin;

/**
 * Class PokemonRandom
 */
class PokemonRandom {
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
		add_action( 'template_redirect', [ $this, 'maybe_redirect_random_pokemon' ] );
	}

	/**
	 * Register the /random rewrite rule.
	 */
	public function register_rewrite_rule(): void {
		add_rewrite_rule( '^random/?$', 'index.php?random_pokemon=1', 'top' );
	}

	/**
	 * Add 'random_pokemon' to the list of query vars.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'random_pokemon';
		return $vars;
	}

	/**
	 * Handle redirect if 'random_pokemon' query var is set.
	 */
	public function maybe_redirect_random_pokemon(): void {
		if ( get_query_var( 'random_pokemon' ) ) {

			if ( FEVER_CODE_CHALLENGE_DEBUG ) {
				wp_register_script(
					'fever_code_challenge-front-pokemon-random',
					$this->plugin->plugin_url() . '/assets/js/front-pokemon-random.js',
					[ 'wp-i18n' ],
					filemtime( $this->plugin->plugin_dir() . '/assets/js/front-pokemon-random.js' ),
					true
				);
			} else {
				wp_register_script(
					'fever_code_challenge-front-pokemon-random',
					$this->plugin->plugin_url() . '/dist/front-pokemon-random.js',
					[ 'wp-i18n' ],
					filemtime( $this->plugin->plugin_dir() . '/dist/front-pokemon-random.js', ),
					true
				);
			}

			// Localize the script with nonce and action.
			wp_localize_script(
				'fever_code_challenge-front-pokemon-random',
				'feverCodeChallengeFrontPokemonRandom',
				[
					'rest_url' => rest_url( 'fever-code-challenge/v1/pokemon' ),
				]
			);

			wp_enqueue_script( 'fever_code_challenge-front-pokemon-random' );

			$template = locate_template( 'fever-code-challenge/random-pokemon.php' );
			if ( ! $template ) {
				$template = $this->plugin->plugin_dir() . '/templates/random-pokemon.php';
			}
			include $template;

			exit();
		}
	}
}
