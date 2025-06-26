<?php
/**
 * Front class file.
 *
 * @package fever-code-challenge
 */

namespace FeverCodeChallenge;

/**
 * Class Front
 *
 * Handles all public-facing functionality of the plugin.
 */
class Front {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Main plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize front-end logic.
	 */
	public function init(): void {
		$this->register_hooks();

		( new Front\Pokemon( $this->plugin ) )->init();
		( new Front\PokemonList( $this->plugin ) )->init();
		( new Front\PokemonGenerate( $this->plugin ) )->init();
		( new Front\PokemonRandom( $this->plugin ) )->init();
	}

	/**
	 * Register front-end specific hooks.
	 */
	protected function register_hooks(): void {

		// Enqueue front-end assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue front-end styles and scripts.
	 */
	public function enqueue_assets(): void {

		if ( FEVER_CODE_CHALLENGE_DEBUG ) {
			wp_enqueue_style(
				'fever-code-challenge-style',
				$this->plugin->plugin_url() . '/assets/css/style.css',
				[],
				filemtime( $this->plugin->plugin_dir() . '/assets/css/style.css' )
			);
		} else {
			wp_enqueue_style(
				'fever-code-challenge-style',
				$this->plugin->plugin_url() . '/assets/css/style.css',
				[],
				filemtime( $this->plugin->plugin_dir() . '/assets/css/style.css' )
			);
		}
	}
}
